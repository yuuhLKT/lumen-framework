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

- Roteamento basico.
- Validacao.
- Transacao do driver JSON.
- HTTP client fake.

Use estes testes como referencia para testar controllers, services, repositories e clients do seu projeto.
