<?php
/**
 * Super Admin Routes Definition
 */

declare(strict_types=1);

use App\Core\Router;
use App\Controllers\AuthController;
use App\Controllers\Admin\AdminDashboardController;
use App\Controllers\Admin\AdminServiceController;
use App\Controllers\Admin\AdminOrderController;
use App\Controllers\Admin\AdminUserController;
use App\Controllers\Admin\AdminSettingController;
use App\Middleware\AdminMiddleware;
use App\Middleware\CsrfMiddleware;

// Public Admin Login & Entry
Router::get('/admin', function () {
    (new \App\Core\Response())->redirect('/admin/dashboard');
});
Router::get('/admin/login', [AuthController::class, 'showAdminLogin']);
Router::post('/admin/login', [AuthController::class, 'adminLogin'], [CsrfMiddleware::class]);
Router::get('/admin/logout', [AuthController::class, 'logout']);

// Protected Super Admin Panel
Router::group(['prefix' => '/admin', 'middleware' => [AdminMiddleware::class]], function () {
    Router::get('/dashboard', [AdminDashboardController::class, 'index']);
    Router::get('/services', [AdminServiceController::class, 'index']);
    Router::get('/orders', [AdminOrderController::class, 'index']);
    Router::get('/users', [AdminUserController::class, 'index']);
    Router::get('/settings', [AdminSettingController::class, 'index']);

    // Admin Operations
    Router::post('/services/sync', [AdminServiceController::class, 'syncFromProvider'], [CsrfMiddleware::class]);
    Router::post('/services/{id}/price', [AdminServiceController::class, 'updatePrice'], [CsrfMiddleware::class]);
    Router::post('/services/{id}/toggle', [AdminServiceController::class, 'toggleStatus'], [CsrfMiddleware::class]);
    Router::post('/orders/{id}/refund', [AdminOrderController::class, 'refundOrder'], [CsrfMiddleware::class]);
    Router::post('/users/{id}/toggle', [AdminUserController::class, 'toggleStatus'], [CsrfMiddleware::class]);
    Router::post('/settings/test-provider', [AdminSettingController::class, 'testProviderConnection'], [CsrfMiddleware::class]);
});
