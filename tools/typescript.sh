#!/bin/bash

#################################################################
# WoltLab TypeScript Build-Skript
# Kompiliert TypeScript → JavaScript
# Kopiert D3.js und TopoJSON nach temp_edit/js/3rdParty/
#
# Usage:
#   ./tools/typescript.sh        → Kompiliert TypeScript
#   ./tools/typescript.sh watch   → Watch-Mode (automatische Kompilierung)
#
# Das Script sucht automatisch nach temp_edit/ Verzeichnissen
# in allen Plugin-Verzeichnissen im Projekt-Root
#################################################################

set -e

# Farben
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
MAIN_DIR="$(dirname "$SCRIPT_DIR")"

# Suche nach temp_edit Verzeichnis in Plugin-Verzeichnissen
TEMP_EDIT_DIR=""
for plugin_dir in "${MAIN_DIR}"/*; do
    if [ -d "$plugin_dir" ] && [ -d "$plugin_dir/temp_edit" ] && [ -f "$plugin_dir/temp_edit/package.json" ]; then
        TEMP_EDIT_DIR="$(cd "$plugin_dir/temp_edit" && pwd)"
        break
    fi
done

if [ -z "$TEMP_EDIT_DIR" ]; then
    echo -e "${RED}❌ Fehler: Kein temp_edit Verzeichnis mit package.json gefunden${NC}"
    echo -e "${YELLOW}  Suche in: ${MAIN_DIR}/*/temp_edit/${NC}"
    exit 1
fi

cd "${TEMP_EDIT_DIR}"

# Parameter parsen
WATCH_MODE="${1:-}"

echo -e "${GREEN}========================================${NC}"
echo -e "${GREEN}WoltLab TypeScript Build${NC}"
if [ "$WATCH_MODE" = "watch" ]; then
    echo -e "${GREEN}Watch-Mode: Aktiviert${NC}"
fi
echo -e "${GREEN}Verzeichnis: ${TEMP_EDIT_DIR}${NC}"
echo -e "${GREEN}========================================${NC}\n"

# Prüfe ob package.json existiert
if [ ! -f "package.json" ]; then
    echo -e "${RED}❌ Fehler: package.json nicht gefunden in temp_edit/${NC}"
    echo -e "${YELLOW}  Bitte erstelle zuerst package.json${NC}"
    exit 1
fi

# Prüfe ob tsconfig.json existiert
if [ ! -f "tsconfig.json" ]; then
    echo -e "${RED}❌ Fehler: tsconfig.json nicht gefunden in temp_edit/${NC}"
    echo -e "${YELLOW}  Bitte erstelle zuerst tsconfig.json${NC}"
    exit 1
fi

# npm install, falls node_modules fehlt
if [ ! -d "node_modules" ]; then
    echo -e "${YELLOW}[1/4] npm install...${NC}"
    npm install
    echo -e "${GREEN}✓ npm install abgeschlossen${NC}\n"
else
    echo -e "${GREEN}✓ node_modules vorhanden${NC}\n"
fi

# Prüfe ob TypeScript-Dateien existieren
TS_COUNT=0
if [ -d "ts" ]; then
    TS_COUNT=$(find ts -name "*.ts" 2>/dev/null | wc -l)
fi

if [ "$TS_COUNT" -eq 0 ]; then
    echo -e "${YELLOW}⚠ Keine TypeScript-Dateien gefunden in ts/${NC}"
    echo -e "${YELLOW}  Überspringe Kompilierung${NC}\n"
