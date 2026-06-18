<?php

declare(strict_types=1);

namespace Tests;

use App\Http\HttpResponse;
use App\Http\NativeHttpClient;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class HttpClientTest extends TestCase
{
    public function testHttpResponseHelpers(): void
    {
        $response = new HttpResponse(201, '{"id":10}', ['x-test' => 'ok']);

        self::assertSame(201, $response->status());
        self::assertTrue($response->successful());
        self::assertSame('{"id":10}', $response->body());
        self::assertSame(['x-test' => 'ok'], $response->headers());
        self::assertSame(10, $response->json('id'));
        self::assertSame('fallback', (new HttpResponse(200, 'invalid'))->json('id', 'fallback'));
    }

    public function testNativeHttpClientThrowsWhenRequestFails(): void
    {
        $this->expectException(RuntimeException::class);

        (new NativeHttpClient())->get('http://127.0.0.1:1/unavailable', timeout: 1);
    }
}
