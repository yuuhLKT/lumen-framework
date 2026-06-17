<?php

declare(strict_types=1);

namespace Tests;

use App\Core\Request;
use App\Core\Router;
use PHPUnit\Framework\TestCase;

final class RouterTest extends TestCase
{
    public function testDispatchesRouteWithParameter(): void
    {
        $router = new Router();
        $router->get('/users/{id}', fn (Request $request, array $params): array => [
            'id' => $params['id'],
        ]);

        $response = $router->dispatch(new Request('GET', '/users/10', [], [], [], []));

        ob_start();
        $response->send();
        $content = ob_get_clean();

        self::assertSame('{"id":"10"}', $content);
    }
}
