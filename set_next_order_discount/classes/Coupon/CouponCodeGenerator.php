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

use Configuration;
use Db;
use Tools;
use Exception;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Generates unique, human-friendly coupon codes.
 *
 * The code shape is driven by module configuration (read in the current shop
 * scope for multishop correctness):
 *  - SNOD_CODE_MASK   optional pattern where each "#" is replaced by a random
 *                     character and every other character is kept literally
 *                     (e.g. "NOD-####-####"); ignored if it has no placeholder;
 *  - SNOD_CODE_PREFIX prefix used when no mask is set (default "NOD");
 *  - SNOD_CODE_LENGTH random-part length used when no mask is set (default 12).
 *
 * The random part uses a cryptographically secure source (random_int) over an
 * unambiguous alphabet (no 0/O/1/I/L). Uniqueness is verified against both the
 * native PrestaShop cart rules and the module's own coupon links, retrying up
 * to MAX_ATTEMPTS times before giving up.
 */
class CouponCodeGenerator
{
    public const MAX_ATTEMPTS = 20;
    public const MAX_CODE_LENGTH = 64;

    private const DEFAULT_PREFIX = 'NOD';
    private const DEFAULT_RANDOM_LENGTH = 12;
    private const MIN_RANDOM_LENGTH = 4;
    private const MAX_RANDOM_LENGTH = 32;
    private const MAX_PREFIX_LENGTH = 20;
    private const MASK_PLACEHOLDER = '#';
    private const ALPHABET = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';

    private $idShop;
    private $overrides;

    /**
     * @param int   $idShop    shop scope used to read configuration (0 = global)
     * @param array $overrides per-rule overrides: keys 'prefix' (string),
     *                         'length' (int), 'mask' (string). An empty/zero
     *                         override falls back to the global configuration.
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
     * Produces one candidate code from the mask, or from prefix + random part.
     *
     * @return string
     */
    private function buildCode()
    {
        $mask = $this->getMask();
        if ($mask !== '') {
            return $this->buildFromMask($mask);
        }

        return $this->buildFromPrefix();
    }

    /**
     * @param string $mask sanitized pattern containing at least one placeholder
     *
     * @return string
     */
    private function buildFromMask($mask)
    {
        $code = '';
        $length = strlen($mask);
        for ($i = 0; $i < $length; ++$i) {
            $char = $mask[$i];
            $code .= ($char === self::MASK_PLACEHOLDER) ? $this->randomChar() : $char;
        }

        return $code;
    }

    /**
     * @return string
     */
    private function buildFromPrefix()
    {
        $prefix = $this->getPrefix();
        $random = $this->randomString($this->getRandomLength());

        // Prefix (<=20) + separator + random (<=32) never exceeds MAX_CODE_LENGTH.
        if ($prefix === '') {
            return $random;
        }

        return $prefix . '-' . $random;
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
     * @param int $length
     *
     * @return string
     */
    private function randomString($length)
    {
        $length = (int) $length;
        $result = '';
        for ($i = 0; $i < $length; ++$i) {
            $result .= $this->randomChar();
        }

        return $result;
    }

    /**
     * @return string one random character from the alphabet
     */
    private function randomChar()
    {
        $maxIndex = strlen(self::ALPHABET) - 1;

        return self::ALPHABET[$this->randomInt(0, $maxIndex)];
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
     * @return string sanitized prefix (A-Z0-9, <=MAX_PREFIX_LENGTH); '' if the
     *                merchant explicitly cleared it
     */
    private function getPrefix()
    {
        $override = isset($this->overrides['prefix']) ? (string) $this->overrides['prefix'] : '';
        $raw = $override !== '' ? $override : $this->getConfig('SNOD_CODE_PREFIX');
        if ($raw === false || $raw === null) {
            $raw = self::DEFAULT_PREFIX;
        }

        $prefix = preg_replace('/[^A-Z0-9]/', '', Tools::strtoupper(trim((string) $raw)));
        if (!is_string($prefix)) {
            return '';
        }

        return substr($prefix, 0, self::MAX_PREFIX_LENGTH);
    }

    /**
     * @return int random-part length clamped to [MIN_RANDOM_LENGTH, MAX_RANDOM_LENGTH]
     */
    private function getRandomLength()
    {
        $override = isset($this->overrides['length']) ? (int) $this->overrides['length'] : 0;
        $length = $override > 0 ? $override : (int) $this->getConfig('SNOD_CODE_LENGTH');
        if ($length <= 0) {
            $length = self::DEFAULT_RANDOM_LENGTH;
        }

        return max(self::MIN_RANDOM_LENGTH, min(self::MAX_RANDOM_LENGTH, $length));
    }

    /**
     * @return string sanitized mask containing at least one placeholder, or ''
     *                when no usable mask is configured
     */
    private function getMask()
    {
        $override = isset($this->overrides['mask']) ? (string) $this->overrides['mask'] : '';
        $raw = $override !== '' ? $override : $this->getConfig('SNOD_CODE_MASK');
        if ($raw === false || $raw === null) {
            return '';
        }

        $mask = preg_replace('/[^A-Z0-9#_\-]/', '', Tools::strtoupper(trim((string) $raw)));
        if (!is_string($mask)) {
            return '';
        }

        $mask = substr($mask, 0, self::MAX_CODE_LENGTH);
        if (strpos($mask, self::MASK_PLACEHOLDER) === false) {
            return '';
        }

        return $mask;
    }

    /**
     * Reads a configuration value in the configured shop scope, falling back to
     * the global value when no shop is set.
     *
     * @param string $key
     *
     * @return mixed
     */
    private function getConfig($key)
    {
        if ($this->idShop > 0) {
            return Configuration::get($key, null, null, $this->idShop);
        }

        return Configuration::get($key);
    }
}
