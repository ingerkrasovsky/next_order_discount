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
 * Data-access layer for the `snod_rule` table and its condition tables.
 *
 * A rule is "conditions -> discount": the scalar columns hold the outcome and
 * range/flag conditions, while the six M2M tables hold list conditions
 * (statuses, groups, countries, currencies, categories, manufacturers). All
 * writes go through the PrestaShop Db helper, which escapes values with pSQL.
 */
class SnodRuleRepository
{
    public const TABLE_NAME = 'snod_rule';
    public const PRIMARY_KEY = 'id_snod_rule';

    public const MODE_ALL = 'all';
    public const MODE_INCLUDE = 'include';
    public const MODE_EXCLUDE = 'exclude';

    public const DISCOUNT_PERCENT = 'percent';
    public const DISCOUNT_AMOUNT = 'amount';
    public const DISCOUNT_FREE_SHIPPING = 'free_shipping';

    /**
     * Condition type => [m2m table without prefix, foreign column].
     */
    private const CONDITION_TABLES = [
        'status' => ['snod_rule_status', 'id_order_state'],
        'group' => ['snod_rule_group', 'id_group'],
        'country' => ['snod_rule_country', 'id_country'],
        'currency' => ['snod_rule_currency', 'id_currency'],
        'category' => ['snod_rule_category', 'id_category'],
        'manufacturer' => ['snod_rule_manufacturer', 'id_manufacturer'],
    ];

    private const ALLOWED_COLUMNS = [
        'id_shop',
        'id_shop_group',
        'name',
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
        'group_mode',
        'country_mode',
        'currency_mode',
        'category_mode',
        'manufacturer_mode',
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
    ];

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

        return (bool) Db::getInstance()->delete(
            self::TABLE_NAME,
            self::PRIMARY_KEY . ' = ' . $id
        );
    }

    /**
     * @param int $id
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

        $row = Db::getInstance()->getRow(
            'SELECT * FROM `' . _DB_PREFIX_ . self::TABLE_NAME . '`'
            . ' WHERE `' . self::PRIMARY_KEY . '` = ' . $id
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
     * @param int  $idShop
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

        $rows = Db::getInstance()->executeS($sql);
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
        return (int) Db::getInstance()->getValue(
            'SELECT COUNT(*) FROM `' . _DB_PREFIX_ . self::TABLE_NAME . '` WHERE `id_shop` = ' . (int) $idShop
        );
    }

    /**
     * Returns the selected entity ids for one condition type of a rule.
     *
     * @param int    $idRule
     * @param string $type one of the CONDITION_TABLES keys
     *
     * @return array list of ids
     */
    public function getConditions($idRule, $type)
    {
        $idRule = (int) $idRule;
        if ($idRule <= 0 || !isset(self::CONDITION_TABLES[$type])) {
            return [];
        }

        list($table, $column) = self::CONDITION_TABLES[$type];

        $rows = Db::getInstance()->executeS(
            'SELECT `' . $column . '` FROM `' . _DB_PREFIX_ . $table . '`'
            . ' WHERE `id_snod_rule` = ' . $idRule
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
        foreach (array_keys(self::CONDITION_TABLES) as $type) {
            $conditions[$type] = $this->getConditions($idRule, $type);
        }

        return $conditions;
    }

    /**
     * Replaces the selected entity ids for one condition type of a rule.
     *
     * @param int    $idRule
     * @param string $type
     * @param array  $ids
     *
     * @return bool
     */
    public function setConditions($idRule, $type, array $ids)
    {
        $idRule = (int) $idRule;
        if ($idRule <= 0 || !isset(self::CONDITION_TABLES[$type])) {
            return false;
        }

        list($table, $column) = self::CONDITION_TABLES[$type];

        Db::getInstance()->delete($table, 'id_snod_rule = ' . $idRule);

        $clean = [];
        foreach ($ids as $id) {
            $id = (int) $id;
            if ($id > 0) {
                $clean[$id] = $id;
            }
        }

        foreach ($clean as $id) {
            $inserted = Db::getInstance()->insert($table, [
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
     * @param mixed  $value
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
