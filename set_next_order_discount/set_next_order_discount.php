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

class set_next_order_discount extends Module
{
    private const MODULE_HOOKS = [
        'actionValidateOrder',
        'actionOrderStatusPostUpdate',
    ];

    private const COUPON_CLASS_FILES = [
        '/classes/Repository/CouponLinkRepository.php',
        '/classes/Coupon/CouponCodeGenerator.php',
        '/classes/Coupon/CartRuleAdapter.php',
        '/classes/Coupon/CouponGenerationService.php',
    ];

    private $couponClassesLoaded = false;

    private const RULE_CLASS_FILES = [
        '/classes/Rule/RuleConditionSchema.php',
        '/classes/Rule/RuleValueFormatter.php',
        '/classes/Repository/RuleRepository.php',
        '/classes/Rule/RuleFormHandler.php',
        '/classes/Rule/RulePresenter.php',
        '/classes/Rule/RuleMatcher.php',
    ];

    private $ruleClassesLoaded = false;

    public function __construct()
    {
        $this->name = 'set_next_order_discount';
        $this->tab = 'advertising_marketing';
        $this->version = '1.0.0';
        $this->author = 'Smart Ecommerce Tech';
        $this->need_instance = 0;
        $this->module_key = '';
        $this->ps_versions_compliancy = [
            'min' => '8.1.0.0',
            'max' => _PS_VERSION_,
        ];
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->trans(
            'Next Order Discount',
            [],
            'Modules.Setnextorderdiscount.Admin'
        );
        $this->description = $this->trans(
            'Automatically creates a personal discount coupon after a successful order and manages its full lifecycle.',
            [],
            'Modules.Setnextorderdiscount.Admin'
        );

        $this->confirmUninstall = $this->trans(
            'Are you sure you want to uninstall Next Order Discount?',
            [],
            'Modules.Setnextorderdiscount.Admin'
        );
    }

    public function install()
    {
        $installSqlResult = include dirname(__FILE__) . '/sql/install.php';
        if (!$installSqlResult) {
            return false;
        }

        if (!parent::install()) {
            return false;
        }

        if (!$this->updateConfigurationValues($this->getDefaultConfigurationValues())) {
            return false;
        }

        if (!$this->registerModuleHooks()) {
            return false;
        }

        return $this->installTab();
    }

    public function uninstall()
    {
        $uninstallSqlResult = include dirname(__FILE__) . '/sql/uninstall.php';
        if (!$uninstallSqlResult) {
            return false;
        }

        if (!parent::uninstall()) {
            return false;
        }

        if (!$this->unregisterModuleHooks()) {
            return false;
        }

        if (!$this->deleteConfigurationValues()) {
            return false;
        }

        return $this->uninstallTab();
    }

    public function getContent()
    {
        Tools::redirectAdmin($this->context->link->getAdminLink('NextOrderDiscount'));

        return '';
    }

    /**
     * Fired when an order is first validated. Uses the initial order status to
     * decide (via eligibility) whether a coupon should be issued right away
     * (e.g. payment methods that create an already-paid order).
     *
     * @param array $params
     *
     * @return void
     */
    public function hookActionValidateOrder(array $params)
    {
        if (!isset($params['order']) || !($params['order'] instanceof Order)) {
            return;
        }

        $orderState = (isset($params['orderStatus']) && $params['orderStatus'] instanceof OrderState)
            ? $params['orderStatus']
            : null;

        $this->processOrderForCoupon($params['order'], $orderState);
    }

    /**
     * Main generation trigger: fired on every order status change. The new
     * status is passed to the eligibility policy, which only greenlights target
     * (or paid) statuses. Idempotency guarantees a single coupon per order.
     *
     * @param array $params
     *
     * @return void
     */
    public function hookActionOrderStatusPostUpdate(array $params)
    {
        $idOrder = isset($params['id_order']) ? (int) $params['id_order'] : 0;
        if ($idOrder <= 0) {
            return;
        }

        $order = new Order($idOrder);
        if (!Validate::isLoadedObject($order)) {
            return;
        }

        $orderState = (isset($params['newOrderStatus']) && $params['newOrderStatus'] instanceof OrderState)
            ? $params['newOrderStatus']
            : null;

        $this->processOrderForCoupon($order, $orderState);
    }

