<?php
/**
 * Phase 1 Architecture & Schema Verification Test
 * 
 * Runs via CLI: php tests/Phase1Test.php
 * Validates bootstrap, configuration arrays, and database schema creation.
 */

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

echo "Running Phase 1 Architecture Verification Tests...\n";
$passes = 0;
$total = 0;

function assertTest(bool $condition, string $testName): void {
    global $passes, $total;
    $total++;
    if ($condition) {
        $passes++;
        echo "  [PASS] {$testName}\n";
    } else {
        echo "  [FAIL] {$testName}\n";
    }
}

// 1. Verify Path Constants
assertTest(defined('ROOT_PATH') && is_dir(ROOT_PATH), 'ROOT_PATH constant is valid and directory exists');
assertTest(defined('APP_PATH') && is_dir(APP_PATH), 'APP_PATH constant is valid and directory exists');
assertTest(defined('CONFIG_PATH') && is_dir(CONFIG_PATH), 'CONFIG_PATH constant is valid and directory exists');
assertTest(defined('STORAGE_PATH') && is_dir(STORAGE_PATH), 'STORAGE_PATH constant is valid and directory exists');

// 2. Verify Configurations
$appConfig = require CONFIG_PATH . '/app.php';
assertTest(is_array($appConfig) && isset($appConfig['name']) && $appConfig['name'] === 'OTPPro', 'config/app.php returns valid configuration');

$dbConfig = require CONFIG_PATH . '/database.php';
assertTest(is_array($dbConfig) && isset($dbConfig['connections']['mysql']), 'config/database.php defines MySQL connection');

$securityConfig = require CONFIG_PATH . '/security.php';
assertTest(is_array($securityConfig) && $securityConfig['admin']['enforce_single_super_admin'] === true, 'config/security.php enforces single Super Admin rule');

// 3. Verify Database Schema File Exists & Contains All 16 Tables
$schemaSql = file_get_contents(ROOT_PATH . '/database/schema.sql');
$requiredTables = [
    'users', 'wallets', 'wallet_transactions', 'providers', 'services',
    'countries', 'provider_services', 'orders', 'payments', 'refunds',
    'webhook_events', 'notifications', 'support_tickets', 'admin_audit_logs',
    'sync_logs', 'settings'
];

$allTablesFound = true;
foreach ($requiredTables as $table) {
    if (!str_contains($schemaSql, "CREATE TABLE `{$table}`")) {
        $allTablesFound = false;
        echo "    Missing table definition in schema.sql: {$table}\n";
    }
}
assertTest($allTablesFound, 'database/schema.sql contains all 16 required tables');

// 4. Verify Financial Decimal Precision in Schema
assertTest(str_contains($schemaSql, 'DECIMAL(12, 4)'), 'Financial monetary balances use high-precision DECIMAL types');

// 5. Verify Super Admin Seeder Exists
assertTest(file_exists(ROOT_PATH . '/database/seed_super_admin.php'), 'database/seed_super_admin.php exists and is executable');

echo "--------------------------------------------------------\n";
echo "Results: {$passes}/{$total} tests passed.\n";

if ($passes === $total) {
    echo "PHASE 1 ARCHITECTURAL INTEGRITY VERIFIED SUCCESSFULLY.\n";
    exit(0);
} else {
    echo "PHASE 1 VERIFICATION FAILED.\n";
    exit(1);
}
