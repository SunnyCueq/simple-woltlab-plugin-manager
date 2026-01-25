#!/bin/bash

#################################################################
# Dockge Management Script
# Pfad: tools/dockge.sh
# 
# Verwaltet Dockge Container für Docker-Management
# Dockge ist eine moderne, schnellere Alternative zu Portainer
# 
# Usage:
#   ./dockge.sh        → Startet Dockge/zeigt Status
#   ./dockge.sh start  → Startet Dockge
#   ./dockge.sh stop   → Stoppt Dockge
#   ./dockge.sh restart → Startet Dockge neu
#   ./dockge.sh status → Zeigt Dockge Status
#   ./dockge.sh open   → Öffnet Dockge im Browser
#################################################################

set -e

# Verzeichnisse
TOOLS_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ENV_FILE="$TOOLS_DIR/.env"

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

# Lade .env falls vorhanden
if [ -f "$ENV_FILE" ]; then
    source "$ENV_FILE" 2>/dev/null || true
fi

# Dockge-Einstellungen
DOCKGE_NAME="${DOCKGE_NAME:-dockge}"
DOCKGE_PORT="${DOCKGE_PORT:-5001}"
DOCKGE_IMAGE="${DOCKGE_IMAGE:-louislam/dockge:latest}"
DOCKGE_STACKS_DIR="${DOCKGE_STACKS_DIR:-$TOOLS_DIR/dockge/stacks}"
DOCKGE_DATA_DIR="${DOCKGE_DATA_DIR:-$TOOLS_DIR/dockge}"

# Prüfe ob Docker installiert ist
if ! command -v docker &> /dev/null; then
    print_error "Docker ist nicht installiert!"
    echo "Installiere Docker mit: sudo pacman -S docker"
    exit 1
fi

# Prüfe ob Docker läuft
if ! docker info &>/dev/null; then
    print_error "Docker läuft nicht!"
    echo "Starte Docker mit: sudo systemctl start docker"
    exit 1
fi

# Funktion: Dockge Status prüfen
dockge_status() {
    if docker ps -a --format "{{.Names}}" | grep -q "^${DOCKGE_NAME}$"; then
        if docker ps --format "{{.Names}}" | grep -q "^${DOCKGE_NAME}$"; then
            echo "running"
        else
            echo "stopped"
        fi
    else
        echo "not_exists"
    fi
}

# Funktion: Dockge starten
dockge_start() {
    local status=$(dockge_status)
    
    if [ "$status" = "running" ]; then
        print_success "Dockge läuft bereits!"
        return 0
    fi
    
    print_info "Starte Dockge..."
    
    if [ "$status" = "stopped" ]; then
        # Container existiert, aber ist gestoppt
        if docker start "$DOCKGE_NAME" &>/dev/null; then
            print_success "Dockge gestartet!"
            return 0
        else
            print_error "Dockge konnte nicht gestartet werden!"
            return 1
        fi
    fi
    
    # Container existiert nicht, erstelle ihn
    print_info "Erstelle Dockge Container..."
    print_info "Dockge ist eine moderne, schnellere Alternative zu Portainer"
    print_info "Dokumentation: https://dockge.kuma.pet/"
    
    # Erstelle Verzeichnisse falls nicht vorhanden
    mkdir -p "$DOCKGE_STACKS_DIR"
    mkdir -p "$DOCKGE_DATA_DIR"
    
    # Prüfe ob Port bereits belegt ist
    if command -v ss &> /dev/null; then
        if ss -tuln | grep -q ":${DOCKGE_PORT} "; then
            print_warning "Port ${DOCKGE_PORT} ist bereits belegt!"
            print_info "Prüfe ob Dockge bereits läuft..."
            if docker ps --format "{{.Names}}" | grep -q "dockge"; then
                print_success "Dockge läuft bereits unter anderem Namen!"
                return 0
            fi
        fi
    fi
    
    # Erstelle Dockge Container
    # Dockge verwendet Port 5001 (nicht 9000 wie Portainer)
    # Stacks-Verzeichnis für Docker Compose Stacks
    if docker run -d \
        --name "$DOCKGE_NAME" \
        --restart=always \
        -p "${DOCKGE_PORT}:5001" \
        -v /var/run/docker.sock:/var/run/docker.sock \
        -v "$DOCKGE_STACKS_DIR:/app/data/stacks" \
        -v "$DOCKGE_DATA_DIR:/data" \
        -e DOCKGE_STACKS_DIR=/app/data/stacks \
        "$DOCKGE_IMAGE" &>/dev/null; then
        print_success "Dockge Container erstellt und gestartet!"
        print_info "Warte auf Dockge-Bereitschaft..."
        sleep 3
        return 0
    else
        print_error "Dockge Container konnte nicht erstellt werden!"
        print_info "Prüfe Docker-Logs: docker logs $DOCKGE_NAME"
        return 1
    fi
}

