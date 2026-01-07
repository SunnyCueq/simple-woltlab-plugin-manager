#!/bin/bash

#################################################################
# Git Push & Release Script
# Package: info.benjaro.urlshort.affiliate
# Author: Sunny C.
# 
# Automatisiert:
# - Git Commit
# - Git Push
# - Release mit Changelog erstellen
#################################################################

set -e  # Exit on error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Script directory
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
cd "${SCRIPT_DIR}"

# Package information
PACKAGE_NAME="info.benjaro.urlshort.affiliate"

# Read version from package.xml
if [ ! -f "${SCRIPT_DIR}/package.xml" ]; then
    echo -e "${RED}❌ Fehler: package.xml nicht gefunden${NC}"
    exit 1
fi

VERSION=$(grep -oP '<version>\K[^<]+' "${SCRIPT_DIR}/package.xml")
DATE=$(date +%Y-%m-%d)

echo -e "${GREEN}========================================${NC}"
echo -e "${GREEN}Git Push & Release Script${NC}"
echo -e "${GREEN}Package: ${PACKAGE_NAME}${NC}"
echo -e "${GREEN}Version: ${VERSION}${NC}"
echo -e "${GREEN}========================================${NC}\n"

# Check if git repository
if [ ! -d ".git" ]; then
    echo -e "${RED}❌ Fehler: Kein Git-Repository gefunden${NC}"
    exit 1
fi

# Check if there are changes
if [ -z "$(git status --porcelain)" ]; then
    echo -e "${YELLOW}⚠ Keine Änderungen zum Committen${NC}"
    exit 0
fi

# Check if changelog entry exists for this version
echo -e "${YELLOW}[0/6] Prüfe Changelog...${NC}"
if [ -f "CHANGELOG.md" ]; then
    CHANGELOG_EXISTS=$(awk -v version="${VERSION}" '/^## Version / {if ($0 ~ "Version " version) {found=1; exit}} END {if (found) print "yes"; else print "no"}' CHANGELOG.md)
    
    if [ "$CHANGELOG_EXISTS" = "no" ]; then
        echo -e "${YELLOW}⚠ Kein Changelog-Eintrag für Version ${VERSION} gefunden${NC}"
        
        # Analysiere geänderte Dateien, um sinnvolle Changelog-Einträge zu generieren
        # (ignoriere Auto-Commits, die nicht hilfreich sind)
        CHANGELOG_ENTRY=""
        
        # Prüfe welche Dateien seit dem letzten Commit geändert wurden
        if git rev-parse HEAD~1 >/dev/null 2>&1; then
            CHANGED_FILES=$(git diff --name-only HEAD~1 HEAD 2>/dev/null)
        else
            # Fallback: Verwende staged/untracked files
            CHANGED_FILES=$(git status --porcelain | cut -c4-)
        fi
        
        # Analysiere Änderungen basierend auf Dateien
        if echo "$CHANGED_FILES" | grep -q "postInstall"; then
            CHANGELOG_ENTRY="${CHANGELOG_ENTRY}- PostInstall-Script verbessert"$'\n'
        fi
        if echo "$CHANGED_FILES" | grep -q "install_info.*\.php"; then
            CHANGELOG_ENTRY="${CHANGELOG_ENTRY}- Datenbank-Installation aktualisiert"$'\n'
        fi
        if echo "$CHANGED_FILES" | grep -qE "\.(xml|acpMenu|option|userGroupOption|objectType|cronjob)"; then
            CHANGELOG_ENTRY="${CHANGELOG_ENTRY}- XML-Konfiguration aktualisiert"$'\n'
        fi
        if echo "$CHANGED_FILES" | grep -qE "\.(scss|css)"; then
            CHANGELOG_ENTRY="${CHANGELOG_ENTRY}- Styles aktualisiert"$'\n'
        fi
        if echo "$CHANGED_FILES" | grep -qE "\.(ts|js)"; then
            CHANGELOG_ENTRY="${CHANGELOG_ENTRY}- JavaScript/TypeScript aktualisiert"$'\n'
        fi
        if echo "$CHANGED_FILES" | grep -q "language"; then
            CHANGELOG_ENTRY="${CHANGELOG_ENTRY}- Sprachdateien aktualisiert"$'\n'
        fi
        if echo "$CHANGED_FILES" | grep -qE "\.(tpl|template)"; then
            CHANGELOG_ENTRY="${CHANGELOG_ENTRY}- Templates aktualisiert"$'\n'
        fi
        if echo "$CHANGED_FILES" | grep -q "gitpush\.sh\|build\.sh"; then
            CHANGELOG_ENTRY="${CHANGELOG_ENTRY}- Build-Scripts verbessert"$'\n'
        fi
        
        # Entferne letztes Newline
        CHANGELOG_ENTRY="${CHANGELOG_ENTRY%$'\n'}"
        
        # Wenn immer noch leer, verwende Standard-Text
        if [ -z "$CHANGELOG_ENTRY" ] || [ -z "$(echo "$CHANGELOG_ENTRY" | tr -d '[:space:]')" ]; then
            CHANGELOG_ENTRY="- Bugfixes und Verbesserungen"
        fi
        
        echo -e "${YELLOW}   Generiere Changelog aus geänderten Dateien...${NC}"
        
        # Erstelle Changelog-Eintrag
        TEMP_FILE=$(mktemp)
        {
            echo "# Changelog - URL-Shortener: Affiliate-Erweiterung"
            echo ""
            echo "**Aktuelle Version:** ${VERSION}"
            echo "**Status:** Produktionsbereit ✅"
            echo ""
            echo "---"
            echo ""
            echo "## Version ${VERSION} ($(date +%Y-%m-%d))"
            echo ""
            echo "### 🔧 Änderungen"
            echo ""
            # Ausgabe des Changelog-Eintrags (mit Newlines)
            echo -e "$CHANGELOG_ENTRY"
            echo ""
            echo "---"
            echo ""
            # Rest der Datei anhängen (ab der 2. Zeile um Duplikate zu vermeiden)
            tail -n +2 CHANGELOG.md
        } > "$TEMP_FILE"
        
        mv "$TEMP_FILE" CHANGELOG.md
        echo -e "${GREEN}✓ Changelog-Eintrag für Version ${VERSION} erstellt${NC}\n"
    else
        echo -e "${GREEN}✓ Changelog-Eintrag für Version ${VERSION} gefunden${NC}\n"
    fi
