COMPOSE ?= docker compose
PHP ?= php
ENV_FILE ?= .env

.DEFAULT_GOAL := help

.PHONY: help env up debug-on debug-off db-json db-sqlite db-mysql db-pgsql db-postgres down down-v logs ps build php-shell validate

help:
	@echo "Comandos:"
	@echo "  make up        Abre menu, troca o .env e sobe Docker + ngrok local"
	@echo "  make env       Cria .env a partir de .env.docker.example se nao existir"
	@echo "  make debug-on  Define APP_DEBUG=true no .env"
	@echo "  make debug-off Define APP_DEBUG=false no .env"
	@echo "  make db-json   Troca DB_CONNECTION para json no .env"
	@echo "  make db-sqlite Troca DB_CONNECTION para sqlite no .env"
	@echo "  make db-mysql  Troca .env para MySQL via Docker"
	@echo "  make db-pgsql  Troca .env para PostgreSQL via Docker"
	@echo "  make down      Para containers"
	@echo "  make down-v    Para containers e apaga volumes dos bancos"

env:
	@$(PHP) tools/env.php --init $(ENV_FILE)

debug-on: env
	@$(PHP) tools/env.php $(ENV_FILE) APP_DEBUG true

debug-off: env
	@$(PHP) tools/env.php $(ENV_FILE) APP_DEBUG false

up:
	@$(PHP) tools/up.php $(ENV_FILE) "$(COMPOSE)"

db-json: env
	@$(PHP) tools/env.php $(ENV_FILE) DB_CONNECTION json DB_JSON_PATH storage/database.json

db-sqlite: env
	@$(PHP) tools/env.php $(ENV_FILE) DB_CONNECTION sqlite DB_SQLITE_PATH storage/database.sqlite

db-mysql: env
	@$(PHP) tools/env.php $(ENV_FILE) DB_CONNECTION mysql DB_MYSQL_HOST mysql DB_MYSQL_PORT 3306 DB_MYSQL_DATABASE base DB_MYSQL_USERNAME base DB_MYSQL_PASSWORD base DB_MYSQL_CHARSET utf8mb4 DB_MYSQL_ROOT_PASSWORD root

db-pgsql: env
	@$(PHP) tools/env.php $(ENV_FILE) DB_CONNECTION pgsql DB_PGSQL_HOST postgres DB_PGSQL_PORT 5432 DB_PGSQL_DATABASE base DB_PGSQL_USERNAME base DB_PGSQL_PASSWORD base

db-postgres: db-pgsql

build:
	@$(COMPOSE) build php

php-shell:
	@$(COMPOSE) run --rm php sh

logs:
	@$(COMPOSE) logs -f

ps:
	@$(COMPOSE) ps

down:
	@$(COMPOSE) down

down-v:
	@$(COMPOSE) down -v

validate:
	@$(COMPOSE) --profile mysql --profile postgres config >/dev/null
	@$(PHP) -l tools/env.php
	@$(PHP) -l tools/up.php
