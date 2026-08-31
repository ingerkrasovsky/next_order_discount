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
namespace Setecom\NextOrderDiscount\Logger;

use Setecom\NextOrderDiscount\Repository\LogRepository;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Structured module logger.
 *
 * Writes level/channel/message entries — with an optional JSON context and a
 * correlation id for tracing a single operation across steps — to the module log
 * store. A configurable minimum level (from SNOD_LOG_LEVEL) filters out anything
 * below the threshold. Logging is strictly best-effort: it never throws, so an
 * observability failure can never disrupt the business operation being logged.
 */
class ModuleLogger
{
    public const LEVEL_DEBUG = 'debug';
    public const LEVEL_INFO = 'info';
    public const LEVEL_WARNING = 'warning';
    public const LEVEL_ERROR = 'error';

    /**
     * Numeric severity of each level, used for threshold comparison.
     */
    private const LEVEL_WEIGHTS = [
        self::LEVEL_DEBUG => 10,
        self::LEVEL_INFO => 20,
        self::LEVEL_WARNING => 30,
        self::LEVEL_ERROR => 40,
    ];

    private $repository;
    private $minWeight;

    /**
     * @param LogRepository $repository
     * @param string $minLevel minimum level to persist (defaults to info)
     */
    public function __construct(LogRepository $repository, $minLevel = self::LEVEL_INFO)
    {
        $this->repository = $repository;
        $this->minWeight = $this->weightOf($minLevel, self::LEVEL_WEIGHTS[self::LEVEL_INFO]);
    }

    /**
     * @param string $message
     * @param array $context
     * @param string|null $correlationId
     * @param string $channel
     *
     * @return void
     */
    public function debug($message, array $context = [], $correlationId = null, $channel = '')
    {
        $this->log(self::LEVEL_DEBUG, $message, $context, $correlationId, $channel);
    }

    /**
     * @param string $message
     * @param array $context
     * @param string|null $correlationId
     * @param string $channel
     *
     * @return void
     */
    public function info($message, array $context = [], $correlationId = null, $channel = '')
    {
        $this->log(self::LEVEL_INFO, $message, $context, $correlationId, $channel);
    }

    /**
     * @param string $message
     * @param array $context
     * @param string|null $correlationId
     * @param string $channel
     *
     * @return void
     */
    public function warning($message, array $context = [], $correlationId = null, $channel = '')
    {
        $this->log(self::LEVEL_WARNING, $message, $context, $correlationId, $channel);
    }

    /**
     * @param string $message
     * @param array $context
     * @param string|null $correlationId
     * @param string $channel
     *
     * @return void
     */
    public function error($message, array $context = [], $correlationId = null, $channel = '')
    {
        $this->log(self::LEVEL_ERROR, $message, $context, $correlationId, $channel);
    }

    /**
     * Deletes log entries older than the given number of days (0 = keep forever).
     * Best-effort: a pruning failure never disrupts the caller.
     *
     * @param int $days
     *
     * @return void
     */
    public function pruneOlderThan($days)
    {
        try {
            $this->repository->pruneOlderThan((int) $days);
        } catch (\Throwable $e) {
            // Retention cleanup is best-effort.
        }
    }

    /**
     * Persists a log entry when its level meets the configured threshold.
     *
     * @param string $level
     * @param string $message
     * @param array $context
     * @param string|null $correlationId
     * @param string $channel
     *
     * @return void
     */
    private function log($level, $message, array $context, $correlationId, $channel)
    {
        if ($this->weightOf($level, 0) < $this->minWeight) {
            return;
        }

        try {
            $this->repository->insert([
                'id_shop' => $this->currentShopId(),
                'level' => (string) $level,
                'channel' => (string) $channel,
                'message' => (string) $message,
                'context_json' => empty($context) ? null : $this->encodeContext($context),
                'correlation_id' => ($correlationId === null || $correlationId === '') ? null : (string) $correlationId,
            ]);
        } catch (\Throwable $e) {
            // Logging must never break the operation it observes.
        }
    }

    /**
     * @param string $level
     * @param int $default
     *
     * @return int
     */
    private function weightOf($level, $default)
    {
        $level = (string) $level;

        return isset(self::LEVEL_WEIGHTS[$level]) ? self::LEVEL_WEIGHTS[$level] : $default;
    }

    /**
     * @return int
     */
    private function currentShopId()
    {
        return max(0, (int) \Shop::getContextShopID(true));
    }

    /**
     * @param array $context
     *
     * @return string JSON, never false
     */
    private function encodeContext(array $context)
    {
        $json = json_encode($context);

        return $json === false ? '{}' : $json;
    }
}
