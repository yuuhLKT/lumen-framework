# Lumen PHP para estudos

Estrutura simples em PHP puro para copiar em desafios e focar na logica do problema, sem depender de framework. Composer e opcional, mas recomendado para testes e ferramentas de qualidade.

O lumen ja vem com roteador, request/response, controller base, Mini Auth com tokens, validacao, tratamento de erros, DTOs, repositories, query builder, relacionamentos, middleware, CLI proprio, services por convencao, client HTTP, migrations/seeders simples (com rollback), transacoes e banco trocavel entre JSON, SQLite, MySQL e PostgreSQL.

## Criar um novo projeto

Na pasta `lumen`, rode:

```bash
./lumen.sh
```

Ele pergunta o nome, se o projeto deve incluir Mini Auth, e copia `src/` para uma nova pasta no mesmo nivel, por exemplo `../meu-projeto`.

Projetos gerados nao recebem `AGENTS.md` nem a pasta `tests/`. Os comandos de testes e qualidade continuam disponiveis para voce adicionar seus proprios testes quando fizer sentido.

Veja mais em [docs/gerador.md](docs/gerador.md).

## Rodar local

Dentro de `src`:

```bash
php lumen.php serve
```

Ou diretamente com o PHP:

```bash
php -S localhost:8000 -t public
```

Acesse:

```text
http://localhost:8000/health
```

Resposta esperada:

```json
{"status":"ok"}
```

## CLI

O lumen possui um CLI proprio em `lumen.php` (com wrappers `lumen` para Unix e `lumen.bat` para Windows).

```bash
php lumen.php list
```

Comandos principais:

```bash
php lumen.php migrate              # executa migrations pendentes
php lumen.php migrate:rollback     # reverte a ultima migration
php lumen.php migrate:list         # lista status das migrations
php lumen.php seed                 # executa seeders
php lumen.php fresh                # migrate + seed

php lumen.php make                 # seleciona um ou mais geradores
php lumen.php make:controller NomeController
php lumen.php make:repository NomeRepository
php lumen.php make:middleware NomeMiddleware
php lumen.php make:dto NomeDTO
php lumen.php make:migration create_nome_da_tabela

php lumen.php route:list
php lumen.php serve

php lumen.php test
php lumen.php analyse
php lumen.php lint
php lumen.php format
php lumen.php format-check
php lumen.php qa         # lint + format-check + analyse + test
php lumen.php doctor     # checa ambiente
```

Veja mais em [docs/cli.md](docs/cli.md).

## Composer

Se quiser usar Composer:

```bash
composer install
composer test
composer analyse
composer lint
composer format-check
```

## Docker e Makefile

O Makefile gerencia Docker e configuracao de ambiente. Com Docker disponivel:

```bash
make up
```

Abre um menu para escolher entre PHP no Docker ou PHP local, com JSON, SQLite, MySQL ou PostgreSQL. As opcoes Docker nao exigem PHP instalado no host.

Para forcar PHP local:

```bash
make RUNNER=local up
```

Se quiser PHP local com banco em Docker:

```bash
make db-up-mysql
# ou
make db-up-pg

make RUNNER=local up
```

Outros comandos do Makefile:

```bash
make env              # cria/atualiza .env
make db-json          # configura banco JSON
make db-sqlite        # configura SQLite
make db-mysql         # configura MySQL
make db-pgsql         # configura PostgreSQL
make down             # para containers
make build            # docker compose build / composer install
make validate         # valida compose / lint dos tools
make help             # lista todos os comandos
```

Para migrations, seeders, testes, qualidade e servidor local, use o CLI:

```bash
php lumen.php migrate
php lumen.php seed
php lumen.php test
php lumen.php analyse
php lumen.php qa
php lumen.php serve
```

## Requisitos

