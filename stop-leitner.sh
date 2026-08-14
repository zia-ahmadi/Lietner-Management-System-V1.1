#!/usr/bin/env bash

# Stop the Laravel development server started by start-leitner.
set -Eeuo pipefail

SCRIPT_PATH="$(readlink -f -- "${BASH_SOURCE[0]}")"
APP_DIRECTORY="$(cd -- "$(dirname -- "$SCRIPT_PATH")" && pwd)"
PID_FILE="${APP_DIRECTORY}/storage/framework/leitner-dev-server.pid"

if [[ ! -f "$PID_FILE" ]]; then
    printf 'Leitner is not running.\n'
    exit 0
fi

read -r server_pid < "$PID_FILE" || true

if [[ ! "$server_pid" =~ ^[0-9]+$ ]] || ! kill -0 "$server_pid" 2>/dev/null; then
    rm -f "$PID_FILE"
    printf 'Leitner is not running. Removed its stale PID file.\n'
    exit 0
fi

server_command="$(ps -p "$server_pid" -o args= 2>/dev/null || true)"
if [[ "$server_command" != *"artisan serve"* ]]; then
    printf 'The saved process is not a Laravel development server, so it was not stopped.\n' >&2
    exit 1
fi

kill "$server_pid"

for _ in {1..25}; do
    if ! kill -0 "$server_pid" 2>/dev/null; then
        rm -f "$PID_FILE"
        printf 'Leitner development server stopped.\n'
        exit 0
    fi

    sleep 0.2
done

printf 'Leitner did not stop in time. Its PID is %s.\n' "$server_pid" >&2
exit 1
