#!/bin/bash
# WoltLab Snapshot erstellen - Einmalig nach frischer Installation

set -e

TOOLS_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
MAIN_DIR="$(dirname "$(dirname "$TOOLS_DIR")")"
SNAPSHOT_DIR="$TOOLS_DIR/../woltlab-snapshot"
PUBLIC_DIR="$TOOLS_DIR/../woltlab-dev/public"
ENV_FILE="$TOOLS_DIR/../.env"
COMMON_SH="$TOOLS_DIR/../common.sh"

# Lade gemeinsame Funktionen falls vorhanden
if [ -f "$COMMON_SH" ]; then
    source "$COMMON_SH"
fi

# Lade .env Datei falls vorhanden
if [ -f "$ENV_FILE" ]; then
    source "$ENV_FILE" 2>/dev/null || true
fi

# Verwende Werte aus .env oder Standard-Werte
DB_NAME="${DB_NAME:-db}"
DB_USER="${DB_USER:-db}"
DB_PASSWORD="${DB_PASSWORD:-db}"
WOLTLAB_ADMIN_USERNAME="${WOLTLAB_ADMIN_USERNAME:-Admin}"
WOLTLAB_ADMIN_EMAIL="${WOLTLAB_ADMIN_EMAIL:-admin@example.com}"
WOLTLAB_ADMIN_PASSWORD="${WOLTLAB_ADMIN_PASSWORD:-123456}"

GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

echo -e "${BLUE}╔════════════════════════════════════════╗${NC}"
echo -e "${BLUE}║  WoltLab Snapshot Erstellen            ║${NC}"
echo -e "${BLUE}╚════════════════════════════════════════╝${NC}\n"

# [1/6] DDEV starten
echo -e "${YELLOW}[1/6] Starte DDEV...${NC}"
cd "$TOOLS_DIR/../woltlab-dev"
ddev start > /dev/null 2>&1
echo -e "${GREEN}✓ DDEV läuft${NC}\n"