else
    echo -e "${YELLOW}⚠ CHANGELOG.md nicht gefunden - überspringe Prüfung${NC}\n"
fi

# Show status
echo -e "${YELLOW}[1/6] Git Status:${NC}"
git status --short
echo ""

# Ask for commit message
if [ -z "$1" ]; then
    echo -e "${YELLOW}Commit-Message (leer = automatisch):${NC}"
    read -r COMMIT_MESSAGE
else
    COMMIT_MESSAGE="$1"
fi

# Default commit message
if [ -z "$COMMIT_MESSAGE" ]; then
    COMMIT_MESSAGE="Version ${VERSION} - $(date +%Y-%m-%d)"
fi

# Commit
echo -e "${YELLOW}[2/6] Committing changes...${NC}"
git add -A

# Exclude files that shouldn't be committed
if [ -f "ICON_IMPLEMENTATION_STATUS.md" ]; then
    git reset HEAD ICON_IMPLEMENTATION_STATUS.md 2>/dev/null || true
fi
if [ -f "CLAUDE.md" ]; then
    git reset HEAD CLAUDE.md 2>/dev/null || true
fi

git commit -m "${COMMIT_MESSAGE}"
echo -e "${GREEN}✓ Committed: ${COMMIT_MESSAGE}${NC}\n"

# Push
echo -e "${YELLOW}[3/6] Pushing to remote...${NC}"
git push
echo -e "${GREEN}✓ Pushed to remote${NC}\n"

# Create release tag
TAG_NAME="v${VERSION}"
echo -e "${YELLOW}[4/6] Creating release tag...${NC}"

# Check if tag already exists
if git rev-parse "${TAG_NAME}" >/dev/null 2>&1; then
    echo -e "${YELLOW}⚠ Tag ${TAG_NAME} existiert bereits${NC}"
    echo -e "${YELLOW}   Überspringe Tag-Erstellung${NC}"
