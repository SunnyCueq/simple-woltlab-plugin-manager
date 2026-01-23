#!/usr/bin/env bash

#################################################################
# WoltLab Plugin Builder
# Pfad: tools/build.sh
# 
# Usage:
#   ./tools/build.sh [plugin] [version] → Plugin bauen
#   ./tools/build.sh patch              → Patch-Version erhoehen (Standard)
#   ./tools/build.sh minor              → Minor-Version erhoehen
#   ./tools/build.sh major              → Major-Version erhoehen
#
# Das Script sucht automatisch nach Plugin-Verzeichnissen
# im Projekt-Root (Verzeichnisse mit package.xml)
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
    print_info() { echo -e "${CYAN}ℹ $1${NC}"; }
fi

# Parameter parsen
PLUGIN_TARGET="${1:-}"
VERSION_TYPE="${2:-patch}"

# Wenn erster Parameter ein Version-Typ ist, dann kein Plugin angegeben
if [[ "$PLUGIN_TARGET" =~ ^(patch|minor|major)$ ]]; then
    VERSION_TYPE="$PLUGIN_TARGET"
    PLUGIN_TARGET=""
fi

# Suche nach Plugin-Verzeichnissen
if [ -n "$PLUGIN_TARGET" ]; then
    # Spezifisches Plugin-Verzeichnis
    PROJECT_ROOT="$(cd "${MAIN_DIR}/${PLUGIN_TARGET}" && pwd)"
    if [ ! -f "$PROJECT_ROOT/package.xml" ]; then
        print_error "${PLUGIN_TARGET} ist kein gueltiges Plugin-Verzeichnis"
        exit 1
    fi
else
    # Erstes Plugin-Verzeichnis mit package.xml finden
    PROJECT_ROOT=""
    for plugin_dir in "${MAIN_DIR}"/*; do
        if [ -d "$plugin_dir" ] && [ -f "$plugin_dir/package.xml" ]; then
            PROJECT_ROOT="$(cd "$plugin_dir" && pwd)"
            break
        fi
    done
    
    if [ -z "$PROJECT_ROOT" ]; then
        print_error "Kein Plugin-Verzeichnis mit package.xml gefunden"
        print_warning "Suche in: ${MAIN_DIR}/*/package.xml"
        exit 1
    fi
fi

cd "${PROJECT_ROOT}"

# Validierung
if [[ ! "$VERSION_TYPE" =~ ^(patch|minor|major)$ ]]; then
    print_error "Ungueltiger Version-Typ '$VERSION_TYPE'"
    echo "Verwendung: ${0} [patch|minor|major]"
    exit 1
fi

PLUGIN_NAME=$(basename "$PROJECT_ROOT")
print_section "WoltLab Plugin Builder" "Hauptmenue" "Build"
print_info "Plugin: ${PLUGIN_NAME}"
print_info "Version-Typ: $VERSION_TYPE"
echo ""

