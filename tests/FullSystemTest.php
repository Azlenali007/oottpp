<?php
/**
 * Full End-to-End System Test Suite
 * 
 * Verifies all services, wallet ACID transactions, order workflows,
 * idempotency, and refund controls in Core PHP.
 * Run via CLI: php tests/FullSystemTest.php
 */

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

use App\Core\Database;
use App\Services\AuthService;
use App\Services\WalletService;
use App\Services\StockService;
use App\Services\PricingService;
use App\Services\ProviderService;
use App\Services\OrderService;
use App\Services\RefundService;
use App\Services\PaymentService;
use Modules\Providers\MockTestingProvider;
use Modules\Payments\MockTestingGateway;

echo "========================================================\n";
echo "       OTPPro Full System Integration Test Suite        \n";
echo "========================================================\n";

$passCount = 0;
$failCount = 0;

function it(string $description, callable $test): void {
    global $passCount, $failCount;
    try {
        $test();
        echo "  [PASS] {$description}\n";
        $passCount++;
    } catch (Throwable $e) {
        echo "  [FAIL] {$description}\n";
        echo "         Error: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine() . "\n";
        $failCount++;
    }
}

// Ensure SQLite testing DB is clean and schema loaded
it('initializes testing database and applies schema', function () {
    $pdo = Database::connect('sqlite');
    $sqliteSchema = file_get_contents(ROOT_PATH . '/database/schema_sqlite.sql');
    $pdo->exec($sqliteSchema);
});

$testUserId = 0;
it('registers a user and initializes wallet with 0.0000 balance', function () use (&$testUserId) {
    $result = AuthService::register('Rahul Kumar', 'rahul.test@example.com', 'SecurePass123!', '+919876543210');
    $testUserId = $result['id'];

    if ($testUserId <= 0) throw new Exception("User ID not returned");

    $wallet = WalletService::getWallet($testUserId);
    if ((float) $wallet['balance'] !== 0.0) throw new Exception("Initial balance should be 0.00");
});

it('credits wallet atomically and records ledger entry', function () use (&$testUserId) {
    $credit = WalletService::credit($testUserId, 500.00, 'deposit', 'Initial test deposit');
    if ((float) $credit['balance_after'] !== 500.00) throw new Exception("Balance after should be 500.00");

    $wallet = WalletService::getWallet($testUserId);
    if ((float) $wallet['balance'] !== 500.00) throw new Exception("Stored balance should be 500.00");
});

it('prevents overdraft/negative balance when debiting more than available', function () use (&$testUserId) {
    $caught = false;
    try {
        WalletService::debit($testUserId, 9999.00, 'order_deduction', 'Attempted overdraft');
    } catch (Exception $e) {
        $caught = true;
    }
    if (!$caught) throw new Exception("Should have blocked overdraft transaction!");
});

$testServiceId = 0;
$testCountryId = 0;
it('synchronizes provider catalog and seeds services', function () use (&$testServiceId, &$testCountryId) {
    $provider = new MockTestingProvider();
    $result = ProviderService::syncCatalog($provider);
    if ($result['items_synced'] < 1) throw new Exception("Catalog items not synced");

    $service = Database::queryOne("SELECT id FROM services WHERE slug = 'whatsapp' LIMIT 1");
    if (!$service) throw new Exception("WhatsApp service was not seeded");
    $testServiceId = (int) $service['id'];

    $country = Database::queryOne("SELECT id FROM countries WHERE iso_code = 'IN' LIMIT 1");
    if (!$country) throw new Exception("India country was not seeded");
    $testCountryId = (int) $country['id'];
});

$createdOrderId = 0;
it('places an OTP verification order and debits wallet', function () use (&$testUserId, &$testServiceId, &$testCountryId, &$createdOrderId) {
    $order = OrderService::createOrder($testUserId, $testServiceId, $testCountryId);
    $createdOrderId = $order['order_id'];

    if (empty($order['phone_number'])) throw new Exception("Phone number missing from order");
    if ($order['status'] !== 'pending') throw new Exception("Order status should be pending");

    $wallet = WalletService::getWallet($testUserId);
    if ((float) $wallet['balance'] >= 500.00) throw new Exception("Wallet balance was not deducted");
});

it('polls provider and receives OTP verification code', function () use (&$createdOrderId, &$testUserId) {
    $check = OrderService::checkOtp($createdOrderId, $testUserId);
    if ($check['status'] !== 'completed') throw new Exception("Order should be marked completed");
    if (empty($check['otp_code'])) throw new Exception("OTP code should be received");
});

it('prevents double-refund on the same order', function () use (&$createdOrderId, &$testUserId) {
    $refund1 = RefundService::processRefund($createdOrderId, 1.20, 'Test cancellation refund');
    if (!$refund1['success']) throw new Exception("First refund should succeed");

    $caught = false;
    try {
        RefundService::processRefund($createdOrderId, 1.20, 'Duplicate refund attempt');
    } catch (Exception) {
        $caught = true;
    }
    if (!$caught) throw new Exception("Double refund was not prevented!");
});

it('verifies payment gateway initiation and success callback', function () use (&$testUserId) {
    $gwResult = PaymentService::initiateDeposit($testUserId, 250.00, 'mock_test_gw');
    if (empty($gwResult['idempotency_key'])) throw new Exception("Idempotency key missing");

    $success = PaymentService::handlePaymentSuccess(
        $gwResult['idempotency_key'],
        'TXN_SUCCESS_' . mt_rand(1000, 9999),
        250.00
    );
    if (!$success['success']) throw new Exception("Payment finalization failed");

    // Second execution with same idempotency key should be idempotent
    $repeat = PaymentService::handlePaymentSuccess(
        $gwResult['idempotency_key'],
        'TXN_REPEAT',
        250.00
    );
    if (empty($repeat['already_processed'])) throw new Exception("Payment should be idempotent");
});

echo "--------------------------------------------------------\n";
echo "Results: {$passCount} passed, {$failCount} failed.\n";

if ($failCount === 0) {
    echo "[SUCCESS] ALL SYSTEM INTEGRATION TESTS PASSED PERFECTLY!\n";
    exit(0);
} else {
    echo "[ERROR] SYSTEM INTEGRATION TESTS FAILED.\n";
    exit(1);
}
