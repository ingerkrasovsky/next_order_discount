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

namespace Setecom\NextOrderDiscount\Reminder;

use Setecom\NextOrderDiscount\Queue\QueueService;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Plans coupon reminders by enqueuing reminder tasks for coupons that have
 * reached their reminder window.
 *
 * For each ripe coupon it enqueues a `reminder_1` (or `reminder_2`) task keyed by
 * a deterministic correlation id, so re-running the planner never creates a
 * duplicate task: while a task is still pending the queue returns the existing
 * one, and once a reminder has actually been sent the candidate query excludes
 * the coupon (its reminder timestamp is set). The two reminders are independent
 * slots — each with its own day offset — so a rule may use either, both or
 * neither. Whether a reminder is due, and its timing, is configured per rule and
 * enforced by the candidate query, so the planner itself carries no policy.
 */
class ReminderPlanner
{
    /**
     * Default maximum number of coupons planned per reminder stage per run.
     */
    public const DEFAULT_BATCH_SIZE = 100;

    private $candidateRepository;
    private $queueService;

    /**
     * @param ReminderCandidateRepository $candidateRepository
     * @param QueueService                $queueService
     */
    public function __construct(
        ReminderCandidateRepository $candidateRepository,
        QueueService $queueService
    ) {
        $this->candidateRepository = $candidateRepository;
        $this->queueService = $queueService;
    }

    /**
     * Plans the reminders due right now.
     *
     * @param int $batchSize maximum coupons per reminder stage
     * @param int $idShop    optional shop filter (0 = any shop)
     *
     * @return array summary counters: first_planned, second_planned, skipped
     */
    public function plan($batchSize = self::DEFAULT_BATCH_SIZE, $idShop = 0)
    {
        $summary = [
            'first_planned' => 0,
            'second_planned' => 0,
            'skipped' => 0,
        ];

        $batchSize = max(1, (int) $batchSize);
        $idShop = (int) $idShop;

        $firstDue = $this->candidateRepository->findDueForFirstReminder(
            $batchSize,
            $idShop
        );
        foreach ($firstDue as $coupon) {
            if ($this->enqueueReminder(QueueService::TASK_REMINDER_1, $coupon)) {
                ++$summary['first_planned'];
            } else {
                ++$summary['skipped'];
            }
        }

        $secondDue = $this->candidateRepository->findDueForSecondReminder(
            $batchSize,
            $idShop
        );
        foreach ($secondDue as $coupon) {
            if ($this->enqueueReminder(QueueService::TASK_REMINDER_2, $coupon)) {
                ++$summary['second_planned'];
            } else {
                ++$summary['skipped'];
            }
        }

        return $summary;
    }

    /**
     * Enqueues one reminder task for a coupon, idempotently.
     *
     * @param string $taskType QueueService::TASK_REMINDER_1 or _2
     * @param array  $coupon   a snod_coupon_link row
     *
     * @return bool whether a task is now queued for this coupon/stage
     */
    private function enqueueReminder($taskType, array $coupon)
    {
        $idCouponLink = isset($coupon[ReminderCandidateRepository::PRIMARY_KEY])
            ? (int) $coupon[ReminderCandidateRepository::PRIMARY_KEY]
            : 0;
        if ($idCouponLink <= 0) {
            return false;
        }

        $idShop = isset($coupon['id_shop']) ? (int) $coupon['id_shop'] : 0;

        $taskId = $this->queueService->enqueue(
            $taskType,
            ['id_snod_coupon_link' => $idCouponLink],
            $idShop,
            QueueService::correlationFor($taskType, $idCouponLink)
        );

        return $taskId > 0;
    }
}