# TypeScript IMMER neu kompilieren (vor jedem Build)
# Nutze typescript.sh, das auch .min.js Dateien erstellt und 3rdParty Bibliotheken kopiert
if [ -d "temp_edit" ] && [ -d "temp_edit/ts" ]; then
    TS_COUNT=$(find temp_edit/ts -name "*.ts" 2>/dev/null | wc -l)
    if [ "$TS_COUNT" -gt 0 ]; then
        print_info "[0/5] TypeScript kompilieren (via typescript.sh)..."
        print_info "${TS_COUNT} TypeScript-Dateien gefunden"
        
        # Rufe typescript.sh auf (kompiliert TypeScript, erstellt .min.js, kopiert 3rdParty)
        TOOLS_DIR="$(cd "${SCRIPT_DIR}" && pwd)"
        TYPESCRIPT_SCRIPT="${TOOLS_DIR}/typescript.sh"
        
        if [ ! -f "$TYPESCRIPT_SCRIPT" ]; then
            log_error_with_context "typescript.sh nicht gefunden in ${TYPESCRIPT_SCRIPT}" "TypeScript-Kompilierung"
            exit 1
        fi
        
        # Fuehre typescript.sh aus (ohne watch-Mode)
        bash "$TYPESCRIPT_SCRIPT"
        TSC_EXIT=$?
        
        if [ $TSC_EXIT -eq 0 ]; then
            print_success "TypeScript kompiliert (via typescript.sh)"
            
            # Pruefe ob JavaScript-Dateien nach Kompilierung existieren
            JS_COUNT=$(find temp_edit/js -name "*.js" ! -name "*.min.js" 2>/dev/null | wc -l)
            if [ "$JS_COUNT" -eq 0 ] && [ "$TS_COUNT" -gt 0 ]; then
                log_error_with_context "Keine JavaScript-Dateien nach Kompilierung gefunden!" "TypeScript-Kompilierung: ${TS_COUNT} TypeScript-Dateien gefunden, aber 0 JavaScript-Dateien erstellt"
                exit 1
            else
                print_success "${JS_COUNT} JavaScript-Dateien erstellt"
            fi
            
            # ZUSÄTZLICHE VALIDIERUNG: Pruefe ob .js Dateien neuer sind als .ts Dateien
            # Wenn eine .ts Datei neuer ist als die entsprechende .js Datei, wurde nicht neu kompiliert!
            print_info "Pruefe Synchronisation von .ts und .js Dateien..."
            UNSYNCED_TS_JS=()
            mapfile -t ts_files < <(find temp_edit/ts -name "*.ts" -type f 2>/dev/null)
            for ts_file in "${ts_files[@]}"; do
                # Konvertiere ts/.../file.ts zu js/.../file.js
                js_file="${ts_file#temp_edit/ts/}"
                js_file="${js_file%.ts}.js"
                js_path="temp_edit/js/${js_file}"
                
                if [ -f "$js_path" ]; then
                    # Pruefe ob .ts Datei neuer ist als .js Datei
                    if [ "$ts_file" -nt "$js_path" ]; then
                        UNSYNCED_TS_JS+=("$ts_file (neuer als $js_path)")
                    fi
                fi
            done
            
            if [ ${#UNSYNCED_TS_JS[@]} -gt 0 ]; then
                print_error "FEHLER: ${#UNSYNCED_TS_JS[@]} TypeScript-Datei(en) sind neuer als ihre .js Dateien!"
                print_error "TypeScript wurde nicht korrekt neu kompiliert!"
                for unsynced in "${UNSYNCED_TS_JS[@]}"; do
                    echo -e "   - ${RED}${unsynced}${NC}"
                done
                print_warning "Fuehre typescript.sh manuell aus, um zu synchronisieren"
                exit 1
            else
                print_success "Alle .ts und .js Dateien sind synchronisiert"
            fi
            
            # KRITISCHE VALIDIERUNG: Pruefe ob .js und .min.js Dateien identisch sind
            print_info "Pruefe dass alle .js und .min.js Dateien identisch sind..."
            JS_MIN_JS_ERRORS=0
            mapfile -t all_js_files < <(find temp_edit/js -name "*.js" ! -name "*.min.js" ! -path "*/3rdParty/*" -type f 2>/dev/null)
            for js_file in "${all_js_files[@]}"; do
                min_file="${js_file%.js}.min.js"
                if [ ! -f "$min_file" ]; then
                    print_error "$min_file fehlt fuer $js_file"
                    JS_MIN_JS_ERRORS=$((JS_MIN_JS_ERRORS + 1))
                elif ! diff -q "$js_file" "$min_file" > /dev/null 2>&1; then
                    print_error "$js_file und $min_file sind NICHT identisch!"
                    JS_MIN_JS_ERRORS=$((JS_MIN_JS_ERRORS + 1))
                fi
            done
            
            if [ "$JS_MIN_JS_ERRORS" -gt 0 ]; then
                log_error_with_context "KRITISCHER FEHLER: ${JS_MIN_JS_ERRORS} .js/.min.js Synchronisationsfehler gefunden!" ".js und .min.js Dateien MÜSSEN identisch sein!"
                print_error "Build wird abgebrochen!"
                exit 1
            else
                print_success "Alle .js und .min.js Dateien sind identisch"
            fi
            
            echo ""
        else
            log_error_with_context "TypeScript-Kompilierung fehlgeschlagen" "typescript.sh Exit-Code: ${TSC_EXIT}"
            exit 1
        fi
    else
        print_success "Keine TypeScript-Dateien gefunden, ueberspringe Kompilierung"
        echo ""
    fi
fi

# Pruefe ob temp_edit existiert
if [ ! -d "temp_edit" ]; then
    print_error "temp_edit Ordner nicht gefunden"
    print_warning "Bitte entpacke zuerst die TARs:"
    print_list_item "•" "rm -rf temp_edit && mkdir temp_edit"
    print_list_item "•" "tar -xf files.tar -C temp_edit"
    print_list_item "•" "tar -xf templates.tar -C temp_edit"
    print_list_item "•" "tar -xf acptemplates.tar -C temp_edit"
    exit 1
fi

# Version aus package.xml lesen
if [ ! -f "package.xml" ]; then
    print_error "package.xml nicht gefunden"
    exit 1
fi

CURRENT_VERSION=$(grep -oP '<version>\K[^<]+' "package.xml" 2>/dev/null || echo "")
if [ -z "$CURRENT_VERSION" ]; then
    print_error "Version nicht in package.xml gefunden"
    exit 1
fi

print_info "Aktuelle Version: $CURRENT_VERSION"

# Version erhoehen
IFS='.' read -ra VERSION_PARTS <<< "$CURRENT_VERSION"
MAJOR="${VERSION_PARTS[0]}"
MINOR="${VERSION_PARTS[1]}"
PATCH="${VERSION_PARTS[2]}"

case "$VERSION_TYPE" in
    patch)
        PATCH=$((PATCH + 1))
        ;;
    minor)
        MINOR=$((MINOR + 1))
        PATCH=0
        ;;
    major)
        MAJOR=$((MAJOR + 1))
        MINOR=0
        PATCH=0
        ;;
