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
namespace Setecom\NextOrderDiscount\Mail;

use Language;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Centralizes everything the coupon mailer needs to know about the module's
 * email templates: where they live, their base name, which language is actually
 * used, and the language-specific strings that live outside the template body
 * (the subject line and the free-shipping label).
 *
 * The language resolution mirrors PrestaShop's own Mail::send fallback order
 * (customer language, then the shop's default language, then English) so the
 * subject the mailer builds always matches the language of the template body
 * the core mailer ends up selecting.
 */
class MailTemplateResolver
{
    /**
     * Base name of the template files (without the .html / .txt extension).
     */
    public const TEMPLATE_NAME = 'next_order_discount';

    /**
     * Technical name of the module, used to build the mails/ path.
     */
    public const MODULE_NAME = 'set_next_order_discount';

    /**
     * Fallback ISO code, guaranteed to be shipped with the module.
     */
    private const FALLBACK_ISO = 'en';

    /**
     * Subject line per shipped language, keyed by ISO code.
     */
    private const SUBJECTS = [
        'en' => 'A discount for your next order',
        'ru' => 'Скидка на ваш следующий заказ',
    ];

    /**
     * Free-shipping wording per shipped language, keyed by ISO code.
     */
    private const FREE_SHIPPING_LABELS = [
        'en' => 'Free shipping',
        'ru' => 'Бесплатная доставка',
    ];

    /**
     * Absolute filesystem path to the module's mails/ directory, with a
     * trailing slash, as expected by Mail::send's $templatePath argument.
     *
     * @return string
     */
    public function getTemplatePath()
    {
        return rtrim(_PS_MODULE_DIR_, '/') . '/' . self::MODULE_NAME . '/mails/';
    }

    /**
     * @return string the template base name passed to Mail::send
     */
    public function getTemplateName()
    {
        return self::TEMPLATE_NAME;
    }

    /**
     * Resolves the ISO code whose template files will actually be used for a
     * given customer language and shop, following the same order as the core
     * mailer: customer language, shop default language, then English. The first
     * candidate that ships both a .html and a .txt file wins; English is the
     * guaranteed terminal fallback.
     *
     * @param int $idLang the customer's language id
     * @param int $idShop the shop the coupon belongs to
     *
     * @return string an ISO code that has complete template files
     */
    public function resolveIso($idLang, $idShop)
    {
        foreach ($this->buildIsoCandidates((int) $idLang, (int) $idShop) as $iso) {
            if ($this->templateExists($iso)) {
                return $iso;
            }
        }

        return self::FALLBACK_ISO;
    }

    /**
     * @param string $iso a resolved ISO code
     *
     * @return string the localized subject line
     */
    public function getSubject($iso)
    {
        $iso = (string) $iso;

        return isset(self::SUBJECTS[$iso]) ? self::SUBJECTS[$iso] : self::SUBJECTS[self::FALLBACK_ISO];
    }

    /**
     * @param string $iso a resolved ISO code
     *
     * @return string the localized "free shipping" label
     */
    public function getFreeShippingLabel($iso)
    {
        $iso = (string) $iso;

        return isset(self::FREE_SHIPPING_LABELS[$iso])
            ? self::FREE_SHIPPING_LABELS[$iso]
            : self::FREE_SHIPPING_LABELS[self::FALLBACK_ISO];
    }

    /**
     * Builds the ordered, de-duplicated list of ISO candidates to probe.
     *
     * @param int $idLang
     * @param int $idShop
     *
     * @return string[]
     */
    private function buildIsoCandidates($idLang, $idShop)
    {
        $candidates = [];

        $customerIso = $idLang > 0 ? (string) \Language::getIsoById($idLang) : '';
        if ($customerIso !== '') {
            $candidates[] = $customerIso;
        }

        $defaultLangId = (int) \Configuration::get('PS_LANG_DEFAULT', null, null, $idShop > 0 ? $idShop : null);
        $defaultIso = $defaultLangId > 0 ? (string) \Language::getIsoById($defaultLangId) : '';
        if ($defaultIso !== '') {
            $candidates[] = $defaultIso;
        }

        $candidates[] = self::FALLBACK_ISO;

        return array_values(array_unique($candidates));
    }

    /**
     * A template is only usable when both its plain-text and HTML parts exist,
     * matching the completeness check the core mailer performs.
     *
     * @param string $iso
     *
     * @return bool
     */
    private function templateExists($iso)
    {
        $base = $this->getTemplatePath() . $iso . '/' . self::TEMPLATE_NAME;

        return is_file($base . '.html') && is_file($base . '.txt');
    }
}
