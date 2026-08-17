<?php

namespace App\Core;

class Response
{
    private int $statusCode;
    private array $headers;
    private mixed $body;

    public function __construct(mixed $body = '', int $statusCode = 200, array $headers = [])
    {
        $this->body = $body;
        $this->statusCode = $statusCode;
        $this->headers = $headers;
    }

    public static function json(mixed $data, int $statusCode = 200, array $headers = []): self
    {
        $headers['Content-Type'] = 'application/json; charset=utf-8';
        return new self(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), $statusCode, $headers);
    }

    public static function error(string $message, int $statusCode = 400, array $details = []): self
    {
        $payload = [
            'status' => 'error',
            'error' => [
                'message' => $message,
                'code' => $statusCode,
            ]
        ];
        if (!empty($details)) {
            $payload['error']['details'] = $details;
        }

        return self::json($payload, $statusCode);
    }

    public static function html(string $html, int $statusCode = 200): self
    {
        return new self($html, $statusCode, ['Content-Type' => 'text/html; charset=utf-8']);
    }

    public function send(): void
    {
        http_response_code($this->statusCode);

        foreach ($this->headers as $name => $value) {
            header("$name: $value");
        }

        echo is_string($this->body) ? $this->body : json_encode($this->body);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getBody(): mixed
    {
        return $this->body;
    }
}
