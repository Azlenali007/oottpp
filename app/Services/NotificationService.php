<?php
/**
 * User & System Notification Service
 */

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

class NotificationService {
    public static function send(int $userId, string $title, string $message, string $type = 'info', ?string $actionUrl = null): int {
        return (int) Database::execute("
            INSERT INTO notifications (user_id, title, message, type, action_url, is_read, created_at)
            VALUES (:user_id, :title, :message, :type, :action_url, 0, CURRENT_TIMESTAMP)
        ", [
            ':user_id' => $userId,
            ':title' => $title,
            ':message' => $message,
            ':type' => $type,
            ':action_url' => $actionUrl,
        ]);
    }

    public static function getUserNotifications(int $userId, int $limit = 20): array {
        return Database::query("
            SELECT * FROM notifications
            WHERE user_id = :user_id
            ORDER BY created_at DESC
            LIMIT :limit
        ", [':user_id' => $userId, ':limit' => $limit]);
    }

    public static function getUnreadCount(int $userId): int {
        $row = Database::queryOne("
            SELECT COUNT(*) as count FROM notifications
            WHERE user_id = :user_id AND is_read = 0
        ", [':user_id' => $userId]);

        return (int) ($row['count'] ?? 0);
    }

    public static function markAllAsRead(int $userId): void {
        Database::execute("
            UPDATE notifications SET is_read = 1 WHERE user_id = :user_id AND is_read = 0
        ", [':user_id' => $userId]);
    }
}
