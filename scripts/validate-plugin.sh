#!/bin/bash

# Simple WoltLab Plugin Manager - Validate Plugin Script
# Copyright (c) 2025 SunnyCueq
# License: MIT (Open Source)
# Repository: https://github.com/SunnyCueq/simple-woltlab-plugin-manager
#
# ⚠️ IMPORTANT: This copyright notice must not be removed.
# This project is open source under the MIT License, but the copyright
# attribution must be preserved in all copies and substantial portions.
#
# Script zur Validierung der WoltLab Plugin-Struktur
# Prüft: package.xml, referenzierte Dateien, TAR-Archive, PHP-Syntax
#
# Verwendung: ./validate-plugin.sh [PLUGIN_DIR]
# Falls PLUGIN_DIR nicht angegeben, wird das aktuelle Verzeichnis verwendet

# Fehlerbehandlung
set -uo pipefail

# Logging-Variablen
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
else
    PLUGIN_DIR="$(pwd)"
fi

log "INFO" "Plugin-Verzeichnis: $PLUGIN_DIR"

cd "$PLUGIN_DIR" || {
    log "ERROR" "Konnte nicht in Plugin-Verzeichnis wechseln: $PLUGIN_DIR"
    exit 1
}

echo "═══════════════════════════════════════════════════════════════"
echo "  WoltLab Plugin Validierung"
echo "═══════════════════════════════════════════════════════════════"
echo ""
echo "Verzeichnis: $PLUGIN_DIR"
echo ""

# 1. Prüfe package.xml
echo "🔍 Prüfe package.xml..."
log "INFO" "Prüfe package.xml"

if [ ! -f "package.xml" ]; then
    echo "❌ FEHLER: package.xml nicht gefunden!"
    log "ERROR" "package.xml nicht gefunden"
    ((ERRORS++))
