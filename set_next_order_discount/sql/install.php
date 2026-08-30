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

$defaultShop = (int) Configuration::get('PS_SHOP_DEFAULT');
if ($defaultShop <= 0) {
    $defaultShop = 1;
}
$defaultShopGroup = (int) Db::getInstance()->getValue(
    'SELECT `id_shop_group` FROM `' . _DB_PREFIX_ . 'shop` WHERE `id_shop` = ' . $defaultShop
);
if ($defaultShopGroup <= 0) {
    $defaultShopGroup = 1;
}

$sql = [];

$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'snod_rule` (
            `id_snod_rule` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `id_shop` INT UNSIGNED NOT NULL,
            `id_shop_group` INT UNSIGNED NOT NULL,
            `name` VARCHAR(128) NOT NULL,
            `voucher_name` VARCHAR(255) NULL DEFAULT NULL,
            `voucher_description` VARCHAR(255) NULL DEFAULT NULL,
            `active` TINYINT(1) NOT NULL DEFAULT 1,
            `priority` INT UNSIGNED NOT NULL DEFAULT 1,
            `stop_further` TINYINT(1) NOT NULL DEFAULT 1,
            `discount_type` VARCHAR(16) NOT NULL DEFAULT "percent",
            `discount_value` DECIMAL(20,6) NOT NULL DEFAULT 0,
            `validity_days` INT UNSIGNED NOT NULL DEFAULT 30,
            `next_order_min_amount` DECIMAL(20,6) NOT NULL DEFAULT 0,
            `source_total_min` DECIMAL(20,6) NOT NULL DEFAULT 0,
            `source_total_max` DECIMAL(20,6) NOT NULL DEFAULT 0,
            `date_from` DATETIME DEFAULT NULL,
            `date_to` DATETIME DEFAULT NULL,
            `customer_order_count_min` INT UNSIGNED NOT NULL DEFAULT 0,
            `customer_order_count_max` INT UNSIGNED NOT NULL DEFAULT 0,
            `reminder_enabled` TINYINT(1) NOT NULL DEFAULT 0,
            `reminder_basis` VARCHAR(16) NOT NULL DEFAULT "after_email",
            `reminder1_days` INT UNSIGNED NULL DEFAULT NULL,
            `reminder2_days` INT UNSIGNED NULL DEFAULT NULL,
            `group_mode` VARCHAR(16) NOT NULL DEFAULT "all",
            `country_mode` VARCHAR(16) NOT NULL DEFAULT "all",
            `currency_mode` VARCHAR(16) NOT NULL DEFAULT "all",
            `category_mode` VARCHAR(16) NOT NULL DEFAULT "all",
            `manufacturer_mode` VARCHAR(16) NOT NULL DEFAULT "all",
            `code_length` INT UNSIGNED NULL DEFAULT NULL,
            `code_type` TINYINT UNSIGNED NULL DEFAULT NULL,
            `code_template` VARCHAR(64) NULL DEFAULT NULL,
            `created_at` DATETIME NOT NULL,
            `updated_at` DATETIME NOT NULL,
            PRIMARY KEY (`id_snod_rule`),
            KEY `idx_snod_rule_shop_active` (`id_shop`, `active`, `priority`)
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8mb4;';

$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'snod_rule_status` (
            `id_snod_rule` INT UNSIGNED NOT NULL,
            `id_order_state` INT UNSIGNED NOT NULL,
            PRIMARY KEY (`id_snod_rule`, `id_order_state`),
            FOREIGN KEY (`id_snod_rule`) REFERENCES `' . _DB_PREFIX_ . 'snod_rule` (`id_snod_rule`)
                ON DELETE CASCADE ON UPDATE CASCADE
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8mb4;';

$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'snod_rule_group` (
            `id_snod_rule` INT UNSIGNED NOT NULL,
            `id_group` INT UNSIGNED NOT NULL,
            PRIMARY KEY (`id_snod_rule`, `id_group`),
            FOREIGN KEY (`id_snod_rule`) REFERENCES `' . _DB_PREFIX_ . 'snod_rule` (`id_snod_rule`)
                ON DELETE CASCADE ON UPDATE CASCADE
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8mb4;';

