# CLI

A lumen possui um CLI proprio em `lumen.php` (e wrappers `lumen` para Unix e `lumen.bat` para Windows).

O Makefile continua existindo, mas agora e focado em Docker e configuracao de ambiente (`.env`, banco, subir/parar containers). Para migrations, seeders, testes, qualidade e servidor local, prefira o CLI.

## Uso

```bash
php lumen.php list
```

No Linux/WSL:

```bash
./lumen list
```

No Windows:

```cmd
lumen.bat list
```

## Comandos disponiveis

### Banco de dados

```bash
php lumen.php migrate            # executa migrations pendentes
php lumen.php migrate:rollback   # reverte a ultima migration
php lumen.php migrate:rollback 3 # reverte as 3 ultimas
php lumen.php migrations:list    # lista status das migrations
php lumen.php migrations:list 5  # lista ultimas 5 executadas
php lumen.php seed               # executa seeders
php lumen.php fresh              # migrate + seed
```

### Geradores

```bash
php lumen.php make:controller UserController
php lumen.php make:repository UserRepository
php lumen.php make:middleware EnsureAdmin
php lumen.php make:migration create_posts_table
```

### Servidor local

```bash
php lumen.php serve
php lumen.php serve localhost 8080
```

### Qualidade

```bash
php lumen.php test
php lumen.php analyse
php lumen.php lint
php lumen.php format
php lumen.php format-check
php lumen.php qa             # lint + format-check + analyse + test
```

### Utilidades

```bash
php lumen.php route:list
php lumen.php doctor         # checa PHP, extensoes, composer, docker, .env e vendor
```

## Formato das migrations

Migrations podem retornar apenas uma funcao (up) ou um array com `up` e `down`:

```php
<?php

declare(strict_types=1);

use App\Database\Contracts\DatabaseConnection;

return [
    'up' => function (DatabaseConnection $connection): void {
        $connection->table('posts')->insert(['name' => 'example']);
    },
    'down' => function (DatabaseConnection $connection): void {
        $connection->table('posts')->delete(1);
    },
];
```

O `down` e opcional.
