#!/bin/bash

# Simple WoltLab Plugin Manager - Update TAR Archives Script
# Copyright (c) 2025 SunnyCueq
# License: MIT (Open Source)
# Repository: https://github.com/SunnyCueq/simple-woltlab-plugin-manager
#
# ⚠️ IMPORTANT: This copyright notice must not be removed.
# This project is open source under the MIT License, but the copyright
# attribution must be preserved in all copies and substantial portions.
#
# Script zum Aktualisieren der TAR-Archive nach Code-Änderungen
# Nutzen Sie dieses Script VOR dem Git-Commit
#
# Verwendung: ./update-tars.sh [PLUGIN_DIR]
# Falls PLUGIN_DIR nicht angegeben, wird das aktuelle Verzeichnis verwendet

set -e

# Plugin-Verzeichnis bestimmen
if [ -n "$1" ]; then
    PLUGIN_DIR="$1"
else
    PLUGIN_DIR="$(pwd)"
fi

cd "$PLUGIN_DIR" || exit 1

echo "=== Aktualisiere TAR-Archive ==="
echo "Verzeichnis: $PLUGIN_DIR"
echo ""

# Prüfe ob _extracted existiert
if [ ! -d "_extracted" ]; then
    echo "❌ Fehler: _extracted/ Ordner nicht gefunden!"
    echo "Führe zuerst ./extract-plugin-files.sh aus"
    exit 1
fi

# Backup alte TAR-Dateien
if [ -f "files.tar" ] || [ -f "files_urlshort.tar" ] || [ -f "templates_urlshort.tar" ] || [ -f "acptemplates_urlshort.tar" ]; then
    echo "📦 Erstelle Backups..."
    mkdir -p .tar-backups
    [ -f "files.tar" ] && cp files.tar .tar-backups/files.tar.bak
    [ -f "files_urlshort.tar" ] && cp files_urlshort.tar .tar-backups/files_urlshort.tar.bak
    [ -f "templates_urlshort.tar" ] && cp templates_urlshort.tar .tar-backups/templates_urlshort.tar.bak
    [ -f "acptemplates_urlshort.tar" ] && cp acptemplates_urlshort.tar .tar-backups/acptemplates_urlshort.tar.bak
    [ -f "templates.tar" ] && cp templates.tar .tar-backups/templates.tar.bak
    [ -f "acptemplates.tar" ] && cp acptemplates.tar .tar-backups/acptemplates.tar.bak
    echo "✓ Backups erstellt in .tar-backups/"
fi

# Aktualisiere TAR-Dateien
echo ""
echo "🔄 Erstelle neue TAR-Archive..."

# files.tar
if [ -d "_extracted/files" ] && [ "$(ls -A _extracted/files 2>/dev/null)" ]; then
    echo "  → files.tar"
    cd _extracted/files && tar -cf ../../files.tar * 2>/dev/null && cd ../..
fi

# files_urlshort.tar
if [ -d "_extracted/files_urlshort" ] && [ "$(ls -A _extracted/files_urlshort 2>/dev/null)" ]; then
    echo "  → files_urlshort.tar"
    cd _extracted/files_urlshort && tar -cf ../../files_urlshort.tar * 2>/dev/null && cd ../..
fi

# templates_urlshort.tar
if [ -d "_extracted/templates_urlshort" ] && [ "$(ls -A _extracted/templates_urlshort 2>/dev/null)" ]; then
    echo "  → templates_urlshort.tar"
    cd _extracted/templates_urlshort && tar -cf ../../templates_urlshort.tar * 2>/dev/null && cd ../..
fi

# acptemplates_urlshort.tar
if [ -d "_extracted/acptemplates_urlshort" ] && [ "$(ls -A _extracted/acptemplates_urlshort 2>/dev/null)" ]; then
    echo "  → acptemplates_urlshort.tar"
    cd _extracted/acptemplates_urlshort && tar -cf ../../acptemplates_urlshort.tar * 2>/dev/null && cd ../..
fi

# templates.tar (falls vorhanden)
if [ -d "_extracted/templates" ] && [ "$(ls -A _extracted/templates 2>/dev/null)" ]; then
    echo "  → templates.tar"
    cd _extracted/templates && tar -cf ../../templates.tar * 2>/dev/null && cd ../..
fi

# acptemplates.tar (falls vorhanden)
if [ -d "_extracted/acptemplates" ] && [ "$(ls -A _extracted/acptemplates 2>/dev/null)" ]; then
    echo "  → acptemplates.tar"
    cd _extracted/acptemplates && tar -cf ../../acptemplates.tar * 2>/dev/null && cd ../..
fi

echo ""
echo "=== Fertig! ==="
echo ""
echo "✅ Alle TAR-Archive wurden aktualisiert"
echo ""
echo "Nächste Schritte:"
echo "  1. git add *.tar"
echo "  2. git commit -m 'Update: Beschreibung'"
echo "  3. git push"
echo ""

