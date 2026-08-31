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
namespace Setecom\NextOrderDiscount\Queue;

use Setecom\NextOrderDiscount\Mail\CouponMailer;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Handles `coupon_email` tasks by delegating to the coupon mailer. The task
 * payload carries the coupon link id recorded when the coupon was issued; the
 * mailer itself is idempotent, so a retried task never sends a duplicate email.
 */
class CouponEmailHandler implements QueueTaskHandlerInterface
{
    private $mailer;

    /**
     * @param CouponMailer $mailer
     */
    public function __construct(CouponMailer $mailer)
    {
        $this->mailer = $mailer;
    }

    /**
     * @param array $task a ps_snod_dispatch_queue row
     *
     * @return bool
     */
    public function handle(array $task)
    {
        $payload = $this->decodePayload($task);
        $idCouponLink = isset($payload['id_snod_coupon_link']) ? (int) $payload['id_snod_coupon_link'] : 0;
        if ($idCouponLink <= 0) {
            return false;
        }

        return $this->mailer->sendForCouponLink($idCouponLink);
    }

    /**
     * @param array $task
     *
     * @return array the decoded task payload
     */
    private function decodePayload(array $task)
    {
        if (!isset($task['payload_json']) || $task['payload_json'] === '') {
            return [];
        }

        $decoded = json_decode((string) $task['payload_json'], true);

        return is_array($decoded) ? $decoded : [];
    }
}
