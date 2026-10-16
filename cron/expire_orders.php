<?php
/**
 * Cron Job: Expire Unfulfilled Orders & Trigger Automated Refunds
 * 
 * Usage: php cron/expire_orders.php
 * Schedule: Every 2 minutes (*\/2 * * * *)
 */

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

use App\Core\Database;
use App\Services\RefundService;
use App\Services\ProviderService;
use App\Helpers\Logger;

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit("CLI access only.\n");
}

$lockFile = STORAGE_PATH . '/cache/cron_expire_orders.lock';
$fp = fopen($lockFile, 'c+');

if (!flock($fp, LOCK_EX | LOCK_NB)) {
    exit(0);
}

try {
    // Orders pending past expiration time
    $expiredOrders = Database::query("
        SELECT o.id, o.order_number, o.total_amount, o.provider_order_id, p.code as provider_code
        FROM orders o
        JOIN providers p ON p.id = o.provider_id
        WHERE o.status = 'pending' AND o.expires_at <= CURRENT_TIMESTAMP
        LIMIT 50
    ");

    $expiredCount = 0;
    foreach ($expiredOrders as $ord) {
        // Cancel with provider if needed
        try {
            $provider = ProviderService::getActiveProvider($ord['provider_code']);
            $provider->cancelOrder($ord['provider_order_id'] ?? '');
        } catch (Exception) {}

        // Automatic refund
        RefundService::processRefund(
            (int) $ord['id'],
            (float) $ord['total_amount'],
            'Automated refund: Order expired without OTP delivery.'
        );

        $expiredCount++;
        echo "[EXPIRED & REFUNDED] Order {$ord['order_number']}\n";
    }

    echo "[COMPLETED] Processed {$expiredCount} expired orders.\n";
} catch (Exception $e) {
    Logger::error('cron', "expire_orders failed: " . $e->getMessage());
} finally {
    flock($fp, LOCK_UN);
    fclose($fp);
}
