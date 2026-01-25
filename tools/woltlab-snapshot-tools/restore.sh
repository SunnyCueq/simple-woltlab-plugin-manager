#!/usr/bin/env bash
# WoltLab Instant-Wiederherstellung aus Snapshot

set -e

TOOLS_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
MAIN_DIR="$(dirname "$(dirname "$TOOLS_DIR")")"
SNAPSHOT_DIR="$TOOLS_DIR/../woltlab-snapshot"
PUBLIC_DIR="$TOOLS_DIR/../woltlab-dev/public"
DDEV_DIR="$TOOLS_DIR/../woltlab-dev"
ENV_FILE="$TOOLS_DIR/../.env"
COMMON_SH="$TOOLS_DIR/../common.sh"

# Lade gemeinsame Funktionen falls vorhanden
if [ -f "$COMMON_SH" ]; then
    source "$COMMON_SH"
else
    # Fallback-Farben
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
    
    print_success() { echo -e "${GREEN}✓ $1${NC}"; }
    print_error() { echo -e "${RED}✗ $1${NC}"; }
    print_warning() { echo -e "${YELLOW}⚠ $1${NC}"; }
    print_info() { echo -e "${YELLOW}ℹ $1${NC}"; }
    log_error_with_context() {
        local message="$1"
        local context="${2:-}"
        echo -e "${RED}✗ ${message}${NC}" >&2
        [ -n "$context" ] && echo -e "${YELLOW}   ${context}${NC}" >&2
    }
fi

# Lade .env Datei falls vorhanden
if [ -f "$ENV_FILE" ]; then
    source "$ENV_FILE" 2>/dev/null || true
fi

# Verwende Werte aus .env oder Standard-Werte
DB_NAME="${DB_NAME:-db}"
DB_USER="${DB_USER:-db}"
DB_PASSWORD="${DB_PASSWORD:-db}"

print_section "WoltLab Instant-Installation" "Hauptmenü" "Snapshot"

# Snapshot-Prüfung
if [ ! -d "$SNAPSHOT_DIR" ] || [ ! -f "$SNAPSHOT_DIR/database.sql.gz" ]; then
    log_error_with_context "Snapshot nicht gefunden!${NC}"
    echo -e "${YELLOW}   Bitte zuerst $TOOLS_DIR/snapshot.sh ausführen!${NC}\n"
    exit 1
fi

print_success "Snapshot gefunden${NC}"
cat "$SNAPSHOT_DIR/metadata.txt"
echo ""

# [1/6] DDEV stoppen
echo -e "${YELLOW}[1/6] Stoppe DDEV...${NC}"
cd "$DDEV_DIR"
ddev stop > /dev/null 2>&1 || true
sleep 1
print_success "DDEV gestoppt${NC}\n"

# [2/6] Public-Ordner KOMPLETT leeren
echo -e "${YELLOW}[2/6] Lösche Public-Ordner KOMPLETT...${NC}"
# Methode 1: find + delete (schnell)
if find "$PUBLIC_DIR" -mindepth 1 -delete 2>/dev/null; then
    print_success "Public-Ordner gelöscht (via find)${NC}\n"
