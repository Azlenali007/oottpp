<?php
/**
 * Cron Job: Daily Financial & Volume Summary
 * 
 * Usage: php cron/daily_reports.php
 * Schedule: Daily at midnight (0 0 * * *)
 */

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

use App\Core\Database;
use App\Helpers\Logger;

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit("CLI access only.\n");
}

echo "[" . date('Y-m-d H:i:s') . "] Compiling daily analytics summary...\n";

try {
    $stats = Database::queryOne("
        SELECT 
            COUNT(*) as orders_count,
            COALESCE(SUM(total_amount), 0) as gross_revenue,
            COALESCE(SUM(provider_cost), 0) as provider_cost
        FROM orders
        WHERE created_at >= date('now', '-1 day')
    ");

    $revenue = (float) ($stats['gross_revenue'] ?? 0);
    $cost = (float) ($stats['provider_cost'] ?? 0);
    $profit = max(0.0, $revenue - $cost);

    Logger::info('cron', "Daily Report", [
        'orders' => $stats['orders_count'] ?? 0,
        'revenue' => $revenue,
        'profit' => $profit,
    ]);

    echo "[COMPLETED] Orders: {$stats['orders_count']}, Revenue: ₹{$revenue}, Profit: ₹{$profit}\n";
} catch (Exception $e) {
    Logger::error('cron', "daily_reports error: " . $e->getMessage());
}
