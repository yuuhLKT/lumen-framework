#!/usr/bin/env sh

set -eu

env_file=${1:-.env}
compose=${2:-docker compose}

choose() {
    printf '\n%s\n' 'Escolha como subir o projeto:' >&2
    printf '%s\n' 'Docker:' >&2
    printf '%s\n' '  1) PHP Docker + JSON' >&2
    printf '%s\n' '  2) PHP Docker + SQLite' >&2
    printf '%s\n' '  3) PHP Docker + MySQL container' >&2
    printf '%s\n' '  4) PHP Docker + PostgreSQL container' >&2
    printf '%s\n' 'Local:' >&2
    printf '%s\n' '  5) PHP local + JSON' >&2
    printf '%s\n' '  6) PHP local + SQLite' >&2
    printf '%s\n' '  7) PHP local + MySQL container' >&2
    printf '%s\n' '  8) PHP local + PostgreSQL container' >&2
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

php_command() {
    if command -v php >/dev/null 2>&1; then
        command -v php
        return 0
    fi

    if command -v php.exe >/dev/null 2>&1; then
        command -v php.exe
        return 0
    fi

    old_ifs=$IFS
    IFS=:

    for dir in $PATH; do
        if [ -x "$dir/php.bat" ]; then
            if command -v wslpath >/dev/null 2>&1; then
                bat_target=$(sed -n 's/^"\([^"]*php\.exe\)".*/\1/p' "$dir/php.bat" | head -n 1)

                if [ -n "$bat_target" ]; then
                    wslpath -u "$bat_target"
                    IFS=$old_ifs
                    return 0
                fi
            fi

            printf '%s\n' "$dir/php.bat"
            IFS=$old_ifs
            return 0
        fi
    done

    IFS=$old_ifs
    return 1
}

run_php() {
    php_bin=$(php_command) || {
        printf '%s\n' 'PHP nao encontrado no PATH. Escolha uma opcao Docker (1-4) ou instale PHP local.' >&2
        exit 1
    }

    case "$php_bin" in
        *.bat)
            if ! command -v cmd.exe >/dev/null 2>&1 || ! command -v wslpath >/dev/null 2>&1; then
                printf '%s\n' 'PHP encontrado como .bat, mas cmd.exe/wslpath nao estao disponiveis.' >&2
                exit 1
            fi

            win_php=$(wslpath -w "$php_bin")
            cmd.exe /C ""$win_php" $*"
            ;;
        *)
            "$php_bin" "$@"
            ;;
    esac
}

php_port() {
    if [ ! -f "$env_file" ]; then
        printf '%s' '8000'
        return 0
    fi

    awk -F= '/^[[:space:]]*PHP_PORT[[:space:]]*=/ { gsub(/"/, "", $2); print $2; found=1; exit } END { if (!found) print "8000" }' "$env_file"
}

run_local() {
    port=$(php_port)
    printf '\n%s\n' 'Modo local: iniciando PHP embutido.'
    printf 'App local em: http://localhost:%s\n\n' "$port"
    run_php -S "0.0.0.0:${port}" -t public
}

choice=$(choose)
profile=''
services='php'

case "$choice" in
    1)
        sh tools/env.sh --init "$env_file" .env.docker.example >/dev/null
        sh tools/env.sh "$env_file" DB_CONNECTION json DB_JSON_PATH storage/database.json >/dev/null
        printf '\n%s\n' 'Selecionado: PHP Docker + JSON'
        ;;
    2)
        sh tools/env.sh --init "$env_file" .env.docker.example >/dev/null
        sh tools/env.sh "$env_file" DB_CONNECTION sqlite DB_SQLITE_PATH storage/database.sqlite >/dev/null
        printf '\n%s\n' 'Selecionado: PHP Docker + SQLite'
        ;;
    3)
        sh tools/env.sh --init "$env_file" .env.docker.example >/dev/null
        sh tools/env.sh "$env_file" DB_CONNECTION mysql DB_MYSQL_HOST mysql DB_MYSQL_PORT 3306 DB_MYSQL_DATABASE base DB_MYSQL_USERNAME base DB_MYSQL_PASSWORD base DB_MYSQL_CHARSET utf8mb4 DB_MYSQL_ROOT_PASSWORD root >/dev/null
        profile='--profile mysql'
        services='php mysql'
        printf '\n%s\n' 'Selecionado: PHP Docker + MySQL container'
        ;;
    4)
        sh tools/env.sh --init "$env_file" .env.docker.example >/dev/null
        sh tools/env.sh "$env_file" DB_CONNECTION pgsql DB_PGSQL_HOST postgres DB_PGSQL_PORT 5432 DB_PGSQL_DATABASE base DB_PGSQL_USERNAME base DB_PGSQL_PASSWORD base >/dev/null
        profile='--profile postgres'
        services='php postgres'
        printf '\n%s\n' 'Selecionado: PHP Docker + PostgreSQL container'
        ;;
    5)
        sh tools/env.sh --init "$env_file" .env.example >/dev/null
        sh tools/env.sh "$env_file" DB_CONNECTION json DB_JSON_PATH storage/database.json >/dev/null
        printf '\n%s\n' 'Selecionado: PHP local + JSON'
        run_local
        exit $?
        ;;
    6)
        sh tools/env.sh --init "$env_file" .env.example >/dev/null
        sh tools/env.sh "$env_file" DB_CONNECTION sqlite DB_SQLITE_PATH storage/database.sqlite >/dev/null
        printf '\n%s\n' 'Selecionado: PHP local + SQLite'
        run_local
        exit $?
        ;;
    7)
        sh tools/env.sh --init "$env_file" .env.example >/dev/null
        sh tools/env.sh "$env_file" DB_CONNECTION mysql DB_MYSQL_HOST 127.0.0.1 DB_MYSQL_PORT 3306 DB_MYSQL_DATABASE base DB_MYSQL_USERNAME base DB_MYSQL_PASSWORD base DB_MYSQL_CHARSET utf8mb4 DB_MYSQL_ROOT_PASSWORD root >/dev/null
        printf '\n%s\n' 'Selecionado: PHP local + MySQL container'
        # shellcheck disable=SC2086
        $compose --profile mysql up -d mysql
        run_local
        exit $?
        ;;
    8)
        sh tools/env.sh --init "$env_file" .env.example >/dev/null
        sh tools/env.sh "$env_file" DB_CONNECTION pgsql DB_PGSQL_HOST 127.0.0.1 DB_PGSQL_PORT 5432 DB_PGSQL_DATABASE base DB_PGSQL_USERNAME base DB_PGSQL_PASSWORD base >/dev/null
        printf '\n%s\n' 'Selecionado: PHP local + PostgreSQL container'
        # shellcheck disable=SC2086
        $compose --profile postgres up -d postgres
        run_local
        exit $?
        ;;
esac

printf 'Subindo containers...\n'

# shellcheck disable=SC2086
$compose $profile up --build $services
