# Limitacoes da lumen

Esta lumen e intencionalmente simples. Ela serve para estudos, desafios e projetos pequenos, nao para substituir um framework completo.

> Recursos como Mini Auth so estao presentes se o projeto foi gerado com essa opcao.

## Banco de dados

- Existem migrations em PHP com rollback (`lumen.php migrate:rollback`).
- Nos drivers SQLite, MySQL e PostgreSQL, migrations com `$db->create(...)` criam colunas reais.
- Tabelas acessadas diretamente com `table('nome')`, sem migration de schema, ainda usam o modo simples `id` e `data`.
- `where()` usa o query builder, mas o driver JSON carrega todos os registros em memoria.
- Indices definidos no `Blueprint` existem nos drivers SQL; o driver JSON guarda apenas metadados de schema.
- O driver JSON reescreve o arquivo inteiro a cada escrita.
- Transacoes no driver JSON sao baseadas em snapshot em memoria, nao em lock de arquivo.

## HTTP

- Existem middlewares (`app/Http/Middleware/`) com pipeline e `AuthMiddleware`.
- Nao existe agrupamento de rotas.
- Nao existe container de injecao de dependencia.
- O roteador instancia controllers com `new`, entao construtores precisam ser simples.

## Autenticacao

- Ha auth completa com usuarios: registro, login, logout, tokens opacos com hash SHA-256 (`app/Services/AuthService`).
- Tambem ha suporte a Bearer Token estatico via `.env` (`config/auth.php`).
- Nao ha refresh token ou JWT.
- Use HTTPS se expor qualquer rota protegida fora da maquina local.

## Validacao

- As regras sao basicas.
- `min` e `max` validam tamanho apenas para strings.
- Nao ha validacao aninhada para arrays complexos.

## Docker e Makefile

- MySQL e PostgreSQL usam profiles do Docker Compose.
- O Makefile detecta Docker automaticamente (`RUNNER=auto`). Use `make RUNNER=local up` para forcar PHP local.
- PHP local e necessario apenas para `RUNNER=local` e comandos de qualidade/testes.
- Linux/WSL precisa de `sh` para `make up` no modo Docker.

## Quando evoluir

Considere Laravel, Symfony ou outro framework quando precisar de ORM completo, filas, jobs, refresh tokens, permissoes complexas, cache avancado e deploy de producao.
