#!/bin/bash

#################################################################
# WoltLab Plugin Builder
# Pfad: tools/build.sh
# 
# Usage:
#   ./tools/build.sh [plugin] [version] → Plugin bauen
#   ./tools/build.sh patch              → Patch-Version erhöhen (Standard)
#   ./tools/build.sh minor              → Minor-Version erhöhen
#   ./tools/build.sh major              → Major-Version erhöhen
#
# Das Script sucht automatisch nach Plugin-Verzeichnissen
# im Projekt-Root (Verzeichnisse mit package.xml)
#################################################################

set -e

# Farben
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
MAIN_DIR="$(dirname "$SCRIPT_DIR")"

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
        echo -e "${RED}❌ Fehler: ${PLUGIN_TARGET} ist kein gültiges Plugin-Verzeichnis${NC}"
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
        echo -e "${RED}❌ Fehler: Kein Plugin-Verzeichnis mit package.xml gefunden${NC}"
        echo -e "${YELLOW}  Suche in: ${MAIN_DIR}/*/package.xml${NC}"
        exit 1
    fi
fi

cd "${PROJECT_ROOT}"

# Validierung
if [[ ! "$VERSION_TYPE" =~ ^(patch|minor|major)$ ]]; then
    echo -e "${RED}❌ Fehler: Ungültiger Version-Typ '$VERSION_TYPE'${NC}"
    echo "Verwendung: ${0} [patch|minor|major]"
    exit 1
fi

PLUGIN_NAME=$(basename "$PROJECT_ROOT")
echo -e "${GREEN}========================================${NC}"
echo -e "${GREEN}WoltLab Plugin Builder${NC}"
echo -e "${GREEN}Plugin: ${PLUGIN_NAME}${NC}"
echo -e "${GREEN}Version-Typ: $VERSION_TYPE${NC}"
echo -e "${GREEN}========================================${NC}\n"

