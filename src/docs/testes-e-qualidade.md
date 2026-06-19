# Testes e qualidade

Composer e opcional, mas recomendado para usar testes e ferramentas de qualidade.

## Instalar dependencias

```bash
composer install
```

## Testes

```bash
composer test
```

Ou diretamente:

```bash
vendor/bin/phpunit
```

## Analise estatica

```bash
composer analyse
```

## Lint PHP

```bash
composer lint
```

## Formatacao

```bash
composer format
```

## O que ja vem testado

- Roteamento basico com parametros e middlewares.
- Validacao de input.
- Transacao do driver JSON.
- Query builder (JSON e SQLite) com where, orWhere, whereIn, groupBy, join, paginate.
- Relacionamentos: HasMany, BelongsTo, BelongsToMany (attach/detach/sync).
- MigrationRunner (status e run).
- AuthService (registro, login, logout, token invalido).
- HTTP client fake.

Use estes testes como referencia para testar controllers, services, repositories e clients do seu projeto.

## Primeiro teste no projeto gerado

Projetos gerados pelo `lumen.sh` nao trazem a pasta `tests/` para que voce crie seus proprios testes. Para comecar, crie `tests/MeuTeste.php`:

```php
<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;

final class MeuTeste extends TestCase
{
    public function testExemplo(): void
    {
        self::assertTrue(true);
    }
}
```

E execute:

```bash
composer test
# ou
php lumen.php test
```

Se `tests/` ainda nao existir, o comando explica como comecar.
