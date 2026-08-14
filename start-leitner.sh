#!/usr/bin/env bash

# Launch the Leitner Laravel app on its own local port and open it in the default browser.
set -Eeuo pipefail

SCRIPT_PATH="$(readlink -f -- "${BASH_SOURCE[0]}")"
APP_DIRECTORY="$(cd -- "$(dirname -- "$SCRIPT_PATH")" && pwd)"
HOST="127.0.0.1"
PORT="${LEITNER_PORT:-8137}"
URL="http://${HOST}:${PORT}"
PID_FILE="${APP_DIRECTORY}/storage/framework/leitner-dev-server.pid"
LOG_FILE="${APP_DIRECTORY}/storage/logs/leitner-dev-server.log"

open_browser() {
    if [[ "${LEITNER_NO_BROWSER:-0}" == "1" ]]; then
        printf 'Server is ready at %s.\n' "$URL"
        return
    fi

    if command -v xdg-open >/dev/null 2>&1; then
        xdg-open "$URL" >/dev/null 2>&1 &
    else
        printf 'Server is ready. Open %s in your browser.\n' "$URL"
    fi
}

port_is_in_use() {
    php -r '
        $socket = @fsockopen("127.0.0.1", (int) $argv[1], $errorNumber, $errorMessage, 0.2);
        if ($socket) {
            fclose($socket);
            exit(0);
        }
        exit(1);
    ' "$PORT"
}

if ! command -v php >/dev/null 2>&1; then
    printf 'PHP is required but was not found in PATH.\n' >&2
    exit 1
fi

if [[ ! -f "${APP_DIRECTORY}/artisan" ]]; then
    printf 'Laravel artisan file was not found in %s.\n' "$APP_DIRECTORY" >&2
    exit 1
fi

mkdir -p "$(dirname "$PID_FILE")" "$(dirname "$LOG_FILE")"

if [[ -f "$PID_FILE" ]]; then
    read -r existing_pid < "$PID_FILE" || true

    if [[ "$existing_pid" =~ ^[0-9]+$ ]] && kill -0 "$existing_pid" 2>/dev/null; then
        existing_command="$(ps -p "$existing_pid" -o args= 2>/dev/null || true)"
        if [[ "$existing_command" == *"artisan serve"* ]]; then
            printf 'Leitner is already running at %s. Opening it in your browser...\n' "$URL"
            open_browser
            exit 0
        fi
    fi

    rm -f "$PID_FILE"
fi

if port_is_in_use; then
    printf 'Port %s is already in use. Leitner was not started to avoid opening another application.\n' "$PORT" >&2
    printf 'Stop the process using that port, or run with another port: LEITNER_PORT=8138 %s\n' "$0" >&2
    exit 1
fi

cd "$APP_DIRECTORY"
nohup php artisan serve --host="$HOST" --port="$PORT" >"$LOG_FILE" 2>&1 &
server_pid=$!
printf '%s\n' "$server_pid" > "$PID_FILE"

for _ in {1..50}; do
    if port_is_in_use; then
        printf 'Leitner is running at %s. Opening it in your browser...\n' "$URL"
        open_browser
        exit 0
    fi

    if ! kill -0 "$server_pid" 2>/dev/null; then
        rm -f "$PID_FILE"
        printf 'Leitner could not start. Check the server log: %s\n' "$LOG_FILE" >&2
        exit 1
    fi

    sleep 0.2
done

rm -f "$PID_FILE"
printf 'Leitner did not become ready in time. Check the server log: %s\n' "$LOG_FILE" >&2
exit 1
