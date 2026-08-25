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

if (!defined('SNOD_AUTOLOAD_REGISTERED')) {
    define('SNOD_AUTOLOAD_REGISTERED', true);
    // PSR-4 autoloader for the module's own classes/ (no Composer/vendor needed;
    // the module ships zero third-party dependencies).
    spl_autoload_register(function ($class) {
        $prefix = 'Setecom\\NextOrderDiscount\\';
        if (strpos($class, $prefix) !== 0) {
            return;
        }
        $relative = substr($class, strlen($prefix));
        $path = __DIR__ . '/classes/' . str_replace('\\', '/', $relative) . '.php';
        if (is_file($path)) {
            require_once $path;
        }
    });
}

use Setecom\NextOrderDiscount\Coupon\CartRuleAdapter;
use Setecom\NextOrderDiscount\Coupon\CouponGenerationService;
use Setecom\NextOrderDiscount\Coupon\CouponLifecycleManager;
use Setecom\NextOrderDiscount\Cron\CronRouter;
use Setecom\NextOrderDiscount\Cron\CronSecurityService;
use Setecom\NextOrderDiscount\Cron\LockManager;
use Setecom\NextOrderDiscount\Logger\ModuleLogger;
use Setecom\NextOrderDiscount\Mail\CouponMailer;
use Setecom\NextOrderDiscount\Mail\MailTemplateResolver;
use Setecom\NextOrderDiscount\Queue\CouponEmailHandler;
use Setecom\NextOrderDiscount\Queue\QueueRetryPolicy;
use Setecom\NextOrderDiscount\Queue\QueueService;
use Setecom\NextOrderDiscount\Queue\QueueWorker;
use Setecom\NextOrderDiscount\Queue\ReminderEmailHandler;
use Setecom\NextOrderDiscount\Reminder\ReminderCandidateRepository;
use Setecom\NextOrderDiscount\Reminder\ReminderMailer;
use Setecom\NextOrderDiscount\Reminder\ReminderPlanner;
use Setecom\NextOrderDiscount\Reminder\ReminderPolicy;
use Setecom\NextOrderDiscount\Repository\CouponLinkRepository;
use Setecom\NextOrderDiscount\Repository\CronLockRepository;
use Setecom\NextOrderDiscount\Repository\DispatchQueueRepository;
use Setecom\NextOrderDiscount\Repository\LogRepository;
use Setecom\NextOrderDiscount\Repository\RuleEmailRepository;
use Setecom\NextOrderDiscount\Repository\RuleRepository;
use Setecom\NextOrderDiscount\Rule\RuleFormHandler;
use Setecom\NextOrderDiscount\Rule\RuleMatcher;
use Setecom\NextOrderDiscount\Rule\RulePresenter;

class set_next_order_discount extends Module
{
    private const MODULE_HOOKS = [
        'actionValidateOrder',
        'actionOrderStatusPostUpdate',
    ];

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
     * Factory for the coupon generation service with its collaborators wired.
     * Classes load on demand through the registered PSR-4 autoloader.
     *
     * @return CouponGenerationService
     */
    private function getCouponGenerationService()
    {
        return new CouponGenerationService(
            $this->getRuleMatcher(),
            new CartRuleAdapter(),
            new CouponLinkRepository(),
            new QueueService(new DispatchQueueRepository()),
            $this->getModuleLogger()
        );
    }

    /**
     * Factory for the rule repository. Public so the admin controller can list
     * and manage discount rules.
     *
     * @return RuleRepository
     */
    public function getRuleRepository()
    {
        return new RuleRepository();
    }

    /**
     * Factory for the rule form handler (validation + persistence).
     *
     * @return RuleFormHandler
     */
    public function getRuleFormHandler()
    {
        return new RuleFormHandler(new RuleRepository(), new RuleEmailRepository());
    }

    /**
     * Factory for the rule list presenter.
     *
     * @return RulePresenter
     */
    public function getRulePresenter()
    {
        return new RulePresenter();
    }

    /**
     * Factory for the rule matcher (order context -> matching rules).
     *
     * @return RuleMatcher
     */
    public function getRuleMatcher()
    {
        return new RuleMatcher(new RuleRepository());
    }

    /**
     * Guards the public cron endpoint with the secret token.
     *
     * @return CronSecurityService
     */
    public function getCronSecurityService()
    {
        return new CronSecurityService();
    }

    /**
     * Structured module logger, with the minimum level taken from configuration.
     *
     * @return ModuleLogger
     */
    public function getModuleLogger()
    {
        $level = (string) Configuration::get('SNOD_LOG_LEVEL');
        if ($level === '') {
            $level = ModuleLogger::LEVEL_INFO;
        }

        return new ModuleLogger(new LogRepository(), $level);
    }

    /**
     * Composition root for the background tasks: assembles the queue worker (with
     * its email/reminder handlers), the reminder planner and the coupon lifecycle
     * manager behind the lock-aware cron router.
     *
     * @return CronRouter
     */
    public function getCronRouter()
    {
        $couponLinkRepository = new CouponLinkRepository();
        $dispatchQueueRepository = new DispatchQueueRepository();
        $queueService = new QueueService($dispatchQueueRepository);

        $ruleEmailRepository = new RuleEmailRepository();

        $reminderHandler = new ReminderEmailHandler(new ReminderMailer($couponLinkRepository, $ruleEmailRepository));
        $handlers = [
            QueueService::TASK_COUPON_EMAIL => new CouponEmailHandler(
                new CouponMailer($couponLinkRepository, new MailTemplateResolver(), $ruleEmailRepository)
            ),
            QueueService::TASK_REMINDER_1 => $reminderHandler,
            QueueService::TASK_REMINDER_2 => $reminderHandler,
        ];

        $logger = $this->getModuleLogger();

        $worker = new QueueWorker($dispatchQueueRepository, new QueueRetryPolicy(), $handlers, $logger);

        $planner = new ReminderPlanner(
            new ReminderCandidateRepository(),
            $queueService,
            ReminderPolicy::fromConfiguration($this->getCurrentShopId())
        );

        $lifecycleManager = new CouponLifecycleManager($couponLinkRepository, new CartRuleAdapter());

        return new CronRouter(
            new LockManager(new CronLockRepository()),
            $worker,
            $planner,
            $lifecycleManager,
            $logger
        );
    }

    /**
     * @return int|null the current shop id, or null when not in a single-shop context
     */
    private function getCurrentShopId()
    {
        if (isset($this->context->shop) && (int) $this->context->shop->id > 0) {
            return (int) $this->context->shop->id;
        }

        return null;
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
            'SNOD_CRON_TOKEN' => $this->generateCronToken(),
            'SNOD_LOG_LEVEL' => ModuleLogger::LEVEL_INFO,
        ];
    }

    /**
     * Generates a high-entropy secret token for the public cron endpoint, with a
     * portable fallback when the CSPRNG is unavailable.
     *
     * @return string
     */
    private function generateCronToken()
    {
        try {
            return bin2hex(random_bytes(16));
        } catch (Exception $e) {
            return md5(uniqid((string) mt_rand(), true));
        }
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
