#!/usr/bin/env bash
# Prüft, ob Plugin-/App-Dateien im Docker-Container dem Webserver-User gehören.
# Exit 1 = ACP-Update würde mit „Permission denied“ scheitern.
#
# Usage:
#   ./tools/check-woltlab-app-permissions.sh [plugin-dir] [extra-path-in-container ...]

set -euo pipefail

readonly SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
readonly MAIN_DIR="$(dirname "$SCRIPT_DIR")"
readonly CONTAINER="${WOLTLAB_DOCKER_CONTAINER:-woltlab-web}"
readonly WEB_USER="${WOLTLAB_WEB_USER:-www-data}"

# shellcheck source=common.sh
source "$SCRIPT_DIR/common.sh"
# shellcheck source=swpm-package-resolve.sh
source "$SCRIPT_DIR/swpm-package-resolve.sh"

PLUGIN_ARG="${WOLTLAB_PLUGIN_DIR:-basis-plugin}"
EXTRA_PATHS=()
USE_CUSTOM_ROOTS=0
for arg in "$@"; do
    if [[ "$arg" == /* ]]; then
        EXTRA_PATHS+=("$arg")
        USE_CUSTOM_ROOTS=1
    elif [ "$USE_CUSTOM_ROOTS" -eq 0 ] && { [ -d "$MAIN_DIR/$arg" ] || [ -f "$MAIN_DIR/$arg/package.xml" ]; }; then
        PLUGIN_ARG="$arg"
    else
        EXTRA_PATHS+=("$arg")
        USE_CUSTOM_ROOTS=1
    fi
done

if [ "$USE_CUSTOM_ROOTS" -eq 0 ]; then
    if ! swpm_load_plugin_context "$PLUGIN_ARG" "$SCRIPT_DIR" "$MAIN_DIR"; then
        exit 1
    fi
    mapfile -t SCAN_ROOTS < <(swpm_collect_container_paths "$SWPM_APP_ABBREV" "$SWPM_PACKAGE_ID" "${EXTRA_PATHS[@]}")
else
    SCAN_ROOTS=("${EXTRA_PATHS[@]}")
fi

if ! docker ps --format '{{.Names}}' | grep -qx "$CONTAINER"; then
    print_error "Container '$CONTAINER' läuft nicht."
    exit 1
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
    echo "Fix: ./tools/fix-woltlab-app-permissions.sh ${PLUGIN_ARG}" >&2
    echo "Doku: tools/docs/DOCKER-APP-PERMISSIONS.de.md" >&2
    exit 1
fi

print_success "App-Berechtigungen OK ($WEB_USER) — ACP-Update möglich."
exit 0
