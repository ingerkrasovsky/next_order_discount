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

use Setecom\NextOrderDiscount\Cron\CronRouter;
use Setecom\NextOrderDiscount\Mail\DefaultEmailProvider;
use Setecom\NextOrderDiscount\Repository\CouponLinkRepository;
use Setecom\NextOrderDiscount\Repository\CronLockRepository;
use Setecom\NextOrderDiscount\Repository\LogRepository;
use Setecom\NextOrderDiscount\Repository\RuleEmailRepository;
use Setecom\NextOrderDiscount\Repository\RuleRepository;
use Setecom\NextOrderDiscount\Rule\RuleConditionSchema;
use Setecom\NextOrderDiscount\Rule\RuleFormHandler;

/**
 * @property set_next_order_discount $module
 */
class NextOrderDiscountController extends ModuleAdminController
{
    private const COUPON_STATUSES = ['created', 'emailed', 'reminded', 'used', 'expired', 'canceled'];

    /**
     * Log levels selectable in the Logs tab filter.
     */
    private const LOG_LEVELS = ['debug', 'info', 'warning', 'error'];

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
        if (Tools::getValue('ajax')) {
            $action = Tools::getValue('action');
            if ($action === 'runCronTask') {
                $this->respondJson($this->runCronTaskAjax());
            }
            if ($action === 'previewRuleEmail') {
                $this->ajaxProcessPreviewRuleEmail();
            }
            if ($action === 'sendTestRuleEmail') {
                $this->ajaxProcessSendTestRuleEmail();
            }
        }

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
     * @param RuleRepository $repository
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
     * persistence to RuleFormHandler.
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
            'snod_languages' => $this->getFormLanguages(),
            'snod_email_types' => $this->getEmailTypeBlocks(),
            'snod_email_content' => $this->buildRuleEmailContent($idRule),
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
            'code_prefix' => $this->getSubmittedString('snod_rule_code_prefix'),
            'code_length' => $this->getSubmittedString('snod_rule_code_length'),
            'code_mask' => $this->getSubmittedString('snod_rule_code_mask'),
            'email' => $this->readEmailInput(),
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
            RuleFormHandler::ERR_NAME_REQUIRED => $this->trans('Rule name is required.', [], $domain),
            RuleFormHandler::ERR_DISCOUNT_VALUE => $this->trans('Discount value must be a number greater than zero.', [], $domain),
            RuleFormHandler::ERR_DISCOUNT_PERCENT_MAX => $this->trans('A percentage discount cannot exceed 100.', [], $domain),
            RuleFormHandler::ERR_VALIDITY => $this->trans('Validity period must be a whole number of days (at least 1).', [], $domain),
            RuleFormHandler::ERR_SOURCE_RANGE => $this->trans('Order total minimum cannot be greater than the maximum.', [], $domain),
            RuleFormHandler::ERR_ORDER_COUNT_RANGE => $this->trans('Order count minimum cannot be greater than the maximum.', [], $domain),
            RuleFormHandler::ERR_DATE_RANGE => $this->trans('The start date cannot be after the end date.', [], $domain),
            RuleFormHandler::ERR_SAVE_FAILED => $this->trans('Could not save the rule.', [], $domain),
            RuleFormHandler::ERR_NOT_FOUND => $this->trans('Rule not found.', [], $domain),
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
                RuleConditionSchema::TYPE_GROUP => $this->trans('Groups', [], $domain),
                RuleConditionSchema::TYPE_COUNTRY => $this->trans('Countries', [], $domain),
                RuleConditionSchema::TYPE_CURRENCY => $this->trans('Currencies', [], $domain),
                RuleConditionSchema::TYPE_CATEGORY => $this->trans('Categories', [], $domain),
                RuleConditionSchema::TYPE_MANUFACTURER => $this->trans('Brands', [], $domain),
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
     * @return array shop languages: id_lang, name, iso_code
     */
    private function getFormLanguages()
    {
        $languages = [];
        foreach (Language::getLanguages(false) as $lang) {
            $languages[] = [
                'id_lang' => (int) $lang['id_lang'],
                'name' => (string) $lang['name'],
                'iso_code' => (string) $lang['iso_code'],
            ];
        }

        return $languages;
    }

