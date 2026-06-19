#!/usr/bin/env bash

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
SOURCE_DIR="$SCRIPT_DIR/src"
TARGET_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"

echo "Lumen PHP"
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

if [[ "$PROJECT_NAME" == "lumen" ]]; then
    echo "Erro: escolha outro nome para nao conflitar com a pasta lumen." >&2
    exit 1
fi

read -r -p "Incluir Mini Auth? [s/N]: " INCLUDE_AUTH
INCLUDE_AUTH="$(printf '%s' "$INCLUDE_AUTH" | tr -d '\r' | xargs | tr '[:upper:]' '[:lower:]')"

if [[ "$INCLUDE_AUTH" == "s" || "$INCLUDE_AUTH" == "sim" || "$INCLUDE_AUTH" == "y" || "$INCLUDE_AUTH" == "yes" ]]; then
    INCLUDE_AUTH="yes"
else
    INCLUDE_AUTH="no"
fi

TARGET_DIR="$TARGET_ROOT/$PROJECT_NAME"

if [[ -e "$TARGET_DIR" ]]; then
    echo "Erro: destino ja existe: $TARGET_DIR" >&2
    exit 1
fi

mkdir -p "$TARGET_DIR"

copy_source_files() {
    if command -v rsync >/dev/null 2>&1; then
        rsync -a \
            --exclude='.env' \
            --exclude='storage/*.sqlite' \
            --exclude='storage/*.sqlite-*' \
            --exclude='AGENTS.md' \
            --exclude='tests/' \
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
        rm -rf "$TARGET_DIR/AGENTS.md" "$TARGET_DIR/tests"
        rm -rf "$TARGET_DIR/vendor" "$TARGET_DIR/composer.lock"
        rm -rf "$TARGET_DIR/.phpunit.cache" "$TARGET_DIR/.php-cs-fixer.cache" "$TARGET_DIR/coverage"
    fi
}

copy_source_files

if [[ -f "$SCRIPT_DIR/.gitattributes" ]]; then
    cp "$SCRIPT_DIR/.gitattributes" "$TARGET_DIR/.gitattributes"
fi

remove_auth_files() {
    rm -f \
        "$TARGET_DIR/app/Controllers/AuthController.php" \
        "$TARGET_DIR/app/Services/AuthService.php" \
        "$TARGET_DIR/app/Repositories/AuthTokenRepository.php" \
        "$TARGET_DIR/app/Repositories/UserRepository.php" \
        "$TARGET_DIR/app/Core/Auth.php" \
        "$TARGET_DIR/app/Http/Middleware/AuthMiddleware.php" \
        "$TARGET_DIR/config/auth.php" \
        "$TARGET_DIR/docs/autenticacao.md"

    cat > "$TARGET_DIR/routes/web.php" <<'PHP'
<?php

declare(strict_types=1);

use App\Controllers\HealthController;
use App\Core\Router;

$router = new Router();

$router->get('/health', [HealthController::class, 'show']);

return $router;
PHP

    if [[ -f "$TARGET_DIR/app/Core/Router.php" ]]; then
        sed '/^use App\\Http\\Middleware\\AuthMiddleware;$/d' "$TARGET_DIR/app/Core/Router.php" \
            | awk '
                /public function auth\(\): self/ {
                    print "    public function auth(): self";
                    print "    {";
                    print "        throw new \\LogicException('\''Mini Auth was not included in this project.'\'');";
                    print "    }";
                    skip = 1;
                    next;
                }
                skip && /^    }$/ {
                    skip = 0;
                    next;
                }
                !skip { print }
            ' > "$TARGET_DIR/app/Core/Router.php.tmp" \
            && mv "$TARGET_DIR/app/Core/Router.php.tmp" "$TARGET_DIR/app/Core/Router.php"
    fi

    cat > "$TARGET_DIR/database/migrations/2026_01_01_000000_create_initial_tables.php" <<'PHP'
<?php

declare(strict_types=1);

use App\Database\Contracts\DatabaseConnection;

