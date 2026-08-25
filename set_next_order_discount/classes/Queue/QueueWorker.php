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

use InvalidArgumentException;
use Setecom\NextOrderDiscount\Logger\ModuleLogger;
use Setecom\NextOrderDiscount\Repository\DispatchQueueRepository;
use Throwable;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Batch worker for the dispatch queue.
 *
 * Fetches a batch of due `pending` tasks and drives each one through the status
 * lifecycle: pending -> processing -> done on success, or pending -> processing
 * -> pending (rescheduled with backoff) on a retryable failure, until the retry
 * policy is exhausted and the task lands in `failed` with its last error
 * recorded. Every task is isolated: a handler returning false, a handler
 * throwing, an unknown task type, or an unexpected bookkeeping error can never
 * abort the rest of the batch.
 *
 * Handlers are injected as a task_type => handler map, so new task types are
 * supported purely by registration, without changing the worker.
 *
 * Concurrency: the worker claims tasks by marking them `processing`, but fetch
 * and claim are not a single atomic step, so it assumes a single concurrent
 * runner. That guarantee is provided by the cron lock (a later stage); callers
 * must not run two workers over the same queue in parallel. Sequential retries
 * are always safe, and the coupon-email handler is itself idempotent.
 */
class QueueWorker
{
    /**
     * Default number of tasks processed per batch.
     */
    public const DEFAULT_BATCH_SIZE = 20;

    /**
     * Upper bound for a persisted error message; last_error is a TEXT column but
     * there is no value in storing unbounded handler output.
     */
    private const MAX_ERROR_LENGTH = 1000;

    /**
     * Log channel for queue worker events.
     */
    private const LOG_CHANNEL = 'queue';

    private $repository;
    private $retryPolicy;
    private $handlers;
    private $logger;

    /**
     * @param DispatchQueueRepository     $repository
     * @param QueueRetryPolicy            $retryPolicy
     * @param QueueTaskHandlerInterface[] $handlers    map of task_type => handler
     * @param ModuleLogger|null           $logger      optional structured logger
     *
     * @throws InvalidArgumentException when a handler does not implement the
     *                                  QueueTaskHandlerInterface contract
     */
    public function __construct(
        DispatchQueueRepository $repository,
        QueueRetryPolicy $retryPolicy,
        array $handlers = [],
        ModuleLogger $logger = null
    ) {
        foreach ($handlers as $type => $handler) {
            if (!$handler instanceof QueueTaskHandlerInterface) {
                throw new InvalidArgumentException(
                    'Handler for task type "' . (string) $type . '" must implement QueueTaskHandlerInterface.'
                );
            }
        }

        $this->repository = $repository;
        $this->retryPolicy = $retryPolicy;
        $this->handlers = $handlers;
        $this->logger = $logger;
    }

    /**
     * Processes one batch of due tasks.
     *
     * @param int $batchSize maximum number of tasks to process
     * @param int $idShop    optional shop filter (0 = any shop)
     *
     * @return array summary counters: fetched, done, retried, failed, errors
     */
    public function run($batchSize = self::DEFAULT_BATCH_SIZE, $idShop = 0)
    {
        $summary = [
            'fetched' => 0,
            'done' => 0,
            'retried' => 0,
            'failed' => 0,
            'errors' => 0,
        ];

        $tasks = $this->repository->fetchPending((int) $batchSize, (int) $idShop);
        $summary['fetched'] = count($tasks);

        foreach ($tasks as $task) {
            try {
                $outcome = $this->processTask($task);
                if (isset($summary[$outcome])) {
                    ++$summary[$outcome];
                }
            } catch (Throwable $e) {
                // A failure in the queue bookkeeping itself must never abort the
                // remaining tasks in the batch.
                ++$summary['errors'];
            }
        }

        return $summary;
    }

