<?php
/**
 * Cron Job: Refresh Stock & Availability
 * 
 * Usage: php cron/sync_stock.php
 * Schedule: Every 10 minutes (*\/10 * * * *)
 */

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

use App\Core\Database;
use App\Services\ProviderService;
use App\Services\StockService;
use App\Helpers\Logger;

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit("CLI access only.\n");
}

$lockFile = STORAGE_PATH . '/cache/cron_sync_stock.lock';
$fp = fopen($lockFile, 'c+');

if (!flock($fp, LOCK_EX | LOCK_NB)) {
    echo "Sync stock is already running. Skipping.\n";
    exit(0);
}

echo "[" . date('Y-m-d H:i:s') . "] Starting stock status check...\n";

try {
    $provider = ProviderService::getActiveProvider();
    
    // Select active provider_services
    $pivots = Database::query("
        SELECT ps.id, ps.provider_service_code, c.iso_code
        FROM provider_services ps
        JOIN countries c ON c.id = ps.country_id
        WHERE ps.is_active = 1
        LIMIT 50
    ");

    $updated = 0;
    foreach ($pivots as $p) {
        $check = $provider->checkStock($p['provider_service_code'], $p['iso_code']);
        StockService::updateStockStatus((int) $p['id'], $check['status'], (int) ($check['quantity'] ?? 0));
        $updated++;
    }

    echo "[SUCCESS] Stock checked and updated for {$updated} services.\n";
} catch (Exception $e) {
    echo "[ERROR] Stock sync failed: " . $e->getMessage() . "\n";
    Logger::error('cron', "sync_stock failed: " . $e->getMessage());
} finally {
    flock($fp, LOCK_UN);
    fclose($fp);
}
