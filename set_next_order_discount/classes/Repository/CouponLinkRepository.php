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
 * Data-access layer for the `snod_coupon_link` table.
 *
 * The table stores the link between a business event (source order) and the
 * generated CartRule, together with the coupon lifecycle timestamps. All values
 * are persisted through the PrestaShop Db helper, which escapes them with pSQL.
 */
class SnodCouponLinkRepository
{
    public const TABLE_NAME = 'snod_coupon_link';
    public const PRIMARY_KEY = 'id_snod_coupon_link';

    public const STATUS_CREATED = 'created';
    public const STATUS_EMAILED = 'emailed';
    public const STATUS_REMINDED = 'reminded';
    public const STATUS_USED = 'used';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_CANCELED = 'canceled';

    private const ALLOWED_COLUMNS = [
        'id_shop',
        'id_shop_group',
        'id_customer',
        'id_order_source',
        'id_cart_rule',
        'coupon_code',
        'status',
        'valid_from',
        'valid_to',
        'generated_at',
        'emailed_at',
        'first_reminder_at',
        'second_reminder_at',
        'used_at',
        'expired_at',
        'metadata_json',
    ];

    private const INT_COLUMNS = [
        'id_shop',
        'id_shop_group',
        'id_customer',
        'id_order_source',
        'id_cart_rule',
    ];

    private const NULLABLE_COLUMNS = [
        'id_cart_rule',
        'valid_from',
        'valid_to',
        'generated_at',
        'emailed_at',
        'first_reminder_at',
        'second_reminder_at',
        'used_at',
        'expired_at',
        'metadata_json',
    ];

    /**
     * Inserts a new coupon link row.
     *
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
     * Updates an existing coupon link row.
     *
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
     * Convenience helper to move a coupon to a new lifecycle status.
     *
     * @param int    $id
     * @param string $status
     *
     * @return bool
     */
    public function updateStatus($id, $status)
    {
        return $this->update($id, ['status' => (string) $status]);
    }

    /**
     * @param int $id
     *
     * @return array|null
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

        return is_array($row) && !empty($row) ? $row : null;
    }

    /**
     * Returns the coupon link issued for a given source order in a shop.
     * Used to enforce the "one coupon per order" idempotency guarantee.
     *
     * @param int $idShop
     * @param int $idOrderSource
     *
     * @return array|null
     */
    public function findByShopAndOrder($idShop, $idOrderSource)
    {
        $idShop = (int) $idShop;
        $idOrderSource = (int) $idOrderSource;

        $row = Db::getInstance()->getRow(
            'SELECT * FROM `' . _DB_PREFIX_ . self::TABLE_NAME . '`'
            . ' WHERE `id_shop` = ' . $idShop
            . ' AND `id_order_source` = ' . $idOrderSource
        );

        return is_array($row) && !empty($row) ? $row : null;
    }

    /**
     * @param int $idCartRule
     *
     * @return array|null
     */
    public function findByCartRule($idCartRule)
    {
        $idCartRule = (int) $idCartRule;
        if ($idCartRule <= 0) {
            return null;
        }

        $row = Db::getInstance()->getRow(
            'SELECT * FROM `' . _DB_PREFIX_ . self::TABLE_NAME . '`'
            . ' WHERE `id_cart_rule` = ' . $idCartRule
        );

        return is_array($row) && !empty($row) ? $row : null;
    }

    /**
     * Lists coupon links for a customer within a shop, newest first.
     *
     * @param int $idShop
     * @param int $idCustomer
     * @param int $limit
     * @param int $offset
     *
     * @return array
     */
    public function findByCustomer($idShop, $idCustomer, $limit = 50, $offset = 0)
    {
        $idShop = (int) $idShop;
        $idCustomer = (int) $idCustomer;
        $limit = max(1, (int) $limit);
        $offset = max(0, (int) $offset);

        $rows = Db::getInstance()->executeS(
            'SELECT * FROM `' . _DB_PREFIX_ . self::TABLE_NAME . '`'
            . ' WHERE `id_shop` = ' . $idShop
            . ' AND `id_customer` = ' . $idCustomer
            . ' ORDER BY `' . self::PRIMARY_KEY . '` DESC'
            . ' LIMIT ' . $offset . ', ' . $limit
        );

        return is_array($rows) ? $rows : [];
    }

    /**
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
