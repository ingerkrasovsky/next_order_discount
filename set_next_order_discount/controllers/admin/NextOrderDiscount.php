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
 * @property set_next_order_discount $module
 */
class NextOrderDiscountController extends ModuleAdminController
{
    private const COUPON_STATUSES = ['created', 'emailed', 'reminded', 'used', 'expired', 'canceled'];

    public $bootstrap = true;

    private $adminLink;

    public function __construct()
    {
        parent::__construct();

        $this->adminLink = $this->context->link->getAdminLink('NextOrderDiscount');
    }

    public function initContent()
    {
        parent::initContent();

        $domain = 'Modules.Setnextorderdiscount.Admin';
        $tabs = [
            'dashboard' => ['name' => $this->trans('Dashboard', [], $domain), 'url' => $this->adminLink . '&tab=dashboard', 'level' => 0],
            'rules' => ['name' => $this->trans('Rules', [], $domain), 'url' => $this->adminLink . '&tab=rules', 'level' => 0],
            'settings' => ['name' => $this->trans('Settings', [], $domain), 'url' => $this->adminLink . '&tab=settings', 'level' => 0],
            'coupons' => ['name' => $this->trans('Coupons', [], $domain), 'url' => $this->adminLink . '&tab=coupons', 'level' => 0],
            'logs' => ['name' => $this->trans('Logs', [], $domain), 'url' => $this->adminLink . '&tab=logs', 'level' => 0],
            'cron_tools' => ['name' => $this->trans('Cron/Tools', [], $domain), 'url' => $this->adminLink . '&tab=cron_tools', 'level' => 0],
        ];

        $requestTab = Tools::getValue('tab', '');
        if (!is_string($requestTab) || !isset($tabs[$requestTab])) {
            $currentTabCode = 'dashboard';
        } else {
            $currentTabCode = $requestTab;
        }

        $parentCode = $currentTabCode;
        $tabs[$currentTabCode]['active'] = 'Y';
        if (!empty($tabs[$currentTabCode]['level'])) {
            $parentCode = $tabs[$currentTabCode]['parent'];
            $tabs[$parentCode]['active'] = 'Y';
        }

        $currentTab = $tabs[$currentTabCode];

        $functionName = str_replace('_', '', $currentTabCode) . 'Tab';
        if (method_exists($this, $functionName)) {
            $this->{$functionName}();
        }

        $modulePath = '/modules/' . $this->module->name;
        $this->addCSS($modulePath . '/views/css/back.css');
        $this->addJS($modulePath . '/views/js/back.js');

        $this->context->smarty->assign([
            'arTabs' => $tabs,
            'AdminLink' => $this->adminLink,
            'currentTab' => $currentTab,
            'currentTabCode' => $currentTabCode,
            'parentCode' => $parentCode,
            'isPs9' => version_compare(_PS_VERSION_, '9.0.0', '>='),
        ]);

        $this->setTemplate('main.tpl');
    }

    public function settingsTab()
    {
        $errors = [];
        if (Tools::isSubmit('saveSettings')) {
            $errors = $this->saveSettings();
        }

        $currency = $this->context->currency;
        $currencySign = Validate::isLoadedObject($currency) ? (string) $currency->sign : '';

        if (!empty($errors)) {
            // Keep the values the merchant just submitted so they can fix them
            // instead of silently reverting to the persisted configuration.
            $values = [
                'snod_enabled' => $this->getSubmittedString('snod_enabled') === '1' ? 1 : 0,
                'snod_debug_mode' => $this->getSubmittedString('snod_debug_mode') === '1' ? 1 : 0,
                'snod_discount_type' => $this->getSubmittedString('snod_discount_type'),
                'snod_discount_value' => $this->getSubmittedString('snod_discount_value'),
                'snod_validity_days' => $this->getSubmittedString('snod_validity_days'),
                'snod_min_order_amount' => $this->getSubmittedString('snod_min_order_amount'),
            ];
        } else {
            $values = [
                'snod_enabled' => (int) Configuration::get('SNOD_ENABLED'),
                'snod_debug_mode' => (int) Configuration::get('SNOD_DEBUG_MODE'),
                'snod_discount_type' => (string) Configuration::get('SNOD_DISCOUNT_TYPE'),
                'snod_discount_value' => (string) Configuration::get('SNOD_DISCOUNT_VALUE'),
                'snod_validity_days' => (string) Configuration::get('SNOD_VALIDITY_DAYS'),
                'snod_min_order_amount' => (string) Configuration::get('SNOD_MIN_ORDER_AMOUNT'),
            ];
        }

        // Guarantee the <select> always matches one of its options.
        if (!in_array($values['snod_discount_type'], ['percent', 'amount'], true)) {
            $values['snod_discount_type'] = 'percent';
        }

        // Target order statuses (multi-select). On a failed save keep the
        // merchant's selection, otherwise read the persisted CSV.
        $values['snod_order_states'] = OrderState::getOrderStates((int) $this->context->language->id);
        if (!empty($errors)) {
            $submitted = Tools::getValue('snod_target_statuses', []);
            $values['snod_target_statuses'] = $this->normalizeStatusIds(is_array($submitted) ? $submitted : []);
        } else {
            $values['snod_target_statuses'] = $this->parseTargetStatuses((string) Configuration::get('SNOD_TARGET_STATUSES'));
        }

        $values['snodErrors'] = $errors;
        $values['snod_currency_sign'] = $currencySign;

        $this->context->smarty->assign($values);
    }

