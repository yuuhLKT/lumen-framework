#!/usr/bin/env sh

set -eu

env_file=${1:-.env}
compose=${2:-docker compose}
profile_flag=${3:-}

select_choice() {
    if [ "$profile_flag" = '--profile=mysql' ]; then
        printf '%s' d3
        return 0
    fi

    if [ "$profile_flag" = '--profile=pgsql' ] || [ "$profile_flag" = '--profile=postgres' ]; then
        printf '%s' d4
        return 0
    fi

    printf '\n%s\n' 'Escolha o ambiente/banco para subir:' >&2
    printf '%s\n' 'Docker:' >&2
    printf '%s\n' '  1) JSON local (em container)' >&2
    printf '%s\n' '  2) SQLite local (em container)' >&2
    printf '%s\n' '  3) MySQL (container docker)' >&2
    printf '%s\n' '  4) PostgreSQL (container docker)' >&2
    printf '%s\n' 'PHP local (sem Docker):' >&2
    printf '%s\n' '  5) JSON local (arquivo)' >&2
    printf '%s\n' '  6) SQLite local (arquivo)' >&2
    printf '%s\n' '  7) MySQL (servidor na maquina)' >&2
    printf '%s\n' '  8) PostgreSQL (servidor na maquina)' >&2
    printf '\n%s' 'Opcao [1]: ' >&2

    read selected
    selected=${selected:-1}

    case "$selected" in
        1|2|3|4|5|6|7|8)
            printf '%s' "$selected"
            ;;
        *)
            printf '%s\n' 'Opcao invalida.' >&2
            exit 1
            ;;
    esac
}

choice=$(select_choice)
mode=docker
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
        sh tools/env.sh "$env_file" DB_CONNECTION mysql DB_MYSQL_HOST mysql DB_MYSQL_PORT 3306 DB_MYSQL_DATABASE base DB_MYSQL_USERNAME base DB_MYSQL_PASSWORD base DB_MYSQL_CHARSET utf8mb4 DB_MYSQL_ROOT_PASSWORD root >/dev/null
        ;;
    4)
        label='PostgreSQL (container docker)'
        profile='--profile postgres'
        services='php postgres'
        sh tools/env.sh "$env_file" DB_CONNECTION pgsql DB_PGSQL_HOST postgres DB_PGSQL_PORT 5432 DB_PGSQL_DATABASE base DB_PGSQL_USERNAME base DB_PGSQL_PASSWORD base >/dev/null
        ;;
    5)
        label='JSON local (arquivo)'
        mode=local
        sh tools/env.sh --init "$env_file" .env.example >/dev/null
        sh tools/env.sh "$env_file" DB_CONNECTION json DB_JSON_PATH storage/database.json >/dev/null
        ;;
    6)
        label='SQLite local (arquivo)'
        mode=local
        sh tools/env.sh --init "$env_file" .env.example >/dev/null
        sh tools/env.sh "$env_file" DB_CONNECTION sqlite DB_SQLITE_PATH storage/database.sqlite >/dev/null
        ;;
    7)
        label='MySQL (servidor na maquina)'
        mode=local
        sh tools/env.sh --init "$env_file" .env.example >/dev/null
        sh tools/env.sh "$env_file" DB_CONNECTION mysql DB_MYSQL_HOST 127.0.0.1 DB_MYSQL_PORT 3306 DB_MYSQL_DATABASE base DB_MYSQL_USERNAME base DB_MYSQL_PASSWORD base DB_MYSQL_CHARSET utf8mb4 DB_MYSQL_ROOT_PASSWORD root >/dev/null
        ;;
    8)
        label='PostgreSQL (servidor na maquina)'
        mode=local
        sh tools/env.sh --init "$env_file" .env.example >/dev/null
        sh tools/env.sh "$env_file" DB_CONNECTION pgsql DB_PGSQL_HOST 127.0.0.1 DB_PGSQL_PORT 5432 DB_PGSQL_DATABASE base DB_PGSQL_USERNAME base DB_PGSQL_PASSWORD base >/dev/null
        ;;
esac

php_port=$(awk -F= '/^[[:space:]]*PHP_PORT[[:space:]]*=/ { gsub(/"/, "", $2); print $2; found=1; exit } END { if (!found) print "8000" }' "$env_file")

printf '\nBanco selecionado: %s\n' "$label"
printf 'Arquivo %s atualizado.\n' "$env_file"

if [ "$mode" = 'local' ]; then
    printf '\nModo local: iniciando PHP embutido.\n'
    printf 'App local em: http://localhost:%s\n\n' "$php_port"
    php -S "0.0.0.0:${php_port}" -t public
    exit $?
fi

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
