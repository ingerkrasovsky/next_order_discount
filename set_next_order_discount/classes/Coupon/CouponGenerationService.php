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
 * Orchestrates coupon generation for a source order:
 * idempotency -> eligibility -> code -> CartRule -> coupon link (status "created").
 *
 * The service is decoupled from hooks and controllers: it receives an order
 * context array and reads the discount settings in the order's shop scope.
 * A single coupon is guaranteed per source order: a pre-check short-circuits
 * repeated calls, and the unique (id_shop, id_order_source) index guards against
 * concurrent races. If persisting the link fails after the CartRule was created,
 * the orphan voucher is deactivated so no unlinked, usable coupon is left behind.
 */
class SnodCouponGenerationService
{
    public const MAX_VALIDITY_DAYS = 3650;

    private $eligibilityResolver;
    private $cartRuleAdapter;
    private $couponLinkRepository;

    /**
     * @param SnodCouponEligibilityResolver $eligibilityResolver
     * @param SnodCartRuleAdapter           $cartRuleAdapter
     * @param SnodCouponLinkRepository      $couponLinkRepository
     */
    public function __construct(
        SnodCouponEligibilityResolver $eligibilityResolver,
        SnodCartRuleAdapter $cartRuleAdapter,
        SnodCouponLinkRepository $couponLinkRepository
    ) {
        $this->eligibilityResolver = $eligibilityResolver;
        $this->cartRuleAdapter = $cartRuleAdapter;
        $this->couponLinkRepository = $couponLinkRepository;
    }

