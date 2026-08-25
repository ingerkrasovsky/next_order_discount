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
 * Contract for a queue task handler.
 *
 * The worker looks up the handler registered for a task's type and delegates the
 * real work to it. Implementations must be self-contained: they receive the raw
 * task row and return whether the work succeeded. Returning false — or throwing —
 * signals a retryable failure, which the worker turns into a backoff retry or a
 * terminal failure according to the retry policy. New task types are supported by
 * registering additional handlers, without touching the worker.
 */
interface QueueTaskHandlerInterface
{
    /**
     * @param array $task a ps_snod_dispatch_queue row
     *
     * @return bool true on success, false to signal a retryable failure
     */
    public function handle(array $task);
}
