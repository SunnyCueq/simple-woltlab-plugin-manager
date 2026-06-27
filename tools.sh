#!/usr/bin/env bash
# WoltLab Plugin Manager – Developer Tools (Einstieg)
#
#   ./tools.sh              → interaktives Menü
#   ./tools.sh help         → alle Befehle
#   ./tools.sh build        → Paket bauen (Patch)
#   ./tools.sh update-paket → Update-Paket (= Patch)
#   ./tools.sh typescript   → TS → JS
#
# Implementierung: tools/tools.sh

set -e
MAIN_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
exec "$MAIN_DIR/tools/tools.sh" "$@"
