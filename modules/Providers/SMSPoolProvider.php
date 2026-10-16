<?php
/**
 * SMSPool Provider Integration Adapter
 * 
 * Implements real HTTP communication with the SMSPool REST API.
 */

declare(strict_types=1);

namespace Modules\Providers;

use App\Helpers\Logger;
use Exception;

class SMSPoolProvider implements ProviderInterface {
    private string $apiKey;
    private string $baseUrl;

    public function __construct(?string $apiKey = null, ?string $baseUrl = null) {
        $this->apiKey = $apiKey ?? env('OTP_PROVIDER_API_KEY', '');
        $this->baseUrl = rtrim($baseUrl ?? env('OTP_PROVIDER_BASE_URL', 'https://api.smspool.net'), '/');
    }

    public function getName(): string {
        return 'SMSPool API';
    }

    public function getCode(): string {
        return 'smspool';
    }

    public function isConfigured(): bool {
        return !empty($this->apiKey);
    }

    private function request(string $endpoint, array $params = [], string $method = 'GET'): array {
        if (!$this->isConfigured()) {
            throw new Exception("SMSPool API key is not configured in .env");
        }

        $url = $this->baseUrl . '/' . ltrim($endpoint, '/');
        $params['key'] = $this->apiKey;

        $ch = curl_init();
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        } else {
            $query = http_build_query($params);
            curl_setopt($ch, CURLOPT_URL, $url . '?' . $query);
        }

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            Logger::error('provider', "SMSPool cURL error: {$error}", ['endpoint' => $endpoint]);
            throw new Exception("Provider network error: {$error}");
        }

        $data = json_decode((string) $response, true);
        if ($httpCode >= 400 || !is_array($data)) {
            Logger::error('provider', "SMSPool HTTP {$httpCode} error", ['response' => $response]);
            throw new Exception("Provider returned error status {$httpCode}");
        }

        return $data;
    }

    public function fetchServices(): array {
        if (!$this->isConfigured()) {
            return [];
        }
        $data = $this->request('service/retrieve_all');
        $services = [];
        foreach ($data as $item) {
            $services[] = [
                'code' => (string) ($item['ID'] ?? $item['id']),
                'name' => (string) ($item['name'] ?? 'Unknown'),
                'cost' => (float) ($item['price'] ?? 0.0),
            ];
        }
        return $services;
    }

    public function fetchCountries(): array {
        if (!$this->isConfigured()) {
            return [];
        }
        $data = $this->request('country/retrieve_all');
        $countries = [];
        foreach ($data as $item) {
            $countries[] = [
                'code' => (string) ($item['short_name'] ?? $item['iso'] ?? 'XX'),
                'name' => (string) ($item['name'] ?? 'Unknown'),
                'dial_code' => (string) ($item['prefix'] ?? '+1'),
            ];
        }
        return $countries;
    }

    public function checkStock(string $serviceCode, string $countryCode): array {
        if (!$this->isConfigured()) {
            return ['status' => 'unknown', 'quantity' => 0, 'price' => 0.0];
        }
        try {
            $data = $this->request('request/price', ['country' => $countryCode, 'service' => $serviceCode]);
            $price = (float) ($data['price'] ?? 0.0);
            $successRate = (float) ($data['success_rate'] ?? 0.0);
            return [
                'status' => $price > 0 ? 'available' : 'unavailable',
                'quantity' => (int) ($data['stock'] ?? 100),
                'price' => $price,
                'success_rate' => $successRate,
            ];
        } catch (Exception) {
            return ['status' => 'unknown', 'quantity' => 0, 'price' => 0.0];
        }
    }

    public function createOrder(string $serviceCode, string $countryCode): array {
        $data = $this->request('purchase/sms', [
            'country' => $countryCode,
            'service' => $serviceCode,
        ], 'POST');

        if (!empty($data['success']) && !empty($data['order_id'])) {
            return [
                'success' => true,
                'provider_order_id' => (string) $data['order_id'],
                'phone_number' => (string) ($data['phonenumber'] ?? $data['number'] ?? ''),
                'cost' => (float) ($data['cost'] ?? $data['price'] ?? 0.0),
            ];
        }

        return [
            'success' => false,
            'provider_order_id' => '',
            'phone_number' => '',
            'cost' => 0.0,
            'error' => $data['message'] ?? 'Unable to acquire number from provider.',
        ];
    }

    public function checkOrderStatus(string $providerOrderId): array {
        $data = $this->request('sms/check', ['orderid' => $providerOrderId]);
        $status = 'pending';
        $code = null;
        $fullSms = null;

        if (isset($data['status'])) {
            if ($data['status'] == 3 || !empty($data['sms'])) {
                $status = 'completed';
                $fullSms = (string) ($data['sms'] ?? '');
                $code = (string) ($data['code'] ?? '');
            } elseif ($data['status'] == 2) {
                $status = 'cancelled';
            } elseif ($data['status'] == 1) {
                $status = 'pending';
            }
        }

        return [
            'status' => $status,
            'otp_code' => $code,
            'full_sms' => $fullSms,
            'raw' => $data,
        ];
    }

    public function cancelOrder(string $providerOrderId): bool {
        try {
            $data = $this->request('sms/cancel', ['orderid' => $providerOrderId], 'POST');
            return !empty($data['success']);
        } catch (Exception) {
            return false;
        }
    }

    public function getBalance(): float {
        if (!$this->isConfigured()) {
            return 0.0;
        }
        $data = $this->request('request/balance');
        return (float) ($data['balance'] ?? 0.0);
    }
}
