#!/bin/bash
# WoltLab Snapshot erstellen - Einmalig nach frischer Installation

set -e

TOOLS_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
MAIN_DIR="$(dirname "$TOOLS_DIR")"
SNAPSHOT_DIR="$MAIN_DIR/woltlab-snapshot"
PUBLIC_DIR="$MAIN_DIR/woltlab-dev/public"

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
cd "$MAIN_DIR/woltlab-dev"
ddev start > /dev/null 2>&1
echo -e "${GREEN}✓ DDEV läuft${NC}\n"

# [2/6] Public-Ordner KOMPLETT LEEREN
echo -e "${YELLOW}[2/6] Leere Public-Ordner...${NC}"
rm -rf "$PUBLIC_DIR"/*
rm -rf "$PUBLIC_DIR"/.[!.]*
# Nur die essentiellen Dateien zurückkopieren
cp "$MAIN_DIR/woltlab-core/WCFSetup.tar.gz" "$PUBLIC_DIR/"
cp "$MAIN_DIR/woltlab-core/install.php" "$PUBLIC_DIR/"
cp "$MAIN_DIR/woltlab-core/test.php" "$PUBLIC_DIR/"
echo -e "${GREEN}✓ Public-Ordner geleert (nur WCFSetup.tar.gz + install.php)${NC}\n"

# [3/6] Datenbank KOMPLETT LEEREN
echo -e "${YELLOW}[3/6] Leere Datenbank...${NC}"
ddev mysql -e "DROP DATABASE IF EXISTS db; CREATE DATABASE db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
echo -e "${GREEN}✓ Datenbank geleert und neu erstellt${NC}\n"

# [4/6] Status VORHER
echo -e "${YELLOW}[4/6] Status VORHER:${NC}"
echo -e "${BLUE}   Dateien: $(find "$PUBLIC_DIR" -type f | wc -l)${NC}"
echo -e "${BLUE}   Tabellen: $(ddev mysql -e "SHOW TABLES FROM db;" | wc -l)${NC}\n"

# [5/6] Browser-Installation
echo -e "${YELLOW}[5/6] Öffne Firefox für Installation...${NC}"
echo -e "${BLUE}   → URL: https://woltlab.ddev.site/install.php${NC}\n"

firefox "https://woltlab.ddev.site/install.php" > /dev/null 2>&1 &

echo -e "${YELLOW}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${YELLOW}  BITTE INSTALLATION DURCHFÜHREN:        ${NC}"
echo -e "${YELLOW}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "  1. Sprache: ${GREEN}Deutsch${NC}"
echo -e "  2. Lizenz akzeptieren"
echo -e "  3. Systemcheck durchlaufen"
echo -e "  4. Datenbank: ${GREEN}db / db / db / db${NC}"
echo -e "  5. Admin: ${GREEN}Admin / admin@example.com / 123456 / 123456${NC}"
echo -e "  6. Lizenz: ${GREEN}✓ Ohne Lizenzdaten fortfahren${NC}"
echo -e "  7. Einstellungen: ${GREEN}Einfach durchklicken (Absenden)${NC}\n"

read -p "Drücke ENTER wenn Installation KOMPLETT FERTIG ist..."

# [6/6] Snapshots NACHHER erstellen
echo -e "\n${YELLOW}[6/6] Erstelle Snapshot...${NC}"

# Snapshot-Verzeichnis vorbereiten
rm -rf "$SNAPSHOT_DIR"
mkdir -p "$SNAPSHOT_DIR"

# Public-Ordner sichern (ohne WCFSetup.tar.gz - zu groß)
echo -e "${BLUE}   → Sichere Public-Ordner...${NC}"
rsync -a --exclude='WCFSetup.tar.gz' --exclude='WCFSetup-*' "$PUBLIC_DIR/" "$SNAPSHOT_DIR/public/"
DATEIEN=$(find "$SNAPSHOT_DIR/public" -type f | wc -l)
GROESSE=$(du -sh "$SNAPSHOT_DIR/public" | cut -f1)
echo -e "${GREEN}   ✓ Public-Ordner: $DATEIEN Dateien, $GROESSE${NC}"

# Datenbank exportieren
echo -e "${BLUE}   → Exportiere Datenbank...${NC}"
ddev export-db --file="$SNAPSHOT_DIR/database.sql.gz" --gzip=true
TABELLEN=$(ddev mysql -e "SHOW TABLES FROM db;" | wc -l)
DB_GROESSE=$(du -sh "$SNAPSHOT_DIR/database.sql.gz" | cut -f1)
echo -e "${GREEN}   ✓ Datenbank: $TABELLEN Tabellen, $DB_GROESSE${NC}"

# Metadaten speichern
cat > "$SNAPSHOT_DIR/metadata.txt" <<EOF
WoltLab Snapshot
================
Erstellt: $(date '+%Y-%m-%d %H:%M:%S')
WoltLab Version: 6.1
Admin User: Admin
Admin Password: 123456
Admin Email: admin@example.com

Datenbank:
- Name: db
- User: db
- Password: db
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
