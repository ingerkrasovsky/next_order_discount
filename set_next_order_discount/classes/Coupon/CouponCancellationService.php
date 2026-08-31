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

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Cancels the coupon(s) issued for a source order when that order is reversed
 * (canceled or refunded).
 *
 * For each coupon linked to the source order it deactivates the CartRule (so the
 * reward can no longer be applied) and moves the coupon link to the terminal
 * `canceled` status, recording the triggering order state in metadata. The
 * operation is idempotent (a coupon already in a terminal state — used, expired
 * or canceled — is skipped) and isolated (one coupon's failure never aborts the
 * rest), so replaying the order hook leaves the state unchanged and can never
 * disrupt order processing.
 */
class CouponCancellationService
{
    /**
     * Log channel for coupon cancellation events.
     */
    private const LOG_CHANNEL = 'coupon';

    /**
     * Statuses from which a coupon must not be moved to `canceled`.
     */
    private const TERMINAL_STATUSES = [
        CouponLinkRepository::STATUS_USED,
        CouponLinkRepository::STATUS_EXPIRED,
        CouponLinkRepository::STATUS_CANCELED,
    ];

    private $couponLinkRepository;
    private $cartRuleAdapter;
    private $logger;

    /**
     * @param CouponLinkRepository $couponLinkRepository
     * @param CartRuleAdapter $cartRuleAdapter
     * @param ModuleLogger|null $logger optional structured logger
     */
    public function __construct(
        CouponLinkRepository $couponLinkRepository,
        CartRuleAdapter $cartRuleAdapter,
        ?ModuleLogger $logger = null,
    ) {
        $this->couponLinkRepository = $couponLinkRepository;
        $this->cartRuleAdapter = $cartRuleAdapter;
        $this->logger = $logger;
    }

    /**
     * Cancels every non-terminal coupon issued for a reversed source order.
     *
     * @param int $idOrderSource the canceled/refunded order
     * @param int $idOrderState the triggering order state (recorded in metadata)
     * @param int $idShop optional shop filter (0 = any shop)
     *
     * @return array summary counters: canceled, deactivated, skipped, errors
     */
    public function cancelForOrderSource($idOrderSource, $idOrderState = 0, $idShop = 0)
    {
        $summary = ['canceled' => 0, 'deactivated' => 0, 'skipped' => 0, 'errors' => 0];

        $links = $this->couponLinkRepository->findAllByOrderSource((int) $idOrderSource, (int) $idShop);
        foreach ($links as $link) {
            try {
                $result = $this->cancelLink($link, (int) $idOrderState);
                if ($result === null) {
                    ++$summary['skipped'];
                    continue;
                }

                ++$summary['canceled'];
                if ($result) {
                    ++$summary['deactivated'];
                }
            } catch (\Throwable $e) {
                // One coupon's failure must never abort the rest or break the order.
                ++$summary['errors'];
            }
        }

        return $summary;
    }

    /**
     * Applies the cancellation transition to one coupon link.
     *
     * @param array $link
     * @param int $idOrderState
     *
     * @return bool|null null when already terminal (skipped); otherwise true/false
     *                   depending on whether its CartRule was deactivated
     */
    private function cancelLink(array $link, $idOrderState)
    {
        $status = isset($link['status']) ? (string) $link['status'] : '';
        if (in_array($status, self::TERMINAL_STATUSES, true)) {
            return null;
        }

        // Deactivating the CartRule is best-effort: an already-inactive or deleted
        // voucher leaves nothing to do and must not block the status transition.
        $deactivated = false;
        $idCartRule = isset($link['id_cart_rule']) ? (int) $link['id_cart_rule'] : 0;
        if ($idCartRule > 0) {
            $deactivated = (bool) $this->cartRuleAdapter->deactivate($idCartRule);
        }

        $idLink = (int) $link[CouponLinkRepository::PRIMARY_KEY];
        $persisted = $this->couponLinkRepository->update($idLink, [
            'status' => CouponLinkRepository::STATUS_CANCELED,
            'metadata_json' => $this->mergeMetadata($link, $idOrderState),
        ]);
        if (!$persisted) {
            // Surface the failure so it is counted as an error; the next hook pass
            // (still non-terminal) retries the same transition safely.
            throw new \RuntimeException('Failed to persist cancellation of coupon link ' . $idLink . '.');
        }

        $this->logCancellation($link, $idLink, $idOrderState);

        return $deactivated;
    }

    /**
     * Records the cancellation (timestamp + triggering order state) in metadata,
     * preserving any existing keys. Best-effort: unreadable metadata is replaced.
     *
     * @param array $link
     * @param int $idOrderState
     *
     * @return string JSON metadata
     */
    private function mergeMetadata(array $link, $idOrderState)
    {
        $metadata = [];
        if (isset($link['metadata_json']) && $link['metadata_json'] !== '') {
            $decoded = json_decode((string) $link['metadata_json'], true);
            if (is_array($decoded)) {
                $metadata = $decoded;
            }
        }

        $metadata['canceled_at'] = date('Y-m-d H:i:s');
        $metadata['canceled_order_state'] = (int) $idOrderState;

        $encoded = json_encode($metadata);

        return $encoded === false ? '{"canceled_order_state":' . (int) $idOrderState . '}' : $encoded;
    }

    /**
     * @param array $link
     * @param int $idLink
     * @param int $idOrderState
     *
     * @return void
     */
    private function logCancellation(array $link, $idLink, $idOrderState)
    {
        if ($this->logger === null) {
            return;
        }

        $idOrderSource = isset($link['id_order_source']) ? (int) $link['id_order_source'] : 0;
        $this->logger->info(
            'Coupon canceled',
            [
                'id_coupon_link' => (int) $idLink,
                'id_cart_rule' => isset($link['id_cart_rule']) ? (int) $link['id_cart_rule'] : 0,
                'code' => isset($link['coupon_code']) ? (string) $link['coupon_code'] : '',
                'id_order_source' => $idOrderSource,
                'id_order_state' => (int) $idOrderState,
            ],
            'order:' . $idOrderSource,
            self::LOG_CHANNEL,
        );
    }
}
