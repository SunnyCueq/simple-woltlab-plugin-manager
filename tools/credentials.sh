#!/bin/bash

#################################################################
# WoltLab Development Tools - Credentials Manager
# 
# Verwaltung von Zugangsdaten in .env Datei
#
# @author      Sunny C
# @copyright   2026 Sunny C
#################################################################

set -e

# Verzeichnisse
TOOLS_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
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
        echo -e "${BLUE}==========================================${NC}"
        echo -e "${CYAN}Credentials Manager${NC}"
        echo -e "${BLUE}==========================================${NC}"
        echo ""
    }
    
    print_success() { echo -e "${GREEN}✓ $1${NC}"; }
    print_error() { echo -e "${RED}✗ $1${NC}"; }
    print_info() { echo -e "${YELLOW}→ $1${NC}"; }
fi

# Funktion: Lade .env Datei
load_env() {
    if [ ! -f "$ENV_FILE" ]; then
        return 1
    fi
    
    source "$ENV_FILE" 2>/dev/null || true
}

# Funktion: Zeige Zugangsdaten (maskiert)
show_credentials() {
    print_header
    
    if [ ! -f "$ENV_FILE" ]; then
        print_error ".env Datei nicht gefunden!"
        print_info "Erstelle .env Datei mit Option 1"
        return 1
    fi
    
    load_env
    
    echo -e "${CYAN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo -e "${CYAN}  Aktuelle Zugangsdaten${NC}"
    echo -e "${CYAN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo ""
    
    echo -e "${BLUE}DDEV:${NC}"
    echo -e "  Projekt:     ${GREEN}${DDEV_PROJECT_NAME:-nicht gesetzt}${NC}"
    echo ""
    
    echo -e "${BLUE}MySQL:${NC}"
    echo -e "  Host:        ${GREEN}${DB_HOST:-nicht gesetzt}${NC}"
    echo -e "  Port:        ${GREEN}${DB_PORT:-nicht gesetzt}${NC}"
    echo -e "  Datenbank:   ${GREEN}${DB_NAME:-nicht gesetzt}${NC}"
    echo -e "  Benutzer:    ${GREEN}${DB_USER:-nicht gesetzt}${NC}"
    echo -e "  Passwort:    ${YELLOW}$(echo "${DB_PASSWORD:-nicht gesetzt}" | sed 's/./*/g')${NC}"
    echo ""
    
    echo -e "${BLUE}WoltLab Admin:${NC}"
    echo -e "  Benutzername: ${GREEN}${WOLTLAB_ADMIN_USERNAME:-nicht gesetzt}${NC}"
    echo -e "  E-Mail:       ${GREEN}${WOLTLAB_ADMIN_EMAIL:-nicht gesetzt}${NC}"
    echo -e "  Passwort:      ${YELLOW}$(echo "${WOLTLAB_ADMIN_PASSWORD:-nicht gesetzt}" | sed 's/./*/g')${NC}"
    echo ""
    
    echo -e "${BLUE}HeidiSQL:${NC}"
    echo -e "  Host:        ${GREEN}${HEIDISQL_HOST:-nicht gesetzt}${NC}"
    echo -e "  Port:        ${GREEN}${HEIDISQL_PORT:-nicht gesetzt}${NC}"
    echo -e "  Benutzer:    ${GREEN}${HEIDISQL_USER:-nicht gesetzt}${NC}"
    echo -e "  Passwort:    ${YELLOW}$(echo "${HEIDISQL_PASSWORD:-nicht gesetzt}" | sed 's/./*/g')${NC}"
    echo ""
    
    if [ -n "${GITHUB_USERNAME:-}" ]; then
        echo -e "${BLUE}GitHub:${NC}"
        echo -e "  Benutzername: ${GREEN}${GITHUB_USERNAME}${NC}"
        echo -e "  Token:         ${YELLOW}$(echo "${GITHUB_TOKEN:-nicht gesetzt}" | sed 's/./*/g')${NC}"
        echo ""
    fi
}

