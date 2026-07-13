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

    /**
     * List conditions exposed in the rule form (mode + multi-select).
     */
    private const EDITABLE_MODE_CONDITIONS = ['group', 'country', 'currency', 'category', 'manufacturer'];

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
            'rule_edit' => ['name' => $this->trans('Rule', [], $domain), 'url' => $this->adminLink . '&tab=rule_edit', 'level' => 1, 'parent' => 'rules'],
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

    /**
     * Global settings only: module active, debug and coupon code format.
     * Discount, validity, minimum and conditions are configured per rule.
     *
     * @return void
     */
    public function settingsTab()
    {
        $errors = [];
        if (Tools::isSubmit('saveSettings')) {
            $errors = $this->saveSettings();
        }

        if (!empty($errors)) {
            // Keep the submitted values so the merchant can fix them.
            $values = [
                'snod_enabled' => $this->getSubmittedString('snod_enabled') === '1' ? 1 : 0,
                'snod_debug_mode' => $this->getSubmittedString('snod_debug_mode') === '1' ? 1 : 0,
                'snod_code_prefix' => $this->getSubmittedString('snod_code_prefix'),
                'snod_code_length' => $this->getSubmittedString('snod_code_length'),
                'snod_code_mask' => $this->getSubmittedString('snod_code_mask'),
            ];
        } else {
            $values = [
                'snod_enabled' => (int) Configuration::get('SNOD_ENABLED'),
                'snod_debug_mode' => (int) Configuration::get('SNOD_DEBUG_MODE'),
                'snod_code_prefix' => (string) Configuration::get('SNOD_CODE_PREFIX'),
                'snod_code_length' => (string) Configuration::get('SNOD_CODE_LENGTH'),
                'snod_code_mask' => (string) Configuration::get('SNOD_CODE_MASK'),
            ];
        }

        $values['snodErrors'] = $errors;

        $this->context->smarty->assign($values);
    }

    /**
     * Validates and persists the global settings through the Configuration API
     * (current shop context, multishop aware). Nothing is written unless valid.
     *
     * @return array validation error messages (empty on success)
     */
    private function saveSettings()
    {
        $domain = 'Modules.Setnextorderdiscount.Admin';
        $errors = [];

        $enabled = $this->getSubmittedString('snod_enabled') === '1' ? 1 : 0;
        $debugMode = $this->getSubmittedString('snod_debug_mode') === '1' ? 1 : 0;
        $codePrefix = $this->getSubmittedString('snod_code_prefix');
        $codeMask = $this->getSubmittedString('snod_code_mask');

        $codeLengthRaw = trim($this->getSubmittedString('snod_code_length'));
        if ($codeLengthRaw === '' || !ctype_digit($codeLengthRaw) || (int) $codeLengthRaw < 1) {
            $errors[] = $this->trans('Code length must be a whole number greater than zero.', [], $domain);
        }

        if (!empty($errors)) {
            return $errors;
        }

        Configuration::updateValue('SNOD_ENABLED', $enabled);
        Configuration::updateValue('SNOD_DEBUG_MODE', $debugMode);
        Configuration::updateValue('SNOD_CODE_PREFIX', $codePrefix);
        Configuration::updateValue('SNOD_CODE_LENGTH', (int) $codeLengthRaw);
        Configuration::updateValue('SNOD_CODE_MASK', $codeMask);

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
            . ' c.`email` AS customer_email,'
            . ' r.`name` AS rule_name'
            . ' FROM `' . _DB_PREFIX_ . 'snod_coupon_link` cl'
            . ' LEFT JOIN `' . _DB_PREFIX_ . 'customer` c ON c.`id_customer` = cl.`id_customer`'
            . ' LEFT JOIN `' . _DB_PREFIX_ . 'snod_rule` r ON r.`id_snod_rule` = cl.`id_snod_rule`'
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

        $presenter = $this->module->getRulePresenter();
        $statusNames = $this->getOrderStateNameMap();
        $labels = $this->getRuleConditionLabels();
        $currencySign = $this->getCurrencySign();

        $view = [];
        foreach ($repository->findAllByShop($idShop) as $rule) {
            $view[] = $presenter->present($rule, $currencySign, $statusNames, $labels);
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
                $repository->reorder($idShop, $idRule, $action);
                break;
        }
    }

    /**
     * Add/edit form for a single discount rule. Delegates validation and
     * persistence to SnodRuleFormHandler.
     *
     * @return void
     */
    public function ruleeditTab()
    {
        $handler = $this->module->getRuleFormHandler();
        $repository = $this->module->getRuleRepository();
        $idShop = (int) $this->context->shop->id;
        $idRule = (int) Tools::getValue('id_rule');

        $input = $this->readRuleInput();
        $errors = [];
        if (Tools::isSubmit('saveRule')) {
            $result = $handler->save($input, $idShop, $idRule, (int) $this->context->shop->id_shop_group);
            if (empty($result['errors'])) {
                Tools::redirectAdmin($this->adminLink . '&tab=rules');
            }
            $errors = $this->translateRuleErrors($result['errors']);
        }

        $rule = null;
        if ($idRule > 0) {
            $rule = $repository->findById($idRule);
            if ($rule === null || (int) $rule['id_shop'] !== $idShop) {
                $rule = null;
                $idRule = 0;
            }
        }

        if (Tools::isSubmit('saveRule')) {
            $form = $handler->valuesFromInput($input);
        } elseif ($rule !== null) {
            $form = $handler->valuesFromRule($rule);
        } else {
            $form = $handler->defaultValues();
        }

        $this->context->smarty->assign([
            'snod_rule_errors' => $errors,
            'snod_rule_id' => $idRule,
            'snod_is_edit' => $idRule > 0,
            'snod_rule_form' => $form,
            'snod_order_states' => OrderState::getOrderStates((int) $this->context->language->id),
            'snod_conditions' => $this->getRuleConditionBlocks($form),
            'snod_currency_sign' => $this->getCurrencySign(),
        ]);
    }

    /**
     * Builds the list-condition blocks (mode + entity multi-select) rendered in
     * the rule form: customer groups, countries, currencies.
     *
     * @param array $form current form values
     *
     * @return array
     */
    private function getRuleConditionBlocks(array $form)
    {
        $idLang = (int) $this->context->language->id;
        $domain = 'Modules.Setnextorderdiscount.Admin';

        $sources = [
            'group' => [
                $this->trans('Customer groups', [], $domain),
                $this->normalizeEntities(Group::getGroups($idLang), 'id_group'),
            ],
            'country' => [
                $this->trans('Countries', [], $domain),
                $this->normalizeEntities(Country::getCountries($idLang, true), 'id_country'),
            ],
            'currency' => [
                $this->trans('Currencies', [], $domain),
                $this->normalizeEntities(Currency::getCurrencies(), 'id_currency'),
            ],
            'category' => [
                $this->trans('Product categories', [], $domain),
                $this->normalizeEntities(Category::getSimpleCategories($idLang), 'id_category'),
            ],
            'manufacturer' => [
                $this->trans('Brands', [], $domain),
                $this->normalizeEntities(Manufacturer::getManufacturers(false, $idLang), 'id_manufacturer'),
            ],
        ];

        $blocks = [];
        foreach (self::EDITABLE_MODE_CONDITIONS as $type) {
            if (!isset($sources[$type])) {
                continue;
            }
            $blocks[] = [
                'type' => $type,
                'label' => $sources[$type][0],
                'list' => $sources[$type][1],
                'mode' => isset($form[$type . '_mode']) ? $form[$type . '_mode'] : 'all',
                'ids' => isset($form[$type . '_ids']) ? $form[$type . '_ids'] : [],
            ];
        }

        return $blocks;
    }

    /**
     * Normalizes a PrestaShop entity list to a flat [{id, name}] shape.
     *
     * @param mixed  $rows
     * @param string $idKey
     *
     * @return array
     */
    private function normalizeEntities($rows, $idKey)
    {
        $entities = [];
        foreach ((array) $rows as $row) {
            if (!isset($row[$idKey])) {
                continue;
            }
            $id = (int) $row[$idKey];
            $entities[] = [
                'id' => $id,
                'name' => isset($row['name']) ? $row['name'] : ('#' . $id),
            ];
        }

        return $entities;
    }

    /**
     * Reads the raw rule form fields into a plain input array for the handler.
     *
     * @return array
     */
    private function readRuleInput()
    {
        $statuses = Tools::getValue('snod_rule_statuses', []);

        $input = [
            'name' => $this->getSubmittedString('snod_rule_name'),
            'active' => $this->getSubmittedString('snod_rule_active'),
            'stop_further' => $this->getSubmittedString('snod_rule_stop'),
            'discount_type' => $this->getSubmittedString('snod_rule_discount_type'),
            'discount_value' => $this->getSubmittedString('snod_rule_discount_value'),
            'validity_days' => $this->getSubmittedString('snod_rule_validity_days'),
            'next_order_min_amount' => $this->getSubmittedString('snod_rule_next_min'),
            'source_total_min' => $this->getSubmittedString('snod_rule_source_min'),
            'source_total_max' => $this->getSubmittedString('snod_rule_source_max'),
            'date_from' => $this->getSubmittedString('snod_rule_date_from'),
            'date_to' => $this->getSubmittedString('snod_rule_date_to'),
            'customer_order_count_min' => $this->getSubmittedString('snod_rule_order_count_min'),
            'customer_order_count_max' => $this->getSubmittedString('snod_rule_order_count_max'),
            'status_ids' => is_array($statuses) ? $statuses : [],
        ];

        foreach (self::EDITABLE_MODE_CONDITIONS as $type) {
            $ids = Tools::getValue('snod_rule_' . $type . '_ids', []);
            $input[$type . '_mode'] = $this->getSubmittedString('snod_rule_' . $type . '_mode');
            $input[$type . '_ids'] = is_array($ids) ? $ids : [];
        }

        return $input;
    }

    /**
     * Maps handler error codes to translated messages.
     *
     * @param array $codes
     *
     * @return array
     */
    private function translateRuleErrors(array $codes)
    {
        $messages = $this->getRuleErrorMessages();
        $translated = [];
        foreach ($codes as $code) {
            $translated[] = isset($messages[$code]) ? $messages[$code] : $code;
        }

        return $translated;
    }

    /**
     * @return array error code => translated message
     */
    private function getRuleErrorMessages()
    {
        $domain = 'Modules.Setnextorderdiscount.Admin';

        return [
            SnodRuleFormHandler::ERR_NAME_REQUIRED => $this->trans('Rule name is required.', [], $domain),
            SnodRuleFormHandler::ERR_DISCOUNT_VALUE => $this->trans('Discount value must be a number greater than zero.', [], $domain),
            SnodRuleFormHandler::ERR_DISCOUNT_PERCENT_MAX => $this->trans('A percentage discount cannot exceed 100.', [], $domain),
            SnodRuleFormHandler::ERR_VALIDITY => $this->trans('Validity period must be a whole number of days (at least 1).', [], $domain),
            SnodRuleFormHandler::ERR_SOURCE_RANGE => $this->trans('Order total minimum cannot be greater than the maximum.', [], $domain),
            SnodRuleFormHandler::ERR_ORDER_COUNT_RANGE => $this->trans('Order count minimum cannot be greater than the maximum.', [], $domain),
            SnodRuleFormHandler::ERR_DATE_RANGE => $this->trans('The start date cannot be after the end date.', [], $domain),
            SnodRuleFormHandler::ERR_SAVE_FAILED => $this->trans('Could not save the rule.', [], $domain),
            SnodRuleFormHandler::ERR_NOT_FOUND => $this->trans('Rule not found.', [], $domain),
        ];
    }

    /**
     * @return array translated labels consumed by the rule presenter
     */
    private function getRuleConditionLabels()
    {
        $domain = 'Modules.Setnextorderdiscount.Admin';

        return [
            'free_shipping' => $this->trans('Free shipping', [], $domain),
            'order_total' => $this->trans('Order total', [], $domain),
            'order_no' => $this->trans('Order no.', [], $domain),
            'date_window' => $this->trans('Date window', [], $domain),
            'conditions' => [
                SnodRuleConditionSchema::TYPE_GROUP => $this->trans('Groups', [], $domain),
                SnodRuleConditionSchema::TYPE_COUNTRY => $this->trans('Countries', [], $domain),
                SnodRuleConditionSchema::TYPE_CURRENCY => $this->trans('Currencies', [], $domain),
                SnodRuleConditionSchema::TYPE_CATEGORY => $this->trans('Categories', [], $domain),
                SnodRuleConditionSchema::TYPE_MANUFACTURER => $this->trans('Brands', [], $domain),
            ],
        ];
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
     * @return string current context currency sign
     */
    private function getCurrencySign()
    {
        $currency = $this->context->currency;

        return Validate::isLoadedObject($currency) ? (string) $currency->sign : '';
    }
}
