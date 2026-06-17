<?php

declare(strict_types=1);

namespace App\Http;

final class HttpResponse
{
    /** @param array<string, string> $headers */
    public function __construct(
        private readonly int $status,
        private readonly string $body,
        private readonly array $headers = [],
    ) {
    }

    public function status(): int
    {
        return $this->status;
    }

    public function body(): string
    {
        return $this->body;
    }

    /** @return array<string, string> */
    public function headers(): array
    {
        return $this->headers;
    }

    public function successful(): bool
    {
        return $this->status >= 200 && $this->status < 300;
    }

    public function json(?string $key = null, mixed $default = null): mixed
    {
        $data = json_decode($this->body, true);

        if (!is_array($data)) {
            return $default;
        }

        return $key === null ? $data : ($data[$key] ?? $default);
    }
}
