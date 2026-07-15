#!/usr/bin/env bash
# Optional PHPStan for a plugin that ships its own config.
#
# Runs only when phpstan.neon or phpstan.neon.dist exists under the plugin
# (or temp_edit/). Does not invent WCF stubs — configure those in the plugin.
#
# Usage:
#   ./tools/run-phpstan.sh [--require-bin] PLUGIN_DIR
# Exit: 0 ok/skip, 1 analysis failed / missing bin with --require-bin, 2 usage

set -euo pipefail

REQUIRE_BIN=0
PLUGIN_DIR=""

while [ $# -gt 0 ]; do
    case "$1" in
        --require-bin) REQUIRE_BIN=1; shift ;;
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
    echo "Usage: $0 [--require-bin] PLUGIN_DIR" >&2
    exit 2
fi

PLUGIN_DIR="$(cd "$PLUGIN_DIR" && pwd)"

CONFIG=""
for cand in \
    "$PLUGIN_DIR/phpstan.neon" \
    "$PLUGIN_DIR/phpstan.neon.dist" \
    "$PLUGIN_DIR/temp_edit/phpstan.neon" \
    "$PLUGIN_DIR/temp_edit/phpstan.neon.dist"
do
    if [ -f "$cand" ]; then
        CONFIG="$cand"
        break
    fi
done

if [ -z "$CONFIG" ]; then
    echo "run-phpstan: keine phpstan.neon(.dist) — übersprungen"
    exit 0
fi

PHPSTAN_BIN=""
if command -v phpstan &>/dev/null; then
    PHPSTAN_BIN="phpstan"
elif [ -x "$PLUGIN_DIR/vendor/bin/phpstan" ]; then
    PHPSTAN_BIN="$PLUGIN_DIR/vendor/bin/phpstan"
elif [ -x "$(dirname "$CONFIG")/vendor/bin/phpstan" ]; then
    PHPSTAN_BIN="$(dirname "$CONFIG")/vendor/bin/phpstan"
fi

if [ -z "$PHPSTAN_BIN" ]; then
    msg="run-phpstan: Config gefunden ($CONFIG), aber phpstan Binary fehlt"
    if [ "$REQUIRE_BIN" -eq 1 ]; then
        echo "$msg" >&2
        exit 1
    fi
    echo "$msg — übersprungen (composer require --dev phpstan/phpstan)"
    exit 0
fi

ROOT="$(dirname "$CONFIG")"
echo "run-phpstan: $PHPSTAN_BIN analyse -c $CONFIG"
cd "$ROOT"
"$PHPSTAN_BIN" analyse -c "$CONFIG" --no-progress --error-format=raw
