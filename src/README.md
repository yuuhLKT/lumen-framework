# Base PHP para estudos

Estrutura simples em PHP puro para copiar em desafios e focar na logica do problema, sem depender de framework. Composer e opcional, mas recomendado para testes e ferramentas de qualidade.

A base ja vem com roteador, request/response, controller base, Mini Auth com tokens, validacao, tratamento de erros, DTOs, repositories, services por convencao, client HTTP, migrations/seeders simples, transacoes e banco trocavel entre JSON, SQLite, MySQL e PostgreSQL.

## Rodar

Para criar um novo projeto a partir da base, rode na pasta `base`:

```bash
./base.sh
```

Ele pergunta o nome e copia `src/` para uma nova pasta no mesmo nivel da `base`, por exemplo `../example`.

Na pasta `src`:

```bash
php -S localhost:8000 -t public
```

Se quiser usar Composer:

```bash
composer install
composer test
composer analyse
composer lint
```

Acesse:

```text
http://localhost:8000/health
```

Com Docker:

```bash
docker compose up --build php
```

Com Makefile:

```bash
make up
```

O comando detecta se o Docker esta disponivel. Com Docker, abre um menu para escolher JSON, SQLite, MySQL ou PostgreSQL, atualiza o `.env`, sobe os containers e inicia o ngrok local apontando para a porta do PHP. Sem Docker, usa PHP local.

Para forcar PHP local:

```bash
make local
```

Se quiser PHP local com banco em Docker, suba so o banco antes:

```bash
make db-up-mysql
# ou
make db-up-pg

make local
```

Tambem existem aliases `local-*`, como `make local-up`, `make local-build`, `make local-test` e `make local-quality`.

Teste rapido:

```bash
curl http://localhost:8000/health
```

Resposta esperada:

```json
{"status":"ok"}
```

## Requisitos

- PHP 8.1+ recomendado, porque o projeto usa recursos modernos como `readonly`, `str_starts_with`, union types e spread em arrays.
- Extensao `pdo_sqlite` somente se voce quiser usar SQLite.
- Extensao `pdo_mysql` somente se voce quiser usar MySQL.
- Extensao `pdo_pgsql` somente se voce quiser usar PostgreSQL.
- Nenhuma dependencia externa obrigatoria.
- Composer opcional para PHPUnit, PHPStan e PHP-CS-Fixer.
- Docker Compose, PowerShell (Windows) ou `sh` (Linux/WSL) sao suficientes para rodar pelo `make up` sem PHP local.

## Estrutura

```text
public/index.php                    Entrada HTTP da aplicacao
bootstrap/app.php                   Autoload simples, helpers e .env
routes/web.php                      Registro das rotas
config/config.php                   Configuracoes gerais
config/database.php                 Configuracao do banco
config/auth.php                     Tokens fixos opcionais
app/Core/Router.php                 Roteador GET/POST/PUT/PATCH/DELETE
app/Core/Request.php                Query string, body e servidor
app/Core/Response.php               JSON, HTML, texto e redirect
app/Core/Controller.php             Atalhos para controllers
app/Core/Auth.php                   Integracao do Mini Auth com rotas protegidas
app/Core/ErrorHandler.php           Tratamento central de excecoes
app/Database/                       Drivers JSON, SQLite, MySQL e PostgreSQL
app/Http/                           Client HTTP nativo e fake para testes
app/DTO/BaseDTO.php                 DTO base com fromArray/toArray
app/Exceptions/                     Excecoes HTTP e validacao
app/Repositories/BaseRepository.php Repository base por tabela
app/Controllers/                    Controllers HTTP
app/Services/                       Services de regra de negocio
app/Clients/                        Clients para servicos externos
app/Support/HttpStatus.php          Constantes de status HTTP
app/Validation/Validator.php        Validador simples de input
database/migrations/                Migrations PHP simples
database/seeders/                   Seeders PHP simples
storage/database.json               Banco JSON inicial
utils/helpers.php                   Helpers globais
docker-compose.yml                  PHP e bancos via Docker
Makefile                            Atalhos para Docker, banco e ngrok local
tools/env.php                       Atualizador simples do .env (PHP local)
tools/env.sh                        Atualizador simples do .env (Linux/WSL)
tools/env.ps1                       Atualizador simples do .env (Windows)
tools/up-docker.sh                  Sobe Docker via menu interativo (Linux/WSL)
tools/up-docker.ps1                 Sobe Docker via menu interativo (Windows)
tools/migrate.php                   Executa migrations
tools/seed.php                      Executa seeders
tests/                              Testes PHPUnit
docs/                               Documentacao detalhada
```

## Documentacao

- [Indice da documentacao](docs/README.md)
- [Arquitetura e fluxo da requisicao](docs/arquitetura.md)
- [Rotas e controllers](docs/rotas-e-controllers.md)
- [Mini Auth](docs/autenticacao.md)
- [Request e response](docs/request-e-response.md)
- [Validacao](docs/validacao.md)
- [Banco de dados e repositories](docs/banco-e-repositories.md)
- [Migrations e seeders](docs/migrations-e-seeders.md)
- [HTTP client](docs/http-client.md)
- [DTOs](docs/dtos.md)
- [Services e organizacao de camadas](docs/services-e-camadas.md)
- [Testes e qualidade](docs/testes-e-qualidade.md)
- [Configuracao, .env e helpers](docs/configuracao-e-helpers.md)
- [Docker Compose](docs/docker.md)
- [Erros e codigos HTTP](docs/erros-e-http-status.md)
- [Limitacoes da base](docs/limitacoes.md)
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

## Banco padrao

Por padrao usa JSON em `storage/database.json`:

```php
$created = db()->table('users')->insert([
    'name' => 'Yuri',
    'email' => 'yuri@example.com',
]);
```

Para SQLite, use `.env`:

```env
DB_CONNECTION=sqlite
DB_SQLITE_PATH=storage/database.sqlite
```

Para MySQL:

```env
DB_CONNECTION=mysql
DB_MYSQL_HOST=127.0.0.1
DB_MYSQL_PORT=3306
DB_MYSQL_DATABASE=base
DB_MYSQL_USERNAME=root
DB_MYSQL_PASSWORD=
```

Para PostgreSQL:

```env
DB_CONNECTION=pgsql
DB_PGSQL_HOST=127.0.0.1
DB_PGSQL_PORT=5432
DB_PGSQL_DATABASE=base
DB_PGSQL_USERNAME=postgres
DB_PGSQL_PASSWORD=
```

Veja `.env.example` para as variaveis disponiveis.

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

## Proteger rotas

Proteja a rota com `->auth()`:

```php
$router->get('/me', fn ($request) => ['user' => $request->user()])->auth();
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
- O projeto nao cria migrations. Nos drivers SQL, cada tabela e criada automaticamente com colunas `id` e `data`.
- O `.env` tem prioridade sobre variaveis de ambiente do sistema.
- Erros internos so mostram `trace` quando `APP_DEBUG=true`.
- O driver JSON e bom para estudos e desafios pequenos; SQLite, MySQL e PostgreSQL aproximam mais de persistencia real.
