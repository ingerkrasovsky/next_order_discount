#!/usr/bin/env php
<?php
/**
 * Demo order generator for PrestaShop 8/9.
 *
 * Deployment: copy this file to the PrestaShop root, next to config/, classes/
 * and modules/, then invoke it with PHP CLI from cron.
 *
 * This is demo-only software. It creates real customers, addresses, carts,
 * orders and order-status history through PrestaShop's own ObjectModel API.
 */

if (PHP_SAPI !== 'cli') {
    if (!headers_sent()) {
        http_response_code(404);
    }
    exit;
}

$prestashopRoot = __DIR__;
$bootstrapFile = $prestashopRoot . '/config/config.inc.php';

if (!is_file($bootstrapFile)) {
    fwrite(STDERR, "Place demo_order_generator.php in the PrestaShop root directory.\n");
    exit(2);
}

require_once $bootstrapFile;

// PaymentModule::validateOrder() uses locale and translator services that are
// provided by the Symfony kernel in modern PrestaShop versions. Web requests
// boot it automatically; a standalone CLI entry point must do so explicitly.
if (is_file($prestashopRoot . '/app/AppKernel.php') && is_file($prestashopRoot . '/app/AdminKernel.php')) {
    require_once $prestashopRoot . '/app/AppKernel.php';
    require_once $prestashopRoot . '/app/AdminKernel.php';

    global $kernel;
    if (!isset($kernel) || !is_object($kernel)) {
        $kernel = new AdminKernel('prod', false);
        $kernel->boot();
        Context::getContext()->container = $kernel->getContainer();
        register_shutdown_function(function () use (&$kernel) {
            if (is_object($kernel)) {
                $kernel->shutdown();
            }
        });
    }
}

final class DemoOrderGenerator
{
    const DEFAULT_MIN_ORDERS = 26;
    const DEFAULT_MAX_ORDERS = 34;
    const DEFAULT_START_HOUR = 7;
    const DEFAULT_END_HOUR = 23;
    const DEFAULT_MAX_PER_RUN = 3;
    const DEFAULT_COUPON_USE_RATE = 35;
    const RETURNING_CUSTOMER_EMAIL = 'demo.returning@example.test';

    private $context;
    private $shop;
    private $idShop;
    private $idShopGroup;
    private $idLang;
    private $paymentModule;
    private $rootDir;
    private $stateDir;
    private $verbose;
    private $productCache = [];

    private $returningCustomerProfiles = [
        ['email' => self::RETURNING_CUSTOMER_EMAIL, 'firstname' => 'Demo', 'lastname' => 'Returning'],
        ['email' => 'alex.martin.demo@example.test', 'firstname' => 'Alex', 'lastname' => 'Martin'],
        ['email' => 'emma.bernard.demo@example.test', 'firstname' => 'Emma', 'lastname' => 'Bernard'],
        ['email' => 'lucas.wilson.demo@example.test', 'firstname' => 'Lucas', 'lastname' => 'Wilson'],
        ['email' => 'sofia.andersen.demo@example.test', 'firstname' => 'Sofia', 'lastname' => 'Andersen'],
        ['email' => 'noah.miller.demo@example.test', 'firstname' => 'Noah', 'lastname' => 'Miller'],
        ['email' => 'mia.dubois.demo@example.test', 'firstname' => 'Mia', 'lastname' => 'Dubois'],
        ['email' => 'leo.taylor.demo@example.test', 'firstname' => 'Leo', 'lastname' => 'Taylor'],
        ['email' => 'anna.jensen.demo@example.test', 'firstname' => 'Anna', 'lastname' => 'Jensen'],
        ['email' => 'olivia.brown.demo@example.test', 'firstname' => 'Olivia', 'lastname' => 'Brown'],
        ['email' => 'ethan.davis.demo@example.test', 'firstname' => 'Ethan', 'lastname' => 'Davis'],
        ['email' => 'ava.moore.demo@example.test', 'firstname' => 'Ava', 'lastname' => 'Moore'],
        ['email' => 'liam.jackson.demo@example.test', 'firstname' => 'Liam', 'lastname' => 'Jackson'],
        ['email' => 'isabella.white.demo@example.test', 'firstname' => 'Isabella', 'lastname' => 'White'],
        ['email' => 'mateo.harris.demo@example.test', 'firstname' => 'Mateo', 'lastname' => 'Harris'],
        ['email' => 'amelia.clark.demo@example.test', 'firstname' => 'Amelia', 'lastname' => 'Clark'],
        ['email' => 'henry.lewis.demo@example.test', 'firstname' => 'Henry', 'lastname' => 'Lewis'],
        ['email' => 'charlotte.walker.demo@example.test', 'firstname' => 'Charlotte', 'lastname' => 'Walker'],
        ['email' => 'james.hall.demo@example.test', 'firstname' => 'James', 'lastname' => 'Hall'],
        ['email' => 'harper.young.demo@example.test', 'firstname' => 'Harper', 'lastname' => 'Young'],
        ['email' => 'benjamin.king.demo@example.test', 'firstname' => 'Benjamin', 'lastname' => 'King'],
        ['email' => 'evelyn.wright.demo@example.test', 'firstname' => 'Evelyn', 'lastname' => 'Wright'],
        ['email' => 'daniel.scott.demo@example.test', 'firstname' => 'Daniel', 'lastname' => 'Scott'],
        ['email' => 'camila.green.demo@example.test', 'firstname' => 'Camila', 'lastname' => 'Green'],
        ['email' => 'sebastian.baker.demo@example.test', 'firstname' => 'Sebastian', 'lastname' => 'Baker'],
        ['email' => 'luna.adams.demo@example.test', 'firstname' => 'Luna', 'lastname' => 'Adams'],
        ['email' => 'jack.nelson.demo@example.test', 'firstname' => 'Jack', 'lastname' => 'Nelson'],
        ['email' => 'ella.carter.demo@example.test', 'firstname' => 'Ella', 'lastname' => 'Carter'],
        ['email' => 'samuel.mitchell.demo@example.test', 'firstname' => 'Samuel', 'lastname' => 'Mitchell'],
        ['email' => 'grace.perez.demo@example.test', 'firstname' => 'Grace', 'lastname' => 'Perez'],
        ['email' => 'david.roberts.demo@example.test', 'firstname' => 'David', 'lastname' => 'Roberts'],
        ['email' => 'chloe.turner.demo@example.test', 'firstname' => 'Chloe', 'lastname' => 'Turner'],
        ['email' => 'joseph.phillips.demo@example.test', 'firstname' => 'Joseph', 'lastname' => 'Phillips'],
        ['email' => 'zoe.campbell.demo@example.test', 'firstname' => 'Zoe', 'lastname' => 'Campbell'],
        ['email' => 'owen.parker.demo@example.test', 'firstname' => 'Owen', 'lastname' => 'Parker'],
        ['email' => 'nora.evans.demo@example.test', 'firstname' => 'Nora', 'lastname' => 'Evans'],
        ['email' => 'gabriel.edwards.demo@example.test', 'firstname' => 'Gabriel', 'lastname' => 'Edwards'],
        ['email' => 'lily.collins.demo@example.test', 'firstname' => 'Lily', 'lastname' => 'Collins'],
        ['email' => 'julian.stewart.demo@example.test', 'firstname' => 'Julian', 'lastname' => 'Stewart'],
        ['email' => 'maya.morris.demo@example.test', 'firstname' => 'Maya', 'lastname' => 'Morris'],
    ];

