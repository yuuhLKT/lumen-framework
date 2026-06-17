<?php

declare(strict_types=1);

namespace App\Http;

use RuntimeException;

final class NativeHttpClient implements HttpClient
{
    public function get(string $url, array $headers = [], int $timeout = 5): HttpResponse
    {
        return $this->request('GET', $url, [
            'headers' => $headers,
            'timeout' => $timeout,
        ]);
    }

    public function post(string $url, array $data = [], array $headers = [], int $timeout = 5): HttpResponse
    {
        return $this->request('POST', $url, [
            'headers' => ['Content-Type' => 'application/json', ...$headers],
            'json' => $data,
            'timeout' => $timeout,
        ]);
    }

    public function request(string $method, string $url, array $options = []): HttpResponse
    {
        $method = strtoupper($method);
        $headers = $this->formatHeaders($options['headers'] ?? []);
        $body = $this->body($options);

        $context = stream_context_create([
            'http' => [
                'method' => $method,
                'header' => implode("\r\n", $headers),
                'content' => $body,
                'timeout' => (int) ($options['timeout'] ?? 5),
                'ignore_errors' => true,
            ],
        ]);

        $responseBody = @file_get_contents($url, false, $context);

        if ($responseBody === false) {
            throw new RuntimeException("Falha ao chamar URL [{$url}].");
        }

        return new HttpResponse($this->status($http_response_header), $responseBody, $this->headers($http_response_header));
    }

    /** @param array<string, mixed> $options */
    private function body(array $options): ?string
    {
        if (array_key_exists('json', $options)) {
            $json = json_encode($options['json'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            return $json === false ? '{}' : $json;
        }

        if (isset($options['body']) && is_string($options['body'])) {
            return $options['body'];
        }

        return null;
    }

    /** @param array<string, string> $headers */
    private function formatHeaders(array $headers): array
    {
        $formatted = [];

        foreach ($headers as $name => $value) {
            $formatted[] = $name . ': ' . $value;
        }

        return $formatted;
    }

    /** @param array<int, string> $headers */
    private function status(array $headers): int
    {
        if (($headers[0] ?? '') !== '' && preg_match('/\s(\d{3})\s/', $headers[0], $matches)) {
            return (int) $matches[1];
        }

        return 0;
    }

    /** @param array<int, string> $rawHeaders */
    private function headers(array $rawHeaders): array
    {
        $headers = [];

        foreach ($rawHeaders as $header) {
            if (!str_contains($header, ':')) {
                continue;
            }

            [$name, $value] = explode(':', $header, 2);
            $headers[strtolower(trim($name))] = trim($value);
        }

        return $headers;
    }
}