# TypeScript IMMER neu kompilieren (vor jedem Build)
# Nutze typescript.sh, das auch .min.js Dateien erstellt und 3rdParty Bibliotheken kopiert
if [ -d "temp_edit" ] && [ -d "temp_edit/ts" ]; then
    TS_COUNT=$(find temp_edit/ts -name "*.ts" 2>/dev/null | wc -l)
    if [ "$TS_COUNT" -gt 0 ]; then
        echo -e "${YELLOW}[0/5] TypeScript kompilieren (via typescript.sh)...${NC}"
        echo -e "${YELLOW}  ${TS_COUNT} TypeScript-Dateien gefunden${NC}"
        
        # Rufe typescript.sh auf (kompiliert TypeScript, erstellt .min.js, kopiert 3rdParty)
        TOOLS_DIR="$(cd "${SCRIPT_DIR}" && pwd)"
        TYPESCRIPT_SCRIPT="${TOOLS_DIR}/typescript.sh"
        
        if [ ! -f "$TYPESCRIPT_SCRIPT" ]; then
            echo -e "${RED}❌ FEHLER: typescript.sh nicht gefunden in ${TYPESCRIPT_SCRIPT}${NC}"
            exit 1
        fi
        
        # Führe typescript.sh aus (ohne watch-Mode)
        bash "$TYPESCRIPT_SCRIPT"
        TSC_EXIT=$?
        
        if [ $TSC_EXIT -eq 0 ]; then
            echo -e "${GREEN}✓ TypeScript kompiliert (via typescript.sh)${NC}"
            
            # Prüfe ob JavaScript-Dateien nach Kompilierung existieren
            JS_COUNT=$(find temp_edit/js -name "*.js" ! -name "*.min.js" 2>/dev/null | wc -l)
            if [ "$JS_COUNT" -eq 0 ] && [ "$TS_COUNT" -gt 0 ]; then
                echo -e "${RED}❌ FEHLER: Keine JavaScript-Dateien nach Kompilierung gefunden!${NC}"
                echo -e "${RED}   ${TS_COUNT} TypeScript-Dateien gefunden, aber 0 JavaScript-Dateien erstellt${NC}"
                exit 1
            else
                echo -e "${GREEN}  ✓ ${JS_COUNT} JavaScript-Dateien erstellt${NC}"
            fi
            
            # ZUSÄTZLICHE VALIDIERUNG: Prüfe ob .js Dateien neuer sind als .ts Dateien
            # Wenn eine .ts Datei neuer ist als die entsprechende .js Datei, wurde nicht neu kompiliert!
            echo -e "${YELLOW}  Prüfe Synchronisation von .ts und .js Dateien...${NC}"
            UNSYNCED_TS_JS=()
            mapfile -t ts_files < <(find temp_edit/ts -name "*.ts" -type f 2>/dev/null)
            for ts_file in "${ts_files[@]}"; do
                # Konvertiere ts/.../file.ts zu js/.../file.js
                js_file="${ts_file#temp_edit/ts/}"
                js_file="${js_file%.ts}.js"
                js_path="temp_edit/js/${js_file}"
                
                if [ -f "$js_path" ]; then
                    # Prüfe ob .ts Datei neuer ist als .js Datei
                    if [ "$ts_file" -nt "$js_path" ]; then
                        UNSYNCED_TS_JS+=("$ts_file (neuer als $js_path)")
                    fi
                fi
            done
            
            if [ ${#UNSYNCED_TS_JS[@]} -gt 0 ]; then
                echo -e "${RED}❌ FEHLER: ${#UNSYNCED_TS_JS[@]} TypeScript-Datei(en) sind neuer als ihre .js Dateien!${NC}"
                echo -e "${RED}   → TypeScript wurde nicht korrekt neu kompiliert!${NC}"
                for unsynced in "${UNSYNCED_TS_JS[@]}"; do
                    echo -e "${RED}   - $unsynced${NC}"
                done
                echo -e "${YELLOW}  → Führe typescript.sh manuell aus, um zu synchronisieren${NC}"
                exit 1
            else
                echo -e "${GREEN}  ✓ Alle .ts und .js Dateien sind synchronisiert${NC}"
            fi
            
            # KRITISCHE VALIDIERUNG: Prüfe ob .js und .min.js Dateien identisch sind
            echo -e "${YELLOW}  Prüfe dass alle .js und .min.js Dateien identisch sind...${NC}"
            JS_MIN_JS_ERRORS=0
            mapfile -t all_js_files < <(find temp_edit/js -name "*.js" ! -name "*.min.js" ! -path "*/3rdParty/*" -type f 2>/dev/null)
            for js_file in "${all_js_files[@]}"; do
                min_file="${js_file%.js}.min.js"
                if [ ! -f "$min_file" ]; then
                    echo -e "${RED}   ❌ $min_file fehlt für $js_file${NC}"
                    JS_MIN_JS_ERRORS=$((JS_MIN_JS_ERRORS + 1))
                elif ! diff -q "$js_file" "$min_file" > /dev/null 2>&1; then
                    echo -e "${RED}   ❌ $js_file und $min_file sind NICHT identisch!${NC}"
                    JS_MIN_JS_ERRORS=$((JS_MIN_JS_ERRORS + 1))
                fi
            done
            
            if [ "$JS_MIN_JS_ERRORS" -gt 0 ]; then
                echo -e "${RED}❌ KRITISCHER FEHLER: ${JS_MIN_JS_ERRORS} .js/.min.js Synchronisationsfehler gefunden!${NC}"
                echo -e "${RED}   → .js und .min.js Dateien MÜSSEN identisch sein!${NC}"
                echo -e "${RED}   → Build wird abgebrochen!${NC}"
                exit 1
            else
                echo -e "${GREEN}  ✓ Alle .js und .min.js Dateien sind identisch${NC}"
            fi
            
            echo ""
        else
            echo -e "${RED}❌ TypeScript-Kompilierung fehlgeschlagen (typescript.sh Exit-Code: ${TSC_EXIT})!${NC}"
            exit 1
        fi
    else
        echo -e "${GREEN}✓ Keine TypeScript-Dateien gefunden, überspringe Kompilierung${NC}\n"
    fi
fi

# Prüfe ob temp_edit existiert
if [ ! -d "temp_edit" ]; then
    echo -e "${RED}❌ Fehler: temp_edit Ordner nicht gefunden${NC}"
    echo -e "${YELLOW}  Bitte entpacke zuerst die TARs:${NC}"
    echo -e "${YELLOW}  rm -rf temp_edit && mkdir temp_edit${NC}"
    echo -e "${YELLOW}  tar -xf files.tar -C temp_edit${NC}"
    echo -e "${YELLOW}  tar -xf templates.tar -C temp_edit${NC}"
    echo -e "${YELLOW}  tar -xf acptemplates.tar -C temp_edit${NC}"
    exit 1
fi

# Version aus package.xml lesen
if [ ! -f "package.xml" ]; then
    echo -e "${RED}❌ Fehler: package.xml nicht gefunden${NC}"
    exit 1
fi

CURRENT_VERSION=$(grep -oP '<version>\K[^<]+' "package.xml" 2>/dev/null || echo "")
if [ -z "$CURRENT_VERSION" ]; then
    echo -e "${RED}❌ Fehler: Version nicht in package.xml gefunden${NC}"
    exit 1
fi

echo -e "${YELLOW}Aktuelle Version: $CURRENT_VERSION${NC}"

# Version erhöhen
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

echo -e "${YELLOW}Neue Version: $NEW_VERSION${NC}\n"

# Version und Datum in package.xml aktualisieren
sed -i "s/<version>${CURRENT_VERSION}<\/version>/<version>${NEW_VERSION}<\/version>/" package.xml
sed -i "s/<date>[^<]*<\/date>/<date>${TODAY}<\/date>/" package.xml

echo -e "${GREEN}✓ package.xml aktualisiert${NC}"

# TARs aus temp_edit neu erstellen
echo -e "${YELLOW}[1/5] Packe TARs aus temp_edit...${NC}"

cd temp_edit

# files.tar erstellen (lib/, acp/, style/, PHP-Dateien, aber keine Templates)
# WICHTIG: app.config.inc.php NICHT packen - wird von WoltLab automatisch erstellt!
# Vor jeder Installation muss die Datenbank bereinigt werden (alte shrinkr-Einträge löschen)
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
    echo -e "${GREEN}✓ files.tar erstellt${NC}"
    
    # Prüfe ob app.config.inc.php versehentlich enthalten ist
    if tar -tf ../files.tar 2>/dev/null | grep -q "^app\.config\.inc\.php$"; then
        echo -e "${RED}❌ FEHLER: app.config.inc.php ist in files.tar enthalten!${NC}"
        echo -e "${RED}   → Diese Datei wird von WoltLab automatisch erstellt und darf nicht im Paket sein!${NC}"
        exit 1
    fi
else
    echo -e "${YELLOW}⚠ Keine Dateien für files.tar gefunden${NC}"
fi

# templates.tar erstellen (Dateien direkt im Root, keine Verzeichnisse!)
if [ -d "templates" ]; then
    cd templates
    if ls *.tpl 1> /dev/null 2>&1; then
        # VALIDIERUNG: Prüfe ob redirect.tpl die neuen Logs enthält
        if [ -f "redirect.tpl" ]; then
            if grep -q "Rufe setupFunc auf" "redirect.tpl" 2>/dev/null; then
                echo -e "${GREEN}  ✓ redirect.tpl enthält neue Debug-Logs${NC}"
            else
                echo -e "${RED}❌ FEHLER: redirect.tpl enthält KEINE neuen Debug-Logs!${NC}"
                echo -e "${RED}   → Template wurde möglicherweise nicht korrekt aktualisiert!${NC}"
                exit 1
            fi
        fi
        
        tar -cf ../../templates.tar *.tpl
        echo -e "${GREEN}✓ templates.tar erstellt (aus templates/*.tpl)${NC}"
        
        # VALIDIERUNG: Prüfe ob redirect.tpl im TAR die neuen Logs enthält
        if tar -xOf ../../templates.tar redirect.tpl 2>/dev/null | grep -q "Rufe setupFunc auf"; then
            echo -e "${GREEN}  ✓ redirect.tpl im TAR enthält neue Debug-Logs${NC}"
        else
            echo -e "${RED}❌ FEHLER: redirect.tpl im TAR enthält KEINE neuen Debug-Logs!${NC}"
            exit 1
        fi
    else
        echo -e "${YELLOW}⚠ Keine .tpl Dateien in templates/ gefunden${NC}"
    fi
    cd ..
elif ls *.tpl 1> /dev/null 2>&1; then
    # Templates liegen direkt im temp_edit
    # VALIDIERUNG: Prüfe ob redirect.tpl die neuen Logs enthält
    if [ -f "redirect.tpl" ]; then
        if grep -q "Rufe setupFunc auf" "redirect.tpl" 2>/dev/null; then
            echo -e "${GREEN}  ✓ redirect.tpl enthält neue Debug-Logs${NC}"
        else
            echo -e "${RED}❌ FEHLER: redirect.tpl enthält KEINE neuen Debug-Logs!${NC}"
            exit 1
        fi
    fi
    
    tar -cf ../templates.tar *.tpl
    echo -e "${GREEN}✓ templates.tar erstellt (aus *.tpl)${NC}"
    
    # VALIDIERUNG: Prüfe ob redirect.tpl im TAR die neuen Logs enthält
    if tar -xOf ../templates.tar redirect.tpl 2>/dev/null | grep -q "Rufe setupFunc auf"; then
        echo -e "${GREEN}  ✓ redirect.tpl im TAR enthält neue Debug-Logs${NC}"
    else
        echo -e "${RED}❌ FEHLER: redirect.tpl im TAR enthält KEINE neuen Debug-Logs!${NC}"
        exit 1
    fi
else
    echo -e "${YELLOW}⚠ Keine Templates gefunden${NC}"
fi

# acptemplates.tar erstellen (Dateien direkt im Root, keine Verzeichnisse!)
if [ -d "acptemplates" ]; then
    cd acptemplates
    if ls *.tpl 1> /dev/null 2>&1; then
        tar -cf ../../acptemplates.tar *.tpl
        echo -e "${GREEN}✓ acptemplates.tar erstellt${NC}"
    else
        echo -e "${YELLOW}⚠ Keine .tpl Dateien in acptemplates/ gefunden${NC}"
    fi
    cd ..
else
    echo -e "${YELLOW}⚠ Kein acptemplates/ Ordner gefunden${NC}"
fi

# files_wcf.tar erstellen (JavaScript-Dateien für WCF)
if [ -d "js" ]; then
    # Prüfe ob wichtige JS-Dateien existieren, bevor wir packen
    if [ -f "js/Shrinkr/Ui/Redirect/PasswordDialog.js" ]; then
        # Prüfe ob Datei die erwarteten Logs enthält
        if grep -q "setup() aufgerufen" "js/Shrinkr/Ui/Redirect/PasswordDialog.js" 2>/dev/null; then
            echo -e "${GREEN}  ✓ PasswordDialog.js enthält erwartete Logs vor dem Packen${NC}"
        else
            echo -e "${RED}❌ FEHLER: PasswordDialog.js enthält KEINE erwarteten Logs!${NC}"
            echo -e "${RED}   → Datei wurde möglicherweise nicht korrekt kompiliert!${NC}"
            exit 1
        fi
    fi
    
    tar -cf ../files_wcf.tar js/
    echo -e "${GREEN}✓ files_wcf.tar erstellt${NC}"
    
    # VALIDIERUNG: Prüfe ob PasswordDialog.js wirklich im TAR ist
    if tar -tf ../files_wcf.tar 2>/dev/null | grep -q "js/Shrinkr/Ui/Redirect/PasswordDialog.js"; then
        echo -e "${GREEN}  ✓ PasswordDialog.js ist in files_wcf.tar enthalten${NC}"
        
        # Prüfe ob die Datei im TAR die erwarteten Logs enthält
        if tar -xOf ../files_wcf.tar js/Shrinkr/Ui/Redirect/PasswordDialog.js 2>/dev/null | grep -q "setup() aufgerufen"; then
            echo -e "${GREEN}  ✓ PasswordDialog.js im TAR enthält erwartete Logs${NC}"
        else
            echo -e "${RED}❌ FEHLER: PasswordDialog.js im TAR enthält KEINE erwarteten Logs!${NC}"
            exit 1
        fi
    else
        echo -e "${RED}❌ FEHLER: PasswordDialog.js fehlt in files_wcf.tar!${NC}"
        exit 1
    fi
else
    echo -e "${RED}❌ FEHLER: Kein js/ Ordner gefunden für files_wcf.tar${NC}"
    exit 1
fi

cd ..

echo -e "${GREEN}✓ TARs erfolgreich erstellt${NC}\n"

# ========================================
# VALIDIERUNGEN - Sicherheitsmechanismen
# ========================================
echo -e "${YELLOW}[VALIDIERUNG] Prüfe Paket-Integrität...${NC}"

VALIDATION_ERRORS=0

# 1. KRITISCH: Prüfe package.xml: files_wcf.tar MUSS application="wcf" haben
#    JavaScript-Dateien müssen ins WCF-Verzeichnis installiert werden
if ! grep -qE '<instruction[^>]*type="file"[^>]*application\s*=\s*["\047]wcf["\047][^>]*>files_wcf\.tar</instruction>' package.xml && \
   ! grep -qE '<instruction[^>]*application\s*=\s*["\047]wcf["\047][^>]*type="file"[^>]*>files_wcf\.tar</instruction>' package.xml; then
    echo -e "${RED}❌ KRITISCHER FEHLER: files_wcf.tar hat KEIN application=\"wcf\" in package.xml!${NC}"
    echo -e "${RED}   → JavaScript-Dateien müssen ins WCF-Verzeichnis installiert werden!${NC}"
    echo -e "${RED}   → Korrekt: <instruction type=\"file\" application=\"wcf\">files_wcf.tar</instruction>${NC}"
    VALIDATION_ERRORS=$((VALIDATION_ERRORS + 1))
fi

# 1b. Prüfe dass files_wcf.tar application="wcf" hat (nicht application="shrinkr" oder ohne)
FILES_WCF_LINE=$(grep -E 'files_wcf\.tar' package.xml | head -1)
if ! echo "$FILES_WCF_LINE" | grep -qE 'application\s*=\s*["\047]wcf["\047]'; then
    echo -e "${RED}❌ KRITISCHER FEHLER: files_wcf.tar hat nicht application=\"wcf\" in package.xml!${NC}"
    echo -e "${RED}   → Gefunden: ${FILES_WCF_LINE}${NC}"
    echo -e "${RED}   → files_wcf.tar MUSS application=\"wcf\" haben!${NC}"
    echo -e "${RED}   → Korrekt: <instruction type=\"file\" application=\"wcf\">files_wcf.tar</instruction>${NC}"
    VALIDATION_ERRORS=$((VALIDATION_ERRORS + 1))
else
    echo -e "${GREEN}  ✓ files_wcf.tar hat application=\"wcf\" (korrekt)${NC}"
fi

# 2. Prüfe ob files_wcf.tar existiert
if [ ! -f "files_wcf.tar" ]; then
    echo -e "${RED}❌ FEHLER: files_wcf.tar nicht gefunden!${NC}"
    VALIDATION_ERRORS=$((VALIDATION_ERRORS + 1))
else
    # 3. Prüfe ob files_wcf.tar JavaScript-Dateien enthält
    JS_FILES_IN_TAR=$(tar -tf files_wcf.tar 2>/dev/null | grep -c "\.js$" || echo "0")
    if [ "$JS_FILES_IN_TAR" -eq "0" ]; then
        echo -e "${RED}❌ FEHLER: files_wcf.tar enthält keine JavaScript-Dateien!${NC}"
        VALIDATION_ERRORS=$((VALIDATION_ERRORS + 1))
    else
        echo -e "${GREEN}  ✓ files_wcf.tar enthält ${JS_FILES_IN_TAR} JavaScript-Dateien${NC}"
    fi
    
    # 4. Prüfe kritische JavaScript-Dateien
    CRITICAL_JS_FILES=(
        "js/Shrinkr/Ui/Redirect/PasswordDialog.js"
        "js/Shrinkr/Acp/Ui/Statistics/TimeSeriesChart.js"
        "js/3rdParty/d3/d3.js"
    )
    
    for js_file in "${CRITICAL_JS_FILES[@]}"; do
        if ! tar -tf files_wcf.tar 2>/dev/null | grep -q "^${js_file}$"; then
            echo -e "${RED}❌ FEHLER: Kritische Datei fehlt in files_wcf.tar: ${js_file}${NC}"
            VALIDATION_ERRORS=$((VALIDATION_ERRORS + 1))
        else
            echo -e "${GREEN}  ✓ ${js_file} vorhanden${NC}"
        fi
    done
    
    # 5. Prüfe ob d3.min.js (falsch) vorhanden ist statt d3.js (korrekt)
    if tar -tf files_wcf.tar 2>/dev/null | grep -q "js/3rdParty/d3/d3\.min\.js$"; then
        echo -e "${RED}❌ FEHLER: d3.min.js gefunden (falsch)! Sollte d3.js sein (ohne .min)${NC}"
        echo -e "${RED}   → Woltlab fügt automatisch .min.js hinzu, Dateiname darf kein .min enthalten${NC}"
        VALIDATION_ERRORS=$((VALIDATION_ERRORS + 1))
    fi
fi

# 6. Prüfe Template-Referenzen: Finde alle {js application='shrinkr' file='...'} Referenzen
echo -e "${YELLOW}  Prüfe Template-Referenzen...${NC}"
TEMPLATE_JS_REFS=$(grep -rh "{js application='shrinkr' file=" temp_edit/templates/ temp_edit/acptemplates/ 2>/dev/null | \
    grep -oP "file='\K[^']+" | sort -u || true)

if [ -n "$TEMPLATE_JS_REFS" ]; then
    while IFS= read -r js_ref; do
        # Konvertiere Template-Referenz zu Dateipfad (z.B. "Shrinkr/Ui/Redirect/PasswordDialog" -> "js/Shrinkr/Ui/Redirect/PasswordDialog.js")
        js_path="js/${js_ref}.js"
        if [ -f "files_wcf.tar" ] && tar -tf files_wcf.tar 2>/dev/null | grep -q "^${js_path}$"; then
            echo -e "${GREEN}  ✓ Template-Referenz gefunden: ${js_ref} → ${js_path}${NC}"
        else
            echo -e "${RED}❌ FEHLER: Template referenziert JavaScript-Datei, die nicht in files_wcf.tar vorhanden ist!${NC}"
            echo -e "${RED}   → Template: {js application='shrinkr' file='${js_ref}'}${NC}"
            echo -e "${RED}   → Erwartet: ${js_path}${NC}"
            VALIDATION_ERRORS=$((VALIDATION_ERRORS + 1))
        fi
    done <<< "$TEMPLATE_JS_REFS"
fi

# 7. Prüfe ob alle referenzierten TARs existieren
REQUIRED_TARS=("files.tar" "templates.tar" "acptemplates.tar" "files_wcf.tar")
for tar_file in "${REQUIRED_TARS[@]}"; do
    if [ ! -f "$tar_file" ]; then
        echo -e "${YELLOW}  ⚠ Warnung: ${tar_file} nicht gefunden (kann optional sein)${NC}"
    fi
done

# 8. Prüfe package.xml: Alle referenzierten Dateien müssen existieren
echo -e "${YELLOW}  Prüfe package.xml Referenzen...${NC}"
PACKAGE_XML_FILES=$(grep -oP '<instruction[^>]*>\K[^<]+' package.xml | grep -v "^$" | sort -u || true)
if [ -n "$PACKAGE_XML_FILES" ]; then
    while IFS= read -r xml_file; do
        # Überspringe leere Instructions und bekannte Standard-Dateien
        if [[ "$xml_file" =~ ^(files\.tar|templates\.tar|acptemplates\.tar|files_wcf\.tar)$ ]] || \
           [[ "$xml_file" =~ \.(xml|php)$ ]] && [ -f "$xml_file" ] || [ -f "temp_edit/$xml_file" ]; then
            echo -e "${GREEN}  ✓ package.xml Referenz gefunden: ${xml_file}${NC}"
        elif [[ "$xml_file" =~ \.(xml|php)$ ]]; then
            echo -e "${YELLOW}  ⚠ Warnung: package.xml referenziert ${xml_file}, aber Datei nicht gefunden${NC}"
        fi
    done <<< "$PACKAGE_XML_FILES"
fi

# 9. KRITISCH: Prüfe dass keine alten build.sh oder typescript.sh im Root oder temp_edit existieren
# TOOLS_DIR muss vor dieser Prüfung definiert werden
TOOLS_DIR="$(cd "${PROJECT_ROOT}/../tools" && pwd)"
echo -e "${YELLOW}  Prüfe auf redundante Script-Dateien...${NC}"
if [ -f "build.sh" ]; then
    echo -e "${RED}❌ KRITISCHER FEHLER: build.sh existiert noch im Root-Verzeichnis!${NC}"
    echo -e "${RED}   → Sollte nur in ${TOOLS_DIR}/build.sh existieren!${NC}"
    echo -e "${RED}   → Bitte löschen: rm build.sh${NC}"
    VALIDATION_ERRORS=$((VALIDATION_ERRORS + 1))
else
    echo -e "${GREEN}  ✓ Keine build.sh im Root gefunden${NC}"
fi

if [ -f "temp_edit/typescript.sh" ]; then
    echo -e "${RED}❌ KRITISCHER FEHLER: typescript.sh existiert noch in temp_edit/!${NC}"
    echo -e "${RED}   → Sollte nur in ${TOOLS_DIR}/typescript.sh existieren!${NC}"
    echo -e "${RED}   → Bitte löschen: rm temp_edit/typescript.sh${NC}"
    VALIDATION_ERRORS=$((VALIDATION_ERRORS + 1))
else
    echo -e "${GREEN}  ✓ Keine typescript.sh in temp_edit/ gefunden${NC}"
fi

# 10. Prüfe dass tools/build.sh und tools/typescript.sh existieren (außerhalb von basis-plugin)
if [ ! -f "${TOOLS_DIR}/build.sh" ]; then
    echo -e "${RED}❌ FEHLER: ${TOOLS_DIR}/build.sh nicht gefunden!${NC}"
    VALIDATION_ERRORS=$((VALIDATION_ERRORS + 1))
else
    echo -e "${GREEN}  ✓ tools/build.sh vorhanden${NC}"
fi

if [ ! -f "${TOOLS_DIR}/typescript.sh" ]; then
    echo -e "${RED}❌ FEHLER: ${TOOLS_DIR}/typescript.sh nicht gefunden!${NC}"
    VALIDATION_ERRORS=$((VALIDATION_ERRORS + 1))
else
    echo -e "${GREEN}  ✓ tools/typescript.sh vorhanden${NC}"
fi

# 11. Prüfe package.xml Struktur: applicationdirectory muss "shrinkr" sein
APPLICATION_DIR=$(grep -oP '<applicationdirectory>\K[^<]+' package.xml | head -1)
if [ "$APPLICATION_DIR" != "shrinkr" ]; then
    echo -e "${RED}❌ FEHLER: applicationdirectory ist nicht 'shrinkr'! Gefunden: '${APPLICATION_DIR}'${NC}"
    VALIDATION_ERRORS=$((VALIDATION_ERRORS + 1))
else
    echo -e "${GREEN}  ✓ applicationdirectory korrekt: ${APPLICATION_DIR}${NC}"
fi

# 12. Prüfe dass files.tar keine JavaScript-Dateien enthält (sollten in files_wcf.tar sein)
if [ -f "files.tar" ]; then
    JS_IN_FILES_TAR=$(tar -tf files.tar 2>/dev/null | grep -c "\.js$" 2>/dev/null || echo "0")
    # Stelle sicher, dass JS_IN_FILES_TAR eine Zahl ist
    if ! [[ "$JS_IN_FILES_TAR" =~ ^[0-9]+$ ]]; then
        JS_IN_FILES_TAR=0
    fi
    if [ "$JS_IN_FILES_TAR" -gt 0 ]; then
        echo -e "${YELLOW}  ⚠ Warnung: files.tar enthält ${JS_IN_FILES_TAR} JavaScript-Dateien${NC}"
        echo -e "${YELLOW}   → JavaScript-Dateien sollten in files_wcf.tar sein, nicht in files.tar${NC}"
    fi
fi

# Validierungsfehler zusammenfassen
if [ "$VALIDATION_ERRORS" -gt 0 ]; then
    echo -e "\n${RED}========================================${NC}"
    echo -e "${RED}❌ VALIDIERUNG FEHLGESCHLAGEN!${NC}"
    echo -e "${RED}========================================${NC}"
    echo -e "${RED}${VALIDATION_ERRORS} kritische Fehler gefunden!${NC}"
    echo -e "${RED}Das Paket wird NICHT erstellt, bis alle Fehler behoben sind.${NC}\n"
    exit 1
else
    echo -e "${GREEN}✓ Alle Validierungen bestanden${NC}\n"
fi

# Package-Name aus package.xml lesen
PACKAGE_NAME=$(grep -oP '<package name="\K[^"]+' "package.xml" | head -1)
if [ -z "$PACKAGE_NAME" ]; then
    echo -e "${RED}❌ Fehler: Package-Name nicht in package.xml gefunden${NC}"
    exit 1
fi

# Finales .tar.gz Paket erstellen
echo -e "${YELLOW}[2/3] Erstelle finales Paket...${NC}"

TAR_GZ_NAME="${PACKAGE_NAME}_v${NEW_VERSION}.tar.gz"

# Temporäres Verzeichnis für Paket-Erstellung
TEMP_PACKAGE_DIR=$(mktemp -d)
trap "rm -rf ${TEMP_PACKAGE_DIR}" EXIT

# Alle Dateien ins temporäre Verzeichnis kopieren
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
echo -e "${YELLOW}[VALIDIERUNG] Prüfe finales Paket...${NC}"
if [ ! -f "${TAR_GZ_NAME}" ]; then
    echo -e "${RED}❌ FEHLER: Paket ${TAR_GZ_NAME} wurde nicht erstellt!${NC}"
    exit 1
fi

# Prüfe ob files_wcf.tar im finalen Paket vorhanden ist
if ! tar -tzf "${TAR_GZ_NAME}" 2>/dev/null | grep -q "^files_wcf.tar$"; then
    echo -e "${RED}❌ FEHLER: files_wcf.tar fehlt im finalen Paket!${NC}"
    exit 1
fi

# Prüfe ob PasswordDialog.js im finalen Paket vorhanden ist
if ! tar -xOf "${TAR_GZ_NAME}" files_wcf.tar 2>/dev/null | tar -tf - 2>/dev/null | grep -q "js/Shrinkr/Ui/Redirect/PasswordDialog.js"; then
    echo -e "${RED}❌ FEHLER: PasswordDialog.js fehlt im finalen Paket!${NC}"
    exit 1
fi

echo -e "${GREEN}✓ Finale Paket-Validierung bestanden${NC}\n"
echo -e "${GREEN}✓ Paket erstellt: ${TAR_GZ_NAME}${NC}\n"

# Aufräumen: Nur letzte 5 Versionen behalten
echo -e "${YELLOW}[3/5] Räume alte Pakete auf...${NC}"
KEEP_COUNT=5
PACKAGE_PATTERN="${PACKAGE_NAME}_v*.tar.gz"

if ls ${PACKAGE_PATTERN} 1> /dev/null 2>&1; then
    TOTAL_COUNT=$(ls -t ${PACKAGE_PATTERN} 2>/dev/null | wc -l)
    if [ "$TOTAL_COUNT" -gt "$KEEP_COUNT" ]; then
        OLD_COUNT=$((TOTAL_COUNT - KEEP_COUNT))
        echo -e "${YELLOW}  ${TOTAL_COUNT} Pakete gefunden, entferne ${OLD_COUNT} älteste...${NC}"
        ls -t ${PACKAGE_PATTERN} | tail -n +$((KEEP_COUNT + 1)) | while read -r old_package; do
            rm -v "$old_package"
        done
        echo -e "${GREEN}  ✓ Aufräumen abgeschlossen: ${KEEP_COUNT} Pakete behalten${NC}\n"
    else
        echo -e "${GREEN}  ✓ ${TOTAL_COUNT} Pakete vorhanden (kein Aufräumen nötig)${NC}\n"
    fi
fi

echo -e "${GREEN}========================================${NC}"
echo -e "${GREEN}✓ Build abgeschlossen!${NC}"
echo -e "${GREEN}========================================${NC}"
echo -e "${GREEN}Version: ${NEW_VERSION}${NC}"
echo -e "${GREEN}Paket: ${TAR_GZ_NAME}${NC}"
echo -e "${GREEN}========================================${NC}\n"
