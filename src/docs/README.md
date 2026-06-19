# Documentacao da lumen

Esta pasta separa a documentacao por assunto. Use o README da raiz para rodar o projeto e estes arquivos para entender como cada parte funciona.

## Indice

- [Arquitetura e fluxo da requisicao](arquitetura.md)
- [Gerador de projetos](gerador.md)
- [Rotas e controllers](rotas-e-controllers.md)
- [Middleware](middleware.md)
- [Mini Auth](autenticacao.md)
- [Request e response](request-e-response.md)
- [Validacao](validacao.md)
- [Banco de dados e repositories](banco-e-repositories.md)
- [Migrations e seeders](migrations-e-seeders.md)
- [CLI](cli.md)
- [HTTP client](http-client.md)
- [DTOs](dtos.md)
- [Services e organizacao de camadas](services-e-camadas.md)
- [Configuracao, .env e helpers](configuracao-e-helpers.md)
- [Testes e qualidade](testes-e-qualidade.md)
- [Docker Compose](docker.md)
- [Erros e codigos HTTP](erros-e-http-status.md)
- [Limitacoes da lumen](limitacoes.md)
- [Guia rapido: criar recurso CRUD](guia-crud.md)

## Ideia da lumen

A lumen simula o esqueleto minimo de um framework pequeno:

- `public/index.php` recebe a requisicao.
- `bootstrap/app.php` prepara autoload, helpers, `.env` e timezone.
- `routes/web.php` registra as rotas.
- `Router` encontra o handler certo.
- `Auth` valida Bearer Token de usuario quando a rota estiver protegida.
- `Request` entrega query string, body e dados do servidor.
- `Controller` ajuda a validar e responder.
- `Response` envia JSON, HTML, texto ou redirect.
- `ErrorHandler` padroniza erros em JSON.
- `Database`, `Table` e `BaseRepository` escondem se o banco e JSON, SQLite, MySQL ou PostgreSQL.
- `MigrationRunner` e `SeederRunner` executam scripts simples de banco.
- `HttpClient` padroniza chamadas externas e facilita testes com fake.

## Como estudar

Leia nesta ordem se estiver conhecendo o projeto:

1. `arquitetura.md`
2. `gerador.md`
3. `rotas-e-controllers.md`
4. `autenticacao.md`
5. `request-e-response.md`
6. `validacao.md`
7. `banco-e-repositories.md`
8. `migrations-e-seeders.md`
9. `services-e-camadas.md`
10. `http-client.md`
11. `testes-e-qualidade.md`
12. `docker.md`
13. `limitacoes.md`
14. `guia-crud.md`
