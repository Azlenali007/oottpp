<?php
/**
 * Rate Limiting Middleware
 * 
 * Protects endpoints from brute-force and request flooding.
 * Uses atomic file cache in storage/cache/ to track attempts per IP.
 */

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;
use App\Helpers\ResponseHelper;

class RateLimitMiddleware {
    private int $maxAttempts;
    private int $windowSeconds;
    private string $keyPrefix;

    public function __construct(int $maxAttempts = 60, int $windowSeconds = 60, string $keyPrefix = 'general') {
        $this->maxAttempts = $maxAttempts;
        $this->windowSeconds = $windowSeconds;
        $this->keyPrefix = $keyPrefix;
    }

    public function handle(Request $request): bool {
        $ip = $request->getIp();
        $safeIp = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $ip);
        $cacheFile = STORAGE_PATH . "/cache/ratelimit_{$this->keyPrefix}_{$safeIp}.json";

        $currentTime = time();
        $records = [];

        if (file_exists($cacheFile)) {
            $content = file_get_contents($cacheFile);
            $decoded = json_decode($content ?: '[]', true);
            if (is_array($decoded)) {
                // Filter out timestamps outside window
                $records = array_filter($decoded, fn($ts) => ($currentTime - $ts) < $this->windowSeconds);
            }
        }

        if (count($records) >= $this->maxAttempts) {
            $retryAfter = $this->windowSeconds - ($currentTime - reset($records));
            header("Retry-After: {$retryAfter}");
            ResponseHelper::error("Too many requests. Please try again in {$retryAfter} seconds.", 429);
            return false;
        }

        // Add current hit and save
        $records[] = $currentTime;
        @file_put_contents($cacheFile, json_encode($records));

        return true;
    }
}
