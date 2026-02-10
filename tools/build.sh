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
#   ./tools/build.sh unpack [plugin] [package.tar.gz] → Plugin entpacken
#
# Das Script sucht automatisch nach Plugin-Verzeichnissen
# im Projekt-Root (Verzeichnisse mit package.xml)
#################################################################

set -e

#=====================================
# KONFIGURATION
#=====================================
readonly SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
readonly MAIN_DIR="$(dirname "$SCRIPT_DIR")"

#=====================================
# QUELLEN
#=====================================
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

#=====================================
# HAUPTLOGIK (Parameter & Dispatch)
#=====================================
COMMAND="${1:-}"
PLUGIN_TARGET="${2:-}"
VERSION_TYPE="${3:-patch}"

# Wenn erster Parameter "unpack" ist, dann unpack.sh aufrufen
if [ "$COMMAND" = "unpack" ]; then
    TOOLS_DIR="$(cd "${SCRIPT_DIR}" && pwd)"
    UNPACK_SCRIPT="${TOOLS_DIR}/unpack.sh"
    
    if [ ! -f "$UNPACK_SCRIPT" ]; then
        print_error "unpack.sh nicht gefunden in ${UNPACK_SCRIPT}"
        exit 1
    fi
    
    # Rufe unpack.sh auf mit verbleibenden Parametern
    bash "$UNPACK_SCRIPT" "$PLUGIN_TARGET" "$VERSION_TYPE"
    exit $?
fi

# Wenn erster Parameter ein Version-Typ ist, dann kein Plugin angegeben
if [[ "$COMMAND" =~ ^(patch|minor|major)$ ]]; then
    VERSION_TYPE="$COMMAND"
    PLUGIN_TARGET=""
    COMMAND=""
elif [ -n "$COMMAND" ] && [[ ! "$COMMAND" =~ ^(patch|minor|major)$ ]]; then
    # COMMAND ist wahrscheinlich PLUGIN_TARGET
    PLUGIN_TARGET="$COMMAND"
    COMMAND=""
    # Prüfe ob zweiter Parameter ein Version-Typ ist
    if [[ "$VERSION_TYPE" =~ ^(patch|minor|major)$ ]]; then
        # VERSION_TYPE ist bereits korrekt gesetzt
        :
    else
        VERSION_TYPE="patch"
    fi
fi

# Suche nach Plugin-Verzeichnissen
# Prüfe zuerst auf temp_edit/package.xml, dann auf Root-package.xml (optional)
if [ -n "$PLUGIN_TARGET" ]; then
    # Spezifisches Plugin-Verzeichnis
    PROJECT_ROOT="$(cd "${MAIN_DIR}/${PLUGIN_TARGET}" && pwd)"
    if [ ! -f "$PROJECT_ROOT/temp_edit/package.xml" ] && [ ! -f "$PROJECT_ROOT/package.xml" ]; then
        print_error "${PLUGIN_TARGET} ist kein gueltiges Plugin-Verzeichnis (weder temp_edit/package.xml noch package.xml gefunden)"
        exit 1
    fi
