<?php
/**
 * Application Configuration
 */

declare(strict_types=1);

return [
    'name' => env('APP_NAME', 'OTPPro'),
    'env' => env('APP_ENV', 'production'),
    'debug' => (bool) env('APP_DEBUG', false),
    'url' => env('APP_URL', 'http://localhost:3000'),
    'timezone' => env('APP_TIMEZONE', 'UTC'),
    'currency' => [
        'code' => env('APP_CURRENCY', 'INR'),
        'symbol' => env('APP_CURRENCY_SYMBOL', '₹'),
    ],
    'super_admin_email' => env('SUPER_ADMIN_EMAIL', 'admin@otppro.local'),
];
