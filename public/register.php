<?php
/**
 * OTPPro - Direct Registration Entry Point
 * 
 * Provides direct access at https://yourdomain.com/register.php
 * or fallback on servers without mod_rewrite.
 */

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

use App\Core\Request;
use App\Controllers\AuthController;
use App\Middleware\CsrfMiddleware;

$request = new Request();
$controller = new AuthController();

if ($request->getMethod() === 'POST') {
    $csrf = new CsrfMiddleware();
    if ($csrf->handle($request)) {
        $controller->register($request);
    }
} else {
    $controller->showRegister($request);
}