$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'snod_rule_country` (
            `id_snod_rule` INT UNSIGNED NOT NULL,
            `id_country` INT UNSIGNED NOT NULL,
            PRIMARY KEY (`id_snod_rule`, `id_country`),
            FOREIGN KEY (`id_snod_rule`) REFERENCES `' . _DB_PREFIX_ . 'snod_rule` (`id_snod_rule`)
                ON DELETE CASCADE ON UPDATE CASCADE
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8mb4;';

$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'snod_rule_currency` (
            `id_snod_rule` INT UNSIGNED NOT NULL,
            `id_currency` INT UNSIGNED NOT NULL,
            PRIMARY KEY (`id_snod_rule`, `id_currency`),
            FOREIGN KEY (`id_snod_rule`) REFERENCES `' . _DB_PREFIX_ . 'snod_rule` (`id_snod_rule`)
                ON DELETE CASCADE ON UPDATE CASCADE
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8mb4;';

$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'snod_rule_category` (
            `id_snod_rule` INT UNSIGNED NOT NULL,
            `id_category` INT UNSIGNED NOT NULL,
            PRIMARY KEY (`id_snod_rule`, `id_category`),
            FOREIGN KEY (`id_snod_rule`) REFERENCES `' . _DB_PREFIX_ . 'snod_rule` (`id_snod_rule`)
                ON DELETE CASCADE ON UPDATE CASCADE
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8mb4;';

$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'snod_rule_manufacturer` (
            `id_snod_rule` INT UNSIGNED NOT NULL,
            `id_manufacturer` INT UNSIGNED NOT NULL,
            PRIMARY KEY (`id_snod_rule`, `id_manufacturer`),
            FOREIGN KEY (`id_snod_rule`) REFERENCES `' . _DB_PREFIX_ . 'snod_rule` (`id_snod_rule`)
                ON DELETE CASCADE ON UPDATE CASCADE
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8mb4;';

$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'snod_rule_email` (
            `id_snod_rule` INT UNSIGNED NOT NULL,
            `email_type` VARCHAR(32) NOT NULL,
            `id_lang` INT UNSIGNED NOT NULL,
            `subject` VARCHAR(255) NOT NULL DEFAULT "",
            `html` LONGTEXT DEFAULT NULL,
            `updated_at` DATETIME NOT NULL,
            PRIMARY KEY (`id_snod_rule`, `email_type`, `id_lang`),
            FOREIGN KEY (`id_snod_rule`) REFERENCES `' . _DB_PREFIX_ . 'snod_rule` (`id_snod_rule`)
                ON DELETE CASCADE ON UPDATE CASCADE
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8mb4;';

$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'snod_coupon_link` (
            `id_snod_coupon_link` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `id_shop` INT UNSIGNED NOT NULL,
            `id_shop_group` INT UNSIGNED NOT NULL,
            `id_customer` INT UNSIGNED NOT NULL,
            `id_order_source` INT UNSIGNED NOT NULL,
            `id_snod_rule` INT UNSIGNED NOT NULL DEFAULT 0,
            `id_cart_rule` INT UNSIGNED DEFAULT NULL,
            `coupon_code` VARCHAR(64) NOT NULL,
            `status` VARCHAR(32) NOT NULL,
            `valid_from` DATETIME DEFAULT NULL,
            `valid_to` DATETIME DEFAULT NULL,
            `generated_at` DATETIME DEFAULT NULL,
            `emailed_at` DATETIME DEFAULT NULL,
            `first_reminder_at` DATETIME DEFAULT NULL,
            `second_reminder_at` DATETIME DEFAULT NULL,
            `used_at` DATETIME DEFAULT NULL,
            `expired_at` DATETIME DEFAULT NULL,
            `metadata_json` LONGTEXT DEFAULT NULL,
            `created_at` DATETIME NOT NULL,
            `updated_at` DATETIME NOT NULL,
            PRIMARY KEY (`id_snod_coupon_link`),
            UNIQUE KEY `uniq_snod_coupon_by_shop_order_rule` (`id_shop`, `id_order_source`, `id_snod_rule`),
            KEY `idx_snod_coupon_shop_group` (`id_shop_group`),
            KEY `idx_snod_coupon_customer_status_valid` (`id_customer`, `status`, `valid_to`),
            KEY `idx_snod_coupon_cart_rule` (`id_cart_rule`),
            KEY `idx_snod_coupon_rule` (`id_snod_rule`),
            KEY `idx_snod_coupon_valid_to_status` (`valid_to`, `status`),
            KEY `idx_snod_coupon_code` (`coupon_code`)
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8mb4;';

