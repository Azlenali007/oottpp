<?php
/**
 * OTPPro - Web Installer Direct Entry Point
 * 
 * Accessible at https://yourdomain.com/install/
 */

declare(strict_types=1);

require_once __DIR__ . '/../../bootstrap.php';

use App\Core\Request;
use App\Core\Session;
use App\Controllers\InstallController;
use App\Middleware\CsrfMiddleware;

Session::start();

$request = new Request();
$uri = $request->getUri();
$method = $request->getMethod();
$action = $request->query('action', '');
$controller = new InstallController();

$isRequirements = str_ends_with($uri, '/requirements') || $action === 'requirements';
$isTestDb = str_ends_with($uri, '/test-db') || $action === 'test-db';
$isRun = str_ends_with($uri, '/run') || $action === 'run';

if ($isRequirements) {
    $controller->checkRequirements($request);
} elseif ($isTestDb && $method === 'POST') {
    $csrf = new CsrfMiddleware();
    if ($csrf->handle($request)) {
        $controller->testDatabase($request);
    }
} elseif ($isRun && $method === 'POST') {
    $csrf = new CsrfMiddleware();
    if ($csrf->handle($request)) {
        $controller->runInstallation($request);
    }
} else {
    $controller->index($request);
}

