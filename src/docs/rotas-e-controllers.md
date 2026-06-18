# Rotas e controllers

> O atalho `->auth()` so esta disponivel se o projeto foi gerado com Mini Auth. Projetos sem Auth usam apenas `->middleware()`.

## Onde registrar rotas

As rotas ficam em `routes/web.php`.

```php
use App\Core\Response;
use App\Core\Router;

$router = new Router();

$router->get('/health', fn () => Response::json(['status' => 'ok']));

return $router;
```

O arquivo precisa retornar a instancia de `Router`.

## Metodos disponiveis

```php
$router->get('/path', $handler);
$router->post('/path', $handler);
$router->put('/path', $handler);
$router->patch('/path', $handler);
$router->delete('/path', $handler);
```

## Parametros na URL

Use `{nome}` para capturar um trecho do path:

```php
$router->get('/products/{id}', [ProductController::class, 'show']);
```

No controller:

```php
public function show(Request $request, array $params): Response
{
    $id = (int) $params['id'];

    return $this->ok(['id' => $id]);
}
```

Regras dos parametros:

- O nome precisa comecar com letra ou underscore.
- Depois pode ter letras, numeros ou underscore.
- O valor capturado nao atravessa `/`.
- O valor e passado por `urldecode()`.

## Formatos de handler

Closure:

```php
$router->get('/ping', fn () => ['message' => 'pong']);
```

Array com classe e metodo:

```php
$router->get('/products/{id}', [ProductController::class, 'show']);
```

String no formato `Classe@metodo`:

```php
$router->get('/products/{id}', ProductController::class . '@show');
```

Quando o handler for array ou string, o roteador instancia a classe com `new $class()`. Por isso, se o controller tiver construtor com dependencias, elas precisam ter valor padrao ou serem criadas dentro do construtor.

## Retorno do handler

O handler recebe sempre:

```php
function (Request $request, array $params): mixed
```

O retorno pode ser:

- `Response`: enviada como esta.
- `array`: convertido para JSON com status `200`.
- qualquer outro valor: convertido para string e enviado como HTML.

Exemplos:

```php
$router->get('/json', fn () => ['ok' => true]);
$router->get('/html', fn () => '<h1>Ola</h1>');
$router->get('/text', fn () => Response::text('Ola'));
```

## Proteger rota

Use `->auth()` logo apos registrar a rota:

```php
$router->get('/me', fn () => ['user' => 'admin'])->auth();
$router->post('/products', [ProductController::class, 'store'])->auth();
```

Rotas protegidas exigem header `Authorization` com Bearer Token valido:

```bash
curl http://localhost:8000/me -H "Authorization: Bearer dev-token"
```

Se o token estiver ausente ou invalido, a resposta sera HTTP `401`:

```json
{
  "error": "Token de autenticacao invalido ou ausente."
}
```

Veja [Autenticacao Bearer Token](autenticacao.md) para configuracao completa.

## Controller base

Todo controller pode estender `App\Core\Controller` para ganhar atalhos:

```php
<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Support\HttpStatus;

final class ProductController extends Controller
{
    public function show(Request $request, array $params): Response
    {
        if ((int) $params['id'] <= 0) {
            $this->abort(HttpStatus::NOT_FOUND, 'Produto nao encontrado.');
        }

        return $this->ok([
            'id' => (int) $params['id'],
            'name' => 'Produto exemplo',
        ]);
    }
}
```

Atalhos disponiveis:

```php
return $this->json(['error' => 'Mensagem'], HttpStatus::BAD_REQUEST);
return $this->html('<h1>Ola</h1>');
return $this->ok(['data' => $data]);
return $this->created($data);
return $this->noContent();

$this->abort(HttpStatus::NOT_FOUND, 'Registro nao encontrado.');
$data = $this->validate($request, ['name' => 'required|string']);
```

## Ordem das rotas

O roteador testa as rotas na ordem em que foram registradas dentro do mesmo metodo HTTP. Registre rotas mais especificas antes de rotas genericas quando houver conflito.
