<?php
/**
 * Security & Protection Configuration
 */

declare(strict_types=1);

return [
    // CSRF Configuration
    'csrf' => [
        'token_name' => '_csrf_token',
        'header_name' => 'X-CSRF-TOKEN',
        'lifetime_seconds' => 7200, // 2 hours
    ],

    // Session Configuration
    'session' => [
        'name' => 'OTPPRO_SESSID',
        'lifetime' => 86400, // 24 hours
        'secure' => (bool) env('SESSION_SECURE', false),
        'httponly' => true,
        'samesite' => 'Strict',
    ],

    // Password Policy
    'password' => [
        'min_length' => 8,
        'require_uppercase' => true,
        'require_numeric' => true,
        'algo' => PASSWORD_BCRYPT,
        'options' => ['cost' => 12],
    ],

    // Rate Limiting (attempts per window)
    'rate_limits' => [
        'login' => ['max_attempts' => 5, 'window_seconds' => 300], // 5 attempts per 5 mins
        'order' => ['max_attempts' => 20, 'window_seconds' => 60], // 20 orders per min
        'api' => ['max_attempts' => 100, 'window_seconds' => 60],  // 100 requests per min
    ],

    // Super Admin Policy (strictly 1 single Super Admin account)
    'admin' => [
        'enforce_single_super_admin' => true,
        'allow_public_registration' => false,
        'session_timeout_seconds' => 3600, // 1 hour inactivity
    ],
];
