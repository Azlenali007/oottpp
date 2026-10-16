<?php
/**
 * User & Super Admin Authentication Service
 * 
 * Manages registration, credential verification, session lifecycle,
 * and profile management.
 */

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\Session;
use App\Helpers\Security;
use App\Helpers\Logger;
use Exception;

class AuthService {
    /**
     * Register a new user and initialize their empty wallet
     */
    public static function register(string $name, string $email, string $password, ?string $phone = null): array {
        $email = strtolower(trim($email));

        // Check for duplicate account
        $existing = Database::queryOne("SELECT id FROM users WHERE email = :email LIMIT 1", [':email' => $email]);
        if ($existing) {
            throw new Exception("An account with this email address already exists.");
        }

        $passwordHash = Security::hashPassword($password);

        return Database::transaction(function ($pdo) use ($name, $email, $passwordHash, $phone) {
            $stmt = $pdo->prepare("
                INSERT INTO users (name, email, phone, password_hash, role, status, created_at, updated_at)
                VALUES (:name, :email, :phone, :password_hash, 'user', 'active', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
            ");
            $stmt->execute([
                ':name' => $name,
                ':email' => $email,
                ':phone' => $phone,
                ':password_hash' => $passwordHash,
            ]);

            $userId = (int) $pdo->lastInsertId();

            // Initialize associated wallet
            $walletStmt = $pdo->prepare("
                INSERT INTO wallets (user_id, balance, currency, locked_balance, total_deposited, total_spent)
                VALUES (:user_id, 0.0000, 'INR', 0.0000, 0.0000, 0.0000)
            ");
            $walletStmt->execute([':user_id' => $userId]);

            // Welcome Notification
            NotificationService::send(
                $userId,
                "Welcome to OTPPro!",
                "Your account has been successfully created. Add funds to your wallet to start ordering OTP verifications.",
                'success'
            );

            Logger::info('auth', "User registered successfully", ['user_id' => $userId, 'email' => $email]);

            return [
                'id' => $userId,
                'name' => $name,
                'email' => $email,
                'role' => 'user',
            ];
        });
    }

    /**
     * Authenticate user credentials and establish native session
     */
    public static function login(string $email, string $password, bool $adminOnly = false): array {
        $email = strtolower(trim($email));

        $user = Database::queryOne("SELECT * FROM users WHERE email = :email LIMIT 1", [':email' => $email]);
        if (!$user) {
            throw new Exception("Invalid email or password.");
        }

        if ($user['status'] !== 'active') {
            throw new Exception("Your account is currently " . $user['status'] . ". Please contact support.");
        }

        if ($adminOnly && $user['role'] !== 'super_admin') {
            throw new Exception("Administrative credentials required.");
        }

        if (!Security::verifyPassword($password, $user['password_hash'])) {
            throw new Exception("Invalid email or password.");
        }

        // Regenerate Session ID to mitigate session fixation attacks
        Session::regenerate();
        Session::set('user_id', (int) $user['id']);
        Session::set('user_name', $user['name']);
        Session::set('user_email', $user['email']);
        Session::set('user_role', $user['role']);
        Session::set('logged_in_at', time());

        // Update login stats
        Database::execute("
            UPDATE users SET last_login_at = CURRENT_TIMESTAMP, last_login_ip = :ip WHERE id = :id
        ", [
            ':ip' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
            ':id' => $user['id'],
        ]);

        Logger::info('auth', "User logged in", ['user_id' => $user['id'], 'role' => $user['role']]);

        return $user;
    }

    /**
     * Terminate the session securely
     */
    public static function logout(): void {
        $userId = Session::get('user_id');
        if ($userId) {
            Logger::info('auth', "User logged out", ['user_id' => $userId]);
        }
        Session::destroy();
    }

    /**
     * Get current authenticated user profile
     */
    public static function user(): ?array {
        $userId = Session::get('user_id');
        if (!$userId) {
            return null;
        }

        return Database::queryOne("
            SELECT u.id, u.name, u.email, u.phone, u.role, u.status, u.created_at,
                   w.balance, w.currency
            FROM users u
            LEFT JOIN wallets w ON w.user_id = u.id
            WHERE u.id = :id
            LIMIT 1
        ", [':id' => $userId]);
    }

    /**
     * Change user password securely
     */
    public static function changePassword(int $userId, string $currentPassword, string $newPassword): void {
        $user = Database::queryOne("SELECT password_hash FROM users WHERE id = :id LIMIT 1", [':id' => $userId]);
        if (!$user || !Security::verifyPassword($currentPassword, $user['password_hash'])) {
            throw new Exception("Current password verification failed.");
        }

        $newHash = Security::hashPassword($newPassword);
        Database::execute("UPDATE users SET password_hash = :hash WHERE id = :id", [
            ':hash' => $newHash,
            ':id' => $userId,
        ]);

        Logger::info('auth', "Password changed", ['user_id' => $userId]);
    }
}
