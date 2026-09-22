<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Creates one real PrestaShop order for safely exercising demo rules.
 */
class SetDemoOrderCreator
{
    const MAX_QUANTITY = 100;
    const MAX_TOTAL = 1000000;

    private $context;
    private $shop;
    private $idShop;
    private $idShopGroup;

    public function __construct(Context $context)
    {
        $this->context = $context;
        $this->shop = $context->shop;
        $this->idShop = (int) $context->shop->id;
        $this->idShopGroup = (int) $context->shop->id_shop_group;
    }

    /**
     * @return array
     */
    public function create(array $input)
    {
        $data = $this->validate($input);
        $customer = $this->findOrCreateCustomer($data);
        $this->assignCustomerGroup($customer, $data['id_group']);
        $address = $this->findOrCreateAddress($customer, $data);
        $paymentModule = $this->loadPaymentModule();
        $currency = new Currency($data['id_currency'], $data['id_lang'], $this->idShop);
        $country = new Country($data['id_country'], $data['id_lang']);

        $this->context->customer = $customer;
        $this->context->currency = $currency;
        $this->context->country = $country;
        $this->context->language = new Language($data['id_lang']);

        $cart = $this->createCart($customer, $address, $data);
        $this->context->cart = $cart;

        $idAttribute = (int) Product::getDefaultAttribute($data['id_product']);
        $stockBefore = (int) StockAvailable::getQuantityAvailableByProduct(
            $data['id_product'],
            $idAttribute,
            $this->idShop
        );
        $temporaryRule = null;

        try {
            $this->fillCartToTarget($cart, $data['id_product'], $idAttribute, (int) $address->id, $data['target_total']);
            $temporaryRule = $this->adjustCartToExactTotal($cart, $customer, $data['target_total'], $data['id_currency'], $data['id_lang']);
            $total = (float) $cart->getOrderTotal(true, Cart::BOTH);
            if (abs($total - $data['target_total']) > 0.02) {
                throw new RuntimeException(sprintf('Could not calculate the requested total. Cart total is %.2f.', $total));
            }

            $marker = 'SDOG-' . strtoupper(substr(sha1(uniqid('', true)), 0, 12));
            $message = '[set-demo-order-generator:' . $marker . '] exact_total=' . number_format($total, 2, '.', '');
            $validated = false;
            $validationError = null;
            try {
                $validated = $paymentModule->validateOrder(
                    (int) $cart->id,
                    $data['id_order_state'],
                    $total,
                    'Demo order generator',
                    $message,
                    ['transaction_id' => $marker],
                    $data['id_currency'],
                    false,
                    (string) $customer->secure_key,
                    $this->shop
                );
            } catch (Throwable $e) {
                // A payment module may throw after the order has already been
                // persisted (for example while sending its own email). Never
                // invite the user to retry and accidentally create a duplicate.
                $validationError = $e;
            }

            $idOrder = (int) Order::getIdByCartId((int) $cart->id);
            if ($idOrder <= 0) {
                $idOrder = (int) $paymentModule->currentOrder;
            }
            if ($idOrder <= 0) {
                if ($validationError !== null) {
                    throw $validationError;
                }
                throw new RuntimeException('PrestaShop did not finish creating the order.');
            }
            if (!$validated && $validationError === null) {
                throw new RuntimeException('PrestaShop created an incomplete order.');
            }

            $order = new Order($idOrder);
            $coupon = $this->findGeneratedCoupon($idOrder);
            $usageSaved = $this->saveUsage($order, $customer, $currency, $coupon);

            return [
                'order_id' => $idOrder,
                'customer_id' => (int) $customer->id,
                'email' => (string) $customer->email,
                'total' => (float) $order->total_paid,
                'currency' => (string) $currency->iso_code,
                'coupon_code' => $coupon ? (string) $coupon['coupon_code'] : '',
                'coupon_status' => $coupon ? (string) $coupon['status'] : '',
                'usage_saved' => $usageSaved,
            ];
        } finally {
            if ($temporaryRule instanceof CartRule && Validate::isLoadedObject($temporaryRule)) {
                $temporaryRule->delete();
            }
            $stockAfter = (int) StockAvailable::getQuantityAvailableByProduct(
                $data['id_product'],
                $idAttribute,
                $this->idShop
            );
            if ($stockAfter < $stockBefore) {
                StockAvailable::updateQuantity(
                    $data['id_product'],
                    $idAttribute,
                    $stockBefore - $stockAfter,
                    $this->idShop
                );
            }
        }
    }