esac

NEW_VERSION="${MAJOR}.${MINOR}.${PATCH}"
TODAY=$(date +%Y-%m-%d)

print_info "Neue Version: $NEW_VERSION"
echo ""

# Version und Datum in package.xml aktualisieren
sed -i "s/<version>${CURRENT_VERSION}<\/version>/<version>${NEW_VERSION}<\/version>/" package.xml
sed -i "s/<date>[^<]*<\/date>/<date>${TODAY}<\/date>/" package.xml

print_success "package.xml aktualisiert"

# TARs aus temp_edit neu erstellen
print_info "[1/5] Packe TARs aus temp_edit..."

cd temp_edit

# files.tar erstellen (lib/, acp/, style/, PHP-Dateien, aber keine Templates)
# WICHTIG: app.config.inc.php NICHT packen - wird von WoltLab automatisch erstellt!
# Vor jeder Installation muss die Datenbank bereinigt werden (alte shrinkr-Eintraege loeschen)
FILES_TO_PACK=""
[ -d "lib" ] && FILES_TO_PACK="${FILES_TO_PACK} lib/"
[ -d "acp" ] && FILES_TO_PACK="${FILES_TO_PACK} acp/"
[ -d "style" ] && FILES_TO_PACK="${FILES_TO_PACK} style/"
[ -f "global.php" ] && FILES_TO_PACK="${FILES_TO_PACK} global.php"
[ -f "index.php" ] && FILES_TO_PACK="${FILES_TO_PACK} index.php"
# app.config.inc.php wird NICHT mitgeliefert (WoltLab erstellt sie automatisch)

if [ -n "$FILES_TO_PACK" ]; then
    # Erstelle files.tar und schließe app.config.inc.php explizit aus
    # (wird von WoltLab automatisch erstellt und darf nicht im Paket sein)
    tar -cf ../files.tar --exclude="app.config.inc.php" $FILES_TO_PACK
    print_success "files.tar erstellt"
    
    # Pruefe ob app.config.inc.php versehentlich enthalten ist
    if tar -tf ../files.tar 2>/dev/null | grep -q "^app\.config\.inc\.php$"; then
        log_error_with_context "app.config.inc.php ist in files.tar enthalten!" "Diese Datei wird von WoltLab automatisch erstellt und darf nicht im Paket sein!"
        exit 1
    fi
else
    print_warning "Keine Dateien fuer files.tar gefunden"
fi

# templates.tar erstellen (Dateien direkt im Root, keine Verzeichnisse!)
if [ -d "templates" ]; then
    cd templates
    if ls *.tpl 1> /dev/null 2>&1; then
        tar -cf ../../templates.tar *.tpl
        print_success "templates.tar erstellt [aus templates/*.tpl]"
    else
        print_warning "Keine .tpl Dateien in templates/ gefunden"
    fi
    cd ..
elif ls *.tpl 1> /dev/null 2>&1; then
    # Templates liegen direkt im temp_edit
    tar -cf ../templates.tar *.tpl
    print_success "templates.tar erstellt (aus *.tpl)"
else
    print_warning "Keine Templates gefunden"
fi

# acptemplates.tar erstellen
if [ -d "acptemplates" ]; then
    cd acptemplates
    if ls *.tpl 1> /dev/null 2>&1; then
        tar -cf ../../acptemplates.tar *.tpl
        print_success "acptemplates.tar erstellt"
    else
        print_warning "Keine .tpl Dateien in acptemplates/ gefunden"
    fi
    cd ..
