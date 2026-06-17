COMPOSE ?= docker compose
COMPOSER ?= composer
PHP ?= php
SH ?= sh
ENV_FILE ?= .env

ifeq ($(OS),Windows_NT)
ENV_SCRIPT = powershell.exe -NoProfile -ExecutionPolicy Bypass -File tools/env.ps1
UP_DOCKER_SCRIPT = powershell.exe -NoProfile -ExecutionPolicy Bypass -File tools/up-docker.ps1
SCRIPT_VALIDATE = powershell.exe -NoProfile -ExecutionPolicy Bypass -File tools/validate-scripts.ps1
else
ENV_SCRIPT = $(SH) tools/env.sh
UP_DOCKER_SCRIPT = $(SH) tools/up-docker.sh
SCRIPT_VALIDATE = $(SH) -n tools/env.sh && $(SH) -n tools/up-docker.sh
endif

# RUNNER controla o executor usado pelos alvos compartilhados.
#   auto   - padrao. Usa docker se o daemon estiver disponivel, senao local.
#   docker - forca docker compose em up/down/build/shell/logs/ps/validate.
#   local  - forca PHP + composer locais nos mesmos alvos.
RUNNER ?= auto

ifeq ($(filter auto docker local,$(RUNNER)),)
$(error RUNNER invalido: '$(RUNNER)'. Use auto, docker ou local.)
endif

ifeq ($(RUNNER),auto)
HAVE_DOCKER := $(shell docker info >/dev/null 2>&1 && docker compose version >/dev/null 2>&1 && echo y || echo n)
ifeq ($(HAVE_DOCKER),y)
RUNNER := docker
else
RUNNER := local
endif
endif

ifeq ($(RUNNER),docker)
ENV_RUN        = $(ENV_SCRIPT)
UP_RUN         = script -q -c '$(UP_DOCKER_SCRIPT) $(ENV_FILE) "$(COMPOSE)"' /dev/null
UP_MYSQL_RUN   = script -q -c '$(UP_DOCKER_SCRIPT) $(ENV_FILE) "$(COMPOSE)" --profile=mysql' /dev/null
UP_PG_RUN      = script -q -c '$(UP_DOCKER_SCRIPT) $(ENV_FILE) "$(COMPOSE)" --profile=pgsql' /dev/null
DOWN_RUN       = $(COMPOSE) down
DOWN_V_RUN     = $(COMPOSE) down -v
BUILD_RUN      = $(COMPOSE) build php
SHELL_RUN      = $(COMPOSE) run --rm php sh
LOGS_RUN       = $(COMPOSE) logs -f
PS_RUN         = $(COMPOSE) ps
VALIDATE_RUN   = $(COMPOSE) --profile mysql --profile postgres config >/dev/null
ENV_TEMPLATE   = .env.docker.example
DB_HOST_MYSQL  = mysql
DB_HOST_PG     = postgres
else
ENV_RUN        = $(PHP) tools/env.php
UP_RUN         = $(PHP) -S 0.0.0.0:8000 -t public
UP_MYSQL_RUN   = $(error MySQL em modo local exige um MySQL rodando na maquina. Ajuste DB_MYSQL_HOST no .env ou use 'make docker-up'.)
UP_PG_RUN      = $(error Postgres em modo local exige um PostgreSQL rodando na maquina. Ajuste DB_PGSQL_HOST no .env ou use 'make docker-up'.)
DOWN_RUN       = @echo "Nada para parar no modo local (servidor roda em foreground com Ctrl+C)."
DOWN_V_RUN     = @echo "Nada para parar. Para limpar dados locais use: rm -rf storage/*.sqlite storage/*.json"
BUILD_RUN      = $(COMPOSER) install
SHELL_RUN      = $(PHP) -a
LOGS_RUN       = @echo "Sem logs de container no modo local."
PS_RUN         = @echo "Sem container no modo local. Use: ps aux | grep php"
VALIDATE_RUN   = $(PHP) -l tools/env.php && $(PHP) -l tools/up.php
ENV_TEMPLATE   = .env.example
DB_HOST_MYSQL  = 127.0.0.1
DB_HOST_PG     = 127.0.0.1
endif

