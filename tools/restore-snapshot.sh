#!/usr/bin/env bash

#################################################################
# WoltLab Snapshot Wiederherstellung
# Pfad: tools/restore-snapshot.sh
# 
# Stellt die komplette WoltLab-Installation aus dem Snapshot wieder her
#################################################################

TOOLS_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
SNAPSHOT_TOOLS_DIR="$TOOLS_DIR/woltlab-snapshot-tools"

if [ -f "$TOOLS_DIR/common.sh" ]; then
    source "$TOOLS_DIR/common.sh"
else
    RED='\033[0;31m'
    GREEN='\033[0;32m'
    YELLOW='\033[1;33m'
    BLUE='\033[0;34m'
    CYAN='\033[0;36m'
    NC='\033[0m'
    print_section() {
        local title="$1"
        shift
        local breadcrumbs=("$@")
        if [ ${#breadcrumbs[@]} -gt 0 ]; then
            echo -e "${BLUE}Navigation:${NC} ${CYAN}${breadcrumbs[*]}${NC}"
            echo ""
        fi
        echo -e "${CYAN}==========================================${NC}"
        echo -e "${CYAN}${title}${NC}"
        echo -e "${CYAN}==========================================${NC}"
        echo ""
    }
    print_error() { echo -e "${RED}✗ $1${NC}"; }
    ensure_executable() {
        local script_path="$1"
        if [ -f "$script_path" ] && [ ! -x "$script_path" ]; then
            chmod +x "$script_path" 2>/dev/null || return 1
        fi
    }
fi

print_section "Restore Snapshot - WoltLab wiederherstellen" "Hauptmenü" "Restore Snapshot"

if [ ! -d "$SNAPSHOT_TOOLS_DIR" ] || [ ! -f "$SNAPSHOT_TOOLS_DIR/restore.sh" ]; then
    print_error "restore.sh nicht gefunden!"
    exit 1
fi

ensure_executable "$SNAPSHOT_TOOLS_DIR/restore.sh" || {
    print_error "Keine Berechtigung für restore.sh"
    exit 1
}

cd "$SNAPSHOT_TOOLS_DIR"
exec ./restore.sh
