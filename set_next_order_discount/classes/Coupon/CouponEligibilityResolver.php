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
 * Decides whether a source order qualifies for a next-order coupon.
 *
 * Pure business policy: it depends only on the module configuration and the
 * order context passed to it, never on the current hook, controller or HTTP
 * request. This keeps the decision unit-testable and reusable across hooks and
 * cron. Idempotency ("one coupon per order") is intentionally out of scope and
 * handled elsewhere, so this class keeps a single responsibility.
 */
class SnodCouponEligibilityResolver
{
    public const REASON_MODULE_DISABLED = 'module_disabled';
    public const REASON_INVALID_SHOP = 'invalid_shop';
    public const REASON_INVALID_CUSTOMER = 'invalid_customer';
    public const REASON_INVALID_ORDER = 'invalid_order';
    public const REASON_STATUS_NOT_TARGETED = 'status_not_targeted';
    public const REASON_ORDER_NOT_PAID = 'order_not_paid';
    public const REASON_BELOW_MINIMUM_AMOUNT = 'below_minimum_amount';

    /**
     * Evaluates every eligibility rule and returns a structured verdict.
     *
     * Expected context keys:
     *  - id_shop           (int)   shop that owns the order
     *  - id_customer       (int)   customer who placed the order
     *  - id_order_source   (int)   the order being evaluated
     *  - id_order_state    (int)   current order state id
     *  - order_is_paid     (bool)  whether the current state is a paid state
     *  - order_total_paid  (float) order total used for the minimum check
     *
     * @param array $context
     *
     * @return array associative array: 'eligible' => bool, 'reasons' => string[]
     */
    public function evaluate(array $context)
    {
        $idShop = isset($context['id_shop']) ? (int) $context['id_shop'] : 0;
        $idCustomer = isset($context['id_customer']) ? (int) $context['id_customer'] : 0;
        $idOrderSource = isset($context['id_order_source']) ? (int) $context['id_order_source'] : 0;
        $idOrderState = isset($context['id_order_state']) ? (int) $context['id_order_state'] : 0;
        $orderIsPaid = !empty($context['order_is_paid']);
        $orderTotalPaid = isset($context['order_total_paid'])
            ? (float) str_replace(',', '.', (string) $context['order_total_paid'])
            : 0.0;

        $reasons = [];

        if (!$this->isModuleEnabled($idShop)) {
            $reasons[] = self::REASON_MODULE_DISABLED;
        }

        if ($idShop <= 0) {
            $reasons[] = self::REASON_INVALID_SHOP;
        }

        if ($idCustomer <= 0) {
            $reasons[] = self::REASON_INVALID_CUSTOMER;
        }

        if ($idOrderSource <= 0) {
            $reasons[] = self::REASON_INVALID_ORDER;
        }

        $targetStatuses = $this->getTargetStatuses($idShop);
        if (!$this->isOrderStatusEligible($targetStatuses, $idOrderState, $orderIsPaid)) {
            $reasons[] = empty($targetStatuses)
                ? self::REASON_ORDER_NOT_PAID
                : self::REASON_STATUS_NOT_TARGETED;
        }

        if (!$this->isAmountEligible($idShop, $orderTotalPaid)) {
            $reasons[] = self::REASON_BELOW_MINIMUM_AMOUNT;
        }

        return [
            'eligible' => empty($reasons),
            'reasons' => $reasons,
        ];
    }

    /**
     * Convenience boolean wrapper around evaluate().
     *
     * @param array $context
     *
     * @return bool
     */
    public function isEligible(array $context)
    {
        $result = $this->evaluate($context);

        return (bool) $result['eligible'];
    }

    /**
     * @param int $idShop
     *
     * @return bool
     */
    private function isModuleEnabled($idShop)
    {
        return (int) $this->getConfig('SNOD_ENABLED', $idShop) === 1;
    }

    /**
     * When a target-status whitelist is configured, the order state must be in
     * it. Otherwise the module falls back to "the order is paid".
     *
     * @param array $targetStatuses whitelist of order-state ids (may be empty)
     * @param int   $idOrderState
     * @param bool  $orderIsPaid
     *
     * @return bool
     */
    private function isOrderStatusEligible(array $targetStatuses, $idOrderState, $orderIsPaid)
    {
        if (!empty($targetStatuses)) {
            return in_array((int) $idOrderState, $targetStatuses, true);
        }

        return (bool) $orderIsPaid;
    }

    /**
     * @param int   $idShop
     * @param float $orderTotalPaid
     *
     * @return bool
     */
    private function isAmountEligible($idShop, $orderTotalPaid)
    {
        $minimum = (float) str_replace(',', '.', (string) $this->getConfig('SNOD_MIN_ORDER_AMOUNT', $idShop));

        if ($minimum <= 0.0) {
            return true;
        }

        return (float) $orderTotalPaid >= $minimum;
    }

    /**
     * Parses the SNOD_TARGET_STATUSES CSV into a list of unique positive ints.
     *
     * @param int $idShop
     *
     * @return array list of unique positive order-state ids
     */
    private function getTargetStatuses($idShop)
    {
        $raw = (string) $this->getConfig('SNOD_TARGET_STATUSES', $idShop);
        if (trim($raw) === '') {
            return [];
        }

        $statuses = [];
        foreach (explode(',', $raw) as $piece) {
            $value = (int) trim($piece);
            if ($value > 0) {
                $statuses[$value] = $value;
            }
        }

        return array_values($statuses);
    }

    /**
     * Reads a configuration value scoped to a specific shop for multishop
     * correctness, falling back to the global value when no shop is given.
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
}