    /**
     * Runs the full generation pipeline for one order context.
     *
     * Expected context keys:
     *  - id_shop, id_shop_group, id_customer, id_order_source (int)
     *  - id_order_state (int), order_is_paid (bool), order_total_paid (float)
     *  - id_currency (int), optional voucher_name (string)
     *
     * @param array $context
     *
     * @return array result: success (bool), reason (string), reasons (string[]),
     *               id_coupon_link (int), id_cart_rule (int), code (string)
     */
    public function generateForOrderContext(array $context)
    {
        $idShop = isset($context['id_shop']) ? (int) $context['id_shop'] : 0;
        $idCustomer = isset($context['id_customer']) ? (int) $context['id_customer'] : 0;
        $idOrderSource = isset($context['id_order_source']) ? (int) $context['id_order_source'] : 0;

        $idCartRule = 0;

        try {
            // Idempotency guard: never issue a second coupon for the same source
            // order. Handles repeated hook/cron retries. Concurrent races are
            // additionally caught by the unique (id_shop, id_order_source) index
            // at insert time, which rolls back the transient voucher below.
            if ($idOrderSource > 0) {
                $existing = $this->couponLinkRepository->findByShopAndOrder($idShop, $idOrderSource);
                if ($existing !== null) {
                    return $this->existingResult($existing);
                }
            }

            $eligibility = $this->eligibilityResolver->evaluate($context);
            if (empty($eligibility['eligible'])) {
                return $this->failure('not_eligible', ['reasons' => $eligibility['reasons']]);
            }

            $generator = new SnodCouponCodeGenerator($idShop);
            $code = $generator->generate();
            if ($code === '') {
                return $this->failure('code_generation_failed');
            }

            $now = date('Y-m-d H:i:s');
            $validityDays = $this->getValidityDays($idShop);
            $validTo = date('Y-m-d H:i:s', strtotime('+' . $validityDays . ' days'));

            $idCurrency = isset($context['id_currency']) ? (int) $context['id_currency'] : 0;
            if ($idCurrency <= 0) {
                $idCurrency = (int) Configuration::get('PS_CURRENCY_DEFAULT');
            }

            $idCartRule = $this->cartRuleAdapter->create([
                'code' => $code,
                'id_customer' => $idCustomer,
                'id_shop' => $idShop,
                'id_currency' => $idCurrency,
                'name' => isset($context['voucher_name']) ? (string) $context['voucher_name'] : '',
                'discount_type' => $this->getDiscountType($idShop),
                'discount_value' => $this->getDiscountValue($idShop),
                'minimum_amount' => $this->getMinimumNextOrderAmount($idShop),
                'date_from' => $now,
                'date_to' => $validTo,
                'quantity' => 1,
                'quantity_per_user' => 1,
            ]);

            if ($idCartRule <= 0) {
                return $this->failure('cart_rule_failed', ['code' => $code]);
            }

            $idLink = $this->couponLinkRepository->insert([
                'id_shop' => $idShop,
                'id_shop_group' => isset($context['id_shop_group']) ? (int) $context['id_shop_group'] : 0,
                'id_customer' => $idCustomer,
                'id_order_source' => $idOrderSource,
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

                // A concurrent request may have won the race and inserted the
                // coupon first (the unique index rejected ours). Report that as
                // an idempotent hit rather than a failure.
                if ($idOrderSource > 0) {
                    $winner = $this->couponLinkRepository->findByShopAndOrder($idShop, $idOrderSource);
                    if ($winner !== null) {
                        return $this->existingResult($winner);
                    }
                }

                return $this->failure('link_insert_failed', [
                    'code' => $code,
                    'id_cart_rule' => $idCartRule,
                ]);
            }

            return [
                'success' => true,
                'reason' => 'ok',
                'reasons' => [],
                'id_coupon_link' => $idLink,
                'id_cart_rule' => $idCartRule,
                'code' => $code,
            ];
        } catch (Exception $e) {
            // This service runs inside order hooks: an unexpected error (e.g. a
            // DB exception thrown in debug mode) must never break the order
            // flow. Roll back any created voucher and report a clean failure.
            if ($idCartRule > 0) {
                $this->cartRuleAdapter->deactivate($idCartRule);
            }

            return $this->failure('exception');
        }
    }

    /**
     * Builds a successful result that points at an already-issued coupon, used
     * when the idempotency guard detects the order was processed before.
     *
     * @param array $existing a ps_snod_coupon_link row
     *
     * @return array
     */
    private function existingResult(array $existing)
    {
        return [
            'success' => true,
            'reason' => 'already_exists',
            'reasons' => [],
            'id_coupon_link' => (int) $existing['id_snod_coupon_link'],
            'id_cart_rule' => (int) $existing['id_cart_rule'],
            'code' => (string) $existing['coupon_code'],
        ];
    }

    /**
     * @param int $idShop
     *
     * @return string 'percent' or 'amount'
     */
    private function getDiscountType($idShop)
    {
        $type = (string) $this->getConfig('SNOD_DISCOUNT_TYPE', $idShop);

        return in_array($type, ['percent', 'amount'], true) ? $type : 'percent';
    }

    /**
     * @param int $idShop
     *
     * @return float
     */
    private function getDiscountValue($idShop)
    {
        $value = (float) str_replace(',', '.', (string) $this->getConfig('SNOD_DISCOUNT_VALUE', $idShop));

        return max(0.0, $value);
    }

    /**
     * @param int $idShop
     *
     * @return int number of days, clamped to [1, MAX_VALIDITY_DAYS]
     */
    private function getValidityDays($idShop)
    {
        $days = (int) $this->getConfig('SNOD_VALIDITY_DAYS', $idShop);
        if ($days < 1) {
            $days = 1;
        }

        return min($days, self::MAX_VALIDITY_DAYS);
    }

    /**
     * @param int $idShop
     *
     * @return float
     */
    private function getMinimumNextOrderAmount($idShop)
    {
        $amount = (float) str_replace(',', '.', (string) $this->getConfig('SNOD_MIN_ORDER_AMOUNT', $idShop));

        return max(0.0, $amount);
    }

    /**
     * Reads a configuration value in the order's shop scope, falling back to the
     * global value when no shop is provided.
     *
     * @param string $key
     * @param int    $idShop
     *
     * @return mixed
     */
    private function getConfig($key, $idShop)
    {
        $idShop = (int) $idShop;
        if ($idShop > 0) {
            return Configuration::get($key, null, null, $idShop);
        }

        return Configuration::get($key);
    }

    /**
     * Builds a standardized failure result.
     *
     * @param string $reason
     * @param array  $extra
     *
     * @return array
     */
    private function failure($reason, array $extra = [])
    {
        return array_merge(
            [
                'success' => false,
                'reason' => $reason,
                'reasons' => [],
                'id_coupon_link' => 0,
                'id_cart_rule' => 0,
                'code' => '',
            ],
            $extra
        );
    }
}
