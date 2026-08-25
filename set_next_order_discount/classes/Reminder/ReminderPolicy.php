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

namespace Setecom\NextOrderDiscount\Reminder;

use Configuration;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Reminder policy: whether reminders are enabled and how close to a coupon's
 * expiry each of the two reminders becomes due, expressed in days before the
 * expiry date.
 *
 * It is a pure, validated value object with no side effects, injected into the
 * planner. A named constructor reads the values from the module configuration
 * (falling back to sensible defaults when the keys are absent) so the same
 * policy can be driven by merchant settings once a settings UI wires them.
 */
class ReminderPolicy
{
    public const CONFIG_ENABLED = 'SNOD_REMINDER_ENABLED';
    public const CONFIG_FIRST_DAYS = 'SNOD_REMINDER_1_DAYS';
    public const CONFIG_SECOND_DAYS = 'SNOD_REMINDER_2_DAYS';

    public const DEFAULT_ENABLED = true;
    public const DEFAULT_FIRST_DAYS = 7;
    public const DEFAULT_SECOND_DAYS = 2;

    private $enabled;
    private $firstReminderDays;
    private $secondReminderDays;

    /**
     * @param bool $enabled
     * @param int  $firstReminderDays  days before expiry the first reminder is due (>= 1)
     * @param int  $secondReminderDays days before expiry the second reminder is due (>= 1)
     */
    public function __construct(
        $enabled = self::DEFAULT_ENABLED,
        $firstReminderDays = self::DEFAULT_FIRST_DAYS,
        $secondReminderDays = self::DEFAULT_SECOND_DAYS
    ) {
        $this->enabled = (bool) $enabled;
        $this->firstReminderDays = max(1, (int) $firstReminderDays);
        $this->secondReminderDays = max(1, (int) $secondReminderDays);
    }

    /**
     * Builds the policy from the module configuration for a given shop, applying
     * defaults for any key that has never been stored.
     *
     * @param int|null $idShop
     *
     * @return self
     */
    public static function fromConfiguration($idShop = null)
    {
        $idShop = $idShop !== null ? (int) $idShop : null;

        return new self(
            self::readBool(self::CONFIG_ENABLED, self::DEFAULT_ENABLED, $idShop),
            self::readInt(self::CONFIG_FIRST_DAYS, self::DEFAULT_FIRST_DAYS, $idShop),
            self::readInt(self::CONFIG_SECOND_DAYS, self::DEFAULT_SECOND_DAYS, $idShop)
        );
    }

    /**
     * @return bool
     */
    public function isEnabled()
    {
        return $this->enabled;
    }

    /**
     * @return int
     */
    public function getFirstReminderDays()
    {
        return $this->firstReminderDays;
    }

    /**
     * @return int
     */
    public function getSecondReminderDays()
    {
        return $this->secondReminderDays;
    }

    /**
     * @param string   $key
     * @param bool     $default
     * @param int|null $idShop
     *
     * @return bool
     */
    private static function readBool($key, $default, $idShop)
    {
        $raw = Configuration::get($key, null, null, $idShop);
        if ($raw === false) {
            return $default;
        }

        return (int) $raw === 1;
    }

    /**
     * @param string   $key
     * @param int      $default
     * @param int|null $idShop
     *
     * @return int
     */
    private static function readInt($key, $default, $idShop)
    {
        $raw = Configuration::get($key, null, null, $idShop);
        if ($raw === false || !is_numeric($raw)) {
            return $default;
        }

        return max(1, (int) $raw);
    }
}