    /**
     * @param string $csv comma-separated order-state ids
     *
     * @return array unique positive ids
     */
    private function parseTargetStatuses($csv)
    {
        $csv = trim((string) $csv);
        if ($csv === '') {
            return [];
        }

        return $this->normalizeStatusIds(explode(',', $csv));
    }

    /**
     * @param mixed $ids
     *
     * @return array unique positive ids
     */
    private function normalizeStatusIds($ids)
    {
        $result = [];
        foreach ((array) $ids as $id) {
            $id = (int) $id;
            if ($id > 0) {
                $result[$id] = $id;
            }
        }

        return array_values($result);
    }

    /**
     * Validates and persists the Settings form. Values are stored through the
     * Configuration API in the current shop context (multishop aware). Nothing
     * is written unless every field is valid.
     *
     * @return array<int, string> validation error messages (empty on success)
     */
    private function saveSettings()
    {
        $domain = 'Modules.Setnextorderdiscount.Admin';
        $errors = [];

        $enabled = $this->getSubmittedString('snod_enabled') === '1' ? 1 : 0;
        $debugMode = $this->getSubmittedString('snod_debug_mode') === '1' ? 1 : 0;

        $discountType = $this->getSubmittedString('snod_discount_type');
        if (!in_array($discountType, ['percent', 'amount'], true)) {
            $discountType = 'percent';
        }

        $discountValueRaw = str_replace(',', '.', trim($this->getSubmittedString('snod_discount_value')));
        if ($discountValueRaw === '' || !is_numeric($discountValueRaw) || (float) $discountValueRaw <= 0) {
            $errors[] = $this->trans('Discount value must be a number greater than zero.', [], $domain);
        } elseif ($discountType === 'percent' && (float) $discountValueRaw > 100) {
            $errors[] = $this->trans('A percentage discount cannot exceed 100.', [], $domain);
        }

        $validityDaysRaw = trim($this->getSubmittedString('snod_validity_days'));
        if ($validityDaysRaw === '' || !ctype_digit($validityDaysRaw) || (int) $validityDaysRaw < 1) {
            $errors[] = $this->trans('Validity period must be a whole number of days (at least 1).', [], $domain);
        }

        $minOrderRaw = str_replace(',', '.', trim($this->getSubmittedString('snod_min_order_amount')));
        if ($minOrderRaw === '') {
            $minOrderRaw = '0';
        }
        if (!is_numeric($minOrderRaw) || (float) $minOrderRaw < 0) {
            $errors[] = $this->trans('Minimum order amount must be zero or a positive number.', [], $domain);
        }

        if (!empty($errors)) {
            return $errors;
        }

        $targetStatuses = $this->normalizeStatusIds(Tools::getValue('snod_target_statuses', []));

        Configuration::updateValue('SNOD_ENABLED', $enabled);
        Configuration::updateValue('SNOD_DISCOUNT_TYPE', $discountType);
        Configuration::updateValue('SNOD_DISCOUNT_VALUE', (float) $discountValueRaw);
        Configuration::updateValue('SNOD_VALIDITY_DAYS', (int) $validityDaysRaw);
        Configuration::updateValue('SNOD_MIN_ORDER_AMOUNT', (float) $minOrderRaw);
        Configuration::updateValue('SNOD_TARGET_STATUSES', implode(',', $targetStatuses));
        Configuration::updateValue('SNOD_DEBUG_MODE', $debugMode);

        $this->context->smarty->assign(['updatedMessage' => true]);

        return [];
    }

