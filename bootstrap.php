<?php
/**
 * OTPPro Application Bootstrapper
 * 
 * Core PHP 8.2+ bootstrap runtime.
 * Sets strict error reporting, custom error/exception handlers,
 * environment parser, and PSR-4 style autoloader without third-party packages.
 */

declare(strict_types=1);

// Define Base Paths
define('ROOT_PATH', realpath(__DIR__));
define('APP_PATH', ROOT_PATH . '/app');
define('CONFIG_PATH', ROOT_PATH . '/config');
define('STORAGE_PATH', ROOT_PATH . '/storage');
define('MODULES_PATH', ROOT_PATH . '/modules');
define('VIEWS_PATH', ROOT_PATH . '/views');

// Ensure storage directories exist
$requiredStorageDirs = [
    STORAGE_PATH . '/logs',
    STORAGE_PATH . '/cache',
    STORAGE_PATH . '/database',
];
foreach ($requiredStorageDirs as $dir) {
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
}

// ----------------------------------------------------------------------------
// Simple Custom .env Parser (Core PHP, zero external dependencies)
// ----------------------------------------------------------------------------
function loadEnvironment(string $filePath): void {
    if (!file_exists($filePath)) {
        return;
    }

    $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
        return;
    }

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }

        $parts = explode('=', $line, 2);
        if (count($parts) === 2) {
            $key = trim($parts[0]);
            $val = trim($parts[1]);

            // Strip enclosing quotes if present
            if ((str_starts_with($val, '"') && str_ends_with($val, '"')) ||
                (str_starts_with($val, "'") && str_ends_with($val, "'"))) {
                $val = substr($val, 1, -1);
            }

            if (!array_key_exists($key, $_SERVER) && !array_key_exists($key, $_ENV)) {
                putenv("{$key}={$val}");
                $_ENV[$key] = $val;
                $_SERVER[$key] = $val;
            }
        }
    }
}

// Helper to retrieve environment variable with default fallback
function env(string $key, mixed $default = null): mixed {
    $val = getenv($key);
    if ($val === false) {
        return $_ENV[$key] ?? $_SERVER[$key] ?? $default;
    }

    return match (strtolower($val)) {
        'true', '(true)' => true,
        'false', '(false)' => false,
        'null', '(null)' => null,
        'empty', '(empty)' => '',
        default => $val,
    };
}

// Load .env file
loadEnvironment(ROOT_PATH . '/.env');

// Set timezone (default UTC)
$tz = env('APP_TIMEZONE', 'UTC');
date_default_timezone_set($tz);

// Error reporting settings based on environment
$isDev = env('APP_ENV', 'production') === 'development' || env('APP_DEBUG', false);
if ($isDev) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
} else {
    error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
    ini_set('error_log', STORAGE_PATH . '/logs/error.log');
}

// ----------------------------------------------------------------------------
// Custom Error and Exception Handlers
// ----------------------------------------------------------------------------
set_error_handler(function (int $severity, string $message, string $file, int $line): bool {
    if (!(error_reporting() & $severity)) {
        return false;
    }
    $logMsg = sprintf("[%s] PHP Error [%d]: %s in %s on line %d%s", date('Y-m-d H:i:s'), $severity, $message, $file, $line, PHP_EOL);
    @error_log($logMsg, 3, STORAGE_PATH . '/logs/app.log');
    if (env('APP_DEBUG', false)) {
        echo "<pre style='color:red;'><b>Error:</b> {$message} in {$file}:{$line}</pre>";
    }
    return true;
});

set_exception_handler(function (Throwable $e): void {
    $logMsg = sprintf("[%s] Uncaught Exception: %s in %s:%d\nStack trace:\n%s\n", date('Y-m-d H:i:s'), $e->getMessage(), $e->getFile(), $e->getLine(), $e->getTraceAsString());
    @error_log($logMsg, 3, STORAGE_PATH . '/logs/exception.log');

    if (php_sapi_name() === 'cli') {
        fwrite(STDERR, "Uncaught Exception: " . $e->getMessage() . PHP_EOL);
        fwrite(STDERR, $e->getTraceAsString() . PHP_EOL);
        exit(1);
    }

    http_response_code(500);
    if (env('APP_DEBUG', false)) {
        echo "<div style='font-family:sans-serif;padding:24px;background:#fff1f2;border:1px solid #fecdd3;color:#9f1239;margin:20px;border-radius:8px;'>";
        echo "<h2>Application Error</h2>";
        echo "<p><b>" . htmlspecialchars($e->getMessage()) . "</b></p>";
        echo "<p><small>in " . htmlspecialchars($e->getFile()) . " on line " . $e->getLine() . "</small></p>";
        echo "<pre style='background:#ffffff;padding:12px;border:1px solid #e2e8f0;border-radius:4px;overflow:auto;font-size:12px;'>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
        echo "</div>";
    } else {
        echo "<h1>500 Internal Server Error</h1><p>An unexpected error occurred. Please try again later.</p>";
    }
    exit(1);
});

// ----------------------------------------------------------------------------
// Core PSR-4 Compliant Autoloader
// ----------------------------------------------------------------------------
spl_autoload_register(function (string $class): void {
    $prefixes = [
        'App\\' => APP_PATH . '/',
        'Modules\\' => MODULES_PATH . '/',
    ];

    foreach ($prefixes as $prefix => $baseDir) {
        $len = strlen($prefix);
        if (strncmp($prefix, $class, $len) !== 0) {
            continue;
        }

        $relativeClass = substr($class, $len);
        $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

return true;
