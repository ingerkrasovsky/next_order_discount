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
use Setecom\NextOrderDiscount\Rule\RuleConditionSchema;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Data-access layer for the `snod_rule` table and its condition tables.
 *
 * A rule is "conditions -> discount": the scalar columns hold the outcome and
 * range/flag conditions, while the M2M tables described by
 * {@see RuleConditionSchema} hold list conditions. All writes go through the
 * PrestaShop Db helper, which escapes values with pSQL.
 */
class RuleRepository
{
    public const TABLE_NAME = 'snod_rule';
    public const PRIMARY_KEY = 'id_snod_rule';

    public const DISCOUNT_PERCENT = 'percent';
    public const DISCOUNT_AMOUNT = 'amount';
    public const DISCOUNT_FREE_SHIPPING = 'free_shipping';

    public const REMINDER_BASIS_AFTER_EMAIL = 'after_email';
    public const REMINDER_BASIS_BEFORE_EXPIRY = 'before_expiry';

    /**
     * Step between priorities when renumbering, leaving room for manual tweaks.
     */
    private const PRIORITY_STEP = 1;

    private const ALLOWED_COLUMNS = [
        'id_shop',
        'id_shop_group',
        'name',
        'voucher_name',
        'voucher_description',
        'active',
        'priority',
        'stop_further',
        'discount_type',
        'discount_value',
        'validity_days',
        'next_order_min_amount',
        'source_total_min',
        'source_total_max',
        'date_from',
        'date_to',
        'customer_order_count_min',
        'customer_order_count_max',
        'reminder_enabled',
        'reminder_basis',
        'reminder1_days',
        'reminder2_days',
        'group_mode',
        'country_mode',
        'currency_mode',
        'category_mode',
        'manufacturer_mode',
        'code_length',
        'code_type',
        'code_template',
    ];

    private const INT_COLUMNS = [
        'id_shop',
        'id_shop_group',
        'active',
        'priority',
        'stop_further',
        'validity_days',
        'customer_order_count_min',
        'customer_order_count_max',
        'code_length',
        'code_type',
        'reminder_enabled',
        'reminder1_days',
        'reminder2_days',
    ];

    private const FLOAT_COLUMNS = [
        'discount_value',
        'next_order_min_amount',
        'source_total_min',
        'source_total_max',
    ];

    private const NULLABLE_COLUMNS = [
        'date_from',
        'date_to',
        'voucher_name',
        'voucher_description',
        // Per-rule coupon-code settings: an empty value is stored as NULL and
        // resolved to the built-in default at generation time.
        'code_length',
        'code_type',
        'code_template',
        // Per-rule reminder day offsets: empty/0 means "no reminder at this stage".
        'reminder1_days',
        'reminder2_days',
    ];

    /**
     * @return array all supported reminder timing bases
     */
    public static function reminderBases()
    {
        return [self::REMINDER_BASIS_AFTER_EMAIL, self::REMINDER_BASIS_BEFORE_EXPIRY];
    }

    /**
     * @return array all supported discount type keys
     */
    public static function discountTypes()
    {
        return [self::DISCOUNT_PERCENT, self::DISCOUNT_AMOUNT, self::DISCOUNT_FREE_SHIPPING];
    }

    /**
     * @param array $data associative array keyed by column name
     *
     * @return int the new primary key, or 0 on failure
     */
    public function insert(array $data)
    {
        $now = date('Y-m-d H:i:s');
        $row = $this->filterColumns($data);
        $row['created_at'] = $now;
        $row['updated_at'] = $now;

        if (!\Db::getInstance()->insert(self::TABLE_NAME, $row, true)) {
            return 0;
        }

        return (int) \Db::getInstance()->Insert_ID();
    }

    /**
     * @param int $id
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

        return (bool) \Db::getInstance()->update(
            self::TABLE_NAME,
            $row,
            self::PRIMARY_KEY . ' = ' . $id,
            0,
            true,
        );
    }

    /**
     * Deletes a rule. Its condition rows are removed by the ON DELETE CASCADE
     * foreign keys.
     *
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

        return (bool) \Db::getInstance()->delete(
            self::TABLE_NAME,
            self::PRIMARY_KEY . ' = ' . $id,
        );
    }

    /**
     * @param int $id
     * @param bool $active
     *
     * @return bool
     */
    public function setActive($id, $active)
    {
        return $this->update($id, ['active' => $active ? 1 : 0]);
    }

