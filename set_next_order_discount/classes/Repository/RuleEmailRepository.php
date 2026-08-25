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
 * Data-access layer for the `snod_rule_email` table.
 *
 * Each rule owns its own email content (subject + HTML) per email type and per
 * language. Rows are seeded from the shipped default templates when a rule is
 * created, then edited freely by the merchant; the mailers read the rule's own
 * content at send time. All writes go through pSQL-escaped statements.
 */
class RuleEmailRepository
{
    public const TABLE_NAME = 'snod_rule_email';

    public const TYPE_COUPON = 'coupon';
    public const TYPE_REMINDER_1 = 'reminder_1';
    public const TYPE_REMINDER_2 = 'reminder_2';

    /**
     * @return string[] the email types stored per rule
     */
    public static function types()
    {
        return [self::TYPE_COUPON, self::TYPE_REMINDER_1, self::TYPE_REMINDER_2];
    }

    /**
     * Returns the stored subject/html for a rule email in one language.
     *
     * @param int    $idRule
     * @param string $emailType
     * @param int    $idLang
     *
     * @return array|null ['subject' => string, 'html' => string] or null
     */
    public function findContent($idRule, $emailType, $idLang)
    {
        $row = Db::getInstance()->getRow(
            'SELECT `subject`, `html` FROM `' . _DB_PREFIX_ . self::TABLE_NAME . '`'
            . ' WHERE `id_snod_rule` = ' . (int) $idRule
            . ' AND `email_type` = "' . pSQL((string) $emailType) . '"'
            . ' AND `id_lang` = ' . (int) $idLang
        );

        if (!is_array($row) || empty($row)) {
            return null;
        }

        return [
            'subject' => (string) $row['subject'],
            'html' => (string) $row['html'],
        ];
    }

    /**
     * Loads every stored email for a rule, indexed by [type][id_lang].
     *
     * @param int $idRule
     *
     * @return array
     */
    public function findAllForRule($idRule)
    {
        $rows = Db::getInstance()->executeS(
            'SELECT `email_type`, `id_lang`, `subject`, `html` FROM `' . _DB_PREFIX_ . self::TABLE_NAME . '`'
            . ' WHERE `id_snod_rule` = ' . (int) $idRule
        );

        $out = [];
        foreach ((array) $rows as $row) {
            $type = (string) $row['email_type'];
            $idLang = (int) $row['id_lang'];
            $out[$type][$idLang] = [
                'subject' => (string) $row['subject'],
                'html' => (string) $row['html'],
            ];
        }

        return $out;
    }

    /**
     * Inserts or updates one rule email row.
     *
     * @param int    $idRule
     * @param string $emailType
     * @param int    $idLang
     * @param string $subject
     * @param string $html
     *
     * @return bool
     */
    public function save($idRule, $emailType, $idLang, $subject, $html)
    {
        $idRule = (int) $idRule;
        $idLang = (int) $idLang;
        $emailType = (string) $emailType;
        if ($idRule <= 0 || $idLang <= 0 || $emailType === '') {
            return false;
        }

        $now = date('Y-m-d H:i:s');

        return (bool) Db::getInstance()->execute(
            'INSERT INTO `' . _DB_PREFIX_ . self::TABLE_NAME . '`'
            . ' (`id_snod_rule`, `email_type`, `id_lang`, `subject`, `html`, `updated_at`)'
            . ' VALUES (' . $idRule . ', "' . pSQL($emailType) . '", ' . $idLang . ','
            . ' "' . pSQL((string) $subject) . '", "' . pSQL((string) $html, true) . '", "' . pSQL($now) . '")'
            . ' ON DUPLICATE KEY UPDATE'
            . ' `subject` = VALUES(`subject`),'
            . ' `html` = VALUES(`html`),'
            . ' `updated_at` = VALUES(`updated_at`)'
        );
    }
}