# Funktion: Dockge stoppen
dockge_stop() {
    local status=$(dockge_status)
    
    if [ "$status" = "stopped" ] || [ "$status" = "not_exists" ]; then
        print_warning "Dockge läuft nicht!"
        return 0
    fi
    
    print_info "Stoppe Dockge..."
    
    if docker stop "$DOCKGE_NAME" &>/dev/null; then
        print_success "Dockge gestoppt!"
        return 0
    else
        print_error "Dockge konnte nicht gestoppt werden!"
        return 1
    fi
}

# Funktion: Dockge entfernen
dockge_remove() {
    local status=$(dockge_status)
    
    if [ "$status" = "not_exists" ]; then
        print_warning "Dockge Container existiert nicht!"
        return 0
    fi
    
    print_info "Entferne Dockge Container..."
    
    if [ "$status" = "running" ]; then
        docker stop "$DOCKGE_NAME" &>/dev/null
    fi
    
    if docker rm "$DOCKGE_NAME" &>/dev/null; then
        print_success "Dockge Container entfernt!"
        return 0
    else
        print_error "Dockge Container konnte nicht entfernt werden!"
        return 1
    fi
}

# Funktion: Dockge im Browser öffnen
dockge_open() {
    local status=$(dockge_status)
    
    if [ "$status" != "running" ]; then
        print_error "Dockge läuft nicht!"
        print_info "Starte Dockge zuerst mit: ./dockge.sh start"
        return 1
    fi
    
    local url="http://localhost:${DOCKGE_PORT}"
    print_info "Öffne Dockge im Browser: $url"
    
    # Verwende open_browser wenn verfügbar (aus common.sh), sonst Fallbacks
    if type open_browser &>/dev/null; then
        if open_browser "$url"; then
            print_success "Browser geöffnet"
            return 0
        else
            print_warning "Browser konnte nicht automatisch geöffnet werden"
            echo "Öffne manuell: $url"
            return 1
        fi
    elif command -v xdg-open &> /dev/null; then
        xdg-open "$url" &>/dev/null && print_success "Browser geöffnet" || {
            print_warning "Browser konnte nicht automatisch geöffnet werden"
            echo "Öffne manuell: $url"
        }
    elif command -v open &> /dev/null; then
        open "$url" &>/dev/null && print_success "Browser geöffnet" || {
            print_warning "Browser konnte nicht automatisch geöffnet werden"
            echo "Öffne manuell: $url"
        }
    elif command -v firefox &> /dev/null; then
        firefox "$url" > /dev/null 2>&1 &
        if [ $? -eq 0 ]; then
            print_success "Browser geöffnet"
        else
            print_warning "Browser konnte nicht automatisch geöffnet werden"
            echo "Öffne manuell: $url"
        fi
    else
        print_warning "Kein Browser gefunden - öffne manuell: $url"
        return 1
    fi
}