else
    print_warning "Kein acptemplates/ Ordner gefunden"
fi

# files_wcf.tar erstellen
if [ -d "js" ]; then
    tar -cf ../files_wcf.tar js/
    print_success "files_wcf.tar erstellt"
else
    log_error_with_context "Kein js/ Ordner gefunden fuer files_wcf.tar!" "Verzeichnis fehlt"
    exit 1
fi


cd ..

print_success "TARs erfolgreich erstellt"
echo ""

# ========================================
# VALIDIERUNGEN - Sicherheitsmechanismen
# ========================================
print_info "[VALIDIERUNG] Pruefe Paket-Integritaet..."

VALIDATION_ERRORS=0

# 1. KRITISCH: Pruefe package.xml: files_wcf.tar MUSS application="wcf" haben
#    JavaScript-Dateien muessen ins WCF-Verzeichnis installiert werden
if ! grep -qE '<instruction[^>]*type="file"[^>]*application\s*=\s*"wcf"[^>]*>files_wcf\.tar</instruction>' package.xml; then
    log_error_with_context "files_wcf.tar fehlt application=\"wcf\" in package.xml!" "Validierung fehlgeschlagen"
    VALIDATION_ERRORS=$((VALIDATION_ERRORS + 1))
fi

# 2. Pruefe kritische JavaScript-Dateien
CRITICAL_JS_FILES=(
    "js/Shrinkr/Acp/Ui/Statistics/TimeSeriesChart.js"
    "js/3rdParty/d3/d3.js"
)

for js_file in "${CRITICAL_JS_FILES[@]}"; do
    if ! tar -tf files_wcf.tar 2>/dev/null | grep -q "^${js_file}$"; then
        log_error_with_context "Kritische Datei fehlt in files_wcf.tar: ${js_file}" "Validierung fehlgeschlagen"
        VALIDATION_ERRORS=$((VALIDATION_ERRORS + 1))
    else
        print_success "${js_file} vorhanden"
    fi
done

# 3. Pruefe ob d3.min.js (falsch) vorhanden ist statt d3.js (korrekt)
if tar -tf files_wcf.tar 2>/dev/null | grep -q "js/3rdParty/d3/d3\.min\.js$"; then
    log_error_with_context "d3.min.js gefunden [falsch]! Sollte d3.js sein [ohne .min]" "Woltlab fuegt automatisch .min.js hinzu, Dateiname darf kein .min enthalten"
    VALIDATION_ERRORS=$((VALIDATION_ERRORS + 1))
fi


# 6. Pruefe Template-Referenzen: Finde alle {js application='shrinkr' file='...'} Referenzen
print_info "Pruefe Template-Referenzen..."
TEMPLATE_JS_REFS=$(grep -rh "{js application='shrinkr' file=" temp_edit/templates/ temp_edit/acptemplates/ 2>/dev/null | \
    grep -oP "file='\K[^']+" | sort -u || true)

if [ -n "$TEMPLATE_JS_REFS" ]; then
    while IFS= read -r js_ref; do
        js_path="js/${js_ref}.js"
        if [ -f "files_wcf.tar" ] && tar -tf files_wcf.tar 2>/dev/null | grep -q "^${js_path}$"; then
            print_success "Template-Referenz gefunden: ${js_ref} → ${js_path}"
        else
            log_error_with_context "Template referenziert JavaScript-Datei, die nicht in files_wcf.tar vorhanden ist!" "Template: {js application='shrinkr' file='${js_ref}'} | Erwartet: ${js_path}"
            VALIDATION_ERRORS=$((VALIDATION_ERRORS + 1))
        fi
    done <<< "$TEMPLATE_JS_REFS"
fi

# 7. Pruefe ob alle referenzierten TARs existieren
REQUIRED_TARS=("files.tar" "templates.tar" "acptemplates.tar" "files_wcf.tar")
for tar_file in "${REQUIRED_TARS[@]}"; do
    if [ ! -f "$tar_file" ]; then
        print_warning "${tar_file} nicht gefunden [kann optional sein]"
    fi
done

