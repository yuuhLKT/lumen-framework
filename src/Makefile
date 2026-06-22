COMPOSE ?= docker compose
COMPOSER ?= composer
PHP ?= $(shell if command -v php >/dev/null 2>&1; then command -v php; elif command -v php.exe >/dev/null 2>&1; then command -v php.exe; else printf '%s' php; fi)
SH ?= sh
ENV_FILE ?= .env

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
ENV_RUN        = $(SH) tools/env.sh
UP_RUN         = if command -v script >/dev/null 2>&1; then script -q -c '$(SH) tools/up-docker.sh $(ENV_FILE) "$(COMPOSE)"' /dev/null; else $(SH) tools/up-docker.sh $(ENV_FILE) "$(COMPOSE)"; fi
DOWN_RUN       = $(COMPOSE) down
DOWN_V_RUN     = $(COMPOSE) down -v
BUILD_RUN      = $(COMPOSE) build php
SHELL_RUN      = $(COMPOSE) run --rm php sh
LOGS_RUN       = $(COMPOSE) logs -f
PS_RUN         = $(COMPOSE) ps
ENV_TEMPLATE   = .env.docker.example
DB_HOST_MYSQL  = mysql
DB_HOST_PG     = postgres
else
ENV_RUN        = $(PHP) tools/env.php
UP_RUN         = $(PHP) -S 0.0.0.0:8000 -t public
DOWN_RUN       = @echo "Nada para parar no modo local (servidor roda em foreground com Ctrl+C)."
DOWN_V_RUN     = @echo "Nada para parar. Para limpar dados locais use: rm -rf storage/*.sqlite storage/*.json"
BUILD_RUN      = $(COMPOSER) install
SHELL_RUN      = $(PHP) -a
LOGS_RUN       = @echo "Sem logs de container no modo local."
PS_RUN         = @echo "Sem container no modo local. Use: ps aux | grep php"
ENV_TEMPLATE   = .env.example
DB_HOST_MYSQL  = 127.0.0.1
DB_HOST_PG     = 127.0.0.1
endif

.DEFAULT_GOAL := help

.PHONY: help runner env up local down down-v build php-shell logs ps validate \
        debug-on debug-off db-json db-sqlite db-mysql db-pgsql db-postgres \
        db-up-mysql db-up-pg db-up-postgres db-down \
        deps serve migrate seed fresh test local-test analyse lint format format-check quality qa doctor

help:
	@echo "Lumen PHP - Makefile"
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
	@echo "  make up             Abre menu para subir PHP/banco via Docker ou PHP local"
	@echo "  make local          Sobe o PHP local sem Docker"
	@echo "  make db-up-mysql    Sobe somente MySQL no Docker para uso com make RUNNER=local up"
	@echo "  make db-up-pg       Sobe somente PostgreSQL no Docker para uso com make RUNNER=local up"
	@echo "  make db-down        Para bancos Docker"
	@echo "  make down           Para containers / avisa no modo local"
	@echo "  make down-v         Para containers e remove volumes / avisa no modo local"
	@echo "  make build          docker compose build ou composer install"
	@echo "  make php-shell      Shell dentro do container ou REPL local do PHP"
	@echo "  make logs           Logs do compose / aviso no modo local"
	@echo "  make ps             Containers / aviso no modo local"
	@echo "  make validate       Valida compose / lint dos tools"
	@echo
	@echo "Qualidade:"
	@echo "  make deps           composer install"
	@echo "  make migrate        Roda migrations (lumen.php migrate)"
	@echo "  make seed           Roda seeders (lumen.php seed)"
	@echo "  make fresh          migrate + seed"
	@echo "  make serve          php -S localhost:8000 -t public"
	@echo "  make test           PHPUnit (lumen.php test)"
	@echo "  make local-test     PHPUnit no host"
	@echo "  make analyse        PHPStan (lumen.php analyse)"
	@echo "  make lint           Lint proprio (lumen.php lint)"
	@echo "  make format         PHP CS Fixer (altera arquivos)"
	@echo "  make format-check   PHP CS Fixer (dry-run)"
	@echo "  make quality        lint + format-check + analyse + test"
	@echo "  make qa             executa php lumen.php qa"
	@echo "  make doctor         checa ambiente (php lumen.php doctor)"

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

local:
	@$(MAKE) RUNNER=local up

db-json: env
	@$(ENV_RUN) $(ENV_FILE) DB_CONNECTION json DB_JSON_PATH storage/database.json

db-sqlite: env
	@$(ENV_RUN) $(ENV_FILE) DB_CONNECTION sqlite DB_SQLITE_PATH storage/database.sqlite

db-mysql: env
	@$(ENV_RUN) $(ENV_FILE) DB_CONNECTION mysql DB_MYSQL_HOST $(DB_HOST_MYSQL) DB_MYSQL_PORT 3306 DB_MYSQL_DATABASE lumen DB_MYSQL_USERNAME lumen DB_MYSQL_PASSWORD lumen DB_MYSQL_CHARSET utf8mb4 DB_MYSQL_ROOT_PASSWORD root

db-pgsql: env
	@$(ENV_RUN) $(ENV_FILE) DB_CONNECTION pgsql DB_PGSQL_HOST $(DB_HOST_PG) DB_PGSQL_PORT 5432 DB_PGSQL_DATABASE lumen DB_PGSQL_USERNAME lumen DB_PGSQL_PASSWORD lumen

db-postgres: db-pgsql

db-up-mysql:
	@$(SH) tools/env.sh --init $(ENV_FILE) .env.example
	@$(SH) tools/env.sh $(ENV_FILE) DB_CONNECTION mysql DB_MYSQL_HOST 127.0.0.1 DB_MYSQL_PORT 3306 DB_MYSQL_DATABASE lumen DB_MYSQL_USERNAME lumen DB_MYSQL_PASSWORD lumen DB_MYSQL_CHARSET utf8mb4 DB_MYSQL_ROOT_PASSWORD root
	@$(COMPOSE) --profile mysql up -d mysql
	@echo "MySQL Docker pronto. Rode: make RUNNER=local up"

db-up-pg:
	@$(SH) tools/env.sh --init $(ENV_FILE) .env.example
	@$(SH) tools/env.sh $(ENV_FILE) DB_CONNECTION pgsql DB_PGSQL_HOST 127.0.0.1 DB_PGSQL_PORT 5432 DB_PGSQL_DATABASE lumen DB_PGSQL_USERNAME lumen DB_PGSQL_PASSWORD lumen
	@$(COMPOSE) --profile postgres up -d postgres
	@echo "PostgreSQL Docker pronto. Rode: make RUNNER=local up"

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
	@$(SH) -n tools/env.sh
	@$(SH) -n tools/up-docker.sh
else
	@$(PHP) -l tools/env.php
	@$(PHP) -l tools/migrate.php
	@$(PHP) -l tools/seed.php
endif

deps:
	@$(COMPOSER) install

serve:
	@$(PHP) -S localhost:8000 -t public

migrate:
	@$(PHP) lumen.php migrate

seed:
	@$(PHP) lumen.php seed

fresh: migrate seed

test:
	@$(PHP) lumen.php test

local-test: test

analyse:
	@$(PHP) lumen.php analyse

lint:
	@$(PHP) lumen.php lint

format:
	@$(PHP) lumen.php format

format-check:
	@$(PHP) lumen.php format-check

quality: lint format-check analyse test

qa:
	@$(PHP) lumen.php qa

doctor:
	@$(PHP) lumen.php doctor
