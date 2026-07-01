#!/usr/bin/env bash
# Prüft, ob Plugin-/App-Dateien im Docker-Container dem Webserver-User gehören.
# Exit 1 = ACP-Update würde mit „Permission denied“ scheitern.
#
# Usage:
#   ./tools/check-woltlab-app-permissions.sh
#   ./tools/check-woltlab-app-permissions.sh /var/www/html/shrinkr/lib/page/Foo.class.php

set -euo pipefail

readonly SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
readonly CONTAINER="${WOLTLAB_DOCKER_CONTAINER:-woltlab-web}"
readonly WEB_USER="${WOLTLAB_WEB_USER:-www-data}"

if [ -f "$SCRIPT_DIR/common.sh" ]; then
    # shellcheck source=common.sh
    source "$SCRIPT_DIR/common.sh"
else
    print_error() { echo "✗ $1" >&2; }
    print_success() { echo "✓ $1"; }
fi

if ! docker ps --format '{{.Names}}' | grep -qx "$CONTAINER"; then
    print_error "Container '$CONTAINER' läuft nicht."
    exit 1
fi

SCAN_ROOTS=(/var/www/html/shrinkr /var/www/html/js/Shrinkr /var/www/html/lib/bootstrap/de.sunnyc.wsc.shrinkr.php)
if [ $# -gt 0 ]; then
    SCAN_ROOTS=("$@")
fi

BAD=0
while IFS= read -r line; do
    [ -z "$line" ] && continue
    print_error "Falscher Besitzer (nicht $WEB_USER): $line"
    BAD=$((BAD + 1))
done < <(
    docker exec "$CONTAINER" sh -c '
        for root in "$@"; do
            [ -e "$root" ] || continue
            find "$root" ! -user '"$WEB_USER"' -printf "%p (%u:%g)\n" 2>/dev/null
        done
    ' _ "${SCAN_ROOTS[@]}"
)

if [ "$BAD" -gt 0 ]; then
    print_error "$BAD Datei(en) blockieren ACP-Paket-Updates."
    echo "Fix: ./tools/fix-woltlab-app-permissions.sh" >&2
    echo "Doku: tools/docs/DOCKER-APP-PERMISSIONS.de.md" >&2
    exit 1
fi

print_success "App-Berechtigungen OK ($WEB_USER) — ACP-Update möglich."
exit 0
