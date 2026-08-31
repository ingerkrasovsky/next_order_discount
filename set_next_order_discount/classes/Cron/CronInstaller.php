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

use Setecom\NextOrderDiscount\Logger\ModuleLogger;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Best-effort installer of a system crontab entry for the module.
 *
 * This is a convenience for servers the merchant controls (typically a VPS)
 * where PHP may both run shell commands and manage the crontab. It is NOT a
 * universal solution: on shared hosting shell_exec is usually disabled, the
 * crontab binary may be absent, or the web-server user cannot own a crontab —
 * so every entry point first probes capabilities and the UI only offers the
 * button when installation is actually feasible.
 *
 * The managed entry is wrapped between marker comments so it can be replaced or
 * removed cleanly (on demand or when the module is uninstalled) without touching
 * any other crontab line the merchant may have.
 */
class CronInstaller
{
    public const MARKER_BEGIN = '# BEGIN set_next_order_discount';
    public const MARKER_END = '# END set_next_order_discount';

    private const LOG_CHANNEL = 'cron';

    private $logger;

    /**
     * @param ModuleLogger|null $logger optional structured logger
     */
    public function __construct(?ModuleLogger $logger = null)
    {
        $this->logger = $logger;
    }

    /**
     * Probes whether a crontab entry can be installed on this server.
     *
     * @return array {
     *               available: bool,  // all of the below are satisfied
     *               shell_exec: bool, // PHP may run shell commands
     *               crontab: bool,    // the crontab binary exists
     *               reason: string    // 'ok' | 'shell_exec_blocked' | 'no_crontab' | 'crontab_denied'
     *               }
     */
    public function capabilities()
    {
        if (!$this->shellExecAvailable()) {
            return ['available' => false, 'shell_exec' => false, 'crontab' => false, 'reason' => 'shell_exec_blocked'];
        }
        if (!$this->hasCrontabBinary()) {
            return ['available' => false, 'shell_exec' => true, 'crontab' => false, 'reason' => 'no_crontab'];
        }
        if (!$this->canManageCrontab()) {
            return ['available' => false, 'shell_exec' => true, 'crontab' => true, 'reason' => 'crontab_denied'];
        }

        return ['available' => true, 'shell_exec' => true, 'crontab' => true, 'reason' => 'ok'];
    }

    /**
     * Whether the module's managed block is currently present in the crontab.
     *
     * @return bool
     */
    public function isInstalled()
    {
        if (!$this->shellExecAvailable() || !$this->hasCrontabBinary()) {
            return false;
        }

        return strpos($this->readCrontab(), self::MARKER_BEGIN) !== false;
    }

    /**
     * Installs (or replaces) the module's managed crontab block.
     *
     * @param string $cronLine a full crontab line (schedule + command)
     *
     * @return array ['success' => bool, 'reason' => string]
     */
    public function install($cronLine)
    {
        $capabilities = $this->capabilities();
        if (empty($capabilities['available'])) {
            return ['success' => false, 'reason' => $capabilities['reason']];
        }

        try {
            $stripped = $this->stripBlock($this->readCrontab());
            $block = self::MARKER_BEGIN . "\n" . rtrim((string) $cronLine) . "\n" . self::MARKER_END;
            $content = rtrim($stripped) === '' ? $block : (rtrim($stripped) . "\n" . $block);

            if (!$this->writeCrontab($content)) {
                return ['success' => false, 'reason' => 'write_failed'];
            }

            // Trust nothing: confirm the block is actually there afterwards.
            if (!$this->isInstalled()) {
                return ['success' => false, 'reason' => 'verify_failed'];
            }

            $this->log(ModuleLogger::LEVEL_INFO, 'Cron installed to crontab', ['line' => (string) $cronLine]);

            return ['success' => true, 'reason' => 'ok'];
        } catch (\Throwable $e) {
            return ['success' => false, 'reason' => 'exception'];
        }
    }

    /**
     * Removes the module's managed crontab block. Idempotent and best-effort: an
     * absent block, a missing binary or a blocked shell all resolve to "nothing
     * left to do" so the module can call this safely on uninstall.
     *
     * @return bool whether the crontab is free of the module's block afterwards
     */
    public function remove()
    {
        if (!$this->shellExecAvailable() || !$this->hasCrontabBinary()) {
            return true;
        }

        try {
            $current = $this->readCrontab();
            if (strpos($current, self::MARKER_BEGIN) === false) {
                return true;
            }

            $stripped = $this->stripBlock($current);
            $ok = $this->writeCrontab($stripped);
            if ($ok) {
                $this->log(ModuleLogger::LEVEL_INFO, 'Cron removed from crontab', []);
            }

            return $ok;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * @return bool
     */
    private function shellExecAvailable()
    {
        if (!function_exists('shell_exec')) {
            return false;
        }
        $disabled = array_map('trim', explode(',', (string) ini_get('disable_functions')));

        return !in_array('shell_exec', $disabled, true);
    }

    /**
     * @return bool
     */
    private function hasCrontabBinary()
    {
        $out = @shell_exec('command -v crontab 2>/dev/null');

        return $out !== null && trim((string) $out) !== '';
    }

    /**
     * Whether `crontab -l` runs without a hard error. Exit code 0 (a crontab
     * exists) and 1 (no crontab yet for this user) both mean we can manage it;
     * anything else (e.g. denied by cron.deny) means we cannot.
     *
     * @return bool
     */
    private function canManageCrontab()
    {
        $out = @shell_exec('crontab -l 2>&1; echo "___EXIT:$?"');
        if ($out === null || !preg_match('/___EXIT:(\d+)/', $out, $m)) {
            return false;
        }

        return in_array((int) $m[1], [0, 1], true);
    }

    /**
     * @return string current crontab content ('' when none/unreadable)
     */
    private function readCrontab()
    {
        $out = @shell_exec('crontab -l 2>/dev/null');

        return $out === null ? '' : (string) $out;
    }

    /**
     * Removes the module's marked block from a crontab body, keeping every other
     * line untouched.
     *
     * @param string $content
     *
     * @return string
     */
    private function stripBlock($content)
    {
        $lines = preg_split('/\r\n|\r|\n/', (string) $content);
        $out = [];
        $inBlock = false;
        foreach ($lines as $line) {
            $trimmed = trim($line);
            if ($trimmed === self::MARKER_BEGIN) {
                $inBlock = true;
                continue;
            }
            if ($trimmed === self::MARKER_END) {
                $inBlock = false;
                continue;
            }
            if (!$inBlock) {
                $out[] = $line;
            }
        }

        return implode("\n", $out);
    }

    /**
     * Writes a full crontab body via a temp file (never through the shell, so the
     * command URL cannot be interpreted by the shell).
     *
     * @param string $content
     *
     * @return bool
     */
    private function writeCrontab($content)
    {
        $tmp = tempnam(sys_get_temp_dir(), 'snodcron');
        if ($tmp === false) {
            return false;
        }

        // crontab expects a trailing newline; an empty body clears the crontab.
        $body = rtrim((string) $content);
        file_put_contents($tmp, $body === '' ? "\n" : ($body . "\n"));

        $out = @shell_exec('crontab ' . escapeshellarg($tmp) . ' 2>&1; echo "___EXIT:$?"');
        @unlink($tmp);

        return $out !== null && preg_match('/___EXIT:0/', $out) === 1;
    }

    /**
     * @param string $level
     * @param string $message
     * @param array $context
     *
     * @return void
     */
    private function log($level, $message, array $context)
    {
        if ($this->logger === null) {
            return;
        }

        $this->logger->{$level}($message, $context, null, self::LOG_CHANNEL);
    }
}