    /**
     * Shared entry point for both order hooks. Cheap early-out when the module
     * is disabled, then delegates to the generation service. Wrapped so a coupon
     * error can never disrupt order processing.
     *
     * @param Order            $order
     * @param OrderState|null  $orderState
     *
     * @return void
     */
    private function processOrderForCoupon(Order $order, $orderState)
    {
        try {
            if (!Validate::isLoadedObject($order)) {
                return;
            }

            if (!$this->isModuleEnabledForShop((int) $order->id_shop)) {
                return;
            }

            $this->getCouponGenerationService()->generateForOrderContext(
                $this->buildOrderContext($order, $orderState)
            );
        } catch (Exception $e) {
            // A coupon must never break checkout or order status updates.
        }
    }

    /**
     * Builds the order context array consumed by the generation service.
     *
     * @param Order           $order
     * @param OrderState|null $orderState
     *
     * @return array
     */
    private function buildOrderContext(Order $order, $orderState)
    {
        if ($orderState instanceof OrderState && Validate::isLoadedObject($orderState)) {
            $idOrderState = (int) $orderState->id;
            $orderIsPaid = (bool) $orderState->paid;
        } else {
            $idOrderState = (int) $order->current_state;
            $currentState = new OrderState($idOrderState);
            $orderIsPaid = Validate::isLoadedObject($currentState) ? (bool) $currentState->paid : false;
        }

        $idShop = (int) $order->id_shop;
        $idCustomer = (int) $order->id_customer;
        $idLang = (int) $order->id_lang;

        // The product-derived context (order products + their categories and
        // manufacturers) is the most expensive part, so only gather it when an
        // active rule actually filters on categories or brands.
        $productCategoryIds = [];
        $productManufacturerIds = [];
        if ($this->getRuleRepository()->hasActiveProductConditions($idShop)) {
            $productIds = $this->getOrderProductIds($order);
            $productCategoryIds = $this->getProductCategoryIds($productIds);
            $productManufacturerIds = $this->getProductManufacturerIds($productIds);
        }

        return [
            'id_shop' => $idShop,
            'id_shop_group' => (int) $order->id_shop_group,
            'id_customer' => $idCustomer,
            'id_order_source' => (int) $order->id,
            'id_order_state' => $idOrderState,
            'order_is_paid' => $orderIsPaid,
            'order_total_paid' => (float) $order->total_paid,
            'id_currency' => (int) $order->id_currency,
            'id_lang' => $idLang,
            'id_country' => $this->getOrderCountry($order),
            'customer_group_ids' => Customer::getGroupsStatic($idCustomer),
            'customer_valid_order_count' => (int) Order::getCustomerNbOrders($idCustomer),
            'product_category_ids' => $productCategoryIds,
            'product_manufacturer_ids' => $productManufacturerIds,
            'voucher_name' => $this->getVoucherName($idLang),
        ];
    }

    /**
     * Localized voucher name, resolved in the order's language (the coupon is
     * shown to the customer, so it must not follow the back-office language).
     *
     * @param int $idLang
     *
     * @return string
     */
    private function getVoucherName($idLang)
    {
        $locale = null;
        $language = new Language((int) $idLang);
        if (Validate::isLoadedObject($language) && !empty($language->locale)) {
            $locale = $language->locale;
        }

        return $this->trans('Next order discount', [], 'Modules.Setnextorderdiscount.Shop', $locale);
    }

    /**
     * @param Order $order
     *
     * @return array unique product ids in the order
     */
    private function getOrderProductIds(Order $order)
    {
        $productIds = [];
        foreach ((array) $order->getProducts() as $product) {
            $id = (int) (isset($product['product_id']) ? $product['product_id'] : (isset($product['id_product']) ? $product['id_product'] : 0));
            if ($id > 0) {
                $productIds[$id] = $id;
            }
        }

        return array_values($productIds);
    }

