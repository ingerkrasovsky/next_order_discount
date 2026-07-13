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
if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Small, reusable value formatting helpers shared by the rule services so the
 * same decimal/date presentation logic is not duplicated.
 */
class SnodRuleValueFormatter
{
    /**
     * Formats a decimal for display, trimming trailing zeros ("7.50" -> "7.5",
     * "10.00" -> "10").
     *
     * @param mixed $value
     *
     * @return string
     */
    public static function decimal($value)
    {
        $formatted = number_format((float) $value, 2, '.', '');
        $formatted = rtrim(rtrim($formatted, '0'), '.');

        return $formatted === '' ? '0' : $formatted;
    }

    /**
     * Extracts the date part of a datetime column for an <input type="date">.
     *
     * @param string|null $value
     *
     * @return string 'Y-m-d' or ''
     */
    public static function dateColumnToInput($value)
    {
        $value = (string) $value;
        if ($value === '' || strpos($value, '0000-00-00') === 0) {
            return '';
        }

        return Tools::substr($value, 0, 10);
    }
}
