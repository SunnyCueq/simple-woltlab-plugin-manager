#!/bin/bash

#################################################################
# WoltLab Development Tools - Setup Script
# 
# Interaktive oder vollautomatische Installation aller benötigten Tools
#
# @author      Sunny C
# @copyright   2026 Sunny C
#################################################################

set -e

# Verzeichnisse
TOOLS_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
MAIN_DIR="$(dirname "$TOOLS_DIR")"
ENV_FILE="$TOOLS_DIR/.env"
ENV_EXAMPLE="$TOOLS_DIR/.env.example"

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
        echo -e "${BLUE}║     ${CYAN}WoltLab Development Tools Setup${BLUE}              ║${NC}"
        echo -e "${BLUE}║                                                       ║${NC}"
        echo -e "${BLUE}╚═══════════════════════════════════════════════════════╝${NC}"
        echo ""
    }
    
    print_success() { echo -e "${GREEN}✓ $1${NC}"; }
    print_error() { echo -e "${RED}✗ $1${NC}"; }
    print_info() { echo -e "${YELLOW}→ $1${NC}"; }
fi

# Konfigurationsvariablen
INSTALL_DDEV=""
INSTALL_HEIDISQL=""
INSTALL_NODEJS=""
DOWNLOAD_WOLTLAB=""
WOLTLAB_PATH=""
USE_STANDARD_PASSWORDS=""
GENERATE_PASSWORDS=""
INIT_GIT=""
CREATE_SNAPSHOT=""
ERROR_HANDLING="ask"

# Funktion: Frage mit Standard-Wert
ask_yes_no() {
    local prompt="$1"
    local default="${2:-y}"
    local answer
    
    if [ "$MODE" = "auto" ]; then
        echo "$default"
        return
    fi
    
    while true; do
        if [ "$default" = "y" ]; then
            read -p "$(echo -e "${YELLOW}$prompt${NC} [Y/n]: ")" answer
        else
            read -p "$(echo -e "${YELLOW}$prompt${NC} [y/N]: ")" answer
        fi
        
        answer="${answer:-$default}"
        case "$answer" in
            [Yy]* ) echo "y"; return;;
            [Nn]* ) echo "n"; return;;
            * ) echo -e "${RED}Bitte Y oder N eingeben${NC}";;
        esac
    done
}

# Funktion: Frage mit Text-Eingabe
ask_text() {
    local prompt="$1"
    local default="$2"
    
    if [ "$MODE" = "auto" ]; then
        echo "$default"
        return
    fi
    
    read -p "$(echo -e "${YELLOW}$prompt${NC}${default:+ [$default]}: ")" answer
    echo "${answer:-$default}"
}

# Funktion: Prüfe ob Kommando verfügbar ist
command_exists() {
    command -v "$1" >/dev/null 2>&1
}

# Funktion: Installiere DDEV
install_ddev() {
    if command_exists ddev; then
        print_success "DDEV ist bereits installiert"
        return 0
    fi
    
    print_info "Installiere DDEV..."
    echo ""
    echo -e "${CYAN}ℹ️  DDEV ist eine lokale Entwicklungsumgebung für WoltLab${NC}"
    echo -e "${CYAN}   Dokumentation: ${BLUE}https://github.com/ddev/ddev${NC}"
    echo ""
    
    if curl -fsSL https://ddev.com/install.sh | bash; then
        print_success "DDEV installiert"
        echo ""
        echo -e "${CYAN}ℹ️  Nächste Schritte:${NC}"
        echo -e "   1. DDEV wird automatisch konfiguriert"
        echo -e "   2. Starte DDEV mit: ${YELLOW}cd tools/woltlab-dev && ddev start${NC}"
        echo -e "   3. Oder über das Menü: ${YELLOW}Option 4${NC}"
        return 0
    else
        print_error "DDEV Installation fehlgeschlagen"
        echo ""
        echo -e "${YELLOW}⚠️  Manuelle Installation:${NC}"
        echo -e "   ${BLUE}https://github.com/ddev/ddev${NC}"
        echo -e "   ${BLUE}https://ddev.readthedocs.io/en/stable/users/install/${NC}"
        return 1
    fi
}

