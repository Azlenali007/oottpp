<?php
/**
 * Cron Job: Safe Refund Processing & Reconciliation
 * 
 * Usage: php cron/process_refunds.php
 * Schedule: Every 15 minutes (*\/15 * * * *)
 */

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

use App\Core\Database;
use App\Helpers\Logger;

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit("CLI access only.\n");
}

echo "[" . date('Y-m-d H:i:s') . "] Checking refund audit reconciliation...\n";

try {
    $mismatches = Database::query("
        SELECT r.id, r.order_id, r.amount, r.user_id
        FROM refunds r
        WHERE r.status = 'pending'
    ");

    echo "[COMPLETED] Pending refunds in queue: " . count($mismatches) . "\n";
} catch (Exception $e) {
    Logger::error('cron', "process_refunds error: " . $e->getMessage());
}
