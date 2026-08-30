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
namespace Setecom\NextOrderDiscount\Coupon;

use CartRule;
use Configuration;
use Db;
use Language;
use Shop;
use Validate;
use Exception;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Thin wrapper around the native PrestaShop CartRule API.
 *
 * Encapsulates the creation of a personal, single-use, time-limited voucher and
 * its later deactivation, so the rest of the module never touches the CartRule
 * ObjectModel directly.
 */
class CartRuleAdapter
{
    /**
     * Creates a CartRule from normalized parameters.
     *
     * Expected keys:
     *  - code            (string) unique voucher code
     *  - id_customer     (int)    owner of the personal voucher
     *  - id_shop         (int)    shop the voucher belongs to (multishop)
     *  - id_currency     (int)    currency for amount/minimum reductions
     *  - discount_type   (string) 'percent' or 'amount'
     *  - discount_value  (float)  reduction value
     *  - minimum_amount  (float)  minimum next-order total (0 disables)
     *  - date_from       (string) 'Y-m-d H:i:s'
     *  - date_to         (string) 'Y-m-d H:i:s'
     *  - quantity        (int)    total uses (default 1)
     *  - quantity_per_user (int)  uses per customer (default 1)
     *  - name            (string) optional voucher name (defaults to the code)
     *  - description     (string) optional back-office voucher description
     *
     * @param array $params
     *
     * @return int the new id_cart_rule, or 0 on failure
     */
    public function create(array $params)
    {
        $code = isset($params['code']) ? (string) $params['code'] : '';
        $idCustomer = isset($params['id_customer']) ? (int) $params['id_customer'] : 0;
        if ($code === '' || $idCustomer <= 0) {
            return 0;
        }

        $idCurrency = isset($params['id_currency']) ? (int) $params['id_currency'] : 0;
        if ($idCurrency <= 0) {
            $idCurrency = (int) Configuration::get('PS_CURRENCY_DEFAULT');
        }

        $name = isset($params['name']) ? trim((string) $params['name']) : '';
        if ($name === '') {
            $name = $code;
        }

        $cartRule = new CartRule();
        $cartRule->code = $code;
        $cartRule->id_customer = $idCustomer;
        $cartRule->name = $this->buildLocalizedName($name);
        // Description is a single (non-localized) back-office note on the voucher.
        $cartRule->description = isset($params['description']) ? (string) $params['description'] : '';
        $cartRule->date_from = isset($params['date_from']) ? (string) $params['date_from'] : date('Y-m-d H:i:s');
        $cartRule->date_to = isset($params['date_to']) ? (string) $params['date_to'] : date('Y-m-d H:i:s');
        $cartRule->quantity = isset($params['quantity']) ? max(1, (int) $params['quantity']) : 1;
        $cartRule->quantity_per_user = isset($params['quantity_per_user']) ? max(1, (int) $params['quantity_per_user']) : 1;
        $cartRule->priority = 1;
        $cartRule->partial_use = 1;
        $cartRule->highlight = 1;
        $cartRule->active = 1;
        $cartRule->free_shipping = 0;
        $cartRule->reduction_product = 0;
        $cartRule->reduction_exclude_special = 0;

        $this->applyReduction($cartRule, $params, $idCurrency);
        $this->applyMinimumAmount($cartRule, $params, $idCurrency);

        $idShop = isset($params['id_shop']) ? (int) $params['id_shop'] : 0;
        if ($idShop > 0) {
            $cartRule->id_shop_list = [$idShop];
        }

        try {
            if (!$cartRule->add()) {
                return 0;
            }
        } catch (Exception $e) {
            return 0;
        }

        $idCartRule = (int) $cartRule->id;
        if ($idCartRule > 0 && $idShop > 0) {
            $this->ensureShopAssociation($idCartRule, $idShop);
        }

        return $idCartRule;
    }

    /**
     * Deactivates a voucher (active = 0) without deleting it, preserving audit.
     *
     * @param int $idCartRule
     *
     * @return bool
     */
    public function deactivate($idCartRule)
    {
        $idCartRule = (int) $idCartRule;
        if ($idCartRule <= 0) {
            return false;
        }

        $cartRule = new CartRule($idCartRule);
        if (!Validate::isLoadedObject($cartRule)) {
            return false;
        }

        $cartRule->active = 0;

        try {
            return (bool) $cartRule->update();
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * @param CartRule $cartRule
     * @param array    $params
     * @param int      $idCurrency
     *
     * @return void
     */
    private function applyReduction(CartRule $cartRule, array $params, $idCurrency)
    {
        $type = isset($params['discount_type']) ? (string) $params['discount_type'] : 'percent';
        $value = isset($params['discount_value'])
            ? (float) str_replace(',', '.', (string) $params['discount_value'])
            : 0.0;
        $value = max(0.0, $value);

        if ($type === 'free_shipping') {
            $cartRule->free_shipping = 1;
            $cartRule->reduction_percent = 0;
            $cartRule->reduction_amount = 0;
            $cartRule->reduction_currency = 0;
            $cartRule->reduction_tax = 0;

            return;
        }

        if ($type === 'amount') {
            $cartRule->reduction_amount = $value;
            $cartRule->reduction_tax = isset($params['reduction_tax']) ? (int) (bool) $params['reduction_tax'] : 1;
            $cartRule->reduction_currency = (int) $idCurrency;
            $cartRule->reduction_percent = 0;

            return;
        }

        $cartRule->reduction_percent = min(100.0, $value);
        $cartRule->reduction_amount = 0;
        $cartRule->reduction_currency = 0;
        $cartRule->reduction_tax = 0;
    }

    /**
     * @param CartRule $cartRule
     * @param array    $params
     * @param int      $idCurrency
     *
     * @return void
     */
    private function applyMinimumAmount(CartRule $cartRule, array $params, $idCurrency)
    {
        $minimum = isset($params['minimum_amount'])
            ? (float) str_replace(',', '.', (string) $params['minimum_amount'])
            : 0.0;

        if ($minimum <= 0.0) {
            $cartRule->minimum_amount = 0;
            $cartRule->minimum_amount_tax = 0;
            $cartRule->minimum_amount_currency = 0;
            $cartRule->minimum_amount_shipping = 0;

            return;
        }

        $cartRule->minimum_amount = $minimum;
        $cartRule->minimum_amount_tax = isset($params['minimum_amount_tax']) ? (int) (bool) $params['minimum_amount_tax'] : 1;
        $cartRule->minimum_amount_currency = (int) $idCurrency;
        $cartRule->minimum_amount_shipping = 0;
    }

    /**
     * Builds a per-language name array so every cart_rule_lang row is filled.
     *
     * @param string $name
     *
     * @return array
     */
    private function buildLocalizedName($name)
    {
        $localized = [];
        foreach (Language::getLanguages(false) as $language) {
            $localized[(int) $language['id_lang']] = $name;
        }

        return $localized;
    }

    /**
     * Guarantees the voucher is associated with the target shop, regardless of
     * the ambient shop context in which it was created.
     *
     * @param int $idCartRule
     * @param int $idShop
     *
     * @return void
     */
    private function ensureShopAssociation($idCartRule, $idShop)
    {
        if (!Shop::isFeatureActive()) {
            return;
        }

        Db::getInstance()->execute(
            'INSERT IGNORE INTO `' . _DB_PREFIX_ . 'cart_rule_shop` (`id_cart_rule`, `id_shop`)'
            . ' VALUES (' . (int) $idCartRule . ', ' . (int) $idShop . ')'
        );
    }
}
