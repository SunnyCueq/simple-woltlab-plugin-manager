#!/usr/bin/env bash

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
        print_info "╔═══════════════════════════════════════════════════════╗${NC}"
        print_info "║                                                       ║${NC}"
        print_info "║     ${CYAN}WoltLab Development Tools Setup${BLUE}              ║${NC}"
        print_info "║                                                       ║${NC}"
        print_info "╚═══════════════════════════════════════════════════════╝${NC}"
        echo ""
    }
    
    print_success() { print_success "$1${NC}"; }
    print_error() { echo -e "${RED}✗ $1${NC}"; }
    print_info() { echo -e "${YELLOW}→ $1${NC}"; }
fi

# Konfigurationsvariablen
INSTALL_DDEV=""
INSTALL_PHPMYADMIN=""
INSTALL_NODEJS=""
DOWNLOAD_WOLTLAB=""
WOLTLAB_PATH=""
USE_STANDARD_PASSWORDS=""
GENERATE_PASSWORDS=""
INIT_GIT=""
CREATE_SNAPSHOT=""
ERROR_HANDLING="ask"

# Funktion: Frage mit Standard-Wert (J/N)
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
            read -p "$(echo -e "${YELLOW}$prompt${NC} [J/n]: ")" answer
        else
            read -p "$(echo -e "${YELLOW}$prompt${NC} [j/N]: ")" answer
        fi

        answer="${answer:-$default}"
        case "$answer" in
            [JjYy]* ) echo "y"; return;;
            [Nn]* ) echo "n"; return;;
            * ) echo -e "${RED}Bitte J oder N eingeben${NC}";;
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

# Funktion: Installiere phpMyAdmin über DDEV
install_phpmyadmin() {
    local tools_dir="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
    local ddev_dir="$tools_dir/woltlab-dev"
    
    # Prüfe ob DDEV installiert ist
    if ! command_exists ddev; then
        print_warning "DDEV ist nicht installiert. phpMyAdmin benötigt DDEV."
        echo -e "${CYAN}ℹ️  Installiere zuerst DDEV, dann kann phpMyAdmin installiert werden.${NC}"
        return 1
    fi
    
    # Prüfe ob DDEV-Projekt existiert
    if [ ! -d "$ddev_dir/.ddev" ]; then
        print_warning "DDEV-Projekt ist noch nicht initialisiert."
        echo -e "${CYAN}ℹ️  Initialisiere zuerst DDEV, dann kann phpMyAdmin installiert werden.${NC}"
        return 1
    fi
    
    # Prüfe ob phpMyAdmin bereits installiert ist
    cd "$ddev_dir" 2>/dev/null || {
        print_error "Konnte nicht ins DDEV-Verzeichnis wechseln: $ddev_dir"
        return 1
    }
    
    # Prüfe ob phpMyAdmin-Add-on bereits konfiguriert ist
    if [ -f ".ddev/commands/web/phpmyadmin" ] || ddev describe 2>/dev/null | grep -q "phpmyadmin\|phpMyAdmin"; then
        print_success "phpMyAdmin ist bereits installiert"
        cd - > /dev/null 2>&1 || true
        return 0
    fi
    
    print_info "Installiere phpMyAdmin über DDEV..."
    echo ""
    echo -e "${CYAN}ℹ️  phpMyAdmin ist ein Web-basiertes Datenbank-Verwaltungstool${NC}"
    echo -e "${CYAN}   Verfügbar unter: ${BLUE}https://woltlab.ddev.site/phpmyadmin${NC}"
    echo ""
    
    # Installiere phpMyAdmin als DDEV Add-on
    if ddev add-on get ddev/ddev-phpmyadmin 2>/dev/null; then
        print_success "phpMyAdmin Add-on installiert"
        
        # Starte DDEV neu, damit phpMyAdmin verfügbar wird
        print_info "Starte DDEV neu, damit phpMyAdmin verfügbar wird..."
        if ddev restart 2>/dev/null; then
            print_success "DDEV neu gestartet - phpMyAdmin ist jetzt verfügbar"
            echo ""
            echo -e "${GREEN}✓${NC} phpMyAdmin URL: ${BLUE}https://woltlab.ddev.site/phpmyadmin${NC}"
            cd - > /dev/null 2>&1 || true
            return 0
        else
            print_warning "DDEV konnte nicht automatisch neu gestartet werden"
            echo -e "${YELLOW}ℹ️  Starte DDEV manuell neu: ${BLUE}ddev restart${NC}"
            cd - > /dev/null 2>&1 || true
            return 0  # Nicht kritisch, Installation war erfolgreich
        fi
    else
        print_warning "phpMyAdmin konnte nicht automatisch installiert werden"
        echo ""
        echo -e "${YELLOW}ℹ️  Manuelle Installation:${NC}"
        echo -e "   ${BLUE}cd tools/woltlab-dev${NC}"
        echo -e "   ${BLUE}ddev add-on get ddev/ddev-phpmyadmin${NC}"
        echo -e "   ${BLUE}ddev restart${NC}"
        echo ""
        echo -e "${CYAN}ℹ️  Kein Problem! phpMyAdmin ist optional.${NC}"
        echo -e "   Die Installation kann auch später durchgeführt werden."
        cd - > /dev/null 2>&1 || true
        return 0  # Nicht kritisch, daher return 0
    fi
}

