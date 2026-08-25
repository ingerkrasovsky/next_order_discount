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

use Db;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Read model for reminder planning over the `snod_coupon_link` table.
 *
 * It selects coupons that are ripe for their first or second reminder: still
 * usable (not used, expired or canceled), not yet past their expiry date, bound
 * to a real CartRule, and inside the reminder window (their expiry falls within
 * N days from now). The first-reminder query requires that no first reminder has
 * been recorded yet; the second-reminder query requires the first reminder to
 * have been sent and the second not yet. All bounds are integer-cast, and status
 * values are fixed literals, so the queries carry no injectable input.
 */
class ReminderCandidateRepository
{
    public const TABLE_NAME = 'snod_coupon_link';
    public const PRIMARY_KEY = 'id_snod_coupon_link';

    /**
     * Statuses that make a coupon ineligible for any further reminder.
     */
    private const TERMINAL_STATUSES = ['used', 'expired', 'canceled'];

    /**
     * Coupons due for their first reminder.
     *
     * @param int $days   reminder window in days before expiry
     * @param int $limit  maximum rows to return
     * @param int $idShop optional shop filter (0 = any shop)
     *
     * @return array
     */
    public function findDueForFirstReminder($days, $limit, $idShop = 0)
    {
        return $this->fetch(
            '`first_reminder_at` IS NULL',
            $days,
            $limit,
            $idShop
        );
    }

    /**
     * Coupons due for their second reminder (first reminder already sent).
     *
     * @param int $days   reminder window in days before expiry
     * @param int $limit  maximum rows to return
     * @param int $idShop optional shop filter (0 = any shop)
     *
     * @return array
     */
    public function findDueForSecondReminder($days, $limit, $idShop = 0)
    {
        return $this->fetch(
            '`first_reminder_at` IS NOT NULL AND `second_reminder_at` IS NULL',
            $days,
            $limit,
            $idShop
        );
    }

    /**
     * Runs the shared reminder-candidate query with a stage-specific predicate.
     *
     * @param string $stagePredicate SQL fragment gating the reminder stage
     * @param int    $days
     * @param int    $limit
     * @param int    $idShop
     *
     * @return array
     */
    private function fetch($stagePredicate, $days, $limit, $idShop)
    {
        $days = max(0, (int) $days);
        $limit = max(1, (int) $limit);
        $idShop = (int) $idShop;

        $sql = 'SELECT * FROM `' . _DB_PREFIX_ . self::TABLE_NAME . '`'
            . ' WHERE `status` NOT IN (' . $this->terminalStatusList() . ')'
            . ' AND ' . $stagePredicate
            . ' AND `id_cart_rule` IS NOT NULL AND `id_cart_rule` > 0'
            . ' AND `valid_to` IS NOT NULL'
            . ' AND `valid_to` > NOW()'
            . ' AND `valid_to` <= DATE_ADD(NOW(), INTERVAL ' . $days . ' DAY)';

        if ($idShop > 0) {
            $sql .= ' AND `id_shop` = ' . $idShop;
        }

        $sql .= ' ORDER BY `valid_to` ASC, `' . self::PRIMARY_KEY . '` ASC'
            . ' LIMIT ' . $limit;

        $rows = Db::getInstance()->executeS($sql);

        return is_array($rows) ? $rows : [];
    }

    /**
     * @return string a comma-separated list of quoted terminal statuses
     */
    private function terminalStatusList()
    {
        $quoted = [];
        foreach (self::TERMINAL_STATUSES as $status) {
            $quoted[] = '"' . pSQL($status) . '"';
        }

        return implode(', ', $quoted);
    }
}