else
    # Erstes Plugin-Verzeichnis mit temp_edit/package.xml oder package.xml finden
    PROJECT_ROOT=""
    for plugin_dir in "${MAIN_DIR}"/*; do
        if [ -d "$plugin_dir" ]; then
            if [ -f "$plugin_dir/temp_edit/package.xml" ] || [ -f "$plugin_dir/package.xml" ]; then
                PROJECT_ROOT="$(cd "$plugin_dir" && pwd)"
                break
            fi
        fi
    done
    
    if [ -z "$PROJECT_ROOT" ]; then
        print_error "Kein Plugin-Verzeichnis mit temp_edit/package.xml oder package.xml gefunden"
        print_warning "Suche in: ${MAIN_DIR}/*/temp_edit/package.xml oder ${MAIN_DIR}/*/package.xml"
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

# Version aus temp_edit/package.xml lesen (Quelle der Wahrheit)
PACKAGE_XML="temp_edit/package.xml"
if [ ! -f "$PACKAGE_XML" ]; then
    # Fallback auf Root-package.xml (für Kompatibilität)
    if [ -f "package.xml" ]; then
        PACKAGE_XML="package.xml"
        print_warning "temp_edit/package.xml nicht gefunden, verwende Root-package.xml"
    else
        print_error "package.xml nicht gefunden (weder temp_edit/package.xml noch package.xml)"
        exit 1
    fi
fi

CURRENT_VERSION=$(grep -oP '<version>\K[^<]+' "$PACKAGE_XML" 2>/dev/null || echo "")
if [ -z "$CURRENT_VERSION" ]; then
    print_error "Version nicht in $PACKAGE_XML gefunden"
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

# Version und Datum in temp_edit/package.xml aktualisieren (Quelle der Wahrheit)
sed -i "s/<version>${CURRENT_VERSION}<\/version>/<version>${NEW_VERSION}<\/version>/" "$PACKAGE_XML"
sed -i "s/<date>[^<]*<\/date>/<date>${TODAY}<\/date>/" "$PACKAGE_XML"

print_success "$PACKAGE_XML aktualisiert"

# TARs aus temp_edit neu erstellen
print_info "[1/5] Packe TARs aus temp_edit..."

cd temp_edit

# files.tar erstellen (lib/, acp/, style/, PHP-Dateien, aber keine Templates)
# WICHTIG: app.config.inc.php NICHT packen - wird von WoltLab automatisch erstellt!
# Vor jeder Installation muss die Datenbank bereinigt werden (alte Plugin-Eintraege loeschen)
FILES_TO_PACK=""
[ -d "lib" ] && FILES_TO_PACK="${FILES_TO_PACK} lib/"
[ -d "acp" ] && FILES_TO_PACK="${FILES_TO_PACK} acp/"
[ -d "style" ] && FILES_TO_PACK="${FILES_TO_PACK} style/"
[ -f "global.php" ] && FILES_TO_PACK="${FILES_TO_PACK} global.php"
[ -f "index.php" ] && FILES_TO_PACK="${FILES_TO_PACK} index.php"
# app.config.inc.php wird NICHT mitgeliefert (WoltLab erstellt sie automatisch)

if [ -n "$FILES_TO_PACK" ]; then
    # Erstelle files.tar und schließe app.config.inc.php und lib/bootstrap/ explizit aus
    # app.config.inc.php wird von WoltLab automatisch erstellt und darf nicht im Paket sein
    # lib/bootstrap/ muss ins WCF-Verzeichnis (wird in files_wcf.tar gepackt)
    tar -cf ../files.tar --exclude="app.config.inc.php" --exclude="lib/bootstrap" $FILES_TO_PACK
    print_success "files.tar erstellt"
    
    # Pruefe ob app.config.inc.php versehentlich enthalten ist
    if tar -tf ../files.tar 2>/dev/null | grep -q "^app\.config\.inc\.php$"; then
        log_error_with_context "app.config.inc.php ist in files.tar enthalten!" "Diese Datei wird von WoltLab automatisch erstellt und darf nicht im Paket sein!"
        exit 1
    fi
    
    # Pruefe ob lib/bootstrap/ versehentlich enthalten ist (muss in files_wcf.tar sein)
    if tar -tf ../files.tar 2>/dev/null | grep -q "^lib/bootstrap/"; then
        log_error_with_context "lib/bootstrap/ ist in files.tar enthalten!" "Bootstrap-Dateien müssen in files_wcf.tar sein, damit sie ins WCF-Verzeichnis kopiert werden!"
        exit 1
    fi
    
    # Pruefe ob TAR-Datei nicht leer ist
    FILE_COUNT=$(tar -tf ../files.tar 2>/dev/null | wc -l)
    if [ "$FILE_COUNT" -eq 0 ]; then
        log_error_with_context "files.tar ist leer!" "TAR-Datei enthält keine Dateien"
        exit 1
    fi
    print_success "files.tar enthaelt ${FILE_COUNT} Datei(en)"
else
    print_warning "Keine Dateien fuer files.tar gefunden"
fi

# templates.tar erstellen (Dateien direkt im Root, keine Verzeichnisse!)
if [ -d "templates" ]; then
    cd templates
    if ls *.tpl 1> /dev/null 2>&1; then
        tar -cf ../../templates.tar *.tpl
        TEMPLATE_COUNT=$(tar -tf ../../templates.tar 2>/dev/null | wc -l)
        print_success "templates.tar erstellt [aus templates/*.tpl] (${TEMPLATE_COUNT} Datei(en))"
    else
        print_warning "Keine .tpl Dateien in templates/ gefunden"
    fi
    cd ..
elif ls *.tpl 1> /dev/null 2>&1; then
    # Templates liegen direkt im temp_edit
    tar -cf ../templates.tar *.tpl
    TEMPLATE_COUNT=$(tar -tf ../templates.tar 2>/dev/null | wc -l)
    print_success "templates.tar erstellt (aus *.tpl) (${TEMPLATE_COUNT} Datei(en))"
else
    print_warning "Keine Templates gefunden"
fi

# acptemplates.tar erstellen
if [ -d "acptemplates" ]; then
    cd acptemplates
    if ls *.tpl 1> /dev/null 2>&1; then
        tar -cf ../../acptemplates.tar *.tpl
        ACP_TEMPLATE_COUNT=$(tar -tf ../../acptemplates.tar 2>/dev/null | wc -l)
        print_success "acptemplates.tar erstellt (${ACP_TEMPLATE_COUNT} Datei(en))"
    else
        print_warning "Keine .tpl Dateien in acptemplates/ gefunden"
    fi
    cd ..
else
    print_warning "Kein acptemplates/ Ordner gefunden"
fi

# files_wcf.tar erstellen (JavaScript-Dateien und Bootstrap-Dateien für WCF-Verzeichnis)
WCF_FILES_TO_PACK=""
if [ -d "js" ]; then
    WCF_FILES_TO_PACK="${WCF_FILES_TO_PACK} js/"
fi
if [ -d "lib/bootstrap" ]; then
    WCF_FILES_TO_PACK="${WCF_FILES_TO_PACK} lib/bootstrap/"
fi

if [ -n "$WCF_FILES_TO_PACK" ]; then
    tar -cf ../files_wcf.tar $WCF_FILES_TO_PACK
    WCF_FILE_COUNT=$(tar -tf ../files_wcf.tar 2>/dev/null | wc -l)
    if [ "$WCF_FILE_COUNT" -eq 0 ]; then
        log_error_with_context "files_wcf.tar ist leer!" "WCF-Dateien (js/ oder lib/bootstrap/) fehlen"
        exit 1
    fi
    
    # Pruefe ob lib/bootstrap/ enthalten ist
    if tar -tf ../files_wcf.tar 2>/dev/null | grep -q "^lib/bootstrap/"; then
        BOOTSTRAP_COUNT=$(tar -tf ../files_wcf.tar 2>/dev/null | grep -c "^lib/bootstrap/" || echo "0")
        print_success "files_wcf.tar erstellt (${WCF_FILE_COUNT} Datei(en), davon ${BOOTSTRAP_COUNT} Bootstrap-Datei(en))"
    else
        print_success "files_wcf.tar erstellt (${WCF_FILE_COUNT} Datei(en))"
    fi
else
    log_error_with_context "Keine WCF-Dateien gefunden fuer files_wcf.tar!" "js/ oder lib/bootstrap/ Verzeichnis fehlt"
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
if ! grep -qE '<instruction[^>]*type="file"[^>]*application\s*=\s*"wcf"[^>]*>files_wcf\.tar</instruction>' "$PACKAGE_XML"; then
    log_error_with_context "files_wcf.tar fehlt application=\"wcf\" in $PACKAGE_XML!" "Validierung fehlgeschlagen"
    VALIDATION_ERRORS=$((VALIDATION_ERRORS + 1))
fi

# 2. DYNAMISCHE VALIDIERUNG: Pruefe alle TypeScript -> JavaScript Dateien
print_info "Pruefe TypeScript -> JavaScript Kompilierung..."
TS_COUNT=0
JS_MISSING=0
MINJS_MISSING=0
MISSING_FILES=()

if [ -d "temp_edit/ts" ]; then
    while IFS= read -r -d '' ts_file; do
        # Überspringe .d.ts Dateien (TypeScript Definition Files)
        if [[ "$ts_file" == *.d.ts ]]; then
            continue
        fi
        
        TS_COUNT=$((TS_COUNT + 1))
        # Konvertiere .ts Pfad zu .js Pfad (temp_edit/ts/... -> temp_edit/js/...)
        js_file="${ts_file/\/ts\//\/js\/}"
        js_file="${js_file%.ts}.js"
        minjs_file="${js_file%.js}.min.js"
        
        # Prüfe ob .js existiert
        if [ ! -f "$js_file" ]; then
            print_error "JavaScript fehlt: $js_file (aus $(basename $ts_file))"
            MISSING_FILES+=("$js_file")
            JS_MISSING=$((JS_MISSING + 1))
        fi
        
        # Prüfe ob .min.js existiert
        if [ ! -f "$minjs_file" ]; then
            print_error "Minified JavaScript fehlt: $minjs_file"
            MISSING_FILES+=("$minjs_file")
            MINJS_MISSING=$((MINJS_MISSING + 1))
        fi
    done < <(find temp_edit/ts -type f -name "*.ts" -print0)
    
    if [ $JS_MISSING -gt 0 ] || [ $MINJS_MISSING -gt 0 ]; then
        echo ""
        log_error_with_context "$JS_MISSING .js und $MINJS_MISSING .min.js Dateien fehlen!" "TypeScript-Kompilierung unvollständig"
        print_error "Fehlende Dateien:"
        for missing in "${MISSING_FILES[@]}"; do
            echo "  - $missing"
        done
        print_error ""
        print_error "Führe 'bash tools/typescript.sh' aus, um die fehlenden Dateien zu erstellen"
        VALIDATION_ERRORS=$((VALIDATION_ERRORS + 1))
    else
        print_success "Alle $TS_COUNT TypeScript-Dateien korrekt kompiliert (.js und .min.js vorhanden)"
    fi
fi

# 3. d3.js wird nicht mehr verwendet (ersetzt durch flot.js)
# Validierung entfernt, da d3.js nicht mehr benötigt wird


# 6. Pruefe Template-Referenzen: Finde alle {js application='...' file='...'} Referenzen
print_info "Pruefe Template-Referenzen..."
# Lese applicationdirectory aus package.xml (dynamisch)
APPLICATION_DIR_FOR_JS=$(grep -oP '<applicationdirectory>\K[^<]+' "$PACKAGE_XML" | head -1)
if [ -z "$APPLICATION_DIR_FOR_JS" ]; then
    print_warning "applicationdirectory nicht in $PACKAGE_XML gefunden, ueberspringe Template-Referenz-Pruefung"
else
    TEMPLATE_JS_REFS=$(grep -rh "{js application='${APPLICATION_DIR_FOR_JS}' file=" temp_edit/templates/ temp_edit/acptemplates/ 2>/dev/null | \
        grep -oP "file='\K[^']+" | sort -u || true)

    if [ -n "$TEMPLATE_JS_REFS" ]; then
        while IFS= read -r js_ref; do
            js_path="js/${js_ref}.js"
            if [ -f "files_wcf.tar" ] && tar -tf files_wcf.tar 2>/dev/null | grep -q "^${js_path}$"; then
                print_success "Template-Referenz gefunden: ${js_ref} → ${js_path}"
            else
                log_error_with_context "Template referenziert JavaScript-Datei, die nicht in files_wcf.tar vorhanden ist!" "Template: {js application='${APPLICATION_DIR_FOR_JS}' file='${js_ref}'} | Erwartet: ${js_path}"
                VALIDATION_ERRORS=$((VALIDATION_ERRORS + 1))
            fi
        done <<< "$TEMPLATE_JS_REFS"
    fi
fi

# 7. Pruefe ob alle referenzierten TARs existieren
# KRITISCH: files_wcf.tar MUSS vorhanden sein (JavaScript-Dateien)
REQUIRED_TARS=("files_wcf.tar")
OPTIONAL_TARS=("files.tar" "templates.tar" "acptemplates.tar")
for tar_file in "${REQUIRED_TARS[@]}"; do
    if [ ! -f "$tar_file" ]; then
        log_error_with_context "${tar_file} fehlt! Diese Datei ist erforderlich." "TAR-Datei fehlt"
        VALIDATION_ERRORS=$((VALIDATION_ERRORS + 1))
    else
        print_success "${tar_file} vorhanden"
    fi
done
for tar_file in "${OPTIONAL_TARS[@]}"; do
    if [ ! -f "$tar_file" ]; then
        print_warning "${tar_file} nicht gefunden [optional]"
    else
        print_success "${tar_file} vorhanden"
    fi
done

# 8. Pruefe package.xml: Alle referenzierten Dateien muessen existieren
print_info "Pruefe package.xml Referenzen..."
PACKAGE_XML_FILES=$(grep -oP '<instruction[^>]*>\K[^<]+' "$PACKAGE_XML" | grep -v "^$" | sort -u || true)
MISSING_XML_FILES=()
if [ -n "$PACKAGE_XML_FILES" ]; then
    while IFS= read -r xml_file; do
        # Überspringe TAR-Dateien (werden separat geprüft)
        if [[ "$xml_file" =~ ^(files\.tar|templates\.tar|acptemplates\.tar|files_wcf\.tar)$ ]]; then
            continue
        fi
        # Prüfe XML- und PHP-Dateien
        if [[ "$xml_file" =~ \.(xml|php)$ ]]; then
            if [ -f "$xml_file" ] || [ -f "temp_edit/$xml_file" ]; then
                print_success "package.xml Referenz gefunden: ${xml_file}"
            else
                MISSING_XML_FILES+=("$xml_file")
                print_warning "package.xml referenziert ${xml_file}, aber Datei nicht gefunden"
            fi
        fi
    done <<< "$PACKAGE_XML_FILES"
    
    if [ ${#MISSING_XML_FILES[@]} -gt 0 ]; then
        print_warning "${#MISSING_XML_FILES[@]} referenzierte Datei(en) fehlen in package.xml"
        # Nicht als kritischen Fehler behandeln, da einige Dateien optional sein können
    fi
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

# 11. Pruefe package.xml Struktur: Erforderliche Felder
print_info "Pruefe package.xml Struktur..."
APPLICATION_DIR=$(grep -oP '<applicationdirectory>\K[^<]+' "$PACKAGE_XML" | head -1)
if [ -z "$APPLICATION_DIR" ]; then
    log_error_with_context "applicationdirectory fehlt in $PACKAGE_XML!" "package.xml Struktur-Fehler"
    VALIDATION_ERRORS=$((VALIDATION_ERRORS + 1))
else
    print_success "applicationdirectory gefunden: ${APPLICATION_DIR}"
fi

# Prüfe ob package name vorhanden ist
PACKAGE_NAME_CHECK=$(grep -oP '<package name="\K[^"]+' "$PACKAGE_XML" | head -1)
if [ -z "$PACKAGE_NAME_CHECK" ]; then
    log_error_with_context "package name fehlt in $PACKAGE_XML!" "package.xml Struktur-Fehler"
    VALIDATION_ERRORS=$((VALIDATION_ERRORS + 1))
else
    print_success "package name gefunden: ${PACKAGE_NAME_CHECK}"
fi

# Prüfe ob version vorhanden ist
VERSION_CHECK=$(grep -oP '<version>\K[^<]+' "$PACKAGE_XML" | head -1)
if [ -z "$VERSION_CHECK" ]; then
    log_error_with_context "version fehlt in $PACKAGE_XML!" "package.xml Struktur-Fehler"
    VALIDATION_ERRORS=$((VALIDATION_ERRORS + 1))
else
    print_success "version gefunden: ${VERSION_CHECK}"
fi

# Prüfe ob date vorhanden ist
DATE_CHECK=$(grep -oP '<date>\K[^<]+' "$PACKAGE_XML" | head -1)
if [ -z "$DATE_CHECK" ]; then
    log_error_with_context "date fehlt in $PACKAGE_XML!" "package.xml Struktur-Fehler"
    VALIDATION_ERRORS=$((VALIDATION_ERRORS + 1))
else
    print_success "date gefunden: ${DATE_CHECK}"
fi

# 12. Pruefe dass files.tar keine JavaScript-Dateien oder Bootstrap-Dateien enthaelt (sollten in files_wcf.tar sein)
# Hinweis: Laut WoltLab-Docs (package/pip/file) geht files.tar ins App-Verzeichnis; acp/js/ gehoert dorthin.
# Die Warnung ist projektkonventionell – ACP-App-JS in acp/js/ (z. B. Chart.js) ist sachlich korrekt.
if [ -f "files.tar" ]; then
    JS_IN_FILES_TAR=$(tar -tf files.tar 2>/dev/null | grep -c "\.js$" 2>/dev/null || echo "0")
    if ! [[ "$JS_IN_FILES_TAR" =~ ^[0-9]+$ ]]; then
        JS_IN_FILES_TAR=0
    fi
    if [ "$JS_IN_FILES_TAR" -gt 0 ]; then
        print_warning "files.tar enthaelt ${JS_IN_FILES_TAR} JavaScript-Dateien"
        print_warning "JavaScript-Dateien sollten in files_wcf.tar sein, nicht in files.tar"
    fi
    
    # Pruefe ob Bootstrap-Dateien in files.tar enthalten sind (sollten in files_wcf.tar sein)
    BOOTSTRAP_IN_FILES_TAR=$(tar -tf files.tar 2>/dev/null | grep -c "^lib/bootstrap/" 2>/dev/null || echo "0")
    if ! [[ "$BOOTSTRAP_IN_FILES_TAR" =~ ^[0-9]+$ ]]; then
        BOOTSTRAP_IN_FILES_TAR=0
    fi
    if [ "$BOOTSTRAP_IN_FILES_TAR" -gt 0 ]; then
        log_error_with_context "files.tar enthaelt ${BOOTSTRAP_IN_FILES_TAR} Bootstrap-Dateien!" "Bootstrap-Dateien müssen in files_wcf.tar sein, damit sie ins WCF-Verzeichnis kopiert werden!"
        VALIDATION_ERRORS=$((VALIDATION_ERRORS + 1))
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
PACKAGE_NAME=$(grep -oP '<package name="\K[^"]+' "$PACKAGE_XML" | head -1)
if [ -z "$PACKAGE_NAME" ]; then
    log_error_with_context "Package-Name nicht in $PACKAGE_XML gefunden" "package.xml Struktur-Fehler"
    exit 1
fi

# Finales .tar.gz Paket erstellen
print_info "[2/3] Erstelle finales Paket..."

TAR_GZ_NAME="${PACKAGE_NAME}_v${NEW_VERSION}.tar.gz"

# Temporaeres Verzeichnis fuer Paket-Erstellung
TEMP_PACKAGE_DIR=$(mktemp -d)
trap "rm -rf ${TEMP_PACKAGE_DIR}" EXIT

# Alle Dateien ins temporaere Verzeichnis kopieren
# WICHTIG: package.xml aus temp_edit/ kopieren (Quelle der Wahrheit)
cp "$PACKAGE_XML" "${TEMP_PACKAGE_DIR}/package.xml"
cp *.tar "${TEMP_PACKAGE_DIR}/" 2>/dev/null || true

# WICHTIG: XML-Dateien aus temp_edit/ kopieren (nicht aus Root!)
# package.xml wurde bereits aus temp_edit/ kopiert (wurde dort aktualisiert)
# Alle anderen XML-Dateien kommen aus temp_edit/ (Original-Paket)
if [ -d "temp_edit" ]; then
    # Kopiere alle XML-Dateien aus temp_edit/ (außer package.xml, das kommt aus Root)
    XML_COUNT=0
    for xml_file in temp_edit/*.xml; do
        if [ -f "$xml_file" ] && [ "$(basename "$xml_file")" != "package.xml" ]; then
            cp "$xml_file" "${TEMP_PACKAGE_DIR}/"
            XML_COUNT=$((XML_COUNT + 1))
        fi
    done
    if [ "$XML_COUNT" -gt 0 ]; then
        print_success "${XML_COUNT} XML-Datei(en) kopiert"
    else
        print_warning "Keine XML-Dateien in temp_edit/ gefunden"
    fi
else
    # Fallback: Falls temp_edit nicht existiert, aus Root kopieren
    print_warning "temp_edit/ nicht gefunden, kopiere XML-Dateien aus Root (könnte veraltet sein!)"
    cp *.xml "${TEMP_PACKAGE_DIR}/" 2>/dev/null || true
fi

# Language-Dateien kopieren und validieren (aus temp_edit/language/)
if [ -d "temp_edit/language" ]; then
    cp -r temp_edit/language "${TEMP_PACKAGE_DIR}/"
    LANGUAGE_COUNT=$(find temp_edit/language -name "*.xml" 2>/dev/null | wc -l)
    if [ "$LANGUAGE_COUNT" -gt 0 ]; then
        print_success "language/ kopiert (${LANGUAGE_COUNT} Datei(en))"
    else
        print_warning "temp_edit/language/ Verzeichnis vorhanden, aber keine XML-Dateien gefunden"
    fi
elif [ -d "language" ]; then
    # Fallback: Falls temp_edit/language nicht existiert, aus Root kopieren
    print_warning "temp_edit/language/ nicht gefunden, kopiere aus Root (könnte veraltet sein!)"
    cp -r language "${TEMP_PACKAGE_DIR}/"
    LANGUAGE_COUNT=$(find language -name "*.xml" 2>/dev/null | wc -l)
    if [ "$LANGUAGE_COUNT" -gt 0 ]; then
        print_success "language/ kopiert (${LANGUAGE_COUNT} Datei(en))"
    else
        print_warning "language/ Verzeichnis vorhanden, aber keine XML-Dateien gefunden"
    fi
else
    print_warning "language/ Verzeichnis nicht gefunden [kann optional sein]"
fi

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

# Pruefe ob alle kritischen Dateien im finalen Paket vorhanden sind
print_info "Pruefe Paket-Inhalt..."
PACKAGE_CONTENT=$(tar -tzf "${TAR_GZ_NAME}" 2>/dev/null || echo "")
if [ -z "$PACKAGE_CONTENT" ]; then
    log_error_with_context "Paket ist leer oder beschädigt!" "Paket-Validierung fehlgeschlagen"
    exit 1
fi

# Prüfe kritische Dateien
CRITICAL_FILES=("package.xml" "files_wcf.tar")
MISSING_CRITICAL=()
for critical_file in "${CRITICAL_FILES[@]}"; do
    if ! echo "$PACKAGE_CONTENT" | grep -q "^${critical_file}$"; then
        MISSING_CRITICAL+=("$critical_file")
    fi
done

if [ ${#MISSING_CRITICAL[@]} -gt 0 ]; then
    log_error_with_context "Kritische Datei(en) fehlen im Paket: ${MISSING_CRITICAL[*]}" "Paket-Validierung fehlgeschlagen"
    exit 1
fi

# Prüfe ob package.xml im Paket vorhanden ist
if ! echo "$PACKAGE_CONTENT" | grep -q "^package\.xml$"; then
    log_error_with_context "package.xml fehlt im finalen Paket!" "Paket-Validierung fehlgeschlagen"
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
VALIDATE_DIR="${PROJECT_ROOT#$MAIN_DIR/}"
print_info "Vor Store-Release: ./tools/validate-plugin.sh ${VALIDATE_DIR} ausführen (Plugin-Store & WoltLab-Cloud Kriterien)."
echo ""


