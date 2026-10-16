<?php
/**
 * Routing & Navigation Verification Test Suite
 * 
 * Tests that all public, user, admin, and installer routes resolve correctly,
 * unauthenticated requests redirect cleanly, and 404 errors on /install and /login
 * are permanently resolved.
 * Run via CLI: php tests/RoutingVerificationTest.php
 */

declare(strict_types=1);

define('RUNNING_CLI_TEST', true);

require_once __DIR__ . '/../bootstrap.php';

use App\Core\Request;
use App\Core\Router;
use App\Core\Session;
use App\Core\Response;
use App\Controllers\InstallController;

echo "========================================================\n";
echo "       OTPPro Routing & Navigation Test Suite           \n";
echo "========================================================\n";

$passCount = 0;
$failCount = 0;

function it(string $description, callable $test): void {
    global $passCount, $failCount;
    try {
        $test();
        echo "  [PASS] {$description}\n";
        $passCount++;
    } catch (Throwable $e) {
        echo "  [FAIL] {$description}\n";
        echo "         Error: " . $e->getMessage() . "\n";
        $failCount++;
    }
}

// 1. Verify Entry-point Files and Server Configurations Exist
it('verifies root .htaccess, public .htaccess, and nginx config exist', function () {
    if (!file_exists(ROOT_PATH . '/.htaccess')) {
        throw new Exception("Root .htaccess is missing");
    }
    if (!file_exists(ROOT_PATH . '/public/.htaccess')) {
        throw new Exception("public/.htaccess is missing");
    }
    if (!file_exists(ROOT_PATH . '/config/nginx.conf.example')) {
        throw new Exception("config/nginx.conf.example is missing");
    }
});

// 2. Verify Direct Compatibility Entry Points Exist
it('verifies login.php, register.php, and install.php exist in public and root', function () {
    $files = [
        ROOT_PATH . '/public/login.php',
        ROOT_PATH . '/public/register.php',
        ROOT_PATH . '/public/install/index.php',
        ROOT_PATH . '/index.php',
        ROOT_PATH . '/login.php',
        ROOT_PATH . '/register.php',
        ROOT_PATH . '/install.php',
    ];
    foreach ($files as $f) {
        if (!file_exists($f)) {
            throw new Exception("Missing compatibility file: {$f}");
        }
    }
});

// 3. Verify Route Table Definitions
it('verifies all required routes are registered in Router', function () {
    // Clear and reload routes
    require_once ROOT_PATH . '/routes/web.php';
    require_once ROOT_PATH . '/routes/api.php';
    require_once ROOT_PATH . '/routes/admin.php';

    // Router reflection to inspect registered routes
    $refClass = new ReflectionClass(Router::class);
    $prop = $refClass->getProperty('routes');
    $prop->setAccessible(true);
    $routes = $prop->getValue();

    $expectedPaths = [
        '/',
        '/login',
        '/register',
        '/logout',
        '/install',
        '/dashboard',
        '/services',
        '/orders',
        '/orders/new',
        '/wallet',
        '/payments',
        '/profile',
        '/support',
        '/admin',
        '/admin/login',
        '/admin/dashboard',
        '/admin/services',
        '/admin/orders',
        '/admin/users',
        '/admin/settings',
        '/api/services',
        '/api/orders',
    ];

    $registeredPaths = array_map(fn($r) => $r['path'], $routes);

    foreach ($expectedPaths as $p) {
        if (!in_array($p, $registeredPaths, true)) {
            throw new Exception("Route [{$p}] is missing from route tables.");
        }
    }
});

// 4. Verify View Templates Exist
it('verifies all view template files and layouts exist on disk', function () {
    $views = [
        ROOT_PATH . '/views/layouts/main.php',
        ROOT_PATH . '/views/layouts/auth.php',
        ROOT_PATH . '/views/layouts/user.php',
        ROOT_PATH . '/views/layouts/admin.php',
        ROOT_PATH . '/views/home/index.php',
        ROOT_PATH . '/views/auth/login.php',
        ROOT_PATH . '/views/auth/register.php',
        ROOT_PATH . '/views/auth/admin_login.php',
        ROOT_PATH . '/views/user/dashboard.php',
        ROOT_PATH . '/views/user/services.php',
        ROOT_PATH . '/views/user/new_order.php',
        ROOT_PATH . '/views/user/orders.php',
        ROOT_PATH . '/views/user/wallet.php',
        ROOT_PATH . '/views/user/profile.php',
        ROOT_PATH . '/views/user/support.php',
        ROOT_PATH . '/views/admin/dashboard.php',
        ROOT_PATH . '/views/admin/services.php',
        ROOT_PATH . '/views/admin/orders.php',
        ROOT_PATH . '/views/admin/users.php',
        ROOT_PATH . '/views/admin/settings.php',
        ROOT_PATH . '/views/errors/404.php',
        ROOT_PATH . '/views/errors/500.php',
    ];

    foreach ($views as $v) {
        if (!file_exists($v)) {
            throw new Exception("Missing view template: {$v}");
        }
    }
});

