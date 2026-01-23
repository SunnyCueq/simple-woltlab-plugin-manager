#!/bin/bash

#################################################################
# Migration Script: Portainer → Dockge
# Pfad: tools/migrate-to-dockge.sh
# 
# Migriert von Portainer zu Dockge (moderne, schnellere Alternative)
# 
# Usage:
#   ./migrate-to-dockge.sh        → Führt Migration durch
#################################################################

set -e

# Verzeichnisse
TOOLS_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

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
    
    print_section() {
        local title="$1"
        shift
        local breadcrumbs=("$@")
        if [ ${#breadcrumbs[@]} -gt 0 ]; then
            print_info "Navigation:${NC} ${CYAN}${breadcrumbs[*]}${NC}"
            echo ""
        fi
        echo -e "${CYAN}==========================================${NC}"
        echo -e "${CYAN}${title}${NC}"
        echo -e "${CYAN}==========================================${NC}"
        echo ""
    }
    
    print_success() { print_success "$1${NC}"; }
    print_error() { echo -e "${RED}✗ $1${NC}"; }
    print_info() { print_info "$1${NC}"; }
    print_warning() { print_warning "$1${NC}"; }
fi

print_section "Migration: Portainer → Dockge" "Hauptmenü" "Dockge"

# Prüfe ob Docker installiert ist
if ! command -v docker &> /dev/null; then
    print_error "Docker ist nicht installiert!"
    exit 1
fi

# Prüfe ob Docker läuft
if ! docker info &>/dev/null; then
    print_error "Docker läuft nicht!"
    exit 1
fi

# Prüfe Portainer Status
PORTAINER_NAME="portainer"
PORTAINER_DATA_VOLUME="portainer_data"

portainer_exists=false
portainer_running=false
volume_exists=false

if docker ps -a --format "{{.Names}}" | grep -q "^${PORTAINER_NAME}$"; then
    portainer_exists=true
    if docker ps --format "{{.Names}}" | grep -q "^${PORTAINER_NAME}$"; then
        portainer_running=true
    fi
fi

if docker volume ls --format "{{.Name}}" | grep -q "^${PORTAINER_DATA_VOLUME}$"; then
    volume_exists=true
fi

echo -e "${CYAN}Aktueller Status:${NC}"
echo -e "   Portainer Container: ${portainer_exists:+${GREEN}✓${NC}} ${portainer_exists:-${RED}✗${NC}} ${portainer_exists:-nicht vorhanden}"
if [ "$portainer_exists" = true ]; then
    echo -e "   Portainer läuft: ${portainer_running:+${GREEN}✓${NC}} ${portainer_running:-${YELLOW}gestoppt${NC}}"
fi
echo -e "   Portainer Volume: ${volume_exists:+${GREEN}✓${NC}} ${volume_exists:-${RED}✗${NC}} ${volume_exists:-nicht vorhanden}"
echo ""

# Bestätigung
print_warning "Dies wird Portainer entfernen und Dockge installieren!"
echo ""
echo -e "${CYAN}Was passiert:${NC}"
echo "   1. Portainer Container wird gestoppt"
echo "   2. Portainer Container wird entfernt"
if [ "$volume_exists" = true ]; then
    echo "   3. Portainer Volume wird entfernt (optional)"
fi
echo "   4. Dockge wird installiert"
echo ""
read -p "Fortfahren? (j/N): " confirm

if [[ ! "$confirm" =~ ^[Jj]$ ]]; then
    print_info "Migration abgebrochen"
    exit 0
fi

echo ""

# Schritt 1: Portainer stoppen
if [ "$portainer_running" = true ]; then
    print_info "Stoppe Portainer..."
    if docker stop "$PORTAINER_NAME" &>/dev/null; then
        print_success "Portainer gestoppt"
    else
        print_warning "Portainer konnte nicht gestoppt werden (läuft vielleicht nicht)"
    fi
fi

# Schritt 2: Portainer Container entfernen
if [ "$portainer_exists" = true ]; then
    print_info "Entferne Portainer Container..."
    if docker rm "$PORTAINER_NAME" &>/dev/null; then
        print_success "Portainer Container entfernt"
    else
        print_warning "Portainer Container konnte nicht entfernt werden"
    fi
fi

# Schritt 3: Portainer Volume entfernen (optional)
if [ "$volume_exists" = true ]; then
    echo ""
    read -p "Portainer Volume (portainer_data) auch entfernen? (j/N): " remove_volume
    if [[ "$remove_volume" =~ ^[Jj]$ ]]; then
        print_info "Entferne Portainer Volume..."
        if docker volume rm "$PORTAINER_DATA_VOLUME" &>/dev/null; then
            print_success "Portainer Volume entfernt"
        else
            print_warning "Portainer Volume konnte nicht entfernt werden (vielleicht noch in Verwendung)"
        fi
    else
        print_info "Portainer Volume bleibt erhalten"
    fi
fi

echo ""
print_success "Portainer erfolgreich entfernt!"
echo ""

# Schritt 4: Dockge installieren
print_info "Installiere Dockge..."
echo ""

# Prüfe ob Dockge bereits existiert
DOCKGE_NAME="dockge"
if docker ps -a --format "{{.Names}}" | grep -q "^${DOCKGE_NAME}$"; then
    print_warning "Dockge Container existiert bereits!"
    read -p "Trotzdem fortfahren und neu installieren? (j/N): " reinstall
    if [[ ! "$reinstall" =~ ^[Jj]$ ]]; then
        print_info "Installation abgebrochen"
        exit 0
    fi
    
    # Entferne existierenden Dockge Container
    if docker ps --format "{{.Names}}" | grep -q "^${DOCKGE_NAME}$"; then
        docker stop "$DOCKGE_NAME" &>/dev/null || true
    fi
    docker rm "$DOCKGE_NAME" &>/dev/null || true
fi

# Erstelle Dockge Container
# Dockge verwendet Port 5001 (nicht 9000 wie Portainer)
DOCKGE_PORT="${DOCKGE_PORT:-5001}"
DOCKGE_STACKS_DIR="${DOCKGE_STACKS_DIR:-$TOOLS_DIR/dockge/stacks}"

# Erstelle Stacks-Verzeichnis falls nicht vorhanden
mkdir -p "$DOCKGE_STACKS_DIR"

print_info "Erstelle Dockge Container..."
print_info "Port: $DOCKGE_PORT"
print_info "Stacks-Verzeichnis: $DOCKGE_STACKS_DIR"
print_info "Dokumentation: https://dockge.kuma.pet/"

if docker run -d \
    --name "$DOCKGE_NAME" \
    --restart=always \
    -p "${DOCKGE_PORT}:5001" \
    -v /var/run/docker.sock:/var/run/docker.sock \
    -v "$DOCKGE_STACKS_DIR:/app/data/stacks" \
    -v "$TOOLS_DIR/dockge:/data" \
    -e DOCKGE_STACKS_DIR=/app/data/stacks \
    louislam/dockge:latest &>/dev/null; then
    print_success "Dockge Container erstellt und gestartet!"
    print_info "Warte auf Dockge-Bereitschaft..."
    sleep 3
else
    print_error "Dockge Container konnte nicht erstellt werden!"
    exit 1
fi

echo ""
print_success "Migration abgeschlossen!"
echo ""
print_section "Dockge ist jetzt verfügbar" "Hauptmenü" "Dockge"
echo -e "   URL:      ${GREEN}http://localhost:${DOCKGE_PORT}${NC}"
echo -e "   Container: ${BLUE}${DOCKGE_NAME}${NC}"
echo ""
echo -e "   ${CYAN}ℹ️  Info:${NC}"
echo -e "      Dockge ist eine moderne, schnellere Alternative zu Portainer"
echo -e "      Verwaltet Docker-Container und Compose-Stacks visuell"
echo -e "      Dokumentation: ${BLUE}https://dockge.kuma.pet/${NC}"
echo ""
echo -e "   💡 Befehle:"
echo -e "      ${YELLOW}./dockge.sh${NC}        → Dockge verwalten"
echo -e "      ${YELLOW}./dockge.sh open${NC}    → Im Browser öffnen"
echo ""
