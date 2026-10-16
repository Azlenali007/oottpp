<?php
/**
 * Mock Payment Gateway Adapter
 * 
 * STRICTLY for test suites and development environments.
 * Refuses execution if APP_ENV === 'production'.
 */

declare(strict_types=1);

namespace Modules\Payments;

use Exception;

class MockTestingGateway implements PaymentGatewayInterface {
    public function __construct() {
        if (env('APP_ENV') === 'production') {
            throw new Exception("MockTestingGateway is strictly forbidden in production mode!");
        }
    }

    public function getName(): string {
        return 'Mock Testing Gateway';
    }

    public function getCode(): string {
        return 'mock_test_gw';
    }

    public function isConfigured(): bool {
        return true;
    }

    public function createPayment(float $amount, string $currency, int $userId, string $idempotencyKey): array {
        return [
            'success' => true,
            'payment_reference' => 'TEST_PAY_' . mt_rand(100000, 999999),
            'checkout_url' => '/wallet?status=mock_ready',
        ];
    }

    public function verifyPayment(string $paymentReference): array {
        return [
            'status' => 'completed',
            'transaction_id' => 'TXN_TEST_' . mt_rand(1000000, 9999999),
            'amount' => 500.00,
            'verified' => true,
        ];
    }

    public function verifyWebhook(string $rawPayload, string $signatureHeader): array {
        $data = json_decode($rawPayload, true) ?: [];
        return [
            'verified' => true,
            'status' => 'completed',
            'payment_reference' => (string) ($data['payment_reference'] ?? 'TEST_REF'),
            'transaction_id' => 'TXN_TEST_' . mt_rand(1000, 9999),
            'amount' => (float) ($data['amount'] ?? 500.00),
            'raw' => $data,
        ];
    }
}