- PHP 8.1+ recomendado, porque o projeto usa recursos modernos como `readonly`, `str_starts_with`, union types e spread em arrays.
- Extensao `pdo_sqlite` somente se voce quiser usar SQLite.
- Extensao `pdo_mysql` somente se voce quiser usar MySQL.
- Extensao `pdo_pgsql` somente se voce quiser usar PostgreSQL.
- Nenhuma dependencia externa obrigatoria.
- Composer opcional para PHPUnit, PHPStan e PHP-CS-Fixer.
- Docker Compose e `sh` (Linux/WSL/Git Bash) sao suficientes para as opcoes Docker do `make up` sem PHP local.

## Estrutura

```text
public/index.php                    Entrada HTTP da aplicacao
bootstrap/app.php                   Autoload simples, helpers e .env
routes/web.php                      Registro das rotas
config/config.php                   Configuracoes gerais
config/database.php                 Configuracao do banco
config/auth.php                     Tokens fixos opcionais
app/Core/Router.php                 Roteador com middleware
app/Core/Request.php                Query string, body e servidor
app/Core/Response.php               JSON, HTML, texto e redirect
app/Core/Controller.php             Atalhos para controllers
app/Core/Auth.php                   Integracao do Mini Auth com rotas protegidas
app/Core/ErrorHandler.php           Tratamento central de excecoes
app/Http/Middleware/                Interface, pipeline e middlewares
app/Database/                       Drivers JSON, SQLite, MySQL e PostgreSQL + query builder + relacoes
app/Http/                           Client HTTP nativo e fake para testes
app/DTO/BaseDTO.php                 DTO base com fromArray/toArray
app/Exceptions/                     Excecoes HTTP e validacao
app/Repositories/BaseRepository.php Repository base por tabela
app/Controllers/                    Controllers HTTP
app/Services/                       Services de regra de negocio
app/Console/                        CLI proprio
app/Clients/                        Clients para servicos externos
app/Support/HttpStatus.php          Constantes de status HTTP
app/Validation/Validator.php        Validador simples de input
database/migrations/                Migrations PHP simples
database/seeders/                   Seeders PHP simples
storage/database.json               Banco JSON inicial
utils/helpers.php                   Helpers globais
docker-compose.yml                  PHP e bancos via Docker
Makefile                            Atalhos para Docker, banco e PHP local
lumen.php                            CLI proprio
lumen                                Wrapper do CLI para Unix
lumen.bat                            Wrapper do CLI para Windows
tools/                              Scripts auxiliares (env, migrate, seed, lint)
tests/                              Testes PHPUnit (opcional em projetos gerados)
docs/                               Documentacao detalhada
```

## Documentacao

- [Indice da documentacao](docs/README.md)
- [Gerador de projetos](docs/gerador.md)
- [Arquitetura e fluxo da requisicao](docs/arquitetura.md)
- [Rotas e controllers](docs/rotas-e-controllers.md)
- [Middleware](docs/middleware.md)
- [Mini Auth](docs/autenticacao.md)
- [Request e response](docs/request-e-response.md)
- [Validacao](docs/validacao.md)
- [Banco de dados, repositories e relacoes](docs/banco-e-repositories.md)
- [Migrations e seeders](docs/migrations-e-seeders.md)
- [CLI](docs/cli.md)
- [HTTP client](docs/http-client.md)
- [DTOs](docs/dtos.md)
- [Services e organizacao de camadas](docs/services-e-camadas.md)
- [Testes e qualidade](docs/testes-e-qualidade.md)
- [Configuracao, .env e helpers](docs/configuracao-e-helpers.md)
- [Docker Compose](docs/docker.md)
- [Erros e codigos HTTP](docs/erros-e-http-status.md)
- [Limitacoes do lumen](docs/limitacoes.md)
- [Guia rapido: criar recurso CRUD](docs/guia-crud.md)

## Primeiro exemplo

Crie uma rota em `routes/web.php`:

```php
$router->get('/ping', fn () => ['message' => 'pong']);
```

Como o handler retornou um array, o roteador transforma automaticamente em JSON.

