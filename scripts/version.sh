#!/bin/bash

# Simple WoltLab Plugin Manager - Version Management Script
# Copyright (c) 2025 SunnyCueq
# License: MIT (Open Source)
# Repository: https://github.com/SunnyCueq/simple-woltlab-plugin-manager
#
# ⚠️ IMPORTANT: This copyright notice must not be removed.
# This project is open source under the MIT License, but the copyright
# attribution must be preserved in all copies and substantial portions.
#
# This script manages version numbers for the Simple WoltLab Plugin Manager
# and creates version tags following WoltLab's semantic versioning (MAJOR.MINOR.PATCH)
#
# Usage: ./scripts/version.sh [major|minor|patch] [--dry-run]
#   major: Increment major version (1.0.0 -> 2.0.0) - Breaking changes
#   minor: Increment minor version (1.0.0 -> 1.1.0) - New features, backwards compatible
#   patch: Increment patch version (1.0.0 -> 1.0.1) - Bug fixes, backwards compatible
#   --dry-run: Show what would be done without making changes

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"
cd "$REPO_ROOT"

# Current version from README.md
CURRENT_VERSION=$(grep -E "^\*\*Version:\*\*" README.md | head -1 | sed 's/.*\*\*Version:\*\* //' | sed 's/ .*//')

if [ -z "$CURRENT_VERSION" ]; then
    echo "❌ Fehler: Konnte aktuelle Version nicht aus README.md lesen"
    exit 1
fi

echo "═══════════════════════════════════════════════════════════════"
echo "  Version Management"
echo "═══════════════════════════════════════════════════════════════"
echo ""
echo "Aktuelle Version: $CURRENT_VERSION"
echo ""

# Parse version
IFS='.' read -ra VERSION_PARTS <<< "$CURRENT_VERSION"
MAJOR="${VERSION_PARTS[0]}"
MINOR="${VERSION_PARTS[1]}"
PATCH="${VERSION_PARTS[2]}"

# Determine increment type
INCREMENT_TYPE="${1:-patch}"
DRY_RUN=false

if [ "$1" == "--dry-run" ]; then
    DRY_RUN=true
    INCREMENT_TYPE="patch"
elif [ "$2" == "--dry-run" ]; then
    DRY_RUN=true
fi

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
    *)
        echo "❌ Fehler: Ungültiger Inkrement-Typ: $INCREMENT_TYPE"
        echo ""
        echo "Verwendung: $0 [major|minor|patch] [--dry-run]"
        echo ""
        echo "  major: Hauptversion (1.0.0 -> 2.0.0) - Breaking Changes"
        echo "  minor: Nebenversion (1.0.0 -> 1.1.0) - Neue Features"
        echo "  patch: Patch-Version (1.0.0 -> 1.0.1) - Bugfixes"
        exit 1
        ;;
esac

NEW_VERSION="${NEW_MAJOR}.${NEW_MINOR}.${NEW_PATCH}"

echo "Neue Version: $NEW_VERSION"
echo ""
echo "Inkrement-Typ: $INCREMENT_TYPE"
echo "  • Major: Breaking Changes, API-Änderungen"
echo "  • Minor: Neue Features, rückwärtskompatibel"
echo "  • Patch: Bugfixes, rückwärtskompatibel"
echo ""

if [ "$DRY_RUN" = true ]; then
    echo "🔍 DRY-RUN Modus - Es werden keine Änderungen vorgenommen"
    echo ""
    echo "Folgende Dateien würden aktualisiert:"
    echo "  • README.md"
    echo "  • README_EN.md"
    echo "  • docs/README_ADVANCED.md"
    echo ""
    echo "Git-Tag würde erstellt: v$NEW_VERSION"
    exit 0
fi

read -p "Möchtest du die Version auf $NEW_VERSION erhöhen? (j/n): " -n 1 -r
echo
if [[ ! $REPLY =~ ^[JjYy]$ ]]; then
    echo "Abgebrochen."
    exit 0
fi

echo ""
echo "📝 Aktualisiere Version und Datum in Dateien..."

# Get current date
CURRENT_DATE=$(date +%Y-%m-%d)

# Update README.md
sed -i "s/\*\*Version:\*\* $CURRENT_VERSION/\*\*Version:\*\* $NEW_VERSION/g" README.md
sed -i "s/Version: $CURRENT_VERSION/Version: $NEW_VERSION/g" README.md
sed -i "s/\*\*Letzte Aktualisierung:\*\* [0-9]\{4\}-[0-9]\{2\}-[0-9]\{2\}/\*\*Letzte Aktualisierung:\*\* $CURRENT_DATE/g" README.md

# Update README_EN.md
sed -i "s/\*\*Version:\*\* $CURRENT_VERSION/\*\*Version:\*\* $NEW_VERSION/g" README_EN.md
sed -i "s/Version: $CURRENT_VERSION/Version: $NEW_VERSION/g" README_EN.md
sed -i "s/\*\*Last Updated:\*\* [0-9]\{4\}-[0-9]\{2\}-[0-9]\{2\}/\*\*Last Updated:\*\* $CURRENT_DATE/g" README_EN.md

# Update docs/README_ADVANCED.md (if version is mentioned)
if grep -q "Version:" docs/README_ADVANCED.md 2>/dev/null; then
    sed -i "s/Version: $CURRENT_VERSION/Version: $NEW_VERSION/g" docs/README_ADVANCED.md
fi

echo "✅ Version und Datum aktualisiert"
echo ""

# Create git tag
echo "🏷️  Erstelle Git-Tag..."
if git tag -a "v$NEW_VERSION" -m "Version $NEW_VERSION

$(case "$INCREMENT_TYPE" in
    major) echo "Breaking Changes - Major Update" ;;
    minor) echo "New Features - Minor Update" ;;
    patch) echo "Bugfixes - Patch Update" ;;
esac)" 2>/dev/null; then
    echo "✅ Git-Tag erstellt: v$NEW_VERSION"
else
    echo "⚠️  Warnung: Git-Tag konnte nicht erstellt werden (möglicherweise existiert bereits)"
fi

echo ""
echo "═══════════════════════════════════════════════════════════════"
echo "  ✅ Version erfolgreich auf $NEW_VERSION erhöht"
echo "═══════════════════════════════════════════════════════════════"
echo ""
echo "Nächste Schritte:"
echo ""
echo "1. Änderungen prüfen:"
echo "   git diff"
echo ""
echo "2. Änderungen committen:"
echo "   git add README.md README_EN.md docs/README_ADVANCED.md"
echo "   git commit -m \"chore: Version auf $NEW_VERSION erhöht\""
echo ""
echo "3. Tag pushen:"
echo "   git push origin v$NEW_VERSION"
echo ""
echo "4. Änderungen pushen:"
echo "   git push origin main"
echo ""
echo "5. GitHub Release erstellen:"
echo "   # Automatisch mit GitHub CLI (falls installiert):"
echo "   gh release create v$NEW_VERSION --title \"Version $NEW_VERSION\" --notes \"## Version $NEW_VERSION\""
echo ""
echo "   # Oder manuell auf GitHub:"
echo "   # https://github.com/SunnyCueq/simple-woltlab-plugin-manager/releases/new"
echo "   # Tag: v$NEW_VERSION"
echo "   # Titel: Version $NEW_VERSION"
echo ""
echo "⚠️  WICHTIG: Bei jeder neuen Version IMMER ein GitHub Release erstellen!"
echo ""

