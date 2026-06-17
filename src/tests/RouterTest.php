<?php

declare(strict_types=1);

namespace Tests;

use App\Core\Request;
use App\Core\Response;
use App\Core\Router;
use App\Http\Middleware\Middleware;
use Closure;
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

    public function testMiddlewareIsExecuted(): void
    {
        $router = new Router();
        $router->get('/ping', fn (): array => ['ok' => true])
            ->middleware([TestHeaderMiddleware::class]);

        $response = $router->dispatch(new Request('GET', '/ping', [], [], [], []));

        ob_start();
        $response->send();
        $content = ob_get_clean();

        self::assertSame('{"ok":true}', $content);
        self::assertTrue(TestHeaderMiddleware::$called);

        TestHeaderMiddleware::$called = false;
    }

    public function testMiddlewareCanShortCircuit(): void
    {
        $router = new Router();
        $router->get('/admin', fn (): array => ['secret' => true])
            ->middleware([BlockMiddleware::class]);

        $response = $router->dispatch(new Request('GET', '/admin', [], [], [], []));

        ob_start();
        $response->send();
        $content = ob_get_clean();

        self::assertSame('{"blocked":true}', $content);
    }
}

final class TestHeaderMiddleware implements Middleware
{
    public static bool $called = false;

    public function handle(Request $request, Closure $next): Response
    {
        self::$called = true;

        return $next($request);
    }
}

final class BlockMiddleware implements Middleware
{
    public function handle(Request $request, Closure $next): Response
    {
        return Response::json(['blocked' => true], 403);
    }
}
