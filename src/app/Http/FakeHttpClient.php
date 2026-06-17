<?php

declare(strict_types=1);

namespace App\Http;

use RuntimeException;

final class FakeHttpClient implements HttpClient
{
    /** @var array<int, HttpResponse> */
    private array $responses;

    /** @var array<int, array{method: string, url: string, options: array<string, mixed>}> */
    private array $requests = [];

    public function __construct(HttpResponse ...$responses)
    {
        $this->responses = array_values($responses);
    }

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
            'headers' => $headers,
            'json' => $data,
            'timeout' => $timeout,
        ]);
    }

    public function request(string $method, string $url, array $options = []): HttpResponse
    {
        $this->requests[] = [
            'method' => strtoupper($method),
            'url' => $url,
            'options' => $options,
        ];

        if ($this->responses === []) {
            throw new RuntimeException('Nenhuma resposta fake configurada.');
        }

        return array_shift($this->responses);
    }

    /** @return array<int, array{method: string, url: string, options: array<string, mixed>}> */
    public function requests(): array
    {
        return $this->requests;
    }
}