    private function validate(array $input)
    {
        $email = isset($input['email']) ? trim((string) $input['email']) : '';
        $firstname = isset($input['firstname']) ? trim((string) $input['firstname']) : '';
        $lastname = isset($input['lastname']) ? trim((string) $input['lastname']) : '';
        $targetTotal = isset($input['target_total']) && is_numeric($input['target_total'])
            ? (float) $input['target_total']
            : 0.0;

        if (!Validate::isEmail($email)) {
            throw new InvalidArgumentException('Enter a valid customer email address.');
        }
        if (!Validate::isCustomerName($firstname) || $firstname === '') {
            throw new InvalidArgumentException('Enter a valid first name.');
        }
        if (!Validate::isCustomerName($lastname) || $lastname === '') {
            throw new InvalidArgumentException('Enter a valid last name.');
        }
        if ($targetTotal <= 0 || $targetTotal > self::MAX_TOTAL) {
            throw new InvalidArgumentException('Order total must be greater than 0 and no more than 1,000,000.');
        }

        $ids = [
            'id_product' => 'Product',
            'id_currency' => 'Currency',
            'id_country' => 'Country',
            'id_group' => 'Customer group',
            'id_lang' => 'Language',
            'id_order_state' => 'Order status',
        ];
        foreach ($ids as $key => $label) {
            if (empty($input[$key]) || (int) $input[$key] <= 0) {
                throw new InvalidArgumentException($label . ' is required.');
            }
        }

        $product = new Product((int) $input['id_product'], false, (int) $input['id_lang'], $this->idShop);
        $currency = new Currency((int) $input['id_currency']);
        $country = new Country((int) $input['id_country']);
        $group = new Group((int) $input['id_group']);
        $language = new Language((int) $input['id_lang']);
        $state = new OrderState((int) $input['id_order_state']);
        if (!Validate::isLoadedObject($product)
            || !(bool) $product->active
            || !(bool) $product->available_for_order
            || (bool) $product->is_virtual) {
            throw new InvalidArgumentException('The selected product is not available for demo orders.');
        }
        foreach ([$currency, $country, $group, $language, $state] as $object) {
            if (!Validate::isLoadedObject($object)) {
                throw new InvalidArgumentException('One of the selected values no longer exists. Refresh the page and try again.');
            }
        }

        return [
            'email' => $email,
            'firstname' => $firstname,
            'lastname' => $lastname,
            'target_total' => Tools::ps_round($targetTotal, 2),
            'id_product' => (int) $input['id_product'],
            'id_currency' => (int) $input['id_currency'],
            'id_country' => (int) $input['id_country'],
            'id_group' => (int) $input['id_group'],
            'id_lang' => (int) $input['id_lang'],
            'id_order_state' => (int) $input['id_order_state'],
        ];
    }

    private function findOrCreateCustomer(array $data)
    {
        $idCustomer = (int) Customer::customerExists($data['email'], true, true);
        if ($idCustomer > 0) {
            $customer = new Customer($idCustomer);
            if (!Validate::isLoadedObject($customer)) {
                throw new RuntimeException('The existing customer could not be loaded.');
            }

            return $customer;
        }

        $customer = new Customer();
        $customer->firstname = $data['firstname'];
        $customer->lastname = $data['lastname'];
        $customer->email = $data['email'];
        $customer->passwd = Tools::hash(Tools::passwdGen(24));
        $customer->secure_key = md5(uniqid((string) random_int(1, PHP_INT_MAX), true));
        $customer->id_lang = $data['id_lang'];
        $customer->id_shop = $this->idShop;
        $customer->id_shop_group = $this->idShopGroup;
        $customer->id_default_group = $data['id_group'];
        $customer->active = 1;
        $customer->is_guest = 0;
        $customer->newsletter = 0;
        $customer->optin = 0;
        if (!$customer->add()) {
            throw new RuntimeException('The demo customer could not be created.');
        }

        return $customer;
    }

    private function assignCustomerGroup(Customer $customer, $idGroup)
    {
        $customer->id_default_group = (int) $idGroup;
        if (!$customer->update()) {
            throw new RuntimeException('The customer group could not be updated.');
        }
        $customer->updateGroup([(int) $idGroup]);
    }

    private function findOrCreateAddress(Customer $customer, array $data)
    {
        $alias = 'Demo generator ' . (int) $data['id_country'];
        $idAddress = (int) Db::getInstance()->getValue(
            'SELECT `id_address` FROM `' . _DB_PREFIX_ . 'address`'
            . ' WHERE `id_customer` = ' . (int) $customer->id
            . ' AND `id_country` = ' . (int) $data['id_country']
            . ' AND `alias` = "' . pSQL($alias) . '" AND `deleted` = 0'
            . ' ORDER BY `id_address` DESC'
        );
        if ($idAddress > 0) {
            return new Address($idAddress);
        }

        $country = new Country($data['id_country'], $data['id_lang']);
        $address = new Address();
        $address->id_customer = (int) $customer->id;
        $address->alias = $alias;
        $address->firstname = (string) $customer->firstname;
        $address->lastname = (string) $customer->lastname;
        $address->address1 = '1 Demo Street';
        $address->postcode = $this->buildPostcode($country);
        $address->city = 'Demo City';
        $address->phone = '+12025550123';
        $address->dni = !empty($country->need_identification_number) ? '12345678' : '';
        $address->id_country = (int) $country->id;
        $address->id_state = $this->firstStateId((int) $country->id);
        if (!$address->add()) {
            throw new RuntimeException('A delivery address could not be created for the selected country.');
        }

        return $address;
    }

