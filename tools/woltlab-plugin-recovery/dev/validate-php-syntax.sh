#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
FAIL=0
PHP_BIN="${PHP_BIN:-}"

if [[ -z "$PHP_BIN" ]]; then
  for candidate in php php83 php8.3 php82 php8.2 php81 php8.1; do
    if command -v "$candidate" >/dev/null 2>&1; then
      PHP_BIN="$candidate"
      break
    fi
  done
fi

if [[ -z "$PHP_BIN" ]]; then
  echo "WARN: php nicht im PATH — Syntax-Check übersprungen (PHP_BIN setzen)."
  exit 0
fi

check_dir() {
  local dir="$1"
  while IFS= read -r -d '' f; do
    if ! "$PHP_BIN" -l "$f" >/dev/null 2>&1; then
      echo "SYNTAX ERROR: $f"
      "$PHP_BIN" -l "$f" || true
      FAIL=1
    fi
  done < <(find "$dir" -name '*.php' -print0)
}

echo "PHP syntax: stub/"
check_dir "$ROOT/stub"

echo "PHP syntax: recovery-src/"
check_dir "$ROOT/recovery-src"

if [[ -f "$ROOT/dist/plugin-recovery-tool.php" ]]; then
  echo "PHP syntax: dist/plugin-recovery-tool.php"
  "$PHP_BIN" -l "$ROOT/dist/plugin-recovery-tool.php"
fi

if [[ $FAIL -ne 0 ]]; then
  echo "FAILED"
  exit 1
fi

echo "All PHP files OK"