# Funktion: Erstelle .env Datei
create_env() {
    print_header
    
    if [ -f "$ENV_FILE" ]; then
        print_warning ".env Datei existiert bereits"
        read -p "$(echo -e "${YELLOW}Überschreiben?${NC} [y/N]: ")" overwrite
        if [ "${overwrite:-n}" != "y" ]; then
            print_info "Abgebrochen"
            return 0
        fi
    fi
    
    if [ ! -f "$ENV_EXAMPLE" ]; then
        print_error ".env.example nicht gefunden!"
        return 1
    fi
    
    print_info "Erstelle .env Datei aus Template..."
    cp "$ENV_EXAMPLE" "$ENV_FILE"
    
    echo ""
    print_info "Möchtest du sichere Passwörter generieren?"
    read -p "$(echo -e "${YELLOW}Generieren?${NC} [y/N]: ")" generate
    
    if [ "${generate:-n}" = "y" ]; then
        local db_password=$(openssl rand -base64 12 | tr -d "=+/" | cut -c1-16)
        local admin_password=$(openssl rand -base64 12 | tr -d "=+/" | cut -c1-16)
        
        sed -i "s/DB_PASSWORD=db/DB_PASSWORD=$db_password/" "$ENV_FILE"
        sed -i "s/WOLTLAB_ADMIN_PASSWORD=123456/WOLTLAB_ADMIN_PASSWORD=$admin_password/" "$ENV_FILE"
        sed -i "s/WOLTLAB_ADMIN_PASSWORD_CONFIRM=123456/WOLTLAB_ADMIN_PASSWORD_CONFIRM=$admin_password/" "$ENV_FILE"
        sed -i "s/HEIDISQL_PASSWORD=db/HEIDISQL_PASSWORD=$db_password/" "$ENV_FILE"
        
        print_success "Sichere Passwörter generiert"
        echo ""
        echo -e "${YELLOW}Wichtig:${NC} Notiere dir die Passwörter!"
        echo -e "  DB Passwort:     ${GREEN}$db_password${NC}"
        echo -e "  Admin Passwort:  ${GREEN}$admin_password${NC}"
        echo ""
    fi
    
    print_success ".env Datei erstellt: $ENV_FILE"
    print_info "Bearbeite die Datei manuell für weitere Anpassungen"
}

# Funktion: Validiere Zugangsdaten
validate_credentials() {
    print_header
    
    if [ ! -f "$ENV_FILE" ]; then
        print_error ".env Datei nicht gefunden!"
        return 1
    fi
    
    load_env
    
    local errors=0
    
    echo -e "${CYAN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo -e "${CYAN}  Validierung${NC}"
    echo -e "${CYAN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo ""
    
    # Prüfe MySQL-Verbindung
    if command -v mysql >/dev/null 2>&1; then
        print_info "Prüfe MySQL-Verbindung..."
        if mysql -h "${DB_HOST:-127.0.0.1}" -P "${DB_PORT:-3306}" -u "${DB_USER:-db}" -p"${DB_PASSWORD:-db}" -e "SELECT 1;" >/dev/null 2>&1; then
            print_success "MySQL-Verbindung erfolgreich"
        else
            print_error "MySQL-Verbindung fehlgeschlagen"
            errors=$((errors + 1))
        fi
    else
        print_warning "mysql Client nicht installiert - überspringe MySQL-Validierung"
    fi
    
    # Prüfe DDEV
    if command -v ddev >/dev/null 2>&1; then
        print_info "Prüfe DDEV..."
        if ddev describe >/dev/null 2>&1; then
            print_success "DDEV läuft"
        else
            print_warning "DDEV läuft nicht (kann normal sein)"
        fi
    else
        print_warning "DDEV nicht installiert"
    fi
    
    echo ""
    if [ $errors -eq 0 ]; then
        print_success "Validierung erfolgreich"
    else
        print_error "Validierung fehlgeschlagen ($errors Fehler)"
        return 1
    fi
}

# Funktion: Generiere sicheres Passwort
generate_password() {
    local length="${1:-16}"
    openssl rand -base64 12 | tr -d "=+/" | cut -c1-"$length"
}

# Funktion: Menü
show_menu() {
    print_header
    
    echo -e "${GREEN}Verfügbare Optionen:${NC}"
    echo ""
    echo -e "   ${YELLOW}1)${NC} ${CYAN}.env Datei erstellen${NC}        ${ARROW} Erstellt .env aus Template"
    echo -e "   ${YELLOW}2)${NC} ${CYAN}Zugangsdaten anzeigen${NC}       ${ARROW} Zeigt aktuelle Werte (maskiert)"
    echo -e "   ${YELLOW}3)${NC} ${CYAN}Zugangsdaten validieren${NC}     ${ARROW} Prüft Verbindungen"
    echo -e "   ${YELLOW}4)${NC} ${CYAN}Passwort generieren${NC}         ${ARROW} Generiert sicheres Passwort"
    echo -e "   ${YELLOW}5)${NC} ${CYAN}HeidiSQL Passwort speichern${NC} ${ARROW} Speichert in HeidiSQL-Konfiguration"
    echo ""
    echo -e "   ${YELLOW}0)${NC} Beenden"
    echo ""
}

