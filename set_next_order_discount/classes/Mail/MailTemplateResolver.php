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

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Tells the coupon mailer where the module's mail templates live and the base
 * name of the coupon template passed to Mail::send.
 *
 * The templates are content-agnostic pass-through shells (see
 * MailTemplateInstaller); the language-specific strings (subject, free-shipping
 * label) and the ISO resolution live in DefaultEmailProvider, the single source
 * of truth for shipped defaults.
 */
class MailTemplateResolver
{
    /**
     * Base name of the coupon template files (without the .html / .txt extension).
     */
    public const TEMPLATE_NAME = 'next_order_discount';

    /**
     * Technical name of the module, used to build the mails/ path.
     */
    public const MODULE_NAME = 'set_next_order_discount';

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
     * @return string the template base name passed to Mail::send (the pure
     *                pass-through coupon template)
     */
    public function getTemplateName()
    {
        return self::TEMPLATE_NAME;
    }
}
