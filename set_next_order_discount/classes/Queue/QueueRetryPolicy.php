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

namespace Setecom\NextOrderDiscount\Queue;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Retry policy for queue tasks: how many attempts a failing task gets and how
 * long to wait before each retry (exponential backoff, capped at a maximum).
 *
 * It is pure decision logic with no side effects, so the worker can consult it
 * for every task type and it stays trivially unit-testable. All bounds are
 * validated and clamped in the constructor so the policy can never produce a
 * negative delay or an attempt budget below one.
 */
class QueueRetryPolicy
{
    public const DEFAULT_MAX_ATTEMPTS = 3;
    public const DEFAULT_BASE_DELAY_SECONDS = 60;
    public const DEFAULT_MAX_DELAY_SECONDS = 3600;

    /**
     * Above this exponent the backoff has long since reached the cap; stopping
     * here avoids pointless huge shifts and any integer overflow.
     */
    private const MAX_EXPONENT = 30;

    private $maxAttempts;
    private $baseDelaySeconds;
    private $maxDelaySeconds;

    /**
     * @param int $maxAttempts      total attempts before a task is failed (>= 1)
     * @param int $baseDelaySeconds delay before the first retry (>= 0)
     * @param int $maxDelaySeconds  upper bound for the backoff delay
     */
    public function __construct(
        $maxAttempts = self::DEFAULT_MAX_ATTEMPTS,
        $baseDelaySeconds = self::DEFAULT_BASE_DELAY_SECONDS,
        $maxDelaySeconds = self::DEFAULT_MAX_DELAY_SECONDS
    ) {
        $this->maxAttempts = max(1, (int) $maxAttempts);
        $this->baseDelaySeconds = max(0, (int) $baseDelaySeconds);
        $this->maxDelaySeconds = max($this->baseDelaySeconds, (int) $maxDelaySeconds);
    }

    /**
     * @return int
     */
    public function getMaxAttempts()
    {
        return $this->maxAttempts;
    }

    /**
     * @param int $attempts number of attempts already made (including the one
     *                      that just failed)
     *
     * @return bool whether another attempt is allowed
     */
    public function canRetry($attempts)
    {
        return (int) $attempts < $this->maxAttempts;
    }

    /**
     * Exponential backoff: base * 2^(attempts - 1), capped at the maximum delay.
     *
     * @param int $attempts number of attempts already made
     *
     * @return int delay in seconds before the next attempt
     */
    public function getBackoffSeconds($attempts)
    {
        if ($this->baseDelaySeconds === 0) {
            return 0;
        }

        $attempts = (int) $attempts;
        if ($attempts < 1) {
            $attempts = 1;
        }

        $exponent = $attempts - 1;
        if ($exponent > self::MAX_EXPONENT) {
            return $this->maxDelaySeconds;
        }

        $delay = $this->baseDelaySeconds * (2 ** $exponent);

        return (int) min($this->maxDelaySeconds, $delay);
    }

    /**
     * Absolute timestamp at which a rescheduled task becomes eligible again.
     *
     * @param int      $attempts number of attempts already made
     * @param int|null $nowTs    reference epoch time; defaults to now (injectable)
     *
     * @return string 'Y-m-d H:i:s'
     */
    public function getNextAvailableAt($attempts, $nowTs = null)
    {
        $nowTs = $nowTs === null ? time() : (int) $nowTs;

        return date('Y-m-d H:i:s', $nowTs + $this->getBackoffSeconds($attempts));
    }
}
