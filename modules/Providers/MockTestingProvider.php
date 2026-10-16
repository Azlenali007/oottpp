<?php
/**
 * Mock Testing Provider Adapter
 * 
 * STRICTLY for local automated test suites and development environments.
 * Automatically refuses to run if APP_ENV is set to production.
 */

declare(strict_types=1);

namespace Modules\Providers;

use Exception;

class MockTestingProvider implements ProviderInterface {
    public function __construct() {
        if (env('APP_ENV') === 'production') {
            throw new Exception("MockTestingProvider is strictly forbidden in production mode!");
        }
    }

    public function getName(): string {
        return 'Mock Testing Adapter (Development Only)';
    }

    public function getCode(): string {
        return 'mock_test';
    }

    public function isConfigured(): bool {
        return true;
    }

    public function fetchServices(): array {
        return [
            ['code' => 'whatsapp', 'name' => 'WhatsApp', 'cost' => 0.80],
            ['code' => 'telegram', 'name' => 'Telegram', 'cost' => 0.70],
            ['code' => 'google', 'name' => 'Google', 'cost' => 1.10],
            ['code' => 'instagram', 'name' => 'Instagram', 'cost' => 0.90],
            ['code' => 'facebook', 'name' => 'Facebook', 'cost' => 0.85],
        ];
    }

    public function fetchCountries(): array {
        return [
            ['code' => 'IN', 'name' => 'India', 'dial_code' => '+91'],
            ['code' => 'US', 'name' => 'United States', 'dial_code' => '+1'],
            ['code' => 'GB', 'name' => 'United Kingdom', 'dial_code' => '+44'],
            ['code' => 'CA', 'name' => 'Canada', 'dial_code' => '+1'],
        ];
    }

    public function checkStock(string $serviceCode, string $countryCode): array {
        return [
            'status' => 'available',
            'quantity' => 1500,
            'price' => 0.80,
            'success_rate' => 98.5,
        ];
    }

    public function createOrder(string $serviceCode, string $countryCode): array {
        $mockId = 'MOCK_' . mt_rand(100000, 999999);
        $mockNumber = '+9198' . mt_rand(10000000, 99999999);
        return [
            'success' => true,
            'provider_order_id' => $mockId,
            'phone_number' => $mockNumber,
            'cost' => 0.80,
        ];
    }

    public function checkOrderStatus(string $providerOrderId): array {
        return [
            'status' => 'completed',
            'otp_code' => (string) mt_rand(10000, 99999),
            'full_sms' => 'Your OTP verification code is ' . mt_rand(10000, 99999),
            'raw' => ['mock' => true],
        ];
    }

    public function cancelOrder(string $providerOrderId): bool {
        return true;
    }

    public function getBalance(): float {
        return 9999.00;
    }
}
