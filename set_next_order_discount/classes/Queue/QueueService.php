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

use Setecom\NextOrderDiscount\Repository\DispatchQueueRepository;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Application service over the dispatch queue.
 *
 * Order hooks stay fast by never sending emails or running maintenance inline:
 * they enqueue a task here and return. Each task type carries a deterministic
 * correlation id (e.g. "coupon_email:42"), so enqueuing the same logical work
 * twice is a no-op — the unique correlation index and the pre-check together
 * make enqueue idempotent and race-safe. The batch worker (a later stage)
 * consumes tasks via fetchDue() and drives them through the status transitions
 * exposed here.
 */
class QueueService
{
    public const TASK_COUPON_EMAIL = 'coupon_email';
    public const TASK_REMINDER_1 = 'reminder_1';
    public const TASK_REMINDER_2 = 'reminder_2';
    public const TASK_EXPIRE = 'expire';

    /**
     * Every task type the queue accepts. Enqueuing an unknown type is rejected.
     */
    private const TASK_TYPES = [
        self::TASK_COUPON_EMAIL,
        self::TASK_REMINDER_1,
        self::TASK_REMINDER_2,
        self::TASK_EXPIRE,
    ];

    private $repository;

    /**
     * @param DispatchQueueRepository $repository
     */
    public function __construct(DispatchQueueRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Adds a task to the queue in `pending` status. If a task with the same
     * correlation id already exists (including a concurrent insert winning the
     * unique-index race), its id is returned instead of creating a duplicate.
     *
     * @param string      $taskType     one of the TASK_* constants
     * @param array       $payload      JSON-serializable task payload
     * @param int         $idShop
     * @param string|null $correlationId deterministic key; auto-generated when null
     * @param string|null $availableAt  'Y-m-d H:i:s' delay; defaults to now
     *
     * @return int the task id (existing or new), or 0 on rejection/failure
     */
    public function enqueue($taskType, array $payload, $idShop, $correlationId = null, $availableAt = null)
    {
        $taskType = (string) $taskType;
        if (!in_array($taskType, self::TASK_TYPES, true)) {
            return 0;
        }

        if ($correlationId === null || $correlationId === '') {
            $correlationId = $this->generateCorrelationId($taskType);
        }

        $existing = $this->repository->findByCorrelationId($correlationId);
        if ($existing !== null) {
            return (int) $existing[DispatchQueueRepository::PRIMARY_KEY];
        }

        $data = [
            'id_shop' => (int) $idShop,
            'task_type' => $taskType,
            'payload_json' => $this->encodePayload($payload),
            'status' => DispatchQueueRepository::STATUS_PENDING,
            'attempts' => 0,
            'correlation_id' => $correlationId,
        ];
        if ($availableAt !== null && $availableAt !== '') {
            $data['available_at'] = (string) $availableAt;
        }

        $id = (int) $this->repository->enqueue($data);
        if ($id <= 0) {
            // The insert may have lost the unique-correlation race to a
            // concurrent enqueue; return the winner rather than a failure.
            $winner = $this->repository->findByCorrelationId($correlationId);
            if ($winner !== null) {
                return (int) $winner[DispatchQueueRepository::PRIMARY_KEY];
            }
        }

        return $id;
    }

    /**
     * Enqueues the "send the coupon email" task for a freshly issued coupon.
     * The coupon link id keys the correlation, so repeated order hooks never
     * queue a second email for the same coupon.
     *
     * @param int   $idCouponLink ps_snod_coupon_link primary key
     * @param int   $idShop
     * @param array $payload      extra fields (id_customer, coupon_code, ...)
     *
     * @return int
     */
    public function enqueueCouponEmail($idCouponLink, $idShop, array $payload = [])
    {
        $idCouponLink = (int) $idCouponLink;
        if ($idCouponLink <= 0) {
            return 0;
        }

        $payload = array_merge(['id_snod_coupon_link' => $idCouponLink], $payload);

        return $this->enqueue(
            self::TASK_COUPON_EMAIL,
            $payload,
            $idShop,
            self::correlationFor(self::TASK_COUPON_EMAIL, $idCouponLink)
        );
    }

    /**
     * Returns a batch of due `pending` tasks (oldest first).
     *
     * @param int $limit
     * @param int $idShop 0 = any shop
     *
     * @return array
     */
    public function fetchDue($limit = 20, $idShop = 0)
    {
        return $this->repository->fetchPending($limit, $idShop);
    }

    /**
     * @param int $id
     *
     * @return bool
     */
    public function markProcessing($id)
    {
        return $this->repository->markStatus($id, DispatchQueueRepository::STATUS_PROCESSING);
    }

    /**
     * @param int $id
     *
     * @return bool
     */
    public function markDone($id)
    {
        return $this->repository->markStatus($id, DispatchQueueRepository::STATUS_DONE);
    }

    /**
     * @param int         $id
     * @param string|null $error
     *
     * @return bool
     */
    public function markFailed($id, $error = null)
    {
        return $this->repository->markStatus($id, DispatchQueueRepository::STATUS_FAILED, $error);
    }

    /**
     * Decodes a task row's JSON payload into an array.
     *
     * @param array $task a queue row
     *
     * @return array
     */
    public function decodePayload(array $task)
    {
        if (!isset($task['payload_json']) || $task['payload_json'] === '' || $task['payload_json'] === null) {
            return [];
        }

        $decoded = json_decode((string) $task['payload_json'], true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Builds the deterministic correlation id for a keyed task.
     *
     * @param string $taskType
     * @param int    $key
     *
     * @return string
     */
    public static function correlationFor($taskType, $key)
    {
        return (string) $taskType . ':' . (int) $key;
    }

    /**
     * @return array the accepted task types
     */
    public static function taskTypes()
    {
        return self::TASK_TYPES;
    }

    /**
     * @param array $payload
     *
     * @return string JSON, never false
     */
    private function encodePayload(array $payload)
    {
        $json = json_encode($payload);

        return $json === false ? '{}' : $json;
    }

    /**
     * Fallback correlation id for tasks with no natural key. Uniqueness matters
     * more than readability here, so a high-entropy suffix is used.
     *
     * @param string $taskType
     *
     * @return string
     */
    private function generateCorrelationId($taskType)
    {
        return (string) $taskType . ':' . uniqid('', true);
    }
}
