<?php
/**
 * Authentication Middleware
 * 
 * Verifies that the client has an active authenticated user session.
 */

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Helpers\ResponseHelper;

class AuthMiddleware {
    public function handle(Request $request): bool {
        Session::start();
        $userId = Session::get('user_id');

        if (!$userId) {
            if ($request->isAjax()) {
                ResponseHelper::unauthorized('Authentication required to access this resource.');
                return false;
            }

            $response = new Response();
            $response->redirect('/login');
            return false;
        }

        return true;
    }
}
