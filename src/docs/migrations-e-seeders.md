# Migrations e seeders

As migrations e seeders sao arquivos PHP simples que retornam uma funcao. Eles recebem a conexao atual de banco.

## Migration

Crie arquivos em `database/migrations` usando ordem no nome:

```text
database/migrations/2026_01_01_000001_create_users.php
```

Exemplo usando a API generica da lumen:

```php
<?php

declare(strict_types=1);

use App\Database\Contracts\DatabaseConnection;

return function (DatabaseConnection $db): void {
    $db->table('users')->insert([
        'name' => 'Admin',
        'email' => 'admin@example.com',
    ]);
};
```

Exemplo usando SQL em drivers PDO:

```php
return function (DatabaseConnection $db): void {
    $db->execute('CREATE TABLE IF NOT EXISTS users_sql (id INTEGER PRIMARY KEY, name TEXT NOT NULL)');
};
```

Execute:

```bash
php tools/migrate.php
```

Ou com Composer:

```bash
composer migrate
```

As migrations executadas ficam registradas na tabela `migrations`.

## Seeder

Crie arquivos em `database/seeders`:

```php
<?php

declare(strict_types=1);

use App\Database\Contracts\DatabaseConnection;

return function (DatabaseConnection $db): void {
    $db->table('users')->insert([
        'name' => 'User Example',
        'email' => 'user@example.com',
    ]);
};
```

Execute:

```bash
php tools/seed.php
```

Seeders rodam sempre que chamados. Se precisar evitar duplicidade, faca a checagem dentro do proprio seeder.