Para usar controller:

```php
use App\Controllers\ProductController;

$router->get('/products/{id}', [ProductController::class, 'show']);
```

Controller em `app/Controllers/ProductController.php`:

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

## Middleware

Crie um middleware implementando `App\Http\Middleware\Middleware`:

```php
final class EnsureAdmin implements Middleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (($request->user()['role'] ?? null) !== 'admin') {
            return Response::json(['error' => 'Acesso negado.'], 403);
        }

        return $next($request);
    }
}
```

Use na rota:

```php
$router->get('/admin', [AdminController::class, 'index'])
    ->middleware([EnsureAdmin::class]);
```

A auth nativa continua funcionando via `->auth()`:

```php
$router->get('/me', [AuthController::class, 'me'])->auth();
```

Veja mais em [docs/middleware.md](docs/middleware.md).

## Banco de dados e query builder

Por padrao usa JSON em `storage/database.json`:

```php
$created = db()->table('users')->insert([
    'name' => 'Yuri',
    'email' => 'yuri@example.com',
]);

$user = db()->table('users')->find($created['id']);

$admins = db()->table('users')->query()
    ->where('role', 'admin')
    ->orderBy('name')
    ->get();
```

Para trocar de banco, use `.env`:

```env
DB_CONNECTION=sqlite
DB_SQLITE_PATH=storage/database.sqlite
```

Outros exemplos:

```php
$recent = db()->table('users')->query()
    ->select(['name', 'email'])
    ->where('active', true)
    ->where('age', 18, '>=')
    ->whereLike('name', 'Yuri%')
    ->orderBy('created_at', 'desc')
    ->paginate(1, 15);
```

Veja mais em [docs/banco-e-repositories.md](docs/banco-e-repositories.md).

## Relacionamentos

Declare relacionamentos no repository:

```php
final class UserRepository extends BaseRepository
{
    protected string $table = 'users';

    public function posts(): HasMany
    {
        return $this->hasMany(PostRepository::class, 'user_id');
    }
}
```

Uso:

```php
$user = $users->find(1);
$posts = $users->posts()->for($user)->get();

$users->posts()->for($user)->create(['title' => 'Novo post']);
```

Tambem suporta `belongsTo` e `belongsToMany`. Veja mais em [docs/banco-e-repositories.md](docs/banco-e-repositories.md).

## Mini Auth

Cadastro:

```bash
curl -X POST http://localhost:8000/auth/register \
  -H "Content-Type: application/json" \
  -d '{"name":"Yuri Luiz","email":"yuri@example.com","password":"password123"}'
```

Login:

```bash
curl -X POST http://localhost:8000/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"yuri@example.com","password":"password123"}'
```

Use o `access_token` retornado nas rotas protegidas:

```bash
curl http://localhost:8000/auth/me -H "Authorization: Bearer token-gerado"
```

## Quando usar cada camada

- `routes/web.php`: entrada das URLs e escolha do handler.
- `Controller`: recebe `Request`, valida input e devolve `Response`.
- `Auth`: valida Bearer Token nas rotas protegidas.
- `Service`: regra de negocio, quando o controller comecar a crescer.
- `DTO`: objeto de entrada/saida quando arrays ficarem confusos.
- `Repository`: acesso reutilizavel a uma tabela.
- `Validator`: validacao de dados externos.
- `db()`: acesso direto ao banco quando o caso for simples.

## Observacoes

- O autoload carrega classes dentro do namespace `App\` a partir da pasta `app/`.
- O projeto nao exige migrations. Nos drivers SQL, cada tabela e criada automaticamente com colunas `id` e `data`.
- O `.env` tem prioridade sobre variaveis de ambiente do sistema.
- Erros internos so mostram `trace` quando `APP_DEBUG=true`.
- O driver JSON e bom para estudos e desafios pequenos; SQLite, MySQL e PostgreSQL aproximam mais de persistencia real.
