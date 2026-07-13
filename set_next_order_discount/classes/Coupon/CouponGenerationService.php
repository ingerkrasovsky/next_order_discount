<?php
/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to a custom commercial license.
 * You may not redistribute, resell, sublicense, or share this file.
 * One license is valid for one installation (one store).
 *
 * For full license terms, contact: info@setecom.tech
 *
 * @author    Smart Ecommerce Tech
 * @copyright 2026 Smart Ecommerce Tech
 * @license   Commercial License
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Orchestrates coupon generation for a source order using the rule engine.
 *
 * The matcher returns the rules that apply to the order (in priority order,
 * honoring stop_further). One coupon is issued per matched rule, each with that
 * rule's own discount outcome. Idempotency is per (shop, order, rule): repeated
 * calls skip rules already issued, and the unique index guards concurrent
 * races. Every rule is processed in isolation and exception-safe, so one rule's
 * failure never affects the others and a coupon can never break order flow.
 */
class SnodCouponGenerationService
{
    public const MAX_VALIDITY_DAYS = 3650;

    private $ruleMatcher;
    private $cartRuleAdapter;
    private $couponLinkRepository;

    /**
     * @param SnodRuleMatcher          $ruleMatcher
     * @param SnodCartRuleAdapter      $cartRuleAdapter
     * @param SnodCouponLinkRepository $couponLinkRepository
     */
    public function __construct(
        SnodRuleMatcher $ruleMatcher,
        SnodCartRuleAdapter $cartRuleAdapter,
        SnodCouponLinkRepository $couponLinkRepository
    ) {
        $this->ruleMatcher = $ruleMatcher;
        $this->cartRuleAdapter = $cartRuleAdapter;
        $this->couponLinkRepository = $couponLinkRepository;
    }

    /**
     * Generates a coupon for every rule that matches the order context.
     *
     * @param array $context see SnodRuleMatcher for the expected keys, plus
     *                       id_customer, id_order_source, id_shop_group,
     *                       id_currency and an optional voucher_name
     *
     * @return array 'coupons' => one result row per matched rule
     */
    public function generateForOrderContext(array $context)
    {
        $idShop = isset($context['id_shop']) ? (int) $context['id_shop'] : 0;
        $idCustomer = isset($context['id_customer']) ? (int) $context['id_customer'] : 0;
        $idOrderSource = isset($context['id_order_source']) ? (int) $context['id_order_source'] : 0;

        $results = [];
        foreach ($this->ruleMatcher->match($context) as $rule) {
            $results[] = $this->generateForRule($rule, $context, $idShop, $idCustomer, $idOrderSource);
        }

        return ['coupons' => $results];
    }

