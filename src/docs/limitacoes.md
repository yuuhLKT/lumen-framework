# Limitacoes da base

Esta base e intencionalmente simples. Ela serve para estudos, desafios e projetos pequenos, nao para substituir um framework completo.

## Banco de dados

- Existem migrations simples em PHP, mas nao ha rollback automatico nem diff de schema.
- Nos drivers SQLite, MySQL e PostgreSQL, cada tabela tem `id` e `data`.
- Os atributos ficam serializados como JSON na coluna `data`.
- `where()` carrega todos os registros e filtra em PHP.
- Nao ha indices por campo do JSON.
- O driver JSON reescreve o arquivo inteiro a cada escrita.
- Transacoes no driver JSON sao baseadas em snapshot em memoria, nao em lock de arquivo.

## HTTP

- Nao existem middlewares.
- Nao existe agrupamento de rotas.
- Nao existe container de injecao de dependencia.
- O roteador instancia controllers com `new`, entao construtores precisam ser simples.

## Autenticacao

- Bearer Token e estatico via `.env`.
- Nao existe usuario logado, sessao, refresh token ou JWT.
- Use HTTPS se expor qualquer rota protegida fora da maquina local.

## Validacao

- As regras sao basicas.
- `min` e `max` validam tamanho apenas para strings.
- Nao ha validacao aninhada para arrays complexos.

## Docker e Makefile

- MySQL e PostgreSQL usam profiles do Docker Compose.
- Ngrok roda localmente pelo Makefile, nao dentro do Docker.
- O Makefile espera `php`, `docker compose`, `make` e `ngrok` disponiveis no sistema.

## Quando evoluir

Considere Laravel, Symfony ou outro framework quando precisar de migrations reversiveis, ORM completo, filas, jobs, autenticação de usuários, permissões complexas, middlewares, cache avancado e deploy de produção.
