<?php
/**
 * Admin User Management Controller
 */

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Request;
use App\Core\Session;
use App\Core\View;
use App\Core\Database;
use App\Services\AuthService;
use App\Helpers\Logger;
use App\Helpers\ResponseHelper;

class AdminUserController {
    public function index(Request $request): void {
        $admin = AuthService::user();
        $search = $request->query('search');

        $sql = "
            SELECT u.id, u.name, u.email, u.phone, u.role, u.status, u.created_at, u.last_login_at,
                   COALESCE(w.balance, 0.00) as balance,
                   (SELECT COUNT(*) FROM orders o WHERE o.user_id = u.id) as order_count,
                   (SELECT COALESCE(SUM(total_amount), 0) FROM orders o WHERE o.user_id = u.id) as total_spent
            FROM users u
            LEFT JOIN wallets w ON w.user_id = u.id
            WHERE u.role = 'user'
        ";

        $params = [];
        if ($search) {
            $sql .= " AND (u.name LIKE :search OR u.email LIKE :search OR u.phone LIKE :search)";
            $params[':search'] = '%' . $search . '%';
        }

        $sql .= " ORDER BY u.created_at DESC LIMIT 50";

        $users = Database::query($sql, $params);

        if ($request->isAjax()) {
            ResponseHelper::success(['users' => $users]);
            return;
        }

        echo View::render('admin/users', [
            'title' => 'User Management - Super Admin',
            'admin' => $admin,
            'users' => $users,
            'csrf_token' => Session::getCsrfToken(),
        ], 'layouts/admin');
    }

    public function toggleStatus(Request $request, string $userId): void {
        $user = Database::queryOne("SELECT id, status, role FROM users WHERE id = :id LIMIT 1", [':id' => (int) $userId]);
        if (!$user) {
            ResponseHelper::notFound('User not found');
            return;
        }

        if ($user['role'] === 'super_admin') {
            ResponseHelper::forbidden('Cannot alter status of Super Admin account');
            return;
        }

        $newStatus = $user['status'] === 'active' ? 'blocked' : 'active';
        Database::execute("UPDATE users SET status = :status, updated_at = CURRENT_TIMESTAMP WHERE id = :id", [
            ':status' => $newStatus,
            ':id' => (int) $userId,
        ]);

        Logger::audit((int) Session::get('user_id'), 'USER_STATUS_CHANGE', 'user', $userId, ['new_status' => $newStatus]);

        ResponseHelper::success(['status' => $newStatus], "User account is now {$newStatus}.");
    }
}
