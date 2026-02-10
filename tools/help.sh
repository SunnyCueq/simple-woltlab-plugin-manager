#!/bin/bash

#################################################################
# Help & Documentation Viewer
# Pfad: tools/help.sh
# Zeigt die README.md in einem lesbaren Format an.
#################################################################

set -e

#=====================================
# KONFIGURATION
#=====================================
readonly TOOLS_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
readonly README_FILE="$TOOLS_DIR/README.md"

#=====================================
# QUELLEN
#=====================================
if [ -f "$TOOLS_DIR/common.sh" ]; then
    source "$TOOLS_DIR/common.sh"
else
    RED='\033[0;31m'
    GREEN='\033[0;32m'
    YELLOW='\033[1;33m'
    BLUE='\033[0;34m'
    CYAN='\033[0;36m'
    NC='\033[0m'
    print_error() { echo -e "${RED}✗ $1${NC}" >&2; }
fi

#=====================================
# HILFSFUNKTIONEN
#=====================================
show_with_glow() {
    if command -v glow &> /dev/null; then
        glow "$README_FILE" 2>/dev/null
        return $?
    fi
    return 1
}

show_with_bat() {
    if command -v bat &> /dev/null; then
        bat --style=plain --language=markdown "$README_FILE" 2>/dev/null
        return $?
    fi
    return 1
}

show_with_mdless() {
    if command -v mdless &> /dev/null; then
        mdless "$README_FILE" 2>/dev/null
        return $?
    fi
    return 1
}

show_with_less() {
    sed -E \
        -e 's/^#+ (.*)/\n\1\n========================================/g' \
        -e 's/^##+ (.*)/\n\1\n----------------------------------------/g' \
        -e 's/^###+ (.*)/\n\1\n/g' \
        -e 's/```[^`]*```//g' \
        -e 's/`([^`]+)`/\1/g' \
        -e 's/^[-*+] /  • /g' \
        -e 's/^[0-9]+\. /  /g' \
        -e 's/\*\*([^*]+)\*\*/\1/g' \
        -e 's/\*([^*]+)\*/\1/g' \
        -e 's/\[([^\]]+)\]\([^\)]+\)/\1/g' \
        "$README_FILE" | less -R
}

show_with_cat() {
    sed -E \
        -e 's/^#+ (.*)/\n\1\n========================================/g' \
        -e 's/^##+ (.*)/\n\1\n----------------------------------------/g' \
        -e 's/^###+ (.*)/\n\1\n/g' \
        -e 's/```[^`]*```//g' \
        -e 's/`([^`]+)`/\1/g' \
        -e 's/^[-*+] /  • /g' \
        -e 's/^[0-9]+\. /  /g' \
        -e 's/\*\*([^*]+)\*\*/\1/g' \
        -e 's/\*([^*]+)\*/\1/g' \
        -e 's/\[([^\]]+)\]\([^\)]+\)/\1/g' \
        "$README_FILE"
    echo ""
    echo -e "${YELLOW}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo -e "${YELLOW}Tipp:${NC} Für bessere Markdown-Anzeige installiere:"
    echo -e "  ${CYAN}•${NC} glow: ${BLUE}sudo pacman -S glow${NC} (empfohlen)"
    echo -e "  ${CYAN}•${NC} bat:  ${BLUE}sudo pacman -S bat${NC}"
    echo -e "  ${CYAN}•${NC} mdless: ${BLUE}gem install mdless${NC}"
    echo ""
}

#=====================================
# HAUPTLOGIK
#=====================================
if [ ! -f "$README_FILE" ]; then
    print_error "README.md nicht gefunden: $README_FILE"
    exit 1
fi

if show_with_glow; then
    exit 0
elif show_with_bat; then
    exit 0
elif show_with_mdless; then
    exit 0
elif command -v less &> /dev/null; then
    show_with_less
    exit 0
else
    show_with_cat
    exit 0
fi
