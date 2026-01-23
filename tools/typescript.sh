#!/usr/bin/env bash

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

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
MAIN_DIR="$(dirname "$SCRIPT_DIR")"

# Lade gemeinsame Funktionen
if [ -f "$SCRIPT_DIR/common.sh" ]; then
    source "$SCRIPT_DIR/common.sh"
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
        echo -e "${CYAN}==========================================${NC}"
        echo -e "${CYAN}${title}${NC}"
        echo -e "${CYAN}==========================================${NC}"
        echo ""
    }
    
    print_success() { echo -e "${GREEN}✓ $1${NC}"; }
    print_error() { echo -e "${RED}✗ $1${NC}"; }
    print_warning() { echo -e "${YELLOW}⚠ $1${NC}"; }
    print_info() { echo -e "${CYAN}ℹ $1${NC}"; }
fi

# Suche nach temp_edit Verzeichnis in Plugin-Verzeichnissen
TEMP_EDIT_DIR=""
for plugin_dir in "${MAIN_DIR}"/*; do
    if [ -d "$plugin_dir" ] && [ -d "$plugin_dir/temp_edit" ] && [ -f "$plugin_dir/temp_edit/package.json" ]; then
        TEMP_EDIT_DIR="$(cd "$plugin_dir/temp_edit" && pwd)"
        break
    fi
done

if [ -z "$TEMP_EDIT_DIR" ]; then
    print_error "Kein temp_edit Verzeichnis mit package.json gefunden"
    print_warning "Suche in: ${MAIN_DIR}/*/temp_edit/"
    exit 1
fi

cd "${TEMP_EDIT_DIR}"

# Parameter parsen
WATCH_MODE="${1:-}"

# Header anzeigen
if [ "$WATCH_MODE" = "watch" ]; then
    print_section "WoltLab TypeScript Build - Watch-Mode" "Hauptmenü" "Build"
else
    print_section "WoltLab TypeScript Build" "Hauptmenü" "Build"
fi

print_info "Verzeichnis: ${TEMP_EDIT_DIR}"
echo ""

# Prüfe ob package.json existiert
if [ ! -f "package.json" ]; then
    print_error "package.json nicht gefunden in temp_edit/"
    print_warning "Bitte erstelle zuerst package.json"
    exit 1
fi

# Prüfe ob tsconfig.json existiert
if [ ! -f "tsconfig.json" ]; then
    print_error "tsconfig.json nicht gefunden in temp_edit/"
    print_warning "Bitte erstelle zuerst tsconfig.json"
    exit 1
fi

# npm install, falls node_modules fehlt
if [ ! -d "node_modules" ]; then
    print_info "[1/4] npm install..."
    npm install
    print_success "npm install abgeschlossen"
    echo ""
else
    print_success "node_modules vorhanden"
    echo ""
fi

# Prüfe ob TypeScript-Dateien existieren
TS_COUNT=0
if [ -d "ts" ]; then
    TS_COUNT=$(find ts -name "*.ts" 2>/dev/null | wc -l)
fi

if [ "$TS_COUNT" -eq 0 ]; then
    print_warning "Keine TypeScript-Dateien gefunden in ts/"
    print_warning "Überspringe Kompilierung"
    echo ""
