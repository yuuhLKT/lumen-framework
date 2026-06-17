#!/usr/bin/env sh

set -eu

env_file=${1:-.env}
compose=${2:-docker compose}
profile_flag=${3:-}

mysql_host=mysql
pg_host=postgres

select_choice() {
    if [ "$profile_flag" = '--profile=mysql' ]; then
        printf '%s' 3
        return 0
    fi

    if [ "$profile_flag" = '--profile=pgsql' ] || [ "$profile_flag" = '--profile=postgres' ]; then
        printf '%s' 4
        return 0
    fi

    if [ ! -t 0 ]; then
        printf '%s\n' 'STDIN nao e um terminal. Usando banco padrao: JSON local (em container).' >&2
        printf '%s' 1
        return 0
    fi

    printf '\n%s\n' 'Escolha o banco para subir (runner: docker):'
    printf '%s\n' '  1) JSON local (em container)'
    printf '%s\n' '  2) SQLite local (em container)'
    printf '%s\n' '  3) MySQL (container docker)'
    printf '%s\n' '  4) PostgreSQL (container docker)'
    printf '\n%s' 'Opcao [1]: '

    read selected
    selected=${selected:-1}

    case "$selected" in
        1|2|3|4)
            printf '%s' "$selected"
            ;;
        *)
            printf '%s\n' 'Opcao invalida.' >&2
            exit 1
            ;;
    esac
}

choice=$(select_choice)
profile=''
services='php'

sh tools/env.sh --init "$env_file" .env.docker.example >/dev/null

case "$choice" in
    1)
        label='JSON local (em container)'
        sh tools/env.sh "$env_file" DB_CONNECTION json DB_JSON_PATH storage/database.json >/dev/null
        ;;
    2)
        label='SQLite local (em container)'
        sh tools/env.sh "$env_file" DB_CONNECTION sqlite DB_SQLITE_PATH storage/database.sqlite >/dev/null
        ;;
    3)
        label='MySQL (container docker)'
        profile='--profile mysql'
        services='php mysql'
        sh tools/env.sh "$env_file" DB_CONNECTION mysql DB_MYSQL_HOST "$mysql_host" DB_MYSQL_PORT 3306 DB_MYSQL_DATABASE base DB_MYSQL_USERNAME base DB_MYSQL_PASSWORD base DB_MYSQL_CHARSET utf8mb4 DB_MYSQL_ROOT_PASSWORD root >/dev/null
        ;;
    4)
        label='PostgreSQL (container docker)'
        profile='--profile postgres'
        services='php postgres'
        sh tools/env.sh "$env_file" DB_CONNECTION pgsql DB_PGSQL_HOST "$pg_host" DB_PGSQL_PORT 5432 DB_PGSQL_DATABASE base DB_PGSQL_USERNAME base DB_PGSQL_PASSWORD base >/dev/null
        ;;
esac

php_port=$(awk -F= '/^[[:space:]]*PHP_PORT[[:space:]]*=/ { gsub(/"/, "", $2); print $2; found=1; exit } END { if (!found) print "8000" }' "$env_file")

printf '\nBanco selecionado: %s\n' "$label"
printf 'Arquivo %s atualizado.\n' "$env_file"
printf 'Ngrok local vai apontar para http://localhost:%s\n\n' "$php_port"

ngrok_pid=''

if command -v ngrok >/dev/null 2>&1; then
    ngrok http "http://localhost:${php_port}" &
    ngrok_pid=$!
else
    printf 'Ngrok nao encontrado no PATH. Rode manualmente: ngrok http http://localhost:%s\n' "$php_port" >&2
fi

cleanup() {
    if [ -n "$ngrok_pid" ]; then
        kill "$ngrok_pid" >/dev/null 2>&1 || true
    fi
}

trap cleanup EXIT INT TERM

# shellcheck disable=SC2086
$compose $profile up --build $services
