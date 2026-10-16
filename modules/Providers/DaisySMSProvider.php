<?php
/**
 * DaisySMS Provider Integration Adapter
 * 
 * Implements real HTTP communication with the DaisySMS API.
 */

declare(strict_types=1);

namespace Modules\Providers;

use App\Helpers\Logger;
use Exception;

class DaisySMSProvider implements ProviderInterface {
    private string $apiKey;
    private string $baseUrl;

    public function __construct(?string $apiKey = null, ?string $baseUrl = null) {
        $this->apiKey = $apiKey ?? env('DAISYSMS_API_KEY', '');
        $this->baseUrl = rtrim($baseUrl ?? 'https://daisysms.com/stubs/handler_api.php', '/');
    }

    public function getName(): string {
        return 'DaisySMS API';
    }

    public function getCode(): string {
        return 'daisysms';
    }

    public function isConfigured(): bool {
        return !empty($this->apiKey);
    }

    private function request(array $params = []): string {
        if (!$this->isConfigured()) {
            throw new Exception("DaisySMS API key is not configured in .env");
        }

        $params['api_key'] = $this->apiKey;
        $url = $this->baseUrl . '?' . http_build_query($params);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        $response = (string) curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            Logger::error('provider', "DaisySMS cURL error: {$error}");
            throw new Exception("Provider network error: {$error}");
        }

        return trim($response);
    }

    public function fetchServices(): array {
        if (!$this->isConfigured()) {
            return [];
        }
        $raw = $this->request(['action' => 'getPricesVerification']);
        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return [];
        }

        $services = [];
        foreach ($decoded as $countryId => $servs) {
            foreach ($servs as $code => $info) {
                $services[] = [
                    'code' => (string) $code,
                    'name' => ucfirst($code),
                    'cost' => (float) ($info['cost'] ?? 0.0),
                ];
            }
            break;
        }
        return $services;
    }

    public function fetchCountries(): array {
        return [
            ['code' => 'US', 'name' => 'United States', 'dial_code' => '+1'],
            ['code' => 'CA', 'name' => 'Canada', 'dial_code' => '+1'],
            ['code' => 'GB', 'name' => 'United Kingdom', 'dial_code' => '+44'],
        ];
    }

    public function checkStock(string $serviceCode, string $countryCode): array {
        if (!$this->isConfigured()) {
            return ['status' => 'unknown', 'quantity' => 0, 'price' => 0.0];
        }
        $raw = $this->request(['action' => 'getPricesVerification', 'service' => $serviceCode]);
        $data = json_decode($raw, true);
        if (is_array($data) && !empty($data)) {
            return ['status' => 'available', 'quantity' => 100, 'price' => 1.50];
        }
        return ['status' => 'unavailable', 'quantity' => 0, 'price' => 0.0];
    }

    public function createOrder(string $serviceCode, string $countryCode): array {
        $raw = $this->request([
            'action' => 'getNumber',
            'service' => $serviceCode,
        ]);

        if (str_starts_with($raw, 'ACCESS_NUMBER:')) {
            $parts = explode(':', $raw);
            return [
                'success' => true,
                'provider_order_id' => $parts[1] ?? '',
                'phone_number' => $parts[2] ?? '',
                'cost' => 1.50,
            ];
        }

        return [
            'success' => false,
            'provider_order_id' => '',
            'phone_number' => '',
            'cost' => 0.0,
            'error' => $raw,
        ];
    }

    public function checkOrderStatus(string $providerOrderId): array {
        $raw = $this->request(['action' => 'getStatus', 'id' => $providerOrderId]);

        if (str_starts_with($raw, 'STATUS_OK:')) {
            $code = substr($raw, strlen('STATUS_OK:'));
            return [
                'status' => 'completed',
                'otp_code' => $code,
                'full_sms' => "Verification code is {$code}",
                'raw' => ['response' => $raw],
            ];
        }

        if ($raw === 'STATUS_CANCEL') {
            return ['status' => 'cancelled', 'otp_code' => null, 'full_sms' => null, 'raw' => []];
        }

        return ['status' => 'pending', 'otp_code' => null, 'full_sms' => null, 'raw' => ['response' => $raw]];
    }

    public function cancelOrder(string $providerOrderId): bool {
        $raw = $this->request(['action' => 'setStatus', 'id' => $providerOrderId, 'status' => '8']);
        return str_starts_with($raw, 'ACCESS_CANCEL');
    }

    public function getBalance(): float {
        if (!$this->isConfigured()) {
            return 0.0;
        }
        $raw = $this->request(['action' => 'getBalance']);
        if (str_starts_with($raw, 'ACCESS_BALANCE:')) {
            return (float) substr($raw, strlen('ACCESS_BALANCE:'));
        }
        return 0.0;
    }
}
