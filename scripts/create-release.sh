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

# Validierung 1: XML-Syntax prüfen
echo "🔍 Validiere package.xml Syntax..."
if command -v xmllint &> /dev/null; then
    if ! xmllint --noout package.xml 2>/dev/null; then
        echo "❌ Fehler: package.xml hat XML-Syntax-Fehler!"
        echo "   Bitte prüfe die Datei mit: xmllint package.xml"
        exit 1
    fi
    echo "✓ XML-Syntax OK"
else
    echo "⚠️  Warnung: xmllint nicht gefunden, überspringe XML-Validierung"
    echo "   Installiere mit: sudo pacman -S libxml2 (Arch) oder sudo apt install libxml2-utils (Debian)"
fi

# Package-Name aus package.xml extrahieren
PACKAGE_NAME=$(grep -oP 'name="\K[^"]+' package.xml | head -1)
if [ -z "$PACKAGE_NAME" ]; then
    echo "❌ Fehler: Konnte Package-Name nicht aus package.xml extrahieren"
    exit 1
fi

# Validierung 2: Package-Name-Format prüfen
echo "🔍 Validiere Package-Name-Format..."
if [[ ! "$PACKAGE_NAME" =~ ^com\.[a-z0-9]+\.[a-z0-9]+(\.[a-z0-9]+)*$ ]]; then
    echo "⚠️  Warnung: Package-Name entspricht möglicherweise nicht dem Standard-Format!"
    echo "   Erwartetes Format: com.domain.pluginname (z.B. com.example.myplugin)"
    echo "   Gefundener Name: $PACKAGE_NAME"
    echo ""
    read -p "Möchtest du trotzdem fortfahren? (j/n): " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[JjYy]$ ]]; then
        echo "Abgebrochen."
        exit 0
    fi
else
    echo "✓ Package-Name-Format OK"
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

# Backup-System: Behalte immer die zwei letzten Versionen
# System: Aktuelle Version = Release, Vorherige Version = Backup
BACKUP_DIR=".package-backups"
mkdir -p "$BACKUP_DIR"

# Finde alle Package-Archive (sortiert nach Zeit, neueste zuerst)
ALL_PACKAGES=($(ls -t "${PACKAGE_NAME}"-*.tar.gz 2>/dev/null))

if [ ${#ALL_PACKAGES[@]} -gt 0 ]; then
    echo "💾 Backup-System: Behalte die zwei letzten Versionen..."
    
    # Das neueste Package (Index 0) ist die vorherige Version
    # Die aktuelle Version (wird gerade erstellt) wird das neue neueste
    # System: Aktuelle = Release, Vorherige = Backup
    
    if [ ${#ALL_PACKAGES[@]} -ge 1 ]; then
        PREVIOUS_PACKAGE="${ALL_PACKAGES[0]}"
        BACKUP_FILE="$BACKUP_DIR/$(basename "$PREVIOUS_PACKAGE")"
        
        # Verschiebe die vorherige Version ins Backup-Verzeichnis
        if [ ! -f "$BACKUP_FILE" ]; then
            mv "$PREVIOUS_PACKAGE" "$BACKUP_FILE" 2>/dev/null || cp "$PREVIOUS_PACKAGE" "$BACKUP_FILE"
            echo "✓ Backup erstellt: $BACKUP_FILE (vorherige Version als Backup)"
        fi
    fi
    
    # Lösche/Archiviere alle älteren Versionen (behalte nur die zwei neuesten)
    if [ ${#ALL_PACKAGES[@]} -gt 1 ]; then
        for i in "${!ALL_PACKAGES[@]}"; do
            if [ $i -ge 1 ]; then
                OLD_PACKAGE="${ALL_PACKAGES[$i]}"
                OLD_BACKUP="$BACKUP_DIR/$(basename "$OLD_PACKAGE")"
                # Verschiebe ältere Versionen ins Backup-Verzeichnis
                if [ ! -f "$OLD_BACKUP" ] && [ -f "$OLD_PACKAGE" ]; then
                    mv "$OLD_PACKAGE" "$OLD_BACKUP" 2>/dev/null || true
                    echo "ℹ️  Ältere Version archiviert: $(basename "$OLD_PACKAGE")"
                fi
            fi
        done
    fi
    
    echo "✓ Backup-System: Aktuelle Version (wird erstellt) = Release"
    echo "✓ Backup-System: Vorherige Version = Backup in .package-backups/"
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

# Validierung 3: Prüfe ob alle benötigten Dateien existieren
echo "🔍 Prüfe ob alle benötigten Dateien existieren..."
MISSING_FILES=()
for file in "${FILES_TO_PACKAGE[@]}"; do
    file_path="$PLUGIN_DIR/$file"
    if [ ! -f "$file_path" ] && [ ! -d "$file_path" ]; then
        MISSING_FILES+=("$file")
    fi
done

if [ ${#MISSING_FILES[@]} -gt 0 ]; then
    echo "❌ Fehler: Folgende Dateien fehlen:"
    for file in "${MISSING_FILES[@]}"; do
        echo "   - $file"
    done
    echo ""
    echo "Bitte erstelle die fehlenden Dateien oder entferne sie aus package.xml"
    exit 1
fi

echo "✓ Alle benötigten Dateien vorhanden"
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
        echo "⚠️  GitHub CLI nicht gefunden. Installiere es mit:"
        echo "   Linux: sudo pacman -S github-cli (oder entsprechendes Paket-Manager)"
        echo "   macOS: brew install gh"
        echo "   Windows: winget install GitHub.cli"
        echo ""
        echo "Oder erstelle das Release manuell auf GitHub."
        exit 0
    fi
    
    # Prüfe GitHub-Authentifizierung
    if ! gh auth status &> /dev/null; then
        echo "⚠️  Du bist nicht bei GitHub angemeldet!"
        echo "Bitte führe aus: gh auth login"
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

