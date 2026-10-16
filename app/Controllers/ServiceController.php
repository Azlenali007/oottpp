<?php
/**
 * Services Catalog Controller
 * 
 * Lists available verification services, category filters, and live country pricing.
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Session;
use App\Core\View;
use App\Services\AuthService;
use App\Services\PricingService;
use App\Core\Database;
use App\Helpers\ResponseHelper;

class ServiceController {
    public function index(Request $request): void {
        $user = AuthService::user();
        $category = $request->query('category');
        $search = $request->query('search');

        $sql = "
            SELECT s.id, s.slug, s.name, s.category, s.icon, s.is_popular,
                   s.default_selling_price as min_price, s.delivery_time_est, s.success_rate_est,
                   COALESCE(
                       (SELECT stock_status FROM provider_services ps WHERE ps.service_id = s.id AND ps.is_active = 1 LIMIT 1),
                       'available'
                   ) as stock_status,
                   COALESCE(
                       (SELECT stock_quantity FROM provider_services ps WHERE ps.service_id = s.id AND ps.is_active = 1 LIMIT 1),
                       100
                   ) as stock_quantity
            FROM services s
            WHERE s.is_active = 1
        ";

        $params = [];
        if ($category && $category !== 'All') {
            $sql .= " AND s.category = :category";
            $params[':category'] = $category;
        }

        if ($search) {
            $sql .= " AND (s.name LIKE :search OR s.slug LIKE :search)";
            $params[':search'] = '%' . $search . '%';
        }

        $sql .= " ORDER BY s.is_popular DESC, s.sort_order ASC, s.name ASC";

        $services = Database::query($sql, $params);

        if ($request->isAjax()) {
            ResponseHelper::success(['services' => $services]);
            return;
        }

        echo View::render('user/services', [
            'title' => 'Services Catalog - OTPPro',
            'user' => $user,
            'services' => $services,
            'selectedCategory' => $category ?? 'All',
            'searchQuery' => $search ?? '',
            'csrf_token' => Session::getCsrfToken(),
        ], 'layouts/user');
    }

    public function getServiceCountries(Request $request, string $serviceId): void {
        $countries = PricingService::getSupportedCountriesForService((int) $serviceId);
        ResponseHelper::success(['countries' => $countries]);
    }
}
