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
readonly PLUGIN_DIR="$MAIN_DIR/$PLUGIN"
readonly DOCKER_CONTAINER="${WOLTLAB_DOCKER_CONTAINER:-woltlab-web}"
readonly ACP_URL="${WOLTLAB_ACP_INSTALL_URL:-https://wsc.local/acp/index.php?package-start-install/&action=install}"

if [ ! -d "$PLUGIN_DIR" ]; then
    echo "Plugin-Verzeichnis nicht gefunden: $PLUGIN_DIR" >&2
    exit 1
fi

mapfile -t PACKAGES < <(find "$PLUGIN_DIR" -maxdepth 1 -name '*.tar.gz' -type f -printf '%T@ %p\n' 2>/dev/null | sort -rn | cut -d' ' -f2-)
if [ ${#PACKAGES[@]} -eq 0 ]; then
    echo "Kein .tar.gz in $PLUGIN_DIR – zuerst ./tools/build.sh $PLUGIN bauen." >&2
    exit 1
fi

PACKAGE="${PACKAGES[0]}"
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
