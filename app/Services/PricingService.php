<?php
/**
 * Pricing Service
 * 
 * Computes selling prices, discounts, and currency formatting.
 */

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

class PricingService {
    public static function format(float $amount, string $symbol = '₹'): string {
        return $symbol . ' ' . number_format($amount, 2);
    }

    public static function getActiveServicesWithPrices(): array {
        return Database::query("
            SELECT s.id, s.slug, s.name, s.category, s.icon, s.is_popular,
                   s.default_selling_price as min_price,
                   s.delivery_time_est, s.success_rate_est,
                   COALESCE(
                       (SELECT stock_status FROM provider_services ps WHERE ps.service_id = s.id AND ps.is_active = 1 LIMIT 1),
                       'available'
                   ) as stock_status
            FROM services s
            WHERE s.is_active = 1
            ORDER BY s.is_popular DESC, s.sort_order ASC, s.name ASC
        ");
    }

    public static function getSupportedCountriesForService(int $serviceId): array {
        return Database::query("
            SELECT c.id, c.iso_code, c.name, c.dial_code, c.flag_emoji,
                   ps.selling_price, ps.stock_status, ps.stock_quantity
            FROM provider_services ps
            JOIN countries c ON c.id = ps.country_id
            WHERE ps.service_id = :sid AND ps.is_active = 1 AND c.is_active = 1
            ORDER BY c.name ASC
        ", [':sid' => $serviceId]);
    }
}
