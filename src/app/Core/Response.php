<?php

declare(strict_types=1);

namespace App\Core;

final class Response
{
    /** @param array<string, string> $headers */
    public function __construct(
        private readonly string $content,
        private readonly int $status = 200,
        private readonly array $headers = [],
    ) {
    }

    /** @param mixed $data */
    public static function json(mixed $data, int $status = 200, array $headers = []): self
    {
        $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return new self($json === false ? '{}' : $json, $status, [
            'Content-Type' => 'application/json; charset=utf-8',
            ...$headers,
        ]);
    }

    public static function html(string $content, int $status = 200, array $headers = []): self
    {
        return new self($content, $status, [
            'Content-Type' => 'text/html; charset=utf-8',
            ...$headers,
        ]);
    }

    public static function text(string $content, int $status = 200, array $headers = []): self
    {
        return new self($content, $status, [
            'Content-Type' => 'text/plain; charset=utf-8',
            ...$headers,
        ]);
    }

    public static function redirect(string $url, int $status = 302): self
    {
        return new self('', $status, ['Location' => $url]);
    }

    public function send(): void
    {
        http_response_code($this->status);

        foreach ($this->headers as $name => $value) {
            header($name . ': ' . $value);
        }

        echo $this->content;
    }
}