else
    echo "✓ package.xml gefunden"
    log "INFO" "package.xml gefunden"

    # XML-Syntax prüfen (falls xmllint verfügbar)
    if command -v xmllint &> /dev/null; then
        if xmllint --noout package.xml 2>/dev/null; then
            echo "✓ XML-Syntax ist korrekt"
            log "INFO" "XML-Syntax ist korrekt"
        else
            echo "❌ FEHLER: XML-Syntax-Fehler in package.xml!"
            log "ERROR" "XML-Syntax-Fehler in package.xml"
            ((ERRORS++))
        fi
    else
        echo "⚠️  Warnung: xmllint nicht installiert, überspringe XML-Validierung"
        log "WARNING" "xmllint nicht gefunden, überspringe XML-Validierung"
        ((WARNINGS++))
    fi

    # Package-Name prüfen
    PACKAGE_NAME=$(grep -oP 'name="\K[^"]+' package.xml 2>/dev/null | head -1)
    if [ -z "$PACKAGE_NAME" ]; then
        echo "❌ FEHLER: Konnte Package-Name nicht aus package.xml extrahieren!"
        log "ERROR" "Konnte Package-Name nicht extrahieren"
        ((ERRORS++))
    else
        echo "✓ Package-Name: $PACKAGE_NAME"
        log "INFO" "Package-Name: $PACKAGE_NAME"

        # Package-Name-Format prüfen
        if [[ ! "$PACKAGE_NAME" =~ ^com\.[a-z0-9]+\.[a-z0-9]+(\.[a-z0-9]+)*$ ]]; then
            echo "⚠️  Warnung: Package-Name entspricht möglicherweise nicht dem Standard-Format!"
            echo "   Erwartetes Format: com.domain.pluginname"
            echo "   Gefundener Name: $PACKAGE_NAME"
            log "WARNING" "Package-Name-Format möglicherweise nicht standardkonform: $PACKAGE_NAME"
            ((WARNINGS++))
        else
            echo "✓ Package-Name-Format ist korrekt"
            log "INFO" "Package-Name-Format ist korrekt"
        fi
    fi

    # Version prüfen
    VERSION=$(grep -oP '<version>\K[^<]+' package.xml 2>/dev/null | head -1)
    if [ -z "$VERSION" ]; then
        echo "⚠️  Warnung: Konnte Version nicht aus package.xml extrahieren"
        log "WARNING" "Konnte Version nicht extrahieren"
        ((WARNINGS++))
    else
        echo "✓ Version: $VERSION"
        log "INFO" "Version: $VERSION"
    fi

    # Minversion-Validierung (Plugin Store Requirement)
    MINVERSION=$(grep -A 1 '<requiredpackages>' package.xml | grep -oP 'minversion="\K[^"]+' | head -1)

    if [ -n "$MINVERSION" ]; then
        echo "✓ Minversion: $MINVERSION"
        log "INFO" "Minversion: $MINVERSION"

        if [[ "$MINVERSION" =~ ^6\.[0-2]\. ]]; then
            echo "✓ Minversion ist WoltLab 6.0/6.1/6.2 kompatibel"
            log "INFO" "Minversion ist WoltLab 6.0/6.1/6.2 kompatibel"
        elif [[ "$MINVERSION" =~ ^7\. ]]; then
            echo "⚠️  Warnung: Minversion 7.x - WoltLab 7 ist noch nicht released"
            echo "   Plugin Store akzeptiert nur unterstützte Versionen!"
            log "WARNING" "Minversion 7.x - noch nicht released"
            ((WARNINGS++))
        elif [[ "$MINVERSION" =~ ^5\. ]]; then
            echo "⚠️  Warnung: Minversion 5.x - veraltete Core-Version"
            echo "   Empfehlung: Upgrade auf 6.0.0 für Plugin Store"
            log "WARNING" "Minversion 5.x - veraltete Core-Version"
            ((WARNINGS++))
        else
            echo "⚠️  Warnung: Unbekannte Minversion: $MINVERSION"
            log "WARNING" "Unbekannte Minversion: $MINVERSION"
            ((WARNINGS++))
        fi
    else
        echo "⚠️  Warnung: Keine Minversion in package.xml gefunden"
        log "WARNING" "Keine Minversion gefunden"
        ((WARNINGS++))
    fi

    # Package-Server Verbot (Plugin Store Regel)
    if grep -q '<instruction type="packageUpdateServer"' package.xml; then
        echo "❌ FEHLER: Package-Server Installation ist im Plugin Store VERBOTEN!"
        echo "   Entferne <instruction type=\"packageUpdateServer\"> aus package.xml"
        log "ERROR" "packageUpdateServer instruction gefunden (Plugin Store verboten)"
        ((ERRORS++))
    fi

    # Excludedpackages Empfehlung
    HAS_EXCLUDED=$(grep -c '<excludedpackages>' package.xml 2>/dev/null || echo "0")
    if [ "$HAS_EXCLUDED" -eq 0 ] && [[ "$MINVERSION" =~ ^6\. ]]; then
        echo "⚠️  Empfehlung: Füge <excludedpackages> hinzu für WoltLab 7.0 Alpha/Beta"
        echo "   Beispiel:"
        echo "   <excludedpackages>"
        echo "     <excludedpackage version=\"7.0.0 Alpha 1\">com.woltlab.wcf</excludedpackage>"
        echo "   </excludedpackages>"
        log "WARNING" "excludedpackages fehlt (empfohlen für 6.x Plugins)"
        ((WARNINGS++))
    fi
fi

echo ""

# 2. Prüfe TAR-Archive
echo "🔍 Prüfe TAR-Archive..."
log "INFO" "Prüfe TAR-Archive"

TAR_FILES=(files.tar files_urlshort.tar templates_urlshort.tar acptemplates_urlshort.tar templates.tar acptemplates.tar)
TAR_FOUND=0

for tar_file in "${TAR_FILES[@]}"; do
    if [ -f "$tar_file" ]; then
        echo "✓ $tar_file gefunden"
        log "INFO" "$tar_file gefunden"
        ((TAR_FOUND++))

        # TAR-Integrität prüfen
        if tar -tzf "$tar_file" &>/dev/null; then
            echo "  ✓ $tar_file ist gültig"
            log "INFO" "$tar_file ist gültig"
        else
            echo "  ❌ FEHLER: $tar_file ist beschädigt oder ungültig!"
            log "ERROR" "$tar_file ist beschädigt"
            ((ERRORS++))
        fi
    fi
done

if [ $TAR_FOUND -eq 0 ]; then
    echo "⚠️  Warnung: Keine TAR-Dateien gefunden"
    log "WARNING" "Keine TAR-Dateien gefunden"
    ((WARNINGS++))
