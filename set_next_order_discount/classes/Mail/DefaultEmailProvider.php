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
use Setecom\NextOrderDiscount\Repository\RuleEmailRepository;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Supplies the shipped default email content (subject + HTML) for a given email
 * type and language, read from the module's views/email_defaults/ bodies. It is
 * the source used to seed a rule's own editable email when the rule is created,
 * and the fallback the mailers use when a rule has no stored content for a
 * language.
 *
 * These default bodies live outside mails/ on purpose: the mails/ templates are
 * pure pass-through shells ({snod_body_html} / {snod_body_txt}) that carry no
 * content of their own, so the actual email always comes from the rule's own
 * settings (or, as a last resort, these shipped defaults).
 */
class DefaultEmailProvider
{
    public const MODULE_NAME = 'set_next_order_discount';

    private const FALLBACK_ISO = 'en';

    /**
     * Default body file base name per email type (under views/email_defaults/).
     */
    private const TEMPLATE_NAMES = [
        RuleEmailRepository::TYPE_COUPON => 'next_order_discount',
        RuleEmailRepository::TYPE_REMINDER_1 => 'reminder_next_order_discount',
        RuleEmailRepository::TYPE_REMINDER_2 => 'reminder_next_order_discount',
    ];

    /**
     * Default subject per email type, keyed by ISO code. English and French are
     * the shipped localized sets; every other language falls back to English.
     * This is the single source of truth for default subjects — the mailers read
     * their fallback subject from here.
     */
    private const SUBJECTS = [
        RuleEmailRepository::TYPE_COUPON => [
            'en' => 'A discount for your next order',
            'fr' => 'Une réduction pour votre prochaine commande',
        ],
        RuleEmailRepository::TYPE_REMINDER_1 => [
            'en' => 'Your discount is waiting — use it before it expires',
            'fr' => "Votre réduction vous attend — utilisez-la avant son expiration",
        ],
        RuleEmailRepository::TYPE_REMINDER_2 => [
            'en' => 'Your discount is waiting — use it before it expires',
            'fr' => "Votre réduction vous attend — utilisez-la avant son expiration",
        ],
    ];

    /**
     * Free-shipping wording per ISO code (single source of truth). Used when a
     * voucher's headline value is free shipping. English is the fallback.
     */
    private const FREE_SHIPPING_LABELS = [
        'en' => 'Free shipping',
        'fr' => 'Livraison gratuite',
    ];

    /**
     * Default subject and HTML for one email type in one language.
     *
     * @param string $emailType
     * @param int $idLang
     *
     * @return array ['subject' => string, 'html' => string]
     */
    public function getDefault($emailType, $idLang)
    {
        $iso = $this->isoOf((int) $idLang);

        return [
            'subject' => $this->getSubject($emailType, $iso),
            'html' => $this->getHtml($emailType, $iso),
        ];
    }

    /**
     * @param string $emailType
     * @param string $iso
     *
     * @return string
     */
    public function getSubject($emailType, $iso)
    {
        $emailType = (string) $emailType;
        $iso = (string) $iso;
        if (!isset(self::SUBJECTS[$emailType])) {
            return '';
        }
        $map = self::SUBJECTS[$emailType];

        return isset($map[$iso]) ? $map[$iso] : $map[self::FALLBACK_ISO];
    }

    /**
     * @param string $iso
     *
     * @return string the localized "free shipping" label (English fallback)
     */
    public function getFreeShippingLabel($iso)
    {
        $iso = (string) $iso;

        return isset(self::FREE_SHIPPING_LABELS[$iso])
            ? self::FREE_SHIPPING_LABELS[$iso]
            : self::FREE_SHIPPING_LABELS[self::FALLBACK_ISO];
    }

    /**
     * Resolves the ISO code used for the shipped localized strings (subject and
     * free-shipping label), following the core mailer order: customer language,
     * shop default language, then English. The first candidate with a shipped
     * localized set wins; English is the terminal fallback.
     *
     * This is independent of the mail template files on disk (which are
     * content-agnostic pass-through shells): the email language is driven by the
     * rule content and these strings, not by which mails/<iso>/ file exists.
     *
     * @param int $idLang the customer's language id
     * @param int $idShop the shop the coupon belongs to
     *
     * @return string an ISO code with a shipped localized string set
     */
    public function resolveIso($idLang, $idShop)
    {
        foreach ($this->buildIsoCandidates((int) $idLang, (int) $idShop) as $iso) {
            if ($this->supportsIso($iso)) {
                return $iso;
            }
        }

        return self::FALLBACK_ISO;
    }

    /**
     * @param string $iso
     *
     * @return bool whether a shipped localized string set exists for this ISO
     */
    private function supportsIso($iso)
    {
        return isset(self::SUBJECTS[RuleEmailRepository::TYPE_COUPON][(string) $iso]);
    }

    /**
     * Builds the ordered, de-duplicated ISO candidate list to probe.
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
     * Reads the shipped HTML template for an email type and ISO, falling back to
     * English when the requested language has no shipped template.
     *
     * @param string $emailType
     * @param string $iso
     *
     * @return string
     */
    public function getHtml($emailType, $iso)
    {
        $emailType = (string) $emailType;
        if (!isset(self::TEMPLATE_NAMES[$emailType])) {
            return '';
        }
        $template = self::TEMPLATE_NAMES[$emailType];

        foreach ([(string) $iso, self::FALLBACK_ISO] as $candidate) {
            if ($candidate === '') {
                continue;
            }
            $path = $this->getDefaultsPath() . $candidate . '/' . $template . '.html';
            if (is_file($path)) {
                $content = file_get_contents($path);
                if ($content !== false) {
                    return (string) $content;
                }
            }
        }

        return '';
    }

    /**
     * @param int $idLang
     *
     * @return string ISO code (fallback when the language is unknown)
     */
    private function isoOf($idLang)
    {
        $iso = $idLang > 0 ? (string) \Language::getIsoById($idLang) : '';

        return $iso !== '' ? $iso : self::FALLBACK_ISO;
    }

    /**
     * @return string absolute path to the module's default email bodies
     *                directory (views/email_defaults/, with a trailing slash)
     */
    private function getDefaultsPath()
    {
        return rtrim(_PS_MODULE_DIR_, '/') . '/' . self::MODULE_NAME . '/views/email_defaults/';
    }
}
