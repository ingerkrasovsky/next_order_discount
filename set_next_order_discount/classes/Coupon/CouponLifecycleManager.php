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

use RuntimeException;
use Setecom\NextOrderDiscount\Repository\CouponLinkRepository;
use Throwable;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Manages the terminal expiry transition of the coupon lifecycle
 * (created -> emailed -> reminded -> expired): it soft-expires coupons whose
 * validity has lapsed by deactivating their CartRule (active = 0, never
 * deleted, preserving the audit trail) and moving the coupon link to the
 * `expired` status with an expiry timestamp.
 *
 * The operation is idempotent and isolated: the candidate query excludes coupons
 * already in a terminal state, each coupon is guarded against re-expiry, and a
 * failure on one coupon can never abort the rest of the sweep. Deactivating a
 * CartRule that is already inactive or no longer exists is harmless, so a
 * repeated run leaves the state unchanged.
 */
class CouponLifecycleManager
{
    /**
     * Default number of coupons expired per sweep.
     */
    public const DEFAULT_BATCH_SIZE = 100;

    private $couponLinkRepository;
    private $cartRuleAdapter;

    /**
     * @param CouponLinkRepository $couponLinkRepository
     * @param CartRuleAdapter      $cartRuleAdapter
     */
    public function __construct(
        CouponLinkRepository $couponLinkRepository,
        CartRuleAdapter $cartRuleAdapter
    ) {
        $this->couponLinkRepository = $couponLinkRepository;
        $this->cartRuleAdapter = $cartRuleAdapter;
    }

    /**
     * Expires every coupon whose validity has lapsed.
     *
     * @param int $batchSize maximum coupons to expire in this sweep
     * @param int $idShop    optional shop filter (0 = any shop)
     *
     * @return array summary counters: expired, deactivated, skipped, errors
     */
    public function expireDueCoupons($batchSize = self::DEFAULT_BATCH_SIZE, $idShop = 0)
    {
        $summary = [
            'expired' => 0,
            'deactivated' => 0,
            'skipped' => 0,
            'errors' => 0,
        ];

        $coupons = $this->couponLinkRepository->findExpired((int) $batchSize, (int) $idShop);

        foreach ($coupons as $coupon) {
            try {
                $deactivated = $this->expireLink($coupon);
                if ($deactivated === null) {
                    ++$summary['skipped'];
                    continue;
                }

                ++$summary['expired'];
                if ($deactivated) {
                    ++$summary['deactivated'];
                }
            } catch (Throwable $e) {
                // One coupon's failure must never abort the rest of the sweep.
                ++$summary['errors'];
            }
        }

        return $summary;
    }

    /**
     * Expires a single coupon by its link id.
     *
     * @param int $idCouponLink ps_snod_coupon_link primary key
     *
     * @return bool whether the coupon was expired by this call (false when it was
     *              missing or already terminal)
     *
     * @throws RuntimeException when the expiry cannot be persisted
     */
    public function expire($idCouponLink)
    {
        $link = $this->couponLinkRepository->findById((int) $idCouponLink);
        if ($link === null) {
            return false;
        }

        return $this->expireLink($link) !== null;
    }

    /**
     * Applies the expiry transition to one coupon link row.
     *
     * @param array $link coupon link row
     *
     * @return bool|null null when the coupon is already terminal (skipped);
     *                   otherwise true/false depending on whether its CartRule
     *                   was deactivated
     */
    private function expireLink(array $link)
    {
        if ($this->isAlreadyTerminal($link)) {
            return null;
        }

        $idLink = (int) $link[CouponLinkRepository::PRIMARY_KEY];

        // Deactivating the CartRule is best-effort: an already-inactive or
        // deleted voucher leaves nothing to do and must not block the coupon
        // from being marked expired.
        $deactivated = false;
        $idCartRule = isset($link['id_cart_rule']) ? (int) $link['id_cart_rule'] : 0;
        if ($idCartRule > 0) {
            $deactivated = (bool) $this->cartRuleAdapter->deactivate($idCartRule);
        }

        // Persisting the terminal status is mandatory: if it fails the coupon
        // has not truly expired, so surface it instead of reporting success. The
        // sweep isolates this per coupon and the next run retries it safely.
        $persisted = $this->couponLinkRepository->update($idLink, [
            'status' => CouponLinkRepository::STATUS_EXPIRED,
            'expired_at' => date('Y-m-d H:i:s'),
        ]);
        if (!$persisted) {
            throw new RuntimeException('Failed to persist the expiry of coupon link ' . $idLink . '.');
        }

        return $deactivated;
    }

    /**
     * A coupon that is already expired, used or canceled — or that already
     * carries an expiry timestamp — must not be expired again.
     *
     * @param array $link coupon link row
     *
     * @return bool
     */
    private function isAlreadyTerminal(array $link)
    {
        $status = isset($link['status']) ? (string) $link['status'] : '';
        if (in_array($status, [
            CouponLinkRepository::STATUS_EXPIRED,
            CouponLinkRepository::STATUS_USED,
            CouponLinkRepository::STATUS_CANCELED,
        ], true)) {
            return true;
        }

        return isset($link['expired_at']) && $link['expired_at'] !== null && $link['expired_at'] !== ''
            && strpos((string) $link['expired_at'], '0000-00-00') !== 0;
    }
}