else
    echo "✓ $TAR_FOUND TAR-Datei(en) gefunden"
    log "INFO" "$TAR_FOUND TAR-Datei(en) gefunden"
fi

echo ""

# 3. Prüfe _extracted Verzeichnis (falls vorhanden)
if [ -d "_extracted" ]; then
    echo "🔍 Prüfe _extracted Verzeichnis..."
    log "INFO" "Prüfe _extracted Verzeichnis"

    # Prüfe PHP-Dateien auf Syntax-Fehler
    if command -v php &> /dev/null; then
        echo "🔍 Prüfe PHP-Syntax..."
        log "INFO" "Prüfe PHP-Syntax"

        PHP_FILES_CHECKED=0
        PHP_ERRORS=0

        while IFS= read -r -d '' php_file; do
            ((PHP_FILES_CHECKED++))
            if ! php -l "$php_file" &>/dev/null; then
                echo "❌ FEHLER: PHP-Syntax-Fehler in $php_file"
                log "ERROR" "PHP-Syntax-Fehler in $php_file"
                ((ERRORS++))
                ((PHP_ERRORS++))
            fi
        done < <(find _extracted -name "*.php" -type f -print0 2>/dev/null)

        if [ $PHP_FILES_CHECKED -gt 0 ]; then
            if [ $PHP_ERRORS -eq 0 ]; then
                echo "✓ Alle $PHP_FILES_CHECKED PHP-Dateien sind syntaktisch korrekt"
                log "INFO" "Alle $PHP_FILES_CHECKED PHP-Dateien sind syntaktisch korrekt"
            else
                echo "❌ $PHP_ERRORS von $PHP_FILES_CHECKED PHP-Dateien haben Syntax-Fehler"
                log "ERROR" "$PHP_ERRORS von $PHP_FILES_CHECKED PHP-Dateien haben Syntax-Fehler"
            fi
        else
            echo "ℹ️  Keine PHP-Dateien in _extracted gefunden"
            log "INFO" "Keine PHP-Dateien in _extracted gefunden"
        fi
    else
        echo "⚠️  Warnung: PHP CLI nicht installiert, überspringe PHP-Syntax-Prüfung"
        log "WARNING" "PHP CLI nicht gefunden, überspringe PHP-Syntax-Prüfung"
        ((WARNINGS++))
    fi

    echo ""

    # Security-Check: SQL-Injection Patterns
    echo "🛡️  Security-Check: SQL-Injection Patterns..."
    log "INFO" "Prüfe auf SQL-Injection Risiken"
    SECURITY_ISSUES=0

    while IFS= read -r -d '' php_file; do
        # Check 1: Deprecated mysql_* functions
        if grep -qE 'mysql_(query|connect|fetch|escape|real_escape_string)' "$php_file"; then
            echo "⚠️  SECURITY: Deprecated mysql_* function in $(basename "$php_file")"
            echo "   → Verwende WoltLab DatabaseObject oder Prepared Statements"
            log "WARNING" "Deprecated mysql_* in $(basename "$php_file")"
            ((WARNINGS++))
            ((SECURITY_ISSUES++))
        fi

        # Check 2: Direct $_GET/$_POST in SQL
        if grep -qE '\$_(GET|POST|REQUEST)\[.*\].*query|query.*\$_(GET|POST|REQUEST)' "$php_file"; then
            echo "⚠️  SECURITY: Mögliche SQL-Injection in $(basename "$php_file")"
            echo "   → Verwende Prepared Statements mit Parameterbindung!"
            log "WARNING" "Potenzielle SQL-Injection in $(basename "$php_file")"
            ((WARNINGS++))
            ((SECURITY_ISSUES++))
        fi

        # Check 3: String Concatenation in Queries
        if grep -qE 'query.*\$[a-zA-Z_]|\$[a-zA-Z_].*query' "$php_file" && \
           grep -qE '\..*\$|".*\$|"\s*\.\s*\$' "$php_file"; then
            echo "⚠️  SECURITY: String-Concatenation in SQL-Query in $(basename "$php_file")"
            echo "   → Verwende Parameter-Binding statt String-Concatenation"
            log "WARNING" "String-Concatenation in SQL in $(basename "$php_file")"
            ((WARNINGS++))
            ((SECURITY_ISSUES++))
        fi
    done < <(find _extracted -name "*.php" -type f -print0 2>/dev/null)

    if [ $SECURITY_ISSUES -eq 0 ]; then
        echo "✓ Keine offensichtlichen SQL-Injection Risiken gefunden"
        log "INFO" "SQL-Injection Check: Keine Probleme"
    else
        echo "⚠️  $SECURITY_ISSUES potenzielle SQL-Injection Probleme gefunden"
        echo "   Hinweis: Plugin Store lehnt unsichere Queries ab!"
        log "WARNING" "$SECURITY_ISSUES SQL-Injection Warnungen"
    fi

    echo ""

    # Security-Check: XSS in Templates
    echo "🛡️  Security-Check: XSS in Templates..."
    log "INFO" "Prüfe Templates auf XSS-Risiken"
    XSS_ISSUES=0

    while IFS= read -r -d '' tpl_file; do
        # Check: Unescaped Variable Output
        UNESCAPED_VARS=$(grep -oE '\{\$[a-zA-Z_][a-zA-Z0-9_]*\}' "$tpl_file" 2>/dev/null | wc -l)

        if [ "$UNESCAPED_VARS" -gt 0 ]; then
            echo "⚠️  XSS-RISK: $UNESCAPED_VARS unescaped Variable(n) in $(basename "$tpl_file")"
            echo "   → Verwende {|escape} für Text, {|encodeJS} für JavaScript"
            echo "   → Nur sichere Variablen (Language, Constants) dürfen unescaped sein"
            log "WARNING" "Potenzielle XSS in $(basename "$tpl_file"): $UNESCAPED_VARS unescaped vars"
            ((WARNINGS++))
            ((XSS_ISSUES++))
        fi

        # Check: Inline JavaScript mit Variablen
        if grep -qE '<script.*\{\$' "$tpl_file"; then
            echo "⚠️  XSS-RISK: Variable in <script> Tag in $(basename "$tpl_file")"
            echo "   → Verwende {|encodeJS} für Variablen in JavaScript"
            log "WARNING" "Variable in script-tag in $(basename "$tpl_file")"
            ((WARNINGS++))
            ((XSS_ISSUES++))
        fi
    done < <(find _extracted -name "*.tpl" -type f -print0 2>/dev/null)

    if [ $XSS_ISSUES -eq 0 ]; then
        echo "✓ Keine offensichtlichen XSS-Risiken gefunden"
        log "INFO" "XSS Check: Keine Probleme"
    else
        echo "⚠️  $XSS_ISSUES potenzielle XSS-Probleme gefunden"
        echo "   Hinweis: Plugin Store lehnt unsichere Templates ab!"
        log "WARNING" "$XSS_ISSUES XSS Warnungen"
    fi

    echo ""

    # Quality-Check: Debug-Code und Test-Credentials
    echo "🧹 Quality-Check: Debug-Code und Test-Credentials..."
    log "INFO" "Prüfe auf Debug-Code und Test-Credentials"
    DEBUG_ISSUES=0

    while IFS= read -r -d '' php_file; do
        # Check 1: Debug-Funktionen
        if grep -qE 'var_dump\(|print_r\(|var_export\(|debug_backtrace\(' "$php_file"; then
            echo "⚠️  DEBUG-CODE: Debug-Funktionen in $(basename "$php_file")"
            echo "   → Entferne var_dump(), print_r(), var_export() vor Release"
            log "WARNING" "Debug-Funktionen in $(basename "$php_file")"
            ((WARNINGS++))
            ((DEBUG_ISSUES++))
        fi

        # Check 2: Hardcoded Credentials
        if grep -qiE '(password|pwd)\s*=\s*["\047](test|admin|12345|password|root|demo)' "$php_file"; then
            echo "❌ FEHLER: Test-Credentials in $(basename "$php_file")"
            echo "   → Entferne alle Test-Passwörter!"
            log "ERROR" "Test-Credentials in $(basename "$php_file")"
            ((ERRORS++))
            ((DEBUG_ISSUES++))
        fi

        # Check 3: error_reporting/ini_set
        if grep -qE 'error_reporting\(E_ALL\)|ini_set\(["\047]display_errors' "$php_file"; then
            echo "⚠️  DEBUG-CODE: error_reporting/display_errors in $(basename "$php_file")"
            echo "   → Entferne Debugging-Konfiguration vor Release"
            log "WARNING" "Debug-Config in $(basename "$php_file")"
            ((WARNINGS++))
            ((DEBUG_ISSUES++))
        fi
    done < <(find _extracted -name "*.php" -type f -print0 2>/dev/null)

    # Check 4: console.log in JavaScript
    while IFS= read -r -d '' js_file; do
        if grep -q 'console\.log\(' "$js_file"; then
            echo "⚠️  DEBUG-CODE: console.log() in $(basename "$js_file")"
            echo "   → Entferne console.log() Statements vor Release"
            log "WARNING" "console.log in $(basename "$js_file")"
            ((WARNINGS++))
            ((DEBUG_ISSUES++))
        fi
    done < <(find _extracted -name "*.js" -type f -print0 2>/dev/null)

    if [ $DEBUG_ISSUES -eq 0 ]; then
        echo "✓ Kein Debug-Code gefunden"
        log "INFO" "Debug-Code Check: Keine Probleme"
    else
        echo "⚠️  $DEBUG_ISSUES Debug-Code Probleme gefunden"
        log "WARNING" "$DEBUG_ISSUES Debug-Code Warnungen"
    fi

    echo ""

    # Best-Practice Check: WoltLab API-Nutzung
    echo "🎯 Best-Practice Check: WoltLab API-Nutzung..."
    log "INFO" "Prüfe auf WoltLab API Best Practices"
    API_ISSUES=0

    while IFS= read -r -d '' php_file; do
        # Check 1: file_get_contents() für HTTP
        if grep -qE 'file_get_contents\s*\(\s*["\047]https?://' "$php_file"; then
            echo "⚠️  API: file_get_contents() für HTTP in $(basename "$php_file")"
            echo "   → Verwende HTTPRequest/Guzzle für automatische Proxy-Unterstützung"
            echo "   → Cloud-Kompatibilität erfordert Proxy-Support!"
            log "WARNING" "file_get_contents für HTTP in $(basename "$php_file")"
            ((WARNINGS++))
            ((API_ISSUES++))
        fi

        # Check 2: curl_* functions
        if grep -qE 'curl_(init|exec|setopt|close)' "$php_file"; then
            echo "⚠️  API: curl_* functions in $(basename "$php_file")"
            echo "   → Verwende HTTPRequest oder Guzzle statt curl_*"
            echo "   → WoltLab Core APIs unterstützen Proxy automatisch"
            log "WARNING" "curl_* functions in $(basename "$php_file")"
            ((WARNINGS++))
            ((API_ISSUES++))
        fi

        # Check 3: Direct DB Access
        if grep -qE 'new\s+(mysqli|PDO)\(' "$php_file"; then
            echo "⚠️  API: Direkte DB-Verbindung in $(basename "$php_file")"
            echo "   → Verwende WoltLab DatabaseObject/DatabaseObjectList"
            log "WARNING" "Direkte DB-Verbindung in $(basename "$php_file")"
            ((WARNINGS++))
            ((API_ISSUES++))
        fi
    done < <(find _extracted -name "*.php" -type f -print0 2>/dev/null)

    if [ $API_ISSUES -eq 0 ]; then
        echo "✓ WoltLab API Best Practices werden befolgt"
        log "INFO" "API Best Practice Check: Keine Probleme"
    else
        echo "⚠️  $API_ISSUES API-Nutzung Empfehlungen"
        log "WARNING" "$API_ISSUES API Best Practice Warnungen"
    fi

    echo ""
