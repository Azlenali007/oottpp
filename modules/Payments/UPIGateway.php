<?php
/**
 * UPI Payment Gateway Adapter
 * 
 * Supports UPI Intent, dynamic QR generation, and HMAC-SHA256 signed callbacks.
 */

declare(strict_types=1);

namespace Modules\Payments;

use App\Helpers\Logger;
use Exception;

class UPIGateway implements PaymentGatewayInterface {
    private string $merchantId;
    private string $apiKey;
    private string $webhookSecret;
    private string $baseUrl;

    public function __construct() {
        $this->merchantId = env('PAYMENT_GATEWAY_MERCHANT_ID', '');
        $this->apiKey = env('PAYMENT_GATEWAY_API_KEY', '');
        $this->webhookSecret = env('PAYMENT_GATEWAY_WEBHOOK_SECRET', '');
        $this->baseUrl = env('PAYMENT_GATEWAY_BASE_URL', 'https://api.upigateway.com/v1');
    }

    public function getName(): string {
        return 'Instant UPI / QR Gateway';
    }

    public function getCode(): string {
        return 'upi_gateway';
    }

    public function isConfigured(): bool {
        return !empty($this->merchantId) && !empty($this->apiKey);
    }

    public function createPayment(float $amount, string $currency, int $userId, string $idempotencyKey): array {
        if (!$this->isConfigured()) {
            throw new Exception("UPI Gateway credentials are not configured.");
        }

        $payload = [
            'merchant_id' => $this->merchantId,
            'amount' => number_format($amount, 2, '.', ''),
            'currency' => $currency,
            'customer_id' => (string) $userId,
            'order_id' => $idempotencyKey,
            'redirect_url' => env('APP_URL') . '/wallet',
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->baseUrl . '/order/create');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->apiKey,
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);

        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            Logger::error('payments', "UPI cURL error: {$error}");
            return ['success' => false, 'payment_reference' => '', 'error' => $error];
        }

        $data = json_decode((string) $response, true);
        if (!empty($data['status']) && $data['status'] === 'success') {
            return [
                'success' => true,
                'payment_reference' => (string) ($data['payment_id'] ?? $idempotencyKey),
                'checkout_url' => (string) ($data['payment_url'] ?? ''),
                'qr_data' => (string) ($data['upi_intent'] ?? ''),
            ];
        }

        return [
            'success' => false,
            'payment_reference' => '',
            'error' => $data['message'] ?? 'Failed to initialize UPI session',
        ];
    }

    public function verifyPayment(string $paymentReference): array {
        if (!$this->isConfigured()) {
            return ['status' => 'pending', 'transaction_id' => '', 'amount' => 0.0, 'verified' => false];
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->baseUrl . '/order/status?order_id=' . urlencode($paymentReference));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $this->apiKey]);
        $response = curl_exec($ch);
        curl_close($ch);

        $data = json_decode((string) $response, true);
        $status = ($data['status'] ?? '') === 'SUCCESS' ? 'completed' : 'pending';

        return [
            'status' => $status,
            'transaction_id' => (string) ($data['txn_id'] ?? ''),
            'amount' => (float) ($data['amount'] ?? 0.0),
            'verified' => $status === 'completed',
        ];
    }

    public function verifyWebhook(string $rawPayload, string $signatureHeader): array {
        if (empty($this->webhookSecret)) {
            return ['verified' => false, 'status' => 'invalid', 'payment_reference' => '', 'transaction_id' => '', 'amount' => 0.0, 'raw' => []];
        }

        $expectedSig = hash_hmac('sha256', $rawPayload, $this->webhookSecret);
        if (!hash_equals($expectedSig, $signatureHeader)) {
            Logger::warning('payments', "Invalid webhook signature received");
            return ['verified' => false, 'status' => 'invalid_signature', 'payment_reference' => '', 'transaction_id' => '', 'amount' => 0.0, 'raw' => []];
        }

        $data = json_decode($rawPayload, true) ?: [];
        $status = ($data['status'] ?? '') === 'SUCCESS' ? 'completed' : 'failed';

        return [
            'verified' => true,
            'status' => $status,
            'payment_reference' => (string) ($data['order_id'] ?? ''),
            'transaction_id' => (string) ($data['txn_id'] ?? ''),
            'amount' => (float) ($data['amount'] ?? 0.0),
            'raw' => $data,
        ];
    }
}
