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

use Setecom\NextOrderDiscount\Logger\ModuleLogger;
use Setecom\NextOrderDiscount\Repository\CouponLinkRepository;
use Throwable;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Marks module-issued coupons as redeemed when the customer applies them to a
 * new order.
 *
 * The order hooks pass the cart rules attached to the order; this service keeps
 * only the ones this module owns (matched by id_cart_rule in the coupon link
 * table) and transitions them to the terminal `used` status with a used_at
 * timestamp, recording the redeeming order in metadata. The operation is
 * idempotent (a coupon already in a terminal state is skipped) and isolated (one
 * coupon's failure never aborts the rest), so replaying a hook leaves the state
 * unchanged and can never disrupt order processing.
 */
class CouponRedemptionService
{
    /**
     * Log channel for coupon redemption events.
     */
    private const LOG_CHANNEL = 'coupon';

    /**
     * Statuses from which a coupon must not be moved to `used`.
     */
    private const TERMINAL_STATUSES = [
        CouponLinkRepository::STATUS_USED,
        CouponLinkRepository::STATUS_EXPIRED,
        CouponLinkRepository::STATUS_CANCELED,
    ];

    private $couponLinkRepository;
    private $logger;

    /**
     * @param CouponLinkRepository $couponLinkRepository
     * @param ModuleLogger|null    $logger               optional structured logger
     */
    public function __construct(
        CouponLinkRepository $couponLinkRepository,
        ModuleLogger $logger = null
    ) {
        $this->couponLinkRepository = $couponLinkRepository;
        $this->logger = $logger;
    }

    /**
     * Marks every module-issued coupon among the given cart rules as used.
     *
     * @param array $cartRuleIds  id_cart_rule values applied to the order
     * @param int   $idOrderUsed  the order that redeemed the coupon(s)
     *
     * @return array summary counters: used, skipped, errors
     */
    public function markCartRulesUsed(array $cartRuleIds, $idOrderUsed)
    {
        $summary = ['used' => 0, 'skipped' => 0, 'errors' => 0];
        $idOrderUsed = (int) $idOrderUsed;

        foreach ($this->uniqueIds($cartRuleIds) as $idCartRule) {
            try {
                $outcome = $this->redeemCartRule($idCartRule, $idOrderUsed);
                ++$summary[$outcome];
            } catch (Throwable $e) {
                // One coupon's failure must never abort the rest or break checkout.
                ++$summary['errors'];
            }
        }

        return $summary;
    }

    /**
     * Redeems a single cart rule if it belongs to this module and is not yet
     * terminal.
     *
     * @param int $idCartRule
     * @param int $idOrderUsed
     *
     * @return string 'used' when transitioned, otherwise 'skipped'
     */
    private function redeemCartRule($idCartRule, $idOrderUsed)
    {
        $link = $this->couponLinkRepository->findByCartRule($idCartRule);
        if ($link === null) {
            // Not one of ours (e.g. a hand-made voucher) — nothing to record.
            return 'skipped';
        }

        $status = isset($link['status']) ? (string) $link['status'] : '';
        if (in_array($status, self::TERMINAL_STATUSES, true)) {
            return 'skipped';
        }

        $idLink = (int) $link[CouponLinkRepository::PRIMARY_KEY];

        $updated = $this->couponLinkRepository->update($idLink, [
            'status' => CouponLinkRepository::STATUS_USED,
            'used_at' => date('Y-m-d H:i:s'),
            'metadata_json' => $this->mergeMetadata($link, $idOrderUsed),
        ]);
        if (!$updated) {
            // Surface the failure so the caller counts it as an error; the next
            // hook pass (still non-terminal) retries the same transition safely.
            throw new \RuntimeException('Failed to persist redemption of coupon link ' . $idLink . '.');
        }

        $this->logRedemption($link, $idLink, $idOrderUsed);

        return 'used';
    }

    /**
     * Records the redeeming order inside the coupon link's metadata, preserving
     * any existing keys. Best-effort: unreadable metadata is replaced.
     *
     * @param array $link
     * @param int   $idOrderUsed
     *
     * @return string JSON metadata
     */
    private function mergeMetadata(array $link, $idOrderUsed)
    {
        $metadata = [];
        if (isset($link['metadata_json']) && $link['metadata_json'] !== '') {
            $decoded = json_decode((string) $link['metadata_json'], true);
            if (is_array($decoded)) {
                $metadata = $decoded;
            }
        }

        $metadata['used_in_order'] = (int) $idOrderUsed;

        $encoded = json_encode($metadata);

        return $encoded === false ? '{"used_in_order":' . (int) $idOrderUsed . '}' : $encoded;
    }

    /**
     * @param array $link
     * @param int   $idLink
     * @param int   $idOrderUsed
     *
     * @return void
     */
    private function logRedemption(array $link, $idLink, $idOrderUsed)
    {
        if ($this->logger === null) {
            return;
        }

        $this->logger->info(
            'Coupon redeemed',
            [
                'id_coupon_link' => (int) $idLink,
                'id_cart_rule' => isset($link['id_cart_rule']) ? (int) $link['id_cart_rule'] : 0,
                'code' => isset($link['coupon_code']) ? (string) $link['coupon_code'] : '',
                'id_order_source' => isset($link['id_order_source']) ? (int) $link['id_order_source'] : 0,
                'id_order_used' => (int) $idOrderUsed,
            ],
            'order:' . (int) $idOrderUsed,
            self::LOG_CHANNEL
        );
    }

    /**
     * @param array $cartRuleIds
     *
     * @return array unique positive integer cart rule ids
     */
    private function uniqueIds(array $cartRuleIds)
    {
        $ids = [];
        foreach ($cartRuleIds as $id) {
            $id = (int) $id;
            if ($id > 0) {
                $ids[$id] = $id;
            }
        }

        return array_values($ids);
    }
}