else
    # Create annotated tag with changelog
    if [ -f "CHANGELOG.md" ]; then
        # Extract changelog for this version
        CHANGELOG_SECTION=$(awk "/^## Version ${VERSION}/,/^## Version /" CHANGELOG.md | sed '$d')
        if [ -z "$CHANGELOG_SECTION" ]; then
            CHANGELOG_SECTION="Version ${VERSION} - ${DATE}"
        fi
        git tag -a "${TAG_NAME}" -m "${CHANGELOG_SECTION}"
    else
        git tag -a "${TAG_NAME}" -m "Version ${VERSION} - ${DATE}"
    fi
    
    # Push tag
    git push origin "${TAG_NAME}"
    echo -e "${GREEN}✓ Release tag ${TAG_NAME} erstellt und gepusht${NC}"
fi

# Build package after push (damit beide auf dem gleichen Stand sind)
# ENTFERNT: build.sh ruft bereits gitpush.sh auf - rekursiver Aufruf würde Endlosschleife verursachen
# echo -e "${YELLOW}[5/7] Building package...${NC}"
# if [ -f "./build.sh" ]; then
#     ./build.sh > /dev/null 2>&1
#     echo -e "${GREEN}✓ Package built${NC}\n"
# else
#     echo -e "${YELLOW}⚠ build.sh nicht gefunden, überspringe Build${NC}\n"
# fi

# Create GitHub Release
echo -e "${YELLOW}[5/6] Creating GitHub Release...${NC}"

# Check if gh CLI is available
if command -v gh &> /dev/null; then
    # Extract changelog for release notes
    RELEASE_NOTES=""
    if [ -f "CHANGELOG.md" ]; then
        # Extract section from "## Version X" to next "## Version" or end of file
        RELEASE_NOTES=$(awk -v version="${VERSION}" '
            /^## Version / {
                if (found) exit
                if ($0 ~ "Version " version) {
                    found = 1
                    next
                }
            }
            found {
                if (/^## Version /) exit
                print
            }
        ' CHANGELOG.md)
        
        # Fallback if extraction failed
        if [ -z "$RELEASE_NOTES" ] || [ -z "$(echo "$RELEASE_NOTES" | tr -d '[:space:]')" ]; then
            RELEASE_NOTES="Version ${VERSION} - ${DATE}"
        fi
    else
        RELEASE_NOTES="Version ${VERSION} - ${DATE}"
    fi
    
    # Check if release already exists
    if gh release view "${TAG_NAME}" >/dev/null 2>&1; then
        # Update existing release
        if echo "$RELEASE_NOTES" | gh release edit "${TAG_NAME}" --notes-file - 2>/dev/null; then
            echo -e "${GREEN}✓ GitHub Release ${TAG_NAME} aktualisiert${NC}"
        else
            echo -e "${YELLOW}⚠ GitHub Release ${TAG_NAME} konnte nicht aktualisiert werden${NC}"
        fi
    else
        # Create new release
        if echo "$RELEASE_NOTES" | gh release create "${TAG_NAME}" --title "Version ${VERSION}" --notes-file - 2>/dev/null; then
            echo -e "${GREEN}✓ GitHub Release ${TAG_NAME} erstellt${NC}"
        else
            echo -e "${YELLOW}⚠ GitHub Release ${TAG_NAME} konnte nicht erstellt werden${NC}"
        fi
    fi
else
    echo -e "${YELLOW}⚠ GitHub CLI (gh) nicht gefunden${NC}"
    echo -e "${YELLOW}   Installiere mit: sudo pacman -S github-cli (oder apt install gh)${NC}"
    echo -e "${YELLOW}   Oder erstelle Release manuell auf GitHub${NC}"
fi

echo -e "\n${GREEN}========================================${NC}"
echo -e "${GREEN}✓ Git Push & Release abgeschlossen!${NC}"
echo -e "${GREEN}========================================${NC}"
echo -e "${GREEN}Version: ${VERSION}${NC}"
echo -e "${GREEN}Tag: ${TAG_NAME}${NC}"
echo -e "${GREEN}========================================${NC}\n"

