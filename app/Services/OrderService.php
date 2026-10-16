<?php
/**
 * Verification Order Service
 * 
 * Manages the complete lifecycle of OTP verification orders:
 * pre-check -> atomic wallet debit -> provider phone acquisition -> OTP delivery -> cancellation/refund.
 */

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Helpers\Security;
use App\Helpers\Logger;
use Exception;

class OrderService {
    /**
     * Create and place a new OTP verification order
     */
    public static function createOrder(int $userId, int $serviceId, int $countryId): array {
        // 1. Check stock and resolve pricing
        $stock = StockService::checkAvailability($serviceId, $countryId);
        if (!$stock['available']) {
            throw new Exception($stock['reason'] ?? 'Service currently unavailable in selected country.');
        }

        $unitPrice = (float) $stock['selling_price'];
        $providerCost = (float) $stock['provider_cost'];
        $providerId = (int) $stock['provider_id'];
        $providerServiceCode = (string) $stock['provider_service_code'];
        $providerCode = (string) $stock['provider_code'];

        // Resolve country ISO
        $country = Database::queryOne("SELECT iso_code FROM countries WHERE id = :id LIMIT 1", [':id' => $countryId]);
        $countryCode = $country['iso_code'] ?? 'US';

        $orderNumber = Security::generateOrderNumber();

        // 2. Atomic Wallet Debit
        $debitResult = WalletService::debit(
            $userId,
            $unitPrice,
            'order_deduction',
            "Order payment for {$orderNumber}",
            $orderNumber,
            'order'
        );

        // 3. Contact Provider to Acquire Phone Number
        try {
            $provider = ProviderService::getActiveProvider($providerCode);
            $providerResponse = $provider->createOrder($providerServiceCode, $countryCode);

            if (!$providerResponse['success']) {
                throw new Exception($providerResponse['error'] ?? 'Provider failed to allocate a phone number.');
            }

            $providerOrderId = $providerResponse['provider_order_id'];
            $phoneNumber = $providerResponse['phone_number'];

            // 4. Record Order in Database (expires in 15 minutes)
            $expiresAt = date('Y-m-d H:i:s', time() + (15 * 60));

            Database::execute("
                INSERT INTO orders (
                    order_number, user_id, service_id, country_id, provider_id,
                    provider_service_id, provider_order_id, phone_number,
                    unit_price, provider_cost, quantity, total_amount, status,
                    expires_at, created_at, updated_at
                ) VALUES (
                    :order_number, :user_id, :service_id, :country_id, :provider_id,
                    :pivot_id, :provider_order_id, :phone_number,
                    :unit_price, :provider_cost, 1, :total_amount, 'pending',
                    :expires_at, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP
                )
            ", [
                ':order_number' => $orderNumber,
                ':user_id' => $userId,
                ':service_id' => $serviceId,
                ':country_id' => $countryId,
                ':provider_id' => $providerId,
                ':pivot_id' => $stock['pivot_id'],
                ':provider_order_id' => $providerOrderId,
                ':phone_number' => $phoneNumber,
                ':unit_price' => number_format($unitPrice, 4, '.', ''),
                ':provider_cost' => number_format($providerCost, 4, '.', ''),
                ':total_amount' => number_format($unitPrice, 4, '.', ''),
                ':expires_at' => $expiresAt,
            ]);

            $orderId = (int) Database::lastInsertId();

            Logger::info('orders', "Order created successfully", [
                'order_id' => $orderId,
                'order_number' => $orderNumber,
                'phone' => $phoneNumber,
            ]);

            return [
                'success' => true,
                'order_id' => $orderId,
                'order_number' => $orderNumber,
                'phone_number' => $phoneNumber,
                'unit_price' => $unitPrice,
                'status' => 'pending',
                'expires_at' => $expiresAt,
            ];
        } catch (Exception $e) {
            // Provider allocation failed: Rollback by issuing immediate refund!
            Logger::error('orders', "Provider allocation failed, issuing automatic refund", [
                'order_number' => $orderNumber,
                'error' => $e->getMessage(),
            ]);

            WalletService::credit(
                $userId,
                $unitPrice,
                'refund',
                "Automatic refund for failed order {$orderNumber}",
                $orderNumber,
                'order_fail_reversal'
            );

            throw new Exception("Unable to acquire verification number: " . $e->getMessage());
        }
    }

    /**
     * Check if OTP has arrived for a pending order
     */
    public static function checkOtp(int $orderId, ?int $userId = null): array {
        $params = [':id' => $orderId];
        $userCheck = "";
        if ($userId !== null) {
            $userCheck = " AND o.user_id = :uid";
            $params[':uid'] = $userId;
        }

        $order = Database::queryOne("
            SELECT o.*, p.code as provider_code, s.name as service_name
            FROM orders o
            JOIN providers p ON p.id = o.provider_id
            JOIN services s ON s.id = o.service_id
            WHERE o.id = :id {$userCheck}
            LIMIT 1
        ", $params);

        if (!$order) {
            throw new Exception("Order not found.");
        }

        // Already completed
        if ($order['status'] === 'completed') {
            return [
                'status' => 'completed',
                'otp_code' => $order['otp_code'],
                'full_sms' => $order['full_sms_text'],
                'completed_at' => $order['completed_at'],
            ];
        }

        if ($order['status'] !== 'pending') {
            return [
                'status' => $order['status'],
                'otp_code' => null,
                'full_sms' => null,
            ];
        }

        // Check provider for OTP
        $provider = ProviderService::getActiveProvider($order['provider_code']);
        $check = $provider->checkOrderStatus($order['provider_order_id'] ?? '');

        if ($check['status'] === 'completed' && !empty($check['otp_code'])) {
            Database::execute("
                UPDATE orders
                SET status = 'completed',
                    otp_code = :otp,
                    full_sms_text = :sms,
                    completed_at = CURRENT_TIMESTAMP,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = :id
            ", [
                ':otp' => $check['otp_code'],
                ':sms' => $check['full_sms'] ?? 'OTP Received',
                ':id' => $orderId,
            ]);

            NotificationService::send(
                (int) $order['user_id'],
                "OTP Received: {$order['service_name']}",
                "Your OTP code is {$check['otp_code']}",
                'success',
                '/orders'
            );

            return [
                'status' => 'completed',
                'otp_code' => $check['otp_code'],
                'full_sms' => $check['full_sms'],
                'completed_at' => date('Y-m-d H:i:s'),
            ];
        }

        return [
            'status' => 'pending',
            'otp_code' => null,
            'full_sms' => null,
        ];
    }

    /**
     * Cancel a pending order and refund the user's wallet
     */
    public static function cancelOrder(int $orderId, int $userId, string $reason = 'User requested cancellation'): array {
        $order = Database::queryOne("
            SELECT o.*, p.code as provider_code
            FROM orders o
            JOIN providers p ON p.id = o.provider_id
            WHERE o.id = :id AND o.user_id = :uid
            LIMIT 1
        ", [':id' => $orderId, ':uid' => $userId]);

        if (!$order) {
            throw new Exception("Order not found or unauthorized.");
        }

        if ($order['status'] !== 'pending') {
            throw new Exception("Only pending orders can be cancelled. Current status: " . $order['status']);
        }

        // Attempt provider cancellation
        $provider = ProviderService::getActiveProvider($order['provider_code']);
        $provider->cancelOrder($order['provider_order_id'] ?? '');

        // Process refund
        return RefundService::processRefund($orderId, (float) $order['total_amount'], $reason);
    }
}
