<?php
/**
 * Inbound Webhook Controller
 * 
 * Cryptographically verifies signatures, logs raw events into webhook_events,
 * enforces idempotency, and dispatches to business services.
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Database;
use App\Services\PaymentService;
use App\Helpers\Logger;
use App\Helpers\ResponseHelper;
use Exception;

class WebhookController {
    public function handlePayment(Request $request, string $gateway): void {
        $rawPayload = file_get_contents('php://input');
        $signature = $request->header('x-signature') ?? $request->header('x-razorpay-signature') ?? '';

        Logger::info('webhook', "Incoming payment webhook from [{$gateway}]", ['raw' => $rawPayload]);

        try {
            $gw = PaymentService::getGateway($gateway);
            $verification = $gw->verifyWebhook($rawPayload, $signature);

            if (!$verification['verified']) {
                Logger::warning('webhook', "Webhook signature verification failed for [{$gateway}]");
                ResponseHelper::error('Invalid signature', 400);
                return;
            }

            // Deduplicate event via unique constraint on webhook_events
            $eventId = $verification['payment_reference'] . '_' . ($verification['transaction_id'] ?? time());
            
            $existing = Database::queryOne("
                SELECT id, is_processed FROM webhook_events 
                WHERE source = :src AND event_id = :eid LIMIT 1
            ", [':src' => $gateway, ':eid' => $eventId]);

            if ($existing && $existing['is_processed']) {
                Logger::info('webhook', "Event already processed, returning 200 OK");
                ResponseHelper::success(['status' => 'already_processed']);
                return;
            }

            Database::execute("
                INSERT INTO webhook_events (source, event_id, event_type, payload, is_processed, created_at)
                VALUES (:src, :eid, :type, :payload, 0, CURRENT_TIMESTAMP)
            ", [
                ':src' => $gateway,
                ':eid' => $eventId,
                ':type' => $verification['status'],
                ':payload' => $rawPayload,
            ]);

            $webhookDbId = (int) Database::lastInsertId();

            if ($verification['status'] === 'completed') {
                PaymentService::handlePaymentSuccess(
                    $verification['payment_reference'],
                    $verification['transaction_id'],
                    $verification['amount']
                );

                Database::execute("
                    UPDATE webhook_events SET is_processed = 1, processed_at = CURRENT_TIMESTAMP WHERE id = :id
                ", [':id' => $webhookDbId]);
            }

            ResponseHelper::success(['status' => 'processed']);
        } catch (Exception $e) {
            Logger::error('webhook', "Webhook processing failed: " . $e->getMessage());
            ResponseHelper::error($e->getMessage(), 500);
        }
    }
}
