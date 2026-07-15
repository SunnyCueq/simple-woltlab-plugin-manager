#!/usr/bin/env bash
# Compile / typecheck TypeScript when the plugin has TS sources.
#
# Triggers when any of:
#   - temp_edit/tsconfig.json or tsconfig.json
#   - *.ts under temp_edit/ts/ (or ts/ next to tsconfig)
#
# Usage:
#   ./tools/check-typescript.sh [--no-emit] PLUGIN_DIR
# Exit: 0 ok/skip, 1 tsc failed / .ts without tsconfig, 2 usage

set -euo pipefail

NO_EMIT=0
PLUGIN_DIR=""

while [ $# -gt 0 ]; do
    case "$1" in
        --no-emit) NO_EMIT=1; shift ;;
        -h|--help)
            sed -n '2,14p' "$0"
            exit 0
            ;;
        -*)
            echo "Unbekanntes Flag: $1" >&2
            exit 2
            ;;
        *)
            PLUGIN_DIR="$1"
            shift
            ;;
    esac
done

if [ -z "$PLUGIN_DIR" ]; then
    echo "Usage: $0 [--no-emit] PLUGIN_DIR" >&2
    exit 2
fi

PLUGIN_DIR="$(cd "$PLUGIN_DIR" && pwd)"

ROOT="$PLUGIN_DIR"
if [ -d "$PLUGIN_DIR/temp_edit" ]; then
    ROOT="$PLUGIN_DIR/temp_edit"
fi

TSCONFIG=""
for cand in "$ROOT/tsconfig.json" "$PLUGIN_DIR/tsconfig.json"; do
    if [ -f "$cand" ]; then
        TSCONFIG="$cand"
        break
    fi
done

TS_COUNT=0
if [ -d "$ROOT/ts" ]; then
    TS_COUNT=$(find "$ROOT/ts" -name '*.ts' -type f 2>/dev/null | wc -l)
fi

if [ -z "$TSCONFIG" ] && [ "$TS_COUNT" -eq 0 ]; then
    echo "check-typescript: kein TypeScript — übersprungen"
    exit 0
fi

if [ "$TS_COUNT" -gt 0 ] && [ -z "$TSCONFIG" ]; then
    echo "check-typescript: .ts in ts/ gefunden, aber keine tsconfig.json" >&2
    exit 1
fi

cd "$(dirname "$TSCONFIG")"

run_tsc() {
    if command -v npx &>/dev/null; then
        npx --yes tsc "$@"
    elif [ -f "node_modules/typescript/bin/tsc" ]; then
        node node_modules/typescript/bin/tsc "$@"
    elif command -v tsc &>/dev/null; then
        tsc "$@"
    else
        echo "check-typescript: tsc/npx nicht gefunden" >&2
        exit 1
    fi
}

echo "check-typescript: tsc ($TSCONFIG)"
if [ "$NO_EMIT" -eq 1 ]; then
    run_tsc --noEmit
else
    run_tsc
fi