# [2/6] Public-Ordner KOMPLETT LEEREN
echo -e "${YELLOW}[2/6] Leere Public-Ordner...${NC}"
# Verwende safe_remove wenn verfügbar, sonst Standard
if type safe_remove &>/dev/null; then
    safe_remove "$PUBLIC_DIR"/* 2>/dev/null || true
    safe_remove "$PUBLIC_DIR"/.[!.]* 2>/dev/null || true
else
    rm -rf "$PUBLIC_DIR"/* 2>/dev/null || true
    rm -rf "$PUBLIC_DIR"/.[!.]* 2>/dev/null || true
fi

# Nur die essentiellen Dateien zurückkopieren (mit Fallbacks)
if type safe_copy &>/dev/null; then
    safe_copy "$MAIN_DIR/woltlab-core/WCFSetup.tar.gz" "$PUBLIC_DIR/" 2>/dev/null || echo -e "${YELLOW}⚠ WCFSetup.tar.gz nicht gefunden${NC}"
    safe_copy "$MAIN_DIR/woltlab-core/install.php" "$PUBLIC_DIR/" 2>/dev/null || echo -e "${YELLOW}⚠ install.php nicht gefunden${NC}"
    safe_copy "$MAIN_DIR/woltlab-core/test.php" "$PUBLIC_DIR/" 2>/dev/null || echo -e "${YELLOW}⚠ test.php nicht gefunden${NC}"
else
    cp "$MAIN_DIR/woltlab-core/WCFSetup.tar.gz" "$PUBLIC_DIR/" 2>/dev/null || echo -e "${YELLOW}⚠ WCFSetup.tar.gz nicht gefunden${NC}"
    cp "$MAIN_DIR/woltlab-core/install.php" "$PUBLIC_DIR/" 2>/dev/null || echo -e "${YELLOW}⚠ install.php nicht gefunden${NC}"
    cp "$MAIN_DIR/woltlab-core/test.php" "$PUBLIC_DIR/" 2>/dev/null || echo -e "${YELLOW}⚠ test.php nicht gefunden${NC}"
fi
echo -e "${GREEN}✓ Public-Ordner geleert (nur WCFSetup.tar.gz + install.php)${NC}\n"

# [3/6] Datenbank KOMPLETT LEEREN
echo -e "${YELLOW}[3/6] Leere Datenbank...${NC}"
# Versuche DDEV MySQL, mit Fallback
if ddev mysql -e "DROP DATABASE IF EXISTS $DB_NAME; CREATE DATABASE $DB_NAME CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" 2>/dev/null; then
    echo -e "${GREEN}✓ Datenbank geleert und neu erstellt${NC}\n"
elif ddev mysql -e "DROP DATABASE IF EXISTS \`$DB_NAME\`; CREATE DATABASE \`$DB_NAME\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" 2>/dev/null; then
    echo -e "${GREEN}✓ Datenbank geleert und neu erstellt (mit Backticks)${NC}\n"
else
    echo -e "${YELLOW}⚠ Datenbank-Operation fehlgeschlagen, versuche alternative Methode...${NC}"
    # Fallback: Direkte MySQL-Verbindung
    MYSQL_PORT=$(cd "$TOOLS_DIR/../woltlab-dev" && ddev describe 2>/dev/null | grep -oP 'db:3306 -> 127\.0\.0\.1:\K[0-9]+' | head -1)
    if [ -n "$MYSQL_PORT" ] && command -v mysql &> /dev/null; then
        mysql -h 127.0.0.1 -P "$MYSQL_PORT" -u "$DB_USER" -p"$DB_PASSWORD" -e "DROP DATABASE IF EXISTS $DB_NAME; CREATE DATABASE $DB_NAME CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" 2>/dev/null && \
        echo -e "${GREEN}✓ Datenbank geleert und neu erstellt (via mysql Client)${NC}\n" || \
        echo -e "${RED}✗ Datenbank konnte nicht geleert werden${NC}\n"
    else
        echo -e "${RED}✗ Datenbank konnte nicht geleert werden - bitte manuell prüfen${NC}\n"
    fi
fi

# [4/6] Status VORHER
echo -e "${YELLOW}[4/6] Status VORHER:${NC}"
echo -e "${BLUE}   Dateien: $(find "$PUBLIC_DIR" -type f | wc -l)${NC}"
echo -e "${BLUE}   Tabellen: $(ddev mysql -e "SHOW TABLES FROM $DB_NAME;" | wc -l)${NC}\n"

# [5/6] Browser-Installation
echo -e "${YELLOW}[5/6] Öffne Browser für Installation...${NC}"
INSTALL_URL="https://woltlab.ddev.site/install.php"
echo -e "${BLUE}   → URL: $INSTALL_URL${NC}\n"

# Verwende open_browser wenn verfügbar, sonst Fallback
if type open_browser &>/dev/null; then
    if open_browser "$INSTALL_URL"; then
        echo -e "${GREEN}✓ Browser geöffnet${NC}\n"
    else
        echo -e "${YELLOW}⚠ Browser konnte nicht automatisch geöffnet werden${NC}"
        echo -e "${YELLOW}   Bitte öffne manuell: $INSTALL_URL${NC}\n"
    fi
else
    # Fallback: Versuche verschiedene Browser
    if command -v firefox &> /dev/null; then
        firefox "$INSTALL_URL" > /dev/null 2>&1 &
    elif command -v xdg-open &> /dev/null; then
        xdg-open "$INSTALL_URL" > /dev/null 2>&1 &
    elif command -v open &> /dev/null; then
        open "$INSTALL_URL" > /dev/null 2>&1 &
    else
        echo -e "${YELLOW}⚠ Kein Browser gefunden - bitte öffne manuell: $INSTALL_URL${NC}\n"
    fi
fi

echo -e "${YELLOW}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${YELLOW}  BITTE INSTALLATION DURCHFÜHREN:        ${NC}"
echo -e "${YELLOW}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "  1. Sprache: ${GREEN}Deutsch${NC}"
echo -e "  2. Lizenz akzeptieren"
echo -e "  3. Systemcheck durchlaufen"
echo -e "  4. Datenbank: ${GREEN}$DB_NAME / $DB_USER / $DB_PASSWORD / $DB_NAME${NC}"
echo -e "  5. Admin: ${GREEN}$WOLTLAB_ADMIN_USERNAME / $WOLTLAB_ADMIN_EMAIL / $WOLTLAB_ADMIN_PASSWORD / $WOLTLAB_ADMIN_PASSWORD${NC}"
echo -e "  6. Lizenz: ${GREEN}✓ Ohne Lizenzdaten fortfahren${NC}"
echo -e "  7. Einstellungen: ${GREEN}Einfach durchklicken (Absenden)${NC}\n"

read -p "Drücke ENTER wenn Installation KOMPLETT FERTIG ist..."

# [6/6] Snapshots NACHHER erstellen
echo -e "\n${YELLOW}[6/6] Erstelle Snapshot...${NC}"

# Snapshot-Verzeichnis vorbereiten
if type safe_remove &>/dev/null; then
    safe_remove "$SNAPSHOT_DIR" 2>/dev/null || true
else
    rm -rf "$SNAPSHOT_DIR" 2>/dev/null || true
fi

if type safe_mkdir &>/dev/null; then
    safe_mkdir "$SNAPSHOT_DIR" || {
        echo -e "${RED}✗ Konnte Snapshot-Verzeichnis nicht erstellen${NC}"
        exit 1
    }
else
    mkdir -p "$SNAPSHOT_DIR" || {
        echo -e "${RED}✗ Konnte Snapshot-Verzeichnis nicht erstellen${NC}"
        exit 1
    }
fi

# Public-Ordner sichern (ohne WCFSetup.tar.gz - zu groß)
echo -e "${BLUE}   → Sichere Public-Ordner...${NC}"
safe_mkdir "$SNAPSHOT_DIR/public" 2>/dev/null || mkdir -p "$SNAPSHOT_DIR/public" 2>/dev/null || true

# Versuche rsync, mit Fallback auf cp
if command -v rsync &> /dev/null; then
    if rsync -a --exclude='WCFSetup.tar.gz' --exclude='WCFSetup-*' "$PUBLIC_DIR/" "$SNAPSHOT_DIR/public/" 2>/dev/null; then
        DATEIEN=$(find "$SNAPSHOT_DIR/public" -type f 2>/dev/null | wc -l)
        GROESSE=$(du -sh "$SNAPSHOT_DIR/public" 2>/dev/null | cut -f1)
        echo -e "${GREEN}   ✓ Public-Ordner: $DATEIEN Dateien, $GROESSE (via rsync)${NC}"
    elif type safe_copy_dir &>/dev/null; then
        # Erstelle temporäres Verzeichnis ohne Excludes
        TMP_DIR=$(mktemp -d)
        cp -r "$PUBLIC_DIR"/* "$TMP_DIR/" 2>/dev/null || true
        # Entferne große Dateien
        rm -f "$TMP_DIR/WCFSetup.tar.gz" "$TMP_DIR/WCFSetup-"* 2>/dev/null || true
        # Kopiere nach Snapshot
        safe_copy_dir "$TMP_DIR" "$SNAPSHOT_DIR/public" || cp -r "$TMP_DIR"/* "$SNAPSHOT_DIR/public/" 2>/dev/null || true
        rm -rf "$TMP_DIR" 2>/dev/null || true
        DATEIEN=$(find "$SNAPSHOT_DIR/public" -type f 2>/dev/null | wc -l)
        GROESSE=$(du -sh "$SNAPSHOT_DIR/public" 2>/dev/null | cut -f1)
        echo -e "${GREEN}   ✓ Public-Ordner: $DATEIEN Dateien, $GROESSE (via cp)${NC}"
    else
        # Letzter Fallback: cp mit manueller Exclude-Logik
        find "$PUBLIC_DIR" -type f ! -name "WCFSetup.tar.gz" ! -name "WCFSetup-*" -exec cp --parents {} "$SNAPSHOT_DIR/public/" \; 2>/dev/null || \
        cp -r "$PUBLIC_DIR"/* "$SNAPSHOT_DIR/public/" 2>/dev/null || true
        rm -f "$SNAPSHOT_DIR/public/WCFSetup.tar.gz" "$SNAPSHOT_DIR/public/WCFSetup-"* 2>/dev/null || true
        DATEIEN=$(find "$SNAPSHOT_DIR/public" -type f 2>/dev/null | wc -l)
        GROESSE=$(du -sh "$SNAPSHOT_DIR/public" 2>/dev/null | cut -f1)
        echo -e "${GREEN}   ✓ Public-Ordner: $DATEIEN Dateien, $GROESSE (via find+cp)${NC}"
    fi
else
    # Fallback: cp ohne rsync
    if type safe_copy_dir &>/dev/null; then
        safe_copy_dir "$PUBLIC_DIR" "$SNAPSHOT_DIR/public" || cp -r "$PUBLIC_DIR"/* "$SNAPSHOT_DIR/public/" 2>/dev/null || true
    else
        cp -r "$PUBLIC_DIR"/* "$SNAPSHOT_DIR/public/" 2>/dev/null || true
    fi
    rm -f "$SNAPSHOT_DIR/public/WCFSetup.tar.gz" "$SNAPSHOT_DIR/public/WCFSetup-"* 2>/dev/null || true
    DATEIEN=$(find "$SNAPSHOT_DIR/public" -type f 2>/dev/null | wc -l)
    GROESSE=$(du -sh "$SNAPSHOT_DIR/public" 2>/dev/null | cut -f1)
    echo -e "${GREEN}   ✓ Public-Ordner: $DATEIEN Dateien, $GROESSE (via cp)${NC}"
fi

# Datenbank exportieren
echo -e "${BLUE}   → Exportiere Datenbank...${NC}"
# Methode 1: ddev export-db (bevorzugt)
if ddev export-db --file="$SNAPSHOT_DIR/database.sql.gz" --gzip=true 2>/dev/null; then
    TABELLEN=$(ddev mysql -e "SHOW TABLES FROM $DB_NAME;" 2>/dev/null | wc -l)
    DB_GROESSE=$(du -sh "$SNAPSHOT_DIR/database.sql.gz" 2>/dev/null | cut -f1)
    echo -e "${GREEN}   ✓ Datenbank: $TABELLEN Tabellen, $DB_GROESSE (via ddev export-db)${NC}"
# Methode 2: mysqldump direkt (Fallback)
elif command -v mysqldump &> /dev/null; then
    MYSQL_PORT=$(cd "$TOOLS_DIR/../woltlab-dev" && ddev describe 2>/dev/null | grep -oP 'db:3306 -> 127\.0\.0\.1:\K[0-9]+' | head -1)
    if [ -n "$MYSQL_PORT" ]; then
        mysqldump -h 127.0.0.1 -P "$MYSQL_PORT" -u "$DB_USER" -p"$DB_PASSWORD" "$DB_NAME" 2>/dev/null | gzip > "$SNAPSHOT_DIR/database.sql.gz" 2>/dev/null && {
            TABELLEN=$(mysql -h 127.0.0.1 -P "$MYSQL_PORT" -u "$DB_USER" -p"$DB_PASSWORD" -e "SHOW TABLES FROM $DB_NAME;" 2>/dev/null | wc -l)
            DB_GROESSE=$(du -sh "$SNAPSHOT_DIR/database.sql.gz" 2>/dev/null | cut -f1)
            echo -e "${GREEN}   ✓ Datenbank: $TABELLEN Tabellen, $DB_GROESSE (via mysqldump)${NC}"
        } || echo -e "${YELLOW}   ⚠ Datenbank-Export fehlgeschlagen (via mysqldump)${NC}"
    else
        echo -e "${YELLOW}   ⚠ MySQL-Port nicht gefunden${NC}"
    fi
# Methode 3: ddev mysql mit manueller Komprimierung (Fallback)
else
    ddev mysql -e "SHOW TABLES FROM $DB_NAME;" 2>/dev/null > /dev/null && {
        ddev mysql -e "SELECT * FROM information_schema.tables WHERE table_schema='$DB_NAME';" 2>/dev/null | \
        mysqldump -h 127.0.0.1 -u "$DB_USER" -p"$DB_PASSWORD" "$DB_NAME" 2>/dev/null | gzip > "$SNAPSHOT_DIR/database.sql.gz" 2>/dev/null || \
        ddev mysql -e "SELECT * FROM $DB_NAME.*" 2>/dev/null | gzip > "$SNAPSHOT_DIR/database.sql.gz" 2>/dev/null || {
            echo -e "${YELLOW}   ⚠ Datenbank-Export fehlgeschlagen - versuche alternative Methode...${NC}"
            # Letzter Fallback: Einfacher Export ohne Komprimierung
            ddev mysql "$DB_NAME" < /dev/null 2>/dev/null > "$SNAPSHOT_DIR/database.sql" 2>/dev/null && \
            gzip "$SNAPSHOT_DIR/database.sql" 2>/dev/null && {
                TABELLEN=$(ddev mysql -e "SHOW TABLES FROM $DB_NAME;" 2>/dev/null | wc -l)
                DB_GROESSE=$(du -sh "$SNAPSHOT_DIR/database.sql.gz" 2>/dev/null | cut -f1)
                echo -e "${GREEN}   ✓ Datenbank: $TABELLEN Tabellen, $DB_GROESSE (via alternative Methode)${NC}"
            } || echo -e "${RED}   ✗ Datenbank-Export fehlgeschlagen${NC}"
        }
    } || echo -e "${RED}   ✗ Datenbank nicht erreichbar${NC}"
fi

# Metadaten speichern
cat > "$SNAPSHOT_DIR/metadata.txt" <<EOF
WoltLab Snapshot
================
Erstellt: $(date '+%Y-%m-%d %H:%M:%S')
WoltLab Version: 6.1
Admin User: $WOLTLAB_ADMIN_USERNAME
Admin Password: $WOLTLAB_ADMIN_PASSWORD
Admin Email: $WOLTLAB_ADMIN_EMAIL

Datenbank:
- Name: $DB_NAME
- User: $DB_USER
- Password: $DB_PASSWORD
- Tabellen: $TABELLEN

Public-Ordner:
- Dateien: $DATEIEN
- Größe: $GROESSE

Snapshot-Größe gesamt:
$(du -sh "$SNAPSHOT_DIR" | cut -f1)
EOF

echo -e "\n${GREEN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${GREEN}✅ Snapshot erfolgreich erstellt!${NC}"
echo -e "${GREEN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}\n"
cat "$SNAPSHOT_DIR/metadata.txt"
echo ""
echo -e "   📁 Snapshot: ${BLUE}$SNAPSHOT_DIR${NC}"
echo -e "   🚀 Restore:  ${BLUE}$TOOLS_DIR/restore.sh${NC}\n"
