<?php
/**
 * Native Session Wrapper
 * 
 * Enforces secure cookie policies, session ID regeneration,
 * flash message storage, and CSRF token persistence.
 */

declare(strict_types=1);

namespace App\Core;

class Session {
    private static bool $started = false;

    public static function start(): void {
        if (self::$started || session_status() === PHP_SESSION_ACTIVE) {
            self::$started = true;
            return;
        }

        $security = require CONFIG_PATH . '/security.php';
        $sessConfig = $security['session'] ?? [];

        ini_set('session.use_only_cookies', '1');
        ini_set('session.use_strict_mode', '1');
        ini_set('session.cookie_httponly', '1');
        ini_set('session.cookie_samesite', $sessConfig['samesite'] ?? 'Strict');
        if (!empty($sessConfig['secure'])) {
            ini_set('session.cookie_secure', '1');
        }

        session_name($sessConfig['name'] ?? 'OTPPRO_SESSID');
        session_start();
        self::$started = true;
    }

    public static function set(string $key, mixed $value): void {
        self::start();
        $_SESSION[$key] = $value;
    }

    public static function get(string $key, mixed $default = null): mixed {
        self::start();
        return $_SESSION[$key] ?? $default;
    }

    public static function has(string $key): bool {
        self::start();
        return isset($_SESSION[$key]);
    }

    public static function remove(string $key): void {
        self::start();
        unset($_SESSION[$key]);
    }

    public static function regenerate(): void {
        self::start();
        session_regenerate_id(true);
    }

    public static function destroy(): void {
        self::start();
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }
        session_destroy();
        self::$started = false;
    }

    public static function setFlash(string $type, string $message): void {
        self::start();
        $_SESSION['_flash'][$type] = $message;
    }

    public static function getFlash(string $type): ?string {
        self::start();
        if (isset($_SESSION['_flash'][$type])) {
            $msg = $_SESSION['_flash'][$type];
            unset($_SESSION['_flash'][$type]);
            return $msg;
        }
        return null;
    }

    public static function generateCsrfToken(): string {
        self::start();
        $token = bin2hex(random_bytes(32));
        $_SESSION['_csrf_token'] = $token;
        $_SESSION['_csrf_token_time'] = time();
        return $token;
    }

    public static function getCsrfToken(): string {
        self::start();
        if (empty($_SESSION['_csrf_token'])) {
            return self::generateCsrfToken();
        }
        return $_SESSION['_csrf_token'];
    }

    public static function validateCsrfToken(?string $token): bool {
        self::start();
        if (empty($token) || empty($_SESSION['_csrf_token'])) {
            return false;
        }
        return hash_equals($_SESSION['_csrf_token'], $token);
    }
}
