<?php
/**
 * Super Admin CLI Setup & Seeding Script
 * 
 * Enforces the strict rule: EXACTLY ONE SUPER ADMIN ACCOUNT.
 * Usage:
 *   php database/seed_super_admin.php [email] [password] [name]
 * Or via environment variables:
 *   SUPER_ADMIN_EMAIL, SUPER_ADMIN_PASSWORD, SUPER_ADMIN_NAME
 */

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

use App\Core\Database;

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    echo "Forbidden: This script can only be run from the command line interface (CLI).\n";
    exit(1);
}

echo "========================================================\n";
echo "       OTPPro Platform - Super Admin Setup CLI          \n";
echo "========================================================\n";

// Load configuration
$dbConfig = require CONFIG_PATH . '/database.php';
$securityConfig = require CONFIG_PATH . '/security.php';

// Establish PDO Connection
try {
    $driver = $dbConfig['default'];
    $conn = $dbConfig['connections'][$driver];

    if ($driver === 'sqlite') {
        $dsn = "sqlite:" . $conn['database'];
        $pdo = new PDO($dsn, null, null, $conn['options']);
    } else {
        try {
            $dsn = sprintf(
                "mysql:host=%s;port=%d;dbname=%s;charset=%s",
                $conn['host'],
                $conn['port'],
                $conn['database'],
                $conn['charset']
            );
            $pdo = new PDO($dsn, $conn['username'], $conn['password'], $conn['options']);
        } catch (PDOException $e) {
            echo "[NOTICE] MySQL connection failed (" . $e->getMessage() . "). Falling back to SQLite local database.\n";
            $driver = 'sqlite';
            $conn = $dbConfig['connections']['sqlite'];
            $dsn = "sqlite:" . $conn['database'];
            $pdo = new PDO($dsn, null, null, $conn['options']);
        }
    }
} catch (PDOException $e) {
    echo "[ERROR] Database connection failed: " . $e->getMessage() . "\n";
    echo "Please check your .env configuration.\n";
    exit(1);
}

// ----------------------------------------------------------------------------
// RULE CHECK: Enforce Exactly ONE Super Admin
// ----------------------------------------------------------------------------
$checkStmt = $pdo->prepare("SELECT id, email, name, created_at FROM users WHERE role = 'super_admin' LIMIT 1");
$checkStmt->execute();
$existingAdmin = $checkStmt->fetch();

if ($existingAdmin) {
    echo "\n[WARNING] Super Admin account already exists!\n";
    echo "  ID:         " . $existingAdmin['id'] . "\n";
    echo "  Email:      " . $existingAdmin['email'] . "\n";
    echo "  Name:       " . $existingAdmin['name'] . "\n";
    echo "  Created At: " . $existingAdmin['created_at'] . "\n\n";
    echo "System rule: Exactly ONE Super Admin account is permitted. Creation aborted.\n";
    exit(0);
}

// Parse Arguments
$email = $argv[1] ?? env('SUPER_ADMIN_EMAIL', '');
$password = $argv[2] ?? env('SUPER_ADMIN_PASSWORD', '');
$name = $argv[3] ?? env('SUPER_ADMIN_NAME', 'Super Admin');

// Prompt if missing in CLI
if (empty($email)) {
    echo "Enter Super Admin Email: ";
    $email = trim(fgets(STDIN) ?: '');
}

if (empty($password)) {
    echo "Enter Super Admin Password (min 8 characters): ";
    $password = trim(fgets(STDIN) ?: '');
}

// Validation
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo "[ERROR] Invalid email address format: {$email}\n";
    exit(1);
}

if (strlen($password) < $securityConfig['password']['min_length']) {
    echo "[ERROR] Password must be at least {$securityConfig['password']['min_length']} characters long.\n";
    exit(1);
}

// Secure Password Hash using BCRYPT with cost 12
$passwordHash = password_hash(
    $password,
    $securityConfig['password']['algo'],
    $securityConfig['password']['options']
);

// Begin Transaction for Atomic Seeding
$pdo->beginTransaction();

try {
    // 1. Insert Single Super Admin
    $userStmt = $pdo->prepare("
        INSERT INTO users (name, email, password_hash, role, status, email_verified_at, created_at, updated_at)
        VALUES (:name, :email, :password_hash, 'super_admin', 'active', datetime('now'), datetime('now'), datetime('now'))
    ");
    
    // Check if MySQL vs SQLite for datetime function
    if ($driver === 'mysql') {
        $userStmt = $pdo->prepare("
            INSERT INTO users (name, email, password_hash, role, status, email_verified_at, created_at, updated_at)
            VALUES (:name, :email, :password_hash, 'super_admin', 'active', NOW(), NOW(), NOW())
        ");
    }

    $userStmt->execute([
        ':name' => $name,
        ':email' => $email,
        ':password_hash' => $passwordHash,
    ]);

    $adminId = (int) $pdo->lastInsertId();

    // 2. Initialize Admin Wallet
    $walletStmt = $pdo->prepare("
        INSERT INTO wallets (user_id, balance, currency, locked_balance, total_deposited, total_spent)
        VALUES (:user_id, 0.0000, 'INR', 0.0000, 0.0000, 0.0000)
    ");
    $walletStmt->execute([':user_id' => $adminId]);

    // 3. Create Audit Log Entry
    $auditStmt = $pdo->prepare("
        INSERT INTO admin_audit_logs (admin_user_id, action, target_type, target_id, details, ip_address, user_agent)
        VALUES (:admin_id, 'SYSTEM_SUPER_ADMIN_SEEDED', 'user', :target_id, :details, '127.0.0.1', 'CLI-Setup-Script')
    ");
    $auditStmt->execute([
        ':admin_id' => $adminId,
        ':target_id' => (string) $adminId,
        ':details' => json_encode(['email' => $email, 'name' => $name, 'seeded_via' => 'CLI']),
    ]);

    $pdo->commit();

    echo "\n[SUCCESS] Super Admin account created successfully!\n";
    echo "  Email: " . $email . "\n";
    echo "  Role:  super_admin (Exclusive Master Account)\n";
    echo "  ID:    " . $adminId . "\n";
    echo "========================================================\n";

} catch (Exception $e) {
    $pdo->rollBack();
    echo "[ERROR] Failed to seed Super Admin: " . $e->getMessage() . "\n";
    exit(1);
}