    /**
     * Issues (or reuses) a single coupon for one matched rule.
     *
     * @param array $rule
     * @param array $context
     * @param int   $idShop
     * @param int   $idCustomer
     * @param int   $idOrderSource
     *
     * @return array
     */
    private function generateForRule(array $rule, array $context, $idShop, $idCustomer, $idOrderSource)
    {
        $idRule = (int) $rule['id_snod_rule'];

        // Idempotency: never issue a second coupon for the same (order, rule).
        if ($idOrderSource > 0) {
            $existing = $this->couponLinkRepository->findByShopOrderRule($idShop, $idOrderSource, $idRule);
            if ($existing !== null) {
                return $this->existingResult($idRule, $existing);
            }
        }

        $idCartRule = 0;

        try {
            $generator = new SnodCouponCodeGenerator($idShop);
            $code = $generator->generate();
            if ($code === '') {
                return $this->failure($idRule, 'code_generation_failed');
            }

            $now = date('Y-m-d H:i:s');
            $validTo = date('Y-m-d H:i:s', strtotime('+' . $this->validityDays($rule) . ' days'));
            $idCurrency = $this->resolveCurrency($context);

            $idCartRule = $this->cartRuleAdapter->create([
                'code' => $code,
                'id_customer' => $idCustomer,
                'id_shop' => $idShop,
                'id_currency' => $idCurrency,
                'name' => isset($context['voucher_name']) ? (string) $context['voucher_name'] : '',
                'discount_type' => (string) $rule['discount_type'],
                'discount_value' => $rule['discount_value'],
                'minimum_amount' => $rule['next_order_min_amount'],
                'date_from' => $now,
                'date_to' => $validTo,
                'quantity' => 1,
                'quantity_per_user' => 1,
            ]);
            if ($idCartRule <= 0) {
                return $this->failure($idRule, 'cart_rule_failed', ['code' => $code]);
            }

            $idLink = $this->couponLinkRepository->insert([
                'id_shop' => $idShop,
                'id_shop_group' => isset($context['id_shop_group']) ? (int) $context['id_shop_group'] : 0,
                'id_customer' => $idCustomer,
                'id_order_source' => $idOrderSource,
                'id_snod_rule' => $idRule,
                'id_cart_rule' => $idCartRule,
                'coupon_code' => $code,
                'status' => SnodCouponLinkRepository::STATUS_CREATED,
                'valid_from' => $now,
                'valid_to' => $validTo,
                'generated_at' => $now,
            ]);

            if ($idLink <= 0) {
                // Roll back the orphan voucher so no unlinked coupon remains usable.
                $this->cartRuleAdapter->deactivate($idCartRule);

                // A concurrent request may have won the (order, rule) race.
                if ($idOrderSource > 0) {
                    $winner = $this->couponLinkRepository->findByShopOrderRule($idShop, $idOrderSource, $idRule);
                    if ($winner !== null) {
                        return $this->existingResult($idRule, $winner);
                    }
                }

                return $this->failure($idRule, 'link_insert_failed', [
                    'code' => $code,
                    'id_cart_rule' => $idCartRule,
                ]);
            }

            return [
                'success' => true,
                'reason' => 'ok',
                'id_snod_rule' => $idRule,
                'id_coupon_link' => $idLink,
                'id_cart_rule' => $idCartRule,
                'code' => $code,
            ];
        } catch (Exception $e) {
            // A coupon must never break checkout or order status updates.
            if ($idCartRule > 0) {
                $this->cartRuleAdapter->deactivate($idCartRule);
            }

            return $this->failure($idRule, 'exception');
        }
    }

    /**
     * @param array $rule
     *
     * @return int validity in days, clamped to [1, MAX_VALIDITY_DAYS]
     */
    private function validityDays(array $rule)
    {
        $days = (int) $rule['validity_days'];
        if ($days < 1) {
            $days = 1;
        }

        return min($days, self::MAX_VALIDITY_DAYS);
    }

    /**
     * @param array $context
     *
     * @return int
     */
    private function resolveCurrency(array $context)
    {
        $idCurrency = isset($context['id_currency']) ? (int) $context['id_currency'] : 0;
        if ($idCurrency <= 0) {
            $idCurrency = (int) Configuration::get('PS_CURRENCY_DEFAULT');
        }

        return $idCurrency;
    }

    /**
     * @param int   $idRule
     * @param array $existing a ps_snod_coupon_link row
     *
     * @return array
     */
    private function existingResult($idRule, array $existing)
    {
        return [
            'success' => true,
            'reason' => 'already_exists',
            'id_snod_rule' => (int) $idRule,
            'id_coupon_link' => (int) $existing['id_snod_coupon_link'],
            'id_cart_rule' => (int) $existing['id_cart_rule'],
            'code' => (string) $existing['coupon_code'],
        ];
    }

    /**
     * @param int    $idRule
     * @param string $reason
     * @param array  $extra
     *
     * @return array
     */
    private function failure($idRule, $reason, array $extra = [])
    {
        return array_merge(
            [
                'success' => false,
                'reason' => $reason,
                'id_snod_rule' => (int) $idRule,
                'id_coupon_link' => 0,
                'id_cart_rule' => 0,
                'code' => '',
            ],
            $extra
        );
    }
}
