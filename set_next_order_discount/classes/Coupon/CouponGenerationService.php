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
namespace Setecom\NextOrderDiscount\Coupon;

use Exception;
use Setecom\NextOrderDiscount\Logger\ModuleLogger;
use Setecom\NextOrderDiscount\Mail\CouponMailer;
use Setecom\NextOrderDiscount\Queue\QueueService;
use Setecom\NextOrderDiscount\Repository\CouponLinkRepository;
use Setecom\NextOrderDiscount\Rule\RuleMatcher;

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
class CouponGenerationService
{
    public const MAX_VALIDITY_DAYS = 3650;

    /**
     * Log channel for coupon generation events.
     */
    private const LOG_CHANNEL = 'coupon';

    private $ruleMatcher;
    private $cartRuleAdapter;
    private $couponLinkRepository;
    private $queueService;
    private $logger;
    private $couponMailer;

    /**
     * @param RuleMatcher $ruleMatcher
     * @param CartRuleAdapter $cartRuleAdapter
     * @param CouponLinkRepository $couponLinkRepository
     * @param QueueService $queueService
     * @param ModuleLogger|null $logger optional structured logger
     * @param CouponMailer|null $couponMailer optional mailer for immediate
     *                                        delivery; when set the coupon
     *                                        email is sent right away and the
     *                                        queue is only a retry fallback
     */
    public function __construct(
        RuleMatcher $ruleMatcher,
        CartRuleAdapter $cartRuleAdapter,
        CouponLinkRepository $couponLinkRepository,
        QueueService $queueService,
        ?ModuleLogger $logger = null,
        ?CouponMailer $couponMailer = null,
    ) {
        $this->ruleMatcher = $ruleMatcher;
        $this->cartRuleAdapter = $cartRuleAdapter;
        $this->couponLinkRepository = $couponLinkRepository;
        $this->queueService = $queueService;
        $this->logger = $logger;
        $this->couponMailer = $couponMailer;
    }

    /**
     * Generates a coupon for every rule that matches the order context.
     *
     * @param array $context see RuleMatcher for the expected keys, plus
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

        $matchedRules = $this->ruleMatcher->match($context);
        if (empty($matchedRules)) {
            $this->logCoupon(
                ModuleLogger::LEVEL_DEBUG,
                'No matching rule for order',
                ['id_order' => $idOrderSource, 'id_shop' => $idShop, 'id_order_state' => isset($context['id_order_state']) ? (int) $context['id_order_state'] : 0],
                $this->orderCorrelation($idOrderSource),
            );
        }

        $results = [];
        foreach ($matchedRules as $rule) {
            $results[] = $this->generateForRule($rule, $context, $idShop, $idCustomer, $idOrderSource);
        }

        return ['coupons' => $results];
    }

    /**
     * Issues one coupon for a matched rule and logs the outcome.
     *
     * @param array $rule
     * @param array $context
     * @param int $idShop
     * @param int $idCustomer
     * @param int $idOrderSource
     *
     * @return array
     */
    private function generateForRule(array $rule, array $context, $idShop, $idCustomer, $idOrderSource)
    {
        $result = $this->issueForRule($rule, $context, $idShop, $idCustomer, $idOrderSource);
        $this->logResult($result, $rule, $idShop, $idOrderSource, $idCustomer);

        return $result;
    }

    /**
     * Issues (or reuses) a single coupon for one matched rule.
     *
     * @param array $rule
     * @param array $context
     * @param int $idShop
     * @param int $idCustomer
     * @param int $idOrderSource
     *
     * @return array
     */
    private function issueForRule(array $rule, array $context, $idShop, $idCustomer, $idOrderSource)
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
            $generator = new CouponCodeGenerator([
                'length' => isset($rule['code_length']) ? (int) $rule['code_length'] : 0,
                'type' => isset($rule['code_type']) ? (int) $rule['code_type'] : 0,
                'template' => isset($rule['code_template']) ? (string) $rule['code_template'] : '',
            ]);
            $code = $generator->generate();
            if ($code === '') {
                return $this->failure($idRule, 'code_generation_failed');
            }

            $now = date('Y-m-d H:i:s');
            $validTo = date('Y-m-d H:i:s', strtotime('+' . $this->validityDays($rule) . ' days'));
            $idCurrency = $this->resolveCurrency($context);

            // Voucher name: the rule's own value overrides the localized default.
            $voucherName = (isset($rule['voucher_name']) && trim((string) $rule['voucher_name']) !== '')
                ? (string) $rule['voucher_name']
                : (isset($context['voucher_name']) ? (string) $context['voucher_name'] : '');