$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'snod_dispatch_queue` (
            `id_snod_dispatch` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            `id_shop` INT UNSIGNED NOT NULL,
            `task_type` VARCHAR(64) NOT NULL,
            `payload_json` LONGTEXT DEFAULT NULL,
            `status` VARCHAR(32) NOT NULL,
            `attempts` INT UNSIGNED NOT NULL DEFAULT 0,
            `available_at` DATETIME NOT NULL,
            `processed_at` DATETIME DEFAULT NULL,
            `last_error` TEXT DEFAULT NULL,
            `correlation_id` VARCHAR(128) NOT NULL,
            `created_at` DATETIME NOT NULL,
            `updated_at` DATETIME NOT NULL,
            PRIMARY KEY (`id_snod_dispatch`),
            UNIQUE KEY `uniq_snod_dispatch_correlation` (`correlation_id`),
            KEY `idx_snod_dispatch_status_available` (`status`, `available_at`),
            KEY `idx_snod_dispatch_shop` (`id_shop`)
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8mb4;';

$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'snod_cron_lock` (
            `lock_name` VARCHAR(128) NOT NULL,
            `locked_until` DATETIME NOT NULL,
            `owner_token` VARCHAR(128) NOT NULL,
            `updated_at` DATETIME NOT NULL,
            PRIMARY KEY (`lock_name`)
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8mb4;';

$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'snod_log` (
            `id_snod_log` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            `id_shop` INT UNSIGNED NOT NULL DEFAULT 0,
            `level` VARCHAR(16) NOT NULL,
            `channel` VARCHAR(64) NOT NULL DEFAULT "",
            `message` TEXT NOT NULL,
            `context_json` LONGTEXT DEFAULT NULL,
            `correlation_id` VARCHAR(128) DEFAULT NULL,
            `created_at` DATETIME NOT NULL,
            PRIMARY KEY (`id_snod_log`),
            KEY `idx_snod_log_level_date` (`level`, `created_at`),
            KEY `idx_snod_log_channel` (`channel`),
            KEY `idx_snod_log_correlation` (`correlation_id`),
            KEY `idx_snod_log_shop` (`id_shop`)
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8mb4;';

// Seed a sensible default rule (single coupon on any paid order) if none exist.
$sql[] = 'INSERT INTO `' . _DB_PREFIX_ . 'snod_rule`
            (`id_shop`, `id_shop_group`, `name`, `active`, `priority`, `stop_further`,
             `discount_type`, `discount_value`, `validity_days`, `next_order_min_amount`,
             `source_total_min`, `source_total_max`, `customer_order_count_min`,
             `customer_order_count_max`, `group_mode`, `country_mode`, `currency_mode`,
             `category_mode`, `manufacturer_mode`, `created_at`, `updated_at`)
            SELECT ' . $defaultShop . ', ' . $defaultShopGroup . ', "Default discount", 1, 1, 1,
             "percent", 10, 30, 0, 0, 0, 0, 0, "all", "all", "all", "all", "all", NOW(), NOW()
            FROM DUAL
            WHERE NOT EXISTS (SELECT 1 FROM `' . _DB_PREFIX_ . 'snod_rule` r)';

foreach ($sql as $query) {
    if (!Db::getInstance()->execute($query)) {
        return false;
    }
}

return true;
