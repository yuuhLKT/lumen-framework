# Banco de dados e repositories

A base possui uma API simples de tabela com quatro drivers: JSON, SQLite, MySQL e PostgreSQL.

## Configuracao

Arquivo: `config/database.php`.

```php
return [
    'default' => env('DB_CONNECTION', 'json'),
    'connections' => [
        'json' => [
            'path' => path_from_base((string) env('DB_JSON_PATH', 'storage/database.json')),
        ],
        'sqlite' => [
            'path' => path_from_base((string) env('DB_SQLITE_PATH', 'storage/database.sqlite')),
        ],
        'mysql' => [
            'host' => env('DB_MYSQL_HOST', '127.0.0.1'),
            'port' => env('DB_MYSQL_PORT', '3306'),
            'database' => env('DB_MYSQL_DATABASE', 'base'),
            'username' => env('DB_MYSQL_USERNAME', 'root'),
            'password' => env('DB_MYSQL_PASSWORD', ''),
            'charset' => env('DB_MYSQL_CHARSET', 'utf8mb4'),
        ],
        'pgsql' => [
            'host' => env('DB_PGSQL_HOST', '127.0.0.1'),
            'port' => env('DB_PGSQL_PORT', '5432'),
            'database' => env('DB_PGSQL_DATABASE', 'base'),
            'username' => env('DB_PGSQL_USERNAME', 'postgres'),
            'password' => env('DB_PGSQL_PASSWORD', ''),
        ],
    ],
];
```

Valores definidos no `.env` tem prioridade sobre variaveis de ambiente do sistema.

## Usar a conexao padrao

```php
$users = db()->table('users');
```

Operacoes disponiveis:

```php
$created = $users->insert([
    'name' => 'Yuri',
    'email' => 'yuri@example.com',
]);

$all = $users->all();
$user = $users->find($created['id']);
$admins = $users->where('role', 'admin');
$updated = $users->update($created['id'], ['name' => 'Yuri Luiz']);
$deleted = $users->delete($created['id']);
```

Transacoes:

```php
db()->transaction(function ($connection): void {
    $connection->table('orders')->insert(['status' => 'created']);
    $connection->table('logs')->insert(['message' => 'Order created']);
});
```

Para SQLite, MySQL e PostgreSQL a transacao usa `PDO`. No driver JSON, a base usa um snapshot em memoria e grava o arquivo apenas no `commit()`.

## Usar conexao especifica

```php
$jsonUsers = db('json')->table('users');
$sqliteUsers = db('sqlite')->table('users');
$mysqlUsers = db('mysql')->table('users');
$pgsqlUsers = db('pgsql')->table('users');
```

## Driver JSON

Padrao da base.

Arquivo padrao:

```text
storage/database.json
```

Caracteristicas:

- Cria o arquivo se ele nao existir.
- Armazena cada tabela como uma chave no JSON.
- Gera `id` inteiro incremental por tabela.
- Remove `id` informado manualmente no `insert()`.
- Preserva o `id` original no `update()`.
- `where()` compara com `===`.

Exemplo de arquivo:

```json
{
  "users": [
    {
      "id": 1,
      "name": "Yuri",
      "email": "yuri@example.com"
    }
  ]
}
```

## Driver SQLite

Ative com `.env`:

```env
DB_CONNECTION=sqlite
DB_SQLITE_PATH=storage/database.sqlite
```

Ou via PowerShell antes de rodar:

```powershell
$env:DB_CONNECTION = "sqlite"
php -S localhost:8000 -t public
```

Caracteristicas:

- Cria o diretorio se necessario.
- Usa `PDO` com `ERRMODE_EXCEPTION` e `FETCH_ASSOC`.
- Cria cada tabela automaticamente quando `table('nome')` e chamado.
- Cada tabela tem colunas `id INTEGER PRIMARY KEY AUTOINCREMENT` e `data TEXT NOT NULL`.
- Os dados ficam serializados como JSON dentro da coluna `data`.
- `where()` carrega todos os registros e filtra em PHP.
- O nome da tabela precisa seguir `/^[a-zA-Z_][a-zA-Z0-9_]*$/`.

## Driver MySQL

Ative com `.env`:

```env
DB_CONNECTION=mysql
DB_MYSQL_HOST=127.0.0.1
DB_MYSQL_PORT=3306
DB_MYSQL_DATABASE=base
DB_MYSQL_USERNAME=root
DB_MYSQL_PASSWORD=
DB_MYSQL_CHARSET=utf8mb4
```

Caracteristicas:

- Requer extensao `pdo_mysql`.
- Usa `PDO` com `ERRMODE_EXCEPTION` e `FETCH_ASSOC`.
- Cria cada tabela automaticamente quando `table('nome')` e chamado.
- Cada tabela tem colunas `id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY` e `data JSON NOT NULL`.
- Os dados ficam serializados como JSON na coluna `data`.
- `where()` carrega todos os registros e filtra em PHP.
- O nome da tabela precisa seguir `/^[a-zA-Z_][a-zA-Z0-9_]*$/`.

## Driver PostgreSQL

Ative com `.env`:

```env
DB_CONNECTION=pgsql
DB_PGSQL_HOST=127.0.0.1
DB_PGSQL_PORT=5432
DB_PGSQL_DATABASE=base
DB_PGSQL_USERNAME=postgres
DB_PGSQL_PASSWORD=
```

Tambem da para usar `DB_CONNECTION=postgres`.

Caracteristicas:

- Requer extensao `pdo_pgsql`.
- Usa `PDO` com `ERRMODE_EXCEPTION` e `FETCH_ASSOC`.
- Cria cada tabela automaticamente quando `table('nome')` e chamado.
- Cada tabela tem colunas `id SERIAL PRIMARY KEY` e `data JSONB NOT NULL`.
- Os dados ficam serializados como JSONB na coluna `data`.
- `where()` carrega todos os registros e filtra em PHP.
- O nome da tabela precisa seguir `/^[a-zA-Z_][a-zA-Z0-9_]*$/`.

## Repository

Use repository quando quiser reutilizar acesso a uma tabela.

Crie `app/Repositories/UserRepository.php`:

```php
<?php

declare(strict_types=1);

namespace App\Repositories;

final class UserRepository extends BaseRepository
{
    protected string $table = 'users';
}
```

Uso:

```php
use App\Repositories\UserRepository;

$users = new UserRepository();

$created = $users->insert([
    'name' => 'Yuri',
    'email' => 'yuri@example.com',
]);

$allUsers = $users->findAll();
$user = $users->find($created['id']);
$admin = $users->findOne('role', 'admin');
$admins = $users->where('role', 'admin');
$activeAdmin = $users->findBy(['role' => 'admin', 'active' => true]);
$filtered = $users->whereAll(['role' => 'admin', 'active' => true]);
$page = $users->paginate(page: 1, perPage: 10);
$total = $users->count();
$updated = $users->update($created['id'], ['name' => 'Yuri Luiz']);
$exists = $users->exists('email', 'yuri@example.com');
$deleted = $users->delete($created['id']);
```

## Injetar conexao no repository

`BaseRepository` aceita uma conexao opcional no construtor.

```php
$repository = new UserRepository(db('sqlite'));
```

Se nenhuma conexao for passada, usa `Database::connection()` com a conexao padrao.

## Cuidados

- Esta API e para estudos, nao para alto volume.
- `where()` nao usa indice em nenhum driver.
- SQLite, MySQL e PostgreSQL guardam os atributos da linha em `data`, entao nao sao uma modelagem relacional completa.
- O driver JSON reescreve o arquivo inteiro a cada alteracao.