# Methode 2: safe_remove wenn verfügbar
elif type safe_remove &>/dev/null; then
    safe_remove "$PUBLIC_DIR"/* 2>/dev/null || true
    safe_remove "$PUBLIC_DIR"/.[!.]* 2>/dev/null || true
    print_success "Public-Ordner gelöscht (via safe_remove)${NC}\n"
# Methode 3: rm -rf (Fallback)
else
    rm -rf "$PUBLIC_DIR"/* 2>/dev/null || true
    rm -rf "$PUBLIC_DIR"/.[!.]* 2>/dev/null || true
    print_success "Public-Ordner gelöscht (via rm)${NC}\n"
fi

# [3/6] DDEV starten und auf vollständigen Start warten
echo -e "${YELLOW}[3/6] Starte DDEV...${NC}"
cd "$DDEV_DIR"
# Starte DDEV (blockierend, damit wir sicher sind dass es läuft)
ddev start > /dev/null 2>&1
print_success "DDEV gestartet${NC}\n"

# [4/6] Public-Ordner aus Snapshot kopieren (kann sofort gemacht werden)
echo -e "${YELLOW}[4/6] Kopiere Public-Ordner aus Snapshot...${NC}"
# Methode 1: rsync (bevorzugt, schnell)
if command -v rsync &> /dev/null; then
    if rsync -a "$SNAPSHOT_DIR/public/" "$PUBLIC_DIR/" 2>/dev/null; then
        DATEIEN=$(find "$PUBLIC_DIR" -type f 2>/dev/null | wc -l)
        print_success "Public-Ordner kopiert ($DATEIEN Dateien via rsync)${NC}\n"
    elif type safe_copy_dir &>/dev/null; then
        safe_copy_dir "$SNAPSHOT_DIR/public" "$PUBLIC_DIR" && {
            DATEIEN=$(find "$PUBLIC_DIR" -type f 2>/dev/null | wc -l)
            print_success "Public-Ordner kopiert ($DATEIEN Dateien via safe_copy_dir)${NC}\n"
        } || {
            cp -r "$SNAPSHOT_DIR/public"/* "$PUBLIC_DIR/" 2>/dev/null && {
                DATEIEN=$(find "$PUBLIC_DIR" -type f 2>/dev/null | wc -l)
                print_success "Public-Ordner kopiert ($DATEIEN Dateien via cp)${NC}\n"
            } || echo -e "${RED}✗ Public-Ordner konnte nicht kopiert werden${NC}\n"
        }
    else
        cp -r "$SNAPSHOT_DIR/public"/* "$PUBLIC_DIR/" 2>/dev/null && {
            DATEIEN=$(find "$PUBLIC_DIR" -type f 2>/dev/null | wc -l)
            print_success "Public-Ordner kopiert ($DATEIEN Dateien via cp)${NC}\n"
        } || echo -e "${RED}✗ Public-Ordner konnte nicht kopiert werden${NC}\n"
    fi
# Methode 2: safe_copy_dir (Fallback)
elif type safe_copy_dir &>/dev/null; then
    safe_copy_dir "$SNAPSHOT_DIR/public" "$PUBLIC_DIR" && {
        DATEIEN=$(find "$PUBLIC_DIR" -type f 2>/dev/null | wc -l)
        print_success "Public-Ordner kopiert ($DATEIEN Dateien via safe_copy_dir)${NC}\n"
    } || {
        cp -r "$SNAPSHOT_DIR/public"/* "$PUBLIC_DIR/" 2>/dev/null && {
            DATEIEN=$(find "$PUBLIC_DIR" -type f 2>/dev/null | wc -l)
            print_success "Public-Ordner kopiert ($DATEIEN Dateien via cp)${NC}\n"
        } || echo -e "${RED}✗ Public-Ordner konnte nicht kopiert werden${NC}\n"
    }
# Methode 3: cp -r (letzter Fallback)
else
    cp -r "$SNAPSHOT_DIR/public"/* "$PUBLIC_DIR/" 2>/dev/null && {
        DATEIEN=$(find "$PUBLIC_DIR" -type f 2>/dev/null | wc -l)
        print_success "Public-Ordner kopiert ($DATEIEN Dateien via cp)${NC}\n"
    } || echo -e "${RED}✗ Public-Ordner konnte nicht kopiert werden${NC}\n"
fi

# [5/6] Warte auf MySQL und importiere Datenbank
echo -e "${YELLOW}[5/6] Warte auf MySQL und importiere Datenbank...${NC}"
cd "$DDEV_DIR"
# Warte bis MySQL-Container läuft und MySQL bereit ist (max 90 Sekunden)
# WICHTIG: Verwende docker exec direkt, um keinen Neustart zu triggern!
echo -e "${YELLOW}  Warte auf MySQL-Bereitschaft...${NC}"
for i in {1..90}; do
    # Prüfe ob Container läuft
    if ! docker ps | grep -q "ddev-woltlab-db"; then
        sleep 1
        continue
    fi
    # Prüfe ob MySQL antwortet (mit mysql-Datenbank, da MySQL eine Standard-DB braucht)
    if docker exec ddev-woltlab-db mysql -uroot -proot mysql -e "SELECT 1;" > /dev/null 2>&1; then
        echo -e "${GREEN}  MySQL ist bereit nach $i Sekunden${NC}"
        break
    fi
    sleep 1
done
# Datenbank löschen und importieren (direkt mit docker exec, kein ddev mysql!)
# WICHTIG: Fremdschlüssel-Constraints berücksichtigen!
echo -e "${YELLOW}  Erstelle Datenbank...${NC}"
# Fremdschlüssel-Checks deaktivieren für sauberes Löschen
docker exec ddev-woltlab-db mysql -uroot -proot mysql -e "SET FOREIGN_KEY_CHECKS=0; DROP DATABASE IF EXISTS $DB_NAME; SET FOREIGN_KEY_CHECKS=1;" 2>/dev/null || true
docker exec ddev-woltlab-db mysql -uroot -proot mysql -e "CREATE DATABASE $DB_NAME CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" 2>&1 | grep -v "Warning" || true
echo -e "${YELLOW}  Importiere Datenbank (Fremdschlüssel-Checks deaktiviert)...${NC}"
# Fremdschlüssel-Checks während Import deaktivieren, damit Import-Reihenfolge egal ist
# WICHTIG: FOREIGN_KEY_CHECKS deaktivieren, damit Tabellen in beliebiger Reihenfolge importiert werden können
(echo "SET FOREIGN_KEY_CHECKS=0; SET SESSION FOREIGN_KEY_CHECKS=0;"; gunzip < "$SNAPSHOT_DIR/database.sql.gz"; echo "SET FOREIGN_KEY_CHECKS=1; SET SESSION FOREIGN_KEY_CHECKS=1;") | docker exec -i ddev-woltlab-db mysql -uroot -proot $DB_NAME 2>&1 | grep -v "Warning" || true
# Sicherstellen, dass Fremdschlüssel-Checks wieder aktiviert sind
docker exec ddev-woltlab-db mysql -uroot -proot $DB_NAME -e "SET FOREIGN_KEY_CHECKS=1; SET SESSION FOREIGN_KEY_CHECKS=1;" 2>/dev/null || true
TABLES=$(docker exec ddev-woltlab-db mysql -uroot -proot $DB_NAME -e "SHOW TABLES;" 2>/dev/null | tail -n +2 | wc -l)
print_success "Datenbank importiert ($TABLES Tabellen, Fremdschlüssel-Checks aktiviert)${NC}\n"

# [6/6] Logs/Caches löschen, phpMyAdmin-Infos anzeigen, Browser starten
echo -e "${YELLOW}[6/6] Finalisiere...${NC}"
# Logs und Caches löschen
find "$PUBLIC_DIR/log" -type f ! -name ".htaccess" -delete 2>/dev/null || true
rm -rf "$PUBLIC_DIR/tmp/"* 2>/dev/null || true
rm -rf "$PUBLIC_DIR/cache/"* 2>/dev/null || true

# MySQL-Port dynamisch aus DDEV extrahieren
echo -e "${YELLOW}  Ermittle MySQL-Port...${NC}"
MYSQL_PORT=""
# Versuche JSON-Format (wenn jq verfügbar)
if command -v jq &> /dev/null; then
    MYSQL_PORT=$(cd "$DDEV_DIR" && ddev describe -j 2>/dev/null | jq -r '.raw.dbinfo.published_port // empty' 2>/dev/null)
fi
# Fallback: Parse aus ddev describe Text-Output
if [ -z "$MYSQL_PORT" ] || [ "$MYSQL_PORT" = "null" ] || [ "$MYSQL_PORT" = "" ]; then
    MYSQL_PORT=$(cd "$DDEV_DIR" && ddev describe 2>/dev/null | grep -oP 'db:3306 -> 127.0.0.1:\K[0-9]+' | head -1)
fi
# Fallback: Aus docker ps extrahieren
if [ -z "$MYSQL_PORT" ] || [ "$MYSQL_PORT" = "null" ] || [ "$MYSQL_PORT" = "" ]; then
    MYSQL_PORT=$(docker ps --format "{{.Ports}}" | grep "ddev-woltlab-db" | grep -oP '127.0.0.1:\K[0-9]+(?=->3306)' | head -1)
fi

if [ -z "$MYSQL_PORT" ] || [ "$MYSQL_PORT" = "null" ] || [ "$MYSQL_PORT" = "" ]; then
    echo -e "${RED}  ⚠️  Konnte MySQL-Port nicht ermitteln, verwende Standard 3306${NC}"
    MYSQL_PORT="3306"
else
    echo -e "${GREEN}  ✓ MySQL-Port: $MYSQL_PORT${NC}"
fi

print_list "phpMyAdmin"
print_list_item "•" "URL: ${BLUE}https://woltlab.ddev.site/phpmyadmin${NC}"
print_list_item "•" "Benutzer: ${BLUE}${DB_USER}${NC}"
print_list_item "•" "Passwort: ${BLUE}${DB_PASSWORD}${NC}"
print_list_item "•" "Datenbank: ${BLUE}${DB_NAME}${NC}"
echo ""

# Firefox starten
# Browser öffnen (mit Fallbacks)
ACP_URL="https://woltlab.ddev.site/acp/"
if type open_browser &>/dev/null; then
    open_browser "$ACP_URL" || echo -e "${YELLOW}  ⚠ Browser konnte nicht automatisch geöffnet werden${NC}"
elif command -v firefox &> /dev/null; then
    firefox "$ACP_URL" > /dev/null 2>&1 &
elif command -v xdg-open &> /dev/null; then
    xdg-open "$ACP_URL" > /dev/null 2>&1 &
elif command -v open &> /dev/null; then
    open "$ACP_URL" > /dev/null 2>&1 &
else
    echo -e "${YELLOW}  ⚠ Kein Browser gefunden - öffne manuell: $ACP_URL${NC}"
fi

print_success "Fertig${NC}\n"

# Fertig!
echo -e "${GREEN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${GREEN}✅ WoltLab erfolgreich wiederhergestellt!${NC}"
echo -e "${GREEN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}\n"
echo -e "   🌐 Frontend: ${BLUE}https://woltlab.ddev.site/${NC}"
echo -e "   🔧 ACP:      ${BLUE}https://woltlab.ddev.site/acp/${NC}"
echo -e "   👤 Admin:    ${BLUE}Admin${NC}"
echo -e "   🔑 Passwort: ${BLUE}123456${NC}"
echo -e "   🗄️  Datenbank: phpMyAdmin\n"
echo -e "   🔗 phpMyAdmin: ${BLUE}https://woltlab.ddev.site/phpmyadmin${NC}\n"
