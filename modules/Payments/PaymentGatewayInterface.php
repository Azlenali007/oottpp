<?php
/**
 * Payment Gateway Contract
 * 
 * Formal interface for deposit gateways with signed webhook verification.
 */

declare(strict_types=1);

namespace Modules\Payments;

interface PaymentGatewayInterface {
    public function getName(): string;
    public function getCode(): string;
    public function isConfigured(): bool;

    /**
     * Initiate a new payment session
     * @return array{success: bool, payment_reference: string, checkout_url?: string, qr_data?: string, error?: string}
     */
    public function createPayment(float $amount, string $currency, int $userId, string $idempotencyKey): array;

    /**
     * Query gateway directly to verify payment status
     * @return array{status: string, transaction_id: string, amount: float, verified: bool}
     */
    public function verifyPayment(string $paymentReference): array;

    /**
     * Verify signed incoming webhook payload
     * @return array{verified: bool, status: string, payment_reference: string, transaction_id: string, amount: float, raw: array}
     */
    public function verifyWebhook(string $rawPayload, string $signatureHeader): array;
}
