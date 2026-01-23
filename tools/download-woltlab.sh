#!/bin/bash

#################################################################
# WoltLab Development Tools - WoltLab Core Download
# 
# Automatischer Download der aktuellen WoltLab Suite
#
# @author      Sunny C
# @copyright   2026 Sunny C
#################################################################

set -e

# Verzeichnisse
TOOLS_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
MAIN_DIR="$(dirname "$TOOLS_DIR")"
WOLTLAB_CORE_DIR="$MAIN_DIR/woltlab-core"
DOWNLOAD_URL="https://www.woltlab.com/de/woltlab-suite-download/"

# Lade gemeinsame Funktionen
if [ -f "$TOOLS_DIR/common.sh" ]; then
    source "$TOOLS_DIR/common.sh"
else
    RED='\033[0;31m'
    GREEN='\033[0;32m'
    YELLOW='\033[1;33m'
    BLUE='\033[0;34m'
    CYAN='\033[0;36m'
    NC='\033[0m'
    
    print_header() {
        clear
        print_info "╔═══════════════════════════════════════════════════════╗${NC}"
        print_info "║                                                       ║${NC}"
        print_info "║     ${CYAN}WoltLab Core Download${BLUE}                      ║${NC}"
        print_info "║                                                       ║${NC}"
        print_info "╚═══════════════════════════════════════════════════════╝${NC}"
        echo ""
    }
    
    print_success() { print_success "$1${NC}"; }
    print_error() { echo -e "${RED}✗ $1${NC}"; }
    print_info() { echo -e "${YELLOW}→ $1${NC}"; }
    print_warning() { print_warning "$1${NC}"; }
fi

# Funktion: Prüfe ob curl/wget verfügbar ist
check_download_tool() {
    if command -v curl >/dev/null 2>&1; then
        echo "curl"
    elif command -v wget >/dev/null 2>&1; then
        echo "wget"
    else
        print_error "Weder curl noch wget gefunden!"
        print_info "Bitte installiere curl oder wget"
        exit 1
    fi
}

# Funktion: Lade HTML-Seite
download_html() {
    local url="$1"
    local output="$2"
    local tool=$(check_download_tool)
    
    if [ "$tool" = "curl" ]; then
        curl -sL "$url" -o "$output"
    else
        wget -q -O "$output" "$url"
    fi
}

# Funktion: Extrahiere Download-URL aus HTML
extract_download_url() {
    local html_file="$1"
    
    # Versuche verschiedene Patterns zu finden
    # Pattern 1: Direkter Download-Link
    local url=$(grep -oP 'href="[^"]*woltlab[^"]*\.zip' "$html_file" | head -1 | sed 's/href="//;s/"//')
    
    if [ -z "$url" ]; then
        # Pattern 2: Relativer Link
        url=$(grep -oP 'href="[^"]*download[^"]*\.zip' "$html_file" | head -1 | sed 's/href="//;s/"//')
    fi
    
    if [ -z "$url" ]; then
        # Pattern 3: Suche nach Download-Button
        url=$(grep -i "download" "$html_file" | grep -oP 'href="[^"]*\.zip' | head -1 | sed 's/href="//')
    fi
    
    # Wenn URL relativ ist, mache sie absolut
    if [ -n "$url" ] && [[ ! "$url" =~ ^https?:// ]]; then
        url="https://www.woltlab.com$url"
    fi
    
    echo "$url"
}

# Funktion: Lade ZIP-Datei
download_zip() {
    local url="$1"
    local output="$2"
    local tool=$(check_download_tool)
    
    print_info "Lade WoltLab Suite herunter..."
    print_info "URL: $url"
    print_info "Ziel: $output"
    echo ""
    
    if [ "$tool" = "curl" ]; then
        curl -L --progress-bar "$url" -o "$output"
    else
        wget --progress=bar:force "$url" -O "$output"
    fi
}

# Funktion: Extrahiere WCFSetup.tar.gz
extract_wcfsetup() {
    local zip_file="$1"
    local target_dir="$2"
    
    print_info "Extrahiere WCFSetup.tar.gz..."
    
    # Erstelle temporäres Verzeichnis
    local temp_dir=$(mktemp -d)
    
    # Entpacke ZIP
    unzip -q "$zip_file" -d "$temp_dir"
    
    # Finde WCFSetup.tar.gz
    local wcfsetup=$(find "$temp_dir" -name "WCFSetup.tar.gz" -type f | head -1)
    
    if [ -z "$wcfsetup" ]; then
        print_error "WCFSetup.tar.gz nicht in ZIP gefunden!"
        rm -rf "$temp_dir"
        return 1
    fi
    
    # Kopiere nach Zielverzeichnis
    mkdir -p "$target_dir"
    cp "$wcfsetup" "$target_dir/"
    
    # Kopiere auch install.php und test.php falls vorhanden
    find "$temp_dir" -name "install.php" -exec cp {} "$target_dir/" \; 2>/dev/null || true
    find "$temp_dir" -name "test.php" -exec cp {} "$target_dir/" \; 2>/dev/null || true
    
    # Aufräumen
    rm -rf "$temp_dir"
    
    print_success "WCFSetup.tar.gz extrahiert"
}

# Hauptfunktion
main() {
    print_header
    
    # Erstelle woltlab-core Verzeichnis
    mkdir -p "$WOLTLAB_CORE_DIR"
    
    # Prüfe ob bereits vorhanden
    if [ -f "$WOLTLAB_CORE_DIR/WCFSetup.tar.gz" ]; then
        print_warning "WCFSetup.tar.gz existiert bereits"
        read -p "$(echo -e "${YELLOW}Neu herunterladen?${NC} [y/N]: ")" redownload
        if [ "${redownload:-n}" != "y" ]; then
            print_info "Abgebrochen"
            exit 0
        fi
    fi
    
    # Lade HTML-Seite
    print_info "Lade Download-Seite..."
    local html_file=$(mktemp)
    download_html "$DOWNLOAD_URL" "$html_file"
    
    # Extrahiere Download-URL
    print_info "Suche Download-URL..."
    local download_url=$(extract_download_url "$html_file")
    rm "$html_file"
    
    if [ -z "$download_url" ]; then
        print_error "Download-URL nicht gefunden!"
        print_info "Bitte lade WoltLab manuell von: $DOWNLOAD_URL"
        print_info "Und extrahiere WCFSetup.tar.gz nach: $WOLTLAB_CORE_DIR"
        exit 1
    fi
    
    print_success "Download-URL gefunden"
    
    # Lade ZIP-Datei
    local zip_file="$WOLTLAB_CORE_DIR/woltlab-suite.zip"
    download_zip "$download_url" "$zip_file"
    
    if [ ! -f "$zip_file" ]; then
        print_error "Download fehlgeschlagen!"
        exit 1
    fi
    
    print_success "ZIP-Datei heruntergeladen"
    
    # Extrahiere WCFSetup.tar.gz
    extract_wcfsetup "$zip_file" "$WOLTLAB_CORE_DIR"
    
    # Lösche ZIP-Datei (optional)
    print_info "Lösche ZIP-Datei..."
    rm "$zip_file"
    
    echo ""
    print_success "WoltLab Core erfolgreich heruntergeladen!"
    print_info "WCFSetup.tar.gz befindet sich in: $WOLTLAB_CORE_DIR"
    echo ""
}

# Führe Hauptfunktion aus
main
