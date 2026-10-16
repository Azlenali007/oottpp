<?php
/**
 * Order Management Controller
 * 
 * Manages order creation stepper, status polling, and customer cancellations.
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Session;
use App\Core\View;
use App\Core\Database;
use App\Services\AuthService;
use App\Services\OrderService;
use App\Services\PricingService;
use App\Helpers\Validator;
use App\Helpers\ResponseHelper;
use Exception;

class OrderController {
    public function showNewOrder(Request $request): void {
        $user = AuthService::user();
        $services = PricingService::getActiveServicesWithPrices();
        $countries = Database::query("SELECT * FROM countries WHERE is_active = 1 ORDER BY name ASC");

        echo View::render('user/new_order', [
            'title' => 'New Order - OTPPro',
            'user' => $user,
            'services' => $services,
            'countries' => $countries,
            'selectedServiceId' => $request->query('service_id'),
            'csrf_token' => Session::getCsrfToken(),
        ], 'layouts/user');
    }

    public function create(Request $request): void {
        $userId = (int) Session::get('user_id');

        $validator = Validator::make($request->input(), [
            'service_id' => 'required|numeric',
            'country_id' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            ResponseHelper::error('Validation failed', 422, $validator->errors());
            return;
        }

        try {
            $order = OrderService::createOrder(
                $userId,
                (int) $request->input('service_id'),
                (int) $request->input('country_id')
            );

            ResponseHelper::success($order, 'Order created successfully');
        } catch (Exception $e) {
            ResponseHelper::error($e->getMessage(), 400);
        }
    }

    public function index(Request $request): void {
        $user = AuthService::user();
        $userId = (int) $user['id'];
        $status = $request->query('status');
        $search = $request->query('search');

        $sql = "
            SELECT o.id, o.order_number, o.phone_number, o.otp_code, o.full_sms_text,
                   o.unit_price, o.total_amount, o.status, o.expires_at, o.created_at,
                   s.name as service_name, s.icon as service_icon,
                   c.name as country_name, c.flag_emoji, c.iso_code
            FROM orders o
            JOIN services s ON s.id = o.service_id
            JOIN countries c ON c.id = o.country_id
            WHERE o.user_id = :uid
        ";

        $params = [':uid' => $userId];

        if ($status && in_array(strtolower($status), ['pending', 'completed', 'failed', 'cancelled'], true)) {
            $sql .= " AND o.status = :status";
            $params[':status'] = strtolower($status);
        }

        if ($search) {
            $sql .= " AND (o.order_number LIKE :search OR o.phone_number LIKE :search OR s.name LIKE :search)";
            $params[':search'] = '%' . $search . '%';
        }

        $sql .= " ORDER BY o.created_at DESC";

        $orders = Database::query($sql, $params);

        // Fetch count for tabs
        $counts = Database::queryOne("
            SELECT 
                COUNT(*) as all_count,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_count,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_count,
                SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed_count,
                SUM(CASE WHEN status = 'cancelled' OR status = 'refunded' THEN 1 ELSE 0 END) as cancelled_count
            FROM orders WHERE user_id = :uid
        ", [':uid' => $userId]);

        if ($request->isAjax()) {
            ResponseHelper::success(['orders' => $orders, 'counts' => $counts]);
            return;
        }

        echo View::render('user/orders', [
            'title' => 'My Orders - OTPPro',
            'user' => $user,
            'orders' => $orders,
            'counts' => $counts,
            'currentFilter' => $status ?? 'all',
            'searchQuery' => $search ?? '',
            'csrf_token' => Session::getCsrfToken(),
        ], 'layouts/user');
    }

    public function checkOtp(Request $request, string $orderId): void {
        $userId = (int) Session::get('user_id');
        try {
            $result = OrderService::checkOtp((int) $orderId, $userId);
            ResponseHelper::success($result);
        } catch (Exception $e) {
            ResponseHelper::error($e->getMessage(), 400);
        }
    }

    public function cancel(Request $request, string $orderId): void {
        $userId = (int) Session::get('user_id');
        try {
            $result = OrderService::cancelOrder((int) $orderId, $userId);
            ResponseHelper::success($result, 'Order cancelled and balance refunded.');
        } catch (Exception $e) {
            ResponseHelper::error($e->getMessage(), 400);
        }
    }
}