    /**
     * @return array the per-rule email types with translated labels
     */
    private function getEmailTypeBlocks()
    {
        $domain = 'Modules.Setnextorderdiscount.Admin';

        return [
            ['type' => RuleEmailRepository::TYPE_COUPON, 'label' => $this->trans('Coupon email', [], $domain)],
            ['type' => RuleEmailRepository::TYPE_REMINDER_1, 'label' => $this->trans('First reminder email', [], $domain)],
            ['type' => RuleEmailRepository::TYPE_REMINDER_2, 'label' => $this->trans('Second reminder email', [], $domain)],
        ];
    }

    /**
     * Builds the email editor content for the form: the rule's stored subject/HTML
     * per type and language, falling back to the shipped default template for
     * languages the rule has not customized (and for a brand-new rule).
     *
     * @param int $idRule
     *
     * @return array content[type][id_lang] => ['subject' => string, 'html' => string]
     */
    private function buildRuleEmailContent($idRule)
    {
        $idRule = (int) $idRule;
        $emailRepository = new RuleEmailRepository();
        $provider = new DefaultEmailProvider();
        $stored = $idRule > 0 ? $emailRepository->findAllForRule($idRule) : [];
        $languages = $this->getFormLanguages();

        $content = [];
        foreach (RuleEmailRepository::types() as $type) {
            foreach ($languages as $lang) {
                $idLang = (int) $lang['id_lang'];
                if (isset($stored[$type][$idLang]) && trim((string) $stored[$type][$idLang]['html']) !== '') {
                    $content[$type][$idLang] = $stored[$type][$idLang];
                } else {
                    $content[$type][$idLang] = $provider->getDefault($type, $idLang);
                }
            }
        }

        return $content;
    }

    /**
     * @return array the raw submitted per-rule email content (nested array)
     */
    private function readEmailInput()
    {
        $email = Tools::getValue('snod_email', []);

        return is_array($email) ? $email : [];
    }

    /**
     * @return string current context currency sign
     */
    private function getCurrencySign()
    {
        $currency = $this->context->currency;

        return Validate::isLoadedObject($currency) ? (string) $currency->sign : '';
    }

    /**
     * Builds the Cron/Tools tab: the cron endpoint URL (token-bearing), a manual
     * trigger for each background task, current lock state and a queue snapshot.
     *
     * @return void
     */
    public function crontoolsTab()
    {
        $domain = 'Modules.Setnextorderdiscount.Admin';
        $cronToken = (string) Configuration::get('SNOD_CRON_TOKEN');
        $cronBaseUrl = $this->context->link->getModuleLink(
            $this->module->name,
            'cron',
            ['token' => $cronToken],
            true
        );

        $labels = [
            CronRouter::TASK_PROCESS_QUEUE => $this->trans('Process the dispatch queue', [], $domain),
            CronRouter::TASK_PLAN_REMINDERS => $this->trans('Plan coupon reminders', [], $domain),
            CronRouter::TASK_EXPIRE_COUPONS => $this->trans('Expire lapsed coupons', [], $domain),
        ];

        $lockRepository = new CronLockRepository();
        $tasks = [];
        foreach (CronRouter::availableTasks() as $task) {
            $tasks[] = [
                'task' => $task,
                'label' => isset($labels[$task]) ? $labels[$task] : $task,
                'url' => $cronBaseUrl . '&task=' . urlencode($task),
                'locked' => $lockRepository->isLocked(CronRouter::lockNameFor($task)),
            ];
        }

        $this->context->smarty->assign([
            'snod_cron_base_url' => $cronBaseUrl,
            'snod_cron_tasks' => $tasks,
            'snod_queue_counts' => $this->getQueueCounts(),
            'snod_admin_link' => $this->adminLink,
            'snod_admin_token' => (string) $this->token,
        ]);
    }

    /**
     * Canonical PrestaShop AJAX entry point (ajax=1&action=runCronTask). The
     * framework validates the admin token and dispatches here before initContent.
     *
     * @return void
     */
    public function ajaxProcessRunCronTask()
    {
        $this->respondJson($this->runCronTaskAjax());
    }

    /**
     * Handles the manual "run task" AJAX action for the Cron/Tools tab. The admin
     * controller token is validated by the framework before this runs; the task
     * itself executes under the same lock as the public cron endpoint.
     *
     * @return array the cron router result
     */
    private function runCronTaskAjax()
    {
        $task = (string) Tools::getValue('task');

        return $this->module->getCronRouter()->run($task, (int) $this->context->shop->id);
    }

