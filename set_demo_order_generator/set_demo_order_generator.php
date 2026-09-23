<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/classes/SetDemoOrderCreator.php';

class set_demo_order_generator extends Module
{
    const ADMIN_CONTROLLER = 'DemoOrderGenerator';
    const USAGE_TABLE = 'sdog_usage';
    const USAGE_HISTORY_LIMIT = 100;

    public function __construct()
    {
        $this->name = 'set_demo_order_generator';
        $this->tab = 'administration';
        $this->version = '1.1.0';
        $this->author = 'Smart Ecommerce Tech';
        $this->need_instance = 0;
        $this->bootstrap = true;
        $this->ps_versions_compliancy = [
            'min' => '8.1.0.0',
            'max' => _PS_VERSION_,
        ];

        parent::__construct();

        $this->displayName = $this->l('Demo Order Generator');
        $this->description = $this->l('Creates test orders with controlled customer, amount and rule-matching data.');
        $this->confirmUninstall = $this->l('Remove the Demo Order Generator? Existing test orders will not be deleted.');
    }

    public function install()
    {
        return parent::install()
            && $this->installUsageTable()
            && $this->installAdminTab();
    }

    public function uninstall()
    {
        if (!$this->uninstallAdminTab() || !parent::uninstall()) {
            return false;
        }

        return $this->uninstallUsageTable();
    }

    public function getContent()
    {
        $this->context->smarty->assign([
            'sdog_usage_history' => $this->getUsageHistory((int) $this->context->shop->id),
        ]);

        return $this->display(__FILE__, 'views/templates/admin/history.tpl');
    }

    /**
     * @param DemoOrderGeneratorController $controller
     *
     * @return string
     */
    public function renderAdminPage($controller)
    {
        $idLang = (int) $this->context->language->id;
        $idShop = (int) $this->context->shop->id;
        $data = $this->getFormData($idLang, $idShop);

        $this->context->smarty->assign([
            'sdog_main_module_ready' => Module::isInstalled('set_next_order_discount')
                && Module::isEnabled('set_next_order_discount'),
            'sdog_created_result' => $controller->getCreatedResult(),
            'sdog_next_order_discount_url' => $this->context->link->getAdminLink('NextOrderDiscount'),
        ]);

        $output = $this->display(__FILE__, 'views/templates/admin/intro.tpl');
        $output .= $this->renderForm($data, $controller);

        return $output;
    }

    /**
     * @return array
     */
    public function readSubmittedOrderData()
    {
        return [
            'email' => trim((string) Tools::getValue('sdog_email')),
            'firstname' => trim((string) Tools::getValue('sdog_firstname')),
            'lastname' => trim((string) Tools::getValue('sdog_lastname')),
            'target_total' => str_replace(',', '.', trim((string) Tools::getValue('sdog_target_total'))),
            'id_product' => (int) Tools::getValue('sdog_id_product'),
            'id_currency' => (int) Tools::getValue('sdog_id_currency'),
            'id_country' => (int) Tools::getValue('sdog_id_country'),
            'id_group' => (int) Tools::getValue('sdog_id_group'),
            'id_lang' => (int) Tools::getValue('sdog_id_lang'),
            'id_order_state' => (int) Tools::getValue('sdog_id_order_state'),
        ];
    }

    /**
     * @return SetDemoOrderCreator
     */
    public function getOrderCreator()
    {
        return new SetDemoOrderCreator($this->context);
    }

