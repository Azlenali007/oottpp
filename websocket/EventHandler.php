<?php
/**
 * WebSocket Event Handler
 */

declare(strict_types=1);

namespace WebSocket;

class EventHandler {
    public static function formatEvent(string $event, array $payload): string {
        return json_encode([
            'event' => $event,
            'data' => $payload,
            'timestamp' => time(),
        ], JSON_UNESCAPED_SLASHES);
    }
}
