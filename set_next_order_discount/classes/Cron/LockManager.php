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

use Setecom\NextOrderDiscount\Repository\CronLockRepository;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Process-scoped facade over the database cron lock.
 *
 * Each manager instance owns a unique token, so it can only release or extend a
 * lock it actually holds. Acquisition is atomic and self-healing: an expired
 * lock left behind by a crashed run is transparently taken over, so a task can
 * never be blocked forever. Concurrent runs of the same task fail to acquire and
 * are expected to skip.
 */
class LockManager
{
    /**
     * Default lock lifetime; long enough to cover a slow batch, short enough that
     * a crashed run frees the task reasonably soon.
     */
    public const DEFAULT_TTL_SECONDS = 300;

    private $repository;
    private $ownerToken;

    /**
     * @param CronLockRepository $repository
     * @param string|null        $ownerToken explicit owner token (generated when null)
     */
    public function __construct(CronLockRepository $repository, $ownerToken = null)
    {
        $this->repository = $repository;
        $this->ownerToken = ($ownerToken !== null && $ownerToken !== '')
            ? (string) $ownerToken
            : $this->generateOwnerToken();
    }

    /**
     * Attempts to acquire the named lock for this manager's owner token.
     *
     * @param string $lockName
     * @param int    $ttlSeconds
     *
     * @return bool
     */
    public function acquire($lockName, $ttlSeconds = self::DEFAULT_TTL_SECONDS)
    {
        return $this->repository->acquire((string) $lockName, $this->ownerToken, (int) $ttlSeconds);
    }

    /**
     * Releases the named lock, but only if this manager still owns it.
     *
     * @param string $lockName
     *
     * @return bool
     */
    public function release($lockName)
    {
        return $this->repository->release((string) $lockName, $this->ownerToken);
    }

    /**
     * @param string $lockName
     *
     * @return bool whether any non-expired lock currently exists
     */
    public function isLocked($lockName)
    {
        return $this->repository->isLocked((string) $lockName);
    }

    /**
     * @return string this manager's owner token
     */
    public function getOwnerToken()
    {
        return $this->ownerToken;
    }

    /**
     * @return string a high-entropy owner token, with a portable fallback
     */
    private function generateOwnerToken()
    {
        try {
            return bin2hex(random_bytes(16));
        } catch (\Exception $e) {
            return uniqid('snod_', true);
        }
    }
}