    /**
     * Creates the private usage history table. Public so PrestaShop's module
     * upgrade script can call the same idempotent schema operation.
     *
     * @return bool
     */
    public function installUsageTable()
    {
        return (bool) Db::getInstance()->execute(
            'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . self::USAGE_TABLE . '` ('
            . ' `id_sdog_usage` INT UNSIGNED NOT NULL AUTO_INCREMENT,'
            . ' `id_shop` INT UNSIGNED NOT NULL,'
            . ' `id_employee` INT UNSIGNED NOT NULL DEFAULT 0,'
            . ' `id_customer` INT UNSIGNED NOT NULL,'
            . ' `id_order` INT UNSIGNED NOT NULL,'
            . ' `email` VARCHAR(255) NOT NULL,'
            . ' `firstname` VARCHAR(255) NOT NULL DEFAULT "",'
            . ' `lastname` VARCHAR(255) NOT NULL DEFAULT "",'
            . ' `total_paid` DECIMAL(20,6) NOT NULL DEFAULT 0,'
            . ' `currency_iso` CHAR(3) NOT NULL DEFAULT "",'
            . ' `coupon_code` VARCHAR(64) NULL DEFAULT NULL,'
            . ' `coupon_status` VARCHAR(32) NULL DEFAULT NULL,'
            . ' `date_add` DATETIME NOT NULL,'
            . ' PRIMARY KEY (`id_sdog_usage`),'
            . ' UNIQUE KEY `uniq_sdog_usage_order` (`id_order`),'
            . ' KEY `idx_sdog_usage_shop_date` (`id_shop`, `date_add`),'
            . ' KEY `idx_sdog_usage_email` (`email`)'
            . ') ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8mb4'
        );
    }

    /**
     * @return array
     */
    public function getUsageHistory($idShop, $limit = self::USAGE_HISTORY_LIMIT)
    {
        $limit = max(1, min(500, (int) $limit));
        try {
            $rows = Db::getInstance()->executeS(
                'SELECT `id_sdog_usage`, `id_employee`, `id_customer`, `id_order`,'
                . ' `email`, `firstname`, `lastname`, `total_paid`, `currency_iso`,'
                . ' `coupon_code`, `coupon_status`, `date_add`'
                . ' FROM `' . _DB_PREFIX_ . self::USAGE_TABLE . '`'
                . ' WHERE `id_shop` = ' . (int) $idShop
                . ' ORDER BY `id_sdog_usage` DESC LIMIT ' . $limit
            );
        } catch (Throwable $e) {
            return [];
        }

        foreach ((array) $rows as &$row) {
            $row['order_url'] = $this->context->link->getAdminLink(
                'AdminOrders',
                true,
                [],
                ['id_order' => (int) $row['id_order'], 'vieworder' => 1]
            );
            $row['customer_url'] = $this->context->link->getAdminLink(
                'AdminCustomers',
                true,
                [],
                ['id_customer' => (int) $row['id_customer'], 'viewcustomer' => 1]
            );
        }
        unset($row);

        return is_array($rows) ? $rows : [];
    }

    private function renderForm(array $data, $controller)
    {
        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->name;
        $helper->module = $this;
        $helper->default_form_language = (int) Configuration::get('PS_LANG_DEFAULT');
        $helper->allow_employee_form_lang = (int) Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG');
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitCreateDemoOrder';
        $helper->currentIndex = $this->context->link->getAdminLink(self::ADMIN_CONTROLLER, false);
        $helper->token = Tools::getAdminTokenLite(self::ADMIN_CONTROLLER);
        $helper->tpl_vars = [
            'fields_value' => $this->getFieldValues($data, $controller),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => (int) $this->context->language->id,
        ];

        return $helper->generateForm([
            [
                'form' => [
                    'legend' => [
                        'title' => $this->l('Create a test order'),
                        'icon' => 'icon-shopping-cart',
                    ],
                    'description' => $this->l('The order is created through the normal PrestaShop checkout API. Reusing the same email increases that customer\'s order count and lets you test returning-customer rules.'),
                    'input' => [
                        [
                            'type' => 'text',
                            'label' => $this->l('Customer email'),
                            'name' => 'sdog_email',
                            'required' => true,
                            'col' => 4,
                            'hint' => $this->l('Use an inbox you own. The order confirmation and discount email may both be sent.'),
                        ],
                        [
                            'type' => 'text',
                            'label' => $this->l('First name'),
                            'name' => 'sdog_firstname',
                            'required' => true,
                            'col' => 4,
                        ],
                        [
                            'type' => 'text',
                            'label' => $this->l('Last name'),
                            'name' => 'sdog_lastname',
                            'required' => true,
                            'col' => 4,
                        ],
                        [
                            'type' => 'text',
                            'label' => $this->l('Exact order total'),
                            'name' => 'sdog_target_total',
                            'required' => true,
                            'col' => 2,
                            'suffix' => $data['default_currency_sign'],
                            'desc' => $this->l('Final total including tax and delivery. A temporary cart discount makes the total match this value.'),
                        ],
                        $this->selectInput('Product', 'sdog_id_product', $data['products'], 'id', 'name', $this->l('Choose a product whose category and brand should be used by rule matching.')),
                        $this->selectInput('Currency', 'sdog_id_currency', $data['currencies'], 'id', 'name'),
                        $this->selectInput('Delivery country', 'sdog_id_country', $data['countries'], 'id', 'name'),
                        $this->selectInput('Customer group', 'sdog_id_group', $data['groups'], 'id', 'name'),
                        $this->selectInput('Email language', 'sdog_id_lang', $data['languages'], 'id', 'name'),
                        $this->selectInput('Order status', 'sdog_id_order_state', $data['order_states'], 'id', 'name', $this->l('Select a status listed as a trigger in the Next Order Discount rule.')),
                    ],
                    'submit' => [
                        'title' => $this->l('Create test order'),
                        'id' => 'set_demo_order_generator_form_submit_btn',
                        'class' => 'btn btn-primary pull-right',
                        'icon' => 'process-icon-save',
                    ],
                ],
            ],
        ]);
    }

    private function selectInput($label, $name, array $options, $idKey, $nameKey, $description = '')
    {
        $input = [
            'type' => 'select',
            'label' => $this->l($label),
            'name' => $name,
            'required' => true,
            'options' => [
                'query' => $options,
                'id' => $idKey,
                'name' => $nameKey,
            ],
        ];
        if ($description !== '') {
            $input['desc'] = $description;
        }

        return $input;
    }

    private function getFieldValues(array $data, $controller)
    {
        $employee = $this->context->employee;
        $defaults = [
            'sdog_email' => Validate::isLoadedObject($employee) && Validate::isEmail($employee->email)
                ? (string) $employee->email
                : 'demo.customer@example.com',
            'sdog_firstname' => Validate::isLoadedObject($employee) && $employee->firstname
                ? (string) $employee->firstname
                : 'Demo',
            'sdog_lastname' => Validate::isLoadedObject($employee) && $employee->lastname
                ? (string) $employee->lastname
                : 'Customer',
            'sdog_target_total' => '150.00',
            'sdog_id_product' => $data['default_product_id'],
            'sdog_id_currency' => (int) Configuration::get('PS_CURRENCY_DEFAULT'),
            'sdog_id_country' => (int) Configuration::get('PS_COUNTRY_DEFAULT'),
            'sdog_id_group' => (int) Configuration::get('PS_CUSTOMER_GROUP'),
            'sdog_id_lang' => (int) Configuration::get('PS_LANG_DEFAULT'),
            'sdog_id_order_state' => (int) Configuration::get('PS_OS_PAYMENT'),
        ];

        if (!$controller->hasSubmission()) {
            return $defaults;
        }

        foreach ($defaults as $key => $default) {
            $defaults[$key] = Tools::getValue($key, $default);
        }

        return $defaults;
    }

    private function getFormData($idLang, $idShop)
    {
        $currencies = [];
        foreach ((array) Currency::getCurrencies(false, true) as $currency) {
            $id = (int) $currency['id_currency'];
            $currencies[] = [
                'id' => $id,
                'name' => (string) $currency['name'] . ' (' . (string) $currency['iso_code'] . ')',
            ];
        }

        $countries = [];
        foreach ((array) Country::getCountries($idLang, true) as $country) {
            $countries[] = ['id' => (int) $country['id_country'], 'name' => (string) $country['name']];
        }

        $groups = [];
        foreach ((array) Group::getGroups($idLang, true) as $group) {
            $groups[] = ['id' => (int) $group['id_group'], 'name' => (string) $group['name']];
        }

        $languages = [];
        foreach ((array) Language::getLanguages(true, $idShop) as $language) {
            $languages[] = ['id' => (int) $language['id_lang'], 'name' => (string) $language['name']];
        }

        $states = [];
        foreach ((array) OrderState::getOrderStates($idLang) as $state) {
            $states[] = ['id' => (int) $state['id_order_state'], 'name' => (string) $state['name']];
        }

        $products = $this->getProductOptions($idLang, $idShop);
        $currency = new Currency((int) Configuration::get('PS_CURRENCY_DEFAULT'));

        return [
            'currencies' => $currencies,
            'countries' => $countries,
            'groups' => $groups,
            'languages' => $languages,
            'order_states' => $states,
            'products' => $products,
            'default_product_id' => isset($products[0]['id']) ? (int) $products[0]['id'] : 0,
            'default_currency_sign' => Validate::isLoadedObject($currency) ? (string) $currency->sign : '',
        ];
    }

    private function getProductOptions($idLang, $idShop)
    {
        $rows = Db::getInstance()->executeS(
            'SELECT p.`id_product`, pl.`name`, m.`name` AS manufacturer_name'
            . ' FROM `' . _DB_PREFIX_ . 'product` p'
            . ' INNER JOIN `' . _DB_PREFIX_ . 'product_shop` ps ON ps.`id_product` = p.`id_product`'
            . ' AND ps.`id_shop` = ' . (int) $idShop
            . ' INNER JOIN `' . _DB_PREFIX_ . 'product_lang` pl ON pl.`id_product` = p.`id_product`'
            . ' AND pl.`id_shop` = ' . (int) $idShop . ' AND pl.`id_lang` = ' . (int) $idLang
            . ' LEFT JOIN `' . _DB_PREFIX_ . 'manufacturer` m ON m.`id_manufacturer` = p.`id_manufacturer`'
            . ' WHERE ps.`active` = 1 AND ps.`available_for_order` = 1 AND p.`is_virtual` = 0'
            . ' ORDER BY ps.`price` ASC, pl.`name` ASC'
        );

        $products = [];
        foreach ((array) $rows as $row) {
            $label = '#' . (int) $row['id_product'] . ' — ' . (string) $row['name'];
            if (!empty($row['manufacturer_name'])) {
                $label .= ' — ' . (string) $row['manufacturer_name'];
            }
            $products[] = ['id' => (int) $row['id_product'], 'name' => $label];
        }

        return $products;
    }

    private function installAdminTab()
    {
        $existingId = (int) Tab::getIdFromClassName(self::ADMIN_CONTROLLER);
        $tab = new Tab($existingId ?: null);
        $tab->active = 1;
        $tab->class_name = self::ADMIN_CONTROLLER;
        $tab->module = $this->name;
        $tab->id_parent = (int) Tab::getIdFromClassName('AdminCatalog');
        $tab->name = [];
        foreach (Language::getLanguages(false) as $language) {
            $tab->name[(int) $language['id_lang']] = $this->l('Demo Order Generator');
        }

        return (bool) $tab->save();
    }

    private function uninstallAdminTab()
    {
        $idTab = (int) Tab::getIdFromClassName(self::ADMIN_CONTROLLER);
        if ($idTab <= 0) {
            return true;
        }

        return (bool) (new Tab($idTab))->delete();
    }

    private function uninstallUsageTable()
    {
        return (bool) Db::getInstance()->execute(
            'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . self::USAGE_TABLE . '`'
        );
    }
}