# 8. Pruefe package.xml: Alle referenzierten Dateien muessen existieren
print_info "Pruefe package.xml Referenzen..."
PACKAGE_XML_FILES=$(grep -oP '<instruction[^>]*>\K[^<]+' package.xml | grep -v "^$" | sort -u || true)
if [ -n "$PACKAGE_XML_FILES" ]; then
    while IFS= read -r xml_file; do
        # Überspringe leere Instructions und bekannte Standard-Dateien
        if [[ "$xml_file" =~ ^(files\.tar|templates\.tar|acptemplates\.tar|files_wcf\.tar)$ ]] || \
           [[ "$xml_file" =~ \.(xml|php)$ ]] && [ -f "$xml_file" ] || [ -f "temp_edit/$xml_file" ]; then
            print_success "package.xml Referenz gefunden: ${xml_file}"
        elif [[ "$xml_file" =~ \.(xml|php)$ ]]; then
            print_warning "package.xml referenziert ${xml_file}, aber Datei nicht gefunden"
        fi
    done <<< "$PACKAGE_XML_FILES"
fi

# 9. KRITISCH: Pruefe dass keine alten build.sh oder typescript.sh im Root oder temp_edit existieren
# TOOLS_DIR muss vor dieser Pruefung definiert werden
TOOLS_DIR="$(cd "${PROJECT_ROOT}/../tools" && pwd)"
print_info "Pruefe auf redundante Script-Dateien..."
if [ -f "build.sh" ]; then
    log_error_with_context "KRITISCHER FEHLER: build.sh existiert noch im Root-Verzeichnis!" "Sollte nur in ${TOOLS_DIR}/build.sh existieren! Bitte loeschen: rm build.sh"
    VALIDATION_ERRORS=$((VALIDATION_ERRORS + 1))
else
    print_success "Keine build.sh im Root gefunden"
fi

if [ -f "temp_edit/typescript.sh" ]; then
    log_error_with_context "KRITISCHER FEHLER: typescript.sh existiert noch in temp_edit/!" "Sollte nur in ${TOOLS_DIR}/typescript.sh existieren! Bitte loeschen: rm temp_edit/typescript.sh"
    VALIDATION_ERRORS=$((VALIDATION_ERRORS + 1))
else
    print_success "Keine typescript.sh in temp_edit/ gefunden"
fi

# 10. Pruefe dass tools/build.sh und tools/typescript.sh existieren (außerhalb von basis-plugin)
if [ ! -f "${TOOLS_DIR}/build.sh" ]; then
    log_error_with_context "${TOOLS_DIR}/build.sh nicht gefunden!" "Kritische Datei fehlt"
    VALIDATION_ERRORS=$((VALIDATION_ERRORS + 1))
else
    print_success "tools/build.sh vorhanden"
fi

if [ ! -f "${TOOLS_DIR}/typescript.sh" ]; then
    log_error_with_context "${TOOLS_DIR}/typescript.sh nicht gefunden!" "Kritische Datei fehlt"
    VALIDATION_ERRORS=$((VALIDATION_ERRORS + 1))
else
    print_success "tools/typescript.sh vorhanden"
fi

# 11. Pruefe package.xml Struktur: applicationdirectory muss "shrinkr" sein
APPLICATION_DIR=$(grep -oP '<applicationdirectory>\K[^<]+' package.xml | head -1)
if [ "$APPLICATION_DIR" != "shrinkr" ]; then
    log_error_with_context "applicationdirectory ist nicht 'shrinkr'! Gefunden: '${APPLICATION_DIR}'" "package.xml Struktur-Fehler"
    VALIDATION_ERRORS=$((VALIDATION_ERRORS + 1))
else
    print_success "applicationdirectory korrekt: ${APPLICATION_DIR}"
fi

# 12. Pruefe dass files.tar keine JavaScript-Dateien enthaelt (sollten in files_wcf.tar sein)
if [ -f "files.tar" ]; then
    JS_IN_FILES_TAR=$(tar -tf files.tar 2>/dev/null | grep -c "\.js$" 2>/dev/null || echo "0")
    # Stelle sicher, dass JS_IN_FILES_TAR eine Zahl ist
    if ! [[ "$JS_IN_FILES_TAR" =~ ^[0-9]+$ ]]; then
        JS_IN_FILES_TAR=0
    fi
    if [ "$JS_IN_FILES_TAR" -gt 0 ]; then
        print_warning "files.tar enthaelt ${JS_IN_FILES_TAR} JavaScript-Dateien"
        print_warning "JavaScript-Dateien sollten in files_wcf.tar sein, nicht in files.tar"
    fi
fi