else
    print_info "[2/4] TypeScript kompilieren..."
    print_info "${TS_COUNT} TypeScript-Dateien gefunden"
    
    # TypeScript kompilieren
    if command -v npx &> /dev/null; then
        if [ "$WATCH_MODE" = "watch" ]; then
            print_info "Watch-Mode: TypeScript kompiliert automatisch bei Änderungen"
            print_info "Drücke Ctrl+C zum Beenden"
            echo ""
            npx tsc -w
        else
            # WICHTIG: Lösche alle .js und .min.js Dateien, die aus .ts Dateien kompiliert werden
            # Dies stellt sicher, dass TypeScript IMMER neu kompiliert, auch wenn .js Dateien neuer sind
            # Außerdem werden verwaiste .min.js Dateien entfernt (wenn .ts Datei gelöscht wurde)
            print_info "Lösche alte .js und .min.js Dateien für saubere Kompilierung..."
            if [ -d "js" ]; then
                # Finde alle .ts Dateien und lösche die entsprechenden .js und .min.js Dateien
                mapfile -t ts_files < <(find ts -name "*.ts" -type f 2>/dev/null)
                DELETED_JS_COUNT=0
                DELETED_MIN_COUNT=0
                for ts_file in "${ts_files[@]}"; do
                    # Konvertiere ts/.../file.ts zu js/.../file.js
                    js_file="${ts_file#ts/}"
                    js_file="${js_file%.ts}.js"
                    js_path="js/${js_file}"
                    min_path="js/${js_file%.js}.min.js"
                    
                    # Lösche .js Datei, falls sie existiert
                    if [ -f "$js_path" ]; then
                        rm -f "$js_path"
                        DELETED_JS_COUNT=$((DELETED_JS_COUNT + 1))
                    fi
                    
                    # Lösche .min.js Datei, falls sie existiert
                    if [ -f "$min_path" ]; then
                        rm -f "$min_path"
                        DELETED_MIN_COUNT=$((DELETED_MIN_COUNT + 1))
                    fi
                done
                
                if [ "$DELETED_JS_COUNT" -gt 0 ] || [ "$DELETED_MIN_COUNT" -gt 0 ]; then
                    print_success "${DELETED_JS_COUNT} .js Datei(en) und ${DELETED_MIN_COUNT} .min.js Datei(en) gelöscht"
                else
                    print_success "Keine alten Dateien zu löschen"
                fi
            fi
            
            # Jetzt kompilieren (TypeScript wird alle .js Dateien neu erstellen)
            # WICHTIG: Da wir alle .js Dateien gelöscht haben, wird TypeScript IMMER neu kompilieren
            # Zusätzlich: Lösche TypeScript Cache falls vorhanden, um sicherzustellen dass alles frisch ist
            if [ -d ".tsbuildinfo" ]; then
                rm -rf .tsbuildinfo
            fi
            npx tsc
            TSC_EXIT=$?
            if [ $TSC_EXIT -eq 0 ]; then
                print_success "TypeScript kompiliert"
                
                # Validierung: Prüfe ob JavaScript-Dateien erstellt wurden
                JS_COUNT=0
                if [ -d "js" ]; then
                    # WICHTIG: Zähle nur .js Dateien, NICHT .min.js!
                    JS_COUNT=$(find js -name "*.js" ! -name "*.min.js" 2>/dev/null | wc -l)
                fi
                
                if [ "$TS_COUNT" -gt 0 ] && [ "$JS_COUNT" -eq 0 ]; then
                    print_error "TypeScript-Dateien gefunden, aber keine JavaScript-Dateien erstellt!"
                    print_error "Kompilierung scheint fehlgeschlagen zu sein."
                    exit 1
                fi
                
                print_success "Validierung: ${TS_COUNT} TypeScript → ${JS_COUNT} JavaScript Dateien"
                
                # Erstelle .min.js Dateien für Produktionsmodus
                # WICHTIG: WoltLab hängt bei ENABLE_DEBUG_MODE=0 automatisch .min.js an
                # {js file='PasswordDialog'} wird zu:
                #   - PasswordDialog.js (ENABLE_DEBUG_MODE=1)
                #   - PasswordDialog.min.js (ENABLE_DEBUG_MODE=0)
                # Daher müssen wir BEIDE Dateien bereitstellen!
                # Für kleine Dateien kopieren wir einfach .js als .min.js (nicht wirklich minified)
                # AUSNAHME: 3rdParty Bibliotheken (wie d3.js) bekommen KEINE .min.js Version
                print_info "Erstelle/Aktualisiere .min.js Dateien für Produktionsmodus..."
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
                        # -f: Überschreibe vorhandene Dateien ohne Nachfrage
                        cp -f "$jsfile" "$minfile"
                        if [ $? -eq 0 ]; then
                            MIN_JS_COUNT=$((MIN_JS_COUNT + 1))
                        else
                            print_error "Fehler beim Kopieren: $jsfile → $minfile"
                            exit 1
                        fi
                    done
                    
                    # Prüfe, ob alle .js Dateien eine entsprechende .min.js Datei haben
                    print_info "Prüfe Synchronisation von .js und .min.js Dateien..."
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
                    print_success "${MIN_JS_COUNT} .min.js Dateien erstellt/aktualisiert"
                else
                    print_success "Keine .min.js Dateien zu aktualisieren"
                fi
                
                # Prüfe auf fehlende .min.js Dateien
                if [ ${#MISSING_MIN_JS[@]} -gt 0 ]; then
                    print_error "FEHLER: ${#MISSING_MIN_JS[@]} .min.js Datei(en) fehlen:"
                    for missing in "${MISSING_MIN_JS[@]}"; do
                        echo -e "   - ${RED}${missing}${NC}"
                    done
                    echo ""
                fi
                
                # Prüfe auf nicht-synchronisierte Dateien
                if [ ${#UNSYNCED_FILES[@]} -gt 0 ]; then
                    print_error "KRITISCHER FEHLER: ${#UNSYNCED_FILES[@]} Datei(en) sind nicht synchronisiert!"
                    for unsynced in "${UNSYNCED_FILES[@]}"; do
                        echo -e "   - ${RED}${unsynced}${NC}"
                    done
                    print_error ".js und .min.js Dateien MÜSSEN identisch sein!"
                    print_error "Build wird abgebrochen!"
                    echo ""
                    exit 1
                fi
                
                # KRITISCHE PRÜFUNG: Verifiziere dass ALLE .js Dateien identische .min.js Dateien haben
                print_info "Finale Validierung: Prüfe dass alle .js und .min.js Dateien identisch sind..."
                VALIDATION_ERRORS=0
                mapfile -t final_jsfiles < <(find js -name "*.js" ! -name "*.min.js" ! -path "*/3rdParty/*" -type f)
                for jsfile in "${final_jsfiles[@]}"; do
                    minfile="${jsfile%.js}.min.js"
                    if [ ! -f "$minfile" ]; then
                        print_error "$minfile fehlt für $jsfile"
                        VALIDATION_ERRORS=$((VALIDATION_ERRORS + 1))
                    elif ! diff -q "$jsfile" "$minfile" > /dev/null 2>&1; then
                        print_error "$jsfile und $minfile sind NICHT identisch!"
                        VALIDATION_ERRORS=$((VALIDATION_ERRORS + 1))
                    fi
                done
                
                if [ "$VALIDATION_ERRORS" -gt 0 ]; then
                    print_error "KRITISCHER FEHLER: ${VALIDATION_ERRORS} Validierungsfehler gefunden!"
                    print_error "Build wird abgebrochen!"
                    echo ""
                    exit 1
                fi
                
                if [ ${#MISSING_MIN_JS[@]} -eq 0 ] && [ ${#UNSYNCED_FILES[@]} -eq 0 ] && [ "$VALIDATION_ERRORS" -eq 0 ]; then
                    print_success "Alle .js und .min.js Dateien sind synchronisiert und identisch"
                    echo ""
                fi
            else
                print_error "TypeScript-Kompilierung fehlgeschlagen!"
                exit 1
            fi
        fi
    else
        print_error "npx nicht gefunden!"
        print_error "Installiere Node.js und npm"
        exit 1
    fi
fi

# D3.js kopieren
print_info "[3/4] D3.js kopieren..."
if [ -f "node_modules/d3/dist/d3.min.js" ]; then
    mkdir -p js/3rdParty/d3
    cp node_modules/d3/dist/d3.min.js js/3rdParty/d3/d3.js
    print_success "D3.js kopiert nach js/3rdParty/d3/d3.js"
    echo ""
else
    print_warning "D3.js nicht gefunden in node_modules/d3/dist/d3.min.js"
    print_warning "Überspringe D3.js Kopie"
    echo ""
fi

# TopoJSON kopieren
print_info "[4/4] TopoJSON kopieren..."
if [ -f "node_modules/topojson-client/dist/topojson-client.js" ]; then
    mkdir -p js/3rdParty/topojson-client
    cp node_modules/topojson-client/dist/topojson-client.js js/3rdParty/topojson-client/
    print_success "TopoJSON kopiert nach js/3rdParty/topojson-client/topojson-client.js"
    echo ""
elif [ -f "node_modules/topojson-client/dist/topojson-client.min.js" ]; then
    mkdir -p js/3rdParty/topojson-client
    cp node_modules/topojson-client/dist/topojson-client.min.js js/3rdParty/topojson-client/topojson-client.js
    print_success "TopoJSON kopiert nach js/3rdParty/topojson-client/topojson-client.js"
    echo ""
else
    print_warning "TopoJSON nicht gefunden in node_modules/topojson-client/dist/"
    print_warning "Überspringe TopoJSON Kopie"
    echo ""
fi

if [ "$WATCH_MODE" != "watch" ]; then
    echo ""
    print_section "Build abgeschlossen" "Hauptmenü" "Build"
    print_success "TypeScript Build abgeschlossen!"
    echo ""
    print_info "Nächste Schritte:"
    TOOLS_BUILD="$(cd "${SCRIPT_DIR}" && pwd)/build.sh"
    echo -e "  1. ${GREEN}${TOOLS_BUILD}${NC} ausführen (erstellt Plugin-Paket)"
    echo -e "  2. Plugin im ACP installieren"
    echo ""
fi
