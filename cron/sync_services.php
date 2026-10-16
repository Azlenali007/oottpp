<?php
/**
 * Cron Job: Sync Services Catalog from Active Provider
 * 
 * Usage: php cron/sync_services.php
 * Schedule: Every hour (0 * * * *)
 */

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

use App\Services\ProviderService;
use App\Helpers\Logger;

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit("CLI access only.\n");
}

$lockFile = STORAGE_PATH . '/cache/cron_sync_services.lock';
$fp = fopen($lockFile, 'c+');

if (!flock($fp, LOCK_EX | LOCK_NB)) {
    echo "[" . date('Y-m-d H:i:s') . "] Task is already running. Skipping execution.\n";
    exit(0);
}

echo "[" . date('Y-m-d H:i:s') . "] Starting provider services synchronization...\n";

try {
    $provider = ProviderService::getActiveProvider();
    $result = ProviderService::syncCatalog($provider);

    echo "[SUCCESS] Synced {$result['items_synced']} services, updated {$result['items_updated']} in {$result['execution_time_ms']}ms.\n";
} catch (Exception $e) {
    echo "[ERROR] Synchronization failed: " . $e->getMessage() . "\n";
    Logger::error('cron', "sync_services cron failed: " . $e->getMessage());
} finally {
    flock($fp, LOCK_UN);
    fclose($fp);
}
