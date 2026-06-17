<?php

declare(strict_types=1);

namespace Tests\Http;

use App\Http\FakeHttpClient;
use App\Http\HttpResponse;
use PHPUnit\Framework\TestCase;

final class FakeHttpClientTest extends TestCase
{
    public function testReturnsConfiguredResponseAndStoresRequest(): void
    {
        $client = new FakeHttpClient(new HttpResponse(200, '{"ok":true}'));

        $response = $client->post('https://example.com/test', ['name' => 'Base']);

        self::assertTrue($response->successful());
        self::assertTrue($response->json('ok'));
        self::assertSame('POST', $client->requests()[0]['method']);
    }
}
