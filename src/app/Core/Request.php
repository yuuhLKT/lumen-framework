<?php

declare(strict_types=1);

namespace App\Core;

final class Request
{
    /** @var array<string, mixed>|null */
    private ?array $user = null;

    /**
     * @param array<string, mixed> $query
     * @param array<string, mixed> $body
     * @param array<string, mixed> $server
     * @param array<string, string> $headers
     */
    public function __construct(
        private readonly string $method,
        private readonly string $path,
        private readonly array $query,
        private readonly array $body,
        private readonly array $server,
        private readonly array $headers,
    ) {
    }

    public static function capture(): self
    {
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';

        return new self(
            $method,
            self::normalizePath($path),
            $_GET,
            self::parseBody($method),
            $_SERVER,
            self::captureHeaders($_SERVER),
        );
    }

    public function method(): string
    {
        return $this->method;
    }

    public function path(): string
    {
        return $this->path;
    }

    public function query(?string $key = null, mixed $default = null): mixed
    {
        return $key === null ? $this->query : ($this->query[$key] ?? $default);
    }

    public function input(?string $key = null, mixed $default = null): mixed
    {
        return $key === null ? $this->body : ($this->body[$key] ?? $default);
    }

    public function server(string $key, mixed $default = null): mixed
    {
        return $this->server[$key] ?? $default;
    }

    public function header(string $key, mixed $default = null): mixed
    {
        $normalized = strtolower($key);

        return $this->headers[$normalized] ?? $default;
    }

    public function bearerToken(): ?string
    {
        $authorization = $this->header('Authorization');

        if (!is_string($authorization) || !preg_match('/^Bearer\s+(.+)$/i', trim($authorization), $matches)) {
            return null;
        }

        $token = trim($matches[1]);

        return $token === '' ? null : $token;
    }

    /** @param array<string, mixed>|null $user */
    public function setUser(?array $user): void
    {
        $this->user = $user;
    }

    /** @return array<string, mixed>|null */
    public function user(): ?array
    {
        return $this->user;
    }

    /**
     * @param array<string, mixed> $server
     * @return array<string, string>
     */
    private static function captureHeaders(array $server): array
    {
        $headers = [];

        foreach ($server as $key => $value) {
            if (!is_string($value)) {
                continue;
            }

            if (str_starts_with($key, 'HTTP_')) {
                $name = str_replace('_', '-', substr($key, 5));
                $headers[strtolower($name)] = $value;
            }
        }

        foreach (['CONTENT_TYPE', 'CONTENT_LENGTH', 'AUTHORIZATION', 'REDIRECT_HTTP_AUTHORIZATION'] as $key) {
            if (isset($server[$key]) && is_string($server[$key])) {
                $name = $key === 'REDIRECT_HTTP_AUTHORIZATION' ? 'AUTHORIZATION' : $key;
                $headers[strtolower(str_replace('_', '-', $name))] = $server[$key];
            }
        }

        return $headers;
    }

    /** @return array<string, mixed> */
    private static function parseBody(string $method): array
    {
        if ($method === 'GET') {
            return [];
        }

        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        $rawBody = file_get_contents('php://input') ?: '';

        if (str_contains($contentType, 'application/json')) {
            $json = json_decode($rawBody, true);

            return is_array($json) ? self::stringKeys($json) : [];
        }

        if (!empty($_POST)) {
            return self::stringKeys($_POST);
        }

        parse_str($rawBody, $data);

        return self::stringKeys($data);
    }

    /**
     * @param array<int|string, mixed> $data
     * @return array<string, mixed>
     */
    private static function stringKeys(array $data): array
    {
        $result = [];

        foreach ($data as $key => $value) {
            $result[(string) $key] = $value;
        }

        return $result;
    }

    private static function normalizePath(string $path): string
    {
        $path = '/' . trim($path, '/');

        return $path === '/' ? '/' : rtrim($path, '/');
    }
}
