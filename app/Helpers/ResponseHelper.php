<?php
/**
 * Standardized Response Helper
 * 
 * Formats API JSON responses with predictable success, data, and error fields.
 */

declare(strict_types=1);

namespace App\Helpers;

use App\Core\Response;

class ResponseHelper {
    public static function success(mixed $data = null, string $message = 'Success', int $code = 200): void {
        $response = new Response();
        $response->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $code);
    }

    public static function error(string $message = 'An error occurred', int $code = 400, ?array $errors = null): void {
        $response = new Response();
        $payload = [
            'success' => false,
            'error' => $message,
        ];
        if ($errors !== null) {
            $payload['errors'] = $errors;
        }
        $response->json($payload, $code);
    }

    public static function unauthorized(string $message = 'Unauthorized access'): void {
        self::error($message, 401);
    }

    public static function forbidden(string $message = 'Access forbidden'): void {
        self::error($message, 403);
    }

    public static function notFound(string $message = 'Resource not found'): void {
        self::error($message, 404);
    }
}
