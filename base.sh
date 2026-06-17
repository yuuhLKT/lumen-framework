#!/usr/bin/env bash

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
SOURCE_DIR="$SCRIPT_DIR/src"
TARGET_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"

echo "Base PHP"
echo "Origem:  $SOURCE_DIR"
echo "Destino: $TARGET_ROOT"
echo

if [[ ! -d "$SOURCE_DIR" ]]; then
    echo "Erro: pasta src/ nao encontrada em $SCRIPT_DIR" >&2
    exit 1
fi

read -r -p "Nome do novo projeto: " PROJECT_NAME
PROJECT_NAME="$(printf '%s' "$PROJECT_NAME" | tr -d '\r' | xargs)"

if [[ -z "$PROJECT_NAME" ]]; then
    echo "Erro: informe um nome." >&2
    exit 1
fi

if [[ ! "$PROJECT_NAME" =~ ^[a-zA-Z0-9_-]+$ ]]; then
    echo "Erro: use apenas letras, numeros, hifen e underscore." >&2
    exit 1
fi

if [[ "$PROJECT_NAME" == "base" ]]; then
    echo "Erro: escolha outro nome para nao conflitar com a pasta base." >&2
    exit 1
fi

TARGET_DIR="$TARGET_ROOT/$PROJECT_NAME"

if [[ -e "$TARGET_DIR" ]]; then
    echo "Erro: destino ja existe: $TARGET_DIR" >&2
    exit 1
fi

mkdir -p "$TARGET_DIR"

if command -v rsync >/dev/null 2>&1; then
    rsync -a \
        --exclude='.env' \
        --exclude='storage/*.sqlite' \
        --exclude='storage/*.sqlite-*' \
        --exclude='vendor/' \
        --exclude='composer.lock' \
        --exclude='.phpunit.cache' \
        --exclude='.php-cs-fixer.cache' \
        --exclude='coverage' \
        "$SOURCE_DIR/" "$TARGET_DIR/"
else
    cp -R "$SOURCE_DIR/." "$TARGET_DIR/"
    rm -f "$TARGET_DIR/.env"
    rm -f "$TARGET_DIR"/storage/*.sqlite "$TARGET_DIR"/storage/*.sqlite-* 2>/dev/null || true
    rm -rf "$TARGET_DIR/vendor" "$TARGET_DIR/composer.lock"
    rm -rf "$TARGET_DIR/.phpunit.cache" "$TARGET_DIR/.php-cs-fixer.cache" "$TARGET_DIR/coverage"
fi

if [[ -f "$TARGET_DIR/.env.docker.example" ]]; then
    cp "$TARGET_DIR/.env.docker.example" "$TARGET_DIR/.env"
elif [[ -f "$TARGET_DIR/.env.example" ]]; then
    cp "$TARGET_DIR/.env.example" "$TARGET_DIR/.env"
fi

if [[ -f "$TARGET_DIR/composer.json" && ! -d "$TARGET_DIR/vendor" ]]; then
    echo
    if command -v composer >/dev/null 2>&1; then
        echo "Instalando dependencias do Composer..."
        if (cd "$TARGET_DIR" && composer install --no-interaction --prefer-dist); then
            :
        else
            echo "Aviso: composer install falhou. Rode 'composer install' em $TARGET_DIR antes de usar testes/qualidade." >&2
        fi
    else
        echo "Aviso: composer nao encontrado no PATH." >&2
        echo "Para usar testes e qualidade, instale o Composer e rode 'composer install' em $TARGET_DIR" >&2
    fi
fi

echo
echo "Projeto criado em: $TARGET_DIR"
echo
echo "Proximos passos:"
echo "  cd \"$TARGET_DIR\""
echo
echo "  Rodar local sem Docker:"
echo "    php -S localhost:8000 -t public"
echo "    curl http://localhost:8000/health"
echo "    make serve"
echo
echo "  Rodar com Docker:"
echo "    docker compose build php"
echo "    docker compose up php"
echo "    (se composer install nao rodou local, use: docker compose run --rm php composer install)"
echo
echo "  Banco de dados:"
echo "    make db-json     # JSON em storage/database.json (padrao)"
echo "    make db-sqlite   # SQLite em storage/database.sqlite"
echo "    make db-mysql    # MySQL via Docker"
echo "    make db-pgsql    # PostgreSQL via Docker"
echo "    make fresh       # migrate + seed"
echo "    make migrate     # roda migrations"
echo "    make seed        # roda seeders"
echo
echo "  Testes e qualidade (precisa de vendor/):"
echo "    make deps        # composer install"
echo "    make test        # PHPUnit"
echo "    make analyse     # PHPStan"
echo "    make lint"
echo "    make format      # PHP CS Fixer (altera arquivos)"
echo "    make quality     # lint + format-check + analyse + test"
