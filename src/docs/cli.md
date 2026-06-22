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
php lumen.php migrate:list       # lista status das migrations
php lumen.php migrate:list 5     # lista ultimas 5 executadas
php lumen.php seed               # executa seeders
php lumen.php fresh              # migrate + seed
```

### Geradores

```bash
php lumen.php make                 # seleciona um ou mais geradores
php lumen.php make:controller User # cria UserController
php lumen.php make:repository User # cria UserRepository
php lumen.php make:middleware Auth # cria AuthMiddleware
php lumen.php make:dto User        # cria UserDTO
php lumen.php make:test User       # cria UserTest
php lumen.php make:migration create_posts_table
```

O comando agrupado `make` mostra os geradores disponiveis, permite selecionar mais de um por numero separado por virgula e depois pergunta o nome de cada arquivo. Os geradores adicionam o sufixo esperado automaticamente quando ele nao for informado; por exemplo, `make:repository User` cria `UserRepository`, e `make:repository UserRepository` nao duplica o sufixo.

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
use App\Database\Schema\Blueprint;

return [
    'up' => function (DatabaseConnection $connection): void {
        $connection->create('posts', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->text('body')->nullable();
            $table->timestamps();
        });
    },
    'down' => function (DatabaseConnection $connection): void {
        $connection->dropIfExists('posts');
    },
];
```

O `down` e opcional.
