#!/usr/bin/env bash

set -e

echo "======================================================="
echo
echo "Dang khoi dong Mercure Hub tai http://localhost:9999..."
echo
echo "======================================================="

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
MERCURE_DIR="$SCRIPT_DIR/mercure/linux"
MERCURE_BIN="$MERCURE_DIR/mercure"
MERCURE_CONFIG="$MERCURE_DIR/dev.Caddyfile"

export MERCURE_PUBLISHER_JWT_KEY="071c26246473e3a27780cfddf6123f5df4116fb8fc6f1456e04fabbeb8dd193a"
export MERCURE_SUBSCRIBER_JWT_KEY="071c26246473e3a27780cfddf6123f5df4116fb8fc6f1456e04fabbeb8dd193a"
export SERVER_NAME=":9999"
export CORS_ALLOWED_ORIGINS="*"

cleanup_old_mercure() {
    local pids

    pids="$(pgrep -f "$MERCURE_BIN.*dev.Caddyfile|mercure run --config dev.Caddyfile" || true)"
    pids="$(printf '%s\n' "$pids" | awk 'NF && $1 != '"$$"'')"

    if [ -z "$pids" ]; then
        return
    fi

    echo "Phat hien Mercure cu dang chay, tien hanh dung de tranh lock mercure.db..."
    echo "PID se dung: $(echo "$pids" | xargs)"

    kill $pids 2>/dev/null || true
    sleep 1

    pids="$(ps -o pid= -p $(echo "$pids" | xargs) 2>/dev/null | awk 'NF')"
    if [ -n "$pids" ]; then
        echo "Mercure cu chua dung han, force kill..."
        kill -9 $pids 2>/dev/null || true
        sleep 1
    fi
}

if [ ! -x "$MERCURE_BIN" ]; then
    echo "Khong tim thay Mercure binary cho Linux tai: $MERCURE_BIN"
    echo
    echo "Hay tai ban Mercure cho Linux, dat file executable vao thu muc:"
    echo "$MERCURE_DIR"
    echo
    echo "Sau do cap quyen chay:"
    echo "chmod +x $MERCURE_BIN"
    exit 1
fi

cd "$MERCURE_DIR"
cleanup_old_mercure

if ./mercure --help 2>&1 | grep -q " run "; then
    if [ ! -f "$MERCURE_CONFIG" ]; then
        echo "Khong tim thay file cau hinh: $MERCURE_CONFIG"
        echo "Ban co the copy tu mercure/win/dev.Caddyfile sang mercure/linux/dev.Caddyfile neu cau hinh phu hop."
        exit 1
    fi

    ./mercure run --config dev.Caddyfile
else
    echo "Phat hien Mercure legacy binary, chay bang flags thay cho Caddyfile..."
    ./mercure \
        --addr ":9999" \
        --publisher-jwt-key "$MERCURE_PUBLISHER_JWT_KEY" \
        --subscriber-jwt-key "$MERCURE_SUBSCRIBER_JWT_KEY" \
        --cors-allowed-origins "*" \
        --publish-allowed-origins "*" \
        --demo \
        --allow-anonymous \
        --subscriptions
fi
