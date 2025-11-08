#!/bin/bash

# Simple WoltLab Plugin Manager - Plugin Version Management Script
# Copyright (c) 2025 SunnyCueq
# License: MIT (Open Source)
# Repository: https://github.com/SunnyCueq/simple-woltlab-plugin-manager
#
# ⚠️ IMPORTANT: This copyright notice must not be removed.
# This project is open source under the MIT License, but the copyright
# attribution must be preserved in all copies and substantial portions.
#
# This script manages version numbers for WoltLab plugins
# It automatically increments the version in package.xml and creates a release
#
# Usage: ./plugin-version.sh [major|minor|patch] [PLUGIN_DIR] [--no-release]
#   major: Increment major version (1.0.0 -> 2.0.0) - Breaking changes
#   minor: Increment minor version (1.0.0 -> 1.1.0) - New features
#   patch: Increment patch version (1.0.0 -> 1.0.1) - Bug fixes
#   --no-release: Only update version, don't create release package
#   PLUGIN_DIR: Plugin directory (optional, defaults to current directory)

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
CREATE_RELEASE_SCRIPT="$SCRIPT_DIR/create-release.sh"

# Parse arguments
INCREMENT_TYPE=""
PLUGIN_DIR=""
NO_RELEASE=false

for arg in "$@"; do
    case "$arg" in
        major|minor|patch)
            INCREMENT_TYPE="$arg"
            ;;
        --no-release)
            NO_RELEASE=true
            ;;
        *)
            if [ -z "$PLUGIN_DIR" ] && [ -d "$arg" ]; then
                PLUGIN_DIR="$arg"
            fi
            ;;
    esac
done

if [ -z "$INCREMENT_TYPE" ]; then
    echo "❌ Fehler: Inkrement-Typ fehlt!"
    echo ""
    echo "Verwendung: $0 [major|minor|patch] [PLUGIN_DIR] [--no-release]"
    echo ""
    echo "  major: Hauptversion (1.0.0 -> 2.0.0) - Breaking Changes"
    echo "  minor: Nebenversion (1.0.0 -> 1.1.0) - Neue Features"
    echo "  patch: Patch-Version (1.0.0 -> 1.0.1) - Bugfixes"
    echo ""
    echo "Optionen:"
    echo "  --no-release: Nur Version aktualisieren, kein Package erstellen"
    exit 1
fi

PLUGIN_DIR="${PLUGIN_DIR:-$(pwd)}"
cd "$PLUGIN_DIR" || exit 1

# Prüfe ob package.xml existiert
if [ ! -f "package.xml" ]; then
    echo "❌ Fehler: package.xml nicht gefunden in $PLUGIN_DIR"
    exit 1
fi

# Lese aktuelle Version
CURRENT_VERSION=$(grep -oP '<version>\K[^<]+' package.xml | head -1)
if [ -z "$CURRENT_VERSION" ]; then
    echo "❌ Fehler: Konnte Version nicht aus package.xml lesen"
    exit 1
fi

# Parse version
IFS='.' read -ra VERSION_PARTS <<< "$CURRENT_VERSION"
MAJOR="${VERSION_PARTS[0]}"
MINOR="${VERSION_PARTS[1]}"
PATCH="${VERSION_PARTS[2]}"

# Calculate new version
case "$INCREMENT_TYPE" in
    major)
        NEW_MAJOR=$((MAJOR + 1))
        NEW_MINOR=0
        NEW_PATCH=0
        ;;
    minor)
        NEW_MAJOR=$MAJOR
        NEW_MINOR=$((MINOR + 1))
        NEW_PATCH=0
        ;;
    patch)
        NEW_MAJOR=$MAJOR
        NEW_MINOR=$MINOR
        NEW_PATCH=$((PATCH + 1))
        ;;
esac

NEW_VERSION="${NEW_MAJOR}.${NEW_MINOR}.${NEW_PATCH}"

echo "═══════════════════════════════════════════════════════════════"
echo "  Plugin-Versionsverwaltung"
echo "═══════════════════════════════════════════════════════════════"
echo ""
echo "Plugin-Verzeichnis: $PLUGIN_DIR"
echo "Aktuelle Version: $CURRENT_VERSION"
echo "Neue Version: $NEW_VERSION"
echo "Inkrement-Typ: $INCREMENT_TYPE"
echo ""

read -p "Möchten Sie die Version auf $NEW_VERSION erhöhen? (j/n): " -n 1 -r
echo
if [[ ! $REPLY =~ ^[JjYy]$ ]]; then
    echo "Abgebrochen."
    exit 0
fi

# Update version in package.xml
echo ""
echo "📝 Aktualisiere package.xml..."

# Backup package.xml
cp package.xml package.xml.bak

# Update version
sed -i "s/<version>$CURRENT_VERSION<\/version>/<version>$NEW_VERSION<\/version>/g" package.xml

# Update date
TODAY=$(date +%Y-%m-%d)
sed -i "s/<date>[0-9]\{4\}-[0-9]\{2\}-[0-9]\{2\}<\/date>/<date>$TODAY<\/date>/g" package.xml

echo "✅ Version aktualisiert: $CURRENT_VERSION → $NEW_VERSION"
echo "✅ Datum aktualisiert: $TODAY"
echo ""

# Restore backup
rm -f package.xml.bak

# Create release if not disabled
if [ "$NO_RELEASE" = false ]; then
    echo "═══════════════════════════════════════════════════════════════"
    echo "  Package erstellen"
    echo "═══════════════════════════════════════════════════════════════"
    echo ""
    
    # Prüfe ob update-tars.sh ausgeführt werden muss
    if [ -d "_extracted" ]; then
        echo "ℹ️  _extracted/ Verzeichnis gefunden."
        read -p "Möchten Sie die TAR-Archive aktualisieren? (j/n): " -n 1 -r
        echo
        if [[ $REPLY =~ ^[JjYy]$ ]]; then
            echo ""
            "$SCRIPT_DIR/update-tars.sh" "$PLUGIN_DIR"
            echo ""
        fi
    fi
    
    # Erstelle Release
    echo "🚀 Erstelle Package..."
    "$CREATE_RELEASE_SCRIPT" "$NEW_VERSION" "$PLUGIN_DIR" "$GITHUB_REPO"
else
    echo "═══════════════════════════════════════════════════════════════"
    echo "  ✅ Version erfolgreich aktualisiert"
    echo "═══════════════════════════════════════════════════════════════"
    echo ""
    echo "Nächste Schritte:"
    echo ""
    echo "1. TAR-Archive aktualisieren (falls nötig):"
    echo "   ./update-tars.sh"
    echo ""
    echo "2. Package erstellen:"
    echo "   ./create-release.sh $NEW_VERSION"
    echo ""
fi