    private function buildPostcode(Country $country)
    {
        if (empty($country->need_zip_code)) {
            return '';
        }
        $format = trim((string) $country->zip_code_format);
        if ($format === '') {
            return '10000';
        }

        $iso = strtoupper((string) $country->iso_code);
        $postcode = '';
        $isoOffset = 0;
        for ($i = 0, $length = strlen($format); $i < $length; ++$i) {
            $character = $format[$i];
            if ($character === 'N') {
                $postcode .= '1';
            } elseif ($character === 'L') {
                $postcode .= 'A';
            } elseif ($character === 'C') {
                $postcode .= isset($iso[$isoOffset]) ? $iso[$isoOffset++] : 'A';
            } else {
                $postcode .= $character;
            }
        }

        return $postcode;
    }

    private function firstStateId($idCountry)
    {
        return (int) Db::getInstance()->getValue(
            'SELECT `id_state` FROM `' . _DB_PREFIX_ . 'state`'
            . ' WHERE `id_country` = ' . (int) $idCountry . ' AND `active` = 1'
            . ' ORDER BY `id_state` ASC'
        );
    }

    private function createCart(Customer $customer, Address $address, array $data)
    {
        $cart = new Cart();
        $cart->id_shop = $this->idShop;
        $cart->id_shop_group = $this->idShopGroup;
        $cart->id_customer = (int) $customer->id;
        $cart->id_address_delivery = (int) $address->id;
        $cart->id_address_invoice = (int) $address->id;
        $cart->id_currency = $data['id_currency'];
        $cart->id_lang = $data['id_lang'];
        $cart->id_carrier = $this->carrierId($data['id_country'], $data['id_lang']);
        $cart->secure_key = (string) $customer->secure_key;
        $cart->recyclable = 0;
        $cart->gift = 0;
        if (!$cart->add()) {
            throw new RuntimeException('The demo cart could not be created.');
        }

        return $cart;
    }

    private function carrierId($idCountry, $idLang)
    {
        $idZone = (int) Country::getIdZone((int) $idCountry);
        $carriers = Carrier::getCarriers((int) $idLang, true, false, $idZone, null, Carrier::ALL_CARRIERS);
        if (!empty($carriers[0]['id_carrier'])) {
            return (int) $carriers[0]['id_carrier'];
        }

        $idCarrier = (int) Configuration::get('PS_CARRIER_DEFAULT');
        if ($idCarrier <= 0) {
            throw new RuntimeException('No active carrier is available for the selected country.');
        }

        return $idCarrier;
    }

    private function fillCartToTarget(Cart $cart, $idProduct, $idAttribute, $idAddress, $targetTotal)
    {
        $quantity = 0;
        do {
            $result = $cart->updateQty(
                1,
                (int) $idProduct,
                (int) $idAttribute,
                false,
                'up',
                (int) $idAddress,
                $this->shop,
                false,
                true
            );
            if ($result === false || (is_int($result) && $result < 0)) {
                throw new RuntimeException('The selected product could not be added to the cart.');
            }
            ++$quantity;
            $total = (float) $cart->getOrderTotal(true, Cart::BOTH);
            $shipping = (float) $cart->getOrderTotal(true, Cart::ONLY_SHIPPING);
            $products = (float) $cart->getOrderTotal(true, Cart::BOTH_WITHOUT_SHIPPING);
            $canAdjust = $total + 0.001 >= (float) $targetTotal
                && ((float) $targetTotal + 0.001 >= $shipping || $products + 0.001 >= (float) $targetTotal);
        } while (!$canAdjust && $quantity < self::MAX_QUANTITY);

        if (!$canAdjust) {
            throw new RuntimeException('The target total could not be reached with at most 100 product units.');
        }
    }

