#!/usr/bin/env bash

#################################################################
# WoltLab Plugin Builder
# Pfad: tools/build.sh
# 
# Usage:
#   ./tools/build.sh [plugin] [version] → Plugin bauen
#   ./tools/build.sh patch              → Patch-Version erhoehen (Standard)
#   ./tools/build.sh same               → Version aus package.xml NICHT aendern (Entwicklung)
#   ./tools/build.sh [plugin] same      → wie oben, fuer ein Plugin-Verzeichnis
#   ./tools/build.sh minor              → Minor-Version erhoehen
#   ./tools/build.sh major              → Major-Version erhoehen
#   ./tools/build.sh unpack [plugin] [package.tar.gz] → Plugin entpacken
#   ./tools/build.sh --dry-run          → Paket-Inhalt anzeigen, ohne zu bauen
#   ./tools/build.sh --json patch       → CI: JSON-Report
#   ./tools/build.sh --verbose [patch|minor|major|same] → Bauen mit Tree-Output
#   ./tools/build.sh --strict-layout    → Root-*.tpl als Fehler (sonst nur Warnung)
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
# PIP-PARSING (package.xml Instructions)
#=====================================
# Leitet aus package.xml die benoetigten Dateien/TARs ab.
# Liefert eine Liste von Pfaden, die in temp_edit/ existieren muessen.
parse_package_instructions() {
    local pkg="$1"
    local base="${2:-temp_edit}"
    local list=()
    # WoltLab Default-PIP-Dateien
    local -A DEFAULTS=(
        [file]="files.tar"
        [template]="templates.tar"
        [acpTemplate]="acptemplates.tar"
    )
    # Instruction-Zeilen mit explizitem Pfad: <instruction type="X">path</instruction>
    while IFS= read -r line; do
        local path
        path=$(echo "$line" | sed -n 's/.*>\([^<]*\)<.*/\1/p' | command tr -d '[:space:]')
        [ -n "$path" ] && list+=("$base/$path")
    done < <(grep -E '<instruction[^>]*>[^<]+</instruction>' "$pkg" 2>/dev/null || true)
    # file mit application=wcf (files_wcf.tar)
    if grep -qE 'type="file"[^>]*application\s*=\s*"wcf"' "$pkg" 2>/dev/null; then
        local wcf_path
        wcf_path=$(grep -oE 'type="file"[^>]*application\s*=\s*"wcf"[^>]*>[^<]+' "$pkg" | sed 's/.*>//')
        [ -n "$wcf_path" ] && list+=("$base/$wcf_path")
    fi
    # file/template/acpTemplate ohne expliziten Pfad -> Default
    if grep -qE 'type="file"[^>]*/>' "$pkg" 2>/dev/null || grep -qE 'type="file"[^>]*></instruction>' "$pkg" 2>/dev/null; then
        list+=("$base/files.tar")
    fi
    if grep -qE 'type="template"[^>]*/>' "$pkg" 2>/dev/null || grep -qE 'type="template"[^>]*></instruction>' "$pkg" 2>/dev/null; then
        list+=("$base/templates.tar")
    fi
    if grep -qE 'type="acpTemplate"[^>]*/>' "$pkg" 2>/dev/null || grep -qE 'type="acpTemplate"[^>]*></instruction>' "$pkg" 2>/dev/null; then
        list+=("$base/acptemplates.tar")
    fi
    # package.xml, XML-Dateien, language/
    list+=("$base/package.xml")
    for f in "$base"/*.xml; do
        [ -f "$f" ] && [ "$(basename "$f")" != "package.xml" ] && list+=("$f")
    done
    [ -d "$base/language" ] && list+=("$base/language")
    printf '%s\n' "${list[@]}" | sort -u
}

# PIP-Quellen (DevTools-Parität) — check-pip-sources.py
run_pip_source_check() {
    local base="${1:-temp_edit}"
    local pkg="${2:-$base/package.xml}"
    local check="${SCRIPT_DIR}/check-pip-sources.py"
    local -a pip_args=(--strict --strict-case)
    [ "${JSON_MODE:-0}" -eq 1 ] && pip_args+=(--json)
    if command -v python3 &>/dev/null && [ -f "$check" ]; then
        python3 "$check" "${pip_args[@]}" "$base" "$pkg"
        return $?
    fi
    print_error "check-pip-sources.py nicht gefunden — PIP-Validierung übersprungen"
    return 1
}

build_fail() {
    local msg="$1"
    if [ "${JSON_MODE:-0}" -eq 1 ] && command -v python3 &>/dev/null && [ -f "${SCRIPT_DIR}/swpm-package-report.py" ]; then
        python3 "${SCRIPT_DIR}/swpm-package-report.py" build-err "$msg" "${PACKAGE_NAME:-}" "${NEW_VERSION:-}" >&2 || true
    fi
    print_error "$msg"
    exit 1
}

# Tree-Output: Zeigt was gepackt wuerde (Quellen in temp_edit)
output_package_tree() {
    local base="${1:-temp_edit}"
    echo ""
    print_info "Paket-Inhalt (Tree):"
    echo "  package.xml"
    [ -d "$base/files" ] && echo "  files.tar (files/)"
    [ -d "$base/lib" ] || [ -d "$base/acp" ] || [ -d "$base/style" ] && echo "  files.tar (lib/, acp/, style/)"
    [ -d "$base/js" ] || [ -d "$base/lib/bootstrap" ] && echo "  files_wcf.tar (js/, lib/bootstrap/)"
    [ -d "$base/templates" ] && echo "  templates.tar (templates/*.tpl — kanonisch)" || { ls "$base"/*.tpl 1>/dev/null 2>&1 && echo "  templates.tar (*.tpl — Legacy, nach templates/ verschieben)"; }
    [ -d "$base/acptemplates" ] && echo "  acptemplates.tar (acptemplates/*.tpl)"
    for f in "$base"/*.xml; do
        [ -f "$f" ] && [ "$(basename "$f")" != "package.xml" ] && echo "  $(basename "$f")  (PIP-XML, Root)"
    done
    [ -d "$base/language" ] && echo "  language/"
    echo ""
}

#=====================================
# HAUPTLOGIK (Parameter & Dispatch)
#=====================================
DRY_RUN=0
VERBOSE=0
JSON_MODE=0
STRICT_LAYOUT=0

# --dry-run, --verbose, --json, --strict-layout aus Parametern extrahieren
REST=()
for arg in "$@"; do
    case "$arg" in
        --dry-run)  DRY_RUN=1 ;;
        --verbose)  VERBOSE=1 ;;
        --json)     JSON_MODE=1 ;;
        --strict-layout) STRICT_LAYOUT=1 ;;
        *)          REST+=("$arg") ;;
    esac
done

N="${#REST[@]}"
VERSION_TYPE="patch"
PLUGIN_TARGET=""

# Wenn erster Parameter "unpack" ist, dann unpack.sh aufrufen
if [ "$N" -ge 1 ] && [ "${REST[0]}" = "unpack" ]; then
    TOOLS_DIR="$(cd "${SCRIPT_DIR}" && pwd)"
    UNPACK_SCRIPT="${TOOLS_DIR}/unpack.sh"
    
    if [ ! -f "$UNPACK_SCRIPT" ]; then
        print_error "unpack.sh nicht gefunden in ${UNPACK_SCRIPT}"
        exit 1
    fi
    
    bash "$UNPACK_SCRIPT" "${REST[1]:-}" "${REST[2]:-}"
    exit $?
fi

# Argumente: [plugin] [patch|minor|major|same]  oder nur patch|minor|major|same (erstes Plugin)
case "$N" in
    0)
        ;;
    1)
        if [[ "${REST[0]}" =~ ^(patch|minor|major|same)$ ]]; then
            VERSION_TYPE="${REST[0]}"
        else
            PLUGIN_TARGET="${REST[0]}"
        fi
        ;;
    *)
        if [[ "${REST[1]}" =~ ^(patch|minor|major|same)$ ]]; then
            PLUGIN_TARGET="${REST[0]}"
            VERSION_TYPE="${REST[1]}"
        else
            print_error "Zweites Argument muss patch, minor, major oder same sein (ist: ${REST[1]})"
            echo "Verwendung: ${0} <plugin> <patch|minor|major|same>"
            exit 1
        fi
        ;;
esac

# Suche nach Plugin-Verzeichnissen
# Prüfe zuerst auf temp_edit/package.xml, dann auf Root-package.xml (optional)
# PLUGIN_TARGET: relativ zu MAIN_DIR ODER absoluter Pfad (Produktlinien / externe Roots)
if [ -n "$PLUGIN_TARGET" ]; then
    if [[ "$PLUGIN_TARGET" = /* ]]; then
        PROJECT_ROOT="$(cd "$PLUGIN_TARGET" && pwd)"
    elif [ -d "${MAIN_DIR}/${PLUGIN_TARGET}" ]; then
        PROJECT_ROOT="$(cd "${MAIN_DIR}/${PLUGIN_TARGET}" && pwd)"
    elif [ -d "$PLUGIN_TARGET" ]; then
        PROJECT_ROOT="$(cd "$PLUGIN_TARGET" && pwd)"
    else
        print_error "Plugin-Verzeichnis nicht gefunden: ${PLUGIN_TARGET}"
        exit 1
    fi
    if [ ! -f "$PROJECT_ROOT/temp_edit/package.xml" ] && [ ! -f "$PROJECT_ROOT/package.xml" ]; then
        print_error "${PLUGIN_TARGET} ist kein gueltiges Plugin-Verzeichnis (weder temp_edit/package.xml noch package.xml gefunden)"
        exit 1
    fi
else
    # Erstes Plugin über gemeinsame Discovery (find_plugin_directories / scan-workspace)
    PROJECT_ROOT=""
    if declare -f find_plugin_directories &>/dev/null; then
        while IFS= read -r plugin_dir; do
            [ -z "$plugin_dir" ] && continue
            PROJECT_ROOT="$(cd "$plugin_dir" && pwd)"
            break
        done < <(find_plugin_directories "$MAIN_DIR" 2>/dev/null || true)
    fi
    if [ -z "$PROJECT_ROOT" ]; then
        for plugin_dir in "${MAIN_DIR}"/*; do
            if [ -d "$plugin_dir" ]; then
                if [ -f "$plugin_dir/temp_edit/package.xml" ] || [ -f "$plugin_dir/package.xml" ]; then
                    PROJECT_ROOT="$(cd "$plugin_dir" && pwd)"
                    break
                fi
            fi
        done
    fi
    
    if [ -z "$PROJECT_ROOT" ]; then
        print_error "Kein Plugin-Verzeichnis mit temp_edit/package.xml oder package.xml gefunden"
        print_warning "Suche in: ${MAIN_DIR}/*/temp_edit/package.xml oder ${MAIN_DIR}/*/package.xml"
        exit 1
    fi
fi

cd "${PROJECT_ROOT}"

# Validierung
if [[ ! "$VERSION_TYPE" =~ ^(patch|minor|major|same)$ ]]; then
    print_error "Ungueltiger Version-Typ '$VERSION_TYPE'"
    echo "Verwendung: ${0} [patch|minor|major|same]"
    exit 1
fi

PLUGIN_NAME=$(basename "$PROJECT_ROOT")
print_section "WoltLab Plugin Builder" "Hauptmenue" "Build"
print_info "Plugin: ${PLUGIN_NAME}"
print_info "Version-Typ: $VERSION_TYPE"
echo ""

# TypeScript: bei tsconfig.json und/oder .ts-Quellen — Build bricht bei Fehler ab
if [ "$DRY_RUN" -eq 0 ] && [ -d "temp_edit" ]; then
    TS_COUNT=$(find temp_edit/ts -name "*.ts" 2>/dev/null | wc -l)
    HAS_TSCONFIG=0
    [ -f "temp_edit/tsconfig.json" ] && HAS_TSCONFIG=1

    if [ "$TS_COUNT" -gt 0 ] || [ "$HAS_TSCONFIG" -eq 1 ]; then
        print_info "[0/5] TypeScript kompilieren / prüfen..."
        TOOLS_DIR="$(cd "${SCRIPT_DIR}" && pwd)"
        TYPESCRIPT_SCRIPT="${TOOLS_DIR}/typescript.sh"
        CHECK_TS="${TOOLS_DIR}/check-typescript.sh"

        if [ "$TS_COUNT" -gt 0 ]; then
            print_info "${TS_COUNT} TypeScript-Datei(en) in temp_edit/ts/"
            if [ ! -f "$TYPESCRIPT_SCRIPT" ]; then
                log_error_with_context "typescript.sh nicht gefunden in ${TYPESCRIPT_SCRIPT}" "TypeScript-Kompilierung"
                exit 1
            fi
            bash "$TYPESCRIPT_SCRIPT"
            TSC_EXIT=$?
            if [ $TSC_EXIT -ne 0 ]; then
                log_error_with_context "TypeScript-Kompilierung fehlgeschlagen" "typescript.sh Exit-Code: ${TSC_EXIT}"
                exit 1
            fi
            print_success "TypeScript kompiliert (via typescript.sh)"

            JS_COUNT=$(find temp_edit/js -name "*.js" ! -name "*.min.js" 2>/dev/null | wc -l)
            if [ "$JS_COUNT" -eq 0 ]; then
                log_error_with_context "Keine JavaScript-Dateien nach Kompilierung gefunden!" "TypeScript-Kompilierung: ${TS_COUNT} TypeScript-Dateien gefunden, aber 0 JavaScript-Dateien erstellt"
                exit 1
            fi
            print_success "${JS_COUNT} JavaScript-Dateien erstellt"

            print_info "Pruefe Synchronisation von .ts und .js Dateien..."
            UNSYNCED_TS_JS=()
            mapfile -t ts_files < <(find temp_edit/ts -name "*.ts" -type f 2>/dev/null)
            for ts_file in "${ts_files[@]}"; do
                js_file="${ts_file#temp_edit/ts/}"
                js_file="${js_file%.ts}.js"
                js_path="temp_edit/js/${js_file}"
                if [ -f "$js_path" ] && [ "$ts_file" -nt "$js_path" ]; then
                    UNSYNCED_TS_JS+=("$ts_file (neuer als $js_path)")
                fi
            done
            if [ ${#UNSYNCED_TS_JS[@]} -gt 0 ]; then
                print_error "FEHLER: ${#UNSYNCED_TS_JS[@]} TypeScript-Datei(en) sind neuer als ihre .js Dateien!"
                for unsynced in "${UNSYNCED_TS_JS[@]}"; do
                    echo -e "   - ${RED}${unsynced}${NC}"
                done
                exit 1
            fi
            print_success "Alle .ts und .js Dateien sind synchronisiert"

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
                exit 1
            fi
            print_success "Alle .js und .min.js Dateien sind identisch"
        elif [ -f "$CHECK_TS" ]; then
            # tsconfig without classic temp_edit/ts layout — still typecheck
            if ! bash "$CHECK_TS" "$(pwd)"; then
                log_error_with_context "TypeScript-Check fehlgeschlagen" "check-typescript.sh"
                exit 1
            fi
            print_success "TypeScript-Check (tsconfig) OK"
        fi
        echo ""
    else
        print_success "Kein TypeScript (keine tsconfig / keine .ts) — uebersprungen"
        echo ""
    fi
elif [ "$DRY_RUN" -eq 1 ]; then
    print_info "[DRY-RUN] TypeScript-Kompilierung uebersprungen"
fi

# Pruefe ob temp_edit existiert
if [ ! -d "temp_edit" ]; then
    print_error "temp_edit Ordner nicht gefunden"
    print_warning "Bitte entpacke zuerst die TARs:"
    print_list_item "•" "rm -rf temp_edit && mkdir temp_edit"
    print_list_item "•" "tar -xf files.tar -C temp_edit"
    print_list_item "•" "mkdir -p temp_edit/templates && tar -xf templates.tar -C temp_edit/templates"
    print_list_item "•" "mkdir -p temp_edit/acptemplates && tar -xf acptemplates.tar -C temp_edit/acptemplates"
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

# --dry-run: PIP-Validierung + Tree-Output, dann Exit (ohne Bauen)
if [ "$DRY_RUN" -eq 1 ]; then
    print_info "[DRY-RUN] Pruefe package.xml Instructions und zeige Paket-Inhalt..."
    if run_pip_source_check "temp_edit" "$PACKAGE_XML"; then
        :
    else
        print_error "PIP-Validierung fehlgeschlagen. Fehlende Quellen siehe oben."
        exit 1
    fi
    output_package_tree "temp_edit"
    print_success "Dry-run abgeschlossen – Paket wuerde gebaut werden koennen."
    exit 0
fi

CURRENT_VERSION=$(grep -oP '<version>\K[^<]+' "$PACKAGE_XML" 2>/dev/null || echo "")
if [ -z "$CURRENT_VERSION" ]; then
    print_error "Version nicht in $PACKAGE_XML gefunden"
    exit 1
fi

print_info "Aktuelle Version: $CURRENT_VERSION"

if [ "$VERSION_TYPE" = "same" ]; then
    NEW_VERSION="$CURRENT_VERSION"
    print_info "Version-Typ same: Version und Datum in package.xml bleiben unveraendert (Entwicklung)"
    echo ""
else
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
fi

# ========================================
# XML-Dateien validieren (Wohlgeformtheit)
# ========================================
print_info "XML-Dateien validieren (Wohlgeformtheit)..."
XML_VALIDATION_ERRORS=0

# Prüfen ob xmllint (libxml2) verfügbar ist, sonst Python-Fallback
if command -v xmllint >/dev/null 2>&1; then
    validate_xml() {
        local f="$1"
        local xmllint_err
        xmllint_err=$(xmllint --noout "$f" 2>&1)
        if [ $? -ne 0 ]; then
            print_error "Ungültiges XML: $f${xmllint_err:+ ($xmllint_err)}"
            XML_VALIDATION_ERRORS=$((XML_VALIDATION_ERRORS + 1))
        else
            print_success "XML gültig: $f"
        fi
    }
else
    validate_xml() {
        local f="$1"
        local err
        err=$(python3 -c '
import xml.etree.ElementTree as ET
import sys
try:
    ET.parse(sys.argv[1])
except ET.ParseError as e:
    if e.position:
        print("Zeile {}, Spalte {}".format(e.position[0], e.position[1]), file=sys.stderr)
    else:
        print(str(e), file=sys.stderr)
    sys.exit(1)
' "$f" 2>&1)
        local rc=$?
        if [ "$rc" -ne 0 ]; then
            print_error "Ungültiges XML: $f${err:+ ($err)}"
            XML_VALIDATION_ERRORS=$((XML_VALIDATION_ERRORS + 1))
        else
            print_success "XML gültig: $f"
        fi
    }
fi

# package.xml
if [ -f "$PACKAGE_XML" ]; then
    validate_xml "$PACKAGE_XML"
fi

# temp_edit/*.xml (ohne package.xml, wird bereits geprüft)
for xml_file in temp_edit/*.xml; do
    if [ -f "$xml_file" ] && [ "$(basename "$xml_file")" != "package.xml" ]; then
        validate_xml "$xml_file"
    fi
done

# temp_edit/language/*.xml
if [ -d "temp_edit/language" ]; then
    for xml_file in temp_edit/language/*.xml; do
        if [ -f "$xml_file" ]; then
            validate_xml "$xml_file"
        fi
    done
fi

if [ "$XML_VALIDATION_ERRORS" -gt 0 ]; then
    print_error "XML-Validierung fehlgeschlagen: ${XML_VALIDATION_ERRORS} Datei(en) mit Fehlern."
    exit 1
fi

print_success "Alle XML-Dateien sind wohlgeformt"
echo ""

# Shared fail/warn checks (registry = build ↔ validate alignment)
# See tools/swpm-check-registry.txt — PIP-Quellen bleiben separat darunter.
print_info "Plugin-Checks (Registry)..."
JS_AMD_PREFIX=""
if [ -f "${SCRIPT_DIR}/swpm-package-resolve.sh" ]; then
    # shellcheck source=swpm-package-resolve.sh
    source "${SCRIPT_DIR}/swpm-package-resolve.sh"
    if swpm_load_plugin_context "$PROJECT_ROOT" "$SCRIPT_DIR" "$MAIN_DIR" 2>/dev/null; then
        JS_AMD_PREFIX=$(swpm_app_pascal_case "$SWPM_APP_ABBREV" 2>/dev/null || true)
    fi
fi
CHECK_RUNNER_ARGS=(--mode build)
[ "${STRICT_LAYOUT:-0}" -eq 1 ] && CHECK_RUNNER_ARGS+=(--strict-layout)
if [ -n "$JS_AMD_PREFIX" ] && [ -d "temp_edit/js/$JS_AMD_PREFIX" ]; then
    CHECK_RUNNER_ARGS+=(--amd-prefix "$JS_AMD_PREFIX")
fi
CHECK_RUNNER_ARGS+=("temp_edit")
if ! bash "${SCRIPT_DIR}/swpm-run-checks.sh" "${CHECK_RUNNER_ARGS[@]}"; then
    print_error "Plugin-Checks fehlgeschlagen — siehe tools/swpm-check-registry.txt"
    exit 1
fi
echo ""

# Strikte Pfadpruefung: Alle in package.xml referenzierten Quellen muessen existieren
print_info "PIP-Validierung: Pruefe Quellen (DevTools-Paritaet) in temp_edit..."
if run_pip_source_check "temp_edit" "$PACKAGE_XML"; then
    print_success "PIP-Quellen OK (sync-faehig vs. Paket-Update siehe Ausgabe oben)"
else
    print_error "PIP-Validierung fehlgeschlagen. Fehlende Quellen siehe oben."
    exit 1
fi
echo ""

# --verbose: Tree-Output vor TAR-Erstellung
[ "$VERBOSE" -eq 1 ] && output_package_tree "temp_edit"

# TARs aus temp_edit neu erstellen
print_info "[1/5] Packe TARs aus temp_edit..."

PACKAGE_DIR="$(pwd)"
cd temp_edit

# files.tar — Ordner files/ ODER lib/,acp/,style/ (siehe tools/docs/PACKAGE-LAYOUT.de.md)
FILES_TO_PACK=""
if [ -d "files" ]; then
    tar -cf "${PACKAGE_DIR}/files.tar" --exclude="app.config.inc.php" -C files .
    print_success "files.tar erstellt [aus files/]"
    FILE_COUNT=$(tar -tf "${PACKAGE_DIR}/files.tar" 2>/dev/null | wc -l)
    print_success "files.tar enthaelt ${FILE_COUNT} Datei(en)"
else
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
    # acp/uninstall/ muss ebenfalls ins WCF-Verzeichnis (PackageUninstallationDispatcher)
    tar -cf "${PACKAGE_DIR}/files.tar" --exclude="app.config.inc.php" --exclude="lib/bootstrap" --exclude="acp/uninstall" $FILES_TO_PACK
    print_success "files.tar erstellt"
    
    # Pruefe ob app.config.inc.php versehentlich enthalten ist
    if tar -tf "${PACKAGE_DIR}/files.tar" 2>/dev/null | grep -q "^app\.config\.inc\.php$"; then
        log_error_with_context "app.config.inc.php ist in files.tar enthalten!" "Diese Datei wird von WoltLab automatisch erstellt und darf nicht im Paket sein!"
        exit 1
    fi
    
    # Pruefe ob lib/bootstrap/ versehentlich enthalten ist (muss in files_wcf.tar sein)
    if tar -tf "${PACKAGE_DIR}/files.tar" 2>/dev/null | grep -q "^lib/bootstrap/"; then
        log_error_with_context "lib/bootstrap/ ist in files.tar enthalten!" "Bootstrap-Dateien müssen in files_wcf.tar sein, damit sie ins WCF-Verzeichnis kopiert werden!"
        exit 1
    fi

    # Uninstall-Skript darf nicht nur im App-Verzeichnis landen
    if tar -tf "${PACKAGE_DIR}/files.tar" 2>/dev/null | grep -q "^acp/uninstall/"; then
        log_error_with_context "acp/uninstall/ ist in files.tar enthalten!" "Uninstall-Skripte müssen in files_wcf.tar (WCF_DIR/acp/uninstall/) liegen!"
        exit 1
    fi
    
    # Pruefe ob TAR-Datei nicht leer ist
    FILE_COUNT=$(tar -tf "${PACKAGE_DIR}/files.tar" 2>/dev/null | wc -l)
    if [ "$FILE_COUNT" -eq 0 ]; then
        log_error_with_context "files.tar ist leer!" "TAR-Datei enthält keine Dateien"
        exit 1
    fi
    print_success "files.tar enthaelt ${FILE_COUNT} Datei(en)"
else
    print_warning "Keine Dateien fuer files.tar gefunden"
fi
fi

# templates.tar erstellen (kanonisch: templates/; Legacy: Root-*.tpl)
# Beide Layouts gleichzeitig = unklarer Vertrag → Fehler (nicht nur Warnung)
if [ -d "templates" ] && ls templates/*.tpl 1> /dev/null 2>&1 && ls *.tpl 1> /dev/null 2>&1; then
    ROOT_TPL_LIST=$(ls -1 *.tpl 2>/dev/null | tr '\n' ' ')
    log_error_with_context \
        "Frontend-Templates in templates/ und als Root-*.tpl" \
        "Nur eines nutzen — Root nach templates/ verschieben: ${ROOT_TPL_LIST}"
    exit 1
fi

if [ -d "templates" ]; then
    cd templates
    if ls *.tpl 1> /dev/null 2>&1; then
        tar -cf "${PACKAGE_DIR}/templates.tar" *.tpl
        TEMPLATE_COUNT=$(tar -tf "${PACKAGE_DIR}/templates.tar" 2>/dev/null | wc -l)
        print_success "templates.tar erstellt [aus templates/*.tpl] (${TEMPLATE_COUNT} Datei(en))"
    else
        print_warning "Keine .tpl Dateien in templates/ gefunden"
    fi
    cd ..
elif ls *.tpl 1> /dev/null 2>&1; then
    # Legacy-Fallback: Templates liegen direkt im temp_edit-Root
    ROOT_TPL_LIST=$(ls -1 *.tpl 2>/dev/null | tr '\n' ' ')
    print_warning "Root-*.tpl → bitte nach templates/ verschieben (WoltLab-Norm): ${ROOT_TPL_LIST}"
    if [ "${STRICT_LAYOUT:-0}" -eq 1 ]; then
        log_error_with_context "Root-*.tpl bei --strict-layout" "Verschiebe Frontend-Templates nach templates/"
        exit 1
    fi
    tar -cf "${PACKAGE_DIR}/templates.tar" *.tpl
    TEMPLATE_COUNT=$(tar -tf "${PACKAGE_DIR}/templates.tar" 2>/dev/null | wc -l)
    print_success "templates.tar erstellt (Legacy aus *.tpl) (${TEMPLATE_COUNT} Datei(en))"
else
    print_warning "Keine Templates gefunden"
fi

# acptemplates.tar erstellen
if [ -d "acptemplates" ]; then
    cd acptemplates
    if ls *.tpl 1> /dev/null 2>&1; then
        tar -cf "${PACKAGE_DIR}/acptemplates.tar" *.tpl
        ACP_TEMPLATE_COUNT=$(tar -tf "${PACKAGE_DIR}/acptemplates.tar" 2>/dev/null | wc -l)
        print_success "acptemplates.tar erstellt (${ACP_TEMPLATE_COUNT} Datei(en))"
    else
        print_warning "Keine .tpl Dateien in acptemplates/ gefunden"
    fi
    cd ..
else
    print_warning "Kein acptemplates/ Ordner gefunden"
fi

# files_wcf.tar (js/, lib/bootstrap/, acp/uninstall/ oder files_wcf/)
# acp/uninstall/{package}.php MUST land in WCF_DIR (PackageUninstallationDispatcher),
# not in the application directory — otherwise the uninstall script never runs.
WCF_FILES_TO_PACK=""
if [ -d "files_wcf" ]; then
    tar -cf "${PACKAGE_DIR}/files_wcf.tar" -C files_wcf .
    WCF_FILE_COUNT=$(tar -tf "${PACKAGE_DIR}/files_wcf.tar" 2>/dev/null | wc -l)
    print_success "files_wcf.tar erstellt [aus files_wcf/] (${WCF_FILE_COUNT} Datei(en))"
elif [ -d "js" ] || [ -d "lib/bootstrap" ] || [ -d "acp/uninstall" ]; then
if [ -d "js" ]; then
    WCF_FILES_TO_PACK="${WCF_FILES_TO_PACK} js/"
fi
if [ -d "lib/bootstrap" ]; then
    WCF_FILES_TO_PACK="${WCF_FILES_TO_PACK} lib/bootstrap/"
fi
if [ -d "acp/uninstall" ]; then
    WCF_FILES_TO_PACK="${WCF_FILES_TO_PACK} acp/uninstall/"
fi

if [ -n "$WCF_FILES_TO_PACK" ]; then
    tar -cf "${PACKAGE_DIR}/files_wcf.tar" $WCF_FILES_TO_PACK
    WCF_FILE_COUNT=$(tar -tf "${PACKAGE_DIR}/files_wcf.tar" 2>/dev/null | wc -l)
    if [ "$WCF_FILE_COUNT" -eq 0 ]; then
        log_error_with_context "files_wcf.tar ist leer!" "WCF-Dateien (js/ oder lib/bootstrap/) fehlen"
        exit 1
    fi
    
    # Pruefe ob lib/bootstrap/ enthalten ist
    if tar -tf "${PACKAGE_DIR}/files_wcf.tar" 2>/dev/null | grep -q "^lib/bootstrap/"; then
        BOOTSTRAP_COUNT=$(tar -tf "${PACKAGE_DIR}/files_wcf.tar" 2>/dev/null | grep -c "^lib/bootstrap/" || echo "0")
        print_success "files_wcf.tar erstellt (${WCF_FILE_COUNT} Datei(en), davon ${BOOTSTRAP_COUNT} Bootstrap-Datei(en))"
    else
        print_success "files_wcf.tar erstellt (${WCF_FILE_COUNT} Datei(en))"
    fi
    if tar -tf "${PACKAGE_DIR}/files_wcf.tar" 2>/dev/null | grep -q "^acp/uninstall/"; then
        print_success "files_wcf.tar enthaelt acp/uninstall/ (WCF-Uninstall-Skript)"
    elif [ -d "acp/uninstall" ]; then
        log_error_with_context "acp/uninstall/ existiert im Quellbaum, fehlt aber in files_wcf.tar!" "PackageUninstallationDispatcher liest nur WCF_DIR/acp/uninstall/"
        exit 1
    fi
else
    log_error_with_context "Keine WCF-Dateien gefunden fuer files_wcf.tar!" "js/, lib/bootstrap/ oder files_wcf/ fehlt"
    exit 1
fi
fi

# style.tar / style.tgz (style PIP) — Name aus package.xml-Instruction
if grep -qE 'type="style"' "$PACKAGE_XML" 2>/dev/null; then
    PACK_STYLE="${SCRIPT_DIR}/pack-style-tar.sh"
    STYLE_OUT="style.tar"
    STYLE_FROM_XML=$(grep -oE 'type="style"[^>]*>[^<]+' "$PACKAGE_XML" 2>/dev/null | head -1 | sed 's/.*>//' | tr -d '[:space:]' || true)
    if [ -n "${STYLE_FROM_XML:-}" ]; then
        STYLE_OUT="$STYLE_FROM_XML"
    fi
    if [ -f "style/style.xml" ] && [ -f "$PACK_STYLE" ]; then
        bash "$PACK_STYLE" "$(pwd)" "${PACKAGE_DIR}/${STYLE_OUT}"
        print_success "${STYLE_OUT} erstellt"
    else
        print_warning "style PIP in package.xml, aber style/style.xml fehlt (erwartet Archiv: ${STYLE_OUT})"
    fi
fi

cd "$PACKAGE_DIR"

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
# TOOLS_DIR = immer SWPM tools/ (SCRIPT_DIR), nie PROJECT_ROOT/../tools (externe Pakete!)
TOOLS_DIR="$(cd "${SCRIPT_DIR}" && pwd)"
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
# applicationdirectory nur bei Apps Pflicht — Plugin-/Add-on-Pakete haben keins
APPLICATION_DIR=$(grep -oP '<applicationdirectory>\K[^<]+' "$PACKAGE_XML" | head -1)
if [ -z "$APPLICATION_DIR" ]; then
    print_success "Kein applicationdirectory (Plugin/Add-on — OK)"
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
RELEASE_DIR="$(swpm_release_dir "$MAIN_DIR" "$PROJECT_ROOT")"
mkdir -p "$RELEASE_DIR"
PACKAGE_PATH="${RELEASE_DIR}/${TAR_GZ_NAME}"

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

# SQL-Dateien für PIP type="sql" (liegen im Paket-Root, nicht in files.tar)
SQL_COUNT=0
if [ -d "temp_edit" ]; then
    for sql_file in temp_edit/*.sql; do
        if [ -f "$sql_file" ]; then
            cp "$sql_file" "${TEMP_PACKAGE_DIR}/"
            SQL_COUNT=$((SQL_COUNT + 1))
        fi
    done
fi
if [ "$SQL_COUNT" -eq 0 ]; then
    for sql_file in *.sql; do
        if [ -f "$sql_file" ]; then
            cp "$sql_file" "${TEMP_PACKAGE_DIR}/"
            SQL_COUNT=$((SQL_COUNT + 1))
        fi
    done
fi
if [ "$SQL_COUNT" -gt 0 ]; then
    print_success "${SQL_COUNT} SQL-Datei(en) kopiert"
fi

# .tar.gz erstellen (Dateien direkt ohne ./ Prefix) → releases/<plugin>/
cd "${TEMP_PACKAGE_DIR}"
# Alle Dateien explizit auflisten, um ./ Prefix zu vermeiden
tar -czf "${PACKAGE_PATH}" *

cd "${PROJECT_ROOT}"

# Finale Paket-Validierung
print_info "[VALIDIERUNG] Pruefe finales Paket..."
if [ ! -f "${PACKAGE_PATH}" ]; then
    log_error_with_context "Paket ${PACKAGE_PATH} wurde nicht erstellt!" "Paket-Erstellung fehlgeschlagen"
    exit 1
fi

# Pruefe ob alle kritischen Dateien im finalen Paket vorhanden sind
print_info "Pruefe Paket-Inhalt..."
PACKAGE_CONTENT=$(tar -tzf "${PACKAGE_PATH}" 2>/dev/null || echo "")
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

# SQL-PIP: referenzierte Dateien müssen im Archiv-Root liegen
MISSING_SQL=()
while IFS= read -r sql_ref; do
    [ -z "$sql_ref" ] && continue
    if ! echo "$PACKAGE_CONTENT" | grep -q "^${sql_ref}$"; then
        MISSING_SQL+=("$sql_ref")
    fi
done < <(grep -oE '<instruction[^>]*type="sql"[^>]*>[^<]+</instruction>' "${TEMP_PACKAGE_DIR}/package.xml" 2>/dev/null | sed -n 's/.*>\([^<]*\)<.*/\1/p' | command tr -d '[:space:]' || true)
if [ ${#MISSING_SQL[@]} -gt 0 ]; then
    log_error_with_context "SQL-Datei(en) aus package.xml fehlen im Paket: ${MISSING_SQL[*]}" "PIP type=sql erwartet Dateien im Archiv-Root"
    exit 1
fi

print_success "Finale Paket-Validierung bestanden"
print_success "Paket erstellt: ${PACKAGE_PATH}"
echo ""

# Aufraeumen: Nur letzte 5 Versionen in releases/<plugin>/ behalten
print_info "[3/5] Raeume alte Pakete auf..."
KEEP_COUNT=5
PACKAGE_PATTERN="${PACKAGE_NAME}_v*.tar.gz"

# shellcheck disable=SC2086
if ls "${RELEASE_DIR}"/${PACKAGE_PATTERN} 1> /dev/null 2>&1; then
    # shellcheck disable=SC2086
    TOTAL_COUNT=$(ls -t "${RELEASE_DIR}"/${PACKAGE_PATTERN} 2>/dev/null | wc -l)
    if [ "$TOTAL_COUNT" -gt "$KEEP_COUNT" ]; then
        OLD_COUNT=$((TOTAL_COUNT - KEEP_COUNT))
        print_info "${TOTAL_COUNT} Pakete gefunden, entferne ${OLD_COUNT} aelteste..."
        # shellcheck disable=SC2086
        ls -t "${RELEASE_DIR}"/${PACKAGE_PATTERN} | tail -n +$((KEEP_COUNT + 1)) | while read -r old_package; do
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
print_info "Paket: ${PACKAGE_PATH}"
if [ "${JSON_MODE:-0}" -eq 1 ] && command -v python3 &>/dev/null && [ -f "${SCRIPT_DIR}/swpm-package-report.py" ]; then
    python3 "${SCRIPT_DIR}/swpm-package-report.py" build-ok "$PACKAGE_NAME" "$NEW_VERSION" "${PACKAGE_PATH}" "${PROJECT_ROOT}"
fi
echo ""
VALIDATE_DIR="${PROJECT_ROOT}"
case "$PROJECT_ROOT" in
    "$MAIN_DIR"/*) VALIDATE_DIR="${PROJECT_ROOT#$MAIN_DIR/}" ;;
esac
print_info "Vor Store-Release: ./tools/validate-plugin.sh ${VALIDATE_DIR} ausführen (Plugin-Store & WoltLab-Cloud Kriterien)."
echo ""