    /**
     * Reads a submitted value as a scalar string, guarding against array
     * injection (e.g. `field[]=x`) that would otherwise trigger PHP
     * "Array to string conversion" warnings.
     *
     * @param string $key
     *
     * @return string
     */
    private function getSubmittedString($key)
    {
        $value = Tools::getValue($key, '');
        if (is_array($value)) {
            return '';
        }

        return (string) $value;
    }

    /**
     * Builds the Coupons tab: a paginated, filterable list of generated coupon
     * links for the current shop context (all shops when in the "all shops"
     * multishop context). Read-only presentation of the ps_snod_coupon_link
     * table, joined to the customer for display.
     *
     * @return void
     */
    public function couponsTab()
    {
        $perPage = 30;

        $statusFilter = $this->getSubmittedString('snod_filter_status');
        if (!in_array($statusFilter, self::COUPON_STATUSES, true)) {
            $statusFilter = '';
        }

        $codeFilter = preg_replace('/[^A-Za-z0-9_\-]/', '', $this->getSubmittedString('snod_filter_code'));
        $codeFilter = is_string($codeFilter) ? Tools::strtoupper($codeFilter) : '';

        $page = (int) Tools::getValue('snod_page', 1);
        if ($page < 1) {
            $page = 1;
        }

        $conditions = [];
        $isAllShops = Shop::isFeatureActive() && Shop::getContext() == Shop::CONTEXT_ALL;
        if (!$isAllShops) {
            $conditions[] = 'cl.`id_shop` = ' . (int) $this->context->shop->id;
        }
        if ($statusFilter !== '') {
            $conditions[] = 'cl.`status` = "' . pSQL($statusFilter) . '"';
        }
        if ($codeFilter !== '') {
            $conditions[] = 'cl.`coupon_code` LIKE "%' . pSQL($codeFilter) . '%"';
        }
        $where = empty($conditions) ? '1' : implode(' AND ', $conditions);

        $total = (int) Db::getInstance()->getValue(
            'SELECT COUNT(*) FROM `' . _DB_PREFIX_ . 'snod_coupon_link` cl WHERE ' . $where
        );

        $totalPages = $total > 0 ? (int) ceil($total / $perPage) : 1;
        if ($page > $totalPages) {
            $page = $totalPages;
        }
        $offset = ($page - 1) * $perPage;

        $rows = Db::getInstance()->executeS(
            'SELECT cl.*,'
            . ' CONCAT(c.`firstname`, " ", c.`lastname`) AS customer_name,'
            . ' c.`email` AS customer_email'
            . ' FROM `' . _DB_PREFIX_ . 'snod_coupon_link` cl'
            . ' LEFT JOIN `' . _DB_PREFIX_ . 'customer` c ON c.`id_customer` = cl.`id_customer`'
            . ' WHERE ' . $where
            . ' ORDER BY cl.`id_snod_coupon_link` DESC'
            . ' LIMIT ' . (int) $offset . ', ' . (int) $perPage
        );
        $rows = is_array($rows) ? $rows : [];

        $baseUrl = $this->adminLink . '&tab=coupons';
        if ($statusFilter !== '') {
            $baseUrl .= '&snod_filter_status=' . urlencode($statusFilter);
        }
        if ($codeFilter !== '') {
            $baseUrl .= '&snod_filter_code=' . urlencode($codeFilter);
        }

        $this->context->smarty->assign([
            'snod_coupons' => $rows,
            'snod_coupon_status_labels' => $this->getCouponStatusLabels(),
            'snod_coupon_statuses' => self::COUPON_STATUSES,
            'snod_filter_status' => $statusFilter,
            'snod_filter_code' => $codeFilter,
            'snod_page' => $page,
            'snod_prev_page' => $page - 1,
            'snod_next_page' => $page + 1,
            'snod_total_pages' => $totalPages,
            'snod_total_coupons' => $total,
            'snod_coupons_base_url' => $baseUrl,
            'snod_admin_token' => (string) $this->token,
        ]);
    }

