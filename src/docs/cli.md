# CLI

A base possui um CLI proprio em `base.php` (e wrappers `base` para Unix e `base.bat` para Windows).

O Makefile continua existindo, mas agora e focado em Docker e configuracao de ambiente (`.env`, banco, subir/parar containers). Para migrations, seeders, testes, qualidade e servidor local, prefira o CLI.

## Uso

```bash
php base.php list
```

No Linux/WSL:

```bash
./base list
```

No Windows:

```cmd
base.bat list
```

## Comandos disponiveis

### Banco de dados

```bash
php base.php migrate            # executa migrations pendentes
php base.php migrate:rollback   # reverte a ultima migration
php base.php migrate:rollback 3 # reverte as 3 ultimas
php base.php migrations:list    # lista status das migrations
php base.php migrations:list 5  # lista ultimas 5 executadas
php base.php seed               # executa seeders
php base.php fresh              # migrate + seed
```

### Geradores

```bash
php base.php make:controller UserController
php base.php make:repository UserRepository
php base.php make:middleware EnsureAdmin
php base.php make:migration create_posts_table
```

### Servidor local

```bash
php base.php serve
php base.php serve localhost 8080
```

### Qualidade

```bash
php base.php test
php base.php analyse
php base.php lint
php base.php format
php base.php format-check
php base.php qa             # lint + format-check + analyse + test
```

### Utilidades

```bash
php base.php route:list
php base.php doctor         # checa PHP, extensoes, composer, docker, .env e vendor
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