            $idCartRule = $this->cartRuleAdapter->create([
                'code' => $code,
                'id_customer' => $idCustomer,
                'id_shop' => $idShop,
                'id_currency' => $idCurrency,
                'name' => $voucherName,
                'description' => isset($rule['voucher_description']) ? (string) $rule['voucher_description'] : '',
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
                'status' => CouponLinkRepository::STATUS_CREATED,
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

            // Deliver the coupon email immediately (queue is the retry fallback).
            $this->dispatchCouponEmail($idLink, $idShop, $idCustomer, $code);

            return [
                'success' => true,
                'reason' => 'ok',
                'id_snod_rule' => $idRule,
                'id_coupon_link' => $idLink,
                'id_cart_rule' => $idCartRule,
                'code' => $code,
            ];
        } catch (\Exception $e) {
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
            $idCurrency = (int) \Configuration::get('PS_CURRENCY_DEFAULT');
        }

        return $idCurrency;
    }

    /**
     * @param int $idRule
     * @param array $existing a ps_snod_coupon_link row
     *
     * @return array
     */
    private function existingResult($idRule, array $existing)
    {
        // Self-heal: if a prior run issued the coupon but never delivered its
        // email, deliver it now (immediate send, or re-queue). The mailer is
        // idempotent, so an already-emailed coupon is left untouched.
        $this->dispatchCouponEmail(
            (int) $existing['id_snod_coupon_link'],
            isset($existing['id_shop']) ? (int) $existing['id_shop'] : 0,
            isset($existing['id_customer']) ? (int) $existing['id_customer'] : 0,
            (string) $existing['coupon_code'],
        );

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
     * Delivers the coupon email. When a mailer is wired the email is sent
     * immediately so the customer receives the coupon right after checkout; the
     * queue is used only as a retry fallback when that immediate send fails
     * (e.g. a transient SMTP error). Without a mailer it falls back to the queue
     * entirely (cron delivery).
     *
     * Delivery is strictly best-effort: it must never roll back or fail an
     * already-issued coupon, so any error is swallowed and the coupon can still
     * be re-detected and emailed on a later pass.
     *
     * @param int $idCouponLink
     * @param int $idShop
     * @param int $idCustomer
     * @param string $code
     *
     * @return void
     */
    private function dispatchCouponEmail($idCouponLink, $idShop, $idCustomer, $code)
    {
        try {
            if ($this->couponMailer !== null && $this->couponMailer->sendForCouponLink((int) $idCouponLink)) {
                // Sent (or already sent) — no cron round-trip needed.
                return;
            }

            // No mailer, or the immediate send failed: hand off to the queue so a
            // later cron pass retries delivery.
            $this->queueService->enqueueCouponEmail((int) $idCouponLink, (int) $idShop, [
                'id_customer' => (int) $idCustomer,
                'coupon_code' => (string) $code,
            ]);
        } catch (\Exception $e) {
            // Intentionally ignored: see method docblock.
        }
    }

    /**
     * Logs the outcome of a single rule's coupon generation: an issued coupon at
     * info, an idempotent skip at debug, and any failure at error — each keyed by
     * the order so all coupons of one order share a correlation id.
     *
     * @param array $result
     * @param array $rule
     * @param int $idShop
     * @param int $idOrderSource
     * @param int $idCustomer
     *
     * @return void
     */
    private function logResult(array $result, array $rule, $idShop, $idOrderSource, $idCustomer)
    {
        if ($this->logger === null) {
            return;
        }

        $reason = isset($result['reason']) ? (string) $result['reason'] : '';
        $context = [
            'id_order' => (int) $idOrderSource,
            'id_shop' => (int) $idShop,
            'id_customer' => (int) $idCustomer,
            'id_snod_rule' => (int) $rule['id_snod_rule'],
            'rule_name' => isset($rule['name']) ? (string) $rule['name'] : '',
            'code' => isset($result['code']) ? (string) $result['code'] : '',
            'id_cart_rule' => isset($result['id_cart_rule']) ? (int) $result['id_cart_rule'] : 0,
        ];
        $correlationId = $this->orderCorrelation($idOrderSource);

        if ($reason === 'ok') {
            $this->logCoupon(ModuleLogger::LEVEL_INFO, 'Coupon issued', $context, $correlationId);

            return;
        }

        if ($reason === 'already_exists') {
            $this->logCoupon(ModuleLogger::LEVEL_DEBUG, 'Coupon already issued (skipped)', $context, $correlationId);

            return;
        }

        $context['reason'] = $reason;
        $this->logCoupon(ModuleLogger::LEVEL_ERROR, 'Coupon generation failed', $context, $correlationId);
    }

    /**
     * @param int $idOrderSource
     *
     * @return string a correlation id shared by all coupons of one order
     */
    private function orderCorrelation($idOrderSource)
    {
        return 'order:' . (int) $idOrderSource;
    }

    /**
     * Best-effort structured log entry for a coupon event.
     *
     * @param string $level
     * @param string $message
     * @param array $context
     * @param string|null $correlationId
     *
     * @return void
     */
    private function logCoupon($level, $message, array $context, $correlationId)
    {
        if ($this->logger === null) {
            return;
        }

        $this->logger->{$level}($message, $context, $correlationId, self::LOG_CHANNEL);
    }

    /**
     * @param int $idRule
     * @param string $reason
     * @param array $extra
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
            $extra,
        );
    }
}
