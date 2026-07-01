#!/usr/bin/env bash
# Setzt Besitzer auf den WoltLab-Webserver-User (Standard: www-data).
# Pflicht nach jedem `docker cp` in den Container — sonst scheitert der ACP-Paket-Installer
# mit „Permission denied“ beim Überschreiben von Plugin-Dateien.
#
# Usage:
#   ./tools/fix-woltlab-app-permissions.sh [plugin-dir] [extra-path-in-container ...]
#   WOLTLAB_DOCKER_CONTAINER=woltlab-web ./tools/fix-woltlab-app-permissions.sh basis-plugin

set -euo pipefail

readonly SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
readonly MAIN_DIR="$(dirname "$SCRIPT_DIR")"
readonly CONTAINER="${WOLTLAB_DOCKER_CONTAINER:-woltlab-web}"
readonly WEB_USER="${WOLTLAB_WEB_USER:-www-data}"
readonly WEB_GROUP="${WOLTLAB_WEB_GROUP:-www-data}"

# shellcheck source=common.sh
source "$SCRIPT_DIR/common.sh"
# shellcheck source=swpm-package-resolve.sh
source "$SCRIPT_DIR/swpm-package-resolve.sh"

PLUGIN_ARG="${WOLTLAB_PLUGIN_DIR:-basis-plugin}"
EXTRA_PATHS=()
for arg in "$@"; do
    if [[ "$arg" == /* ]]; then
        EXTRA_PATHS+=("$arg")
    elif [ ${#EXTRA_PATHS[@]} -eq 0 ] && [ -d "$MAIN_DIR/$arg" ] || [ -f "$MAIN_DIR/$arg/package.xml" ] || [ -f "$MAIN_DIR/$arg/temp_edit/package.xml" ]; then
        PLUGIN_ARG="$arg"
    else
        EXTRA_PATHS+=("$arg")
    fi
done

if ! swpm_load_plugin_context "$PLUGIN_ARG" "$SCRIPT_DIR" "$MAIN_DIR"; then
    exit 1
fi

if ! docker ps --format '{{.Names}}' | grep -qx "$CONTAINER"; then
    print_error "Container '$CONTAINER' läuft nicht."
    exit 1
fi

mapfile -t PATHS < <(swpm_collect_container_paths "$SWPM_APP_ABBREV" "$SWPM_PACKAGE_ID" "${EXTRA_PATHS[@]}")

EXISTING=()
for p in "${PATHS[@]}"; do
    if docker exec "$CONTAINER" test -e "$p" 2>/dev/null; then
        EXISTING+=("$p")
    fi
done

if [ ${#EXISTING[@]} -eq 0 ]; then
    print_info "Keine App-Pfade für ${SWPM_PACKAGE_ID} im Container — nichts zu tun."
    exit 0
fi

print_info "chown ${WEB_USER}:${WEB_GROUP} (${SWPM_APP_ABBREV}) in $CONTAINER …"
docker exec "$CONTAINER" chown -R "${WEB_USER}:${WEB_GROUP}" "${EXISTING[@]}"
print_success "App-Berechtigungen gesetzt (${#EXISTING[@]} Pfad/Pfade)."
