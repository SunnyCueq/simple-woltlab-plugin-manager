#!/bin/bash

#################################################################
# DDEV Start Script für WoltLab Suite 6.1
# Pfad: /home/benny/Dokumente/affiliate-plugin/woltlab-dev/start.sh
# 
# Usage:
#   ./start.sh        → Startet DDEV
#   ./start.sh logs   → Startet DDEV und zeigt Logs
#   ./start.sh stop   → Stoppt DDEV
#   ./start.sh restart → Startet DDEV neu
#################################################################

set -e

# Farben
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Script-Verzeichnis ermitteln
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$SCRIPT_DIR"

# Funktionen
print_header() {
    echo -e "${BLUE}========================================${NC}"
    echo -e "${BLUE}DDEV Start Script - WoltLab Suite 6.1${NC}"
    echo -e "${BLUE}========================================${NC}"
    echo ""
}

print_success() {
    echo -e "${GREEN}✓${NC} $1"
}

print_error() {
    echo -e "${RED}✗${NC} $1"
}

print_info() {
    echo -e "${YELLOW}→${NC} $1"
}

# Prüfe ob DDEV installiert ist
if ! command -v ddev &> /dev/null; then
    print_error "DDEV ist nicht installiert!"
    echo "Installiere DDEV mit: curl -fsSL https://ddev.com/install.sh | bash"
    exit 1
fi

# Kommando verarbeiten
COMMAND="${1:-start}"

case "$COMMAND" in
    start)
        print_header
        print_info "Starte DDEV..."
        
        if ddev start; then
            print_success "DDEV erfolgreich gestartet!"
            echo ""
            print_info "Website: $(ddev describe | grep -oP 'https://\S+')"
            print_info "ACP: $(ddev describe | grep -oP 'https://\S+')/acp/"
            echo ""
            print_info "Nützliche Befehle:"
            echo "  ddev logs          → Zeige Logs"
            echo "  ddev ssh            → SSH in Container"
            echo "  ddev stop           → Stoppe DDEV"
            echo "  ./start.sh logs     → Starte und zeige Logs"
        else
            print_error "DDEV konnte nicht gestartet werden!"
            exit 1
        fi
        ;;
    
    logs)
        print_header
        print_info "Starte DDEV und zeige Logs..."
        ddev start
        echo ""
        print_info "Zeige Logs (Ctrl+C zum Beenden)..."
        echo ""
        ddev logs -f
        ;;
    
    stop)
        print_header
        print_info "Stoppe DDEV..."
        if ddev stop; then
            print_success "DDEV erfolgreich gestoppt!"
        else
            print_error "DDEV konnte nicht gestoppt werden!"
            exit 1
        fi
        ;;
    
    restart)
        print_header
        print_info "Starte DDEV neu..."
        ddev restart
        print_success "DDEV erfolgreich neu gestartet!"
        echo ""
        print_info "Website: $(ddev describe | grep -oP 'https://\S+')"
        ;;
    
    status)
        print_header
        ddev describe
        ;;
    
    *)
        print_error "Unbekanntes Kommando: $COMMAND"
        echo ""
        echo "Verfügbare Kommandos:"
        echo "  start     → Startet DDEV (Standard)"
        echo "  logs      → Startet DDEV und zeigt Logs"
        echo "  stop      → Stoppt DDEV"
        echo "  restart   → Startet DDEV neu"
        echo "  status   → Zeigt DDEV Status"
        exit 1
        ;;
esac
