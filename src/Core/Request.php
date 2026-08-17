<?php

namespace App\Core;

class Request
{
    private string $method;
    private string $uri;
    private array $headers;
    private array $queryParams;
    private mixed $body;

    public function __construct(
        string $method,
        string $uri,
        array $headers = [],
        array $queryParams = [],
        mixed $body = null
    ) {
        $this->method = strtoupper($method);
        $this->uri = parse_url($uri, PHP_URL_PATH) ?? '/';
        $this->headers = array_change_key_case($headers, CASE_LOWER);
        $this->queryParams = $queryParams;
        $this->body = $body;
    }

    public static function createFromGlobals(): self
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $headers = function_exists('getallheaders') ? getallheaders() : self::extractHeadersFromGlobals();
        $queryParams = $_GET;

        $rawBody = file_get_contents('php://input');
        $body = null;
        if (!empty($rawBody)) {
            $decoded = json_decode($rawBody, true);
            $body = (json_last_error() === JSON_ERROR_NONE) ? $decoded : $rawBody;
        } else {
            $body = $_POST;
        }

        return new self($method, $uri, $headers, $queryParams, $body);
    }

    private static function extractHeadersFromGlobals(): array
    {
        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $name = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($key, 5)))));
                $headers[$name] = $value;
            }
        }
        return $headers;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getUri(): string
    {
        return $this->uri;
    }

    public function getHeader(string $name, ?string $default = null): ?string
    {
        $key = strtolower($name);
        return $this->headers[$key] ?? $default;
    }

    public function getQuery(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->queryParams;
        }
        return $this->queryParams[$key] ?? $default;
    }

    public function getBody(): mixed
    {
        return $this->body;
    }
}
