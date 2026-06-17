#!/usr/bin/env sh

set -eu

usage() {
    printf '%s\n' 'Usage:'
    printf '%s\n' '  sh tools/env.sh --init [.env] [template]'
    printf '%s\n' '  sh tools/env.sh [.env] KEY VALUE [KEY VALUE ...]'
}

init_env() {
    path=$1
    template=${2:-}

    if [ -f "$path" ]; then
        return 0
    fi

    if [ -z "$template" ]; then
        if [ -f .env.docker.example ]; then
            template=.env.docker.example
        else
            template=.env.example
        fi
    fi

    if [ -f "$template" ]; then
        cp "$template" "$path"
    else
        : > "$path"
    fi
}

format_env_value() {
    value=$1

    case "$value" in
        *[\ \#=]*)
            escaped=$(printf '%s' "$value" | sed 's/"/\\"/g')
            printf '"%s"' "$escaped"
            ;;
        *)
            printf '%s' "$value"
            ;;
    esac
}

set_env_value() {
    path=$1
    key=$2
    value=$3

    case "$key" in
        ''|[!A-Z_]*|*[!A-Z0-9_]*)
            printf 'Chave invalida: %s\n' "$key" >&2
            exit 1
            ;;
    esac

    formatted=$(format_env_value "$value")
    tmp="${path}.tmp.$$"

    if grep -Eq "^[[:space:]]*${key}[[:space:]]*=" "$path"; then
        awk -v key="$key" -v line="${key}=${formatted}" '
            $0 ~ "^[[:space:]]*" key "[[:space:]]*=" { print line; next }
            { print }
        ' "$path" > "$tmp"
    else
        cat "$path" > "$tmp"
        printf '%s=%s\n' "$key" "$formatted" >> "$tmp"
    fi

    mv "$tmp" "$path"
    printf '%s=%s\n' "$key" "$value"
}

if [ "$#" -eq 0 ] || [ "${1:-}" = '-h' ] || [ "${1:-}" = '--help' ]; then
    usage
    exit 0
fi

if [ "$1" = '--init' ]; then
    init_env "${2:-.env}" "${3:-}"
    printf 'Arquivo %s pronto.\n' "${2:-.env}"
    exit 0
fi

path=$1
shift

if [ "$#" -lt 2 ] || [ $(( $# % 2 )) -ne 0 ]; then
    printf '%s\n' 'Informe pares KEY VALUE.' >&2
    exit 1
fi

init_env "$path"

while [ "$#" -gt 0 ]; do
    set_env_value "$path" "$1" "$2"
    shift 2
done
