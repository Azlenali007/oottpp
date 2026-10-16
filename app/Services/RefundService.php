<?php
/**
 * Refund Management Service
 * 
 * Enforces zero double-refund guarantees via atomic transactions
 * and database-level unique constraints.
 */

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Helpers\Security;
use App\Helpers\Logger;
use Exception;

class RefundService {
    /**
     * Process an authorized refund for an order
     */
    public static function processRefund(
        int $orderId,
        float $amount,
        string $reason,
        ?int $adminUserId = null
    ): array {
        return Database::transaction(function ($pdo) use ($orderId, $amount, $reason, $adminUserId) {
            // Check if order was already refunded
            $existingRefund = Database::queryOne("SELECT id FROM refunds WHERE order_id = :oid LIMIT 1", [':oid' => $orderId]);
            if ($existingRefund) {
                throw new Exception("This order has already been refunded.");
            }

            $order = Database::queryOne("SELECT id, order_number, user_id, status FROM orders WHERE id = :id LIMIT 1", [':id' => $orderId]);
            if (!$order) {
                throw new Exception("Order not found.");
            }

            $userId = (int) $order['user_id'];
            $refundUuid = Security::generateUuid();

            // 1. Credit wallet
            $credit = WalletService::credit(
                $userId,
                $amount,
                'refund',
                "Refund for order {$order['order_number']}: {$reason}",
                $order['order_number'],
                'order_refund'
            );

            // 2. Insert into refunds ledger
            $refundStmt = $pdo->prepare("
                INSERT INTO refunds (
                    refund_uuid, order_id, user_id, amount, reason, status, processed_by_user_id, created_at
                ) VALUES (
                    :uuid, :oid, :uid, :amount, :reason, 'completed', :admin_id, CURRENT_TIMESTAMP
                )
            ");
            $refundStmt->execute([
                ':uuid' => $refundUuid,
                ':oid' => $orderId,
                ':uid' => $userId,
                ':amount' => number_format($amount, 4, '.', ''),
                ':reason' => $reason,
                ':admin_id' => $adminUserId,
            ]);

            // 3. Update order status
            $pdo->prepare("
                UPDATE orders
                SET status = 'refunded',
                    cancelled_at = CURRENT_TIMESTAMP,
                    cancellation_reason = :reason,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = :id
            ")->execute([
                ':reason' => $reason,
                ':id' => $orderId,
            ]);

            // 4. Notify user
            NotificationService::send(
                $userId,
                "Refund Processed: {$order['order_number']}",
                "₹" . number_format($amount, 2) . " has been credited back to your wallet.",
                'info',
                '/wallet'
            );

            Logger::info('payments', "Order refund completed", [
                'order_id' => $orderId,
                'amount' => $amount,
                'refund_uuid' => $refundUuid,
            ]);

            return [
                'success' => true,
                'refund_uuid' => $refundUuid,
                'amount' => $amount,
                'new_balance' => $credit['balance_after'],
            ];
        });
    }
}
