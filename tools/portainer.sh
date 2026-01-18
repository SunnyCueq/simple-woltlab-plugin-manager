#!/bin/bash

#################################################################
# Portainer Management Script
# Pfad: tools/portainer.sh
# 
# Verwaltet Portainer Container für Docker-Management
# 
# Usage:
#   ./portainer.sh        → Startet Portainer/zeigt Status
#   ./portainer.sh start  → Startet Portainer
#   ./portainer.sh stop   → Stoppt Portainer
#   ./portainer.sh restart → Startet Portainer neu
#   ./portainer.sh status → Zeigt Portainer Status
#   ./portainer.sh open   → Öffnet Portainer im Browser
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
    
    print_header() {
        clear
        echo -e "${BLUE}╔═══════════════════════════════════════════════════════╗${NC}"
        echo -e "${BLUE}║                                                       ║${NC}"
        echo -e "${BLUE}║     ${CYAN}Portainer - Container Management${BLUE}              ║${NC}"
        echo -e "${BLUE}║                                                       ║${NC}"
        echo -e "${BLUE}╚═══════════════════════════════════════════════════════╝${NC}"
        echo ""
    }
    
    print_success() { echo -e "${GREEN}✓ $1${NC}"; }
    print_error() { echo -e "${RED}✗ $1${NC}"; }
    print_info() { echo -e "${YELLOW}→ $1${NC}"; }
    print_warning() { echo -e "${YELLOW}⚠ $1${NC}"; }
fi

# Lade .env falls vorhanden
if [ -f "$ENV_FILE" ]; then
    source "$ENV_FILE" 2>/dev/null || true
fi

# Portainer-Einstellungen
PORTAINER_NAME="${PORTAINER_NAME:-portainer}"
PORTAINER_PORT="${PORTAINER_PORT:-9000}"
PORTAINER_IMAGE="${PORTAINER_IMAGE:-portainer/portainer-ce:latest}"
PORTAINER_DATA_VOLUME="${PORTAINER_DATA_VOLUME:-portainer_data}"

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

# Funktion: Portainer Status prüfen
portainer_status() {
    if docker ps -a --format "{{.Names}}" | grep -q "^${PORTAINER_NAME}$"; then
        if docker ps --format "{{.Names}}" | grep -q "^${PORTAINER_NAME}$"; then
            echo "running"
        else
            echo "stopped"
        fi
    else
        echo "not_exists"
    fi
}

# Funktion: Portainer starten
portainer_start() {
    local status=$(portainer_status)
    
    if [ "$status" = "running" ]; then
        print_success "Portainer läuft bereits!"
        return 0
    fi
    
    print_info "Starte Portainer..."
    
    if [ "$status" = "stopped" ]; then
        # Container existiert, aber ist gestoppt
        if docker start "$PORTAINER_NAME" &>/dev/null; then
            print_success "Portainer gestartet!"
            return 0
        else
            print_error "Portainer konnte nicht gestartet werden!"
            return 1
        fi
    fi
    
    # Container existiert nicht, erstelle ihn
    # Basierend auf Portainer-Dokumentation: https://docs.portainer.io/
    print_info "Erstelle Portainer Container..."
    print_info "Verwende Portainer CE (Community Edition)"
    print_info "Dokumentation: https://docs.portainer.io/"
    
    # Prüfe ob Port bereits belegt ist
    if command -v ss &> /dev/null; then
        if ss -tuln | grep -q ":${PORTAINER_PORT} "; then
            print_warning "Port ${PORTAINER_PORT} ist bereits belegt!"
            print_info "Prüfe ob Portainer bereits läuft..."
            if docker ps --format "{{.Names}}" | grep -q "portainer"; then
                print_success "Portainer läuft bereits unter anderem Namen!"
                return 0
            fi
        fi
    fi
    
    # Erstelle Portainer Container (CE = Community Edition)
    # Volumes: /var/run/docker.sock für Docker-API, /data für Persistenz
    if docker run -d \
        -p "${PORTAINER_PORT}:9000" \
        -p 9443:9443 \
        --name "$PORTAINER_NAME" \
        --restart=always \
        -v /var/run/docker.sock:/var/run/docker.sock \
        -v "${PORTAINER_DATA_VOLUME}:/data" \
        "$PORTAINER_IMAGE" &>/dev/null; then
        print_success "Portainer Container erstellt und gestartet!"
        print_info "Warte auf Portainer-Bereitschaft..."
        sleep 3
        return 0
    else
        print_error "Portainer Container konnte nicht erstellt werden!"
        print_info "Prüfe Docker-Logs: docker logs $PORTAINER_NAME"
        return 1
    fi
}

# Funktion: Portainer stoppen
portainer_stop() {
    local status=$(portainer_status)
    
    if [ "$status" = "stopped" ] || [ "$status" = "not_exists" ]; then
        print_warning "Portainer läuft nicht!"
        return 0
    fi
    
    print_info "Stoppe Portainer..."
    
    if docker stop "$PORTAINER_NAME" &>/dev/null; then
        print_success "Portainer gestoppt!"
        return 0
    else
        print_error "Portainer konnte nicht gestoppt werden!"
        return 1
    fi
}