    /**
     * @param int $id
     * @param int $priority
     *
     * @return bool
     */
    public function setPriority($id, $priority)
    {
        return $this->update($id, ['priority' => max(1, (int) $priority)]);
    }

    /**
     * Moves a rule one position up/down among the shop's rules by renumbering
     * priorities in the new order (robust against duplicate/gapped priorities).
     *
     * @param int $idShop
     * @param int $idRule
     * @param string $direction 'up' or 'down'
     *
     * @return bool true if the order changed
     */
    public function reorder($idShop, $idRule, $direction)
    {
        $idRule = (int) $idRule;

        $ids = [];
        foreach ($this->findAllByShop($idShop) as $rule) {
            $ids[] = (int) $rule[self::PRIMARY_KEY];
        }

        $pos = array_search($idRule, $ids, true);
        if ($pos === false) {
            return false;
        }

        if ($direction === 'up' && $pos > 0) {
            $swap = $pos - 1;
        } elseif ($direction === 'down' && $pos < count($ids) - 1) {
            $swap = $pos + 1;
        } else {
            return false;
        }

        $tmp = $ids[$swap];
        $ids[$swap] = $ids[$pos];
        $ids[$pos] = $tmp;

        foreach ($ids as $index => $id) {
            $this->setPriority($id, ($index + 1) * self::PRIORITY_STEP);
        }

        return true;
    }

    /**
     * Renumbers the shop's rules to a gapless 1..N sequence in their current
     * order. Idempotent: only rows whose priority is already wrong are written,
     * so calling it on an already-normalized list performs no writes. Heals data
     * created before the priority step changed (e.g. old 10/20/30 values).
     *
     * @param int $idShop
     *
     * @return void
     */
    public function normalizePriorities($idShop)
    {
        $index = 0;
        foreach ($this->findAllByShop($idShop) as $rule) {
            ++$index;
            if ((int) $rule['priority'] !== $index) {
                $this->setPriority((int) $rule[self::PRIMARY_KEY], $index);
            }
        }
    }

    /**
     * @param int $idShop
     *
     * @return int priority to place a new rule at the end of the list
     */
    public function nextPriority($idShop)
    {
        return ($this->countByShop($idShop) + 1) * self::PRIORITY_STEP;
    }

    /**
     * Loads a rule together with all its list conditions.
     *
     * @param int $id
     *
     * @return array|null the row with an added 'conditions' key, or null
     */
    public function findById($id)
    {
        $id = (int) $id;
        if ($id <= 0) {
            return null;
        }

        $row = \Db::getInstance()->getRow(
            'SELECT * FROM `' . _DB_PREFIX_ . self::TABLE_NAME . '`'
            . ' WHERE `' . self::PRIMARY_KEY . '` = ' . $id,
        );

        if (!is_array($row) || empty($row)) {
            return null;
        }

        $row['conditions'] = $this->getAllConditions($id);

        return $row;
    }

    /**
     * Lists rules for a shop ordered by priority, each with its conditions.
     *
     * @param int $idShop
     * @param bool $activeOnly
     *
     * @return array
     */
    public function findAllByShop($idShop, $activeOnly = false)
    {
        $idShop = (int) $idShop;

        $sql = 'SELECT * FROM `' . _DB_PREFIX_ . self::TABLE_NAME . '` WHERE `id_shop` = ' . $idShop;
        if ($activeOnly) {
            $sql .= ' AND `active` = 1';
        }
        $sql .= ' ORDER BY `priority` ASC, `' . self::PRIMARY_KEY . '` ASC';

        $rows = \Db::getInstance()->executeS($sql);
        if (!is_array($rows)) {
            return [];
        }

        foreach ($rows as &$row) {
            $row['conditions'] = $this->getAllConditions((int) $row[self::PRIMARY_KEY]);
        }
        unset($row);

        return $rows;
    }

