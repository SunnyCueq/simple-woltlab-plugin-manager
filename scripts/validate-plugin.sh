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
set -euo pipefail

# Fehler-Handler
trap 'error_handler $? $LINENO' ERR

# Logging-Variablen
LOG_FILE="/tmp/validate-plugin-$(date +%Y%m%d-%H%M%S).log"
VERBOSE=false

# Validierungs-Zähler
ERRORS=0
WARNINGS=0

# Fehler-Handler Funktion
error_handler() {
    local exit_code=$1
    local line_number=$2
    echo ""
    echo "❌ FEHLER: Validierung fehlgeschlagen in Zeile $line_number (Exit-Code: $exit_code)"
    echo "   Log-Datei: $LOG_FILE"
    echo ""
    exit 1
}

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

# 5. Ergebnis
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
