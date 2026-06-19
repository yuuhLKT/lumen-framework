# Request e response

## Request

`App\Core\Request` representa a requisicao atual.

Criacao interna:

```php
$request = Request::capture();
```

Normalmente voce recebe o objeto no controller ou closure da rota.

## Dados disponiveis

Metodo HTTP:

```php
$method = $request->method();
```

Path normalizado:

```php
$path = $request->path();
```

Query string:

```php
$page = $request->query('page', 1);
$query = $request->query();
```

Body JSON ou form:

```php
$name = $request->input('name');
$body = $request->input();
```

Servidor:

```php
$userAgent = $request->server('HTTP_USER_AGENT', 'unknown');
```

Headers:

```php
$authorization = $request->header('Authorization');
$contentType = $request->header('Content-Type');
```

Bearer Token:

```php
$token = $request->bearerToken();
```

`bearerToken()` retorna `null` quando o header `Authorization` nao existe ou nao usa o formato `Bearer token`.

## Como o body e lido

- `GET` sempre retorna body vazio.
- `Content-Type` contendo `application/json` tenta decodificar o `php://input`.
- Se existir `$_POST`, ele e usado.
- Caso contrario, a lumen usa `parse_str()` no body cru.
- JSON invalido vira array vazio.

Exemplo JSON:

```bash
curl -X POST http://localhost:8000/users \
  -H "Content-Type: application/json" \
  -d '{"name":"Yuri","email":"yuri@example.com"}'
```

## Response

`App\Core\Response` guarda conteudo, status e headers.

JSON:

```php
return Response::json(['status' => 'ok']);
return Response::json(['error' => 'Nao encontrado'], 404);
```

HTML:

```php
return Response::html('<h1>Ola</h1>');
```

Texto:

```php
return Response::text('Ola');
```

Redirect:

```php
return Response::redirect('/login');
```

Headers extras:

```php
return Response::json(['ok' => true], 200, [
    'X-App' => 'lumen-php',
]);
```

## Content-Type padrao

- `Response::json()`: `application/json; charset=utf-8`
- `Response::html()`: `text/html; charset=utf-8`
- `Response::text()`: `text/plain; charset=utf-8`
- `Response::redirect()`: header `Location`
