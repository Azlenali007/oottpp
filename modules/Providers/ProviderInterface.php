<?php
/**
 * Provider API Interface
 * 
 * Formal contract for all OTP verification provider adapters.
 */

declare(strict_types=1);

namespace Modules\Providers;

interface ProviderInterface {
    public function getName(): string;
    public function getCode(): string;
    public function isConfigured(): bool;

    /**
     * Fetch list of available services from provider
     * @return array<int, array{code: string, name: string, cost: float}>
     */
    public function fetchServices(): array;

    /**
     * Fetch list of supported countries from provider
     * @return array<int, array{code: string, name: string, dial_code: string}>
     */
    public function fetchCountries(): array;

    /**
     * Check current stock and availability
     * @return array{status: string, quantity: int, price: float}
     */
    public function checkStock(string $serviceCode, string $countryCode): array;

    /**
     * Create/request a new phone number order
     * @return array{success: bool, provider_order_id: string, phone_number: string, cost: float, error?: string}
     */
    public function createOrder(string $serviceCode, string $countryCode): array;

    /**
     * Check status and retrieve SMS message or OTP code
     * @return array{status: string, otp_code: ?string, full_sms: ?string, raw: array}
     */
    public function checkOrderStatus(string $providerOrderId): array;

    /**
     * Request order cancellation
     */
    public function cancelOrder(string $providerOrderId): bool;

    /**
     * Check provider account balance
     */
    public function getBalance(): float;
}
