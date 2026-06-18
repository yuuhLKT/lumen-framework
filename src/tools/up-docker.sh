#!/usr/bin/env sh

set -eu

env_file=${1:-.env}
compose=${2:-docker compose}

# Determina profile a partir do DB_CONNECTION do .env.
db_connection='json'

if [ -f "$env_file" ]; then
    db_connection=$(awk -F= '/^[[:space:]]*DB_CONNECTION[[:space:]]*=/ { gsub(/"/, "", $2); print $2; found=1; exit } END { if (!found) print "json" }' "$env_file")
fi

profile=''
services='php'

case "$db_connection" in
    mysql)
        profile='--profile mysql'
        services='php mysql'
        ;;
    pgsql|postgres)
        profile='--profile postgres'
        services='php postgres'
        ;;
esac

printf 'Subindo containers (banco: %s)...\n' "$db_connection"

# shellcheck disable=SC2086
$compose $profile up --build $services
