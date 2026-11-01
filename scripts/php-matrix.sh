#!/bin/bash
# Lance une suite sur plusieurs versions de PHP et journalise les résultats.
# Usage: scripts/php-matrix.sh <manifest> <htdocs> <report-dir> [subset]
set -u
manifest="$1"
root="$2"
reportdir="$3"
subset="${4:-}"
for v in 7.4 8.1 8.2 8.3 8.4; do
    bin="php$v"
    if ! command -v "$bin" >/dev/null 2>&1; then
        echo "SKIP php$v (absent)"
        continue
    fi
    echo "=== php$v"
    args=(bin/mmi-test "--manifest=$manifest" "--dolibarr-root=$root" "--report-dir=$reportdir")
    if [ -n "$subset" ]; then
        args+=("--subset=$subset")
    fi
    "$bin" "${args[@]}"
    rc=$?
    echo "--- php$v exit=$rc"
done
