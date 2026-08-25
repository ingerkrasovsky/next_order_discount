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

namespace Setecom\NextOrderDiscount\Cron;

use Configuration;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Guards the public cron endpoint with the secret token stored in
 * `SNOD_CRON_TOKEN`.
 *
 * The comparison is constant-time to avoid leaking the token through timing, and
 * an empty configured or supplied token is always rejected so a misconfigured
 * installation can never be triggered anonymously.
 */
class CronSecurityService
{
    public const CONFIG_TOKEN = 'SNOD_CRON_TOKEN';

    /**
     * @return string the configured cron token (empty when never generated)
     */
    public function getToken()
    {
        $token = Configuration::get(self::CONFIG_TOKEN);

        return $token === false ? '' : (string) $token;
    }

    /**
     * @param string $providedToken token supplied by the caller
     *
     * @return bool whether the supplied token matches the configured one
     */
    public function isValidToken($providedToken)
    {
        $expected = $this->getToken();
        $providedToken = is_scalar($providedToken) ? (string) $providedToken : '';

        if ($expected === '' || $providedToken === '') {
            return false;
        }

        return hash_equals($expected, $providedToken);
    }
}
