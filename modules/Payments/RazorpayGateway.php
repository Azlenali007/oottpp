<?php
/**
 * Razorpay Payment Gateway Adapter
 * 
 * Supports Razorpay Order creation and HMAC-SHA256 signature verification.
 */

declare(strict_types=1);

namespace Modules\Payments;

use App\Helpers\Logger;
use Exception;

class RazorpayGateway implements PaymentGatewayInterface {
    private string $keyId;
    private string $keySecret;
    private string $webhookSecret;

    public function __construct() {
        $this->keyId = env('RAZORPAY_KEY_ID', '');
        $this->keySecret = env('RAZORPAY_KEY_SECRET', '');
        $this->webhookSecret = env('RAZORPAY_WEBHOOK_SECRET', '');
    }

    public function getName(): string {
        return 'Razorpay';
    }

    public function getCode(): string {
        return 'razorpay';
    }

    public function isConfigured(): bool {
        return !empty($this->keyId) && !empty($this->keySecret);
    }

    public function createPayment(float $amount, string $currency, int $userId, string $idempotencyKey): array {
        if (!$this->isConfigured()) {
            throw new Exception("Razorpay credentials are not configured.");
        }

        $amountInSubunits = (int) round($amount * 100);
        $payload = [
            'amount' => $amountInSubunits,
            'currency' => $currency,
            'receipt' => $idempotencyKey,
            'notes' => ['user_id' => (string) $userId],
        ];

        $ch = curl_init('https://api.razorpay.com/v1/orders');
        curl_setopt($ch, CURLOPT_USERPWD, $this->keyId . ':' . $this->keySecret);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        $response = curl_exec($ch);
        curl_close($ch);

        $data = json_decode((string) $response, true);
        if (!empty($data['id'])) {
            return [
                'success' => true,
                'payment_reference' => (string) $data['id'],
                'checkout_url' => '',
            ];
        }

        return [
            'success' => false,
            'payment_reference' => '',
            'error' => $data['error']['description'] ?? 'Razorpay order creation failed',
        ];
    }

    public function verifyPayment(string $paymentReference): array {
        if (!$this->isConfigured()) {
            return ['status' => 'pending', 'transaction_id' => '', 'amount' => 0.0, 'verified' => false];
        }

        $ch = curl_init("https://api.razorpay.com/v1/payments/{$paymentReference}");
        curl_setopt($ch, CURLOPT_USERPWD, $this->keyId . ':' . $this->keySecret);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);

        $data = json_decode((string) $response, true);
        $status = ($data['status'] ?? '') === 'captured' ? 'completed' : 'pending';

        return [
            'status' => $status,
            'transaction_id' => (string) ($data['id'] ?? ''),
            'amount' => (float) (($data['amount'] ?? 0) / 100),
            'verified' => $status === 'completed',
        ];
    }

    public function verifyWebhook(string $rawPayload, string $signatureHeader): array {
        $expected = hash_hmac('sha256', $rawPayload, $this->webhookSecret);
        if (!hash_equals($expected, $signatureHeader)) {
            return ['verified' => false, 'status' => 'invalid_signature', 'payment_reference' => '', 'transaction_id' => '', 'amount' => 0.0, 'raw' => []];
        }

        $event = json_decode($rawPayload, true) ?: [];
        $payment = $event['payload']['payment']['entity'] ?? [];
        $status = ($event['event'] ?? '') === 'payment.captured' ? 'completed' : 'pending';

        return [
            'verified' => true,
            'status' => $status,
            'payment_reference' => (string) ($payment['order_id'] ?? ''),
            'transaction_id' => (string) ($payment['id'] ?? ''),
            'amount' => (float) (($payment['amount'] ?? 0) / 100),
            'raw' => $event,
        ];
    }
}
