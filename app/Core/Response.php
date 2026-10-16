<?php
/**
 * HTTP Response Handler
 * 
 * Manages HTTP status codes, JSON responses, view rendering,
 * headers, redirects, and cookie delivery.
 */

declare(strict_types=1);

namespace App\Core;

class Response {
    private int $statusCode = 200;
    private array $headers = [];
    private string $content = '';

    public function setStatusCode(int $code): self {
        $this->statusCode = $code;
        return $this;
    }

    public function setHeader(string $name, string $value): self {
        $this->headers[$name] = $value;
        return $this;
    }

    public function setContent(string $content): self {
        $this->content = $content;
        return $this;
    }

    public function json(array $data, int $statusCode = 200): void {
        $this->setStatusCode($statusCode);
        $this->setHeader('Content-Type', 'application/json; charset=UTF-8');
        $this->setContent(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        $this->send();
    }

    public function redirect(string $url, int $statusCode = 302): void {
        $this->setStatusCode($statusCode);
        $this->setHeader('Location', $url);
        $this->send();
        exit(0);
    }

    public function send(): void {
        if (!headers_sent()) {
            http_response_code($this->statusCode);
            foreach ($this->headers as $name => $value) {
                header("{$name}: {$value}");
            }
        }
        echo $this->content;
    }
}
