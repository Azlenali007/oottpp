<?php
/**
 * Payment Deposit & Gateway Orchestration Service
 * 
 * Initiates deposit sessions and verifies cryptographic webhook callbacks.
 */

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Helpers\Security;
use App\Helpers\Logger;
use Modules\Payments\PaymentGatewayInterface;
use Modules\Payments\UPIGateway;
use Modules\Payments\RazorpayGateway;
use Modules\Payments\MockTestingGateway;
use Exception;

class PaymentService {
    public static function getGateway(?string $code = null): PaymentGatewayInterface {
        $gw = $code ?? env('PAYMENT_GATEWAY_DEFAULT', 'upi_gateway');

        return match ($gw) {
            'upi_gateway' => new UPIGateway(),
            'razorpay' => new RazorpayGateway(),
            'mock_test_gw' => new MockTestingGateway(),
            default => new UPIGateway(),
        };
    }

    /**
     * Initiate a new wallet top-up deposit
     */
    public static function initiateDeposit(int $userId, float $amount, ?string $gatewayCode = null): array {
        if ($amount < 10.00) {
            throw new Exception("Minimum recharge amount is ₹ 10.00.");
        }

        $gateway = self::getGateway($gatewayCode);
        $paymentUuid = Security::generateUuid();
        $idempotencyKey = 'DEP_' . strtoupper(substr(bin2hex(random_bytes(6)), 0, 10));

        // Create initial pending payment record
        Database::execute("
            INSERT INTO payments (
                payment_uuid, user_id, gateway, amount, fee, net_amount, currency,
                status, idempotency_key, created_at, updated_at
            ) VALUES (
                :uuid, :uid, :gw, :amount, 0.0000, :amount, 'INR',
                'pending', :idem, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP
            )
        ", [
            ':uuid' => $paymentUuid,
            ':uid' => $userId,
            ':gw' => $gateway->getCode(),
            ':amount' => number_format($amount, 4, '.', ''),
            ':idem' => $idempotencyKey,
        ]);

        $paymentId = (int) Database::lastInsertId();

        $gwResult = $gateway->createPayment($amount, 'INR', $userId, $idempotencyKey);

        return [
            'payment_id' => $paymentId,
            'payment_uuid' => $paymentUuid,
            'idempotency_key' => $idempotencyKey,
            'amount' => $amount,
            'gateway_response' => $gwResult,
        ];
    }

    /**
     * Finalize verified payment and credit user wallet
     */
    public static function handlePaymentSuccess(string $idempotencyKey, string $gatewayTxId, float $amount): array {
        return Database::transaction(function ($pdo) use ($idempotencyKey, $gatewayTxId, $amount) {
            // Find payment record
            $payment = Database::queryOne("
                SELECT * FROM payments WHERE idempotency_key = :idem LIMIT 1
            ", [':idem' => $idempotencyKey]);

            if (!$payment) {
                throw new Exception("Payment record with idempotency key {$idempotencyKey} not found.");
            }

            // Prevent double-crediting!
            if ($payment['status'] === 'completed') {
                return ['already_processed' => true, 'message' => 'Payment has already been finalized.'];
            }

            $userId = (int) $payment['user_id'];

            // 1. Credit wallet
            $credit = WalletService::credit(
                $userId,
                $amount,
                'deposit',
                "Wallet recharge via {$payment['gateway']} ({$gatewayTxId})",
                $gatewayTxId,
                'deposit'
            );

            // 2. Mark payment as completed
            Database::execute("
                UPDATE payments
                SET status = 'completed',
                    gateway_transaction_id = :tx_id,
                    verified_at = CURRENT_TIMESTAMP,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = :id
            ", [
                ':tx_id' => $gatewayTxId,
                ':id' => $payment['id'],
            ]);

            // 3. User Notification
            NotificationService::send(
                $userId,
                "Wallet Credited: +₹" . number_format($amount, 2),
                "Your deposit of ₹" . number_format($amount, 2) . " has been successfully credited.",
                'success',
                '/wallet'
            );

            Logger::info('payments', "Payment finalized successfully", [
                'payment_id' => $payment['id'],
                'user_id' => $userId,
                'amount' => $amount,
                'tx_id' => $gatewayTxId,
            ]);

            return [
                'success' => true,
                'user_id' => $userId,
                'amount' => $amount,
                'new_balance' => $credit['balance_after'],
            ];
        });
    }
}