.DEFAULT_GOAL := help

.PHONY: help runner env up up-mysql up-pg down down-v build php-shell logs ps validate \
        local local-down local-down-v local-build local-shell local-logs local-ps local-validate \
        debug-on debug-off db-json db-sqlite db-mysql db-pgsql db-postgres \
        db-up-mysql db-up-pg db-up-postgres db-down

help:
	@echo "Base PHP - Makefile"
	@echo
	@echo "Runner atual: $(RUNNER)  (override: make RUNNER=local up)"
	@echo "Detalhe do runner: make runner"
	@echo
	@echo "Ambiente:"
	@echo "  make env            Cria $(ENV_FILE) a partir de $(ENV_TEMPLATE) se nao existir"
	@echo "  make debug-on       Define APP_DEBUG=true no $(ENV_FILE)"
	@echo "  make debug-off      Define APP_DEBUG=false no $(ENV_FILE)"
	@echo
	@echo "Banco (altera o $(ENV_FILE)):"
	@echo "  make db-json        DB_CONNECTION=json"
	@echo "  make db-sqlite      DB_CONNECTION=sqlite"
	@echo "  make db-mysql       DB_CONNECTION=mysql (host=$(DB_HOST_MYSQL))"
	@echo "  make db-pgsql       DB_CONNECTION=pgsql (host=$(DB_HOST_PG))"
	@echo "  make db-postgres    alias de db-pgsql"
	@echo
	@echo "Subir / parar (modo detectado em RUNNER):"
	@echo "  make up             Sobe app (docker compose up ou php -S)"
	@echo "  make up-mysql       Sobe app + MySQL (requer docker)"
	@echo "  make up-pg          Sobe app + PostgreSQL (requer docker)"
	@echo "  make db-up-mysql    Sobe somente MySQL no Docker para uso com make local"
	@echo "  make db-up-pg       Sobe somente PostgreSQL no Docker para uso com make local"
	@echo "  make db-down        Para bancos Docker"
	@echo "  make down           Para containers / avisa no modo local"
	@echo "  make down-v         Para containers e remove volumes / avisa no modo local"
	@echo "  make build          docker compose build ou composer install"
	@echo "  make php-shell      Shell dentro do container ou REPL local do PHP"
	@echo "  make logs           Logs do compose / aviso no modo local"
	@echo "  make ps             Containers / aviso no modo local"
	@echo "  make validate       Valida compose / lint dos tools"
	@echo
	@echo "Modo local (forca RUNNER=local):"
	@echo "  make local          Sobe apenas o PHP local, usando o .env atual"
	@echo "  make local-down     Aviso de parada no modo local"
	@echo "  make local-build    composer install"
	@echo "  make local-shell    REPL PHP local"
	@echo "  make local-validate Lint dos tools"
	@echo
	@echo "Outros comandos (use o CLI):"
	@echo "  php base.php list              lista comandos"
	@echo "  php base.php migrate           roda migrations"
	@echo "  php base.php migrate:rollback  reverte migrations"
	@echo "  php base.php migrations:list   status das migrations"
	@echo "  php base.php seed              roda seeders"
	@echo "  php base.php fresh             migrate + seed"
	@echo "  php base.php serve             sobe servidor local"
	@echo "  php base.php test              PHPUnit"
	@echo "  php base.php analyse           PHPStan"
	@echo "  php base.php lint              lint proprio"
	@echo "  php base.php format            formata com PHP CS Fixer"

runner:
	@echo "RUNNER resolvido: $(RUNNER)"
	@echo "ENV template:    $(ENV_TEMPLATE)"
	@echo "UP_RUN:          $(UP_RUN)"

env:
	@$(ENV_RUN) --init $(ENV_FILE) $(ENV_TEMPLATE)

