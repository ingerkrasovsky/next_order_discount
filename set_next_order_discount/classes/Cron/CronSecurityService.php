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
     * The configured cron token, resolved robustly across contexts.
     *
     * The token is a single per-installation secret, so it belongs in the global
     * (shop-independent) configuration. It reads the global value first; if only a
     * legacy per-shop value exists (e.g. installed before this fix, where
     * `Configuration::get()` cannot see it from the public cron front controller),
     * it adopts that value and promotes it to global so every later request —
     * including the token-guarded cron endpoint — resolves it consistently.
     *
     * @return string the configured cron token (empty when never generated)
     */
    public function getToken()
    {
        $global = \Configuration::getGlobalValue(self::CONFIG_TOKEN);
        if (is_string($global) && $global !== '') {
            return $global;
        }

        // Context-scoped value (works in the back office / a shop context).
        $scoped = \Configuration::get(self::CONFIG_TOKEN);
        if ($scoped !== false && (string) $scoped !== '') {
            $this->promoteToGlobal((string) $scoped);

            return (string) $scoped;
        }

        // Legacy fallback: the token was stored only as a per-shop row and is not
        // resolvable in this context. Read it straight from the table.
        $legacy = $this->readAnyStoredToken();
        if ($legacy !== '') {
            $this->promoteToGlobal($legacy);

            return $legacy;
        }

        return '';
    }

    /**
     * Persists the token as a global configuration value so subsequent reads are
     * context-independent. Best-effort: a failure here never breaks token checks.
     *
     * @param string $token
     *
     * @return void
     */
    private function promoteToGlobal($token)
    {
        try {
            \Configuration::updateGlobalValue(self::CONFIG_TOKEN, (string) $token);
        } catch (\Throwable $e) {
            // Ignored on purpose: the returned token is still usable this request.
        }
    }

    /**
     * @return string any non-empty stored token value, regardless of shop scope
     */
    private function readAnyStoredToken()
    {
        try {
            $value = \Db::getInstance()->getValue(
                'SELECT `value` FROM `' . _DB_PREFIX_ . 'configuration`'
                . ' WHERE `name` = "' . pSQL(self::CONFIG_TOKEN) . '" AND `value` <> ""'
                . ' ORDER BY `id_shop` DESC',
            );
        } catch (\Throwable $e) {
            return '';
        }

        return is_string($value) ? $value : '';
    }

    /**
     * @param mixed $providedToken token supplied by the caller (raw request value)
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
