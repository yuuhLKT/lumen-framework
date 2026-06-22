# Migrations e seeders

As migrations e seeders sao arquivos PHP simples. Migrations podem retornar uma funcao unica ou um array com `up` e `down`; seeders retornam uma funcao. Ambos recebem a conexao atual de banco.

## Migration

Crie arquivos em `database/migrations` usando ordem no nome:

```text
database/migrations/2026_01_01_000001_create_users.php
```

Exemplo criando tabela com colunas, tipos e atributos:

```php
<?php

declare(strict_types=1);

use App\Database\Contracts\DatabaseConnection;
use App\Database\Schema\Blueprint;

return [
    'up' => function (DatabaseConnection $db): void {
        $db->create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password_hash');
            $table->timestamps();
        });
    },
    'down' => function (DatabaseConnection $db): void {
        $db->dropIfExists('users');
    },
];
```

Tipos comuns suportados no `Blueprint`:

- `id`, `increments`, `bigIncrements`, `foreignId`
- `string`, `char`, `text`, `mediumText`, `longText`
- `integer`, `unsignedInteger`, `bigInteger`, `unsignedBigInteger`, `smallInteger`, `tinyInteger`
- `boolean`, `decimal`, `float`, `double`
- `json`, `date`, `dateTime`, `timestamp`, `timestamps`, `uuid`, `binary`

Atributos comuns:

- `nullable()`, `default($value)`, `unique()`, `index()`, `primary()`, `unsigned()`
- `constrained('users')`, `references('id')->on('users')`, `cascadeOnDelete()`

Exemplo alterando tabela:

```php
return function (DatabaseConnection $db): void {
    $db->alter('users', function (Blueprint $table): void {
        $table->string('phone')->nullable();
    });
};
```

Tambem e possivel usar SQL direto em drivers PDO com `$db->execute(...)` quando precisar de algo especifico do banco.

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
