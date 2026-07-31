#!/usr/bin/env bash

#################################################################
# WoltLab Core (Setup-Dateien) herunterladen
# Pfad: tools/download-woltlab-core.sh [VERSION]
#
# VERSION:
#   leer     → neueste ZIP der bevorzugten Linie (oder global neueste)
#   6.2.6    → exakt
#   6.2      → neuestes Patch dieser Linie von der Download-Seite
#################################################################

set -euo pipefail

readonly TOOLS_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
readonly MAIN_DIR="$(dirname "$TOOLS_DIR")"
readonly CORE_DIR="$MAIN_DIR/woltlab-core"
WOLTLAB_VERSION="${1:-}"

# shellcheck source=common.sh
source "$TOOLS_DIR/common.sh"
# shellcheck source=woltlab-refs-lib.sh
source "$TOOLS_DIR/woltlab-refs-lib.sh"

DOWNLOAD_URL=""
RESOLVED_VER=""

if [ -n "$WOLTLAB_VERSION" ]; then
    if [[ "$WOLTLAB_VERSION" =~ ^[0-9]+\.[0-9]+\.[0-9]+$ ]]; then
        RESOLVED_VER="$WOLTLAB_VERSION"
        DOWNLOAD_URL="$(woltlab_asset_url_for_version "$RESOLVED_VER")"
        print_info "Zielversion: $RESOLVED_VER (exakt)"
    elif [[ "$WOLTLAB_VERSION" =~ ^[0-9]+\.[0-9]+$ ]]; then
        print_info "Ziel-Linie: $WOLTLAB_VERSION — suche neuestes Patch …"
        local_pick=""
        if local_pick="$(woltlab_pick_latest_release "$WOLTLAB_VERSION")"; then
            RESOLVED_VER="$(printf '%s' "$local_pick" | cut -f1)"
            DOWNLOAD_URL="$(printf '%s' "$local_pick" | cut -f2)"
            woltlab_cache_write_latest "$RESOLVED_VER" "$DOWNLOAD_URL" "$WOLTLAB_VERSION"
            print_info "Gewählt: $RESOLVED_VER"
        else
            print_warning "Kein Release für $WOLTLAB_VERSION auf der Download-Seite — Fallback .0"
            RESOLVED_VER="${WOLTLAB_VERSION}.0"
            DOWNLOAD_URL="$(woltlab_asset_url_for_version "$RESOLVED_VER")"
        fi
    else
        print_error "Ungültige Version: $WOLTLAB_VERSION (erwartet 6.2 oder 6.2.6)"
        exit 1
    fi
else
    line="$(woltlab_preferred_line)"
    print_info "Keine Version angegeben — neuestes Release für Linie $line …"
    if pick="$(woltlab_detect_online_core "$line" --fresh)"; then
        RESOLVED_VER="$(printf '%s' "$pick" | cut -f1)"
        DOWNLOAD_URL="$(printf '%s' "$pick" | cut -f2)"
    elif pick="$(woltlab_pick_latest_release)"; then
        RESOLVED_VER="$(printf '%s' "$pick" | cut -f1)"
        DOWNLOAD_URL="$(printf '%s' "$pick" | cut -f2)"
    fi
fi

if [ -z "$DOWNLOAD_URL" ]; then
    print_error "Download-URL konnte nicht ermittelt werden. Beispiel: $0 6.2.6"
    echo "  Seite: $WOLTLAB_DOWNLOAD_PAGE" >&2
    exit 1
fi

ZIP_FILE="$CORE_DIR/woltlab-suite.zip"
mkdir -p "$CORE_DIR"
[ -f "$CORE_DIR/.gitkeep" ] || touch "$CORE_DIR/.gitkeep"

print_info "Lade WoltLab Suite herunter..."
print_info "URL: $DOWNLOAD_URL"
if ! curl -fsSL -o "$ZIP_FILE" "$DOWNLOAD_URL"; then
    print_error "Download fehlgeschlagen."
    exit 1
fi
print_success "ZIP heruntergeladen${RESOLVED_VER:+ ($RESOLVED_VER)}."

print_info "Extrahiere WCFSetup.tar.gz und Setup-Dateien..."
TMP_EXTRACT=$(mktemp -d)
trap 'rm -rf -- "$TMP_EXTRACT"' EXIT
unzip -q -o "$ZIP_FILE" -d "$TMP_EXTRACT"

if [ -f "$TMP_EXTRACT/WCFSetup.tar.gz" ]; then
    cp "$TMP_EXTRACT/WCFSetup.tar.gz" "$CORE_DIR/"
    [ -f "$TMP_EXTRACT/install.php" ] && cp "$TMP_EXTRACT/install.php" "$CORE_DIR/"
    [ -f "$TMP_EXTRACT/test.php" ] && cp "$TMP_EXTRACT/test.php" "$CORE_DIR/"
else
    FOUND=$(find "$TMP_EXTRACT" -maxdepth 2 -name "WCFSetup.tar.gz" 2>/dev/null | head -1)
    if [ -n "$FOUND" ]; then
        dir=$(dirname "$FOUND")
        cp "$dir/WCFSetup.tar.gz" "$CORE_DIR/"
        [ -f "$dir/install.php" ] && cp "$dir/install.php" "$CORE_DIR/"
        [ -f "$dir/test.php" ] && cp "$dir/test.php" "$CORE_DIR/"
    else
        print_error "WCFSetup.tar.gz im ZIP nicht gefunden."
        rm -f "$ZIP_FILE"
        exit 1
    fi
fi

rm -f "$ZIP_FILE"
if [ -n "$RESOLVED_VER" ]; then
    printf '%s\n' "$RESOLVED_VER" > "$CORE_DIR/.swpm-core-version"
fi
print_success "WoltLab Core nach $CORE_DIR ausgepackt (WCFSetup.tar.gz, install.php, test.php)."
