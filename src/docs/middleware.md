# Middleware

O roteador suporta um pipeline de middlewares.

> O `AuthMiddleware` e o atalho `->auth()` so existem se o projeto foi gerado com Mini Auth.

## Criar um middleware

Implemente `App\Http\Middleware\Middleware`:

```php
<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Core\Request;
use App\Core\Response;
use App\Support\HttpStatus;
use Closure;

final class EnsureAdmin implements Middleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (($user['role'] ?? null) !== 'admin') {
            return Response::json(['error' => 'Acesso negado.'], HttpStatus::FORBIDDEN);
        }

        return $next($request);
    }
}
```

## Usar em rotas

```php
$router->get('/admin/users', [AdminController::class, 'index'])
    ->middleware([EnsureAdmin::class]);
```

Varios middlewares sao executados na ordem:

```php
$router->post('/orders', [OrderController::class, 'store'])
    ->middleware([AuthMiddleware::class, EnsureAdmin::class]);
```

## Auth nativo

O metodo `->auth()` continua funcionando e e um atalho para `->middleware([AuthMiddleware::class])`:

```php
$router->get('/me', [AuthController::class, 'me'])->auth();
```

## Short-circuit

Um middleware pode interceptar a requisicao e retornar uma resposta sem chamar `$next`.

## Ordem de execucao

Os middlewares sao executados da esquerda para a direita. O handler da rota e chamado apenas se todos os middlewares chamarem `$next`.