    /**
     * @return array translated labels keyed by coupon status code
     */
    private function getCouponStatusLabels()
    {
        $domain = 'Modules.Setnextorderdiscount.Admin';

        return [
            'created' => $this->trans('Created', [], $domain),
            'emailed' => $this->trans('Emailed', [], $domain),
            'reminded' => $this->trans('Reminded', [], $domain),
            'used' => $this->trans('Used', [], $domain),
            'expired' => $this->trans('Expired', [], $domain),
            'canceled' => $this->trans('Canceled', [], $domain),
        ];
    }

    /**
     * Builds the Rules tab: a priority-ordered list of discount rules for the
     * current shop, with toggle/reorder/delete actions (POST-redirect-GET).
     *
     * @return void
     */
    public function rulesTab()
    {
        $repository = $this->module->getRuleRepository();
        $idShop = (int) $this->context->shop->id;

        $ruleAction = $this->getSubmittedString('ruleAction');
        $idRule = (int) Tools::getValue('id_rule');
        if ($ruleAction !== '' && $idRule > 0) {
            $this->handleRuleAction($repository, $ruleAction, $idRule, $idShop);
            Tools::redirectAdmin($this->adminLink . '&tab=rules');
        }

        $statusNames = $this->getOrderStateNameMap();
        $currency = $this->context->currency;
        $currencySign = Validate::isLoadedObject($currency) ? (string) $currency->sign : '';

        $view = [];
        foreach ($repository->findAllByShop($idShop) as $rule) {
            $view[] = $this->decorateRuleForList($rule, $statusNames, $currencySign);
        }

        $this->context->smarty->assign([
            'snod_rules' => $view,
            'snod_rules_count' => count($view),
        ]);
    }

    /**
     * Executes a mutating rule action, guarding that the rule belongs to the
     * current shop.
     *
     * @param SnodRuleRepository $repository
     * @param string             $action
     * @param int                $idRule
     * @param int                $idShop
     *
     * @return void
     */
    private function handleRuleAction($repository, $action, $idRule, $idShop)
    {
        $rule = $repository->findById($idRule);
        if ($rule === null || (int) $rule['id_shop'] !== $idShop) {
            return;
        }

        switch ($action) {
            case 'toggle':
                $repository->setActive($idRule, (int) $rule['active'] !== 1);
                break;
            case 'delete':
                $repository->delete($idRule);
                break;
            case 'up':
            case 'down':
                $this->reorderRule($repository, $idShop, $idRule, $action);
                break;
        }
    }

    /**
     * Moves a rule one position up/down by renumbering priorities of the shop's
     * rules in the new order (robust against duplicate/gapped priorities).
     *
     * @param SnodRuleRepository $repository
     * @param int                $idShop
     * @param int                $idRule
     * @param string             $direction 'up' or 'down'
     *
     * @return void
     */
    private function reorderRule($repository, $idShop, $idRule, $direction)
    {
        $ids = [];
        foreach ($repository->findAllByShop($idShop) as $rule) {
            $ids[] = (int) $rule['id_snod_rule'];
        }

        $pos = array_search($idRule, $ids, true);
        if ($pos === false) {
            return;
        }

        if ($direction === 'up' && $pos > 0) {
            $swap = $pos - 1;
        } elseif ($direction === 'down' && $pos < count($ids) - 1) {
            $swap = $pos + 1;
        } else {
            return;
        }

        $tmp = $ids[$swap];
        $ids[$swap] = $ids[$pos];
        $ids[$pos] = $tmp;

        foreach ($ids as $index => $id) {
            $repository->setPriority($id, ($index + 1) * 10);
        }
    }

    /**
     * @return array id_order_state => localized name
     */
    private function getOrderStateNameMap()
    {
        $map = [];
        foreach (OrderState::getOrderStates((int) $this->context->language->id) as $state) {
            $map[(int) $state['id_order_state']] = $state['name'];
        }

        return $map;
    }

