<?php
/**
 * Admin Service Management Controller
 */

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Request;
use App\Core\Session;
use App\Core\View;
use App\Core\Database;
use App\Services\AuthService;
use App\Services\ProviderService;
use App\Helpers\Logger;
use App\Helpers\Validator;
use App\Helpers\ResponseHelper;
use Exception;

class AdminServiceController {
    public function index(Request $request): void {
        $admin = AuthService::user();
        $category = $request->query('category');
        $country = $request->query('country');
        $search = $request->query('search');

        $sql = "
            SELECT s.id, s.name, s.slug, s.category, s.icon, s.is_active,
                   COALESCE(ps.selling_price, s.default_selling_price) as selling_price,
                   COALESCE(ps.provider_cost, 0.00) as provider_cost,
                   COALESCE(ps.stock_status, 'available') as stock_status,
                   COALESCE(ps.stock_quantity, 1000) as stock_quantity,
                   c.name as country_name, c.flag_emoji
            FROM services s
            LEFT JOIN provider_services ps ON ps.service_id = s.id
            LEFT JOIN countries c ON c.id = ps.country_id
            WHERE 1=1
        ";

        $params = [];
        if ($category && $category !== 'all') {
            $sql .= " AND s.category = :category";
            $params[':category'] = $category;
        }

        if ($search) {
            $sql .= " AND (s.name LIKE :search OR s.slug LIKE :search)";
            $params[':search'] = '%' . $search . '%';
        }

        $sql .= " ORDER BY s.name ASC LIMIT 50";

        $services = Database::query($sql, $params);
        $categories = Database::query("SELECT DISTINCT category FROM services ORDER BY category ASC");
        $countries = Database::query("SELECT * FROM countries ORDER BY name ASC");

        if ($request->isAjax()) {
            ResponseHelper::success(['services' => $services]);
            return;
        }

        echo View::render('admin/services', [
            'title' => 'Service Management - Super Admin',
            'admin' => $admin,
            'services' => $services,
            'categories' => $categories,
            'countries' => $countries,
            'csrf_token' => Session::getCsrfToken(),
        ], 'layouts/admin');
    }

    public function syncFromProvider(Request $request): void {
        try {
            $provider = ProviderService::getActiveProvider();
            $result = ProviderService::syncCatalog($provider);

            Logger::audit((int) Session::get('user_id'), 'SYNC_PROVIDER_CATALOG', 'provider', $provider->getCode(), $result);

            ResponseHelper::success($result, 'Service catalog synchronized successfully from provider.');
        } catch (Exception $e) {
            ResponseHelper::error($e->getMessage(), 500);
        }
    }

    public function updatePrice(Request $request, string $serviceId): void {
        $validator = Validator::make($request->input(), [
            'selling_price' => 'required|numeric|min:0.1',
        ]);

        if ($validator->fails()) {
            ResponseHelper::error('Invalid price', 422, $validator->errors());
            return;
        }

        $newPrice = (float) $request->input('selling_price');
        $sid = (int) $serviceId;

        Database::execute("
            UPDATE services SET default_selling_price = :price, updated_at = CURRENT_TIMESTAMP WHERE id = :id
        ", [':price' => $newPrice, ':id' => $sid]);

        Database::execute("
            UPDATE provider_services SET selling_price = :price, updated_at = CURRENT_TIMESTAMP WHERE service_id = :id
        ", [':price' => $newPrice, ':id' => $sid]);

        Logger::audit((int) Session::get('user_id'), 'UPDATE_SERVICE_PRICE', 'service', (string) $sid, ['new_price' => $newPrice]);

        ResponseHelper::success(null, 'Price updated successfully.');
    }

    public function toggleStatus(Request $request, string $serviceId): void {
        $service = Database::queryOne("SELECT id, is_active FROM services WHERE id = :id LIMIT 1", [':id' => (int) $serviceId]);
        if (!$service) {
            ResponseHelper::notFound('Service not found');
            return;
        }

        $newStatus = $service['is_active'] ? 0 : 1;
        Database::execute("UPDATE services SET is_active = :status, updated_at = CURRENT_TIMESTAMP WHERE id = :id", [
            ':status' => $newStatus,
            ':id' => (int) $serviceId,
        ]);

        Logger::audit((int) Session::get('user_id'), 'TOGGLE_SERVICE_STATUS', 'service', $serviceId, ['new_status' => $newStatus]);

        ResponseHelper::success(['is_active' => $newStatus], 'Service status updated.');
    }
}