else
    echo "ℹ️  _extracted Verzeichnis nicht gefunden (führe extract-plugin-files.sh aus, um es zu erstellen)"
    log "INFO" "_extracted Verzeichnis nicht gefunden"
fi

# 4. Prüfe XML-Dateien (PIPs)
echo "🔍 Prüfe XML-Dateien (PIPs)..."
log "INFO" "Prüfe XML-Dateien (PIPs)"

XML_FILES=(page.xml acpmenu.xml menu.xml acp.xml option.xml)
XML_FOUND=0

for xml_file in "${XML_FILES[@]}"; do
    if [ -f "$xml_file" ]; then
        echo "✓ $xml_file gefunden"
        log "INFO" "$xml_file gefunden"
        ((XML_FOUND++))

        # XML-Syntax prüfen (falls xmllint verfügbar)
        if command -v xmllint &> /dev/null; then
            if xmllint --noout "$xml_file" 2>/dev/null; then
                echo "  ✓ $xml_file ist syntaktisch korrekt"
                log "INFO" "$xml_file ist syntaktisch korrekt"
            else
                echo "  ❌ FEHLER: XML-Syntax-Fehler in $xml_file!"
                log "ERROR" "XML-Syntax-Fehler in $xml_file"
                ((ERRORS++))
            fi
        fi
    fi
