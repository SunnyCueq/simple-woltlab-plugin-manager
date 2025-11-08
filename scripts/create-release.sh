#!/bin/bash

# Simple WoltLab Plugin Manager - Create Release Script
# Copyright (c) 2025 SunnyCueq
# License: MIT (Open Source)
# Repository: https://github.com/SunnyCueq/simple-woltlab-plugin-manager
#
# ⚠️ IMPORTANT: This copyright notice must not be removed.
# This project is open source under the MIT License, but the copyright
# attribution must be preserved in all copies and substantial portions.
#
# Script zum Erstellen eines Plugin-Packages und optional GitHub Release
#
# Verwendung: ./create-release.sh VERSION [PLUGIN_DIR] [GITHUB_REPO]
#   VERSION: Versionsnummer (z.B. 1.0.0)
#   PLUGIN_DIR: Plugin-Verzeichnis (optional, Standard: aktuelles Verzeichnis)
#   GITHUB_REPO: GitHub Repository im Format "owner/repo" (optional)
#
# Beispiel: ./create-release.sh 1.0.0 /path/to/plugin owner/repo-name

set -e

# Parameter prüfen
if [ -z "$1" ]; then
    echo "❌ Fehler: Versionsnummer fehlt!"
    echo ""
    echo "Verwendung: $0 VERSION [PLUGIN_DIR] [GITHUB_REPO]"
    echo "Beispiel: $0 1.0.0 /path/to/plugin owner/repo-name"
    exit 1
fi

VERSION="$1"
PLUGIN_DIR="${2:-$(pwd)}"
GITHUB_REPO="$3"

cd "$PLUGIN_DIR" || exit 1

# Prüfe ob package.xml existiert
if [ ! -f "package.xml" ]; then
    echo "❌ Fehler: package.xml nicht gefunden in $PLUGIN_DIR"
    exit 1
fi

# Package-Name aus package.xml extrahieren
PACKAGE_NAME=$(grep -oP 'name="\K[^"]+' package.xml | head -1)
if [ -z "$PACKAGE_NAME" ]; then
    echo "❌ Fehler: Konnte Package-Name nicht aus package.xml extrahieren"
    exit 1
fi

# Aktuelle Version aus package.xml lesen
CURRENT_VERSION=$(grep -oP '<version>\K[^<]+' package.xml | head -1)
if [ -z "$CURRENT_VERSION" ]; then
    echo "⚠️  Warnung: Konnte aktuelle Version nicht aus package.xml lesen"
    CURRENT_VERSION="0.0.0"
fi

PACKAGE_FILE="${PACKAGE_NAME}-${VERSION}.tar.gz"

echo "═══════════════════════════════════════════════════════════════"
echo "  Plugin-Package erstellen"
echo "═══════════════════════════════════════════════════════════════"
echo ""
echo "Package: $PACKAGE_NAME"
echo "Aktuelle Version: $CURRENT_VERSION"
echo "Neue Version: $VERSION"
echo "Verzeichnis: $PLUGIN_DIR"
echo ""

# Backup des letzten TAR-Archivs erstellen
BACKUP_DIR=".package-backups"
mkdir -p "$BACKUP_DIR"

# Finde das letzte Package-Archiv
LAST_PACKAGE=$(ls -t "${PACKAGE_NAME}"-*.tar.gz 2>/dev/null | head -1)

if [ -n "$LAST_PACKAGE" ]; then
    echo "💾 Erstelle Backup des letzten Packages..."
    BACKUP_FILE="$BACKUP_DIR/${LAST_PACKAGE}.backup"
    cp "$LAST_PACKAGE" "$BACKUP_FILE"
    echo "✓ Backup erstellt: $BACKUP_FILE"
    echo ""
fi

# Aktualisiere Version in package.xml
echo "📝 Aktualisiere Version in package.xml..."

# Aktuelle Version ersetzen
if [ "$CURRENT_VERSION" != "$VERSION" ]; then
    # Backup der originalen package.xml
    cp package.xml package.xml.bak
    
    # Aktualisiere Version
    sed -i "s/<version>$CURRENT_VERSION<\/version>/<version>$VERSION<\/version>/g" package.xml
    
    # Aktualisiere auch das Datum
    TODAY=$(date +%Y-%m-%d)
    sed -i "s/<date>[0-9]\{4\}-[0-9]\{2\}-[0-9]\{2\}<\/date>/<date>$TODAY<\/date>/g" package.xml
    
    echo "✓ Version aktualisiert: $CURRENT_VERSION → $VERSION"
    echo "✓ Datum aktualisiert: $TODAY"
    echo ""
    echo "ℹ️  package.xml wurde aktualisiert. Backup: package.xml.bak"
    echo ""
else
    echo "ℹ️  Version bereits $VERSION, keine Änderung nötig"
    echo ""
fi

# Lade Package.xml Parser
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
source "$SCRIPT_DIR/parse-package-xml.sh"

# Zeige Package-Struktur
echo "=== Package-Struktur ==="
show_package_structure "package.xml" "$PLUGIN_DIR"
echo ""