    private function adjustCartToExactTotal(Cart $cart, Customer $customer, $targetTotal, $idCurrency, $idLang)
    {
        $currentTotal = (float) $cart->getOrderTotal(true, Cart::BOTH);
        $shipping = (float) $cart->getOrderTotal(true, Cart::ONLY_SHIPPING);
        $useFreeShipping = (float) $targetTotal + 0.001 < $shipping;
        $adjustableTotal = $useFreeShipping ? $currentTotal - $shipping : $currentTotal;
        $difference = Tools::ps_round($adjustableTotal - (float) $targetTotal, 2);
        if ($difference <= 0) {
            return null;
        }

        $rule = new CartRule();
        $rule->name = [];
        foreach (Language::getLanguages(false) as $language) {
            $rule->name[(int) $language['id_lang']] = 'Demo order total adjustment';
        }
        if (empty($rule->name[(int) $idLang])) {
            $rule->name[(int) $idLang] = 'Demo order total adjustment';
        }
        $rule->id_customer = (int) $customer->id;
        $rule->date_from = date('Y-m-d H:i:s', strtotime('-1 minute'));
        $rule->date_to = date('Y-m-d H:i:s', strtotime('+1 hour'));
        $rule->description = 'Temporary rule created by set_demo_order_generator.';
        $rule->quantity = 1;
        $rule->quantity_per_user = 1;
        $rule->priority = 1;
        $rule->partial_use = 0;
        $rule->code = '';
        $rule->minimum_amount = 0;
        $rule->minimum_amount_tax = 1;
        $rule->minimum_amount_currency = (int) $idCurrency;
        $rule->minimum_amount_shipping = 1;
        $rule->country_restriction = 0;
        $rule->carrier_restriction = 0;
        $rule->group_restriction = 0;
        $rule->cart_rule_restriction = 0;
        $rule->product_restriction = 0;
        $rule->shop_restriction = 0;
        $rule->free_shipping = $useFreeShipping ? 1 : 0;
        $rule->reduction_percent = 0;
        $rule->reduction_amount = $difference;
        $rule->reduction_tax = 1;
        $rule->reduction_currency = (int) $idCurrency;
        $rule->reduction_product = 0;
        $rule->gift_product = 0;
        $rule->gift_product_attribute = 0;
        $rule->highlight = 0;
        $rule->active = 1;
        if (!$rule->add() || !$cart->addCartRule((int) $rule->id)) {
            if (Validate::isLoadedObject($rule)) {
                $rule->delete();
            }
            throw new RuntimeException('The exact-total adjustment could not be applied.');
        }

        return $rule;
    }

    private function loadPaymentModule()
    {
        foreach (['ps_wirepayment', 'ps_checkpayment', 'ps_cashondelivery'] as $name) {
            $module = Module::getInstanceByName($name);
            if ($module instanceof PaymentModule && (bool) $module->active) {
                return $module;
            }
        }

        throw new RuntimeException('Enable ps_wirepayment, ps_checkpayment or ps_cashondelivery before creating demo orders.');
    }

    private function findGeneratedCoupon($idOrder)
    {
        if (!Module::isInstalled('set_next_order_discount')) {
            return null;
        }

        $row = Db::getInstance()->getRow(
            'SELECT `coupon_code`, `status` FROM `' . _DB_PREFIX_ . 'snod_coupon_link`'
            . ' WHERE `id_shop` = ' . $this->idShop . ' AND `id_order_source` = ' . (int) $idOrder
            . ' ORDER BY `id_snod_coupon_link` DESC'
        );

        return is_array($row) && !empty($row) ? $row : null;
    }

    private function saveUsage(Order $order, Customer $customer, Currency $currency, $coupon)
    {
        try {
            $saved = Db::getInstance()->insert(
                set_demo_order_generator::USAGE_TABLE,
                [
                    'id_shop' => $this->idShop,
                    'id_employee' => Validate::isLoadedObject($this->context->employee)
                        ? (int) $this->context->employee->id
                        : 0,
                    'id_customer' => (int) $customer->id,
                    'id_order' => (int) $order->id,
                    'email' => pSQL((string) $customer->email),
                    'firstname' => pSQL((string) $customer->firstname),
                    'lastname' => pSQL((string) $customer->lastname),
                    'total_paid' => (float) $order->total_paid,
                    'currency_iso' => pSQL((string) $currency->iso_code),
                    'coupon_code' => $coupon ? pSQL((string) $coupon['coupon_code']) : null,
                    'coupon_status' => $coupon ? pSQL((string) $coupon['status']) : null,
                    'date_add' => date('Y-m-d H:i:s'),
                ],
                true,
                false,
                Db::INSERT_IGNORE
            );

            return (bool) $saved;
        } catch (Throwable $e) {
            PrestaShopLogger::addLog(
                'Demo Order Generator could not save usage history for order #' . (int) $order->id
                . ': ' . $e->getMessage(),
                2,
                null,
                'Order',
                (int) $order->id,
                true
            );

            return false;
        }
    }
}
