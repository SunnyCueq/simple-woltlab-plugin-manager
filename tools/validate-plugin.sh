#!/bin/bash

#################################################################
# WoltLab Plugin Validierung mit Security-Checks
# Pfad: tools/validate-plugin.sh
# 
# Prüft Plugin-Struktur, Security (SQL-Injection, XSS), 
# Code-Qualität und Plugin-Store-Compliance
#
# Store-Mapping: tools/docs/PLUGIN-STORE-CHECKLIST.md (Stand 2026-06-26)
#
# Usage:
#   ./tools/validate-plugin.sh [PLUGIN_DIR]
#   Falls PLUGIN_DIR nicht angegeben, wird das aktuelle Verzeichnis verwendet
#################################################################

set -euo pipefail

#=====================================
# KONFIGURATION
#=====================================
readonly TOOLS_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
readonly MAIN_DIR="$(dirname "$TOOLS_DIR")"

#=====================================
# QUELLEN
#=====================================
if [ -f "$TOOLS_DIR/common.sh" ]; then
    source "$TOOLS_DIR/common.sh"
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
    
    # Fallback-Funktionen verwenden CHECK, CROSS, WARNING, ARROW aus common.sh falls verfügbar
    print_success() { echo -e "${GREEN}${CHECK:-✓} $1${NC}"; }
    print_error() { echo -e "${RED}${CROSS:-✗} $1${NC}"; }
    print_warning() { echo -e "${YELLOW}${WARNING:-⚠} $1${NC}"; }
    print_info() { echo -e "${CYAN}${ARROW:-→} $1${NC}"; }
fi

#=====================================
# HAUPTLOGIK
#=====================================
LOG_FILE="/tmp/validate-plugin-$(date +%Y%m%d-%H%M%S).log"
VERBOSE=false

# Validierungs-Zähler
ERRORS=0
WARNINGS=0

# Logging-Funktion
log() {
    local level="$1"
    shift
    local message="$*"
    local timestamp=$(date '+%Y-%m-%d %H:%M:%S')
    echo "[$timestamp] [$level] $message" >> "$LOG_FILE"

    if [ "$VERBOSE" = true ] || [ "$level" = "ERROR" ] || [ "$level" = "WARNING" ]; then
        echo "$message" >&2
    fi
}

log "INFO" "Validierung gestartet"

