# Erros e codigos HTTP

> A resposta `401` de rotas protegidas so se aplica se o projeto foi gerado com Mini Auth.

## ErrorHandler

`App\Core\ErrorHandler` centraliza a conversao de excecoes em resposta JSON.

Ele e chamado em `public/index.php`:

```php
try {
    $response = $router->dispatch(Request::capture());
} catch (Throwable $exception) {
    $response = ErrorHandler::handle($exception);
}
```

## HttpException

Use `HttpException` ou `$this->abort()` quando quiser controlar status e mensagem.

```php
$this->abort(HttpStatus::NOT_FOUND, 'Registro nao encontrado.');
```

Resposta:

```json
{
  "error": "Registro nao encontrado."
}
```

Tambem da para enviar erros adicionais:

```php
$this->abort(HttpStatus::BAD_REQUEST, 'Dados invalidos.', [
    'field' => ['Mensagem detalhada.'],
]);
```

Resposta:

```json
{
  "error": "Dados invalidos.",
  "errors": {
    "field": ["Mensagem detalhada."]
  }
}
```

## ValidationException

`ValidationException` estende `HttpException` e sempre usa HTTP `422`.

O `Validator` lanca essa excecao automaticamente quando algum campo falha.

## Token invalido ou ausente

Rotas protegidas com `->auth()` retornam HTTP `401` quando o header `Authorization` nao possui um Bearer Token valido.

```json
{
  "error": "Token de autenticacao invalido ou ausente."
}
```

## Erros inesperados

Qualquer excecao que nao seja `HttpException` vira HTTP `500`.

Resposta padrao:

```json
{
  "error": "Erro interno do servidor."
}
```

Com `APP_DEBUG=true`, a resposta tambem mostra a mensagem da excecao no campo `trace`:

```json
{
  "error": "Erro interno do servidor.",
  "trace": "Mensagem da excecao"
}
```

Use `APP_DEBUG=false` quando nao quiser expor detalhes internos.

## Metodo nao permitido

Se uma URL existir em outro metodo HTTP, o roteador retorna HTTP `405`:

```json
{
  "error": "Metodo nao permitido"
}
```

## HttpStatus

Use `App\Support\HttpStatus` para evitar numeros soltos.

Constantes disponiveis:

```php
HttpStatus::OK                    // 200
HttpStatus::CREATED               // 201
HttpStatus::NO_CONTENT            // 204
HttpStatus::BAD_REQUEST           // 400
HttpStatus::UNAUTHORIZED          // 401
HttpStatus::FORBIDDEN             // 403
HttpStatus::NOT_FOUND             // 404
HttpStatus::METHOD_NOT_ALLOWED    // 405
HttpStatus::CONFLICT              // 409
HttpStatus::UNPROCESSABLE_ENTITY  // 422
HttpStatus::INTERNAL_SERVER_ERROR // 500
```

Exemplo:

```php
return $this->json(['error' => 'Nao encontrado'], HttpStatus::NOT_FOUND);
```

## Rota nao encontrada

Quando nenhuma rota bate com o path e metodo atual, o roteador retorna:

```json
{
  "error": "Rota nao encontrada"
}
```

com HTTP `404`.