# Funktion: Installiere HeidiSQL
install_heidisql() {
    if command_exists heidisql; then
        print_success "HeidiSQL ist bereits installiert"
        return 0
    fi
    
    print_info "Installiere HeidiSQL..."
    echo ""
    echo -e "${CYAN}ℹ️  HeidiSQL ist ein Datenbank-Verwaltungstool${NC}"
    echo -e "${CYAN}   Dokumentation: ${BLUE}https://www.heidisql.com/help.php${NC}"
    echo ""
    
    if command_exists pacman; then
        if sudo pacman -S --noconfirm heidisql heidisql-qt6 2>/dev/null; then
            print_success "HeidiSQL installiert"
            return 0
        fi
    elif command_exists apt; then
        if sudo apt update && sudo apt install -y heidisql 2>/dev/null; then
            print_success "HeidiSQL installiert"
            return 0
        fi
    elif command_exists yum; then
        if sudo yum install -y heidisql 2>/dev/null; then
            print_success "HeidiSQL installiert"
            return 0
        fi
    fi
    
    print_warning "HeidiSQL konnte nicht automatisch installiert werden"
    echo ""
    echo -e "${YELLOW}ℹ️  Manuelle Installation:${NC}"
    echo -e "   ${BLUE}https://www.heidisql.com/download.php${NC}"
    echo -e "   ${BLUE}https://www.heidisql.com/help.php${NC}"
    echo ""
    echo -e "${CYAN}ℹ️  Kein Problem! HeidiSQL ist optional.${NC}"
    echo -e "   Die Datenbank-Verbindung kann auch später konfiguriert werden."
    return 0  # Nicht kritisch, daher return 0
}

# Funktion: Installiere Node.js/npm
install_nodejs() {
    if command_exists node && command_exists npm; then
        print_success "Node.js/npm ist bereits installiert"
        return 0
    fi
    
    print_info "Installiere Node.js/npm..."
    
    if command_exists pacman; then
        sudo pacman -S --noconfirm nodejs npm
    elif command_exists apt; then
        sudo apt update && sudo apt install -y nodejs npm
    elif command_exists yum; then
        sudo yum install -y nodejs npm
    else
        print_error "Paket-Manager nicht erkannt. Bitte Node.js/npm manuell installieren."
        return 1
    fi
    
    print_success "Node.js/npm installiert"
}

# Funktion: Erstelle .env Datei
create_env_file() {
    if [ ! -f "$ENV_EXAMPLE" ]; then
        print_error ".env.example nicht gefunden!"
        return 1
    fi
    
    if [ -f "$ENV_FILE" ]; then
        print_warning ".env Datei existiert bereits"
        if [ "$(ask_yes_no "Überschreiben?" "n")" = "n" ]; then
            print_info "Überspringe .env Erstellung"
            return 0
        fi
    fi
    
    print_info "Erstelle .env Datei..."
    cp "$ENV_EXAMPLE" "$ENV_FILE"
    
    # Generiere Passwörter falls gewünscht
    local db_password="db"
    if [ "$GENERATE_PASSWORDS" = "y" ]; then
        db_password=$(openssl rand -base64 12 | tr -d "=+/" | cut -c1-16)
        local admin_password=$(openssl rand -base64 12 | tr -d "=+/" | cut -c1-16)
        
        sed -i "s/DB_PASSWORD=db/DB_PASSWORD=$db_password/" "$ENV_FILE"
        sed -i "s/WOLTLAB_ADMIN_PASSWORD=123456/WOLTLAB_ADMIN_PASSWORD=$admin_password/" "$ENV_FILE"
        sed -i "s/WOLTLAB_ADMIN_PASSWORD_CONFIRM=123456/WOLTLAB_ADMIN_PASSWORD_CONFIRM=$admin_password/" "$ENV_FILE"
        sed -i "s/HEIDISQL_PASSWORD=db/HEIDISQL_PASSWORD=$db_password/" "$ENV_FILE"
        
        print_success "Sichere Passwörter generiert"
    fi
    
    # Lade .env um Werte zu lesen
    source "$ENV_FILE" 2>/dev/null || true
    
    # Setze Standardwerte falls nicht in .env
    local heidisql_host="${HEIDISQL_HOST:-127.0.0.1}"
    local heidisql_port="${HEIDISQL_PORT:-3306}"
    local heidisql_user="${HEIDISQL_USER:-db}"
    local heidisql_password="${HEIDISQL_PASSWORD:-${DB_PASSWORD:-db}}"
    local heidisql_database="${HEIDISQL_DATABASE:-db}"
    
    # Versuche MySQL-Port aus DDEV zu ermitteln
    local ddev_dir="$TOOLS_DIR/woltlab-dev"
    if [ -d "$ddev_dir/.ddev" ] && command -v ddev &> /dev/null; then
        cd "$ddev_dir" 2>/dev/null || true
        if ddev describe &>/dev/null 2>&1; then
            # Versuche Port aus DDEV zu extrahieren
            local ddev_mysql_port=$(ddev describe 2>/dev/null | grep -oP 'db:3306 -> 127\.0\.0\.1:\K[0-9]+' | head -1)
            if [ -n "$ddev_mysql_port" ]; then
                heidisql_port="$ddev_mysql_port"
                print_info "DDEV MySQL-Port erkannt: $heidisql_port"
            fi
        fi
        cd - >/dev/null 2>&1 || true
    fi
    
    # Speichere HeidiSQL-Konfiguration automatisch
    print_info "Speichere HeidiSQL-Konfiguration..."
    if heidisql_save_config "WoltLab DDEV" "$heidisql_host" "$heidisql_port" "$heidisql_user" "$heidisql_password" "$heidisql_database"; then
        print_success "HeidiSQL-Konfiguration gespeichert"
    else
        print_warning "HeidiSQL-Konfiguration konnte nicht gespeichert werden (kann normal sein)"
    fi
    
    print_success ".env Datei erstellt"
}

