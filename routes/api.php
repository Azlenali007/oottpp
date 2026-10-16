<?php
/**
 * RESTful API Routes Definition
 */

declare(strict_types=1);

use App\Core\Router;
use App\Controllers\ServiceController;
use App\Controllers\OrderController;
use App\Controllers\PaymentController;
use App\Controllers\ProfileController;
use App\Controllers\SupportController;
use App\Controllers\WebhookController;
use App\Middleware\AuthMiddleware;
use App\Middleware\CsrfMiddleware;
use App\Middleware\RateLimitMiddleware;

// Public Catalog API
Router::get('/api/services', [ServiceController::class, 'index']);
Router::get('/api/services/{id}/countries', [ServiceController::class, 'getServiceCountries']);

// Inbound Gateway Webhooks (Bypasses CSRF, signature verified inside)
Router::post('/api/webhook/payment/{gateway}', [WebhookController::class, 'handlePayment']);

// Protected User Endpoints
Router::group(['prefix' => '/api', 'middleware' => [AuthMiddleware::class]], function () {
    Router::post('/orders', [OrderController::class, 'create'], [CsrfMiddleware::class, new RateLimitMiddleware(20, 60, 'order')]);
    Router::get('/orders/{id}/check', [OrderController::class, 'checkOtp']);
    Router::post('/orders/{id}/cancel', [OrderController::class, 'cancel'], [CsrfMiddleware::class]);

    Router::post('/wallet/deposit', [PaymentController::class, 'deposit'], [CsrfMiddleware::class]);

    Router::post('/profile/update', [ProfileController::class, 'updateProfile'], [CsrfMiddleware::class]);
    Router::post('/profile/password', [ProfileController::class, 'changePassword'], [CsrfMiddleware::class]);
    Router::post('/support/ticket', [SupportController::class, 'createTicket'], [CsrfMiddleware::class]);
});