else
    echo -e "${YELLOW}[2/4] TypeScript kompilieren...${NC}"
    echo -e "${YELLOW}  ${TS_COUNT} TypeScript-Dateien gefunden${NC}"
    
    # TypeScript kompilieren
    if command -v npx &> /dev/null; then
        if [ "$WATCH_MODE" = "watch" ]; then
            echo -e "${YELLOW}  Watch-Mode: TypeScript kompiliert automatisch bei Änderungen${NC}"
            echo -e "${YELLOW}  Drücke Ctrl+C zum Beenden${NC}\n"
            npx tsc -w
        else
            npx tsc
            TSC_EXIT=$?
            if [ $TSC_EXIT -eq 0 ]; then
                echo -e "${GREEN}✓ TypeScript kompiliert${NC}"
                
                # Validierung: Prüfe ob JavaScript-Dateien erstellt wurden
                JS_COUNT=0
                if [ -d "js" ]; then
                    JS_COUNT=$(find js -name "*.js" 2>/dev/null | wc -l)
                fi
                
                if [ "$TS_COUNT" -gt 0 ] && [ "$JS_COUNT" -eq 0 ]; then
                    echo -e "${RED}❌ TypeScript-Dateien gefunden, aber keine JavaScript-Dateien erstellt!${NC}"
                    echo -e "${RED}   Kompilierung scheint fehlgeschlagen zu sein.${NC}"
                    exit 1
                fi
                
                echo -e "${GREEN}✓ Validierung: ${TS_COUNT} TypeScript → ${JS_COUNT} JavaScript Dateien${NC}"
                
                # Erstelle .min.js Dateien für Produktionsmodus
                # WICHTIG: WoltLab hängt bei ENABLE_DEBUG_MODE=0 automatisch .min.js an
                # {js file='PasswordDialog'} wird zu:
                #   - PasswordDialog.js (ENABLE_DEBUG_MODE=1)
                #   - PasswordDialog.min.js (ENABLE_DEBUG_MODE=0)
                # Daher müssen wir BEIDE Dateien bereitstellen!
                # Für kleine Dateien kopieren wir einfach .js als .min.js (nicht wirklich minified)
                # AUSNAHME: 3rdParty Bibliotheken (wie d3.js) bekommen KEINE .min.js Version
                echo -e "${YELLOW}  Erstelle/Aktualisiere .min.js Dateien für Produktionsmodus...${NC}"
                MIN_JS_COUNT=0
                MISSING_MIN_JS=()
                UNSYNCED_FILES=()
                if [ -d "js" ]; then
                    # WICHTIG: Process Substitution verwenden statt Pipe, damit MIN_JS_COUNT erhalten bleibt
                    # Alternative: Array verwenden, um Subshell-Problem zu vermeiden
                    mapfile -t jsfiles < <(find js -name "*.js" ! -name "*.min.js" -type f)
                    for jsfile in "${jsfiles[@]}"; do
                        # Überspringe 3rdParty Bibliotheken (d3.js, topojson, etc.)
                        if echo "$jsfile" | grep -q "3rdParty"; then
                            continue
                        fi
                        minfile="${jsfile%.js}.min.js"
                        # Immer aktualisieren, damit .min.js mit .js synchron bleibt
                        cp "$jsfile" "$minfile"
                        MIN_JS_COUNT=$((MIN_JS_COUNT + 1))
                    done
                    
                    # Prüfe, ob alle .js Dateien eine entsprechende .min.js Datei haben
                    echo -e "${YELLOW}  Prüfe Synchronisation von .js und .min.js Dateien...${NC}"
                    mapfile -t all_jsfiles < <(find js -name "*.js" ! -name "*.min.js" ! -path "*/3rdParty/*" -type f)
                    for jsfile in "${all_jsfiles[@]}"; do
                        minfile="${jsfile%.js}.min.js"
                        if [ ! -f "$minfile" ]; then
                            MISSING_MIN_JS+=("$minfile (für $jsfile)")
                        else
                            # Prüfe ob Dateien gleich sind (mit diff)
                            if ! diff -q "$jsfile" "$minfile" > /dev/null 2>&1; then
                                UNSYNCED_FILES+=("$jsfile ↔ $minfile")
                            fi
                        fi
                    done
                    
                    # Prüfe, ob alle .min.js Dateien eine entsprechende .js Datei haben
                    mapfile -t all_minfiles < <(find js -name "*.min.js" ! -path "*/3rdParty/*" -type f)
                    for minfile in "${all_minfiles[@]}"; do
                        jsfile="${minfile%.min.js}.js"
                        if [ ! -f "$jsfile" ]; then
                            MISSING_MIN_JS+=("$minfile (ohne entsprechende .js Datei)")
                        fi
                    done
                fi
                
                # Ausgabe der Ergebnisse
                if [ "$MIN_JS_COUNT" -gt 0 ]; then
                    echo -e "${GREEN}✓ ${MIN_JS_COUNT} .min.js Dateien erstellt/aktualisiert${NC}"
                else
                    echo -e "${GREEN}✓ Keine .min.js Dateien zu aktualisieren${NC}"
                fi
                
                # Prüfe auf fehlende .min.js Dateien
                if [ ${#MISSING_MIN_JS[@]} -gt 0 ]; then
                    echo -e "${RED}❌ FEHLER: ${#MISSING_MIN_JS[@]} .min.js Datei(en) fehlen:${NC}"
                    for missing in "${MISSING_MIN_JS[@]}"; do
                        echo -e "${RED}   - $missing${NC}"
                    done
                    echo ""
                fi
                
                # Prüfe auf nicht-synchronisierte Dateien
                if [ ${#UNSYNCED_FILES[@]} -gt 0 ]; then
                    echo -e "${RED}❌ FEHLER: ${#UNSYNCED_FILES[@]} Datei(en) sind nicht synchronisiert:${NC}"
                    for unsynced in "${UNSYNCED_FILES[@]}"; do
                        echo -e "${RED}   - $unsynced${NC}"
                    done
                    echo -e "${YELLOW}  → Führe typescript.sh erneut aus, um zu synchronisieren${NC}\n"
                    exit 1
                fi
                
                if [ ${#MISSING_MIN_JS[@]} -eq 0 ] && [ ${#UNSYNCED_FILES[@]} -eq 0 ]; then
                    echo -e "${GREEN}✓ Alle .js und .min.js Dateien sind synchronisiert${NC}\n"
                fi
            else
                echo -e "${RED}❌ TypeScript-Kompilierung fehlgeschlagen!${NC}"
                exit 1
            fi
        fi
    else
        echo -e "${RED}❌ npx nicht gefunden!${NC}"
        echo -e "${RED}   Installiere Node.js und npm${NC}"
        exit 1
    fi
fi

# D3.js kopieren
echo -e "${YELLOW}[3/4] D3.js kopieren...${NC}"
if [ -f "node_modules/d3/dist/d3.min.js" ]; then
    mkdir -p js/3rdParty/d3
    cp node_modules/d3/dist/d3.min.js js/3rdParty/d3/d3.js
    echo -e "${GREEN}✓ D3.js kopiert nach js/3rdParty/d3/d3.js${NC}\n"
else
    echo -e "${YELLOW}⚠ D3.js nicht gefunden in node_modules/d3/dist/d3.min.js${NC}"
    echo -e "${YELLOW}  Überspringe D3.js Kopie${NC}\n"
fi

# TopoJSON kopieren
echo -e "${YELLOW}[4/4] TopoJSON kopieren...${NC}"
if [ -f "node_modules/topojson-client/dist/topojson-client.js" ]; then
    mkdir -p js/3rdParty/topojson-client
    cp node_modules/topojson-client/dist/topojson-client.js js/3rdParty/topojson-client/
    echo -e "${GREEN}✓ TopoJSON kopiert nach js/3rdParty/topojson-client/topojson-client.js${NC}\n"
elif [ -f "node_modules/topojson-client/dist/topojson-client.min.js" ]; then
    mkdir -p js/3rdParty/topojson-client
    cp node_modules/topojson-client/dist/topojson-client.min.js js/3rdParty/topojson-client/topojson-client.js
    echo -e "${GREEN}✓ TopoJSON kopiert nach js/3rdParty/topojson-client/topojson-client.js${NC}\n"
else
    echo -e "${YELLOW}⚠ TopoJSON nicht gefunden in node_modules/topojson-client/dist/${NC}"
    echo -e "${YELLOW}  Überspringe TopoJSON Kopie${NC}\n"
fi

if [ "$WATCH_MODE" != "watch" ]; then
    echo -e "${GREEN}========================================${NC}"
    echo -e "${GREEN}✓ TypeScript Build abgeschlossen!${NC}"
    echo -e "${GREEN}========================================${NC}\n"
    echo -e "${GREEN}Nächste Schritte:${NC}"
    TOOLS_BUILD="$(cd "${SCRIPT_DIR}" && pwd)/build.sh"
    echo -e "${GREEN}  1. ${TOOLS_BUILD} ausführen (erstellt Plugin-Paket)${NC}"
    echo -e "${GREEN}  2. Plugin im ACP installieren${NC}\n"
fi