    /**
     * @return array dispatch-queue counts keyed by status for the current shop
     *               context (all shops when in the "all shops" context)
     */
    private function getQueueCounts()
    {
        $counts = ['pending' => 0, 'processing' => 0, 'done' => 0, 'failed' => 0];

        $where = '1';
        $isAllShops = Shop::isFeatureActive() && Shop::getContext() == Shop::CONTEXT_ALL;
        if (!$isAllShops) {
            $where = '`id_shop` = ' . (int) $this->context->shop->id;
        }

        $rows = Db::getInstance()->executeS(
            'SELECT `status`, COUNT(*) AS total FROM `' . _DB_PREFIX_ . 'snod_dispatch_queue`'
            . ' WHERE ' . $where
            . ' GROUP BY `status`'
        );
        foreach ((array) $rows as $row) {
            $status = isset($row['status']) ? (string) $row['status'] : '';
            if (array_key_exists($status, $counts)) {
                $counts[$status] = (int) $row['total'];
            }
        }

        return $counts;
    }

    /**
     * Emits a JSON response and terminates the request.
     *
     * @param array $data
     *
     * @return void
     */
    private function respondJson(array $data)
    {
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    /**
     * AJAX: renders a rule email body with sample placeholder values so the
     * merchant can preview it. HTML/subject arrive base64-encoded to survive any
     * request-level filtering.
     *
     * @return void
     */
    public function ajaxProcessPreviewRuleEmail()
    {
        $idLang = (int) Tools::getValue('id_lang', (int) $this->context->language->id);
        $html = base64_decode((string) Tools::getValue('html_b64', ''), true);
        $subject = base64_decode((string) Tools::getValue('subject_b64', ''), true);
        $logo = (string) Configuration::get('PS_LOGO');
        $logoUrl = $logo !== '' ? ($this->context->link->getBaseLink() . 'img/' . $logo) : '';
        $vars = array_merge($this->sampleEmailVars($idLang), ['{shop_logo}' => $logoUrl]);

        $this->respondJson([
            'success' => true,
            'subject' => strtr((string) $subject, $vars),
            'html' => strtr((string) $html, $vars),
        ]);
    }

    /**
     * AJAX: sends a test copy of a rule email (rendered with sample values) to an
     * address, through the generic pass-through mail template.
     *
     * @return void
     */
    public function ajaxProcessSendTestRuleEmail()
    {
        $email = (string) Tools::getValue('email', '');
        if (!Validate::isEmail($email)) {
            $this->respondJson(['success' => false, 'error' => 'invalid_email']);
        }

        $idLang = (int) Tools::getValue('id_lang', (int) $this->context->language->id);
        $idShop = (int) $this->context->shop->id;
        $html = base64_decode((string) Tools::getValue('html_b64', ''), true);
        $subject = base64_decode((string) Tools::getValue('subject_b64', ''), true);
        $vars = array_merge($this->sampleEmailVars($idLang), ['{shop_logo}' => 'cid:shop_logo']);

        $finalHtml = strtr((string) $html, $vars);
        $finalSubject = strtr((string) $subject, $vars);
        if ($finalSubject === '') {
            $finalSubject = $this->trans('Test email', [], 'Modules.Setnextorderdiscount.Admin');
        }

        $sent = false;
        try {
            $sent = Mail::Send(
                $idLang,
                'custom',
                $finalSubject,
                [
                    '{snod_body_html}' => $finalHtml,
                    '{snod_body_txt}' => trim(strip_tags(str_replace(['<br>', '<br/>', '<br />', '</p>', '</div>'], "\n", $finalHtml))),
                ],
                $email,
                null,
                null,
                null,
                null,
                null,
                rtrim(_PS_MODULE_DIR_, '/') . '/' . $this->module->name . '/mails/',
                false,
                $idShop
            );
        } catch (\Throwable $e) {
            $sent = false;
        }

        $this->respondJson(['success' => (bool) $sent]);
    }

    /**
     * @param int $idLang
     *
     * @return array sample placeholder values for previews and test emails
     */
    private function sampleEmailVars($idLang)
    {
        $format = 'Y-m-d';
        $language = new Language((int) $idLang);
        if (Validate::isLoadedObject($language) && !empty($language->date_format_lite)) {
            $format = (string) $language->date_format_lite;
        }

        return [
            '{coupon_code}' => 'NOD-SAMPLE12',
            '{coupon_value}' => '10%',
            '{valid_to}' => date($format, strtotime('+30 days')),
            '{shop_name}' => (string) Configuration::get('PS_SHOP_NAME', null, null, (int) $this->context->shop->id),
            '{customer_firstname}' => 'Alex',
            '{minimum_amount}' => '—',
        ];
    }

    /**
     * Builds the Dashboard tab: the coupon funnel aggregates for the current
     * shop context and a queue snapshot.
     *
     * @return void
     */
    public function dashboardTab()
    {
        $domain = 'Modules.Setnextorderdiscount.Admin';
        $couponLinkRepository = new CouponLinkRepository();
        $funnel = $couponLinkRepository->funnelCounts($this->getShopScopeId());

        $generated = (int) $funnel['generated'];
        $used = (int) $funnel['used'];
        $conversion = $generated > 0 ? round(($used / $generated) * 100, 1) : 0.0;

        $steps = [
            ['key' => 'generated', 'label' => $this->trans('Generated', [], $domain)],
            ['key' => 'emailed', 'label' => $this->trans('Emailed', [], $domain)],
            ['key' => 'reminded', 'label' => $this->trans('Reminded', [], $domain)],
            ['key' => 'used', 'label' => $this->trans('Used', [], $domain)],
            ['key' => 'expired', 'label' => $this->trans('Expired', [], $domain)],
            ['key' => 'canceled', 'label' => $this->trans('Canceled', [], $domain)],
        ];

        $funnelView = [];
        foreach ($steps as $step) {
            $value = (int) $funnel[$step['key']];
            $funnelView[] = [
                'key' => $step['key'],
                'label' => $step['label'],
                'value' => $value,
                'percent' => $generated > 0 ? round(($value / $generated) * 100, 1) : 0.0,
            ];
        }

        $this->context->smarty->assign([
            'snod_funnel' => $funnelView,
            'snod_funnel_generated' => $generated,
            'snod_conversion_rate' => $conversion,
            'snod_queue_counts' => $this->getQueueCounts(),
        ]);
    }

    /**
     * Builds the Logs tab: a paginated, filterable view of the module log,
     * scoped to the current shop context.
     *
     * @return void
     */
    public function logsTab()
    {
        $perPage = 30;

        $levelFilter = $this->getSubmittedString('snod_log_level');
        if (!in_array($levelFilter, self::LOG_LEVELS, true)) {
            $levelFilter = '';
        }

        $channelFilter = preg_replace('/[^a-z0-9_\-]/i', '', $this->getSubmittedString('snod_log_channel'));
        $channelFilter = is_string($channelFilter) ? $channelFilter : '';

        $page = (int) Tools::getValue('snod_log_page', 1);
        if ($page < 1) {
            $page = 1;
        }

        $filters = [
            'id_shop' => $this->getShopScopeId(),
            'level' => $levelFilter,
            'channel' => $channelFilter,
        ];

        $logRepository = new LogRepository();
        $total = $logRepository->countRecent($filters);
        $totalPages = $total > 0 ? (int) ceil($total / $perPage) : 1;
        if ($page > $totalPages) {
            $page = $totalPages;
        }
        $offset = ($page - 1) * $perPage;

        $baseUrl = $this->adminLink . '&tab=logs';
        if ($levelFilter !== '') {
            $baseUrl .= '&snod_log_level=' . urlencode($levelFilter);
        }
        if ($channelFilter !== '') {
            $baseUrl .= '&snod_log_channel=' . urlencode($channelFilter);
        }

        $this->context->smarty->assign([
            'snod_logs' => $logRepository->findRecent($filters, $perPage, $offset),
            'snod_log_levels' => self::LOG_LEVELS,
            'snod_log_filter_level' => $levelFilter,
            'snod_log_filter_channel' => $channelFilter,
            'snod_log_page' => $page,
            'snod_log_prev_page' => $page - 1,
            'snod_log_next_page' => $page + 1,
            'snod_log_total_pages' => $totalPages,
            'snod_log_total' => $total,
            'snod_logs_base_url' => $baseUrl,
        ]);
    }

    /**
     * @return int the shop id to scope aggregates to, or 0 in the "all shops"
     *             multishop context
     */
    private function getShopScopeId()
    {
        $isAllShops = Shop::isFeatureActive() && Shop::getContext() == Shop::CONTEXT_ALL;

        return $isAllShops ? 0 : (int) $this->context->shop->id;
    }
}
