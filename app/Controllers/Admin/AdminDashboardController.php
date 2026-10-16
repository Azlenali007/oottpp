<?php
/**
 * Super Admin Dashboard Controller
 * 
 * Aggregates enterprise metrics, ledger summaries, and Chart.js datasets.
 */

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Request;
use App\Core\Session;
use App\Core\View;
use App\Core\Database;
use App\Services\AuthService;
use App\Helpers\ResponseHelper;

class AdminDashboardController {
    public function index(Request $request): void {
        $admin = AuthService::user();

        // 1. KPI Aggregations
        $userCount = (int) (Database::queryOne("SELECT COUNT(*) as c FROM users WHERE role = 'user'")['c'] ?? 0);
        $orderStats = Database::queryOne("
            SELECT 
                COUNT(*) as total_orders,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_orders,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_orders,
                SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed_orders,
                SUM(CASE WHEN status IN ('cancelled', 'refunded') THEN 1 ELSE 0 END) as cancelled_orders,
                COALESCE(SUM(total_amount), 0) as total_revenue,
                COALESCE(SUM(provider_cost), 0) as total_cost
            FROM orders
        ");

        $totalOrders = (int) ($orderStats['total_orders'] ?? 0);
        $totalRevenue = (float) ($orderStats['total_revenue'] ?? 0.0);
        $totalCost = (float) ($orderStats['total_cost'] ?? 0.0);
        $totalProfit = max(0.0, $totalRevenue - $totalCost);

        // Chart Data: Order Status Breakdown
        $completedCount = (int) ($orderStats['completed_orders'] ?? 0);
        $pendingCount = (int) ($orderStats['pending_orders'] ?? 0);
        $failedCount = (int) ($orderStats['failed_orders'] ?? 0);
        $cancelledCount = (int) ($orderStats['cancelled_orders'] ?? 0);

        // Recent Orders
        $recentOrders = Database::query("
            SELECT o.id, o.order_number, o.status, o.total_amount, o.created_at,
                   u.name as user_name, u.email as user_email,
                   s.name as service_name, s.icon as service_icon
            FROM orders o
            JOIN users u ON u.id = o.user_id
            JOIN services s ON s.id = o.service_id
            ORDER BY o.created_at DESC
            LIMIT 6
        ");

        if ($request->isAjax()) {
            ResponseHelper::success([
                'kpis' => [
                    'total_users' => $userCount,
                    'total_orders' => $totalOrders,
                    'total_revenue' => $totalRevenue,
                    'total_profit' => $totalProfit,
                ],
                'order_breakdown' => [
                    'completed' => $completedCount,
                    'pending' => $pendingCount,
                    'failed' => $failedCount,
                    'cancelled' => $cancelledCount,
                ],
                'recent_orders' => $recentOrders,
            ]);
            return;
        }

        echo View::render('admin/dashboard', [
            'title' => 'Super Admin Dashboard - OTPPro',
            'admin' => $admin,
            'kpis' => [
                'total_users' => $userCount,
                'total_orders' => $totalOrders,
                'total_revenue' => $totalRevenue,
                'total_profit' => $totalProfit,
            ],
            'breakdown' => [
                'completed' => $completedCount,
                'pending' => $pendingCount,
                'failed' => $failedCount,
                'cancelled' => $cancelledCount,
            ],
            'recentOrders' => $recentOrders,
            'csrf_token' => Session::getCsrfToken(),
        ], 'layouts/admin');
    }
}
