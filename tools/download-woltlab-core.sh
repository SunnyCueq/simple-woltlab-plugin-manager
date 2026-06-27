#!/usr/bin/env bash

#################################################################
# WoltLab Core (Setup-Dateien) herunterladen
# Pfad: tools/download-woltlab-core.sh [VERSION]
# Lädt die offizielle WoltLab-Suite-ZIP für VERSION (z. B. 6.2),
# extrahiert WCFSetup.tar.gz, install.php, test.php nach MAIN_DIR/woltlab-core/
# Ohne VERSION: neueste verfügbare Version von der Download-Seite.
#################################################################

set -e

readonly TOOLS_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
readonly MAIN_DIR="$(dirname "$TOOLS_DIR")"
readonly CORE_DIR="$MAIN_DIR/woltlab-core"
WOLTLAB_VERSION="${1:-}"

if [ -f "$TOOLS_DIR/common.sh" ]; then
    source "$TOOLS_DIR/common.sh"
else
    echo "common.sh nicht gefunden."
    exit 1
fi

# Download-URL: bei VERSION z. B. 6.2 -> 6.2.0, https://assets.woltlab.com/release/woltlab-suite-6.2.0.zip
DOWNLOAD_URL=""
if [ -n "$WOLTLAB_VERSION" ]; then
    # Version normalisieren: 6.2 -> 6.2.0 für Asset-URL
    VER_ASSET="$WOLTLAB_VERSION"
    if [[ "$WOLTLAB_VERSION" =~ ^[0-9]+\.[0-9]+$ ]]; then
        VER_ASSET="${WOLTLAB_VERSION}.0"
    fi
    DOWNLOAD_URL="https://assets.woltlab.com/release/woltlab-suite-${VER_ASSET}.zip"
    print_info "Zielversion: $WOLTLAB_VERSION (Asset: $VER_ASSET)"
fi
if [ -z "$DOWNLOAD_URL" ] || [ "$WOLTLAB_VERSION" = "" ]; then
    if command -v curl &>/dev/null; then
        DOWNLOAD_URL=$(curl -sS "https://www.woltlab.com/de/woltlab-suite-download/" 2>/dev/null | grep -oE 'https://assets\.woltlab\.com/release/woltlab-suite-[0-9.]+\.zip' | head -1)
    fi
fi
if [ -z "$DOWNLOAD_URL" ]; then
    print_error "Download-URL konnte nicht ermittelt werden. Optional Version angeben: $0 6.2"
    exit 1
fi

ZIP_FILE="$CORE_DIR/woltlab-suite.zip"
mkdir -p "$CORE_DIR"

print_info "Lade WoltLab Suite herunter..."
print_info "URL: $DOWNLOAD_URL"
if ! curl -fsSL -o "$ZIP_FILE" "$DOWNLOAD_URL"; then
    print_error "Download fehlgeschlagen."
    exit 1
fi
print_success "ZIP heruntergeladen."

print_info "Extrahiere WCFSetup.tar.gz und Setup-Dateien..."
TMP_EXTRACT=$(mktemp -d)
trap "rm -rf '$TMP_EXTRACT'" EXIT
unzip -q -o "$ZIP_FILE" -d "$TMP_EXTRACT"

# ZIP-Struktur: oft ein Unterordner mit WCFSetup.tar.gz, install.php, test.php
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
print_success "WoltLab Core nach $CORE_DIR ausgepackt (WCFSetup.tar.gz, install.php, test.php)."