# Funktion: Initialisiere DDEV-Projekt
init_ddev() {
    local ddev_dir="$TOOLS_DIR/woltlab-dev"
    
    if [ ! -d "$ddev_dir" ]; then
        print_info "Erstelle woltlab-dev Verzeichnis..."
        mkdir -p "$ddev_dir"
    fi
    
    cd "$ddev_dir"
    
    if [ -f ".ddev/config.yaml" ]; then
        print_success "DDEV-Projekt bereits initialisiert"
        return 0
    fi
    
    print_info "Initialisiere DDEV-Projekt..."
    ddev config --project-type=php --php-version=8.3 --project-name=woltlab
    print_success "DDEV-Projekt initialisiert"
}

# Funktion: Lade WoltLab Core
download_woltlab() {
    if [ "$DOWNLOAD_WOLTLAB" = "n" ]; then
        print_info "Überspringe WoltLab Download"
        return 0
    fi
    
    if [ -n "$WOLTLAB_PATH" ] && [ -f "$WOLTLAB_PATH" ]; then
        print_info "Verwende vorhandene WoltLab-Datei: $WOLTLAB_PATH"
        return 0
    fi
    
    print_info "Lade WoltLab Core herunter..."
    "$TOOLS_DIR/download-woltlab.sh" || {
        print_error "WoltLab Download fehlgeschlagen"
        return 1
    }
    print_success "WoltLab Core heruntergeladen"
}

# Funktion: Initialisiere Git Repository
init_git() {
    if [ "$INIT_GIT" = "n" ]; then
        print_info "Überspringe Git-Initialisierung"
        return 0
    fi
    
    if [ -d "$MAIN_DIR/.git" ]; then
        print_success "Git Repository bereits initialisiert"
        return 0
    fi
    
    print_info "Initialisiere Git Repository..."
    cd "$MAIN_DIR"
    git init
    print_success "Git Repository initialisiert"
}

# Funktion: Erstelle ersten Snapshot
create_snapshot() {
    if [ "$CREATE_SNAPSHOT" = "n" ]; then
        print_info "Überspringe Snapshot-Erstellung"
        return 0
    fi
    
    print_info "Erstelle ersten Snapshot..."
    "$TOOLS_DIR/snapshot-manager.sh" create || {
        print_warning "Snapshot-Erstellung fehlgeschlagen (kann später gemacht werden)"
        return 0
    }
    print_success "Snapshot erstellt"
}

# Funktion: Vorkonfiguration (interaktiv)
interactive_config() {
    print_header
    
    echo -e "${CYAN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo -e "${CYAN}  Vorkonfiguration${NC}"
    echo -e "${CYAN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo ""
    
    INSTALL_DDEV=$(ask_yes_no "DDEV installieren?" "y")
    INSTALL_HEIDISQL=$(ask_yes_no "HeidiSQL installieren?" "y")
    INSTALL_NODEJS=$(ask_yes_no "Node.js/npm installieren?" "y")
    
    echo ""
    DOWNLOAD_WOLTLAB=$(ask_yes_no "WoltLab Core automatisch herunterladen?" "y")
    if [ "$DOWNLOAD_WOLTLAB" = "n" ]; then
        WOLTLAB_PATH=$(ask_text "Pfad zur WoltLab-Datei:" "")
    fi
    
    echo ""
    USE_STANDARD_PASSWORDS=$(ask_yes_no "Standard-Passwörter verwenden?" "y")
    if [ "$USE_STANDARD_PASSWORDS" = "n" ]; then
        GENERATE_PASSWORDS=$(ask_yes_no "Sichere Passwörter generieren?" "y")
    fi
    
    echo ""
    INIT_GIT=$(ask_yes_no "Git Repository initialisieren?" "y")
    CREATE_SNAPSHOT=$(ask_yes_no "Snapshot nach Installation erstellen?" "y")
    
    echo ""
    echo -e "${YELLOW}Bei Fehlern:${NC}"
    echo "  1) Stoppen"
    echo "  2) Warnen und fortfahren"
    echo "  3) Immer fragen (Standard)"
    ERROR_HANDLING=$(ask_text "Wähle Option" "3")
}

