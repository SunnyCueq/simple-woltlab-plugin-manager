#!/bin/bash
# WoltLab Instant-Wiederherstellung aus Snapshot

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
echo -e "${BLUE}║  WoltLab Instant-Installation          ║${NC}"
echo -e "${BLUE}╚════════════════════════════════════════╝${NC}\n"

# Snapshot-Prüfung
if [ ! -d "$SNAPSHOT_DIR" ] || [ ! -f "$SNAPSHOT_DIR/database.sql.gz" ]; then
    echo -e "${RED}❌ Snapshot nicht gefunden!${NC}"
    echo -e "${YELLOW}   Bitte zuerst $TOOLS_DIR/snapshot.sh ausführen!${NC}\n"
    exit 1
fi

echo -e "${GREEN}✓ Snapshot gefunden${NC}"
cat "$SNAPSHOT_DIR/metadata.txt"
echo ""

# [1/6] DDEV starten
echo -e "${YELLOW}[1/6] Starte DDEV...${NC}"
cd "$MAIN_DIR/woltlab-dev"
ddev start > /dev/null 2>&1
echo -e "${GREEN}✓ DDEV läuft${NC}\n"

# [2/6] Public-Ordner leeren und wiederherstellen
echo -e "${YELLOW}[2/6] Stelle Public-Ordner wieder her...${NC}"
rm -rf "$PUBLIC_DIR"/*
rm -rf "$PUBLIC_DIR"/.[!.]*
rsync -a "$SNAPSHOT_DIR/public/" "$PUBLIC_DIR/"
# WCFSetup.tar.gz aus woltlab-core kopieren
cp "$MAIN_DIR/woltlab-core/WCFSetup.tar.gz" "$PUBLIC_DIR/"
echo -e "${GREEN}✓ Public-Ordner wiederhergestellt ($(find "$PUBLIC_DIR" -type f | wc -l) Dateien)${NC}\n"

# [3/6] Datenbank leeren und wiederherstellen
echo -e "${YELLOW}[3/6] Stelle Datenbank wieder her...${NC}"
ddev mysql -e "DROP DATABASE IF EXISTS db; CREATE DATABASE db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
gunzip < "$SNAPSHOT_DIR/database.sql.gz" | ddev mysql db
TABLES=$(ddev mysql -e "SHOW TABLES FROM db;" | wc -l)
echo -e "${GREEN}✓ Datenbank wiederhergestellt ($TABLES Tabellen)${NC}\n"

# [4/6] Caches leeren
echo -e "${YELLOW}[4/6] Leere Caches...${NC}"
rm -rf "$PUBLIC_DIR/tmp/"*
ddev exec "[ -d /var/www/html/tmp ] && rm -rf /var/www/html/tmp/* || true"
echo -e "${GREEN}✓ Caches geleert${NC}\n"

# [5/6] HeidiSQL konfigurieren
echo -e "${YELLOW}[5/6] Konfiguriere HeidiSQL...${NC}"
MYSQL_PORT=$(ddev describe -j | jq -r '.raw.dbinfo.published_port')
HEIDISQL_CONFIG="$HOME/.config/heidisql/settings.json"

mkdir -p "$HOME/.config/heidisql"

# Verwende das gespeicherte kodierte Passwort aus der aktuellen Config (falls vorhanden)
SAVED_PASSWORD=$(jq -r '.Servers["Woltlab Local"].Password // "716FD"' "$HEIDISQL_CONFIG" 2>/dev/null || echo "716FD")

jq --arg port "$MYSQL_PORT" --arg pwd "$SAVED_PASSWORD" '
  .Servers["Woltlab Local"] = {
    Host: "127.0.0.1",
    Port: ($port | tonumber),
    User: "db",
    Password: $pwd,
    SessionColor: 49407,
    Databases: "db",
    NetType: 0,
    Compressed: false,
    LoginPrompt: false,
    WantSSL: false
  } | .LastActiveSession = "Woltlab Local" | del(.Servers.Woltlab)
' "$HEIDISQL_CONFIG" 2>/dev/null > "${HEIDISQL_CONFIG}.tmp" && mv "${HEIDISQL_CONFIG}.tmp" "$HEIDISQL_CONFIG" || {
    cat > "$HEIDISQL_CONFIG" <<EOF
{
  "Servers": {
    "Woltlab Local": {
      "Host": "127.0.0.1",
      "Port": $MYSQL_PORT,
      "User": "db",
      "Password": "$SAVED_PASSWORD",
      "SessionColor": 49407,
      "Databases": "db",
      "NetType": 0,
      "Compressed": false,
      "LoginPrompt": false,
      "WantSSL": false
    }
  },
  "LastActiveSession": "Woltlab Local"
}
EOF
}

echo -e "${GREEN}✓ HeidiSQL konfiguriert (Port: $MYSQL_PORT)${NC}\n"

# [6/6] HeidiSQL und Firefox starten
echo -e "${YELLOW}[6/6] Starte HeidiSQL und Firefox...${NC}"
if command -v heidisql &> /dev/null; then
    heidisql > /dev/null 2>&1 &
    echo -e "${GREEN}✓ HeidiSQL gestartet (mit kodiertem Passwort)${NC}"
else
    echo -e "${YELLOW}⚠ HeidiSQL nicht gefunden${NC}"
fi

firefox "https://woltlab.ddev.site/acp/" > /dev/null 2>&1 &
echo -e "${GREEN}✓ Firefox gestartet${NC}\n"

# Fertig!
echo -e "${GREEN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${GREEN}✅ WoltLab erfolgreich wiederhergestellt!${NC}"
echo -e "${GREEN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}\n"
echo -e "   🌐 Frontend: ${BLUE}https://woltlab.ddev.site/${NC}"
echo -e "   🔧 ACP:      ${BLUE}https://woltlab.ddev.site/acp/${NC}"
echo -e "   👤 Admin:    ${BLUE}Admin${NC}"
echo -e "   🔑 Passwort: ${BLUE}123456${NC}"
echo -e "   🗄️  Datenbank: HeidiSQL (Port: $MYSQL_PORT)\n"
