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
