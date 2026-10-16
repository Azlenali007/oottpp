<?php
/**
 * OTPPro - Front Controller Entry Point
 * 
 * Routes all incoming HTTP web requests through the Core PHP kernel.
 */

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

use App\Core\Request;
use App\Core\Router;

// Load Route Tables
require_once ROOT_PATH . '/routes/web.php';
require_once ROOT_PATH . '/routes/api.php';
require_once ROOT_PATH . '/routes/admin.php';

// Dispatch Request
$request = new Request();
Router::dispatch($request);
