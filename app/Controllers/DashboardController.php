<?php
/**
 * User Dashboard Controller
 * 
 * Provides stats, recent order ledger, and promotional banners
 * matching the primary reference interface.
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Session;
use App\Core\View;
use App\Core\Database;
use App\Services\AuthService;
use App\Services\WalletService;
use App\Services\PricingService;
use App\Helpers\ResponseHelper;

class DashboardController {
    public function index(Request $request): void {
        $user = AuthService::user();
        $userId = (int) $user['id'];

        $wallet = WalletService::getWallet($userId);

        // Fetch Order Stats
        $stats = Database::queryOne("
            SELECT 
                COUNT(*) as total_orders,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_orders,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_orders,
                SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed_orders
            FROM orders
            WHERE user_id = :uid
        ", [':uid' => $userId]);

        $totalOrders = (int) ($stats['total_orders'] ?? 0);
        $completedOrders = (int) ($stats['completed_orders'] ?? 0);
        $pendingOrders = (int) ($stats['pending_orders'] ?? 0);
        $successRate = $totalOrders > 0 ? round(($completedOrders / $totalOrders) * 100) : 100;

        // Fetch Recent Orders
        $recentOrders = Database::query("
            SELECT o.id, o.order_number, o.phone_number, o.otp_code, o.status,
                   o.created_at, s.name as service_name, s.icon as service_icon,
                   c.name as country_name, c.flag_emoji, c.iso_code
            FROM orders o
            JOIN services s ON s.id = o.service_id
            JOIN countries c ON c.id = o.country_id
            WHERE o.user_id = :uid
            ORDER BY o.created_at DESC
            LIMIT 5
        ", [':uid' => $userId]);

        // Services catalog preview
        $services = PricingService::getActiveServicesWithPrices();

        if ($request->isAjax()) {
            ResponseHelper::success([
                'user' => $user,
                'wallet' => $wallet,
                'stats' => [
                    'total_orders' => $totalOrders,
                    'completed_orders' => $completedOrders,
                    'pending_orders' => $pendingOrders,
                    'success_rate' => $successRate,
                ],
                'recent_orders' => $recentOrders,
            ]);
            return;
        }

        echo View::render('user/dashboard', [
            'title' => 'Dashboard - OTPPro',
            'user' => $user,
            'wallet' => $wallet,
            'stats' => [
                'total_orders' => $totalOrders,
                'completed_orders' => $completedOrders,
                'pending_orders' => $pendingOrders,
                'success_rate' => $successRate,
            ],
            'recentOrders' => $recentOrders,
            'services' => $services,
            'csrf_token' => Session::getCsrfToken(),
        ], 'layouts/user');
    }
}
