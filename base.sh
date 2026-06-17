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
        "$SOURCE_DIR/" "$TARGET_DIR/"
else
    cp -R "$SOURCE_DIR/." "$TARGET_DIR/"
    rm -f "$TARGET_DIR/.env"
    rm -f "$TARGET_DIR"/storage/*.sqlite "$TARGET_DIR"/storage/*.sqlite-* 2>/dev/null || true
fi

if [[ -f "$TARGET_DIR/.env.docker.example" ]]; then
    cp "$TARGET_DIR/.env.docker.example" "$TARGET_DIR/.env"
elif [[ -f "$TARGET_DIR/.env.example" ]]; then
    cp "$TARGET_DIR/.env.example" "$TARGET_DIR/.env"
fi

echo
echo "Projeto criado em: $TARGET_DIR"
echo
echo "Proximos comandos:"
echo "  cd \"$TARGET_DIR\""
echo "  php -S localhost:8000 -t public"
echo
echo "Ou com Makefile/Docker:"
echo "  cd \"$TARGET_DIR\""
echo "  make up"