# Hauptprogramm
while true; do
    show_menu
    read -p "$(echo -e "${YELLOW}Option wählen${NC}: ")" choice
    
    case "$choice" in
        1)
            create_env
            echo ""
            read -p "Drücke ENTER um fortzufahren..."
            ;;
        2)
            show_credentials
            echo ""
            read -p "Drücke ENTER um fortzufahren..."
            ;;
        3)
            validate_credentials
            echo ""
            read -p "Drücke ENTER um fortzufahren..."
            ;;
        4)
            print_header
            echo -e "${CYAN}Generiere sicheres Passwort...${NC}"
            echo ""
            local pwd=$(generate_password 16)
            echo -e "${GREEN}Passwort:${NC} $pwd"
            echo ""
            read -p "Drücke ENTER um fortzufahren..."
            ;;
        5)
            print_header
            print_section "HeidiSQL - Passwort speichern"
            
            if [ ! -f "$ENV_FILE" ]; then
                print_error ".env Datei nicht gefunden!"
                print_info "Erstelle .env Datei mit Option 1"
                echo ""
                read -p "Drücke ENTER um fortzufahren..."
                continue
            fi
            
            load_env
            
            # Setze Standardwerte
            local heidisql_host="${HEIDISQL_HOST:-127.0.0.1}"
            local heidisql_port="${HEIDISQL_PORT:-3306}"
            local heidisql_user="${HEIDISQL_USER:-db}"
            local heidisql_password="${HEIDISQL_PASSWORD:-${DB_PASSWORD:-db}}"
            local heidisql_database="${HEIDISQL_DATABASE:-db}"
            local server_name="WoltLab DDEV"
            
            echo -e "${YELLOW}Aktuelle Einstellungen:${NC}"
            echo -e "  Server-Name: ${BLUE}${server_name}${NC}"
            echo -e "  Host:        ${BLUE}${heidisql_host}${NC}"
            echo -e "  Port:        ${BLUE}${heidisql_port}${NC}"
            echo -e "  Benutzer:    ${BLUE}${heidisql_user}${NC}"
            echo -e "  Datenbank:   ${BLUE}${heidisql_database}${NC}"
            echo ""
            
            # Versuche MySQL-Port aus DDEV zu ermitteln
            local ddev_dir="$TOOLS_DIR/woltlab-dev"
            if [ -d "$ddev_dir/.ddev" ] && command -v ddev &> /dev/null; then
                cd "$ddev_dir" 2>/dev/null || true
                if ddev describe &>/dev/null 2>&1; then
                    local ddev_mysql_port=$(ddev describe 2>/dev/null | grep -oP 'db:3306 -> 127\.0\.0\.1:\K[0-9]+' | head -1)
                    if [ -n "$ddev_mysql_port" ]; then
                        echo -e "${GREEN}DDEV MySQL-Port erkannt: ${ddev_mysql_port}${NC}"
                        read -p "DDEV-Port verwenden? [j/N]: " use_ddev_port
                        if [[ "${use_ddev_port:-n}" =~ ^[Jj]$ ]]; then
                            heidisql_port="$ddev_mysql_port"
                        fi
                        echo ""
                    fi
                fi
                cd - >/dev/null 2>&1 || true
            fi
            
            echo -e "${YELLOW}Optionen:${NC}"
            echo -e "  ${CYAN}•${NC} Leer lassen → Mit aktuellen Einstellungen speichern"
            echo -e "  ${CYAN}•${NC} anpassen    → Einstellungen manuell anpassen"
            echo ""
            read -p "Was möchtest du tun? [speichern]: " action
            action=${action:-speichern}
            
            if [ "$action" = "anpassen" ]; then
                echo ""
                read -p "Server-Name [${server_name}]: " input_name
                server_name="${input_name:-$server_name}"
                
                read -p "Host [${heidisql_host}]: " input_host
                heidisql_host="${input_host:-$heidisql_host}"
                
                read -p "Port [${heidisql_port}]: " input_port
                heidisql_port="${input_port:-$heidisql_port}"
                
                read -p "Benutzer [${heidisql_user}]: " input_user
                heidisql_user="${input_user:-$heidisql_user}"
                
                read -p "Datenbank [${heidisql_database}]: " input_database
                heidisql_database="${input_database:-$heidisql_database}"
                
                echo ""
                read -sp "Passwort [verwendet aus .env]: " input_password
                echo ""
                if [ -n "$input_password" ]; then
                    heidisql_password="$input_password"
                fi
            fi
            
            echo ""
            print_info "Speichere HeidiSQL-Konfiguration..."
            if heidisql_save_config "$server_name" "$heidisql_host" "$heidisql_port" "$heidisql_user" "$heidisql_password" "$heidisql_database"; then
                print_success "HeidiSQL-Konfiguration erfolgreich gespeichert!"
                echo ""
                print_info "HeidiSQL sollte die Verbindung beim nächsten Start automatisch erkennen."
            else
                print_error "HeidiSQL-Konfiguration konnte nicht gespeichert werden!"
            fi
            echo ""
            read -p "Drücke ENTER um fortzufahren..."
            ;;
        0)
            print_info "Beendet"
            exit 0
            ;;
        *)
            print_error "Ungültige Option"
            sleep 1
            ;;
    esac
done