    private $scenarioNames = [
        'first_order',
        'premium_order',
        'high_value',
        'spend_booster',
        'clothing',
        'studio_design',
        'france_us',
        'usd_market',
        'core_collection',
        'fallback',
    ];

    public function __construct($rootDir, $verbose = false)
    {
        $this->rootDir = rtrim((string) $rootDir, '/');
        $this->stateDir = $this->rootDir . '/var/demo-order-generator';
        $this->verbose = (bool) $verbose;

        $this->idShop = (int) Configuration::get('PS_SHOP_DEFAULT');
        if ($this->idShop <= 0) {
            $this->idShop = 1;
        }

        Shop::setContext(Shop::CONTEXT_SHOP, $this->idShop);
        $this->shop = new Shop($this->idShop);
        if (!Validate::isLoadedObject($this->shop)) {
            throw new RuntimeException('Default shop could not be loaded.');
        }

        $this->idShopGroup = (int) $this->shop->id_shop_group;
        $this->idLang = (int) Configuration::get('PS_LANG_DEFAULT', null, null, $this->idShop);
        if ($this->idLang <= 0) {
            $this->idLang = 1;
        }

        $this->context = Context::getContext();
        $this->context->shop = $this->shop;
        $this->context->language = new Language($this->idLang);
        $this->context->currency = new Currency((int) Configuration::get('PS_CURRENCY_DEFAULT'));
        $this->context->country = new Country((int) Configuration::get('PS_COUNTRY_DEFAULT'), $this->idLang);

        $this->paymentModule = $this->loadPaymentModule();
    }

    public function scenarioNames()
    {
        return $this->scenarioNames;
    }

