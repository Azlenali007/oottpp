<?php
/**
 * WebSocket Authentication & Channel Authorization
 */

declare(strict_types=1);

namespace WebSocket;

use App\Core\Database;
use App\Helpers\Security;

class Auth {
    public static function authenticate(string $token): ?array {
        if (empty($token)) {
            return null;
        }

        // Token may be a session ID or custom auth token
        $user = Database::queryOne("
            SELECT id, name, email, role FROM users WHERE id = :id LIMIT 1
        ", [':id' => (int) $token]);

        return $user ?: null;
    }

    public static function canSubscribe(array $user, string $channel): bool {
        // Super Admin can subscribe to global admin monitoring
        if ($channel === 'admin.orders' || $channel === 'admin.kpis') {
            return ($user['role'] ?? '') === 'super_admin';
        }

        // Users can ONLY subscribe to their private channel
        if (str_starts_with($channel, 'user.')) {
            $expectedChannel = 'user.' . ($user['id'] ?? 0);
            return $channel === $expectedChannel;
        }

        return false;
    }
}
