<?php
/**
 * CSRF Protection Middleware
 * 
 * Verifies CSRF tokens on all state-changing HTTP methods (POST, PUT, DELETE, PATCH).
 */

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;
use App\Core\Session;
use App\Helpers\ResponseHelper;

class CsrfMiddleware {
    public function handle(Request $request): bool {
        $method = $request->getMethod();
        if (in_array($method, ['GET', 'HEAD', 'OPTIONS'], true)) {
            return true;
        }

        // Webhook paths bypass CSRF because they use cryptographic HMAC signatures
        $uri = $request->getUri();
        if (str_starts_with($uri, '/api/webhook')) {
            return true;
        }

        $token = $request->input('_csrf_token') ?? $request->header('x-csrf-token');
        if (!Session::validateCsrfToken($token)) {
            if ($request->isAjax()) {
                ResponseHelper::error('CSRF token validation failed. Please reload and try again.', 419);
                return false;
            }

            http_response_code(419);
            echo "<h1>419 Page Expired</h1><p>CSRF token mismatch. Please go back and refresh the page.</p>";
            return false;
        }

        return true;
    }
}