    public function runScheduled(array $options)
    {
        $date = $options['date'];
        $stateFile = $this->stateDir . '/' . $date . '.json';

        $this->ensureStateDirectory();
        $lock = fopen($this->stateDir . '/generator.lock', 'c+');
        if (!$lock || !flock($lock, LOCK_EX | LOCK_NB)) {
            throw new RuntimeException('Another generator process is already running.');
        }

        try {
            $state = $this->loadState($stateFile);
            if (!$state && $options['status']) {
                echo 'No saved demo-order plan for ' . $date . '.' . PHP_EOL;

                return;
            }
            if (!$state) {
                $count = $options['count'] !== null
                    ? $options['count']
                    : random_int($options['min'], $options['max']);
                $state = $this->buildState(
                    $date,
                    $count,
                    $options['start_hour'],
                    $options['end_hour'],
                    $options['scenario'],
                    $options['outcome'],
                    $options['coupon_use_rate']
                );
                $this->saveState($stateFile, $state);
                $this->log('Created daily plan: ' . $count . ' orders for ' . $date . '.');
            }

            if ($options['status']) {
                $this->printStateSummary($state, $stateFile);

                return;
            }

            $now = time();
            $created = 0;
            $processed = 0;
            foreach ($state['orders'] as $index => $entry) {
                if ($processed >= $options['max_per_run']) {
                    break;
                }
                if (!empty($entry['done']) || strtotime($entry['at']) > $now) {
                    continue;
                }
                if (!array_key_exists('use_coupon', $entry)) {
                    $state['orders'][$index]['use_coupon'] = $this->shouldUseCoupon(
                        $entry['scenario'],
                        $entry['outcome'],
                        $options['coupon_use_rate']
                    );
                    $entry = $state['orders'][$index];
                }
                ++$processed;

                $existingId = $this->findOrderByMarker($entry['id']);
                if ($existingId > 0) {
                    $state['orders'][$index]['done'] = true;
                    $state['orders'][$index]['order_id'] = $existingId;
                    $state['orders'][$index]['recovered'] = true;
                    $this->saveState($stateFile, $state);
                    continue;
                }

                $state['orders'][$index]['attempts'] = isset($entry['attempts'])
                    ? (int) $entry['attempts'] + 1
                    : 1;
                $this->saveState($stateFile, $state);

                try {
                    $result = $this->createPlannedOrder($entry);
                    $state['orders'][$index]['done'] = true;
                    $state['orders'][$index]['order_id'] = $result['order_id'];
                    $state['orders'][$index]['total'] = $result['total'];
                    $state['orders'][$index]['currency'] = $result['currency'];
                    $state['orders'][$index]['final_status'] = $result['final_status'];
                    $state['orders'][$index]['coupon_code'] = $result['coupon_code'];
                    $state['orders'][$index]['coupon_cart_rule_id'] = $result['coupon_cart_rule_id'];
                    unset($state['orders'][$index]['error']);
                    ++$created;
                    $this->log(sprintf(
                        'Created order #%d: %s, %s %.2f, status %s%s.',
                        $result['order_id'],
                        $entry['scenario'],
                        $result['currency'],
                        $result['total'],
                        $result['final_status'],
                        $result['coupon_code'] ? ', used coupon ' . $result['coupon_code'] : ''
                    ));
                } catch (Throwable $e) {
                    $state['orders'][$index]['error'] = $e->getMessage();
                    $this->log('Failed ' . $entry['id'] . ': ' . $e->getMessage(), true);
                }

                $this->saveState($stateFile, $state);
            }

            $this->printStateSummary($state, $stateFile);
            if ($processed > 0) {
                echo sprintf("This run processed %d due slot(s) and created %d order(s).\n", $processed, $created);
            }
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

    public function runForced($count, $scenario, $outcome, $couponUseRate, $dryRun)
    {
        $count = max(1, (int) $count);
        $entries = $this->buildEntries(
            date('Y-m-d'),
            $count,
            0,
            24,
            $scenario,
            $outcome,
            $couponUseRate,
            true
        );

        if ($dryRun) {
            echo json_encode($entries, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;

            return;
        }

        foreach ($entries as $entry) {
            $result = $this->createPlannedOrder($entry);
            $this->log(sprintf(
                'Created order #%d: %s, %s %.2f, status %s%s.',
                $result['order_id'],
                $entry['scenario'],
                $result['currency'],
                $result['total'],
                $result['final_status'],
                $result['coupon_code'] ? ', used coupon ' . $result['coupon_code'] : ''
            ));
        }
    }

    public function previewPlan(array $options)
    {
        $count = $options['count'] !== null
            ? $options['count']
            : random_int($options['min'], $options['max']);
        $state = $this->buildState(
            $options['date'],
            $count,
            $options['start_hour'],
            $options['end_hour'],
            $options['scenario'],
            $options['outcome'],
            $options['coupon_use_rate']
        );
        echo json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
    }

    private function buildState($date, $count, $startHour, $endHour, $scenario, $outcome, $couponUseRate)
    {
        return [
            'version' => 2,
            'date' => $date,
            'created_at' => date('c'),
            'target' => (int) $count,
            'coupon_use_rate' => (int) $couponUseRate,
            'orders' => $this->buildEntries(
                $date,
                $count,
                $startHour,
                $endHour,
                $scenario,
                $outcome,
                $couponUseRate,
                false
            ),
        ];
    }

    private function buildEntries($date, $count, $startHour, $endHour, $scenario, $outcome, $couponUseRate, $immediate)
    {
        $scenarios = [];
        for ($i = 0; $i < $count; ++$i) {
            $scenarios[] = $scenario ?: $this->scenarioNames[$i % count($this->scenarioNames)];
        }
        shuffle($scenarios);

        $start = strtotime($date . ' 00:00:00 +' . (int) $startHour . ' hours');
        $end = strtotime($date . ' 00:00:00 +' . (int) $endHour . ' hours') - 1;
        $entries = [];
        $returningCustomerIndex = random_int(0, count($this->returningCustomerProfiles) - 1);

        foreach ($scenarios as $index => $scenarioName) {
            $timestamp = $immediate ? time() : random_int($start, $end);
            $entryId = sprintf(
                '%s-%03d-%s',
                str_replace('-', '', $date),
                $index + 1,
                substr(sha1($scenarioName . $timestamp . random_int(1, PHP_INT_MAX)), 0, 8)
            );
            $outcomeName = $outcome ?: $this->pickWeighted([
                'paid' => 42,
                'processing' => 20,
                'shipped' => 10,
                'delivered' => 10,
                'awaiting_payment' => 8,
                'canceled' => 5,
                'refunded' => 5,
            ]);
            $entries[] = [
                'id' => $entryId,
                'at' => date('Y-m-d H:i:s', $timestamp),
                'scenario' => $scenarioName,
                'outcome' => $outcomeName,
                'customer_email' => $scenarioName === 'first_order'
                    ? $this->uniqueCustomerEmail($entryId)
                    : $this->returningCustomerEmailByIndex($returningCustomerIndex++),
                'use_coupon' => $this->shouldUseCoupon($scenarioName, $outcomeName, $couponUseRate),
                'done' => false,
                'attempts' => 0,
            ];
        }

        usort($entries, function ($left, $right) {
            return strcmp($left['at'], $right['at']);
        });

        return $entries;
    }

    private function createPlannedOrder(array $entry)
    {
        $scenario = $this->scenarioSpec($entry['scenario']);
        $email = isset($entry['customer_email']) && Validate::isEmail($entry['customer_email'])
            ? (string) $entry['customer_email']
            : ($entry['scenario'] === 'first_order'
                ? $this->uniqueCustomerEmail($entry['id'])
                : $this->returningCustomerEmail($entry['id']));
        if ($entry['scenario'] === 'first_order') {
            $idCustomer = (int) Customer::customerExists($email, true, false);
            $customer = $idCustomer > 0 ? new Customer($idCustomer) : $this->createCustomer($email);
        } else {
            $customer = $this->ensureReturningCustomer($email);
        }

        return $this->createOrderForCustomer(
            $customer,
            $scenario,
            $entry['outcome'],
            $entry['id'],
            $entry['at'],
            !empty($entry['use_coupon'])
        );
    }

    private function shouldUseCoupon($scenario, $outcome, $couponUseRate)
    {
        if ($scenario === 'first_order'
            || !in_array($outcome, ['paid', 'processing', 'shipped', 'delivered'], true)) {
            return false;
        }

        return (int) $couponUseRate > 0 && random_int(1, 100) <= (int) $couponUseRate;
    }

    private function scenarioSpec($name)
    {
        switch ($name) {
            case 'first_order':
                return ['country' => 'US', 'currency' => 'USD', 'product' => 'art', 'target_min' => 0];
            case 'premium_order':
                return ['country' => 'DK', 'currency' => 'USD', 'product' => 'art', 'target_min' => random_int(650, 800)];
            case 'high_value':
                return ['country' => 'DK', 'currency' => 'USD', 'product' => 'art', 'target_min' => random_int(140, 320)];
            case 'spend_booster':
                return ['country' => 'DK', 'currency' => 'USD', 'product' => 'art', 'target_min' => random_int(65, 95)];
            case 'clothing':
                return ['country' => 'DK', 'currency' => 'EUR', 'product' => 'clothing', 'target_min' => 0];
            case 'studio_design':
                return ['country' => 'DK', 'currency' => 'EUR', 'product' => 'studio_accessory', 'target_min' => 0];
            case 'france_us':
                return ['country' => random_int(0, 1) ? 'FR' : 'US', 'currency' => 'EUR', 'product' => 'art', 'target_min' => 0];
            case 'usd_market':
                return ['country' => 'DK', 'currency' => 'USD', 'product' => 'art', 'target_min' => 0];
            case 'core_collection':
                return ['country' => 'DK', 'currency' => 'EUR', 'product' => 'art', 'target_min' => 0];
            case 'fallback':
                return ['country' => 'DK', 'currency' => 'EUR', 'product' => 'non_studio_accessory', 'target_min' => 0];
        }

        throw new InvalidArgumentException('Unknown scenario: ' . $name);
    }

    private function createOrderForCustomer(
        Customer $customer,
        array $scenario,
        $outcome,
        $marker,
        $scheduledAt,
        $useCoupon = false
    )
    {
        $idCountry = $this->countryId($scenario['country']);
        $idCurrency = $this->currencyId($scenario['currency']);
        $address = $this->ensureAddress($customer, $idCountry, $scenario['country']);
        $idProduct = $this->productId($scenario['product']);
        $idAttribute = (int) Product::getDefaultAttribute($idProduct);
        $idCarrier = $this->carrierId($idCountry);

        $currency = new Currency($idCurrency, $this->idLang, $this->idShop);
        $country = new Country($idCountry, $this->idLang);
        $this->context->customer = $customer;
        $this->context->currency = $currency;
        $this->context->country = $country;

        $cart = new Cart();
        $cart->id_shop = $this->idShop;
        $cart->id_shop_group = $this->idShopGroup;
        $cart->id_customer = (int) $customer->id;
        $cart->id_address_delivery = (int) $address->id;
        $cart->id_address_invoice = (int) $address->id;
        $cart->id_currency = $idCurrency;
        $cart->id_lang = $this->idLang;
        $cart->id_carrier = $idCarrier;
        $cart->secure_key = (string) $customer->secure_key;
        $cart->recyclable = 0;
        $cart->gift = 0;
        if (!$cart->add()) {
            throw new RuntimeException('Cart could not be created.');
        }

        $this->context->cart = $cart;
        $stockBefore = (int) StockAvailable::getQuantityAvailableByProduct($idProduct, $idAttribute, $this->idShop);
        $quantity = 0;
        do {
            $result = $cart->updateQty(
                1,
                $idProduct,
                $idAttribute,
                false,
                'up',
                (int) $address->id,
                $this->shop,
                false,
                true
            );
            if ($result === false || (is_int($result) && $result < 0)) {
                throw new RuntimeException('Product could not be added to the cart.');
            }
            ++$quantity;
            $qualificationTotal = (float) $cart->getOrderTotal(true, Cart::BOTH_WITHOUT_SHIPPING);
        } while ($scenario['target_min'] > 0 && $qualificationTotal < $scenario['target_min'] && $quantity < 60);

        Db::getInstance()->delete('cart_cart_rule', '`id_cart` = ' . (int) $cart->id);
        $appliedCoupon = $useCoupon ? $this->applyAvailableModuleCoupon($cart, $customer) : null;
        $total = (float) $cart->getOrderTotal(true, Cart::BOTH);
        if ($scenario['target_min'] > 0 && $qualificationTotal < $scenario['target_min']) {
            throw new RuntimeException('Could not reach the target cart total.');
        }

        if ($outcome === 'awaiting_payment') {
            $initialState = (int) Configuration::get('PS_OS_BANKWIRE');
        } elseif ($outcome === 'bootstrap_delivered') {
            $initialState = (int) Configuration::get('PS_OS_DELIVERED');
        } else {
            $initialState = (int) Configuration::get('PS_OS_PAYMENT');
        }
        $message = '[demo-order-generator:' . $marker . '] scenario=' . $this->safeLabel($scenario)
            . '; outcome=' . $outcome
            . ($appliedCoupon ? '; coupon=' . $appliedCoupon['code'] : '');

        $idOrder = 0;
        $validationError = null;
        try {
            $validated = $this->paymentModule->validateOrder(
                (int) $cart->id,
                $initialState,
                $total,
                'Demo payment',
                $message,
                ['transaction_id' => 'DEMO-' . strtoupper(substr(sha1($marker), 0, 12))],
                $idCurrency,
                false,
                (string) $customer->secure_key,
                $this->shop
            );
            $idOrder = (int) Order::getIdByCartId((int) $cart->id);
            if ($idOrder <= 0) {
                $idOrder = (int) $this->paymentModule->currentOrder;
            }
            if (!$validated || $idOrder <= 0) {
                throw new RuntimeException('PrestaShop did not finish creating the order.');
            }
        } catch (Throwable $e) {
            $validationError = $e;
            $idOrder = (int) Order::getIdByCartId((int) $cart->id);
        } finally {
            $stockAfter = (int) StockAvailable::getQuantityAvailableByProduct($idProduct, $idAttribute, $this->idShop);
            if ($stockAfter < $stockBefore) {
                StockAvailable::updateQuantity($idProduct, $idAttribute, $stockBefore - $stockAfter, $this->idShop);
            }
        }

        if ($validationError !== null) {
            if ($idOrder <= 0) {
                throw $validationError;
            }

            // Some payment modules can throw after writing the order but before
            // adding its first history row. Complete that recoverable state and
            // preserve the marker so the next cron run cannot duplicate it.
            $partialOrder = new Order($idOrder);
            if (!Validate::isLoadedObject($partialOrder)) {
                throw $validationError;
            }
            if ((int) $partialOrder->current_state <= 0) {
                $this->changeOrderState($idOrder, $initialState);
            }
            $this->ensureMarkerMessage($idOrder, (int) $cart->id, (int) $customer->id, $marker, $message);
            $this->log('Recovered partially created order #' . $idOrder . ' after: ' . $validationError->getMessage(), true);
        }

        $finalState = $initialState;
        if ($outcome !== 'paid' && $outcome !== 'awaiting_payment' && $outcome !== 'bootstrap_delivered') {
            $finalState = $this->outcomeStateId($outcome);
            $this->changeOrderState($idOrder, $finalState);
        }

        $this->setOrderDate($idOrder, (int) $cart->id, $scheduledAt);
        $order = new Order($idOrder);
        $state = new OrderState((int) $order->current_state, $this->idLang);

        return [
            'order_id' => $idOrder,
            'total' => (float) $order->total_paid,
            'currency' => (string) $currency->iso_code,
            'final_status' => is_array($state->name) ? (string) reset($state->name) : (string) $state->name,
            'coupon_code' => $appliedCoupon ? (string) $appliedCoupon['code'] : null,
            'coupon_cart_rule_id' => $appliedCoupon ? (int) $appliedCoupon['id_cart_rule'] : null,
        ];
    }

    /**
     * Applies one active voucher previously emailed by set_next_order_discount.
     * CartRule::checkValidity() keeps the demo flow identical to a real checkout:
     * ownership, validity dates, usage limits, currency minimums and restrictions
     * all have to pass before the code is attached to the cart.
     */
    private function applyAvailableModuleCoupon(Cart $cart, Customer $customer)
    {
        if (!Module::isInstalled('set_next_order_discount')
            || !Module::isEnabled('set_next_order_discount')) {
            return null;
        }

        $rows = Db::getInstance()->executeS(
            'SELECT l.`id_cart_rule`, l.`coupon_code`'
            . ' FROM `' . _DB_PREFIX_ . 'snod_coupon_link` l'
            . ' INNER JOIN `' . _DB_PREFIX_ . 'cart_rule` cr'
            . ' ON cr.`id_cart_rule` = l.`id_cart_rule`'
            . ' WHERE l.`id_shop` = ' . $this->idShop
            . ' AND l.`id_customer` = ' . (int) $customer->id
            . ' AND l.`status` IN (\'emailed\', \'reminded\')'
            . ' AND cr.`id_customer` = ' . (int) $customer->id
            . ' AND cr.`active` = 1 AND cr.`quantity` > 0'
            . ' ORDER BY l.`id_snod_coupon_link` DESC LIMIT 50'
        );
        if (!is_array($rows) || empty($rows)) {
            if ($this->verbose) {
                $this->log('No emailed module coupon is available for customer #' . (int) $customer->id . '.');
            }

            return null;
        }

        // Avoid always consuming the newest campaign when several valid codes
        // exist, which makes the demo coupon funnel look more natural.
        shuffle($rows);
        foreach ($rows as $row) {
            $idCartRule = isset($row['id_cart_rule']) ? (int) $row['id_cart_rule'] : 0;
            if ($idCartRule <= 0) {
                continue;
            }

            $cartRule = new CartRule($idCartRule, $this->idLang);
            if (!Validate::isLoadedObject($cartRule)
                || $cartRule->checkValidity($this->context, false, false, true, false) !== true) {
                continue;
            }
            if (!$cart->addCartRule($idCartRule)) {
                continue;
            }

            return [
                'id_cart_rule' => $idCartRule,
                'code' => (string) $cartRule->code,
            ];
        }

        if ($this->verbose) {
            $this->log('Emailed module coupons exist, but none is valid for cart #' . (int) $cart->id . '.');
        }

        return null;
    }

    private function ensureReturningCustomer($email)
    {
        $profile = $this->returningCustomerProfile($email);
        $idCustomer = (int) Customer::customerExists($profile['email'], true, false);
        $customer = $idCustomer > 0
            ? new Customer($idCustomer)
            : $this->createCustomer($profile['email'], $profile['firstname'], $profile['lastname']);
        if (!Validate::isLoadedObject($customer)) {
            throw new RuntimeException('Returning demo customer could not be loaded.');
        }

        if ((int) Order::getCustomerNbOrders((int) $customer->id) === 0) {
            $entryId = 'bootstrap-' . (int) $customer->id;
            $existingId = $this->findOrderByMarker($entryId);
            if ($existingId <= 0) {
                $scenario = ['country' => 'DK', 'currency' => 'EUR', 'product' => 'art', 'target_min' => 0];
                $this->createOrderForCustomer(
                    $customer,
                    $scenario,
                    'bootstrap_delivered',
                    $entryId,
                    date('Y-m-d H:i:s', strtotime('-7 days')),
                    false
                );
                $this->log('Created one historical bootstrap order for ' . $profile['email'] . '.');
            }
        }

        return $customer;
    }

    private function createCustomer($email, $firstName = null, $lastName = null)
    {
        $firstNames = ['Alex', 'Emma', 'Lucas', 'Sofia', 'Noah', 'Mia', 'Leo', 'Anna'];
        $lastNames = ['Martin', 'Bernard', 'Wilson', 'Andersen', 'Miller', 'Dubois', 'Taylor', 'Jensen'];

        $customer = new Customer();
        $customer->firstname = $firstName ?: $firstNames[array_rand($firstNames)];
        $customer->lastname = $lastName ?: $lastNames[array_rand($lastNames)];
        $customer->email = $email;
        $customer->passwd = Tools::hash(Tools::passwdGen(24));
        $customer->secure_key = md5(uniqid((string) random_int(1, PHP_INT_MAX), true));
        $customer->id_lang = $this->idLang;
        $customer->id_shop = $this->idShop;
        $customer->id_shop_group = $this->idShopGroup;
        $customer->id_default_group = (int) Configuration::get('PS_CUSTOMER_GROUP');
        $customer->active = 1;
        $customer->is_guest = 0;
        $customer->newsletter = 0;
        $customer->optin = 0;
        if (!$customer->add()) {
            throw new RuntimeException('Demo customer could not be created: ' . $email);
        }
        $customer->updateGroup([(int) $customer->id_default_group]);

        return $customer;
    }

    private function ensureAddress(Customer $customer, $idCountry, $isoCode)
    {
        $alias = 'Demo ' . strtoupper($isoCode);
        $idAddress = (int) Db::getInstance()->getValue(
            'SELECT `id_address` FROM `' . _DB_PREFIX_ . 'address`'
            . ' WHERE `id_customer` = ' . (int) $customer->id
            . ' AND `id_country` = ' . (int) $idCountry
            . ' AND `alias` = \'' . pSQL($alias) . '\''
            . ' AND `deleted` = 0 ORDER BY `id_address` DESC'
        );
        if ($idAddress > 0) {
            return new Address($idAddress);
        }

        $locations = [
            'US' => ['city' => 'New York', 'postcode' => '10001', 'address' => '25 Broadway'],
            'FR' => ['city' => 'Paris', 'postcode' => '75001', 'address' => '12 Rue de Rivoli'],
            'DK' => ['city' => 'Copenhagen', 'postcode' => '2100', 'address' => '18 Osterbrogade'],
        ];
        $location = isset($locations[$isoCode]) ? $locations[$isoCode] : ['city' => 'Demo City', 'postcode' => '10000', 'address' => '1 Demo Street'];

        $address = new Address();
        $address->id_customer = (int) $customer->id;
        $address->alias = $alias;
        $address->firstname = (string) $customer->firstname;
        $address->lastname = (string) $customer->lastname;
        $address->address1 = $location['address'];
        $address->postcode = $location['postcode'];
        $address->city = $location['city'];
        $address->phone = '+12025550123';
        $address->id_country = (int) $idCountry;
        $address->id_state = $this->firstStateId($idCountry);
        if (!$address->add()) {
            throw new RuntimeException('Demo address could not be created for ' . $isoCode . '.');
        }

        return $address;
    }

    private function productId($type)
    {
        if (isset($this->productCache[$type])) {
            return $this->productCache[$type];
        }

        $studioId = $this->manufacturerId('Studio Design');
        $clothesId = $this->categoryId(['Clothes']);
        $accessoriesId = $this->categoryId(['Accessories']);
        $artId = $this->categoryId(['Art']);
        $conditions = [];

        if ($type === 'clothing' && $clothesId > 0) {
            $conditions[] = $this->categoryExistsSql($clothesId);
        } elseif ($type === 'studio_accessory') {
            if ($studioId > 0) {
                $conditions[] = 'p.`id_manufacturer` = ' . $studioId;
            }
            if ($accessoriesId > 0) {
                $conditions[] = $this->categoryExistsSql($accessoriesId);
            }
        } elseif ($type === 'non_studio_accessory') {
            if ($studioId > 0) {
                $conditions[] = 'p.`id_manufacturer` <> ' . $studioId;
            }
            if ($accessoriesId > 0) {
                $conditions[] = $this->categoryExistsSql($accessoriesId);
            }
        } elseif ($type === 'art') {
            if ($artId > 0) {
                $conditions[] = $this->categoryExistsSql($artId);
            }
            if ($studioId > 0) {
                $conditions[] = 'p.`id_manufacturer` <> ' . $studioId;
            }
            if ($accessoriesId > 0) {
                $conditions[] = 'NOT ' . $this->categoryExistsSql($accessoriesId);
            }
        }

        $sql = 'SELECT p.`id_product` FROM `' . _DB_PREFIX_ . 'product` p'
            . ' INNER JOIN `' . _DB_PREFIX_ . 'product_shop` ps ON ps.`id_product` = p.`id_product`'
            . ' AND ps.`id_shop` = ' . $this->idShop
            . ' WHERE ps.`active` = 1 AND ps.`available_for_order` = 1 AND p.`is_virtual` = 0'
            . ($conditions ? ' AND ' . implode(' AND ', $conditions) : '')
            . ' ORDER BY ps.`price` ASC, p.`id_product` ASC';
        $idProduct = (int) Db::getInstance()->getValue($sql);

        if ($idProduct <= 0) {
            $idProduct = (int) Db::getInstance()->getValue(
                'SELECT p.`id_product` FROM `' . _DB_PREFIX_ . 'product` p'
                . ' INNER JOIN `' . _DB_PREFIX_ . 'product_shop` ps ON ps.`id_product` = p.`id_product`'
                . ' AND ps.`id_shop` = ' . $this->idShop
                . ' WHERE ps.`active` = 1 AND ps.`available_for_order` = 1 AND p.`is_virtual` = 0'
                . ' ORDER BY ps.`price` ASC, p.`id_product` ASC'
            );
            $this->log('Product fallback used for scenario product type ' . $type . '.', true);
        }
        if ($idProduct <= 0) {
            throw new RuntimeException('No active physical product is available.');
        }

        $this->productCache[$type] = $idProduct;

        return $idProduct;
    }

    private function categoryExistsSql($idCategory)
    {
        return 'EXISTS (SELECT 1 FROM `' . _DB_PREFIX_ . 'category_product` cp'
            . ' WHERE cp.`id_product` = p.`id_product` AND cp.`id_category` = ' . (int) $idCategory . ')';
    }

    private function categoryId(array $names)
    {
        foreach ($names as $name) {
            $id = (int) Db::getInstance()->getValue(
                'SELECT `id_category` FROM `' . _DB_PREFIX_ . 'category_lang`'
                . ' WHERE LOWER(`name`) = LOWER(\'' . pSQL($name) . '\')'
                . ' AND `id_shop` = ' . $this->idShop
                . ' ORDER BY (`id_lang` = ' . $this->idLang . ') DESC, `id_category` ASC'
            );
            if ($id > 0) {
                return $id;
            }
        }

        return 0;
    }

    private function manufacturerId($name)
    {
        return (int) Db::getInstance()->getValue(
            'SELECT `id_manufacturer` FROM `' . _DB_PREFIX_ . 'manufacturer`'
            . ' WHERE LOWER(`name`) = LOWER(\'' . pSQL($name) . '\') AND `active` = 1'
        );
    }

    private function countryId($isoCode)
    {
        $id = (int) Country::getByIso($isoCode, true);
        if ($id <= 0) {
            throw new RuntimeException('Required active country is missing: ' . $isoCode);
        }

        return $id;
    }

    private function currencyId($isoCode)
    {
        $id = (int) Currency::getIdByIsoCode($isoCode, $this->idShop);
        $currency = new Currency($id);
        if ($id <= 0 || !Validate::isLoadedObject($currency) || !(bool) $currency->active) {
            throw new RuntimeException('Required active currency is missing: ' . $isoCode);
        }

        return $id;
    }

    private function carrierId($idCountry)
    {
        $idZone = (int) Country::getIdZone((int) $idCountry);
        $carriers = Carrier::getCarriers($this->idLang, true, false, $idZone, null, Carrier::ALL_CARRIERS);
        if (!empty($carriers[0]['id_carrier'])) {
            return (int) $carriers[0]['id_carrier'];
        }

        $id = (int) Configuration::get('PS_CARRIER_DEFAULT');
        if ($id <= 0) {
            throw new RuntimeException('No active carrier is available for the selected country.');
        }

        return $id;
    }

    private function firstStateId($idCountry)
    {
        return (int) Db::getInstance()->getValue(
            'SELECT `id_state` FROM `' . _DB_PREFIX_ . 'state`'
            . ' WHERE `id_country` = ' . (int) $idCountry . ' AND `active` = 1 ORDER BY `id_state` ASC'
        );
    }

    private function outcomeStateId($outcome)
    {
        $map = [
            'processing' => ['config' => 'PS_OS_PREPARATION', 'name' => 'Processing in progress'],
            'shipped' => ['config' => 'PS_OS_SHIPPING', 'name' => 'Shipped'],
            'delivered' => ['config' => 'PS_OS_DELIVERED', 'name' => 'Delivered'],
            'canceled' => ['config' => 'PS_OS_CANCELED', 'name' => 'Canceled'],
            'refunded' => ['config' => 'PS_OS_REFUND', 'name' => 'Refunded'],
        ];
        if (!isset($map[$outcome])) {
            throw new InvalidArgumentException('Unsupported outcome: ' . $outcome);
        }

        $id = (int) Configuration::get($map[$outcome]['config']);
        if ($id <= 0) {
            $id = (int) Db::getInstance()->getValue(
                'SELECT osl.`id_order_state` FROM `' . _DB_PREFIX_ . 'order_state_lang` osl'
                . ' WHERE LOWER(osl.`name`) = LOWER(\'' . pSQL($map[$outcome]['name']) . '\')'
                . ' ORDER BY (osl.`id_lang` = ' . $this->idLang . ') DESC'
            );
        }
        if ($id <= 0) {
            throw new RuntimeException('Order state is missing for outcome: ' . $outcome);
        }

        return $id;
    }

    private function changeOrderState($idOrder, $idOrderState)
    {
        $order = new Order((int) $idOrder);
        if (!Validate::isLoadedObject($order) || (int) $order->current_state === (int) $idOrderState) {
            return;
        }

        $history = new OrderHistory();
        $history->id_order = (int) $order->id;
        $history->id_employee = 0;
        $history->changeIdOrderState((int) $idOrderState, $order);
        $history->id_order_state = (int) $idOrderState;
        if (!$history->add(false)) {
            throw new RuntimeException('Order status history could not be saved.');
        }
    }

    private function setOrderDate($idOrder, $idCart, $date)
    {
        $timestamp = strtotime((string) $date);
        if ($timestamp === false || $timestamp > time()) {
            return;
        }

        $sqlDate = date('Y-m-d H:i:s', $timestamp);
        Db::getInstance()->update('orders', ['date_add' => pSQL($sqlDate)], '`id_order` = ' . (int) $idOrder);
        Db::getInstance()->update('cart', ['date_add' => pSQL($sqlDate)], '`id_cart` = ' . (int) $idCart);
        Db::getInstance()->update('order_history', ['date_add' => pSQL($sqlDate)], '`id_order` = ' . (int) $idOrder);
    }

    private function findOrderByMarker($marker)
    {
        return (int) Db::getInstance()->getValue(
            'SELECT `id_order` FROM `' . _DB_PREFIX_ . 'message`'
            . ' WHERE `message` LIKE \'%' . pSQL('[demo-order-generator:' . $marker . ']') . '%\''
            . ' ORDER BY `id_message` DESC'
        );
    }

    private function ensureMarkerMessage($idOrder, $idCart, $idCustomer, $marker, $messageText)
    {
        if ($this->findOrderByMarker($marker) > 0) {
            return;
        }

        $message = new Message();
        $message->id_order = (int) $idOrder;
        $message->id_cart = (int) $idCart;
        $message->id_customer = (int) $idCustomer;
        $message->id_employee = 0;
        $message->private = 1;
        $message->message = (string) $messageText;
        if (!$message->add()) {
            throw new RuntimeException('Recovered order marker could not be saved.');
        }
    }

    private function loadPaymentModule()
    {
        foreach (['ps_wirepayment', 'ps_checkpayment', 'ps_cashondelivery'] as $name) {
            $module = Module::getInstanceByName($name);
            if ($module instanceof PaymentModule && (bool) $module->active) {
                return $module;
            }
        }

        throw new RuntimeException('Install and enable ps_wirepayment, ps_checkpayment or ps_cashondelivery.');
    }

    private function uniqueCustomerEmail($entryId)
    {
        return 'new.' . strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $entryId)) . '@example.test';
    }

    private function returningCustomerEmail($entryId)
    {
        $index = hexdec(substr(sha1((string) $entryId), 0, 7)) % count($this->returningCustomerProfiles);

        return $this->returningCustomerProfiles[$index]['email'];
    }

    private function returningCustomerEmailByIndex($index)
    {
        $index = (int) $index % count($this->returningCustomerProfiles);

        return $this->returningCustomerProfiles[$index]['email'];
    }

    private function returningCustomerProfile($email)
    {
        foreach ($this->returningCustomerProfiles as $profile) {
            if ($profile['email'] === $email) {
                return $profile;
            }
        }

        throw new InvalidArgumentException('Unknown returning demo customer email: ' . $email);
    }

    private function safeLabel(array $scenario)
    {
        return $scenario['country'] . '/' . $scenario['currency'] . '/' . $scenario['product'];
    }

    private function pickWeighted(array $weights)
    {
        $total = array_sum($weights);
        $pick = random_int(1, $total);
        foreach ($weights as $value => $weight) {
            $pick -= $weight;
            if ($pick <= 0) {
                return $value;
            }
        }

        return key($weights);
    }

    private function ensureStateDirectory()
    {
        if (!is_dir($this->stateDir) && !mkdir($this->stateDir, 0755, true) && !is_dir($this->stateDir)) {
            throw new RuntimeException('Cannot create state directory: ' . $this->stateDir);
        }
        if (!is_writable($this->stateDir)) {
            throw new RuntimeException('State directory is not writable: ' . $this->stateDir);
        }
    }

    private function loadState($file)
    {
        if (!is_file($file)) {
            return null;
        }
        $data = json_decode((string) file_get_contents($file), true);
        if (!is_array($data) || !isset($data['orders']) || !is_array($data['orders'])) {
            throw new RuntimeException('Invalid state file: ' . $file);
        }

        return $data;
    }

    private function saveState($file, array $state)
    {
        $temporary = $file . '.tmp';
        $json = json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if ($json === false || file_put_contents($temporary, $json . PHP_EOL, LOCK_EX) === false) {
            throw new RuntimeException('Could not write state file: ' . $file);
        }
        if (!rename($temporary, $file)) {
            throw new RuntimeException('Could not replace state file: ' . $file);
        }
    }

    private function printStateSummary(array $state, $file)
    {
        $done = 0;
        $failed = 0;
        $next = null;
        foreach ($state['orders'] as $entry) {
            if (!empty($entry['done'])) {
                ++$done;
            } elseif (!empty($entry['error'])) {
                ++$failed;
            }
            if (empty($entry['done']) && ($next === null || $entry['at'] < $next)) {
                $next = $entry['at'];
            }
        }

        echo sprintf(
            "Demo orders %s: %d/%d created, %d with last-attempt errors%s.\nState: %s\n",
            $state['date'],
            $done,
            (int) $state['target'],
            $failed,
            $next ? ', next slot ' . $next : '',
            $file
        );
    }

    private function log($message, $error = false)
    {
        $line = '[' . date('Y-m-d H:i:s') . '] ' . $message . PHP_EOL;
        fwrite($error ? STDERR : STDOUT, $line);
    }
}

function demoGeneratorUsage()
{
    echo <<<'HELP'
Demo order generator for PrestaShop

Daily cron mode:
  php demo_order_generator.php
  php demo_order_generator.php --count=30 --max-per-run=3

Inspection and testing:
  php demo_order_generator.php --dry-run --count=30
  php demo_order_generator.php --status
  php demo_order_generator.php --force=1 --scenario=high_value --outcome=paid

Options:
  --count=N          Exact daily target. Default: random 26..34.
  --min=N            Minimum random daily target.
  --max=N            Maximum random daily target.
  --start-hour=N     First scheduling hour, 0..23. Default: 7.
  --end-hour=N       Exclusive end hour, 1..24. Default: 23.
  --max-per-run=N    Catch-up limit for one cron call. Default: 3.
  --date=YYYY-MM-DD  Plan date. Default: today.
  --scenario=NAME    Force one scenario for dry-run/force/new daily plan.
  --outcome=NAME     Force one outcome for dry-run/force/new daily plan.
  --coupon-use-rate=N Chance (0..100%) that a repeat paid order uses an emailed
                     coupon from set_next_order_discount. Default: 35.
  --force=N          Create N orders immediately, outside the daily plan.
  --dry-run          Print a generated plan without writing state or orders.
  --status           Print today's saved-plan status without creating orders.
  --verbose          Reserved for additional diagnostics.
  --help             Show this help.

Scenarios:
  first_order, premium_order, high_value, spend_booster, clothing,
  studio_design, france_us, usd_market, core_collection, fallback

Outcomes:
  paid, processing, shipped, delivered, awaiting_payment, canceled, refunded
HELP;
    echo PHP_EOL;
}

try {
    $raw = getopt('', [
        'help', 'count:', 'min:', 'max:', 'start-hour:', 'end-hour:',
        'max-per-run:', 'date:', 'scenario:', 'outcome:', 'coupon-use-rate:',
        'force:', 'dry-run', 'status', 'verbose',
    ]);
    if (isset($raw['help'])) {
        demoGeneratorUsage();
        exit(0);
    }

    $options = [
        'count' => isset($raw['count']) ? (int) $raw['count'] : null,
        'min' => isset($raw['min']) ? (int) $raw['min'] : DemoOrderGenerator::DEFAULT_MIN_ORDERS,
        'max' => isset($raw['max']) ? (int) $raw['max'] : DemoOrderGenerator::DEFAULT_MAX_ORDERS,
        'start_hour' => isset($raw['start-hour']) ? (int) $raw['start-hour'] : DemoOrderGenerator::DEFAULT_START_HOUR,
        'end_hour' => isset($raw['end-hour']) ? (int) $raw['end-hour'] : DemoOrderGenerator::DEFAULT_END_HOUR,
        'max_per_run' => isset($raw['max-per-run']) ? (int) $raw['max-per-run'] : DemoOrderGenerator::DEFAULT_MAX_PER_RUN,
        'date' => isset($raw['date']) ? (string) $raw['date'] : date('Y-m-d'),
        'scenario' => isset($raw['scenario']) ? (string) $raw['scenario'] : null,
        'outcome' => isset($raw['outcome']) ? (string) $raw['outcome'] : null,
        'coupon_use_rate' => isset($raw['coupon-use-rate'])
            ? (int) $raw['coupon-use-rate']
            : DemoOrderGenerator::DEFAULT_COUPON_USE_RATE,
        'dry_run' => isset($raw['dry-run']),
        'status' => isset($raw['status']),
    ];

    if ($options['count'] !== null && ($options['count'] < 1 || $options['count'] > 500)) {
        throw new InvalidArgumentException('--count must be between 1 and 500.');
    }
    if ($options['min'] < 1 || $options['max'] < $options['min'] || $options['max'] > 500) {
        throw new InvalidArgumentException('Use a valid --min/--max range between 1 and 500.');
    }
    if ($options['start_hour'] < 0 || $options['start_hour'] > 23
        || $options['end_hour'] < 1 || $options['end_hour'] > 24
        || $options['end_hour'] <= $options['start_hour']) {
        throw new InvalidArgumentException('Use 0..23 for --start-hour and a later 1..24 for --end-hour.');
    }
    if ($options['max_per_run'] < 1 || $options['max_per_run'] > 50) {
        throw new InvalidArgumentException('--max-per-run must be between 1 and 50.');
    }
    if ($options['coupon_use_rate'] < 0 || $options['coupon_use_rate'] > 100) {
        throw new InvalidArgumentException('--coupon-use-rate must be between 0 and 100.');
    }
    $dateObject = DateTime::createFromFormat('!Y-m-d', $options['date']);
    if (!$dateObject || $dateObject->format('Y-m-d') !== $options['date']) {
        throw new InvalidArgumentException('--date must use YYYY-MM-DD.');
    }

    $generator = new DemoOrderGenerator(__DIR__, isset($raw['verbose']));
    if ($options['scenario'] !== null && !in_array($options['scenario'], $generator->scenarioNames(), true)) {
        throw new InvalidArgumentException('Unknown --scenario. Run with --help to list valid values.');
    }
    $validOutcomes = ['paid', 'processing', 'shipped', 'delivered', 'awaiting_payment', 'canceled', 'refunded'];
    if ($options['outcome'] !== null && !in_array($options['outcome'], $validOutcomes, true)) {
        throw new InvalidArgumentException('Unknown --outcome. Run with --help to list valid values.');
    }

    if (isset($raw['force'])) {
        $generator->runForced(
            (int) $raw['force'],
            $options['scenario'],
            $options['outcome'],
            $options['coupon_use_rate'],
            $options['dry_run']
        );
    } elseif ($options['dry_run']) {
        $generator->previewPlan($options);
    } else {
        $generator->runScheduled($options);
    }
} catch (Throwable $e) {
    fwrite(STDERR, '[demo-order-generator] ' . $e->getMessage() . PHP_EOL);
    if (isset($raw['verbose'])) {
        fwrite(STDERR, $e->getTraceAsString() . PHP_EOL);
    }
    exit(1);
}
