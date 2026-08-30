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
use Setecom\NextOrderDiscount\Repository\RuleRepository;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Read model for reminder planning over the `snod_coupon_link` table.
 *
 * Reminder timing is configured per rule. The rule carries `reminder_enabled`,
 * a `reminder_basis` (`after_email` = N days after the coupon email, or
 * `before_expiry` = when expiry is within N days) and the day offsets
 * `reminder1_days` / `reminder2_days` (0/NULL disables that stage), so the query
 * joins the rule and compares against its own columns — no global window.
 *
 * A candidate must still be usable (not used, expired or canceled), not yet past
 * its expiry, bound to a real CartRule, and already emailed. The two reminders
 * are independent slots: each fires on its own day offset and is gated only by
 * its own timestamp (so either, both, or neither can be configured). All bounds
 * are integer-cast and status values are fixed literals, so the queries carry no
 * injectable input.
 */
class ReminderCandidateRepository
{
    public const TABLE_NAME = 'snod_coupon_link';
    public const RULE_TABLE_NAME = 'snod_rule';
    public const PRIMARY_KEY = 'id_snod_coupon_link';

    /**
     * Statuses that make a coupon ineligible for any further reminder.
     */
    private const TERMINAL_STATUSES = ['used', 'expired', 'canceled'];

    /**
     * Coupons due for their first reminder.
     *
     * @param int $limit  maximum rows to return
     * @param int $idShop optional shop filter (0 = any shop)
     *
     * @return array
     */
    public function findDueForFirstReminder($limit, $idShop = 0)
    {
        return $this->fetch(
            'cl.`first_reminder_at` IS NULL',
            'reminder1_days',
            $limit,
            $idShop
        );
    }

    /**
     * Coupons due for their second reminder (independent of the first).
     *
     * @param int $limit  maximum rows to return
     * @param int $idShop optional shop filter (0 = any shop)
     *
     * @return array
     */
    public function findDueForSecondReminder($limit, $idShop = 0)
    {
        return $this->fetch(
            'cl.`second_reminder_at` IS NULL',
            'reminder2_days',
            $limit,
            $idShop
        );
    }

    /**
     * Runs the shared reminder-candidate query for one stage.
     *
     * @param string $stagePredicate SQL fragment gating the reminder stage
     * @param string $daysColumn     the rule column holding this stage's offset
     * @param int    $limit
     * @param int    $idShop
     *
     * @return array
     */
    private function fetch($stagePredicate, $daysColumn, $limit, $idShop)
    {
        $limit = max(1, (int) $limit);
        $idShop = (int) $idShop;
        $daysColumn = preg_replace('/[^a-z0-9_]/', '', (string) $daysColumn);

        $sql = 'SELECT cl.* FROM `' . _DB_PREFIX_ . self::TABLE_NAME . '` cl'
            . ' JOIN `' . _DB_PREFIX_ . self::RULE_TABLE_NAME . '` r ON r.`id_snod_rule` = cl.`id_snod_rule`'
            . ' WHERE cl.`status` NOT IN (' . $this->terminalStatusList() . ')'
            . ' AND ' . $stagePredicate
            . ' AND cl.`id_cart_rule` IS NOT NULL AND cl.`id_cart_rule` > 0'
            . ' AND cl.`emailed_at` IS NOT NULL'
            . ' AND cl.`valid_to` IS NOT NULL'
            . ' AND cl.`valid_to` > NOW()'
            . ' AND r.`reminder_enabled` = 1'
            . ' AND r.`' . $daysColumn . '` IS NOT NULL AND r.`' . $daysColumn . '` > 0'
            // Timing is relative to the rule's chosen basis: N days after the coupon
            // email, or when expiry is within N days.
            . ' AND ('
            . '(r.`reminder_basis` = "' . pSQL(RuleRepository::REMINDER_BASIS_BEFORE_EXPIRY) . '"'
            . ' AND cl.`valid_to` <= DATE_ADD(NOW(), INTERVAL r.`' . $daysColumn . '` DAY))'
            . ' OR (r.`reminder_basis` <> "' . pSQL(RuleRepository::REMINDER_BASIS_BEFORE_EXPIRY) . '"'
            . ' AND DATE_ADD(cl.`emailed_at`, INTERVAL r.`' . $daysColumn . '` DAY) <= NOW())'
            . ')';

        if ($idShop > 0) {
            $sql .= ' AND cl.`id_shop` = ' . $idShop;
        }

        $sql .= ' ORDER BY cl.`valid_to` ASC, cl.`' . self::PRIMARY_KEY . '` ASC'
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
