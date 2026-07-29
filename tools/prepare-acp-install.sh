#!/usr/bin/env bash
# Kopiert das neueste Plugin-.tar.gz in den lokalen Docker-Webserver
# und gibt die Schritte für manuellen ACP-Upload aus.
#
# Usage: ./tools/prepare-acp-install.sh [plugin-dir]
# Beispiel: ./tools/prepare-acp-install.sh basis-plugin

set -euo pipefail

readonly SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
readonly MAIN_DIR="$(dirname "$SCRIPT_DIR")"
readonly PLUGIN="${1:-basis-plugin}"
readonly DOCKER_CONTAINER="${WOLTLAB_DOCKER_CONTAINER:-woltlab-web}"
readonly ACP_URL="${WOLTLAB_ACP_INSTALL_URL:-https://wsc.local/acp/index.php?package-start-install/&action=install}"

# shellcheck source=common.sh
source "$SCRIPT_DIR/common.sh"

if [ -d "$PLUGIN" ]; then
    PLUGIN_DIR="$(cd "$PLUGIN" && pwd)"
elif [ -d "$MAIN_DIR/$PLUGIN" ]; then
    PLUGIN_DIR="$MAIN_DIR/$PLUGIN"
else
    echo "Plugin-Verzeichnis nicht gefunden: $PLUGIN" >&2
    exit 1
fi

PACKAGE="$(swpm_find_latest_package "$MAIN_DIR" "$PLUGIN_DIR" || true)"
if [ -z "$PACKAGE" ] || [ ! -f "$PACKAGE" ]; then
    echo "Kein .tar.gz in $(swpm_release_dir "$MAIN_DIR" "$PLUGIN_DIR") (oder Legacy im Plugin-Root) – zuerst ./tools/build.sh $PLUGIN bauen." >&2
    exit 1
fi

BASENAME="$(basename "$PACKAGE")"

if ! docker ps --format '{{.Names}}' | grep -qx "$DOCKER_CONTAINER"; then
    echo "Container '$DOCKER_CONTAINER' läuft nicht. Stack starten: ../lokal-webserver/tools.sh" >&2
    exit 1
fi

docker cp "$PACKAGE" "$DOCKER_CONTAINER:/var/www/html/$BASENAME"

FIX_PERMS="$SCRIPT_DIR/fix-woltlab-app-permissions.sh"
CHECK_PERMS="$SCRIPT_DIR/check-woltlab-app-permissions.sh"
if [ -x "$FIX_PERMS" ]; then
    "$FIX_PERMS" "$PLUGIN"
fi
if [ -x "$CHECK_PERMS" ]; then
    if ! "$CHECK_PERMS" "$PLUGIN"; then
        echo "" >&2
        echo "✗ ACP-Paket-Update würde mit „Permission denied“ scheitern." >&2
        echo "  Ursache: früheres docker cp ohne www-data-Besitzer." >&2
        echo "  Fix erneut: $FIX_PERMS" >&2
        echo "  Doku: tools/docs/DOCKER-APP-PERMISSIONS.de.md" >&2
        exit 1
    fi
fi

echo ""
echo "=== ACP-Paket-Upload (manuell) ==="
echo ""
echo "1. Öffnen: $ACP_URL"
echo "2. Button „Paket hochladen“ → Dialog"
echo "3. Datei auswählen:"
echo "   $PACKAGE"
echo "   (liegt auch im Container: /var/www/html/$BASENAME)"
echo "4. Absenden → Installation bestätigen"
echo ""
echo "Hinweis: Paketdatei im ACP-Dialog manuell auswählen (Datei-Upload)."
echo "         Nach manuellem Upload sag Bescheid – Tests gehen dann weiter."
echo ""