done

if [ $XML_FOUND -gt 0 ]; then
    echo "✓ $XML_FOUND XML-Datei(en) gefunden"
    log "INFO" "$XML_FOUND XML-Datei(en) gefunden"
else
    echo "ℹ️  Keine zusätzlichen XML-Dateien (PIPs) gefunden"
    log "INFO" "Keine zusätzlichen XML-Dateien gefunden"
fi

echo ""

# 5. Prüfe Übersetzungen (Plugin Store Pflicht: DE + EN)
echo "🔍 Prüfe Übersetzungen (DE/EN Pflicht für Plugin Store)..."
log "INFO" "Prüfe Übersetzungen"

if [ -d "language" ]; then
    DE_FOUND=false
    EN_FOUND=false

    if [ -f "language/de.xml" ]; then
        DE_FOUND=true
        echo "✓ Deutsch (de.xml) gefunden"
        log "INFO" "Deutsch (de.xml) gefunden"
    fi

    if [ -f "language/en.xml" ]; then
        EN_FOUND=true
        echo "✓ Englisch (en.xml) gefunden"
        log "INFO" "Englisch (en.xml) gefunden"
    fi

    if [ "$DE_FOUND" = false ] || [ "$EN_FOUND" = false ]; then
        echo "❌ FEHLER: Plugin Store verlangt DE + EN Übersetzungen!"
        echo "   Gefunden: DE=$DE_FOUND, EN=$EN_FOUND"
        echo "   Hinweis: Beide Sprachen müssen identische Informationen enthalten"
        log "ERROR" "Übersetzungen unvollständig: DE=$DE_FOUND, EN=$EN_FOUND"
        ((ERRORS++))
    else
        echo "✓ DE + EN Übersetzungen vorhanden"
        log "INFO" "DE + EN Übersetzungen vorhanden"
    fi