# Parse package.xml und hole alle benötigten Dateien
echo "📋 Analysiere package.xml..."
FILES_TO_PACKAGE=()
while IFS= read -r file; do
    if [ -n "$file" ]; then
        FILES_TO_PACKAGE+=("$file")
    fi
done < <(get_required_files "package.xml" "$PLUGIN_DIR")

if [ ${#FILES_TO_PACKAGE[@]} -eq 0 ]; then
    echo "❌ Fehler: Keine Dateien zum Packen gefunden"
    exit 1
fi

echo "✅ Gefundene Dateien: ${#FILES_TO_PACKAGE[@]}"
echo ""

# Erstelle Package in /tmp
echo "📦 Erstelle Package..."
cd /tmp || exit 1

# Erstelle temporäres Verzeichnis
TMP_DIR=$(mktemp -d)
trap "rm -rf $TMP_DIR" EXIT

# Kopiere alle benötigten Dateien
for file in "${FILES_TO_PACKAGE[@]}"; do
    file_path="$PLUGIN_DIR/$file"
    
    if [ -f "$file_path" ]; then
        cp "$file_path" "$TMP_DIR/"
        echo "  ✅ Kopiert: $file"
    elif [ -d "$file_path" ]; then
        cp -r "$file_path" "$TMP_DIR/"
        echo "  ✅ Kopiert: $file/ (Verzeichnis)"
    else
        echo "  ⚠️  Warnung: $file nicht gefunden, überspringe..."
    fi
done

# Erstelle TAR.GZ
cd "$TMP_DIR" || exit 1
tar -czf "/tmp/$PACKAGE_FILE" *
mv "/tmp/$PACKAGE_FILE" "$PLUGIN_DIR/"

echo "✅ Package erstellt: $PACKAGE_FILE"
echo ""

# package.xml.bak bleibt als Backup erhalten
# Die aktualisierte package.xml mit neuer Version bleibt aktiv
cd "$PLUGIN_DIR" || exit 1
if [ -f "package.xml.bak" ]; then
    echo "ℹ️  Backup der originalen package.xml: package.xml.bak"
    echo "   Die package.xml wurde mit Version $VERSION aktualisiert"
    echo ""
fi

# GitHub Release erstellen (falls Repository angegeben)
if [ -n "$GITHUB_REPO" ]; then
    echo "=== GitHub Release erstellen ==="
    
    # Prüfe ob GitHub CLI installiert ist
    if ! command -v gh &> /dev/null; then
        echo "⚠️  GitHub CLI nicht gefunden. Installieren Sie es mit:"
        echo "   Linux: sudo pacman -S github-cli (oder entsprechendes Paket-Manager)"
        echo "   macOS: brew install gh"
        echo "   Windows: winget install GitHub.cli"
        echo ""
        echo "Oder erstellen Sie das Release manuell auf GitHub."
        exit 0
    fi
    
    # Prüfe GitHub-Authentifizierung
    if ! gh auth status &> /dev/null; then
        echo "⚠️  Sie sind nicht bei GitHub angemeldet!"
        echo "Bitte führen Sie aus: gh auth login"
        exit 1
    fi
    
    # Release-Titel aus CHANGELOG.md extrahieren (falls vorhanden)
    RELEASE_TITLE="Version $VERSION"
    if [ -f "$PLUGIN_DIR/CHANGELOG.md" ]; then
        # Versuche Titel aus CHANGELOG zu extrahieren
        CHANGELOG_TITLE=$(grep -A 1 "^## Version $VERSION" "$PLUGIN_DIR/CHANGELOG.md" | head -2 | tail -1 | sed 's/^### //' | sed 's/^## //' | xargs)
        if [ -n "$CHANGELOG_TITLE" ]; then
            RELEASE_TITLE="$CHANGELOG_TITLE"
        fi
    fi
    
    # Release erstellen
    echo "🚀 Erstelle GitHub Release v$VERSION..."
    cd "$PLUGIN_DIR" || exit 1
    
    if [ -f "CHANGELOG.md" ]; then
        gh release create "v$VERSION" \
            "$PACKAGE_FILE" \
            --title "Version $VERSION" \
            --notes-file CHANGELOG.md \
            --repo "$GITHUB_REPO"
    else
        gh release create "v$VERSION" \
            "$PACKAGE_FILE" \
            --title "Version $VERSION" \
            --repo "$GITHUB_REPO"
    fi
    
    echo ""
    echo "=== ✅ Release erfolgreich erstellt! ==="
    echo ""
    echo "📦 Download-Link:"
    echo "https://github.com/$GITHUB_REPO/releases/download/v$VERSION/$PACKAGE_FILE"
    echo ""
    echo "🔗 Release-Seite:"
    echo "https://github.com/$GITHUB_REPO/releases/tag/v$VERSION"
else
    echo "ℹ️  Kein GitHub Repository angegeben. Release nur lokal erstellt."
    echo "   Für GitHub Release: $0 $VERSION $PLUGIN_DIR owner/repo-name"
fi

