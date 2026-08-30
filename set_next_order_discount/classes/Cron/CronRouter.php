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

namespace Setecom\NextOrderDiscount\Cron;

use Configuration;
use Setecom\NextOrderDiscount\Coupon\CouponLifecycleManager;
use Setecom\NextOrderDiscount\Logger\ModuleLogger;
use Setecom\NextOrderDiscount\Queue\QueueWorker;
use Setecom\NextOrderDiscount\Reminder\ReminderPlanner;
use Throwable;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Routes a named background task to its runnable unit under a per-task lock.
 *
 * The three tasks — draining the dispatch queue, planning reminders and expiring
 * lapsed coupons — each run inside a dedicated lock so two overlapping cron (or
 * manual) invocations of the same task never run at once. The lock is always
 * released, even if the task throws, and a task that cannot acquire its lock is
 * reported as skipped rather than failed.
 */
class CronRouter
{
    public const TASK_PROCESS_QUEUE = 'process_queue';
    public const TASK_PLAN_REMINDERS = 'plan_reminders';
    public const TASK_EXPIRE_COUPONS = 'expire_coupons';

    /**
     * Meta task that runs every real task in sequence, so a single cron entry can
     * drive the whole module.
     */
    public const TASK_ALL = 'all';

    /**
     * Prefix of the Configuration key that stores each task's last successful run
     * timestamp (used by the Cron/Tools health check).
     */
    public const LASTRUN_PREFIX = 'SNOD_CRON_LASTRUN_';

    /**
     * Lock lifetime for a task run, in seconds.
     */
    private const LOCK_TTL_SECONDS = 300;

    /**
     * Log channel for cron router events.
     */
    private const LOG_CHANNEL = 'cron';

    private $lockManager;
    private $queueWorker;
    private $reminderPlanner;
    private $lifecycleManager;
    private $logger;

    /**
     * @param LockManager             $lockManager
     * @param QueueWorker             $queueWorker
     * @param ReminderPlanner         $reminderPlanner
     * @param CouponLifecycleManager  $lifecycleManager
     * @param ModuleLogger|null       $logger           optional structured logger
     */
    public function __construct(
        LockManager $lockManager,
        QueueWorker $queueWorker,
        ReminderPlanner $reminderPlanner,
        CouponLifecycleManager $lifecycleManager,
        ModuleLogger $logger = null
    ) {
        $this->lockManager = $lockManager;
        $this->queueWorker = $queueWorker;
        $this->reminderPlanner = $reminderPlanner;
        $this->lifecycleManager = $lifecycleManager;
        $this->logger = $logger;
    }

    /**
     * @return string[] the task names this router accepts
     */
    public static function availableTasks()
    {
        return [
            self::TASK_PROCESS_QUEUE,
            self::TASK_PLAN_REMINDERS,
            self::TASK_EXPIRE_COUPONS,
        ];
    }

    /**
     * The lock name used for a given task, exposed so callers can inspect a
     * task's lock state without duplicating the naming convention.
     *
     * @param string $task
     *
     * @return string
     */
    public static function lockNameFor($task)
    {
        return 'snod_cron_' . (string) $task;
    }

    /**
     * The Configuration key holding a task's last successful run timestamp.
     *
     * @param string $task
     *
     * @return string
     */
    public static function lastRunKeyFor($task)
    {
        return self::LASTRUN_PREFIX . (string) $task;
    }

