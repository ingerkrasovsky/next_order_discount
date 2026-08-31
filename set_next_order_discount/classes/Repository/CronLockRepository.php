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

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Data-access layer for the `snod_cron_lock` table.
 *
 * Provides a soft, database-backed mutex with TTL to prevent concurrent cron
 * processes from running the same task. All time arithmetic is done server-side
 * with NOW()/DATE_ADD so the logic is immune to PHP/MySQL timezone drift.
 */
class CronLockRepository
{
    public const TABLE_NAME = 'snod_cron_lock';

    /**
     * Attempts to acquire (or steal an expired) lock atomically.
     *
     * @param string $lockName
     * @param string $ownerToken caller-unique token identifying the holder
     * @param int $ttlSeconds lifetime of the lock in seconds
     *
     * @return bool true if the lock is now held by $ownerToken
     */
    public function acquire($lockName, $ownerToken, $ttlSeconds)
    {
        $name = pSQL((string) $lockName);
        $token = pSQL((string) $ownerToken);
        $ttl = (int) $ttlSeconds;

        if ($name === '' || $token === '' || $ttl <= 0) {
            return false;
        }

        // The ON DUPLICATE clause only overwrites the row when the current lock
        // has already expired. `locked_until` is written last so the IF()
        // conditions above it still read the previous value.
        $sql = 'INSERT INTO `' . _DB_PREFIX_ . self::TABLE_NAME . '`'
            . ' (`lock_name`, `locked_until`, `owner_token`, `updated_at`)'
            . ' VALUES ("' . $name . '", DATE_ADD(NOW(), INTERVAL ' . $ttl . ' SECOND), "' . $token . '", NOW())'
            . ' ON DUPLICATE KEY UPDATE'
            . ' `owner_token` = IF(`locked_until` < NOW(), VALUES(`owner_token`), `owner_token`),'
            . ' `updated_at` = IF(`locked_until` < NOW(), VALUES(`updated_at`), `updated_at`),'
            . ' `locked_until` = IF(`locked_until` < NOW(), VALUES(`locked_until`), `locked_until`)';

        if (!\Db::getInstance()->execute($sql)) {
            return false;
        }

        return $this->isOwnedBy($lockName, $ownerToken);
    }

    /**
     * Extends an owned, still-valid lock.
     *
     * @param string $lockName
     * @param string $ownerToken
     * @param int $ttlSeconds new lifetime in seconds from now
     *
     * @return bool true if the lock was actually extended
     */
    public function refresh($lockName, $ownerToken, $ttlSeconds)
    {
        $name = pSQL((string) $lockName);
        $token = pSQL((string) $ownerToken);
        $ttl = (int) $ttlSeconds;

        if ($name === '' || $token === '' || $ttl <= 0) {
            return false;
        }

        $sql = 'UPDATE `' . _DB_PREFIX_ . self::TABLE_NAME . '`'
            . ' SET `locked_until` = DATE_ADD(NOW(), INTERVAL ' . $ttl . ' SECOND), `updated_at` = NOW()'
            . ' WHERE `lock_name` = "' . $name . '"'
            . ' AND `owner_token` = "' . $token . '"'
            . ' AND `locked_until` >= NOW()';

        if (!\Db::getInstance()->execute($sql)) {
            return false;
        }

        return \Db::getInstance()->Affected_Rows() > 0;
    }

    /**
     * Releases a lock, but only if it is still owned by the given token.
     *
     * @param string $lockName
     * @param string $ownerToken
     *
     * @return bool
     */
    public function release($lockName, $ownerToken)
    {
        $name = pSQL((string) $lockName);
        $token = pSQL((string) $ownerToken);

        if ($name === '' || $token === '') {
            return false;
        }

        return (bool) \Db::getInstance()->execute(
            'DELETE FROM `' . _DB_PREFIX_ . self::TABLE_NAME . '`'
            . ' WHERE `lock_name` = "' . $name . '"'
            . ' AND `owner_token` = "' . $token . '"',
        );
    }

    /**
     * @param string $lockName
     *
     * @return bool true if a non-expired lock exists
     */
    public function isLocked($lockName)
    {
        $name = pSQL((string) $lockName);
        if ($name === '') {
            return false;
        }

        return (bool) \Db::getInstance()->getValue(
            'SELECT 1 FROM `' . _DB_PREFIX_ . self::TABLE_NAME . '`'
            . ' WHERE `lock_name` = "' . $name . '"'
            . ' AND `locked_until` >= NOW()',
        );
    }

    /**
     * @param string $lockName
     * @param string $ownerToken
     *
     * @return bool true if the given token currently holds the lock
     */
    public function isOwnedBy($lockName, $ownerToken)
    {
        $name = pSQL((string) $lockName);
        $token = pSQL((string) $ownerToken);

        if ($name === '' || $token === '') {
            return false;
        }

        return (bool) \Db::getInstance()->getValue(
            'SELECT 1 FROM `' . _DB_PREFIX_ . self::TABLE_NAME . '`'
            . ' WHERE `lock_name` = "' . $name . '"'
            . ' AND `owner_token` = "' . $token . '"'
            . ' AND `locked_until` >= NOW()',
        );
    }

    /**
     * @param string $lockName
     *
     * @return array|null
     */
    public function find($lockName)
    {
        $name = pSQL((string) $lockName);
        if ($name === '') {
            return null;
        }

        $row = \Db::getInstance()->getRow(
            'SELECT * FROM `' . _DB_PREFIX_ . self::TABLE_NAME . '`'
            . ' WHERE `lock_name` = "' . $name . '"',
        );

        return is_array($row) && !empty($row) ? $row : null;
    }

    /**
     * Removes all expired locks. Safe to run at any time.
     *
     * @return bool
     */
    public function purgeExpired()
    {
        return (bool) \Db::getInstance()->execute(
            'DELETE FROM `' . _DB_PREFIX_ . self::TABLE_NAME . '`'
            . ' WHERE `locked_until` < NOW()',
        );
    }
}
