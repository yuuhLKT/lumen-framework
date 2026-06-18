<?php

declare(strict_types=1);

namespace Tests;

use App\Core\Controller;
use App\Core\ErrorHandler;
use App\Core\Request;
use App\Core\Response;
use App\Exceptions\HttpException;
use App\Support\HttpStatus;
use PHPUnit\Framework\TestCase;

final class CoreTest extends TestCase
{
    public function testRequestAccessorsAndBearerToken(): void
    {
        $request = new Request(
            'POST',
            '/users',
            ['page' => '1'],
            ['name' => 'Yuri'],
            ['REQUEST_METHOD' => 'POST'],
            ['authorization' => 'Bearer token-123'],
        );

        self::assertSame('POST', $request->method());
        self::assertSame('/users', $request->path());
        self::assertSame('1', $request->query('page'));
        self::assertSame('Yuri', $request->input('name'));
        self::assertSame('token-123', $request->bearerToken());

        $request->setUser(['id' => 1]);
        self::assertSame(['id' => 1], $request->user());
    }

    public function testResponseSendOutputsContent(): void
    {
        $response = Response::json(['ok' => true], HttpStatus::CREATED);

        ob_start();
        $response->send();
        $content = ob_get_clean();

        self::assertSame('{"ok":true}', $content);
    }

    public function testErrorHandlerFormatsHttpException(): void
    {
        $response = ErrorHandler::handle(new HttpException('Falhou.', HttpStatus::BAD_REQUEST, ['field' => ['erro']]));

        ob_start();
        $response->send();
        $content = ob_get_clean();

        self::assertSame('{"error":"Falhou.","errors":{"field":["erro"]}}', $content);
    }

    public function testControllerHelpers(): void
    {
        $controller = new class () extends Controller {
            public function exposeOk(): Response
            {
                return $this->ok(['ok' => true]);
            }

            public function exposeAbort(): void
            {
                $this->abort(HttpStatus::FORBIDDEN, 'Proibido.');
            }
        };

        ob_start();
        $controller->exposeOk()->send();
        $content = ob_get_clean();

        self::assertSame('{"ok":true}', $content);

        $this->expectException(HttpException::class);
        $controller->exposeAbort();
    }
}
