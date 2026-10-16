<?php
/**
 * Super Admin Middleware
 * 
 * Strictly authorizes requests destined for the Super Admin panel.
 * Rejects non-super-admins with 403 Forbidden.
 */

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Helpers\ResponseHelper;

class AdminMiddleware {
    public function handle(Request $request): bool {
        Session::start();
        $userId = Session::get('user_id');
        $role = Session::get('user_role');

        if (!$userId || $role !== 'super_admin') {
            if ($request->isAjax()) {
                ResponseHelper::forbidden('Administrative privileges required.');
                return false;
            }

            $response = new Response();
            $response->redirect('/admin/login');
            return false;
        }

        return true;
    }
}
