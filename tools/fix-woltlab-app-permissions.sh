#!/usr/bin/env bash
# Setzt Besitzer auf den WoltLab-Webserver-User (Standard: www-data).
# Pflicht nach jedem `docker cp` in den Container — sonst scheitert der ACP-Paket-Installer
# mit „Permission denied“ beim Überschreiben von Plugin-Dateien.
#
# Usage:
#   ./tools/fix-woltlab-app-permissions.sh [extra-path-in-container ...]
#   WOLTLAB_DOCKER_CONTAINER=woltlab-web ./tools/fix-woltlab-app-permissions.sh

set -euo pipefail

readonly SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
readonly CONTAINER="${WOLTLAB_DOCKER_CONTAINER:-woltlab-web}"
readonly WEB_USER="${WOLTLAB_WEB_USER:-www-data}"
readonly WEB_GROUP="${WOLTLAB_WEB_GROUP:-www-data}"

if [ -f "$SCRIPT_DIR/common.sh" ]; then
    # shellcheck source=common.sh
    source "$SCRIPT_DIR/common.sh"
else
    print_info() { echo "ℹ $1"; }
    print_success() { echo "✓ $1"; }
    print_error() { echo "✗ $1" >&2; }
fi

if ! docker ps --format '{{.Names}}' | grep -qx "$CONTAINER"; then
    print_error "Container '$CONTAINER' läuft nicht."
    exit 1
fi

PATHS=(
    /var/www/html/shrinkr
    /var/www/html/shrinkr-max-test.php
    /var/www/html/shrinkr-cron-run.php
)

for extra in "$@"; do
    PATHS+=("$extra")
done

EXISTING=()
for p in "${PATHS[@]}"; do
    if docker exec "$CONTAINER" test -e "$p" 2>/dev/null; then
        EXISTING+=("$p")
    fi
done

if [ ${#EXISTING[@]} -eq 0 ]; then
    print_info "Keine bekannten App-Pfade im Container — nichts zu tun."
    exit 0
fi

print_info "chown ${WEB_USER}:${WEB_GROUP} in $CONTAINER …"
docker exec "$CONTAINER" chown -R "${WEB_USER}:${WEB_GROUP}" "${EXISTING[@]}"
print_success "App-Berechtigungen gesetzt (${#EXISTING[@]} Pfad/Pfade)."
