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
 * type and language, read from the module's mails/ templates. It is the source
 * used to seed a rule's own editable email when the rule is created, and the
 * fallback the mailers use when a rule has no stored content for a language.
 */
class DefaultEmailProvider
{
    public const MODULE_NAME = 'set_next_order_discount';

    private const FALLBACK_ISO = 'en';

    /**
     * Template file base name per email type.
     */
    private const TEMPLATE_NAMES = [
        RuleEmailRepository::TYPE_COUPON => 'next_order_discount',
        RuleEmailRepository::TYPE_REMINDER_1 => 'reminder_next_order_discount',
        RuleEmailRepository::TYPE_REMINDER_2 => 'reminder_next_order_discount',
    ];

    /**
     * Default subject per email type, keyed by ISO code.
     */
    private const SUBJECTS = [
        RuleEmailRepository::TYPE_COUPON => [
            'en' => 'A discount for your next order',
            'ru' => 'Скидка на ваш следующий заказ',
        ],
        RuleEmailRepository::TYPE_REMINDER_1 => [
            'en' => 'Your discount is waiting — use it before it expires',
            'ru' => 'Ваша скидка ждёт — успейте воспользоваться до истечения',
        ],
        RuleEmailRepository::TYPE_REMINDER_2 => [
            'en' => 'Your discount is waiting — use it before it expires',
            'ru' => 'Ваша скидка ждёт — успейте воспользоваться до истечения',
        ],
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
            $path = $this->getMailsPath() . $candidate . '/' . $template . '.html';
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
     * @return string absolute path to the module mails/ directory (trailing slash)
     */
    private function getMailsPath()
    {
        return rtrim(_PS_MODULE_DIR_, '/') . '/' . self::MODULE_NAME . '/mails/';
    }
}
