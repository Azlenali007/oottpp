<?php
/**
 * Cron Job: Poll Pending Orders for OTP Delivery
 * 
 * Usage: php cron/check_orders.php
 * Schedule: Every minute (* * * * *)
 */

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

use App\Core\Database;
use App\Services\OrderService;
use App\Helpers\Logger;

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit("CLI access only.\n");
}

$lockFile = STORAGE_PATH . '/cache/cron_check_orders.lock';
$fp = fopen($lockFile, 'c+');

if (!flock($fp, LOCK_EX | LOCK_NB)) {
    exit(0);
}

try {
    // Find up to 30 pending unexpired orders
    $pendingOrders = Database::query("
        SELECT id, order_number FROM orders
        WHERE status = 'pending' AND expires_at > CURRENT_TIMESTAMP
        ORDER BY created_at ASC
        LIMIT 30
    ");

    $completedCount = 0;
    foreach ($pendingOrders as $ord) {
        $check = OrderService::checkOtp((int) $ord['id']);
        if ($check['status'] === 'completed') {
            $completedCount++;
            echo "[OTP RECEIVED] Order {$ord['order_number']}: {$check['otp_code']}\n";
        }
    }

    echo "[COMPLETED] Processed " . count($pendingOrders) . " orders. Received OTPs: {$completedCount}.\n";
} catch (Exception $e) {
    Logger::error('cron', "check_orders failed: " . $e->getMessage());
} finally {
    flock($fp, LOCK_UN);
    fclose($fp);
}