# Funktion: Zusammenfassung anzeigen
show_summary() {
    print_header
    
    echo -e "${CYAN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo -e "${CYAN}  Installations-Zusammenfassung${NC}"
    echo -e "${CYAN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo ""
    
    echo -e "  DDEV:              ${GREEN}$([ "$INSTALL_DDEV" = "y" ] && echo "Ja" || echo "Nein")${NC}"
    echo -e "  HeidiSQL:          ${GREEN}$([ "$INSTALL_HEIDISQL" = "y" ] && echo "Ja" || echo "Nein")${NC}"
    echo -e "  Node.js/npm:       ${GREEN}$([ "$INSTALL_NODEJS" = "y" ] && echo "Ja" || echo "Nein")${NC}"
    echo -e "  WoltLab Download:  ${GREEN}$([ "$DOWNLOAD_WOLTLAB" = "y" ] && echo "Ja" || echo "Nein")${NC}"
    echo -e "  Passwörter:        ${GREEN}$([ "$GENERATE_PASSWORDS" = "y" ] && echo "Generiert" || echo "Standard")${NC}"
    echo -e "  Git init:          ${GREEN}$([ "$INIT_GIT" = "y" ] && echo "Ja" || echo "Nein")${NC}"
    echo -e "  Snapshot:          ${GREEN}$([ "$CREATE_SNAPSHOT" = "y" ] && echo "Ja" || echo "Nein")${NC}"
    echo ""
    
    if [ "$MODE" != "auto" ]; then
        if [ "$(ask_yes_no "Installation starten?" "y")" = "n" ]; then
            print_info "Installation abgebrochen"
            exit 0
        fi
    fi
}

# Funktion: Fehlerbehandlung
handle_error() {
    local error_msg="$1"
    
    case "$ERROR_HANDLING" in
        1) print_error "$error_msg"; exit 1;;
        2) print_warning "$error_msg"; return 0;;
        3) 
            print_error "$error_msg"
            if [ "$(ask_yes_no "Fortfahren?" "n")" = "n" ]; then
                exit 1
            fi
            ;;
    esac
}

# Hauptfunktion: Installation
run_installation() {
    print_header
    
    echo -e "${CYAN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo -e "${CYAN}  Installation${NC}"
    echo -e "${CYAN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo ""
    
    # DDEV
    if [ "$INSTALL_DDEV" = "y" ]; then
        install_ddev || handle_error "DDEV Installation fehlgeschlagen"
    fi
    
    # HeidiSQL
    if [ "$INSTALL_HEIDISQL" = "y" ]; then
        install_heidisql || handle_error "HeidiSQL Installation fehlgeschlagen"
    fi
    
    # Node.js/npm
    if [ "$INSTALL_NODEJS" = "y" ]; then
        install_nodejs || handle_error "Node.js/npm Installation fehlgeschlagen"
    fi
    
    # .env Datei
    create_env_file || handle_error ".env Datei konnte nicht erstellt werden"
    
    # DDEV initialisieren
    init_ddev || handle_error "DDEV-Initialisierung fehlgeschlagen"
    
    # WoltLab Download
    download_woltlab || handle_error "WoltLab Download fehlgeschlagen"
    
    # Git initialisieren
    init_git || handle_error "Git-Initialisierung fehlgeschlagen"
    
    # Snapshot erstellen
    create_snapshot || handle_error "Snapshot-Erstellung fehlgeschlagen"
    
    echo ""
    print_success "Installation abgeschlossen!"
    echo ""
    print_info "Nächste Schritte:"
    echo "  1. Starte DDEV: ./tools/start-ddev.sh"
    echo "  2. Installiere WoltLab im Browser"
    echo "  3. Erstelle Snapshot: ./tools/snapshot-manager.sh"
}

# Hauptprogramm
print_header

echo -e "${CYAN}WoltLab Development Tools - Setup${NC}"
echo ""
echo "Wähle Modus:"
echo "  1) Vollautomatisch (Standard-Werte)"
echo "  2) Interaktiv (Vorkonfiguration)"
echo ""

read -p "$(echo -e "${YELLOW}Modus wählen${NC} [1/2]: ")" mode_choice
mode_choice="${mode_choice:-1}"

case "$mode_choice" in
    1)
        MODE="auto"
        INSTALL_DDEV="y"
        INSTALL_HEIDISQL="y"
        INSTALL_NODEJS="y"
        DOWNLOAD_WOLTLAB="y"
        USE_STANDARD_PASSWORDS="y"
        INIT_GIT="y"
        CREATE_SNAPSHOT="y"
        ERROR_HANDLING="ask"
        ;;
    2)
        MODE="interactive"
        interactive_config
        ;;
    *)
        print_error "Ungültige Auswahl"
        exit 1
        ;;
esac

show_summary
run_installation