debug-on: env
	@$(ENV_RUN) $(ENV_FILE) APP_DEBUG true

debug-off: env
	@$(ENV_RUN) $(ENV_FILE) APP_DEBUG false

up: env
	@$(UP_RUN)

up-mysql: env
	@$(UP_MYSQL_RUN)

up-pg: env
	@$(UP_PG_RUN)

db-json: env
	@$(ENV_RUN) $(ENV_FILE) DB_CONNECTION json DB_JSON_PATH storage/database.json

db-sqlite: env
	@$(ENV_RUN) $(ENV_FILE) DB_CONNECTION sqlite DB_SQLITE_PATH storage/database.sqlite

db-mysql: env
	@$(ENV_RUN) $(ENV_FILE) DB_CONNECTION mysql DB_MYSQL_HOST $(DB_HOST_MYSQL) DB_MYSQL_PORT 3306 DB_MYSQL_DATABASE base DB_MYSQL_USERNAME base DB_MYSQL_PASSWORD base DB_MYSQL_CHARSET utf8mb4 DB_MYSQL_ROOT_PASSWORD root

db-pgsql: env
	@$(ENV_RUN) $(ENV_FILE) DB_CONNECTION pgsql DB_PGSQL_HOST $(DB_HOST_PG) DB_PGSQL_PORT 5432 DB_PGSQL_DATABASE base DB_PGSQL_USERNAME base DB_PGSQL_PASSWORD base

db-postgres: db-pgsql

db-up-mysql:
	@$(ENV_SCRIPT) --init $(ENV_FILE) .env.example
	@$(ENV_SCRIPT) $(ENV_FILE) DB_CONNECTION mysql DB_MYSQL_HOST 127.0.0.1 DB_MYSQL_PORT 3306 DB_MYSQL_DATABASE base DB_MYSQL_USERNAME base DB_MYSQL_PASSWORD base DB_MYSQL_CHARSET utf8mb4 DB_MYSQL_ROOT_PASSWORD root
	@$(COMPOSE) --profile mysql up -d mysql
	@echo "MySQL Docker pronto. Rode: make local"

db-up-pg:
	@$(ENV_SCRIPT) --init $(ENV_FILE) .env.example
	@$(ENV_SCRIPT) $(ENV_FILE) DB_CONNECTION pgsql DB_PGSQL_HOST 127.0.0.1 DB_PGSQL_PORT 5432 DB_PGSQL_DATABASE base DB_PGSQL_USERNAME base DB_PGSQL_PASSWORD base
	@$(COMPOSE) --profile postgres up -d postgres
	@echo "PostgreSQL Docker pronto. Rode: make local"

db-up-postgres: db-up-pg

db-down:
	@$(COMPOSE) stop mysql postgres

build:
	@$(BUILD_RUN)

php-shell:
	@$(SHELL_RUN)

logs:
	@$(LOGS_RUN)

ps:
	@$(PS_RUN)

down:
	@$(DOWN_RUN)

down-v:
	@$(DOWN_V_RUN)

validate:
ifeq ($(RUNNER),docker)
	@$(COMPOSE) --profile mysql --profile postgres config >/dev/null
	@$(SCRIPT_VALIDATE)
else
	@$(PHP) -l tools/env.php
	@$(PHP) -l tools/up.php
endif

local:
	@$(ENV_SCRIPT) --init $(ENV_FILE) .env.example
	@$(PHP) -S 0.0.0.0:8000 -t public

local-down:
	@echo "Nada para parar no modo local (servidor roda em foreground com Ctrl+C)."

local-down-v:
	@echo "Nada para parar. Para limpar dados locais use: rm -rf storage/*.sqlite storage/*.json"

local-build:
	@$(COMPOSER) install

local-shell:
	@$(PHP) -a

local-logs:
	@echo "Sem logs de container no modo local."

local-ps:
	@echo "Sem container no modo local. Use: ps aux | grep php"

local-validate:
	@$(PHP) -l tools/env.php
	@$(PHP) -l tools/up.php