# Validierungsfehler zusammenfassen
if [ "$VALIDATION_ERRORS" -gt 0 ]; then
    echo ""
    print_section "Validierung fehlgeschlagen" "Hauptmenue" "Build"
    print_error "VALIDIERUNG FEHLGESCHLAGEN!"
    print_error "${VALIDATION_ERRORS} kritische Fehler gefunden!"
    print_error "Das Paket wird NICHT erstellt, bis alle Fehler behoben sind."
    echo ""
    exit 1
else
    print_success "Alle Validierungen bestanden"
    echo ""
fi

# Package-Name aus package.xml lesen
PACKAGE_NAME=$(grep -oP '<package name="\K[^"]+' "package.xml" | head -1)
if [ -z "$PACKAGE_NAME" ]; then
    log_error_with_context "Package-Name nicht in package.xml gefunden" "package.xml Struktur-Fehler"
    exit 1
fi

# Finales .tar.gz Paket erstellen
print_info "[2/3] Erstelle finales Paket..."

TAR_GZ_NAME="${PACKAGE_NAME}_v${NEW_VERSION}.tar.gz"

# Temporaeres Verzeichnis fuer Paket-Erstellung
TEMP_PACKAGE_DIR=$(mktemp -d)
trap "rm -rf ${TEMP_PACKAGE_DIR}" EXIT

# Alle Dateien ins temporaere Verzeichnis kopieren
cp package.xml "${TEMP_PACKAGE_DIR}/"
cp *.tar "${TEMP_PACKAGE_DIR}/" 2>/dev/null || true
cp *.xml "${TEMP_PACKAGE_DIR}/" 2>/dev/null || true
# eventListener.xml aus temp_edit/ kopieren, falls vorhanden
[ -f "temp_edit/eventListener.xml" ] && cp temp_edit/eventListener.xml "${TEMP_PACKAGE_DIR}/" 2>/dev/null || true
cp -r language "${TEMP_PACKAGE_DIR}/" 2>/dev/null || true

# .tar.gz erstellen (Dateien direkt ohne ./ Prefix)
cd "${TEMP_PACKAGE_DIR}"
# Alle Dateien explizit auflisten, um ./ Prefix zu vermeiden
tar -czf "${PROJECT_ROOT}/${TAR_GZ_NAME}" *

cd "${PROJECT_ROOT}"

# Finale Paket-Validierung
print_info "[VALIDIERUNG] Pruefe finales Paket..."
if [ ! -f "${TAR_GZ_NAME}" ]; then
    log_error_with_context "Paket ${TAR_GZ_NAME} wurde nicht erstellt!" "Paket-Erstellung fehlgeschlagen"
    exit 1
fi

# Pruefe ob files_wcf.tar im finalen Paket vorhanden ist
if ! tar -tzf "${TAR_GZ_NAME}" 2>/dev/null | grep -q "^files_wcf.tar$"; then
    log_error_with_context "files_wcf.tar fehlt im finalen Paket!" "Paket-Validierung fehlgeschlagen"
    exit 1
fi


print_success "Finale Paket-Validierung bestanden"
print_success "Paket erstellt: ${TAR_GZ_NAME}"
echo ""

# Aufraeumen: Nur letzte 5 Versionen behalten
print_info "[3/5] Raeume alte Pakete auf..."
KEEP_COUNT=5
PACKAGE_PATTERN="${PACKAGE_NAME}_v*.tar.gz"

if ls ${PACKAGE_PATTERN} 1> /dev/null 2>&1; then
    TOTAL_COUNT=$(ls -t ${PACKAGE_PATTERN} 2>/dev/null | wc -l)
    if [ "$TOTAL_COUNT" -gt "$KEEP_COUNT" ]; then
        OLD_COUNT=$((TOTAL_COUNT - KEEP_COUNT))
        print_info "${TOTAL_COUNT} Pakete gefunden, entferne ${OLD_COUNT} aelteste..."
        ls -t ${PACKAGE_PATTERN} | tail -n +$((KEEP_COUNT + 1)) | while read -r old_package; do
            rm -v "$old_package"
        done
        print_success "Aufraeumen abgeschlossen: ${KEEP_COUNT} Pakete behalten"
        echo ""
    else
        print_success "${TOTAL_COUNT} Pakete vorhanden [kein Aufraeumen noetig]"
        echo ""
    fi
fi

echo ""
print_section "Build abgeschlossen" "Hauptmenue" "Build"
print_success "Build abgeschlossen!"
print_info "Version: ${NEW_VERSION}"
print_info "Paket: ${TAR_GZ_NAME}"
echo ""


