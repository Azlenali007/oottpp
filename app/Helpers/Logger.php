<?php
/**
 * Application Logger
 * 
 * Provides channel-based logging to storage/logs/.
 */

declare(strict_types=1);

namespace App\Helpers;

class Logger {
    public static function log(string $channel, string $level, string $message, array $context = []): void {
        $logFile = STORAGE_PATH . "/logs/{$channel}.log";
        $timestamp = date('Y-m-d H:i:s');
        $contextStr = !empty($context) ? ' ' . json_encode($context, JSON_UNESCAPED_SLASHES) : '';
        $entry = sprintf("[%s] [%s] %s%s\n", $timestamp, strtoupper($level), $message, $contextStr);
        @error_log($entry, 3, $logFile);
    }

    public static function info(string $channel, string $message, array $context = []): void {
        self::log($channel, 'info', $message, $context);
    }

    public static function warning(string $channel, string $message, array $context = []): void {
        self::log($channel, 'warning', $message, $context);
    }

    public static function error(string $channel, string $message, array $context = []): void {
        self::log($channel, 'error', $message, $context);
    }

    public static function audit(int $adminId, string $action, ?string $targetType = null, ?string $targetId = null, array $details = []): void {
        self::log('audit', 'audit', "Admin [{$adminId}] executed [{$action}] on [{$targetType}:{$targetId}]", $details);
    }
}
