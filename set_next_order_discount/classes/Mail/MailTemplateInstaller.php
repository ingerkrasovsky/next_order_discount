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
 * Generates the module's mail templates on disk.
 *
 * The templates are pure pass-through shells — each .html is only
 * {snod_body_html} and each .txt only {snod_body_txt} — because the real,
 * per-language email content is stored per rule in the database and injected at
 * send time. PrestaShop's Mail::send still resolves the template file by the
 * recipient's language folder (mails/<iso>/), and it does not create those
 * folders for a custom module, so this installer materializes an identical shell
 * for every installed language. That keeps a single canonical template (there is
 * no per-language content to maintain) while avoiding the "template missing" log
 * entries Mail::send writes when it has to fall back to another language.
 */
class MailTemplateInstaller
{
    public const MODULE_NAME = 'set_next_order_discount';

    /**
     * Template base names to materialize per language.
     */
    private const TEMPLATE_NAMES = [
        'next_order_discount',
        'reminder_next_order_discount',
    ];

    /**
     * Canonical pass-through bodies. The HTML/text of the actual email comes from
     * the rule's own content, injected by the mailers via these placeholders.
     */
    private const HTML_BODY = "{snod_body_html}\n";
    private const TXT_BODY = "{snod_body_txt}\n";

    /**
     * Writes the pass-through shells for every installed language (active or not).
     *
     * @return bool true when every shell was written
     */
    public function installForAllLanguages()
    {
        $ok = true;
        foreach (\Language::getLanguages(false) as $lang) {
            $iso = isset($lang['iso_code']) ? (string) $lang['iso_code'] : '';
            if ($iso === '') {
                continue;
            }
            $ok = $this->installForIso($iso) && $ok;
        }

        return $ok;
    }

    /**
     * Writes the pass-through shells for one ISO code.
     *
     * @param string $iso
     *
     * @return bool
     */
    public function installForIso($iso)
    {
        $iso = (string) $iso;
        $dir = $this->getMailsPath() . $iso;
        if (!is_dir($dir) && !@mkdir($dir, 0755, true) && !is_dir($dir)) {
            return false;
        }

        $this->ensureIndex($dir);

        $ok = true;
        foreach (self::TEMPLATE_NAMES as $name) {
            // Always (re)write: the shells carry no content, so overwriting keeps
            // them canonical even if an older full-HTML template was present.
            $ok = (@file_put_contents($dir . '/' . $name . '.html', self::HTML_BODY) !== false) && $ok;
            $ok = (@file_put_contents($dir . '/' . $name . '.txt', self::TXT_BODY) !== false) && $ok;
        }

        return $ok;
    }

    /**
     * Drops the standard PrestaShop index.php guard into a generated folder,
     * copied from the module's shipped mails/index.php.
     *
     * @param string $dir
     *
     * @return void
     */
    private function ensureIndex($dir)
    {
        $index = $dir . '/index.php';
        if (is_file($index)) {
            return;
        }

        $source = $this->getMailsPath() . 'index.php';
        if (is_file($source)) {
            @copy($source, $index);
        }
    }

    /**
     * @return string absolute path to the module's mails/ directory (trailing slash)
     */
    private function getMailsPath()
    {
        return rtrim(_PS_MODULE_DIR_, '/') . '/' . self::MODULE_NAME . '/mails/';
    }
}
