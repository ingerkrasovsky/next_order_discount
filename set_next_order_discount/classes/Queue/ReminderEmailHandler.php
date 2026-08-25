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

use Setecom\NextOrderDiscount\Reminder\ReminderMailer;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Handles `reminder_1` and `reminder_2` tasks by delegating to the reminder
 * mailer. A single handler serves both task types: the reminder number is taken
 * from the task type, and the coupon link id from the payload. The mailer is
 * idempotent, so a retried task never sends a duplicate reminder.
 */
class ReminderEmailHandler implements QueueTaskHandlerInterface
{
    private $mailer;

    /**
     * @param ReminderMailer $mailer
     */
    public function __construct(ReminderMailer $mailer)
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

        $taskType = isset($task['task_type']) ? (string) $task['task_type'] : '';
        $reminderNumber = ($taskType === QueueService::TASK_REMINDER_2) ? 2 : 1;

        return $this->mailer->sendReminder($idCouponLink, $reminderNumber);
    }

    /**
     * @param array $task
     *
     * @return array the decoded task payload
     */
    private function decodePayload(array $task)
    {
        if (!isset($task['payload_json']) || $task['payload_json'] === '' || $task['payload_json'] === null) {
            return [];
        }

        $decoded = json_decode((string) $task['payload_json'], true);

        return is_array($decoded) ? $decoded : [];
    }
}