return [
    'up' => function (DatabaseConnection $db): void {
        // Adicione suas tabelas aqui quando precisar inicializar o banco.
    },
    'down' => function (DatabaseConnection $db): void {
        // Reversao intencionalmente vazia para o projeto sem tabelas iniciais.
    },
];
PHP

    for file in "$TARGET_DIR/.env.example" "$TARGET_DIR/.env.docker.example" "$TARGET_DIR/.env"; do
        if [[ -f "$file" ]]; then
            grep -v '^DEV_BEARER_TOKEN=' "$file" > "$file.tmp" && mv "$file.tmp" "$file"
        fi
    done

    if [[ -f "$TARGET_DIR/docker-compose.yml" ]]; then
        grep -v 'DEV_BEARER_TOKEN:' "$TARGET_DIR/docker-compose.yml" > "$TARGET_DIR/docker-compose.yml.tmp" \
            && mv "$TARGET_DIR/docker-compose.yml.tmp" "$TARGET_DIR/docker-compose.yml"
    fi

    if [[ -f "$TARGET_DIR/composer.json" ]]; then
        awk '
            /^[[:space:]]*"autoload-dev"[[:space:]]*:/ {
                skip = 1;
                depth = 1;
                next;
            }
            skip {
                depth += gsub(/\{/, "{");
                depth -= gsub(/\}/, "}");

                if (depth <= 0) {
                    skip = 0;
                }

                next;
            }
            { print }
        ' "$TARGET_DIR/composer.json" > "$TARGET_DIR/composer.json.tmp" \
            && mv "$TARGET_DIR/composer.json.tmp" "$TARGET_DIR/composer.json"
    fi

    if [[ -f "$TARGET_DIR/docs/README.md" ]]; then
        grep -v 'autenticacao.md' "$TARGET_DIR/docs/README.md" \
            | grep -v 'Mini Auth' \
            | grep -v 'Auth` valida Bearer Token' \
            > "$TARGET_DIR/docs/README.md.tmp" \
            && mv "$TARGET_DIR/docs/README.md.tmp" "$TARGET_DIR/docs/README.md"
    fi
}

if [[ "$INCLUDE_AUTH" == "no" ]]; then
    remove_auth_files
fi

if [[ -f "$TARGET_DIR/.env.example" ]]; then
    cp "$TARGET_DIR/.env.example" "$TARGET_DIR/.env"
elif [[ -f "$TARGET_DIR/.env.docker.example" ]]; then
    cp "$TARGET_DIR/.env.docker.example" "$TARGET_DIR/.env"
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
echo "Mini Auth: $([[ "$INCLUDE_AUTH" == "yes" ]] && echo "incluido" || echo "nao incluido")"
echo
echo "Proximos passos:"
echo "  cd \"$TARGET_DIR\""
echo
echo "  Rodar PHP local (recomendado para comecar):"
echo "    make local"
echo "    curl http://localhost:8000/health"
echo
echo "  Rodar PHP local com banco em Docker:"
echo "    make db-up-mysql   # sobe somente MySQL e ajusta .env para 127.0.0.1"
echo "    make local"
echo "    # ou: make db-up-pg && make local"
echo
echo "  Rodar tudo pelo fluxo Docker/auto:"
echo "    make up            # detecta Docker, escolhe banco e sobe o app"
echo "    make down          # para containers do compose"
echo
echo "  Banco de dados:"
echo "    make db-json     # JSON em storage/database.json (padrao)"
echo "    make db-sqlite   # SQLite em storage/database.sqlite"
echo "    make db-mysql    # configura MySQL no .env (host depende do runner)"
echo "    make db-pgsql    # configura PostgreSQL no .env (host depende do runner)"
echo "    make db-up-mysql # sobe somente MySQL no Docker para PHP local"
echo "    make db-up-pg    # sobe somente PostgreSQL no Docker para PHP local"
echo "    make db-down     # para bancos Docker"
echo "    make fresh       # migrate + seed"
echo "    make migrate     # roda migrations"
echo "    make seed        # roda seeders"
echo
echo "  Testes e qualidade (precisa de vendor/):"
echo "    make deps        # composer install"
echo "    make local-test  # PHPUnit no host"
echo "    make test        # PHPUnit"
echo "    make analyse     # PHPStan"
echo "    make lint"
echo "    make format      # PHP CS Fixer (altera arquivos)"
echo "    make quality     # lint + format-check + analyse + test"
