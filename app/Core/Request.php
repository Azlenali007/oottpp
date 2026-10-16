<?php
/**
 * HTTP Request Handler
 * 
 * Safely parses URI, method, headers, query parameters, POST body,
 * JSON payloads, and IP addresses.
 */

declare(strict_types=1);

namespace App\Core;

class Request {
    private string $method;
    private string $uri;
    private array $queryParams;
    private array $bodyParams;
    private array $headers;
    private ?array $jsonBody = null;

    public function __construct() {
        $this->method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        
        // Handle method override for RESTful calls (_method in POST or header)
        if ($this->method === 'POST') {
            if (isset($_POST['_method'])) {
                $this->method = strtoupper((string) $_POST['_method']);
            } elseif (isset($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'])) {
                $this->method = strtoupper((string) $_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE']);
            }
        }

        $rawUri = $_SERVER['REQUEST_URI'] ?? '/';
        $path = parse_url($rawUri, PHP_URL_PATH) ?: '/';
        $this->uri = '/' . trim($path, '/');
        if ($this->uri === '//') {
            $this->uri = '/';
        }

        $this->queryParams = $_GET;
        $this->bodyParams = $_POST;
        $this->headers = $this->collectHeaders();
        $this->parseJson();
    }

    private function collectHeaders(): array {
        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $headerName = strtolower(str_replace('_', '-', substr($key, 5)));
                $headers[$headerName] = (string) $value;
            } elseif (in_array($key, ['CONTENT_TYPE', 'CONTENT_LENGTH'])) {
                $headerName = strtolower(str_replace('_', '-', $key));
                $headers[$headerName] = (string) $value;
            }
        }
        return $headers;
    }

    private function parseJson(): void {
        $contentType = $this->headers['content-type'] ?? '';
        if (str_contains(strtolower($contentType), 'application/json')) {
            $rawInput = file_get_contents('php://input');
            if (!empty($rawInput)) {
                $decoded = json_decode($rawInput, true);
                if (is_array($decoded)) {
                    $this->jsonBody = $decoded;
                }
            }
        }
    }

    public function getMethod(): string {
        return $this->method;
    }

    public function getUri(): string {
        return $this->uri;
    }

    public function query(?string $key = null, mixed $default = null): mixed {
        if ($key === null) {
            return $this->queryParams;
        }
        return $this->queryParams[$key] ?? $default;
    }

    public function input(?string $key = null, mixed $default = null): mixed {
        $data = $this->jsonBody ?? $this->bodyParams;
        if ($key === null) {
            return array_merge($this->queryParams, $data);
        }
        return $data[$key] ?? $this->queryParams[$key] ?? $default;
    }

    public function json(?string $key = null, mixed $default = null): mixed {
        if ($this->jsonBody === null) {
            return $default;
        }
        if ($key === null) {
            return $this->jsonBody;
        }
        return $this->jsonBody[$key] ?? $default;
    }

    public function header(string $key, ?string $default = null): ?string {
        $key = strtolower($key);
        return $this->headers[$key] ?? $default;
    }

    public function isAjax(): bool {
        return ($this->header('x-requested-with') === 'XMLHttpRequest') ||
               str_contains($this->header('accept', ''), 'application/json') ||
               str_contains($this->header('content-type', ''), 'application/json');
    }

    public function getIp(): string {
        return $_SERVER['HTTP_CF_CONNECTING_IP']
            ?? $_SERVER['HTTP_X_FORWARDED_FOR']
            ?? $_SERVER['REMOTE_ADDR']
            ?? '127.0.0.1';
    }

    public function getUserAgent(): string {
        return $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    }
}
