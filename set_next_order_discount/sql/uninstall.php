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
if (!defined('_PS_VERSION_')) {
    exit;
}

$sql = [
    // Rule condition tables reference snod_rule (FK) — drop them first.
    'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'snod_rule_status`',
    'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'snod_rule_group`',
    'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'snod_rule_country`',
    'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'snod_rule_currency`',
    'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'snod_rule_category`',
    'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'snod_rule_manufacturer`',
    'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'snod_rule`',
    'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'snod_dispatch_queue`',
    'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'snod_cron_lock`',
    'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'snod_coupon_link`',
];

foreach ($sql as $query) {
    if (!Db::getInstance()->execute($query)) {
        return false;
    }
}

return true;
