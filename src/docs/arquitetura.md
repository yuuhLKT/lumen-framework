# Arquitetura e fluxo da requisicao

> Recursos de autenticacao so estao disponiveis se o projeto foi gerado com Mini Auth. Projetos sem Auth continuam funcionando normalmente com rotas publicas.

## Fluxo HTTP

1. O servidor embutido do PHP aponta para `public/`.
2. `public/index.php` carrega `bootstrap/app.php`.
3. `bootstrap/app.php` registra o autoload, carrega helpers, le `.env` e define o timezone.
4. `public/index.php` carrega `routes/web.php`, que retorna uma instancia de `Router`.
5. `Request::capture()` monta um objeto com metodo, path, query string, body e `$_SERVER`.
6. `$router->dispatch($request)` encontra a rota compativel.
7. Se a rota estiver protegida, `Auth::check()` valida o Bearer Token.
8. O handler da rota retorna `Response`, array ou string.
9. `Response::send()` envia status, headers e conteudo.
10. Se alguma excecao acontecer, `ErrorHandler::handle()` converte em resposta JSON.

## Entrada da aplicacao

`public/index.php` e propositalmente pequeno:

```php
require_once __DIR__ . '/../bootstrap/app.php';

$router = require __DIR__ . '/../routes/web.php';

try {
    $response = $router->dispatch(Request::capture());
} catch (Throwable $exception) {
    $response = ErrorHandler::handle($exception);
}

$response->send();
```

## Autoload

O autoload aceita classes do namespace `App\` e procura arquivos em `app/`.

Exemplo:

```text
App\Controllers\ProductController
```

vira:

```text
app/Controllers/ProductController.php
```

Classes fora de `App\` nao sao carregadas por esse autoload.

## Camadas sugeridas

- `routes`: define URL, metodo HTTP e handler.
- `Controllers`: orquestram request, validacao, services e response.
- `Auth`: valida token de rotas protegidas.
- `Services`: guardam regra de negocio quando a acao crescer.
- `DTO`: organizam dados de entrada ou saida.
- `Repositories`: concentram acesso a uma tabela.
- `Database`: fornece a mesma API para JSON, SQLite, MySQL e PostgreSQL.

## O que a lumen nao tenta ser

- Nao tem Composer.
- Nao tem container de injecao de dependencia.
- Nao tem middleware.
- Nao tem migrations com todos os recursos de um framework completo; existe uma API pequena de schema.
- Nao tem ORM.
- Nao tem template engine.

Isso e intencional para manter a lumen pequena e facil de copiar em estudos.
