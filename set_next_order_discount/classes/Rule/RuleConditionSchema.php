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

/**
 * Single source of truth for the rule condition types.
 *
 * Each condition maps to an M2M table and a mode column on `snod_rule`. Adding a
 * new condition type means adding one entry here; the repository, form handler,
 * presenter and (later) the matcher all iterate this schema instead of
 * hard-coding table/column names.
 */
class SnodRuleConditionSchema
{
    public const TYPE_STATUS = 'status';
    public const TYPE_GROUP = 'group';
    public const TYPE_COUNTRY = 'country';
    public const TYPE_CURRENCY = 'currency';
    public const TYPE_CATEGORY = 'category';
    public const TYPE_MANUFACTURER = 'manufacturer';

    public const MODE_ALL = 'all';
    public const MODE_INCLUDE = 'include';
    public const MODE_EXCLUDE = 'exclude';

    /**
     * type => [table (no prefix), foreign column, mode column|null, label].
     * A null mode column marks a plain trigger list (statuses) that has no
     * all/include/exclude semantics.
     */
    private const DEFINITIONS = [
        self::TYPE_STATUS => ['snod_rule_status', 'id_order_state', null, 'Statuses'],
        self::TYPE_GROUP => ['snod_rule_group', 'id_group', 'group_mode', 'Groups'],
        self::TYPE_COUNTRY => ['snod_rule_country', 'id_country', 'country_mode', 'Countries'],
        self::TYPE_CURRENCY => ['snod_rule_currency', 'id_currency', 'currency_mode', 'Currencies'],
        self::TYPE_CATEGORY => ['snod_rule_category', 'id_category', 'category_mode', 'Categories'],
        self::TYPE_MANUFACTURER => ['snod_rule_manufacturer', 'id_manufacturer', 'manufacturer_mode', 'Brands'],
    ];

    /**
     * @return array all condition type keys
     */
    public static function types()
    {
        return array_keys(self::DEFINITIONS);
    }

    /**
     * Condition types that support an all/include/exclude mode (everything but
     * the plain status trigger list).
     *
     * @return array
     */
    public static function modeTypes()
    {
        $types = [];
        foreach (self::DEFINITIONS as $type => $definition) {
            if ($definition[2] !== null) {
                $types[] = $type;
            }
        }

        return $types;
    }

    /**
     * @param string $type
     *
     * @return bool
     */
    public static function isValidType($type)
    {
        return isset(self::DEFINITIONS[$type]);
    }

    /**
     * @param string $type
     *
     * @return string m2m table name without the DB prefix
     */
    public static function table($type)
    {
        return self::isValidType($type) ? self::DEFINITIONS[$type][0] : '';
    }

    /**
     * @param string $type
     *
     * @return string foreign key column in the m2m table
     */
    public static function column($type)
    {
        return self::isValidType($type) ? self::DEFINITIONS[$type][1] : '';
    }

    /**
     * @param string $type
     *
     * @return string|null the snod_rule mode column, or null for the status list
     */
    public static function modeColumn($type)
    {
        return self::isValidType($type) ? self::DEFINITIONS[$type][2] : null;
    }

    /**
     * @param string $type
     *
     * @return string untranslated admin label
     */
    public static function label($type)
    {
        return self::isValidType($type) ? self::DEFINITIONS[$type][3] : '';
    }

    /**
     * @param string $mode
     *
     * @return string a valid mode, defaulting to "all"
     */
    public static function normalizeMode($mode)
    {
        $mode = (string) $mode;

        return in_array($mode, [self::MODE_ALL, self::MODE_INCLUDE, self::MODE_EXCLUDE], true)
            ? $mode
            : self::MODE_ALL;
    }
}
