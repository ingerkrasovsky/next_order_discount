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

use Db;
use Exception;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Generates unique coupon codes from a per-rule template.
 *
 * The shape is driven entirely by the rule's own settings (no store-wide
 * configuration):
 *  - length   the number of random characters in the generated key;
 *  - type     the key alphabet: 1 = A-Z, 2 = 0-9, 3 = A-Z + 0-9;
 *  - template a pattern where "%key%" is replaced by the generated key and every
 *             other character is kept literally (e.g. "NOD-%key%" -> "NOD-AB12CD8X").
 *             When the template has no "%key%" placeholder the key is appended.
 *
 * The random key uses a cryptographically secure source (random_int). Uniqueness
 * is verified against both the native PrestaShop cart rules and the module's own
 * coupon links, retrying up to MAX_ATTEMPTS times before giving up.
 */
class CouponCodeGenerator
{
    public const MAX_ATTEMPTS = 20;
    public const MAX_CODE_LENGTH = 64;

    public const TYPE_ALPHA = 1;
    public const TYPE_NUMERIC = 2;
    public const TYPE_ALPHANUMERIC = 3;

    public const DEFAULT_LENGTH = 8;
    public const DEFAULT_TYPE = self::TYPE_ALPHANUMERIC;
    public const DEFAULT_TEMPLATE = 'NOD-%key%';

    public const PLACEHOLDER = '%key%';

    private const MIN_LENGTH = 4;
    private const MAX_LENGTH = 32;

    /**
     * Full-set alphabets per key type (ambiguous characters included, as chosen).
     */
    private const ALPHABETS = [
        self::TYPE_ALPHA => 'ABCDEFGHIJKLMNOPQRSTUVWXYZ',
        self::TYPE_NUMERIC => '0123456789',
        self::TYPE_ALPHANUMERIC => 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789',
    ];

    private $idShop;
    private $overrides;

    /**
     * @param int   $idShop    shop scope (kept for signature compatibility)
     * @param array $overrides per-rule settings: 'length' (int), 'type' (int),
     *                         'template' (string). Empty/invalid values fall back
     *                         to the DEFAULT_* constants.
     */
    public function __construct($idShop = 0, array $overrides = [])
    {
        $this->idShop = (int) $idShop;
        $this->overrides = $overrides;
    }

    /**
     * Builds a coupon code that does not yet exist. Retries on collision up to
     * MAX_ATTEMPTS times.
     *
     * @return string the unique code, or '' if none could be generated
     */
    public function generate()
    {
        for ($attempt = 0; $attempt < self::MAX_ATTEMPTS; ++$attempt) {
            $code = $this->buildCode();
            if ($code === '') {
                return '';
            }
            if (!$this->codeExists($code)) {
                return $code;
            }
        }

        return '';
    }

    /**
     * Produces one candidate code by rendering the key into the template.
     *
     * @return string
     */
    private function buildCode()
    {
        $key = $this->randomKey($this->getLength(), $this->getAlphabet());
        $template = $this->getTemplate();

        $code = strpos($template, self::PLACEHOLDER) !== false
            ? str_replace(self::PLACEHOLDER, $key, $template)
            : $template . $key;

        return substr($code, 0, self::MAX_CODE_LENGTH);
    }

    /**
     * Checks whether a code is already used by a native cart rule or by an
     * existing module coupon link.
     *
     * @param string $code
     *
     * @return bool
     */
    private function codeExists($code)
    {
        $escaped = pSQL($code);

        // Note: Db::getValue() delegates to getRow(), which already appends
        // "LIMIT 1", so no explicit LIMIT is added here (that would double it).
        $existsInCartRules = (int) Db::getInstance()->getValue(
            'SELECT `id_cart_rule` FROM `' . _DB_PREFIX_ . 'cart_rule`'
            . ' WHERE `code` = "' . $escaped . '"'
        );
        if ($existsInCartRules > 0) {
            return true;
        }

        $existsInLinks = (int) Db::getInstance()->getValue(
            'SELECT `id_snod_coupon_link` FROM `' . _DB_PREFIX_ . 'snod_coupon_link`'
            . ' WHERE `coupon_code` = "' . $escaped . '"'
        );

        return $existsInLinks > 0;
    }

    /**
     * @param int    $length
     * @param string $alphabet
     *
     * @return string a random key of the given length over the alphabet
     */
    private function randomKey($length, $alphabet)
    {
        $maxIndex = strlen($alphabet) - 1;
        $result = '';
        for ($i = 0; $i < (int) $length; ++$i) {
            $result .= $alphabet[$this->randomInt(0, $maxIndex)];
        }

        return $result;
    }

    /**
     * Cryptographically secure random integer, with a safe fallback if the
     * system CSPRNG is unavailable so code generation never hard-fails.
     *
     * @param int $min
     * @param int $max
     *
     * @return int
     */
    private function randomInt($min, $max)
    {
        try {
            return random_int($min, $max);
        } catch (Exception $e) {
            return mt_rand($min, $max);
        }
    }

    /**
     * @return int key length clamped to [MIN_LENGTH, MAX_LENGTH]
     */
    private function getLength()
    {
        $override = isset($this->overrides['length']) ? (int) $this->overrides['length'] : 0;
        $length = $override > 0 ? $override : self::DEFAULT_LENGTH;

        return max(self::MIN_LENGTH, min(self::MAX_LENGTH, $length));
    }

    /**
     * @return string the alphabet for the configured key type
     */
    private function getAlphabet()
    {
        $type = isset($this->overrides['type']) ? (int) $this->overrides['type'] : 0;
        if (!isset(self::ALPHABETS[$type])) {
            $type = self::DEFAULT_TYPE;
        }

        return self::ALPHABETS[$type];
    }

    /**
     * @return string sanitized template, guaranteed non-empty
     */
    private function getTemplate()
    {
        $raw = isset($this->overrides['template']) ? trim((string) $this->overrides['template']) : '';
        if ($raw === '') {
            $raw = self::DEFAULT_TEMPLATE;
        }

        $template = self::sanitizeTemplate($raw);
        if ($template === '') {
            $template = self::DEFAULT_TEMPLATE;
        }

        return substr($template, 0, self::MAX_CODE_LENGTH);
    }

    /**
     * Keeps the "%key%" placeholder and a safe literal charset (letters, digits,
     * dash and underscore), dropping anything else.
     *
     * @param string $raw
     *
     * @return string
     */
    public static function sanitizeTemplate($raw)
    {
        // Protect the placeholder while stripping the literal part.
        $sentinel = "\x01";
        $tmp = str_replace(self::PLACEHOLDER, $sentinel, (string) $raw);
        $tmp = preg_replace('/[^A-Za-z0-9_\-' . $sentinel . ']/', '', $tmp);
        $tmp = is_string($tmp) ? $tmp : '';

        return str_replace($sentinel, self::PLACEHOLDER, $tmp);
    }
}
