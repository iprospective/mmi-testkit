#!/bin/bash
# Lance l'interface web du TestKit (serveur intégré PHP).
# Usage: scripts/serve-web.sh [port]
set -e
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"
if [ -f .env ]; then
    set -a
    . ./.env
    set +a
fi
export MMI_TESTKIT_REPORT_DIR="${MMI_TESTKIT_REPORT_DIR:-$ROOT/reports}"
HOST="${HOST:-127.0.0.1}"
PORT="${1:-${PORT:-8099}}"
PHP_BIN="${PHP_BIN:-php}"
exec "$PHP_BIN" -S "$HOST:$PORT" -t public public/router.php
