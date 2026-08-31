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
 * Data-access layer for the `snod_log` table.
 *
 * Stores structured module log entries (level, channel, message, JSON context
 * and a correlation id) and reads them back for the Logs admin tab with optional
 * level/channel/shop filtering and pagination. Every value written goes through
 * the escaping Db helpers, and all read filters are escaped or integer-cast.
 */
class LogRepository
{
    public const TABLE_NAME = 'snod_log';
    public const PRIMARY_KEY = 'id_snod_log';

    private const ALLOWED_COLUMNS = [
        'id_shop',
        'level',
        'channel',
        'message',
        'context_json',
        'correlation_id',
    ];

    private const INT_COLUMNS = [
        'id_shop',
    ];

    private const NULLABLE_COLUMNS = [
        'context_json',
        'correlation_id',
    ];

    /**
     * Persists one log entry.
     *
     * @param array $data associative array keyed by column name
     *
     * @return int the new primary key, or 0 on failure
     */
    public function insert(array $data)
    {
        $row = $this->filterColumns($data);
        if (!isset($row['level']) || $row['level'] === '') {
            return 0;
        }
        if (!isset($row['message'])) {
            $row['message'] = '';
        }
        $row['created_at'] = date('Y-m-d H:i:s');

        if (!\Db::getInstance()->insert(self::TABLE_NAME, $row, true)) {
            return 0;
        }

        return (int) \Db::getInstance()->Insert_ID();
    }

    /**
     * Returns the most recent entries matching the filters, newest first.
     *
     * @param array $filters supported: level, channel, correlation_id, id_shop
     * @param int $limit
     * @param int $offset
     *
     * @return array
     */
    public function findRecent(array $filters, $limit = 50, $offset = 0)
    {
        $limit = max(1, (int) $limit);
        $offset = max(0, (int) $offset);

        $sql = 'SELECT * FROM `' . _DB_PREFIX_ . self::TABLE_NAME . '`'
            . ' WHERE ' . $this->buildWhere($filters)
            . ' ORDER BY `' . self::PRIMARY_KEY . '` DESC'
            . ' LIMIT ' . $offset . ', ' . $limit;

        $rows = \Db::getInstance()->executeS($sql);

        return is_array($rows) ? $rows : [];
    }

    /**
     * Counts the entries matching the filters (for pagination).
     *
     * @param array $filters
     *
     * @return int
     */
    public function countRecent(array $filters)
    {
        return (int) \Db::getInstance()->getValue(
            'SELECT COUNT(*) FROM `' . _DB_PREFIX_ . self::TABLE_NAME . '`'
            . ' WHERE ' . $this->buildWhere($filters),
        );
    }

    /**
     * Deletes every stored log entry.
     *
     * @return bool
     */
    public function clear()
    {
        return (bool) \Db::getInstance()->execute(
            'DELETE FROM `' . _DB_PREFIX_ . self::TABLE_NAME . '`',
        );
    }

    /**
     * Deletes log entries older than the given number of days. A value of 0 (or
     * less) keeps everything (no-op), so retention can be disabled from settings.
     *
     * @param int $days
     *
     * @return bool
     */
    public function pruneOlderThan($days)
    {
        $days = (int) $days;
        if ($days <= 0) {
            return true;
        }

        return (bool) \Db::getInstance()->execute(
            'DELETE FROM `' . _DB_PREFIX_ . self::TABLE_NAME . '`'
            . ' WHERE `created_at` < DATE_SUB(NOW(), INTERVAL ' . $days . ' DAY)',
        );
    }

    /**
     * Builds a safe WHERE clause from the supported filters.
     *
     * @param array $filters
     *
     * @return string
     */
    private function buildWhere(array $filters)
    {
        $conditions = [];

        if (isset($filters['level']) && $filters['level'] !== '') {
            $conditions[] = '`level` = "' . pSQL((string) $filters['level']) . '"';
        }
        if (isset($filters['channel']) && $filters['channel'] !== '') {
            $conditions[] = '`channel` = "' . pSQL((string) $filters['channel']) . '"';
        }
        if (isset($filters['correlation_id']) && $filters['correlation_id'] !== '') {
            $conditions[] = '`correlation_id` = "' . pSQL((string) $filters['correlation_id']) . '"';
        }
        if (isset($filters['id_shop']) && (int) $filters['id_shop'] > 0) {
            $conditions[] = '`id_shop` = ' . (int) $filters['id_shop'];
        }

        return empty($conditions) ? '1' : implode(' AND ', $conditions);
    }

    /**
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
     * @param mixed $value
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