    /**
     * Runs a single task through its lifecycle.
     *
     * @param array $task a ps_snod_dispatch_queue row
     *
     * @return string the outcome key: 'done', 'retried' or 'failed'
     */
    private function processTask(array $task)
    {
        $id = (int) $task[DispatchQueueRepository::PRIMARY_KEY];
        $type = isset($task['task_type']) ? (string) $task['task_type'] : '';

        $this->repository->markStatus($id, DispatchQueueRepository::STATUS_PROCESSING);

        if (!isset($this->handlers[$type])) {
            // A missing handler is a permanent condition, so there is no point in
            // retrying: fail the task immediately with a descriptive error.
            $message = 'No handler registered for task type: ' . $type;
            $this->repository->markStatus($id, DispatchQueueRepository::STATUS_FAILED, $this->truncateError($message));
            $this->logTask(ModuleLogger::LEVEL_ERROR, $message, $task);

            return 'failed';
        }

        $error = '';
        try {
            $succeeded = (bool) $this->handlers[$type]->handle($task);
        } catch (Throwable $e) {
            $succeeded = false;
            $error = $e->getMessage();
        }

        if ($succeeded) {
            $this->repository->markStatus($id, DispatchQueueRepository::STATUS_DONE);
            // Per-task success is verbose (one row per processed task): keep it at
            // debug so a busy queue does not flood the log. Info-level visibility
            // of a successful run comes from the cron router's completion entry.
            $this->logTask(ModuleLogger::LEVEL_DEBUG, 'Queue task completed', $task);

            return 'done';
        }

        if ($error === '') {
            $error = 'Handler reported a failure for task type: ' . $type;
        }

        $outcome = $this->handleFailure($id, $task, $error);
        $level = $outcome === 'retried' ? ModuleLogger::LEVEL_WARNING : ModuleLogger::LEVEL_ERROR;
        $this->logTask($level, 'Queue task ' . $outcome, $task, ['error' => $error]);

        return $outcome;
    }

    /**
     * Best-effort structured log for a task outcome, correlated by the task's
     * correlation id.
     *
     * @param string $level
     * @param string $message
     * @param array  $task
     * @param array  $extra additional context
     *
     * @return void
     */
    private function logTask($level, $message, array $task, array $extra = [])
    {
        if ($this->logger === null) {
            return;
        }

        $context = array_merge([
            'task_type' => isset($task['task_type']) ? (string) $task['task_type'] : '',
            'id_dispatch' => isset($task[DispatchQueueRepository::PRIMARY_KEY]) ? (int) $task[DispatchQueueRepository::PRIMARY_KEY] : 0,
        ], $extra);
        $correlationId = isset($task['correlation_id']) ? (string) $task['correlation_id'] : null;

        $this->logger->{$level}($message, $context, $correlationId, self::LOG_CHANNEL);
    }

    /**
     * Applies the retry policy to a failed task: reschedule with backoff while
     * attempts remain, otherwise move it to `failed` with its last error.
     *
     * @param int    $id
     * @param array  $task
     * @param string $error
     *
     * @return string 'retried' or 'failed'
     */
    private function handleFailure($id, array $task, $error)
    {
        $attempts = (int) (isset($task['attempts']) ? $task['attempts'] : 0) + 1;
        $this->repository->incrementAttempts($id);

        $error = $this->truncateError($error);

        if ($this->retryPolicy->canRetry($attempts)) {
            $this->repository->update($id, [
                'status' => DispatchQueueRepository::STATUS_PENDING,
                'available_at' => $this->retryPolicy->getNextAvailableAt($attempts),
                'last_error' => $error,
            ]);

            return 'retried';
        }

        $this->repository->markStatus($id, DispatchQueueRepository::STATUS_FAILED, $error);

        return 'failed';
    }

    /**
     * @param string $error
     *
     * @return string the error message clamped to a safe length
     */
    private function truncateError($error)
    {
        $error = (string) $error;
        if (function_exists('mb_substr')) {
            return mb_substr($error, 0, self::MAX_ERROR_LENGTH);
        }

        return substr($error, 0, self::MAX_ERROR_LENGTH);
    }
}
