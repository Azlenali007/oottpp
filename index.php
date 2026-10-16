<?php
/**
 * OTPPro - Root Front Controller Gateway
 * 
 * Delegates execution to the public front controller when the web server's
 * DocumentRoot is configured to the project root directory rather than /public.
 */

declare(strict_types=1);

require_once __DIR__ . '/public/index.php';
