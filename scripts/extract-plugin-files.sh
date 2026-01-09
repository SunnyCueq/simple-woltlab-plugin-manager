#!/bin/bash

# Simple WoltLab Plugin Manager - Extract Plugin Files Script
# Copyright (c) 2025 SunnyCueq
# License: MIT (Open Source)
# Repository: https://github.com/SunnyCueq/simple-woltlab-plugin-manager
#
# ⚠️ IMPORTANT: This copyright notice must not be removed.
# This project is open source under the MIT License, but the copyright
# attribution must be preserved in all copies and substantial portions.
#
# Script zum Entpacken der WoltLab Plugin TAR-Dateien
# unter Beibehaltung der Ordnerstruktur
#
# Verwendung: ./extract-plugin-files.sh [PLUGIN_DIR]
# Falls PLUGIN_DIR nicht angegeben, wird das aktuelle Verzeichnis verwendet

# Fehlerbehandlung
set -euo pipefail

# Fehler-Handler
trap 'error_handler $? $LINENO' ERR

# Logging-Variablen
LOG_FILE="/tmp/extract-plugin-files-$(date +%Y%m%d-%H%M%S).log"
VERBOSE=false

# Fehler-Handler Funktion
error_handler() {
    local exit_code=$1
    local line_number=$2
    echo ""
    echo "❌ FEHLER: Entpacken fehlgeschlagen in Zeile $line_number (Exit-Code: $exit_code)"
    echo "   Log-Datei: $LOG_FILE"
    echo ""
    echo "Häufige Probleme:"
    echo "  • TAR-Dateien fehlen oder sind beschädigt"
    echo "  • Fehlende Schreibrechte im Plugin-Verzeichnis"
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

log "INFO" "Entpacken gestartet"

# Plugin-Verzeichnis bestimmen
if [ -n "$1" ]; then
    PLUGIN_DIR="$1"
else
    PLUGIN_DIR="$(pwd)"
fi

log "INFO" "Plugin-Verzeichnis: $PLUGIN_DIR"

cd "$PLUGIN_DIR" || {
    log "ERROR" "Konnte nicht in Plugin-Verzeichnis wechseln: $PLUGIN_DIR"
    exit 1
}

echo "=== Entpacke WoltLab Plugin TAR-Dateien ==="
echo "Verzeichnis: $PLUGIN_DIR"
echo ""

# Prüfe ob bereits entpackt wurde
if [ -d "_extracted" ]; then
    echo "⚠️  Warnung: _extracted Ordner existiert bereits."
    echo "Möchtest du fortfahren? Dies überschreibt existierende Dateien."
    log "WARNING" "_extracted Ordner existiert bereits, Benutzer wird gefragt"
    read -p "Fortfahren? (j/n): " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[JjYy]$ ]]; then
        echo "Abgebrochen."
        log "INFO" "Entpacken vom Benutzer abgebrochen"
        exit 0
    fi
    log "INFO" "Benutzer hat bestätigt, fortfahren"
fi

# Erstelle Zielordner
log "INFO" "Erstelle Zielordner: _extracted/"
mkdir -p _extracted/{files,files_urlshort,templates_urlshort,acptemplates_urlshort}
log "INFO" "Zielordner erfolgreich erstellt"

# Entpacke files.tar
if [ -f "files.tar" ]; then
    echo "📦 Entpacke files.tar..."
    log "INFO" "Entpacke files.tar"
    tar -xf files.tar -C _extracted/files/ 2>/dev/null || true
    echo "✓ files.tar entpackt"
    log "INFO" "files.tar erfolgreich entpackt"
fi

# Entpacke files_urlshort.tar
if [ -f "files_urlshort.tar" ]; then
    echo "📦 Entpacke files_urlshort.tar..."
    log "INFO" "Entpacke files_urlshort.tar"
    tar -xf files_urlshort.tar -C _extracted/files_urlshort/ 2>/dev/null || true
    echo "✓ files_urlshort.tar entpackt"
    log "INFO" "files_urlshort.tar erfolgreich entpackt"
fi

# Entpacke templates_urlshort.tar
if [ -f "templates_urlshort.tar" ]; then
    echo "📦 Entpacke templates_urlshort.tar..."
    log "INFO" "Entpacke templates_urlshort.tar"
    tar -xf templates_urlshort.tar -C _extracted/templates_urlshort/ 2>/dev/null || true
    echo "✓ templates_urlshort.tar entpackt"
    log "INFO" "templates_urlshort.tar erfolgreich entpackt"
fi

# Entpacke acptemplates_urlshort.tar
if [ -f "acptemplates_urlshort.tar" ]; then
    echo "📦 Entpacke acptemplates_urlshort.tar..."
    log "INFO" "Entpacke acptemplates_urlshort.tar"
    tar -xf acptemplates_urlshort.tar -C _extracted/acptemplates_urlshort/ 2>/dev/null || true
    echo "✓ acptemplates_urlshort.tar entpackt"
    log "INFO" "acptemplates_urlshort.tar erfolgreich entpackt"
fi

# Entpacke templates.tar (falls vorhanden)
if [ -f "templates.tar" ]; then
    echo "📦 Entpacke templates.tar..."
    log "INFO" "Entpacke templates.tar"
    mkdir -p _extracted/templates
    tar -xf templates.tar -C _extracted/templates/ 2>/dev/null || true
    echo "✓ templates.tar entpackt"
    log "INFO" "templates.tar erfolgreich entpackt"
fi

# Entpacke acptemplates.tar (falls vorhanden)
if [ -f "acptemplates.tar" ]; then
    echo "📦 Entpacke acptemplates.tar..."
    log "INFO" "Entpacke acptemplates.tar"
    mkdir -p _extracted/acptemplates
    tar -xf acptemplates.tar -C _extracted/acptemplates/ 2>/dev/null || true
    echo "✓ acptemplates.tar entpackt"
    log "INFO" "acptemplates.tar erfolgreich entpackt"
fi

echo ""
echo "=== Fertig! ==="
echo "Entpackte Dateien befinden sich in: _extracted/"
echo ""

# Zeige Struktur (falls tree verfügbar)
if command -v tree &> /dev/null; then
    echo "Struktur:"
    tree -L 2 _extracted/ 2>/dev/null || find _extracted/ -maxdepth 2 -type d
else
    echo "Struktur:"
    find _extracted/ -maxdepth 2 -type d | head -20
fi

echo ""
echo "ℹ️  Log-Datei: $LOG_FILE"
log "INFO" "Entpacken erfolgreich abgeschlossen"

