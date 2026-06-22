# Banco de dados e repositories

A lumen possui uma API simples de tabela com quatro drivers: JSON, SQLite, MySQL e PostgreSQL.

## Configuracao

Arquivo: `config/datalumen.php`.

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
            'database' => env('DB_MYSQL_DATABASE', 'lumen'),
            'username' => env('DB_MYSQL_USERNAME', 'root'),
            'password' => env('DB_MYSQL_PASSWORD', ''),
            'charset' => env('DB_MYSQL_CHARSET', 'utf8mb4'),
        ],
        'pgsql' => [
            'host' => env('DB_PGSQL_HOST', '127.0.0.1'),
            'port' => env('DB_PGSQL_PORT', '5432'),
            'database' => env('DB_PGSQL_DATABASE', 'lumen'),
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

Para SQLite, MySQL e PostgreSQL a transacao usa `PDO`. No driver JSON, a lumen usa um snapshot em memoria e grava o arquivo apenas no `commit()`.

## Usar conexao especifica

```php
$jsonUsers = db('json')->table('users');
$sqliteUsers = db('sqlite')->table('users');
$mysqlUsers = db('mysql')->table('users');
$pgsqlUsers = db('pgsql')->table('users');
```

## Driver JSON

Padrao da lumen.

Arquivo padrao:

```text
storage/database.json
```

Caracteristicas:

- Cria o arquivo se ele nao existir.
- Armazena cada tabela como uma chave no JSON.
- Guarda metadados de schema quando a tabela e criada por migration com `$db->create(...)`.
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
- Migrations com `$db->create(...)` criam colunas reais, tipos, indices e chaves estrangeiras suportadas pelo SQLite.
- Tabelas acessadas direto com `table('nome')`, sem schema, ainda sao criadas automaticamente no modo simples `id` e `data`.
- Em tabelas com colunas reais, `insert()`, `update()`, `where()` e `query()` usam as colunas da tabela.
- O nome da tabela precisa seguir `/^[a-zA-Z_][a-zA-Z0-9_]*$/`.

## Driver MySQL

Ative com `.env`:

```env
DB_CONNECTION=mysql
DB_MYSQL_HOST=127.0.0.1
DB_MYSQL_PORT=3306
DB_MYSQL_DATABASE=lumen
DB_MYSQL_USERNAME=root
DB_MYSQL_PASSWORD=
DB_MYSQL_CHARSET=utf8mb4
```

Caracteristicas:

- Requer extensao `pdo_mysql`.
- Usa `PDO` com `ERRMODE_EXCEPTION` e `FETCH_ASSOC`.
- Migrations com `$db->create(...)` criam colunas reais, tipos, indices e chaves estrangeiras suportadas pelo MySQL.
- Tabelas acessadas direto com `table('nome')`, sem schema, ainda sao criadas automaticamente no modo simples `id` e `data`.
- Em tabelas com colunas reais, `insert()`, `update()`, `where()` e `query()` usam as colunas da tabela.
- O nome da tabela precisa seguir `/^[a-zA-Z_][a-zA-Z0-9_]*$/`.

## Driver PostgreSQL

Ative com `.env`:

```env
DB_CONNECTION=pgsql
DB_PGSQL_HOST=127.0.0.1
DB_PGSQL_PORT=5432
DB_PGSQL_DATABASE=lumen
DB_PGSQL_USERNAME=postgres
DB_PGSQL_PASSWORD=
```

Tambem da para usar `DB_CONNECTION=postgres`.

Caracteristicas:

- Requer extensao `pdo_pgsql`.
- Usa `PDO` com `ERRMODE_EXCEPTION` e `FETCH_ASSOC`.
- Migrations com `$db->create(...)` criam colunas reais, tipos, indices e chaves estrangeiras suportadas pelo PostgreSQL.
- Tabelas acessadas direto com `table('nome')`, sem schema, ainda sao criadas automaticamente no modo simples `id` e `data`.
- Em tabelas com colunas reais, `insert()`, `update()`, `where()` e `query()` usam as colunas da tabela.
- O nome da tabela precisa seguir `/^[a-zA-Z_][a-zA-Z0-9_]*$/`.

## Query Builder

Todas as tabelas expoem um query builder fluente via `query()`.

```php
$users = db()->table('users');

$admins = $users->query()
    ->where('role', 'admin')
    ->orderBy('name')
    ->limit(10)
    ->get();

$user = $users->query()
    ->where('email', 'yuri@example.com')
    ->first();

$names = $users->query()
    ->select(['name', 'email'])
    ->where('active', true)
    ->get();

$paginated = $users->query()
    ->where('active', true)
    ->orderBy('created_at', 'desc')
    ->paginate(page: 1, perPage: 15);
```

Metodos disponiveis:

- `select($columns)` — colunas especificas (`['name', 'email']` ou `'name'`).
- `where($field, $value, $operator = '=')` — filtra com AND.
- `orWhere($field, $value, $operator = '=')` — filtra com OR.
- `whereNot($field, $value)` — atalho para `!=`.
- `orWhereNot($field, $value)` — OR com `!=`.
- `whereIn($field, $values)` — lista de valores.
- `whereNotIn($field, $values)` — fora da lista.
- `whereLike($field, $pattern)` — use `%` como curinga.
- `orderBy($field, $direction = 'asc')` — ordenacao.
- `groupBy($columns)` — agrupamento.
- `having($field, $value, $operator = '=')` — filtro apos agrupamento.
- `limit($limit)` e `offset($offset)` — paginacao manual.
- `join($table, $leftColumn, $rightColumn, $type = 'inner')` — une tabelas.
- `leftJoin($table, $leftColumn, $rightColumn)`.
- `rightJoin($table, $leftColumn, $rightColumn)`.
- `get()` — executa e retorna todos os registros.
- `first()` — primeiro registro.
- `count()` — quantidade de registros que atendem aos filtros.
- `paginate($page = 1, $perPage = 15)` — retorna `data` e `meta`.

Exemplos combinados:

```php
$recent = $users->query()
    ->select(['name', 'email'])
    ->where('active', true)
    ->where('age', 18, '>=')
    ->orWhere('role', 'admin')
    ->whereIn('status', ['pending', 'approved'])
    ->whereLike('name', 'Yuri%')
    ->orderBy('created_at', 'desc')
    ->limit(20)
    ->get();

$totalAdmins = $users->query()->where('role', 'admin')->count();
```

Joins:

```php
$posts = db()->table('posts');

$result = $users->query()
    ->join('posts', 'id', 'user_id')
    ->where('users.name', 'Yuri')
    ->get();
```

No driver JSON os dados da tabela unida ficam prefixados (`posts_id`, `posts_title`).
Nos drivers SQL o join e executado nativamente e os campos da tabela principal sao retornados.

Tambem e possivel usar diretamente pelo repository:

```php
$users = new UserRepository();

$recent = $users->query()
    ->where('active', true)
    ->orderBy('name')
    ->paginate(1, 10);
```

Os metodos de conveniencia do repository tambem usam o query builder:

```php
$users->where('role', 'admin');
$users->whereAll(['role' => 'admin', 'active' => true]);
$users->count();
$users->first();
$users->paginate(1, 10);
```

## Relacionamentos

Repositories podem declarar relacionamentos simples.

### HasMany

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

// ou
$posts = $users->posts(1)->get();

// criar filho ja vinculado
$users->posts()->for($user)->create(['title' => 'Novo post']);
```

### BelongsTo

```php
final class PostRepository extends BaseRepository
{
    protected string $table = 'posts';

    public function user(): BelongsTo
    {
        return $this->belongsTo(UserRepository::class, 'user_id');
    }
}
```

Uso:

```php
$post = $posts->find(1);
$author = $posts->user()->for($post)->first();
```

### BelongsToMany

```php
final class UserRepository extends BaseRepository
{
    protected string $table = 'users';

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(
            RoleRepository::class,
            RoleUserRepository::class,
            'user_id',
            'role_id',
        );
    }
}
```

Uso:

```php
$user = $users->find(1);
$roles = $users->roles()->for($user)->get();

$users->roles()->for($user)->attach($roleId);
$users->roles()->for($user)->detach($roleId);
$users->roles()->for($user)->sync([$roleA, $roleB]);
```

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