else
    echo "⚠️  Warnung: Kein language/ Verzeichnis - Plugin Store verlangt Übersetzungen!"
    echo "   Erstelle language/de.xml und language/en.xml"
    log "WARNING" "Kein language/ Verzeichnis gefunden"
    ((WARNINGS++))
fi

echo ""

# 6. Ergebnis
echo "═══════════════════════════════════════════════════════════════"
echo "  Validierungs-Ergebnis"
echo "═══════════════════════════════════════════════════════════════"
echo ""

if [ $ERRORS -eq 0 ] && [ $WARNINGS -eq 0 ]; then
    echo "✅ Validierung erfolgreich! Keine Fehler oder Warnungen gefunden."
    log "INFO" "Validierung erfolgreich abgeschlossen: 0 Fehler, 0 Warnungen"
    EXIT_CODE=0
elif [ $ERRORS -eq 0 ]; then
    echo "⚠️  Validierung abgeschlossen mit $WARNINGS Warnung(en)."
    echo "   Die Warnungen sind nicht kritisch, aber sollten geprüft werden."
    log "WARNING" "Validierung abgeschlossen mit $WARNINGS Warnung(en)"
    EXIT_CODE=0
else
    echo "❌ Validierung fehlgeschlagen!"
    echo "   Fehler: $ERRORS"
    echo "   Warnungen: $WARNINGS"
    echo ""
    echo "Bitte behebe die Fehler vor dem Release."
    log "ERROR" "Validierung fehlgeschlagen: $ERRORS Fehler, $WARNINGS Warnungen"
    EXIT_CODE=1
fi

echo ""
echo "ℹ️  Log-Datei: $LOG_FILE"
log "INFO" "Validierung abgeschlossen mit Exit-Code $EXIT_CODE"

exit $EXIT_CODE