    /**
     * @param Order $order
     *
     * @return int delivery country id (0 when unavailable)
     */
    private function getOrderCountry(Order $order)
    {
        $idAddress = (int) $order->id_address_delivery;
        if ($idAddress <= 0) {
            return 0;
        }

        $info = Address::getCountryAndState($idAddress);

        return isset($info['id_country']) ? (int) $info['id_country'] : 0;
    }

    /**
     * @param array $productIds
     *
     * @return array distinct category ids across the order products
     */
    private function getProductCategoryIds(array $productIds)
    {
        if (empty($productIds)) {
            return [];
        }

        $rows = Db::getInstance()->executeS(
            'SELECT DISTINCT `id_category` FROM `' . _DB_PREFIX_ . 'category_product`'
            . ' WHERE `id_product` IN (' . implode(',', array_map('intval', $productIds)) . ')'
        );

        return $this->columnAsInts($rows, 'id_category');
    }

    /**
     * @param array $productIds
     *
     * @return array distinct manufacturer ids across the order products
     */
    private function getProductManufacturerIds(array $productIds)
    {
        if (empty($productIds)) {
            return [];
        }

        $rows = Db::getInstance()->executeS(
            'SELECT DISTINCT `id_manufacturer` FROM `' . _DB_PREFIX_ . 'product`'
            . ' WHERE `id_manufacturer` > 0'
            . ' AND `id_product` IN (' . implode(',', array_map('intval', $productIds)) . ')'
        );

        return $this->columnAsInts($rows, 'id_manufacturer');
    }

    /**
     * @param mixed  $rows
     * @param string $column
     *
     * @return array
     */
    private function columnAsInts($rows, $column)
    {
        $ids = [];
        foreach ((array) $rows as $row) {
            if (isset($row[$column])) {
                $ids[] = (int) $row[$column];
            }
        }

        return $ids;
    }

    /**
     * @param int $idShop
     *
     * @return bool
     */
    private function isModuleEnabledForShop($idShop)
    {
        $idShop = (int) $idShop;
        if ($idShop > 0) {
            return (int) Configuration::get('SNOD_ENABLED', null, null, $idShop) === 1;
        }

        return (int) Configuration::get('SNOD_ENABLED') === 1;
    }

    /**
     * Lazily require the coupon classes (no Composer autoloading, per the
     * base-module architecture). Idempotent within a request.
     *
     * @return void
     */
    private function ensureCouponClassesLoaded()
    {
        if ($this->couponClassesLoaded) {
            return;
        }

        foreach (self::COUPON_CLASS_FILES as $relativePath) {
            require_once __DIR__ . $relativePath;
        }

        $this->couponClassesLoaded = true;
    }

    /**
     * Factory for the coupon generation service with its collaborators wired.
     *
     * @return SnodCouponGenerationService
     */
    private function getCouponGenerationService()
    {
        $this->ensureCouponClassesLoaded();

        return new SnodCouponGenerationService(
            $this->getRuleMatcher(),
            new SnodCartRuleAdapter(),
            new SnodCouponLinkRepository()
        );
    }

    /**
     * Lazily require the rule classes (no Composer autoloading, per the
     * base-module architecture). Idempotent within a request.
     *
     * @return void
     */
    private function ensureRuleClassesLoaded()
    {
        if ($this->ruleClassesLoaded) {
            return;
        }

        foreach (self::RULE_CLASS_FILES as $relativePath) {
            require_once __DIR__ . $relativePath;
        }

        $this->ruleClassesLoaded = true;
    }

    /**
     * Factory for the rule repository. Public so the admin controller can list
     * and manage discount rules.
     *
     * @return SnodRuleRepository
     */
    public function getRuleRepository()
    {
        $this->ensureRuleClassesLoaded();

        return new SnodRuleRepository();
    }

