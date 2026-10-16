<?php
/**
 * Admin Order Monitoring & Intervention Controller
 */

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Request;
use App\Core\Session;
use App\Core\View;
use App\Core\Database;
use App\Services\AuthService;
use App\Services\RefundService;
use App\Helpers\Logger;
use App\Helpers\ResponseHelper;
use Exception;

class AdminOrderController {
    public function index(Request $request): void {
        $admin = AuthService::user();
        $status = $request->query('status');
        $search = $request->query('search');

        $sql = "
            SELECT o.id, o.order_number, o.phone_number, o.otp_code, o.status,
                   o.unit_price, o.provider_cost, o.created_at, o.provider_order_id,
                   u.id as user_id, u.name as user_name, u.email as user_email,
                   s.name as service_name, s.icon as service_icon,
                   c.name as country_name, c.flag_emoji,
                   p.name as provider_name
            FROM orders o
            JOIN users u ON u.id = o.user_id
            JOIN services s ON s.id = o.service_id
            JOIN countries c ON c.id = o.country_id
            JOIN providers p ON p.id = o.provider_id
            WHERE 1=1
        ";

        $params = [];
        if ($status && $status !== 'all') {
            $sql .= " AND o.status = :status";
            $params[':status'] = $status;
        }

        if ($search) {
            $sql .= " AND (o.order_number LIKE :search OR u.name LIKE :search OR u.email LIKE :search OR o.phone_number LIKE :search)";
            $params[':search'] = '%' . $search . '%';
        }

        $sql .= " ORDER BY o.created_at DESC LIMIT 50";

        $orders = Database::query($sql, $params);

        if ($request->isAjax()) {
            ResponseHelper::success(['orders' => $orders]);
            return;
        }

        echo View::render('admin/orders', [
            'title' => 'Order Monitoring - Super Admin',
            'admin' => $admin,
            'orders' => $orders,
            'csrf_token' => Session::getCsrfToken(),
        ], 'layouts/admin');
    }

    public function refundOrder(Request $request, string $orderId): void {
        $adminId = (int) Session::get('user_id');
        $reason = (string) ($request->input('reason') ?? 'Super Admin manual refund');

        try {
            $order = Database::queryOne("SELECT id, total_amount, status FROM orders WHERE id = :id LIMIT 1", [':id' => (int) $orderId]);
            if (!$order) {
                ResponseHelper::notFound('Order not found');
                return;
            }

            $result = RefundService::processRefund((int) $orderId, (float) $order['total_amount'], $reason, $adminId);

            Logger::audit($adminId, 'MANUAL_ORDER_REFUND', 'order', $orderId, ['amount' => $order['total_amount'], 'reason' => $reason]);

            ResponseHelper::success($result, 'Order successfully refunded to user wallet.');
        } catch (Exception $e) {
            ResponseHelper::error($e->getMessage(), 400);
        }
    }
}
