<?php
/**
 * Stock & Availability Service
 * 
 * Manages live stock checking, state tracking (available, unavailable, checking, unknown),
 * and pre-order availability validation.
 */

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use Exception;

class StockService {
    /**
     * Check if a specific service & country pair is available for immediate purchase
     */
    public static function checkAvailability(int $serviceId, int $countryId): array {
        $pivot = Database::queryOne("
            SELECT ps.*, p.code as provider_code, p.is_active as provider_active,
                   s.name as service_name, c.name as country_name
            FROM provider_services ps
            JOIN providers p ON p.id = ps.provider_id
            JOIN services s ON s.id = ps.service_id
            JOIN countries c ON c.id = ps.country_id
            WHERE ps.service_id = :sid AND ps.country_id = :cid AND ps.is_active = 1
            LIMIT 1
        ", [':sid' => $serviceId, ':cid' => $countryId]);

        if (!$pivot) {
            return [
                'available' => false,
                'status' => 'unavailable',
                'reason' => 'Service not currently supported in selected country.',
            ];
        }

        if (empty($pivot['provider_active'])) {
            return [
                'available' => false,
                'status' => 'unavailable',
                'reason' => 'Provider is currently undergoing maintenance.',
            ];
        }

        return [
            'available' => $pivot['stock_status'] === 'available',
            'status' => $pivot['stock_status'],
            'selling_price' => (float) $pivot['selling_price'],
            'pivot_id' => (int) $pivot['id'],
            'provider_id' => (int) $pivot['provider_id'],
            'provider_code' => $pivot['provider_code'],
            'provider_service_code' => $pivot['provider_service_code'],
            'provider_cost' => (float) $pivot['provider_cost'],
        ];
    }

    /**
     * Update stock status from provider check
     */
    public static function updateStockStatus(int $pivotId, string $status, int $quantity = 0): void {
        Database::execute("
            UPDATE provider_services
            SET stock_status = :status, stock_quantity = :qty, last_synced_at = CURRENT_TIMESTAMP
            WHERE id = :id
        ", [
            ':status' => $status,
            ':qty' => $quantity,
            ':id' => $pivotId,
        ]);
    }
}