    /**
     * Factory for the rule form handler (validation + persistence).
     *
     * @return SnodRuleFormHandler
     */
    public function getRuleFormHandler()
    {
        $this->ensureRuleClassesLoaded();

        return new SnodRuleFormHandler(new SnodRuleRepository());
    }

    /**
     * Factory for the rule list presenter.
     *
     * @return SnodRulePresenter
     */
    public function getRulePresenter()
    {
        $this->ensureRuleClassesLoaded();

        return new SnodRulePresenter();
    }

    /**
     * Factory for the rule matcher (order context -> matching rules).
     *
     * @return SnodRuleMatcher
     */
    public function getRuleMatcher()
    {
        $this->ensureRuleClassesLoaded();

        return new SnodRuleMatcher(new SnodRuleRepository());
    }

    /**
     * Global configuration. Discount, validity, minimum and target statuses now
     * live on individual rules, so only module-wide settings remain here.
     *
     * @return array
     */
    private function getDefaultConfigurationValues()
    {
        return [
            'SNOD_ENABLED' => 0,
            'SNOD_DEBUG_MODE' => 0,
            'SNOD_CODE_PREFIX' => 'NOD',
            'SNOD_CODE_LENGTH' => 12,
            'SNOD_CODE_MASK' => '',
        ];
    }

    /**
     * All configuration keys removed on uninstall, including legacy keys from
     * the pre rule-engine layout.
     *
     * @return array
     */
    private function getAllConfigurationKeys()
    {
        return array_merge(
            array_keys($this->getDefaultConfigurationValues()),
            ['SNOD_DISCOUNT_TYPE', 'SNOD_DISCOUNT_VALUE', 'SNOD_VALIDITY_DAYS', 'SNOD_MIN_ORDER_AMOUNT', 'SNOD_TARGET_STATUSES']
        );
    }

    private function updateConfigurationValues(array $valuesByKey)
    {
        foreach ($valuesByKey as $key => $value) {
            if (!Configuration::updateValue((string) $key, $value)) {
                return false;
            }
        }

        return true;
    }

    private function deleteConfigurationValues()
    {
        foreach ($this->getAllConfigurationKeys() as $key) {
            if (Configuration::hasKey($key) && !Configuration::deleteByName($key)) {
                return false;
            }
        }

        return true;
    }

    private function registerModuleHooks()
    {
        foreach (self::MODULE_HOOKS as $hookName) {
            if (!$this->registerHook($hookName)) {
                return false;
            }
        }

        return true;
    }

    private function unregisterModuleHooks()
    {
        foreach (self::MODULE_HOOKS as $hookName) {
            if (!$this->unregisterHook($hookName)) {
                return false;
            }
        }

        return true;
    }

    private function installTab()
    {
        $tabId = (int) Db::getInstance()->getValue(
            'SELECT `id_tab` FROM `' . _DB_PREFIX_ . 'tab` WHERE `class_name` = \'NextOrderDiscount\''
        );
        if (!$tabId) {
            $tabId = null;
        }

        $tab = new Tab($tabId);
        $tab->class_name = 'NextOrderDiscount';
        $tab->name = [];
        foreach (Language::getLanguages() as $lang) {
            $tab->name[$lang['id_lang']] = $this->l('Next Order Discount');
        }
        $tab->id_parent = (int) Db::getInstance()->getValue(
            'SELECT `id_tab` FROM `' . _DB_PREFIX_ . 'tab` WHERE `class_name` = \'AdminCatalog\''
        );
        $tab->module = $this->name;

        return $tab->save();
    }

    private function uninstallTab()
    {
        $tabId = (int) Db::getInstance()->getValue(
            'SELECT `id_tab` FROM `' . _DB_PREFIX_ . 'tab` WHERE `class_name` = \'NextOrderDiscount\''
        );
        if (!$tabId) {
            return true;
        }

        $tab = new Tab($tabId);

        return $tab->delete();
    }
}