# Plugin-Verzeichnis bestimmen
if [ $# -ge 1 ]; then
    PLUGIN_DIR="$1"
    # Erweitere relative Pfade
    if [[ ! "$PLUGIN_DIR" =~ ^/ ]]; then
        PLUGIN_DIR="$MAIN_DIR/$PLUGIN_DIR"
    fi
else
    PLUGIN_DIR="$(pwd)"
fi

log "INFO" "Plugin-Verzeichnis: $PLUGIN_DIR"

cd "$PLUGIN_DIR" || {
    print_error "Konnte nicht in Plugin-Verzeichnis wechseln: $PLUGIN_DIR"
    log "ERROR" "Konnte nicht in Plugin-Verzeichnis wechseln: $PLUGIN_DIR"
    exit 1
}

print_header
echo ""
echo -e "${CYAN}Verzeichnis:${NC} $PLUGIN_DIR"
echo ""

# 1. Prüfe package.xml
echo -e "${YELLOW}🔍 Prüfe package.xml...${NC}"
log "INFO" "Prüfe package.xml"

if [ ! -f "package.xml" ]; then
    print_error "package.xml nicht gefunden!"
    log "ERROR" "package.xml nicht gefunden"
    ERRORS=$((ERRORS + 1))
else
    print_success "package.xml gefunden"
    log "INFO" "package.xml gefunden"

    # XML-Syntax prüfen (falls xmllint verfügbar)
    if command -v xmllint &> /dev/null; then
        if xmllint --noout package.xml 2>/dev/null; then
            print_success "XML-Syntax ist korrekt"
            log "INFO" "XML-Syntax ist korrekt"
        else
            print_error "XML-Syntax-Fehler in package.xml!"
            log "ERROR" "XML-Syntax-Fehler in package.xml"
            ERRORS=$((ERRORS + 1))
        fi
    else
        print_warning "xmllint nicht installiert, überspringe XML-Validierung"
        log "WARNING" "xmllint nicht gefunden, überspringe XML-Validierung"
        WARNINGS=$((WARNINGS + 1))
    fi

    # Package-Name prüfen
    PACKAGE_NAME=$(grep -oP 'name="\K[^"]+' package.xml 2>/dev/null | head -1)
    if [ -z "$PACKAGE_NAME" ]; then
        print_error "Konnte Package-Name nicht aus package.xml extrahieren!"
        log "ERROR" "Konnte Package-Name nicht extrahieren"
        ERRORS=$((ERRORS + 1))
    else
        echo -e "   ${CYAN}Package-Name:${NC} $PACKAGE_NAME"
        log "INFO" "Package-Name: $PACKAGE_NAME"

        # Package-Name-Format prüfen
        if [[ ! "$PACKAGE_NAME" =~ ^com\.[a-z0-9]+\.[a-z0-9]+(\.[a-z0-9]+)*$ ]]; then
            print_warning "Package-Name entspricht möglicherweise nicht dem Standard-Format!"
            echo -e "   ${YELLOW}Erwartetes Format:${NC} com.domain.pluginname"
            echo -e "   ${YELLOW}Gefundener Name:${NC} $PACKAGE_NAME"
            log "WARNING" "Package-Name-Format möglicherweise nicht standardkonform: $PACKAGE_NAME"
            WARNINGS=$((WARNINGS + 1))
        else
            print_success "Package-Name-Format ist korrekt"
            log "INFO" "Package-Name-Format ist korrekt"
        fi
    fi

    # Version prüfen
    VERSION=$(grep -oP '<version>\K[^<]+' package.xml 2>/dev/null | head -1)
    if [ -z "$VERSION" ]; then
        print_warning "Konnte Version nicht aus package.xml extrahieren"
        log "WARNING" "Konnte Version nicht extrahieren"
        WARNINGS=$((WARNINGS + 1))
    else
        echo -e "   ${CYAN}Version:${NC} $VERSION"
        log "INFO" "Version: $VERSION"
    fi

    # Minversion-Validierung (Plugin Store Requirement)
    MINVERSION=$(grep -oP '<requiredpackage minversion="\K[^"]+' package.xml 2>/dev/null | head -1) || MINVERSION=""

    if [ -n "$MINVERSION" ]; then
        echo -e "   ${CYAN}Minversion:${NC} $MINVERSION"
        log "INFO" "Minversion: $MINVERSION"

        if [[ "$MINVERSION" =~ ^6\.[0-2]\. ]]; then
            print_success "Minversion ist WoltLab 6.0/6.1/6.2 kompatibel"
            log "INFO" "Minversion ist WoltLab 6.0/6.1/6.2 kompatibel"
        elif [[ "$MINVERSION" =~ ^7\. ]]; then
            print_warning "Minversion 7.x - WoltLab 7 ist noch nicht released"
            echo -e "   ${YELLOW}Plugin Store akzeptiert nur unterstützte Versionen!${NC}"
            log "WARNING" "Minversion 7.x - noch nicht released"
            WARNINGS=$((WARNINGS + 1))
        elif [[ "$MINVERSION" =~ ^5\. ]]; then
            print_warning "Minversion 5.x - veraltete Core-Version"
            echo -e "   ${YELLOW}Empfehlung:${NC} Upgrade auf 6.0.0 für Plugin Store"
            log "WARNING" "Minversion 5.x - veraltete Core-Version"
            WARNINGS=$((WARNINGS + 1))
        else
            print_warning "Unbekannte Minversion: $MINVERSION"
            log "WARNING" "Unbekannte Minversion: $MINVERSION"
            WARNINGS=$((WARNINGS + 1))
        fi
    else
        print_warning "Keine Minversion in package.xml gefunden"
        log "WARNING" "Keine Minversion gefunden"
        WARNINGS=$((WARNINGS + 1))
    fi

    # Package-Server Verbot (Plugin Store Regel)
    if grep -q '<instruction type="packageUpdateServer"' package.xml; then
        print_error "Package-Server Installation ist im Plugin Store VERBOTEN!"
        echo -e "   ${YELLOW}Entferne${NC} <instruction type=\"packageUpdateServer\"> aus package.xml"
        log "ERROR" "packageUpdateServer instruction gefunden (Plugin Store verboten)"
        ERRORS=$((ERRORS + 1))
    fi

    # Excludedpackages Empfehlung
    HAS_EXCLUDED=$(grep -c '<excludedpackages>' package.xml 2>/dev/null || echo "0")
    if [ "$HAS_EXCLUDED" -eq 0 ] && [[ "$MINVERSION" =~ ^6\. ]]; then
        print_warning "Empfehlung: Füge <excludedpackages> hinzu für WoltLab 7.0 Alpha/Beta"
        echo -e "   ${YELLOW}Beispiel:${NC}"
        echo -e "   <excludedpackages>"
        echo -e "     <excludedpackage version=\"7.0.0 Alpha 1\">com.woltlab.wcf</excludedpackage>"
        echo -e "   </excludedpackages>"
        log "WARNING" "excludedpackages fehlt (empfohlen für 6.x Plugins)"
        WARNINGS=$((WARNINGS + 1))
    fi
fi

echo ""

# 2. Prüfe TAR-Archive
echo -e "${YELLOW}🔍 Prüfe TAR-Archive...${NC}"
log "INFO" "Prüfe TAR-Archive"

# Suche nach allen .tar Dateien im Plugin-Verzeichnis
TAR_FOUND=0
TAR_ERRORS=0

while IFS= read -r -d '' tar_file; do
    TAR_FOUND=$((TAR_FOUND + 1))
    echo -e "   ${CYAN}Gefunden:${NC} $(basename "$tar_file")"
    log "INFO" "$(basename "$tar_file") gefunden"

    # TAR-Integrität prüfen
    if tar -tzf "$tar_file" &>/dev/null; then
        print_success "$(basename "$tar_file") ist gültig"
        log "INFO" "$(basename "$tar_file") ist gültig"
    else
        print_error "$(basename "$tar_file") ist beschädigt oder ungültig!"
        log "ERROR" "$(basename "$tar_file") ist beschädigt"
        ERRORS=$((ERRORS + 1))
        TAR_ERRORS=$((TAR_ERRORS + 1))
    fi
done < <(find . -maxdepth 2 \( -name "*.tar" -o -name "*.tar.gz" \) -type f ! -path './maintainer/*' -print0 2>/dev/null)

if [ $TAR_FOUND -eq 0 ]; then
    print_warning "Keine TAR-Dateien gefunden"
    log "WARNING" "Keine TAR-Dateien gefunden"
    WARNINGS=$((WARNINGS + 1))
else
    echo -e "   ${GREEN}✓${NC} $TAR_FOUND TAR-Datei(en) gefunden"
    log "INFO" "$TAR_FOUND TAR-Datei(en) gefunden"
fi

echo ""

# 3. Prüfe _extracted Verzeichnis (falls vorhanden) oder Plugin-Struktur
EXTRACTED_DIR=""
if [ -d "_extracted" ]; then
    EXTRACTED_DIR="_extracted"
elif [ -d "temp_edit" ] && { [ -d "temp_edit/lib" ] || [ -d "temp_edit/templates" ]; }; then
    EXTRACTED_DIR="temp_edit"
elif [ -d "lib" ] || [ -d "templates" ] || [ -d "acptemplates" ]; then
    # Plugin-Struktur direkt im Verzeichnis
    EXTRACTED_DIR="."
fi

# Quellbaum für PIP-/Sprach-Checks (DevTools-Parität)
VALIDATE_SOURCE_DIR=""
if [ -d "temp_edit" ] && [ -f "temp_edit/package.xml" ]; then
    VALIDATE_SOURCE_DIR="temp_edit"
elif [ -n "$EXTRACTED_DIR" ] && [ -f "${EXTRACTED_DIR}/package.xml" ]; then
    VALIDATE_SOURCE_DIR="$EXTRACTED_DIR"
elif [ -f "package.xml" ]; then
    VALIDATE_SOURCE_DIR="."
fi

if [ -n "$EXTRACTED_DIR" ]; then
    echo -e "${YELLOW}🔍 Prüfe Plugin-Struktur...${NC}"
    log "INFO" "Prüfe Plugin-Struktur in: $EXTRACTED_DIR"

    # Prüfe PHP-Dateien auf Syntax-Fehler
    if command -v php &> /dev/null; then
        echo -e "${YELLOW}  → Prüfe PHP-Syntax...${NC}"
        log "INFO" "Prüfe PHP-Syntax"

        PHP_FILES_CHECKED=0
        PHP_ERRORS=0

        while IFS= read -r -d '' php_file; do
            PHP_FILES_CHECKED=$((PHP_FILES_CHECKED + 1))
            if ! php -l "$php_file" &>/dev/null; then
                print_error "PHP-Syntax-Fehler in $php_file"
                log "ERROR" "PHP-Syntax-Fehler in $php_file"
                ERRORS=$((ERRORS + 1))
                PHP_ERRORS=$((PHP_ERRORS + 1))
            fi
        done < <(find "$EXTRACTED_DIR" -name "*.php" -type f -print0 2>/dev/null)

        if [ $PHP_FILES_CHECKED -gt 0 ]; then
            if [ $PHP_ERRORS -eq 0 ]; then
                print_success "Alle $PHP_FILES_CHECKED PHP-Dateien sind syntaktisch korrekt"
                log "INFO" "Alle $PHP_FILES_CHECKED PHP-Dateien sind syntaktisch korrekt"
            else
                print_error "$PHP_ERRORS von $PHP_FILES_CHECKED PHP-Dateien haben Syntax-Fehler"
                log "ERROR" "$PHP_ERRORS von $PHP_FILES_CHECKED PHP-Dateien haben Syntax-Fehler"
            fi
        else
            echo -e "   ${CYAN}ℹ️${NC}  Keine PHP-Dateien gefunden"
            log "INFO" "Keine PHP-Dateien gefunden"
        fi
    else
        print_warning "PHP CLI nicht installiert, überspringe PHP-Syntax-Prüfung"
        log "WARNING" "PHP CLI nicht gefunden, überspringe PHP-Syntax-Prüfung"
        WARNINGS=$((WARNINGS + 1))
    fi

    echo ""

    # Security-Check: SQL-Injection Patterns
    echo -e "${YELLOW}🛡️  Security-Check: SQL-Injection Patterns...${NC}"
    log "INFO" "Prüfe auf SQL-Injection Risiken"
    SECURITY_ISSUES=0

    while IFS= read -r -d '' php_file; do
        # Check 1: Deprecated mysql_* functions
        if grep -qE 'mysql_(query|connect|fetch|escape|real_escape_string)' "$php_file"; then
            print_warning "Deprecated mysql_* function in $(basename "$php_file")"
            echo -e "   ${YELLOW}→${NC} Verwende WoltLab DatabaseObject oder Prepared Statements"
            log "WARNING" "Deprecated mysql_* in $(basename "$php_file")"
            WARNINGS=$((WARNINGS + 1))
            SECURITY_ISSUES=$((SECURITY_ISSUES + 1))
        fi

        # Check 2: Superglobals directly in SQL string building (same line; not readParameters vs execute)
        if grep -qE '\$_(GET|POST|REQUEST|COOKIE)\[[^\]]+\][^;]*(prepareStatement|->query\(|getConditionBuilder\(\)->add|ConditionBuilder)' "$php_file" || \
           grep -qE '(prepareStatement|->query\(|getConditionBuilder\(\)->add)[^;]*\$_(GET|POST|REQUEST|COOKIE)\[' "$php_file"; then
            print_warning "Mögliche SQL-Injection in $(basename "$php_file")"
            echo -e "   ${YELLOW}→${NC} Verwende Prepared Statements mit Parameterbindung!"
            log "WARNING" "Potenzielle SQL-Injection in $(basename "$php_file")"
            WARNINGS=$((WARNINGS + 1))
            SECURITY_ISSUES=$((SECURITY_ISSUES + 1))
        fi

        # Check 3: User input concatenated into SQL string literals (narrow heuristic)
        if grep -qE "['\"][^'\"]*['\"]\s*\.\s*\\\$_(GET|POST|REQUEST|COOKIE)" "$php_file" && \
           grep -qE '(prepareStatement|->query\(|ConditionBuilder|execute\()' "$php_file"; then
            print_warning "String-Concatenation mit Request-Daten in $(basename "$php_file")"
            echo -e "   ${YELLOW}→${NC} Verwende Parameter-Binding statt String-Concatenation"
            log "WARNING" "String-Concatenation mit Request in $(basename "$php_file")"
            WARNINGS=$((WARNINGS + 1))
            SECURITY_ISSUES=$((SECURITY_ISSUES + 1))
        fi
    done < <(find "$EXTRACTED_DIR" -name "*.php" -type f -print0 2>/dev/null)

    if [ $SECURITY_ISSUES -eq 0 ]; then
        print_success "Keine offensichtlichen SQL-Injection Risiken gefunden"
        log "INFO" "SQL-Injection Check: Keine Probleme"
    else
        print_warning "$SECURITY_ISSUES potenzielle SQL-Injection Probleme gefunden"
        echo -e "   ${YELLOW}Hinweis:${NC} Plugin Store lehnt unsichere Queries ab!"
        log "WARNING" "$SECURITY_ISSUES SQL-Injection Warnungen"
    fi

    echo ""

    # Security-Check: LIKE escaping (WoltLab 6.2.5+ pattern)
    echo -e "${YELLOW}🛡️  Security-Check: LIKE-Escaping (escapeLikeValue)...${NC}"
    log "INFO" "Prüfe LIKE-Abfragen auf escapeLikeValue()"
    LIKE_ISSUES=0

    if command -v python3 &> /dev/null && [ -f "$TOOLS_DIR/check-like-escaping.py" ]; then
        while IFS= read -r like_line; do
            [ -z "$like_line" ] && continue
            like_file=$(echo "$like_line" | cut -d: -f1)
            like_kind=$(echo "$like_line" | cut -d: -f3)
            print_warning "LIKE-Escaping ($like_kind) in $like_file"
            echo -e "   ${YELLOW}→${NC} Verwende WCF::getDB()->escapeLikeValue(\$term) statt addcslashes()"
            log "WARNING" "LIKE-Escaping: $like_line"
            WARNINGS=$((WARNINGS + 1))
            LIKE_ISSUES=$((LIKE_ISSUES + 1))
        done < <(python3 "$TOOLS_DIR/check-like-escaping.py" "$EXTRACTED_DIR" 2>/dev/null || true)

        if [ $LIKE_ISSUES -eq 0 ]; then
            print_success "LIKE-Abfragen nutzen escapeLikeValue() oder keine Risiko-Muster"
            log "INFO" "LIKE-Escaping Check: Keine Probleme"
        else
            print_warning "$LIKE_ISSUES LIKE-Escaping Problem(e) gefunden"
            log "WARNING" "$LIKE_ISSUES LIKE-Escaping Warnungen"
        fi
    else
        print_warning "check-like-escaping.py nicht verfügbar, überspringe LIKE-Check"
        log "WARNING" "LIKE-Check übersprungen"
        WARNINGS=$((WARNINGS + 1))
    fi

    echo ""

    # Security-Check: XSS in Templates
    echo -e "${YELLOW}🛡️  Security-Check: XSS in Templates...${NC}"
    log "INFO" "Prüfe Templates auf XSS-Risiken"
    XSS_ISSUES=0

    if command -v python3 &> /dev/null && [ -f "$TOOLS_DIR/check-template-xss.py" ]; then
        while IFS= read -r xss_line; do
            [ -z "$xss_line" ] && continue
            xss_file=$(echo "$xss_line" | cut -d: -f1)
            xss_line_no=$(echo "$xss_line" | cut -d: -f2)
            xss_kind=$(echo "$xss_line" | cut -d: -f3)
            print_warning "XSS ($xss_kind) in $xss_file Zeile $xss_line_no"
            echo -e "   ${YELLOW}→${NC} |encodeHTML für HTML, {unsafe:\$var|encodeJS} in <script>"
            echo -e "   ${YELLOW}→${NC} Auto-Fix: python3 tools/fix-template-xss-escaping.py PLUGIN_DIR --dry-run"
            log "WARNING" "XSS: $xss_line"
            WARNINGS=$((WARNINGS + 1))
            XSS_ISSUES=$((XSS_ISSUES + 1))
        done < <(python3 "$TOOLS_DIR/check-template-xss.py" "$EXTRACTED_DIR" 2>/dev/null || true)
    else
        print_warning "check-template-xss.py nicht verfügbar, überspringe XSS-Check"
        log "WARNING" "XSS-Check übersprungen"
        WARNINGS=$((WARNINGS + 1))
    fi

    if [ $XSS_ISSUES -eq 0 ]; then
        print_success "Keine offensichtlichen XSS-Risiken gefunden"
        log "INFO" "XSS Check: Keine Probleme"
    else
        print_warning "$XSS_ISSUES potenzielle XSS-Probleme gefunden"
        echo -e "   ${YELLOW}Hinweis:${NC} Plugin Store lehnt unsichere Templates ab!"
        log "WARNING" "$XSS_ISSUES XSS Warnungen"
    fi

    echo ""

    # Quality-Check: Debug-Code und Test-Credentials
    echo -e "${YELLOW}🧹 Quality-Check: Debug-Code und Test-Credentials...${NC}"
    log "INFO" "Prüfe auf Debug-Code und Test-Credentials"
    DEBUG_ISSUES=0

    while IFS= read -r -d '' php_file; do
        # Check 1: Debug-Funktionen
        if grep -qE 'var_dump\(|print_r\(|var_export\(|debug_backtrace\(' "$php_file"; then
            print_warning "Debug-Funktionen in $(basename "$php_file")"
            echo -e "   ${YELLOW}→${NC} Entferne var_dump(), print_r(), var_export() vor Release"
            log "WARNING" "Debug-Funktionen in $(basename "$php_file")"
            WARNINGS=$((WARNINGS + 1))
            DEBUG_ISSUES=$((DEBUG_ISSUES + 1))
        fi

        # Check 2: Hardcoded Credentials
        if grep -qiE '(password|pwd)\s*=\s*["\047](test|admin|12345|password|root|demo)' "$php_file"; then
            print_error "Test-Credentials in $(basename "$php_file")"
            echo -e "   ${YELLOW}→${NC} Entferne alle Test-Passwörter!"
            log "ERROR" "Test-Credentials in $(basename "$php_file")"
            ERRORS=$((ERRORS + 1))
            DEBUG_ISSUES=$((DEBUG_ISSUES + 1))
        fi

        # Check 3: error_reporting/ini_set
        if grep -qE 'error_reporting\(E_ALL\)|ini_set\(["\047]display_errors' "$php_file"; then
            print_warning "error_reporting/display_errors in $(basename "$php_file")"
            echo -e "   ${YELLOW}→${NC} Entferne Debugging-Konfiguration vor Release"
            log "WARNING" "Debug-Config in $(basename "$php_file")"
            WARNINGS=$((WARNINGS + 1))
            DEBUG_ISSUES=$((DEBUG_ISSUES + 1))
        fi
    done < <(find "$EXTRACTED_DIR" -name "*.php" -type f -print0 2>/dev/null)

    # Check 4: console.log in JavaScript
    while IFS= read -r -d '' js_file; do
        if grep -q 'console\.log\(' "$js_file"; then
            print_warning "console.log() in $(basename "$js_file")"
            echo -e "   ${YELLOW}→${NC} Entferne console.log() Statements vor Release"
            log "WARNING" "console.log in $(basename "$js_file")"
            WARNINGS=$((WARNINGS + 1))
            DEBUG_ISSUES=$((DEBUG_ISSUES + 1))
        fi
    done < <(find "$EXTRACTED_DIR" -name "*.js" -type f -print0 2>/dev/null)

    if [ $DEBUG_ISSUES -eq 0 ]; then
        print_success "Kein Debug-Code gefunden"
        log "INFO" "Debug-Code Check: Keine Probleme"
    else
        print_warning "$DEBUG_ISSUES Debug-Code Probleme gefunden"
        log "WARNING" "$DEBUG_ISSUES Debug-Code Warnungen"
    fi

    echo ""

    # Best-Practice Check: WoltLab API-Nutzung
    echo -e "${YELLOW}🎯 Best-Practice Check: WoltLab API-Nutzung...${NC}"
    log "INFO" "Prüfe auf WoltLab API Best Practices"
    API_ISSUES=0

    while IFS= read -r -d '' php_file; do
        # Check 1: file_get_contents() für HTTP
        if grep -qE 'file_get_contents\s*\(\s*["\047]https?://' "$php_file"; then
            print_warning "file_get_contents() für HTTP in $(basename "$php_file")"
            echo -e "   ${YELLOW}→${NC} Verwende HTTPRequest/Guzzle für automatische Proxy-Unterstützung"
            echo -e "   ${YELLOW}→${NC} Cloud-Kompatibilität erfordert Proxy-Support!"
            log "WARNING" "file_get_contents für HTTP in $(basename "$php_file")"
            WARNINGS=$((WARNINGS + 1))
            API_ISSUES=$((API_ISSUES + 1))
        fi

        # Check 2: curl_* functions
        if grep -qE 'curl_(init|exec|setopt|close)' "$php_file"; then
            print_warning "curl_* functions in $(basename "$php_file")"
            echo -e "   ${YELLOW}→${NC} Verwende HTTPRequest oder Guzzle statt curl_*"
            echo -e "   ${YELLOW}→${NC} WoltLab Core APIs unterstützen Proxy automatisch"
            log "WARNING" "curl_* functions in $(basename "$php_file")"
            WARNINGS=$((WARNINGS + 1))
            API_ISSUES=$((API_ISSUES + 1))
        fi

        # Check 3: Direct DB Access
        if grep -qE 'new\s+(mysqli|PDO)\(' "$php_file"; then
            print_warning "Direkte DB-Verbindung in $(basename "$php_file")"
            echo -e "   ${YELLOW}→${NC} Verwende WoltLab DatabaseObject/DatabaseObjectList"
            log "WARNING" "Direkte DB-Verbindung in $(basename "$php_file")"
            WARNINGS=$((WARNINGS + 1))
            API_ISSUES=$((API_ISSUES + 1))
        fi

        # Check 4: WoltLab Cloud – keine System-Befehle (exec, shell_exec, system, passthru)
        if python3 - "$php_file" <<'PY' | grep -q 1
import re, sys
text = open(sys.argv[1], encoding="utf-8").read()
# Strip line comments to avoid false positives like "log system (if available)"
text = re.sub(r"//[^\n]*", "", text)
if re.search(r"\b(exec|shell_exec|system|passthru)\s*\(", text):
    print(1)
PY
        then
            print_warning "System-Befehl (exec/shell_exec/system/passthru) in $(basename "$php_file")"
            echo -e "   ${YELLOW}→${NC} WoltLab Cloud erlaubt keine direkten System-Aufrufe"
            log "WARNING" "System-Befehl in $(basename "$php_file") – Cloud inkompatibel"
            WARNINGS=$((WARNINGS + 1))
            API_ISSUES=$((API_ISSUES + 1))
        fi
    done < <(find "$EXTRACTED_DIR" -name "*.php" -type f -print0 2>/dev/null)

    if [ $API_ISSUES -eq 0 ]; then
        print_success "WoltLab API Best Practices werden befolgt"
        log "INFO" "API Best Practice Check: Keine Probleme"
    else
        print_warning "$API_ISSUES API-Nutzung Empfehlungen"
        log "WARNING" "$API_ISSUES API Best Practice Warnungen"
    fi

    echo ""

    # PHP 8.2+: keine dynamischen Properties auf $eventObj in Event-Listenern
    echo -e "${YELLOW}🎯 Best-Practice Check: Event-Listener (dynamic properties)...${NC}"
    log "INFO" "Prüfe Event-Listener auf \$eventObj-> Zuweisungen"
    DYNAMIC_PROP_ISSUES=0

    while IFS= read -r -d '' listener_file; do
        if grep -qE '\$eventObj->[a-zA-Z_][a-zA-Z0-9_]*(\[[^\]]*\])?\s*=' "$listener_file"; then
            print_warning "Dynamische Property auf \$eventObj in $(basename "$listener_file")"
            echo -e "   ${YELLOW}→${NC} Nur WCF::getTPL()->assign() / \$parameters['variables'] (PHP 8.3 Fatal auf IndexPage)"
            log "WARNING" "Dynamic property on eventObj in $(basename "$listener_file")"
            WARNINGS=$((WARNINGS + 1))
            DYNAMIC_PROP_ISSUES=$((DYNAMIC_PROP_ISSUES + 1))
        fi
    done < <(find "$EXTRACTED_DIR/lib/system/event/listener" -name "*.php" -type f -print0 2>/dev/null)

    if [ $DYNAMIC_PROP_ISSUES -eq 0 ]; then
        print_success "Keine \$eventObj-> Zuweisungen in Event-Listenern"
        log "INFO" "Event-Listener dynamic property check: OK"
    else
        print_warning "$DYNAMIC_PROP_ISSUES Event-Listener mit dynamischen Properties"
        log "WARNING" "$DYNAMIC_PROP_ISSUES dynamic property Warnungen"
    fi

    echo ""

    # Prüfe auf Müll-Dateien (Plugin Store: nur in package.xml referenzierte Dateien)
    echo -e "${YELLOW}📦 Prüfe auf unerwünschte Dateien (Store/Archiv)...${NC}"
    log "INFO" "Prüfe auf Müll-Dateien"
    JUNK_FOUND=0
    for pattern in ".DS_Store" "Thumbs.db"; do
        while IFS= read -r -d '' f; do
            JUNK_FOUND=$((JUNK_FOUND + 1))
            print_warning "Unerwünschte Datei: $f"
            echo -e "   ${YELLOW}→${NC} Nicht in package.xml referenzieren; vor Release entfernen"
            log "WARNING" "Junk-Datei: $f"
        done < <(find "$EXTRACTED_DIR" -name "$pattern" -type f -print0 2>/dev/null)
    done
    if [ $JUNK_FOUND -eq 0 ]; then
        print_success "Keine typischen Müll-Dateien (.DS_Store, Thumbs.db) gefunden"
        log "INFO" "Junk-Check: Keine gefunden"
    else
        WARNINGS=$((WARNINGS + JUNK_FOUND))
    fi

    echo ""
else
    echo -e "${CYAN}ℹ️${NC}  Plugin-Struktur nicht gefunden (kein _extracted, lib/, templates/ Verzeichnis)"
    echo -e "   ${YELLOW}Tipp:${NC} Führe zuerst build.sh aus, um TAR-Archive zu erstellen"
    log "INFO" "Plugin-Struktur nicht gefunden"
fi

# 4. Prüfe XML-Dateien (PIPs)
echo -e "${YELLOW}🔍 Prüfe XML-Dateien (PIPs)...${NC}"
log "INFO" "Prüfe XML-Dateien (PIPs)"

XML_FILES=(page.xml acpmenu.xml menu.xml acp.xml option.xml)
XML_FOUND=0

for xml_file in "${XML_FILES[@]}"; do
    if [ -f "$xml_file" ]; then
        print_success "$xml_file gefunden"
        log "INFO" "$xml_file gefunden"
        XML_FOUND=$((XML_FOUND + 1))

        # XML-Syntax prüfen (falls xmllint verfügbar)
        if command -v xmllint &> /dev/null; then
            if xmllint --noout "$xml_file" 2>/dev/null; then
                echo -e "   ${GREEN}✓${NC} $xml_file ist syntaktisch korrekt"
                log "INFO" "$xml_file ist syntaktisch korrekt"
            else
                print_error "XML-Syntax-Fehler in $xml_file!"
                log "ERROR" "XML-Syntax-Fehler in $xml_file"
                ERRORS=$((ERRORS + 1))
            fi
        fi
    fi
done

if [ -f "option.xml" ] && grep -q '<optionsrequired>' option.xml 2>/dev/null; then
    print_error "option.xml enthält ungültiges <optionsrequired> — WoltLab XSD kennt nur <options>"
    log "ERROR" "option.xml: invalid optionsrequired tag"
    ERRORS=$((ERRORS + 1))
fi

if [ $XML_FOUND -gt 0 ]; then
    echo -e "   ${GREEN}✓${NC} $XML_FOUND XML-Datei(en) gefunden"
    log "INFO" "$XML_FOUND XML-Datei(en) gefunden"
else
    echo -e "   ${CYAN}ℹ️${NC}  Keine zusätzlichen XML-Dateien (PIPs) gefunden"
    log "INFO" "Keine zusätzlichen XML-Dateien gefunden"
fi

echo ""

# 5. Prüfe Übersetzungen (Plugin Store Pflicht: DE + EN)
echo -e "${YELLOW}🔍 Prüfe Übersetzungen (DE/EN Pflicht für Plugin Store)...${NC}"
log "INFO" "Prüfe Übersetzungen"

if [ -d "language" ]; then
    DE_FOUND=false
    EN_FOUND=false

    if [ -f "language/de.xml" ]; then
        DE_FOUND=true
        print_success "Deutsch (de.xml) gefunden"
        log "INFO" "Deutsch (de.xml) gefunden"
    fi

    if [ -f "language/en.xml" ]; then
        EN_FOUND=true
        print_success "Englisch (en.xml) gefunden"
        log "INFO" "Englisch (en.xml) gefunden"
    fi

    if [ "$DE_FOUND" = false ] || [ "$EN_FOUND" = false ]; then
        print_error "Plugin Store verlangt DE + EN Übersetzungen!"
        echo -e "   ${YELLOW}Gefunden:${NC} DE=$DE_FOUND, EN=$EN_FOUND"
        echo -e "   ${YELLOW}Hinweis:${NC} Beide Sprachen müssen identische Informationen enthalten"
        log "ERROR" "Übersetzungen unvollständig: DE=$DE_FOUND, EN=$EN_FOUND"
        ERRORS=$((ERRORS + 1))
    else
        print_success "DE + EN Übersetzungen vorhanden"
        log "INFO" "DE + EN Übersetzungen vorhanden"
    fi

    # Language category/item alignment (blocks ACP install if wrong)
    if command -v python3 &> /dev/null && [ -f "$TOOLS_DIR/check-language-categories.py" ]; then
        LANG_CAT_ISSUES=0
        while IFS= read -r lang_line; do
            [ -z "$lang_line" ] && continue
            print_error "Sprach-XML Kategorie-Mismatch: $lang_line"
            echo -e "   ${YELLOW}→${NC} Item in passende <category> verschieben oder Key umbenennen (siehe tools/docs/LANGUAGE-XML.de.md)"
            log "ERROR" "Language category mismatch: $lang_line"
            ERRORS=$((ERRORS + 1))
            LANG_CAT_ISSUES=$((LANG_CAT_ISSUES + 1))
        done < <(python3 "$TOOLS_DIR/check-language-categories.py" "$PLUGIN_DIR" 2>/dev/null || true)
        if [ $LANG_CAT_ISSUES -eq 0 ]; then
            print_success "Sprach-XML: Item/Kategorie-Zuordnung OK"
            log "INFO" "Language category check OK"
        fi
    else
        print_warning "check-language-categories.py nicht verfügbar, überspringe Sprach-Kategorie-Check"
        log "WARNING" "check-language-categories.py nicht verfügbar"
        WARNINGS=$((WARNINGS + 1))
    fi

    LANG_KEYS_CHECK="$TOOLS_DIR/check-language-keys.py"
    LANG_SRC="${VALIDATE_SOURCE_DIR:-$PLUGIN_DIR}"
    if command -v python3 &> /dev/null && [ -f "$LANG_KEYS_CHECK" ] && [ -d "$LANG_SRC/language" ]; then
        echo -e "${YELLOW}  → Sprach-Keys (Plugin-Keys vs. XML, mit Fundstelle)...${NC}"
        LANG_KEY_OUT="/tmp/validate-lang-keys-$$.txt"
        if python3 "$LANG_KEYS_CHECK" "$LANG_SRC" >"$LANG_KEY_OUT" 2>&1; then
            print_success "Sprach-Keys: Plugin-Keys DE/EN vs. Code OK"
            log "INFO" "Language keys check OK"
        else
            print_error "Sprach-Key-Abweichungen (nur App-Keys, nicht wcf.*)"
            sed -n '/^--- Orphaned/,${p}' "$LANG_KEY_OUT" | head -50
            log "ERROR" "Language keys check failed"
            ERRORS=$((ERRORS + 1))
        fi
        rm -f "$LANG_KEY_OUT"
    fi
else
    print_warning "Kein language/ Verzeichnis - Plugin Store verlangt Übersetzungen!"
    echo -e "   ${YELLOW}Erstelle${NC} language/de.xml und language/en.xml"
    log "WARNING" "Kein language/ Verzeichnis gefunden"
    WARNINGS=$((WARNINGS + 1))
fi

echo ""

# 5b. Bootstrap-Sicherheit (WCF lib/bootstrap vs. App lib — Deinstall/Partial-Deploy)
echo -e "${YELLOW}🔍 Prüfe Bootstrap-Guards (Deinstall-sicher)...${NC}"
log "INFO" "Prüfe Bootstrap-Guards"
BOOTSTRAP_DIR=""
if [ -d "lib/bootstrap" ]; then
    BOOTSTRAP_DIR="lib/bootstrap"
elif [ -n "${EXTRACTED_DIR:-}" ] && [ -d "$EXTRACTED_DIR/lib/bootstrap" ]; then
    BOOTSTRAP_DIR="$EXTRACTED_DIR/lib/bootstrap"
fi

if [ -n "$BOOTSTRAP_DIR" ]; then
    BOOTSTRAP_ISSUES=0
    while IFS= read -r -d '' bootstrap_file; do
        if grep -qE 'shrinkr\\|Shrinkr[A-Z]' "$bootstrap_file" 2>/dev/null; then
            if ! grep -qE 'class_exists\s*\([^,]+,\s*false\s*\)' "$bootstrap_file" 2>/dev/null; then
                print_error "Bootstrap ohne class_exists(..., false)-Guard: $(basename "$bootstrap_file")"
                echo -e "   ${YELLOW}→${NC} Bootstrap liegt in wcf/lib; App-Klassen fehlen bei Deinstall — früh return, sonst ACP tot"
                log "ERROR" "Bootstrap guard missing: $bootstrap_file"
                ERRORS=$((ERRORS + 1))
                BOOTSTRAP_ISSUES=$((BOOTSTRAP_ISSUES + 1))
            fi
        fi
    done < <(find "$BOOTSTRAP_DIR" -maxdepth 1 -name '*.php' -type f -print0 2>/dev/null)

    if [ $BOOTSTRAP_ISSUES -eq 0 ]; then
        print_success "Bootstrap-Guards für App-Klassen vorhanden"
        log "INFO" "Bootstrap guard check OK"
    fi
else
    print_info "Kein lib/bootstrap/ — übersprungen"
fi

echo ""

# 5c. PIP-Quellen (WoltLab DevTools-Parität)
if [ -n "$VALIDATE_SOURCE_DIR" ] && [ -f "${VALIDATE_SOURCE_DIR}/package.xml" ]; then
    echo -e "${YELLOW}🔍 PIP-Quellen (DevTools-Parität)...${NC}"
    log "INFO" "PIP-Quellen-Check in $VALIDATE_SOURCE_DIR"
    PIP_CHECK="$TOOLS_DIR/check-pip-sources.py"
    if command -v python3 &> /dev/null && [ -f "$PIP_CHECK" ]; then
        if python3 "$PIP_CHECK" --strict "$VALIDATE_SOURCE_DIR" "${VALIDATE_SOURCE_DIR}/package.xml"; then
            print_success "PIP-Quellen vollständig (sync vs. Paket-Update siehe Log oben)"
            log "INFO" "PIP source check OK"
        else
            print_error "PIP-Quellen fehlen oder Update-Pfade ungültig"
            log "ERROR" "PIP source check failed"
            ERRORS=$((ERRORS + 1))
        fi
    else
        print_warning "check-pip-sources.py nicht verfügbar"
        WARNINGS=$((WARNINGS + 1))
    fi
    echo ""
fi

# 6. Ergebnis
print_section "Validierungs-Ergebnis" "Hauptmenü" "Validierung"

if [ $ERRORS -eq 0 ] && [ $WARNINGS -eq 0 ]; then
    print_success "Validierung erfolgreich! Keine Fehler oder Warnungen gefunden."
    log "INFO" "Validierung erfolgreich abgeschlossen: 0 Fehler, 0 Warnungen"
    EXIT_CODE=0
elif [ $ERRORS -eq 0 ]; then
    print_warning "Validierung abgeschlossen mit $WARNINGS Warnung(en)."
    echo -e "   ${YELLOW}Die Warnungen sind nicht kritisch, aber sollten geprüft werden.${NC}"
    log "WARNING" "Validierung abgeschlossen mit $WARNINGS Warnung(en)"
    EXIT_CODE=0
else
    print_error "Validierung fehlgeschlagen!"
    echo -e "   ${RED}Fehler:${NC} $ERRORS"
    echo -e "   ${YELLOW}Warnungen:${NC} $WARNINGS"
    echo ""
    echo -e "   ${YELLOW}Bitte behebe die Fehler vor dem Release.${NC}"
    log "ERROR" "Validierung fehlgeschlagen: $ERRORS Fehler, $WARNINGS Warnungen"
    EXIT_CODE=1
fi

echo ""
echo -e "${CYAN}ℹ️${NC}  Log-Datei: $LOG_FILE"
log "INFO" "Validierung abgeschlossen mit Exit-Code $EXIT_CODE"

exit $EXIT_CODE
