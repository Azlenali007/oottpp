<?php
/**
 * Web Routes Definition
 */

declare(strict_types=1);

use App\Core\Router;
use App\Core\Response;
use App\Core\Session;
use App\Controllers\AuthController;
use App\Controllers\DashboardController;
use App\Controllers\ServiceController;
use App\Controllers\OrderController;
use App\Controllers\WalletController;
use App\Controllers\ProfileController;
use App\Controllers\SupportController;
use App\Middleware\AuthMiddleware;
use App\Middleware\CsrfMiddleware;

// Public Root Landing Page
Router::get('/', function () {
    Session::start();
    $resp = new Response();
    if (Session::get('user_id')) {
        $resp->redirect(Session::get('user_role') === 'super_admin' ? '/admin/dashboard' : '/dashboard');
        return;
    }

    try {
        $services = \App\Services\PricingService::getActiveServicesWithPrices();
    } catch (\Throwable) {
        $services = [];
    }

    echo \App\Core\View::render('home/index', [
        'title' => 'OTPPro - Instant SMS Verification & Temporary Numbers',
        'services' => $services,
        'csrf_token' => Session::getCsrfToken(),
    ], 'layouts/main');
});

// Authentication Routes
Router::get('/login', [AuthController::class, 'showLogin']);
Router::post('/login', [AuthController::class, 'login'], [CsrfMiddleware::class]);
Router::get('/register', [AuthController::class, 'showRegister']);
Router::post('/register', [AuthController::class, 'register'], [CsrfMiddleware::class]);
Router::get('/logout', [AuthController::class, 'logout']);

// Web-Based Installer Routes
Router::get('/install', [\App\Controllers\InstallController::class, 'index']);
Router::get('/install/requirements', [\App\Controllers\InstallController::class, 'checkRequirements']);
Router::post('/install/test-db', [\App\Controllers\InstallController::class, 'testDatabase'], [CsrfMiddleware::class]);
Router::post('/install/run', [\App\Controllers\InstallController::class, 'runInstallation'], [CsrfMiddleware::class]);

// Protected User Panel Routes
Router::group(['middleware' => [AuthMiddleware::class]], function () {
    Router::get('/dashboard', [DashboardController::class, 'index']);
    Router::get('/services', [ServiceController::class, 'index']);
    Router::get('/orders/new', [OrderController::class, 'showNewOrder']);
    Router::get('/orders', [OrderController::class, 'index']);
    Router::get('/wallet', [WalletController::class, 'index']);
    Router::get('/payments', [WalletController::class, 'payments']);
    Router::get('/profile', [ProfileController::class, 'index']);
    Router::get('/support', [SupportController::class, 'index']);
});