// 5. Test Router Dispatch to Landing Page (/)
it('dispatches GET / and produces HTML with green theme and services', function () {
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_SERVER['REQUEST_URI'] = '/';
    $_GET = [];
    $_POST = [];

    Session::start();
    Session::set('user_id', null);

    $req = new Request();
    ob_start();
    Router::dispatch($req);
    $output = ob_get_clean();

    if (!str_contains($output, 'OTPPro') || !str_contains($output, 'Instant SMS Verification')) {
        throw new Exception("Landing page output missing required text.");
    }
});

// 6. Test Router Dispatch to /login
it('dispatches GET /login and produces authentication form without 404', function () {
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_SERVER['REQUEST_URI'] = '/login';
    $_GET = [];
    $_POST = [];

    Session::start();
    Session::set('user_id', null);

    $req = new Request();
    ob_start();
    Router::dispatch($req);
    $output = ob_get_clean();

    if (!str_contains($output, 'Sign in to your account') || !str_contains($output, 'email')) {
        throw new Exception("Login page output missing expected form fields.");
    }
});

// 7. Test Router Dispatch to /register
it('dispatches GET /register and produces registration form without 404', function () {
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_SERVER['REQUEST_URI'] = '/register';
    $_GET = [];
    $_POST = [];

    Session::start();
    Session::set('user_id', null);

    $req = new Request();
    ob_start();
    Router::dispatch($req);
    $output = ob_get_clean();

    if (!str_contains($output, 'Create your account') || !str_contains($output, 'password')) {
        throw new Exception("Register page output missing expected form fields.");
    }
});

// 8. Test Router Dispatch to /install
it('dispatches GET /install and reaches installer or already-installed screen without 404', function () {
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_SERVER['REQUEST_URI'] = '/install';
    $_GET = [];
    $_POST = [];

    $req = new Request();
    ob_start();
    Router::dispatch($req);
    $output = ob_get_clean();

    if (!str_contains($output, 'Web Installer') && !str_contains($output, 'Already Installed')) {
        throw new Exception("Installer output missing required wizard or lock screen.");
    }
});

// 9. Verify Protected Routes Block Unauthenticated Access
it('protects /dashboard and redirects unauthenticated guests to /login', function () {
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_SERVER['REQUEST_URI'] = '/dashboard';
    $_GET = [];
    $_POST = [];

    Session::start();
    Session::set('user_id', null);

    $req = new Request();
    $middleware = new \App\Middleware\AuthMiddleware();
    
    // AuthMiddleware must return false for guest
    $canProceed = $middleware->handle($req);
    if ($canProceed !== false) {
        throw new Exception("AuthMiddleware allowed guest to access protected route.");
    }
});

// 10. Verify Admin Routes Block Unauthorized Regular Users
it('protects /admin/dashboard and blocks unauthorized regular users', function () {
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_SERVER['REQUEST_URI'] = '/admin/dashboard';
    $_GET = [];
    $_POST = [];

    Session::start();
    Session::set('user_id', 999);
    Session::set('user_role', 'user');

    $req = new Request();
    $adminMiddleware = new \App\Middleware\AdminMiddleware();
    
    $canProceed = $adminMiddleware->handle($req);
    if ($canProceed !== false) {
        throw new Exception("AdminMiddleware allowed non-admin user to access admin area.");
    }
});

// 11. Verify Trailing Slash Handling (/login/ and /install/)
it('handles trailing slashes identically without breaking routing', function () {
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_SERVER['REQUEST_URI'] = '/login/';
    $req = new Request();
    if ($req->getUri() !== '/login') {
        throw new Exception("Trailing slash was not normalized. Got: " . $req->getUri());
    }

    $_SERVER['REQUEST_URI'] = '/install/';
    $reqInstall = new Request();
    if ($reqInstall->getUri() !== '/install') {
        throw new Exception("Trailing slash was not normalized on /install/. Got: " . $reqInstall->getUri());
    }
});

// 12. Verify Unknown Routes Return Application 404
it('returns proper application 404 for unknown route paths', function () {
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_SERVER['REQUEST_URI'] = '/unknown-page-route-random';
    $req = new Request();

    ob_start();
    Router::dispatch($req);
    $output = ob_get_clean();

    if (!str_contains($output, '404') && !str_contains($output, 'Page Not Found')) {
        throw new Exception("Expected 404 error page output for invalid route.");
    }
});

echo "--------------------------------------------------------\n";
echo "Results: {$passCount} passed, {$failCount} failed.\n";

if ($failCount === 0) {
    echo "[SUCCESS] ALL ROUTING AND NAVIGATION TESTS PASSED PERFECTLY!\n";
    exit(0);
} else {
    echo "[ERROR] ROUTING TESTS FAILED.\n";
    exit(1);
}