# Funktion: Portainer entfernen
portainer_remove() {
    local status=$(portainer_status)
    
    if [ "$status" = "not_exists" ]; then
        print_warning "Portainer Container existiert nicht!"
        return 0
    fi
    
    print_info "Entferne Portainer Container..."
    
    if [ "$status" = "running" ]; then
        docker stop "$PORTAINER_NAME" &>/dev/null
    fi
    
    if docker rm "$PORTAINER_NAME" &>/dev/null; then
        print_success "Portainer Container entfernt!"
        return 0
    else
        print_error "Portainer Container konnte nicht entfernt werden!"
        return 1
    fi
}

# Funktion: Portainer im Browser öffnen
portainer_open() {
    local status=$(portainer_status)
    
    if [ "$status" != "running" ]; then
        print_error "Portainer läuft nicht!"
        print_info "Starte Portainer zuerst mit: ./portainer.sh start"
        return 1
    fi
    
    local url="http://localhost:${PORTAINER_PORT}"
    print_info "Öffne Portainer im Browser: $url"
    
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
        firefox "$url" > /dev/null 2>&1 & && print_success "Browser geöffnet" || {
            print_warning "Browser konnte nicht automatisch geöffnet werden"
            echo "Öffne manuell: $url"
        }
    else
        print_warning "Kein Browser gefunden - öffne manuell: $url"
        return 1
    fi
}

# Funktion: Portainer Informationen anzeigen
portainer_info() {
    local status=$(portainer_status)
    
    echo -e "${CYAN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo -e "${CYAN}  Portainer - Container Management${NC}"
    echo -e "${CYAN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo ""
    
    echo -e "   Status:     ${BLUE}$status${NC}"
    echo -e "   Container:  ${BLUE}${PORTAINER_NAME}${NC}"
    echo -e "   Port:       ${BLUE}${PORTAINER_PORT}${NC}"
    echo -e "   Image:      ${BLUE}${PORTAINER_IMAGE}${NC}"
    echo -e "   Edition:    ${BLUE}Community Edition (CE)${NC}"
    echo ""
    
    if [ "$status" = "running" ]; then
        local url="http://localhost:${PORTAINER_PORT}"
        local https_url="https://localhost:9443"
        echo -e "   URLs:"
        echo -e "      HTTP:  ${GREEN}${url}${NC}"
        echo -e "      HTTPS: ${GREEN}${https_url}${NC}"
        echo ""
        echo -e "   ${CYAN}ℹ️  Info:${NC}"
        echo -e "      Portainer verwaltet Docker-Container visuell"
        echo -e "      DDEV-Container werden automatisch erkannt"
        echo -e "      Dokumentation: ${BLUE}https://docs.portainer.io/${NC}"
        echo ""
        echo -e "   💡 Befehle:"
        echo -e "      ${YELLOW}./portainer.sh stop${NC}     → Stoppe Portainer"
        echo -e "      ${YELLOW}./portainer.sh restart${NC}  → Starte neu"
        echo -e "      ${YELLOW}./portainer.sh open${NC}     → Öffne im Browser"
    elif [ "$status" = "stopped" ]; then
        echo -e "   💡 Befehle:"
        echo -e "      ${YELLOW}./portainer.sh start${NC}    → Starte Portainer"
        echo -e "      ${YELLOW}./portainer.sh remove${NC}   → Entferne Container"
    else
        echo -e "   ${CYAN}ℹ️  Info:${NC}"
        echo -e "      Portainer bietet eine Web-Oberfläche für Docker"
        echo -e "      Verwaltet DDEV und andere Container visuell"
        echo -e "      Dokumentation: ${BLUE}https://docs.portainer.io/${NC}"
        echo ""
        echo -e "   💡 Befehle:"
        echo -e "      ${YELLOW}./portainer.sh start${NC}    → Erstelle und starte Portainer"
    fi
    echo ""
}

# Kommando verarbeiten
COMMAND="${1:-status}"

case "$COMMAND" in
    start)
        print_header
        portainer_start
        echo ""
        portainer_info
        ;;
    
    stop)
        print_header
        portainer_stop
        echo ""
        portainer_info
        ;;
    
    restart)
        print_header
        print_info "Starte Portainer neu..."
        portainer_stop
        sleep 1
        portainer_start
        echo ""
        portainer_info
        ;;
    
    status)
        print_header
        portainer_info
        ;;
    
    open)
        print_header
        portainer_open
        echo ""
        ;;
    
    remove)
        print_header
        print_warning "Dies wird den Portainer Container entfernen!"
        read -p "Fortfahren? (j/N): " confirm
        if [[ "$confirm" =~ ^[Jj]$ ]]; then
            portainer_remove
        else
            print_info "Abgebrochen"
        fi
        echo ""
        ;;
    
    *)
        print_error "Unbekanntes Kommando: $COMMAND"
        echo ""
        echo "Verfügbare Kommandos:"
        echo "  start     → Startet Portainer (Standard)"
        echo "  stop      → Stoppt Portainer"
        echo "  restart   → Startet Portainer neu"
        echo "  status    → Zeigt Portainer Status"
        echo "  open      → Öffnet Portainer im Browser"
        echo "  remove    → Entfernt Portainer Container"
        exit 1
        ;;
esac
