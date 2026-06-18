# Docker Compose

A base possui `docker-compose.yml` para rodar PHP, MySQL e PostgreSQL em desenvolvimento.

SQLite nao precisa de container separado. Ele roda dentro do container PHP usando o arquivo configurado em `DB_SQLITE_PATH`.

## Arquivos

- `docker-compose.yml`: servicos PHP, MySQL e PostgreSQL.
- `docker/php/Dockerfile`: imagem PHP com `pdo_mysql` e `pdo_pgsql`.
- `.env.docker.example`: exemplo de variaveis para uso com Docker.
- `Makefile`: atalhos para escolher banco, atualizar `.env` e subir Docker.
- `tools/env.php`: utilitario usado pelo Makefile para alterar `.env`.

## Preparar .env

Na pasta `src`, copie o exemplo de Docker para `.env` se quiser usar os valores prontos:

```bash
cp .env.docker.example .env
```

No Windows PowerShell:

```powershell
Copy-Item .env.docker.example .env
```

O `.env` tem prioridade sobre variaveis de ambiente do sistema.

## Usar Makefile

O Makefile e opcional. Ele automatiza a troca do banco no `.env` e sobe o app no Docker quando o daemon esta disponivel. Se o Docker nao estiver disponivel, `make up` cai para o modo local com `php -S`.

Requisitos:

- `make` instalado no sistema.
- Docker Compose disponivel como `docker compose` para o modo Docker.
- Linux/WSL: `sh` disponivel para `make up` no modo Docker.
- PHP local e necessario apenas para `RUNNER=local`, `make serve` e comandos de qualidade/testes fora do container.

Para subir sem Makefile, use Docker direto:

```bash
cp .env.docker.example .env
docker compose up --build php
```

Ver comandos disponiveis:

```bash
make help
```

Criar `.env` se nao existir:

```bash
make env
```

Trocar somente o banco no `.env`, sem subir container:

```bash
make db-json
make db-sqlite
make db-mysql
make db-pgsql
```

Ligar ou desligar debug no `.env`:

```bash
make debug-on
make debug-off
```

Com debug ligado, erros internos retornam `trace` na resposta JSON.

Subir com o banco configurado no `.env`:

```bash
make up
```

O `make up` le `DB_CONNECTION` do `.env` e sobe os containers necessarios. Se for MySQL ou PostgreSQL, sobe tambem o container do banco. Para escolher o banco antes de subir, use:

```bash
make db-json     # ou db-sqlite, db-mysql, db-pgsql
make up
```

Forcar PHP local, sem subir container PHP:

```bash
make RUNNER=local up
```

Esse comando roda `php -S 0.0.0.0:8000 -t public` usando o `.env` atual.

Para usar PHP local com banco em Docker, suba somente o banco:

```bash
make db-up-mysql
# ou
make db-up-pg

make RUNNER=local up
```

Esses comandos ajustam o `.env` para `127.0.0.1`, porque o PHP esta rodando no host e acessa a porta publicada pelo container do banco.

No modo local, MySQL e PostgreSQL esperam um servidor acessivel pela maquina em `127.0.0.1`.

Parar:

```bash
make down
```

Parar e apagar volumes dos bancos:

```bash
make down-v
```

## Rodar somente PHP via Docker direto

Usa o banco padrao configurado em `DB_CONNECTION`. Se estiver como `json`, nao precisa subir banco externo.

```bash
docker compose up --build php
```

Esse comando sobe o PHP pelo Docker. Para expor externamente com ngrok, rode manualmente:

```bash
ngrok http http://localhost:8000
```

Acesse:

```text
http://localhost:8000/health
```

## Rodar com SQLite

No `.env`:

```env
DB_CONNECTION=sqlite
DB_SQLITE_PATH=storage/database.sqlite
```

Suba com Makefile:

```bash
make up
```

Ou suba direto:

```bash
docker compose up --build php
```

O arquivo SQLite fica em `storage/database.sqlite` no seu projeto.

## Rodar com MySQL

No `.env`:

```env
DB_CONNECTION=mysql
DB_MYSQL_HOST=mysql
DB_MYSQL_PORT=3306
DB_MYSQL_DATABASE=base
DB_MYSQL_USERNAME=base
DB_MYSQL_PASSWORD=base
DB_MYSQL_CHARSET=utf8mb4
DB_MYSQL_ROOT_PASSWORD=root
MYSQL_PORT=3306
```

Suba com Makefile:

```bash
make up
```

Ou suba direto:

```bash
docker compose --profile mysql up --build php mysql
```

Conexao a partir do host:

```text
Host: 127.0.0.1
Porta: 3306
Database: base
Usuario: base
Senha: base
```

Conexao a partir do container PHP:

```text
Host: mysql
Porta: 3306
```

## Rodar com PostgreSQL

No `.env`:

```env
DB_CONNECTION=pgsql
DB_PGSQL_HOST=postgres
DB_PGSQL_PORT=5432
DB_PGSQL_DATABASE=base
DB_PGSQL_USERNAME=base
DB_PGSQL_PASSWORD=base
POSTGRES_PORT=5432
```

Suba com Makefile:

```bash
make up
```

Ou suba direto:

```bash
docker compose --profile postgres up --build php postgres
```

Tambem funciona com o profile `pgsql`:

```bash
docker compose --profile pgsql up --build php postgres
```

Conexao a partir do host:

```text
Host: 127.0.0.1
Porta: 5432
Database: base
Usuario: base
Senha: base
```

Conexao a partir do container PHP:

```text
Host: postgres
Porta: 5432
```

## Ativar e desativar servicos

Subir apenas PHP:

```bash
docker compose up php
```

Subir PHP + MySQL:

```bash
docker compose --profile mysql up php mysql
```

Subir PHP + PostgreSQL:

```bash
docker compose --profile postgres up php postgres
```

Subir PHP + MySQL + PostgreSQL:

```bash
docker compose --profile mysql --profile postgres up php mysql postgres
```

Parar tudo:

```bash
docker compose down
```

Parar e apagar volumes dos bancos:

```bash
docker compose down -v
```

## Volumes

- `mysql_data`: dados do MySQL.
- `postgres_data`: dados do PostgreSQL.
- O codigo fonte fica montado em `/app` dentro do container PHP.

## Observacoes

- Services com `profiles` so sobem quando o profile e ativado ou quando o servico e chamado diretamente.
- O PHP nao usa `depends_on` para os bancos, entao voce escolhe qual banco subir.
- Se o banco ainda estiver iniciando, aguarde o healthcheck ficar saudavel antes de testar rotas que acessam banco.
