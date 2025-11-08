#!/bin/bash

# Script zum Entpacken der WoltLab Plugin TAR-Dateien
# unter Beibehaltung der Ordnerstruktur
#
# Verwendung: ./extract-plugin-files.sh [PLUGIN_DIR]
# Falls PLUGIN_DIR nicht angegeben, wird das aktuelle Verzeichnis verwendet

set -e

# Plugin-Verzeichnis bestimmen
if [ -n "$1" ]; then
    PLUGIN_DIR="$1"
else
    PLUGIN_DIR="$(pwd)"
fi

cd "$PLUGIN_DIR" || exit 1

echo "=== Entpacke WoltLab Plugin TAR-Dateien ==="
echo "Verzeichnis: $PLUGIN_DIR"
echo ""

# Prüfe ob bereits entpackt wurde
if [ -d "_extracted" ]; then
    echo "⚠️  Warnung: _extracted Ordner existiert bereits."
    echo "Möchten Sie fortfahren? Dies überschreibt existierende Dateien."
    read -p "Fortfahren? (j/n): " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[JjYy]$ ]]; then
        echo "Abgebrochen."
        exit 0
    fi
fi

# Erstelle Zielordner
mkdir -p _extracted/{files,files_urlshort,templates_urlshort,acptemplates_urlshort}

# Entpacke files.tar
if [ -f "files.tar" ]; then
    echo "📦 Entpacke files.tar..."
    tar -xf files.tar -C _extracted/files/ 2>/dev/null || true
    echo "✓ files.tar entpackt"
fi

# Entpacke files_urlshort.tar
if [ -f "files_urlshort.tar" ]; then
    echo "📦 Entpacke files_urlshort.tar..."
    tar -xf files_urlshort.tar -C _extracted/files_urlshort/ 2>/dev/null || true
    echo "✓ files_urlshort.tar entpackt"
fi

# Entpacke templates_urlshort.tar
if [ -f "templates_urlshort.tar" ]; then
    echo "📦 Entpacke templates_urlshort.tar..."
    tar -xf templates_urlshort.tar -C _extracted/templates_urlshort/ 2>/dev/null || true
    echo "✓ templates_urlshort.tar entpackt"
fi

# Entpacke acptemplates_urlshort.tar
if [ -f "acptemplates_urlshort.tar" ]; then
    echo "📦 Entpacke acptemplates_urlshort.tar..."
    tar -xf acptemplates_urlshort.tar -C _extracted/acptemplates_urlshort/ 2>/dev/null || true
    echo "✓ acptemplates_urlshort.tar entpackt"
fi

# Entpacke templates.tar (falls vorhanden)
if [ -f "templates.tar" ]; then
    echo "📦 Entpacke templates.tar..."
    mkdir -p _extracted/templates
    tar -xf templates.tar -C _extracted/templates/ 2>/dev/null || true
    echo "✓ templates.tar entpackt"
fi

# Entpacke acptemplates.tar (falls vorhanden)
if [ -f "acptemplates.tar" ]; then
    echo "📦 Entpacke acptemplates.tar..."
    mkdir -p _extracted/acptemplates
    tar -xf acptemplates.tar -C _extracted/acptemplates/ 2>/dev/null || true
    echo "✓ acptemplates.tar entpackt"
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