# Funktion: Dockge Informationen anzeigen
dockge_info() {
    local status=$(dockge_status)
    
    print_section "Dockge - Container Management" "Hauptmenü" "Dockge"
    
    echo -e "   Status:     ${BLUE}$status${NC}"
    echo -e "   Container:  ${BLUE}${DOCKGE_NAME}${NC}"
    echo -e "   Port:       ${BLUE}${DOCKGE_PORT}${NC}"
    echo -e "   Image:      ${BLUE}${DOCKGE_IMAGE}${NC}"
    echo ""
    
    if [ "$status" = "running" ]; then
        local url="http://localhost:${DOCKGE_PORT}"
        echo -e "   URL:        ${GREEN}${url}${NC}"
        echo ""
        echo -e "   ${CYAN}ℹ️  Info:${NC}"
        echo -e "      Dockge ist eine moderne, schnellere Alternative zu Portainer"
        echo -e "      Verwaltet Docker-Container und Compose-Stacks visuell"
        echo -e "      DDEV-Container werden automatisch erkannt"
        echo -e "      Dokumentation: ${BLUE}https://dockge.kuma.pet/${NC}"
        echo ""
        echo -e "   💡 Befehle:"
        echo -e "      ${YELLOW}./dockge.sh stop${NC}     → Stoppe Dockge"
        echo -e "      ${YELLOW}./dockge.sh restart${NC}  → Starte neu"
        echo -e "      ${YELLOW}./dockge.sh open${NC}      → Öffne im Browser"
    elif [ "$status" = "stopped" ]; then
        echo -e "   💡 Befehle:"
        echo -e "      ${YELLOW}./dockge.sh start${NC}    → Starte Dockge"
        echo -e "      ${YELLOW}./dockge.sh remove${NC}   → Entferne Container"
    else
        echo -e "   ${CYAN}ℹ️  Info:${NC}"
        echo -e "      Dockge bietet eine moderne Web-Oberfläche für Docker"
        echo -e "      Verwaltet DDEV und andere Container visuell"
        echo -e "      Schneller und moderner als Portainer"
        echo -e "      Dokumentation: ${BLUE}https://dockge.kuma.pet/${NC}"
        echo ""
        echo -e "   💡 Befehle:"
        echo -e "      ${YELLOW}./dockge.sh start${NC}    → Erstelle und starte Dockge"
    fi
    echo ""
}

# Kommando verarbeiten
COMMAND="${1:-status}"

case "$COMMAND" in
    start)
        print_section "Dockge - Container Management" "Hauptmenü" "Dockge"
        dockge_start
        echo ""
        dockge_info
        ;;
    
    stop)
        print_section "Dockge - Container Management" "Hauptmenü" "Dockge"
        dockge_stop
        echo ""
        dockge_info
        ;;
    
    restart)
        print_section "Dockge - Container Management" "Hauptmenü" "Dockge"
        print_info "Starte Dockge neu..."
        dockge_stop
        sleep 1
        dockge_start
        echo ""
        dockge_info
        ;;
    
    status)
        print_section "Dockge - Container Management" "Hauptmenü" "Dockge"
        dockge_info
        ;;
    
    open)
        print_section "Dockge - Container Management" "Hauptmenü" "Dockge"
        dockge_open
        echo ""
        ;;
    
    remove)
        print_section "Dockge - Container Management" "Hauptmenü" "Dockge"
        print_warning "Dies wird den Dockge Container entfernen!"
        if ask_yes_no "Fortfahren?" "N"; then
            dockge_remove
        else
            print_info "Abgebrochen"
        fi
        echo ""
        ;;
    
    *)
        print_error "Unbekanntes Kommando: $COMMAND"
        echo ""
        echo "Verfügbare Kommandos:"
        echo "  start     → Startet Dockge (Standard)"
        echo "  stop      → Stoppt Dockge"
        echo "  restart   → Startet Dockge neu"
        echo "  status    → Zeigt Dockge Status"
        echo "  open      → Öffnet Dockge im Browser"
        echo "  remove    → Entfernt Dockge Container"
        exit 1
        ;;
esac
