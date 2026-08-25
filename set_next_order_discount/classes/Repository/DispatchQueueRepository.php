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
namespace Setecom\NextOrderDiscount\Repository;

use Db;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Data-access layer for the `snod_dispatch_queue` table.
 *
 * The queue decouples heavy work (emails, reminders, maintenance) from order
 * hooks. Each task carries a unique correlation id used for idempotency.
 */
class DispatchQueueRepository
{
    public const TABLE_NAME = 'snod_dispatch_queue';
    public const PRIMARY_KEY = 'id_snod_dispatch';

    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_DONE = 'done';
    public const STATUS_FAILED = 'failed';

    private const ALLOWED_COLUMNS = [
        'id_shop',
        'task_type',
        'payload_json',
        'status',
        'attempts',
        'available_at',
        'processed_at',
        'last_error',
        'correlation_id',
    ];

    private const INT_COLUMNS = [
        'id_shop',
        'attempts',
    ];

    private const NULLABLE_COLUMNS = [
        'payload_json',
        'processed_at',
        'last_error',
    ];

    /**
     * Adds a new task to the queue. Missing status/attempts/available_at fall
     * back to sane defaults (pending, 0, now).
     *
     * @param array $data associative array keyed by column name
     *
     * @return int the new primary key, or 0 on failure
     */
    public function enqueue(array $data)
    {
        $now = date('Y-m-d H:i:s');
        $row = $this->filterColumns($data);

        if (!isset($row['status']) || $row['status'] === '') {
            $row['status'] = self::STATUS_PENDING;
        }
        if (!isset($row['attempts'])) {
            $row['attempts'] = 0;
        }
        if (!isset($row['available_at']) || $row['available_at'] === null || $row['available_at'] === '') {
            $row['available_at'] = $now;
        }
        $row['created_at'] = $now;
        $row['updated_at'] = $now;

        if (!Db::getInstance()->insert(self::TABLE_NAME, $row, true)) {
            return 0;
        }

        return (int) Db::getInstance()->Insert_ID();
    }

    /**
     * @param int   $id
     * @param array $data associative array keyed by column name
     *
     * @return bool
     */
    public function update($id, array $data)
    {
        $id = (int) $id;
        if ($id <= 0) {
            return false;
        }

        $row = $this->filterColumns($data);
        if (empty($row)) {
            return true;
        }

        $row['updated_at'] = date('Y-m-d H:i:s');

        return (bool) Db::getInstance()->update(
            self::TABLE_NAME,
            $row,
            self::PRIMARY_KEY . ' = ' . $id,
            0,
            true
        );
    }

    /**
     * Moves a task to a new status. Marks processed_at when done and stores an
     * optional error message.
     *
     * @param int         $id
     * @param string      $status
     * @param string|null $error
     *
     * @return bool
     */
    public function markStatus($id, $status, $error = null)
    {
        $data = ['status' => (string) $status];

        if ($status === self::STATUS_DONE) {
            $data['processed_at'] = date('Y-m-d H:i:s');
        }
        if ($error !== null) {
            $data['last_error'] = (string) $error;
        }

        return $this->update($id, $data);
    }

    /**
     * Atomically increments the attempt counter of a task.
     *
     * @param int $id
     *
     * @return bool
     */
    public function incrementAttempts($id)
    {
        $id = (int) $id;
        if ($id <= 0) {
            return false;
        }

        return (bool) Db::getInstance()->update(
            self::TABLE_NAME,
            [
                'attempts' => ['type' => 'sql', 'value' => '`attempts` + 1'],
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            self::PRIMARY_KEY . ' = ' . $id,
            0,
            false
        );
    }

    /**
     * @param int $id
     *
     * @return array|null
     */
    public function findById($id)
    {
        $id = (int) $id;
        if ($id <= 0) {
            return null;
        }

        $row = Db::getInstance()->getRow(
            'SELECT * FROM `' . _DB_PREFIX_ . self::TABLE_NAME . '`'
            . ' WHERE `' . self::PRIMARY_KEY . '` = ' . $id
        );

        return is_array($row) && !empty($row) ? $row : null;
    }

    /**
     * Returns the task carrying a given correlation id, used to avoid enqueuing
     * duplicate work.
     *
     * @param string $correlationId
     *
     * @return array|null
     */
    public function findByCorrelationId($correlationId)
    {
        $correlationId = (string) $correlationId;
        if ($correlationId === '') {
            return null;
        }

        $row = Db::getInstance()->getRow(
            'SELECT * FROM `' . _DB_PREFIX_ . self::TABLE_NAME . '`'
            . ' WHERE `correlation_id` = "' . pSQL($correlationId) . '"'
        );

        return is_array($row) && !empty($row) ? $row : null;
    }

    /**
     * Fetches a batch of due pending tasks (available_at reached), oldest first.
     *
     * @param int $limit
     * @param int $idShop optional shop filter (0 = any shop)
     *
     * @return array
     */
    public function fetchPending($limit = 20, $idShop = 0)
    {
        $limit = max(1, (int) $limit);
        $idShop = (int) $idShop;

        $sql = 'SELECT * FROM `' . _DB_PREFIX_ . self::TABLE_NAME . '`'
            . ' WHERE `status` = "' . pSQL(self::STATUS_PENDING) . '"'
            . ' AND `available_at` <= "' . pSQL(date('Y-m-d H:i:s')) . '"';

        if ($idShop > 0) {
            $sql .= ' AND `id_shop` = ' . $idShop;
        }

        $sql .= ' ORDER BY `available_at` ASC, `' . self::PRIMARY_KEY . '` ASC'
            . ' LIMIT ' . $limit;

        $rows = Db::getInstance()->executeS($sql);

        return is_array($rows) ? $rows : [];
    }

    /**
     * @param int $id
     *
     * @return bool
     */
    public function delete($id)
    {
        $id = (int) $id;
        if ($id <= 0) {
            return false;
        }

        return (bool) Db::getInstance()->delete(
            self::TABLE_NAME,
            self::PRIMARY_KEY . ' = ' . $id
        );
    }

    /**
     * Keeps only known columns and normalizes each value to the right type.
     *
     * @param array $data
     *
     * @return array
     */
    private function filterColumns(array $data)
    {
        $row = [];
        foreach (self::ALLOWED_COLUMNS as $column) {
            if (!array_key_exists($column, $data)) {
                continue;
            }
            $row[$column] = $this->normalizeValue($column, $data[$column]);
        }

        return $row;
    }

    /**
     * @param string $column
     * @param mixed  $value
     *
     * @return int|string|null
     */
    private function normalizeValue($column, $value)
    {
        if ($value === null && in_array($column, self::NULLABLE_COLUMNS, true)) {
            return null;
        }

        if (in_array($column, self::INT_COLUMNS, true)) {
            return (int) $value;
        }

        return (string) $value;
    }
}
