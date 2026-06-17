<?php

declare(strict_types=1);

namespace App\Http;

interface HttpClient
{
    /** @param array<string, string> $headers */
    public function get(string $url, array $headers = [], int $timeout = 5): HttpResponse;

    /**
     * @param array<string, mixed> $data
     * @param array<string, string> $headers
     */
    public function post(string $url, array $data = [], array $headers = [], int $timeout = 5): HttpResponse;

    /** @param array<string, mixed> $options */
    public function request(string $method, string $url, array $options = []): HttpResponse;
}
