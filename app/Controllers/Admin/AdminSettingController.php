<?php
/**
 * Admin System Settings & Health Controller
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
use App\Helpers\ResponseHelper;
use Exception;

class AdminSettingController {
    public function index(Request $request): void {
        $admin = AuthService::user();

        $providers = Database::query("SELECT * FROM providers ORDER BY priority ASC");
        $syncLogs = Database::query("
            SELECT sl.*, p.name as provider_name
            FROM sync_logs sl
            JOIN providers p ON p.id = sl.provider_id
            ORDER BY sl.created_at DESC
            LIMIT 20
        ");
        $auditLogs = Database::query("
            SELECT a.*, u.name as admin_name
            FROM admin_audit_logs a
            JOIN users u ON u.id = a.admin_user_id
            ORDER BY a.created_at DESC
            LIMIT 20
        ");

        if ($request->isAjax()) {
            ResponseHelper::success([
                'providers' => $providers,
                'sync_logs' => $syncLogs,
                'audit_logs' => $auditLogs,
            ]);
            return;
        }

        echo View::render('admin/settings', [
            'title' => 'System Settings - Super Admin',
            'admin' => $admin,
            'providers' => $providers,
            'syncLogs' => $syncLogs,
            'auditLogs' => $auditLogs,
            'csrf_token' => Session::getCsrfToken(),
        ], 'layouts/admin');
    }

    public function testProviderConnection(Request $request): void {
        try {
            $provider = ProviderService::getActiveProvider();
            $balance = $provider->getBalance();
            ResponseHelper::success([
                'provider' => $provider->getName(),
                'code' => $provider->getCode(),
                'balance' => $balance,
                'is_configured' => $provider->isConfigured(),
            ], "Connection test passed. Provider balance: {$balance}");
        } catch (Exception $e) {
            ResponseHelper::error("Provider test failed: " . $e->getMessage(), 500);
        }
    }
}
