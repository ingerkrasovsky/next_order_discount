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
 * Data-access layer for the `snod_coupon_link` table.
 *
 * The table stores the link between a business event (source order) and the
 * generated CartRule, together with the coupon lifecycle timestamps. All values
 * are persisted through the PrestaShop Db helper, which escapes them with pSQL.
 */
class CouponLinkRepository
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
        'id_lang',
        'id_order_source',
        'id_snod_rule',
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
        'id_lang',
        'id_order_source',
        'id_snod_rule',
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

        if (!\Db::getInstance()->insert(self::TABLE_NAME, $row, true)) {
            return 0;
        }

        return (int) \Db::getInstance()->Insert_ID();
    }

    /**
     * Updates an existing coupon link row.
     *
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
     * Convenience helper to move a coupon to a new lifecycle status.
     *
     * @param int $id
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

        $row = \Db::getInstance()->getRow(
            'SELECT * FROM `' . _DB_PREFIX_ . self::TABLE_NAME . '`'
            . ' WHERE `' . self::PRIMARY_KEY . '` = ' . $id,
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

        $row = \Db::getInstance()->getRow(
            'SELECT * FROM `' . _DB_PREFIX_ . self::TABLE_NAME . '`'
            . ' WHERE `id_shop` = ' . $idShop
            . ' AND `id_order_source` = ' . $idOrderSource,
        );

        return is_array($row) && !empty($row) ? $row : null;
    }

    /**
     * Returns the coupon link issued for a given (shop, source order, rule)
     * triple, enforcing the "one coupon per order per rule" idempotency
     * guarantee of the rule engine.
     *
     * @param int $idShop
     * @param int $idOrderSource
     * @param int $idRule
     *
     * @return array|null
     */
    public function findByShopOrderRule($idShop, $idOrderSource, $idRule)
    {
        $idShop = (int) $idShop;
        $idOrderSource = (int) $idOrderSource;
        $idRule = (int) $idRule;

        $row = \Db::getInstance()->getRow(
            'SELECT * FROM `' . _DB_PREFIX_ . self::TABLE_NAME . '`'
            . ' WHERE `id_shop` = ' . $idShop
            . ' AND `id_order_source` = ' . $idOrderSource
            . ' AND `id_snod_rule` = ' . $idRule,
        );

        return is_array($row) && !empty($row) ? $row : null;
    }

    /**
     * Returns every coupon link issued for a source order (one per matched rule).
     *
     * @param int $idOrderSource
     * @param int $idShop optional shop filter (0 = any shop)
     *
     * @return array list of coupon link rows
     */
    public function findAllByOrderSource($idOrderSource, $idShop = 0)
    {
        $idOrderSource = (int) $idOrderSource;
        $idShop = (int) $idShop;
        if ($idOrderSource <= 0) {
            return [];
        }

        $sql = 'SELECT * FROM `' . _DB_PREFIX_ . self::TABLE_NAME . '`'
            . ' WHERE `id_order_source` = ' . $idOrderSource;
        if ($idShop > 0) {
            $sql .= ' AND `id_shop` = ' . $idShop;
        }

        $rows = \Db::getInstance()->executeS($sql);

        return is_array($rows) ? $rows : [];
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

        $row = \Db::getInstance()->getRow(
            'SELECT * FROM `' . _DB_PREFIX_ . self::TABLE_NAME . '`'
            . ' WHERE `id_cart_rule` = ' . $idCartRule,
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

        $rows = \Db::getInstance()->executeS(
            'SELECT * FROM `' . _DB_PREFIX_ . self::TABLE_NAME . '`'
            . ' WHERE `id_shop` = ' . $idShop
            . ' AND `id_customer` = ' . $idCustomer
            . ' ORDER BY `' . self::PRIMARY_KEY . '` DESC'
            . ' LIMIT ' . $offset . ', ' . $limit,
        );

        return is_array($rows) ? $rows : [];
    }

    /**
     * Fetches a batch of coupons whose validity has lapsed and which are not yet
     * in a terminal state (expired, used or canceled), oldest expiry first.
     *
     * Used by the expiry sweep: because it excludes already-expired coupons, a
     * repeated sweep naturally finds nothing to do, which underpins the
     * idempotency of the lifecycle manager. Bounds are integer-cast and the
     * statuses are fixed constants, so the query carries no injectable input.
     *
     * @param int $limit
     * @param int $idShop optional shop filter (0 = any shop)
     *
     * @return array
     */
    public function findExpired($limit, $idShop = 0)
    {
        $limit = max(1, (int) $limit);
        $idShop = (int) $idShop;

        $sql = 'SELECT * FROM `' . _DB_PREFIX_ . self::TABLE_NAME . '`'
            . ' WHERE `status` NOT IN ('
            . '"' . pSQL(self::STATUS_EXPIRED) . '",'
            . '"' . pSQL(self::STATUS_USED) . '",'
            . '"' . pSQL(self::STATUS_CANCELED) . '")'
            . ' AND `valid_to` IS NOT NULL'
            . ' AND `valid_to` < NOW()';

        if ($idShop > 0) {
            $sql .= ' AND `id_shop` = ' . $idShop;
        }

        $sql .= ' ORDER BY `valid_to` ASC, `' . self::PRIMARY_KEY . '` ASC'
            . ' LIMIT ' . $limit;

        $rows = \Db::getInstance()->executeS($sql);

        return is_array($rows) ? $rows : [];
    }

    /**
     * Aggregates the coupon funnel for the dashboard. Reached-stage metrics
     * (emailed, reminded) are counted from their cumulative timestamps rather
     * than the current status, so a coupon that has since expired still counts
     * as having been emailed and reminded.
     *
     * @param int $idShop optional shop filter (0 = any shop)
     *
     * @return array counters: generated, emailed, reminded, used, expired, canceled
     */
    public function funnelCounts($idShop = 0)
    {
        $idShop = (int) $idShop;
        $where = $idShop > 0 ? ' WHERE `id_shop` = ' . $idShop : '';

        $row = \Db::getInstance()->getRow(
            'SELECT'
            . ' COUNT(*) AS `generated`,'
            . ' SUM(`emailed_at` IS NOT NULL) AS `emailed`,'
            . ' SUM(`first_reminder_at` IS NOT NULL) AS `reminded`,'
            . ' SUM(`used_at` IS NOT NULL OR `status` = "' . pSQL(self::STATUS_USED) . '") AS `used`,'
            . ' SUM(`expired_at` IS NOT NULL OR `status` = "' . pSQL(self::STATUS_EXPIRED) . '") AS `expired`,'
            . ' SUM(`status` = "' . pSQL(self::STATUS_CANCELED) . '") AS `canceled`'
            . ' FROM `' . _DB_PREFIX_ . self::TABLE_NAME . '`' . $where,
        );

        $keys = ['generated', 'emailed', 'reminded', 'used', 'expired', 'canceled'];
        $counts = [];
        foreach ($keys as $key) {
            $counts[$key] = (is_array($row) && isset($row[$key])) ? (int) $row[$key] : 0;
        }

        return $counts;
    }

    /**
     * Day-by-day counters for the Dashboard "Daily dynamics" chart: how many
     * coupons were generated, emailed and used on each of the last N days
     * (today included). Missing days are zero-filled so the chart always
     * gets one point per day, even where nothing happened.
     *
     * @param int $idShop optional shop filter (0 = any shop)
     * @param int $days window size in days
     *
     * @return array list of ['date' => 'Y-m-d', 'generated' => int, 'emailed' => int, 'used' => int]
     */
    public function dailySeries($idShop, $days)
    {
        $idShop = (int) $idShop;
        $days = max(1, (int) $days);
        $shopWhere = $idShop > 0 ? ' AND `id_shop` = ' . $idShop : '';
        $since = date('Y-m-d 00:00:00', strtotime('-' . ($days - 1) . ' days'));

        $generatedByDate = $this->countsByDateColumn('generated_at', $since, $shopWhere);
        $emailedByDate = $this->countsByDateColumn('emailed_at', $since, $shopWhere);
        $usedByDate = $this->countsByDateColumn('used_at', $since, $shopWhere);

        $series = [];
        // Start at midnight so the loop's last step lands exactly on today (a start built
        // from "-N days" would carry the current time and drop today from the series).
        $day = new \DateTime('today');
        $day->modify(sprintf('-%d days', $days - 1));
        $today = new \DateTime('today');
        while ($day <= $today) {
            $key = $day->format('Y-m-d');
            $series[] = [
                'date' => $key,
                'generated' => isset($generatedByDate[$key]) ? $generatedByDate[$key] : 0,
                'emailed' => isset($emailedByDate[$key]) ? $emailedByDate[$key] : 0,
                'used' => isset($usedByDate[$key]) ? $usedByDate[$key] : 0,
            ];
            $day->modify('+1 day');
        }

        return $series;
    }

    /**
     * @param string $dateColumn one of the ALLOWED_COLUMNS datetime columns
     * @param string $since 'Y-m-d H:i:s' lower bound
     * @param string $shopWhere pre-built ' AND `id_shop` = N' fragment, or ''
     *
     * @return array<string,int> counts keyed by 'Y-m-d'
     */
    private function countsByDateColumn($dateColumn, $since, $shopWhere)
    {
        $rows = \Db::getInstance()->executeS(
            'SELECT DATE(`' . $dateColumn . '`) AS `date`, COUNT(*) AS `count`'
            . ' FROM `' . _DB_PREFIX_ . self::TABLE_NAME . '`'
            . ' WHERE `' . $dateColumn . '` IS NOT NULL'
            . ' AND `' . $dateColumn . '` >= "' . pSQL($since) . '"'
            . $shopWhere
            . ' GROUP BY DATE(`' . $dateColumn . '`)',
        );

        $map = [];
        foreach ((is_array($rows) ? $rows : []) as $row) {
            $map[(string) $row['date']] = (int) $row['count'];
        }

        return $map;
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

        return (bool) \Db::getInstance()->delete(
            self::TABLE_NAME,
            self::PRIMARY_KEY . ' = ' . $id,
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
     * @param mixed $value
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