    /**
     * Prepares a rule row for the list view (labels, status names, summary).
     *
     * @param array  $rule
     * @param array  $statusNames
     * @param string $currencySign
     *
     * @return array
     */
    private function decorateRuleForList(array $rule, array $statusNames, $currencySign)
    {
        $domain = 'Modules.Setnextorderdiscount.Admin';
        $conditions = isset($rule['conditions']) ? $rule['conditions'] : [];

        $type = (string) $rule['discount_type'];
        $value = $this->formatRuleNumber($rule['discount_value']);
        if ($type === 'amount') {
            $discountLabel = $value . ' ' . $currencySign;
        } elseif ($type === 'free_shipping') {
            $discountLabel = $this->trans('Free shipping', [], $domain);
        } else {
            $discountLabel = $value . ' %';
        }

        $statusList = [];
        foreach ((array) (isset($conditions['status']) ? $conditions['status'] : []) as $sid) {
            $sid = (int) $sid;
            $statusList[] = isset($statusNames[$sid]) ? $statusNames[$sid] : ('#' . $sid);
        }

        return [
            'id_snod_rule' => (int) $rule['id_snod_rule'],
            'name' => (string) $rule['name'],
            'active' => (int) $rule['active'],
            'priority' => (int) $rule['priority'],
            'stop_further' => (int) $rule['stop_further'],
            'discount_label' => $discountLabel,
            'validity_days' => (int) $rule['validity_days'],
            'status_names' => $statusList,
            'summary' => $this->buildConditionSummary($rule, $conditions, $currencySign),
        ];
    }

    /**
     * @param array  $rule
     * @param array  $conditions
     * @param string $currencySign
     *
     * @return array short human-readable condition strings
     */
    private function buildConditionSummary(array $rule, array $conditions, $currencySign)
    {
        $domain = 'Modules.Setnextorderdiscount.Admin';
        $parts = [];

        $min = (float) $rule['source_total_min'];
        $max = (float) $rule['source_total_max'];
        $totalLabel = $this->trans('Order total', [], $domain);
        if ($min > 0 && $max > 0) {
            $parts[] = $totalLabel . ' ' . $this->formatRuleNumber($min) . '–' . $this->formatRuleNumber($max) . ' ' . $currencySign;
        } elseif ($min > 0) {
            $parts[] = $totalLabel . ' ≥ ' . $this->formatRuleNumber($min) . ' ' . $currencySign;
        } elseif ($max > 0) {
            $parts[] = $totalLabel . ' ≤ ' . $this->formatRuleNumber($max) . ' ' . $currencySign;
        }

        $listConditions = [
            'group' => $this->trans('Groups', [], $domain),
            'country' => $this->trans('Countries', [], $domain),
            'currency' => $this->trans('Currencies', [], $domain),
            'category' => $this->trans('Categories', [], $domain),
            'manufacturer' => $this->trans('Brands', [], $domain),
        ];
        foreach ($listConditions as $key => $label) {
            $mode = (string) $rule[$key . '_mode'];
            $count = count((array) (isset($conditions[$key]) ? $conditions[$key] : []));
            if ($mode !== 'all' && $count > 0) {
                $parts[] = $label . ': ' . $mode . ' (' . $count . ')';
            }
        }

        $cmin = (int) $rule['customer_order_count_min'];
        $cmax = (int) $rule['customer_order_count_max'];
        if ($cmin > 0 || $cmax > 0) {
            $parts[] = $this->trans('Order no.', [], $domain) . ' ' . ($cmin > 0 ? $cmin : '…') . '–' . ($cmax > 0 ? $cmax : '…');
        }

        if (!empty($rule['date_from']) || !empty($rule['date_to'])) {
            $parts[] = $this->trans('Date window', [], $domain);
        }

        return $parts;
    }

    /**
     * Formats a decimal for display, trimming trailing zeros.
     *
     * @param mixed $value
     *
     * @return string
     */
    private function formatRuleNumber($value)
    {
        $formatted = number_format((float) $value, 2, '.', '');
        $formatted = rtrim(rtrim($formatted, '0'), '.');

        return $formatted === '' ? '0' : $formatted;
    }
}
