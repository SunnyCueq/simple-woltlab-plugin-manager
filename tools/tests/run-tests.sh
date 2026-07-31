#!/usr/bin/env bash
# Run SWPM toolkit unit/smoke tests (no external test frameworks required).
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
TOOLS_DIR="$(cd "$SCRIPT_DIR/.." && pwd)"
fail=0

echo "== bash: common helpers =="
if ! bash "$SCRIPT_DIR/test_common_helpers.sh"; then
    fail=1
fi

echo "== python: package PIP archives =="
if ! python3 "$SCRIPT_DIR/test_check_package_pip_archives.py"; then
    fail=1
fi

echo "== python: empty pack dirs =="
if ! python3 "$SCRIPT_DIR/test_check_empty_pack_dirs.py"; then
    fail=1
fi

echo "== python: language PIP keys =="
if ! python3 "$SCRIPT_DIR/test_check_language_pip_keys.py"; then
    fail=1
fi

if (( fail )); then
    echo "tools/tests: FAILED"
    exit 1
fi
echo "tools/tests: OK"
exit 0