# Funktion: Installiere Node.js/npm
install_nodejs() {
    if command_exists node && command_exists npm; then
        print_success "Node.js/npm ist bereits installiert"
        return 0
    fi
    
    print_info "Installiere Node.js/npm..."
    
    # Lade common.sh für Plattform-Erkennung (falls verfügbar)
    local tools_dir="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
    if [ -f "$tools_dir/common.sh" ]; then
        source "$tools_dir/common.sh" 2>/dev/null || true
    fi
    
    # Verwende erkannten Package Manager oder erkenne neu
    local pkg_mgr="${PACKAGE_MANAGER:-$(detect_package_manager 2>/dev/null || echo "unknown")}"
    
    case "$pkg_mgr" in
        pacman)
            sudo pacman -S --noconfirm nodejs npm
            ;;
        apt|apt-get)
            sudo apt update && sudo apt install -y nodejs npm
            ;;
        yum|dnf)
            sudo yum install -y nodejs npm 2>/dev/null || sudo dnf install -y nodejs npm
            ;;
        brew)
            brew install node npm
            ;;
        pkg)
            sudo pkg install -y node npm
            ;;
        *)
            print_error "Paket-Manager nicht erkannt. Bitte Node.js/npm manuell installieren."
            echo -e "${YELLOW}Installation:${NC}"
            echo -e "  ${CYAN}•${NC} Linux: ${BLUE}sudo apt install nodejs npm${NC} (Debian/Ubuntu)"
            echo -e "  ${CYAN}•${NC} Linux: ${BLUE}sudo pacman -S nodejs npm${NC} (Arch)"
            echo -e "  ${CYAN}•${NC} macOS: ${BLUE}brew install node npm${NC} (Homebrew)"
            echo -e "  ${CYAN}•${NC} WSL2: ${BLUE}sudo apt install nodejs npm${NC}"
            return 1
            ;;
    esac
    
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
        # phpMyAdmin verwendet DDEV MySQL-Zugangsdaten (keine separate Konfiguration nötig)
        
        print_success "Sichere Passwörter generiert"
    fi
    
    # phpMyAdmin wird über DDEV bereitgestellt und verwendet automatisch die DDEV MySQL-Zugangsdaten
    # Keine separate Konfiguration nötig - phpMyAdmin ist verfügbar unter https://woltlab.ddev.site/phpmyadmin
    print_info "phpMyAdmin wird über DDEV bereitgestellt (falls installiert)"
    print_info "Zugangsdaten: Benutzer=db, Passwort=db, Datenbank=db"
    
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
    
    print_section "Vorkonfiguration" "Hauptmenü" "Setup"
    
    INSTALL_DDEV=$(ask_yes_no "DDEV installieren?" "y")
    INSTALL_PHPMYADMIN=$(ask_yes_no "phpMyAdmin installieren? (über DDEV)" "y")
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
    
    print_section "Installations-Zusammenfassung" "Hauptmenü" "Setup"
    
    echo -e "  DDEV:              ${GREEN}$([ "$INSTALL_DDEV" = "y" ] && echo "Ja" || echo "Nein")${NC}"
    echo -e "  phpMyAdmin:        ${GREEN}$([ "$INSTALL_PHPMYADMIN" = "y" ] && echo "Ja" || echo "Nein")${NC}"
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
    
    print_section "Installation" "Hauptmenü" "Setup"
    
    # DDEV
    if [ "$INSTALL_DDEV" = "y" ]; then
        install_ddev || handle_error "DDEV Installation fehlgeschlagen"
    fi
    
    # phpMyAdmin (nach DDEV-Initialisierung, da es DDEV benötigt)
    if [ "$INSTALL_PHPMYADMIN" = "y" ]; then
        install_phpmyadmin || handle_error "phpMyAdmin Installation fehlgeschlagen"
    fi
    
    # Node.js/npm
    if [ "$INSTALL_NODEJS" = "y" ]; then
        install_nodejs || handle_error "Node.js/npm Installation fehlgeschlagen"
    fi
    
    # .env Datei
    create_env_file || handle_error ".env Datei konnte nicht erstellt werden"
    
    # DDEV initialisieren
    init_ddev || handle_error "DDEV-Initialisierung fehlgeschlagen"
    
    # phpMyAdmin (nach DDEV-Initialisierung, da es DDEV benötigt)
    if [ "$INSTALL_PHPMYADMIN" = "y" ]; then
        install_phpmyadmin || handle_error "phpMyAdmin Installation fehlgeschlagen"
    fi
    
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
        INSTALL_PHPMYADMIN="y"
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