    /**
     * @param int $idShop
     *
     * @return int number of rules defined for the shop
     */
    public function countByShop($idShop)
    {
        return (int) \Db::getInstance()->getValue(
            'SELECT COUNT(*) FROM `' . _DB_PREFIX_ . self::TABLE_NAME . '` WHERE `id_shop` = ' . (int) $idShop,
        );
    }

    /**
     * Cheap check used to skip the expensive product-category/manufacturer
     * context gathering when no active rule filters on products.
     *
     * @param int $idShop
     *
     * @return bool true if an active rule restricts by category or manufacturer
     */
    public function hasActiveProductConditions($idShop)
    {
        return (bool) \Db::getInstance()->getValue(
            'SELECT 1 FROM `' . _DB_PREFIX_ . self::TABLE_NAME . '`'
            . ' WHERE `id_shop` = ' . (int) $idShop
            . ' AND `active` = 1'
            . ' AND (`category_mode` <> "all" OR `manufacturer_mode` <> "all")',
        );
    }

    /**
     * Returns the selected entity ids for one condition type of a rule.
     *
     * @param int $idRule
     * @param string $type a RuleConditionSchema type
     *
     * @return array list of ids
     */
    public function getConditions($idRule, $type)
    {
        $idRule = (int) $idRule;
        if ($idRule <= 0 || !RuleConditionSchema::isValidType($type)) {
            return [];
        }

        $column = RuleConditionSchema::column($type);
        $rows = \Db::getInstance()->executeS(
            'SELECT `' . bqSQL($column) . '` FROM `' . _DB_PREFIX_ . bqSQL(RuleConditionSchema::table($type)) . '`'
            . ' WHERE `id_snod_rule` = ' . $idRule,
        );
        if (!is_array($rows)) {
            return [];
        }

        $ids = [];
        foreach ($rows as $row) {
            $ids[] = (int) $row[$column];
        }

        return $ids;
    }

    /**
     * @param int $idRule
     *
     * @return array conditions keyed by type
     */
    public function getAllConditions($idRule)
    {
        $conditions = [];
        foreach (RuleConditionSchema::types() as $type) {
            $conditions[$type] = $this->getConditions($idRule, $type);
        }

        return $conditions;
    }

    /**
     * Replaces the selected entity ids for one condition type of a rule.
     *
     * @param int $idRule
     * @param string $type
     * @param array $ids
     *
     * @return bool
     */
    public function setConditions($idRule, $type, array $ids)
    {
        $idRule = (int) $idRule;
        if ($idRule <= 0 || !RuleConditionSchema::isValidType($type)) {
            return false;
        }

        $table = RuleConditionSchema::table($type);
        $column = RuleConditionSchema::column($type);

        \Db::getInstance()->delete($table, 'id_snod_rule = ' . $idRule);

        foreach ($this->uniquePositiveInts($ids) as $id) {
            $inserted = \Db::getInstance()->insert($table, [
                'id_snod_rule' => $idRule,
                $column => $id,
            ]);
            if (!$inserted) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param array $ids
     *
     * @return array unique positive integers, order preserved
     */
    private function uniquePositiveInts(array $ids)
    {
        $clean = [];
        foreach ($ids as $id) {
            $id = (int) $id;
            if ($id > 0) {
                $clean[$id] = $id;
            }
        }

        return array_values($clean);
    }

    /**
     * Keeps only known columns and normalizes each value to the right type so
     * that no unexpected column can be written and nullable fields become real
     * SQL NULLs.
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
     * @param mixed $value
     *
     * @return int|float|string|null
     */
    private function normalizeValue($column, $value)
    {
        if ($value === null && in_array($column, self::NULLABLE_COLUMNS, true)) {
            return null;
        }

        if (in_array($column, self::INT_COLUMNS, true)) {
            return (int) $value;
        }

        if (in_array($column, self::FLOAT_COLUMNS, true)) {
            return (float) str_replace(',', '.', (string) $value);
        }

        return (string) $value;
    }
}
