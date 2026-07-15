#!/usr/bin/env bash
# Optional: lint SWPM manager Python (tools/*.py) with ruff.
# Skips cleanly if ruff is not installed (exit 0) unless --require.
#
# Usage:
#   ./tools/lint-manager-python.sh [--fix] [--require]

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
FIX=0
REQUIRE=0

while [ $# -gt 0 ]; do
    case "$1" in
        --fix) FIX=1; shift ;;
        --require) REQUIRE=1; shift ;;
        -h|--help)
            sed -n '2,10p' "$0"
            exit 0
            ;;
        *)
            echo "Unbekanntes Argument: $1" >&2
            exit 2
            ;;
    esac
done

if ! command -v ruff &>/dev/null; then
    if [ "$REQUIRE" -eq 1 ]; then
        echo "lint-manager-python: ruff nicht installiert (pipx install ruff / pip install ruff)" >&2
        exit 1
    fi
    echo "lint-manager-python: ruff nicht installiert — übersprungen"
    exit 0
fi

cd "$SCRIPT_DIR"
mapfile -t PY < <(find . -maxdepth 1 -name 'check-*.py' -o -name 'swpm-*.py' | sort)
# also include other tools python at top level
mapfile -t MORE < <(find . -maxdepth 1 -name '*.py' | sort)
# unique
PY=($(printf '%s\n' "${PY[@]}" "${MORE[@]}" | sort -u))

if [ ${#PY[@]} -eq 0 ]; then
    echo "lint-manager-python: keine .py gefunden"
    exit 0
fi

echo "lint-manager-python: ruff check (${#PY[@]} Dateien)"
if [ "$FIX" -eq 1 ]; then
    ruff check --fix "${PY[@]}"
    ruff format "${PY[@]}"
else
    ruff check "${PY[@]}"
fi
