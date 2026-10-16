<?php
/**
 * Security & Encryption Helper
 * 
 * Cryptographic utilities, password hashing, AES-256-CBC encryption for provider keys,
 * and input sanitization.
 */

declare(strict_types=1);

namespace App\Helpers;

class Security {
    private static string $cipher = 'aes-256-cbc';

    public static function hashPassword(string $password): string {
        $securityConfig = require CONFIG_PATH . '/security.php';
        return password_hash(
            $password,
            $securityConfig['password']['algo'] ?? PASSWORD_BCRYPT,
            $securityConfig['password']['options'] ?? ['cost' => 12]
        );
    }

    public static function verifyPassword(string $password, string $hash): bool {
        return password_verify($password, $hash);
    }

    public static function needsRehash(string $hash): bool {
        $securityConfig = require CONFIG_PATH . '/security.php';
        return password_needs_rehash(
            $hash,
            $securityConfig['password']['algo'] ?? PASSWORD_BCRYPT,
            $securityConfig['password']['options'] ?? ['cost' => 12]
        );
    }

    public static function encrypt(string $plainText, ?string $key = null): string {
        $secretKey = $key ?? env('APP_KEY', 'otppro_super_secret_master_key_32bytes!!');
        $keyHash = hash('sha256', $secretKey, true);
        $iv = random_bytes(openssl_cipher_iv_length(self::$cipher));
        $encrypted = openssl_encrypt($plainText, self::$cipher, $keyHash, 0, $iv);
        return base64_encode($iv . '::' . $encrypted);
    }

    public static function decrypt(string $cipherText, ?string $key = null): ?string {
        $secretKey = $key ?? env('APP_KEY', 'otppro_super_secret_master_key_32bytes!!');
        $keyHash = hash('sha256', $secretKey, true);
        $decoded = base64_decode($cipherText, true);
        if ($decoded === false || !str_contains($decoded, '::')) {
            return null;
        }

        [$iv, $encrypted] = explode('::', $decoded, 2);
        $decrypted = openssl_decrypt($encrypted, self::$cipher, $keyHash, 0, $iv);
        return $decrypted !== false ? $decrypted : null;
    }

    public static function sanitize(mixed $data): mixed {
        if (is_array($data)) {
            foreach ($data as $k => $v) {
                $data[$k] = self::sanitize($v);
            }
            return $data;
        }
        if (is_string($data)) {
            return trim(strip_tags($data));
        }
        return $data;
    }

    public static function generateUuid(): string {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // set version to 0100
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // set bits 6-7 to 10
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    public static function generateOrderNumber(): string {
        return '#ORD' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 6));
    }

    public static function generateTicketNumber(): string {
        return '#TKT' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 6));
    }
}