    /**
     * Runs one named task under its lock.
     *
     * @param string $task   one of the TASK_* constants
     * @param int    $idShop optional shop scope (0 = any shop)
     *
     * @return array a structured result:
     *               - unknown task: ['success' => false, 'error' => 'unknown_task', 'task' => ...]
     *               - locked:       ['success' => false, 'locked' => true, 'task' => ...]
     *               - failure:      ['success' => false, 'error' => 'exception', 'task' => ...]
     *               - success:      ['success' => true, 'task' => ..., 'result' => <task summary>]
     */
    public function run($task, $idShop = 0)
    {
        $task = (string) $task;
        $idShop = (int) $idShop;

        if ($task === self::TASK_ALL) {
            return $this->runAll($idShop);
        }

        $correlationId = $this->buildCorrelationId($task);

        if (!in_array($task, self::availableTasks(), true)) {
            $this->log(ModuleLogger::LEVEL_WARNING, 'Unknown cron task requested', ['task' => $task], $correlationId);

            return ['success' => false, 'error' => 'unknown_task', 'task' => $task];
        }

        $lockName = self::lockNameFor($task);
        if (!$this->lockManager->acquire($lockName, self::LOCK_TTL_SECONDS)) {
            $this->log(ModuleLogger::LEVEL_INFO, 'Cron task skipped (locked)', ['task' => $task], $correlationId);

            return ['success' => false, 'locked' => true, 'task' => $task];
        }

        try {
            $result = $this->dispatch($task, $idShop);
            $this->recordLastRun($task);
            $this->log(ModuleLogger::LEVEL_INFO, 'Cron task completed', ['task' => $task, 'result' => $result], $correlationId);

            return ['success' => true, 'task' => $task, 'result' => $result];
        } catch (Throwable $e) {
            $this->log(ModuleLogger::LEVEL_ERROR, 'Cron task failed', ['task' => $task, 'error' => $e->getMessage()], $correlationId);

            return ['success' => false, 'error' => 'exception', 'task' => $task];
        } finally {
            $this->lockManager->release($lockName);
        }
    }

    /**
     * Runs every real task in sequence (the `all` meta task). Each sub-task keeps
     * its own lock and last-run bookkeeping. A locked sub-task is not treated as a
     * failure (another run holds it); only an actual error makes the batch fail.
     *
     * @param int $idShop
     *
     * @return array ['success' => bool, 'task' => 'all', 'results' => [task => result]]
     */
    private function runAll($idShop)
    {
        $results = [];
        $success = true;
        foreach (self::availableTasks() as $task) {
            $result = $this->run($task, $idShop);
            $results[$task] = $result;
            if (empty($result['success']) && empty($result['locked'])) {
                $success = false;
            }
        }

        return ['success' => $success, 'task' => self::TASK_ALL, 'results' => $results];
    }

    /**
     * Records a task's last successful run timestamp for the health check.
     * Best-effort: a bookkeeping failure must never affect the task outcome.
     *
     * @param string $task
     *
     * @return void
     */
    private function recordLastRun($task)
    {
        try {
            Configuration::updateValue(self::lastRunKeyFor($task), date('Y-m-d H:i:s'));
        } catch (Throwable $e) {
            // Ignored on purpose: see method docblock.
        }
    }

    /**
     * @param string $task
     *
     * @return string a per-run correlation id
     */
    private function buildCorrelationId($task)
    {
        return 'cron_' . $task . '_' . uniqid('', true);
    }

    /**
     * Best-effort structured log entry.
     *
     * @param string      $level
     * @param string      $message
     * @param array       $context
     * @param string|null $correlationId
     *
     * @return void
     */
    private function log($level, $message, array $context, $correlationId)
    {
        if ($this->logger === null) {
            return;
        }

        $this->logger->{$level}($message, $context, $correlationId, self::LOG_CHANNEL);
    }

    /**
     * @param string $task
     * @param int    $idShop
     *
     * @return array the task's own summary counters
     */
    private function dispatch($task, $idShop)
    {
        switch ($task) {
            case self::TASK_PROCESS_QUEUE:
                return $this->queueWorker->run(QueueWorker::DEFAULT_BATCH_SIZE, $idShop);
            case self::TASK_PLAN_REMINDERS:
                return $this->reminderPlanner->plan(ReminderPlanner::DEFAULT_BATCH_SIZE, $idShop);
            case self::TASK_EXPIRE_COUPONS:
                $summary = $this->lifecycleManager->expireDueCoupons(CouponLifecycleManager::DEFAULT_BATCH_SIZE, $idShop);
                // Piggyback log retention on the daily maintenance task.
                $this->pruneLogs();

                return $summary;
        }

        return [];
    }

    /**
     * Deletes log entries older than the configured retention window
     * (SNOD_LOG_RETENTION_DAYS; 0 = keep forever). Best-effort.
     *
     * @return void
     */
    private function pruneLogs()
    {
        if ($this->logger === null) {
            return;
        }

        $days = (int) Configuration::get('SNOD_LOG_RETENTION_DAYS');
        if ($days > 0) {
            $this->logger->pruneOlderThan($days);
        }
    }
}
