#!/bin/bash

#################################################################
# Intelligenter Git Push für Multi-Plugin-Projekt
# Erkennt automatisch, welches Plugin geändert wurde
# Pfad: /home/benny/Dokumente/affiliate-plugin/gitpush.sh
#
# Usage:
#   ./gitpush.sh                    → auto-detect + push
#   ./gitpush.sh "Meine Nachricht"  → mit custom Commit-Message
#   ./gitpush.sh basis              → nur basis-plugin pushen
#   ./gitpush.sh mein               → nur mein-plugin pushen
#   ./gitpush.sh both               → beide Plugins pushen
#
# Voraussetzungen:
#   - SSH-Key bei GitHub hinterlegt (empfohlen) ODER
#   - Personal Access Token (PAT) für HTTPS-Authentifizierung
#################################################################

set -e

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "${SCRIPT_DIR}"

# Plugin-Quellen-Verzeichnisse
BASIS_SOURCE_DIR="${SCRIPT_DIR}/basis-plugin"
MEIN_SOURCE_DIR="${SCRIPT_DIR}/mein-plugin"

# Git Repository URLs
GIT_REPO_SSH="git@github.com:benjarogit/urlshort-featured-links.git"
GIT_REPO_HTTPS="https://github.com/benjarogit/urlshort-featured-links.git"
GIT_REPO_DISPLAY="https://github.com/benjarogit/urlshort-featured-links"

# Parameter parsen
TARGET="${1:-auto}"
COMMIT_MESSAGE="${2:-}"

# Funktion: Version aus package.xml lesen
get_version() {
    local dir="$1"
    if [ -f "$dir/package.xml" ]; then
        grep -oP '<version>\K[^<]+' "$dir/package.xml" 2>/dev/null || echo "unknown"
    elif [ -f "$dir/_extracted/package.xml" ]; then
        grep -oP '<version>\K[^<]+' "$dir/_extracted/package.xml" 2>/dev/null || echo "unknown"
else
        echo "unknown"
fi
}

BASIS_VERSION=$(get_version "$BASIS_SOURCE_DIR")
MEIN_VERSION=$(get_version "$MEIN_SOURCE_DIR")

echo -e "${GREEN}========================================${NC}"
echo -e "${GREEN}Git Push – Multi-Plugin${NC}"
echo -e "${GREEN}basis-plugin: v$BASIS_VERSION | mein-plugin: v$MEIN_VERSION${NC}"
echo -e "${GREEN}Repository: ${GIT_REPO_DISPLAY}${NC}"
echo -e "${GREEN}========================================${NC}\n"

# Funktion: Git-Repository initialisieren oder klonen
setup_git_repository() {
    if [ -d ".git" ]; then
        echo -e "${GREEN}✓ Git-Repository gefunden${NC}"
        
        # Remote prüfen und setzen
        if git remote get-url origin >/dev/null 2>&1; then
            CURRENT_REMOTE=$(git remote get-url origin)
            echo -e "${BLUE}  Aktueller Remote: ${CURRENT_REMOTE}${NC}"
            
            # Prüfe ob Remote korrekt ist (SSH oder HTTPS)
            if [[ "$CURRENT_REMOTE" != *"benjarogit/urlshort-featured-links"* ]]; then
                echo -e "${YELLOW}⚠ Remote URL stimmt nicht überein, setze auf SSH-URL...${NC}"
                git remote set-url origin "${GIT_REPO_SSH}"
                echo -e "${GREEN}✓ Remote auf ${GIT_REPO_SSH} gesetzt${NC}"
            else
                echo -e "${GREEN}✓ Remote URL ist korrekt${NC}"
            fi
        else
            echo -e "${YELLOW}⚠ Kein Remote gefunden, füge hinzu...${NC}"
            git remote add origin "${GIT_REPO_SSH}"
            echo -e "${GREEN}✓ Remote ${GIT_REPO_SSH} hinzugefügt${NC}"
        fi
    else
        echo -e "${YELLOW}⚠ Kein Git-Repository gefunden${NC}"
        echo -e "${BLUE}  Initialisiere Git-Repository...${NC}"
        
        # Prüfe ob SSH-Key verfügbar ist
        if ssh -T git@github.com 2>&1 | grep -q "successfully authenticated"; then
            echo -e "${GREEN}✓ SSH-Key für GitHub gefunden${NC}"
            USE_SSH=true
        else
            echo -e "${YELLOW}⚠ SSH-Key für GitHub nicht gefunden oder nicht konfiguriert${NC}"
            echo -e "${BLUE}  Verwende HTTPS (Personal Access Token erforderlich)${NC}"
            USE_SSH=false
        fi
        
        # Repository klonen oder initialisieren
        if [ "$USE_SSH" = true ]; then
            echo -e "${BLUE}  Klone Repository via SSH...${NC}"
            if git clone "${GIT_REPO_SSH}" . 2>/dev/null; then
                echo -e "${GREEN}✓ Repository erfolgreich geklont${NC}"
            else
                echo -e "${RED}❌ Fehler beim Klonen via SSH${NC}"
                echo -e "${YELLOW}  Versuche HTTPS...${NC}"
                git clone "${GIT_REPO_HTTPS}" . || {
                    echo -e "${RED}❌ Fehler beim Klonen${NC}"
                    echo -e "${YELLOW}  Initialisiere neues Repository stattdessen...${NC}"
                    git init
                    git remote add origin "${GIT_REPO_SSH}"
                }
            fi
        else
            echo -e "${BLUE}  Klone Repository via HTTPS...${NC}"
            echo -e "${YELLOW}  Hinweis: Du wirst nach Username und Personal Access Token gefragt${NC}"
            if git clone "${GIT_REPO_HTTPS}" . 2>/dev/null; then
                echo -e "${GREEN}✓ Repository erfolgreich geklont${NC}"
            else
                echo -e "${RED}❌ Fehler beim Klonen${NC}"
                echo -e "${YELLOW}  Initialisiere neues Repository stattdessen...${NC}"
    git init
                git remote add origin "${GIT_REPO_HTTPS}"
                echo -e "${YELLOW}  WICHTIG: Du musst später beim Push dein Personal Access Token eingeben${NC}"
fi
        fi
    fi
    
    echo ""
}

# Git-Repository einrichten
setup_git_repository

# Änderungen prüfen (nur wenn auto-Detection)
if [ "$TARGET" = "auto" ]; then
    CHANGED_BASIS=$(git diff --name-only HEAD -- "$BASIS_SOURCE_DIR" 2>/dev/null | wc -l)
    CHANGED_MEIN=$(git diff --name-only HEAD -- "$MEIN_SOURCE_DIR" 2>/dev/null | wc -l)
    CHANGED_ROOT=$(git status --porcelain 2>/dev/null | grep -v "^[^ ]* \(basis-plugin\|mein-plugin\)" | wc -l)

    if [ "$CHANGED_BASIS" -eq 0 ] && [ "$CHANGED_MEIN" -eq 0 ] && [ "$CHANGED_ROOT" -eq 0 ]; then
        echo -e "${YELLOW}⚠ Keine Änderungen erkannt${NC}"
        exit 0
    fi

    # Auto-Detection: Welches Plugin wurde geändert?
    if [ "$CHANGED_BASIS" -gt 0 ] && [ "$CHANGED_MEIN" -eq 0 ]; then
        TARGET="basis"
    elif [ "$CHANGED_MEIN" -gt 0 ] && [ "$CHANGED_BASIS" -eq 0 ]; then
        TARGET="mein"
    else
        TARGET="both"
    fi

    echo -e "${YELLOW}Erkannte Änderungen:${NC}"
    [ "$CHANGED_BASIS" -gt 0 ] && echo "   • basis-plugin"
    [ "$CHANGED_MEIN" -gt 0 ] && echo "   • mein-plugin"
    [ "$CHANGED_ROOT" -gt 0 ] && echo "   • Root-Dateien (Skripte, README etc.)"
    echo ""
fi

# Ziel bestimmen
case "$TARGET" in
    basis)
        TO_PUSH="basis-plugin"
        ;;
    mein)
        TO_PUSH="mein-plugin"
        ;;
    both)
        TO_PUSH="beide Plugins"
        ;;
    auto)
        TO_PUSH="beide Plugins"
        ;;
    *)
        # Wenn TARGET eine Commit-Message ist (kein bekannter Parameter)
        if [ -z "$COMMIT_MESSAGE" ]; then
            COMMIT_MESSAGE="$TARGET"
            TARGET="auto"
            TO_PUSH="beide Plugins"
        else
            TO_PUSH="beide Plugins"
fi
        ;;
esac

echo -e "${YELLOW}Push-Ziel: $TO_PUSH${NC}\n"

# Check if there are changes
if [ -z "$(git status --porcelain)" ]; then
    echo -e "${YELLOW}⚠ Keine Änderungen zum Committen${NC}"
    exit 0
fi

# Use mein-plugin version as main version
VERSION="${MEIN_VERSION}"
DATE=$(date +%Y-%m-%d)

# Check if changelog entry exists
echo -e "${YELLOW}[0/6] Prüfe Changelog...${NC}"
if [ -f "CHANGELOG.md" ]; then
    CHANGELOG_EXISTS=$(awk -v version="${VERSION}" '/^## Version / {if ($0 ~ "Version " version) {found=1; exit}} END {if (found) print "yes"; else print "no"}' CHANGELOG.md)
    
    if [ "$CHANGELOG_EXISTS" = "no" ]; then
        echo -e "${YELLOW}⚠ Kein Changelog-Eintrag für Version ${VERSION} gefunden${NC}"
        
        # Generate changelog entry - WICHTIG: /r/{hash}/ statt /urls/{hash}/
        CHANGELOG_ENTRY="- Migration zu WoltLab Suite 6.1
- SEO-optimierte URLs: /r/{hash}/
- basis-plugin v${BASIS_VERSION}
- mein-plugin v${MEIN_VERSION}"
        
        # Create changelog entry
        TEMP_FILE=$(mktemp)
        {
            echo "# Changelog - URL-Shortener: Featured Links"
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
            echo -e "$CHANGELOG_ENTRY"
            echo ""
            echo "---"
            echo ""
            tail -n +2 CHANGELOG.md 2>/dev/null || true
        } > "$TEMP_FILE"
        
        mv "$TEMP_FILE" CHANGELOG.md
        echo -e "${GREEN}✓ Changelog-Eintrag für Version ${VERSION} erstellt${NC}\n"
    else
        echo -e "${GREEN}✓ Changelog-Eintrag für Version ${VERSION} gefunden${NC}\n"
    fi
else
    echo -e "${YELLOW}⚠ CHANGELOG.md nicht gefunden - erstelle neuen${NC}"
    cat > CHANGELOG.md <<EOF
# Changelog - URL-Shortener: Featured Links

**Aktuelle Version:** ${VERSION}
**Status:** Produktionsbereit ✅

---

## Version ${VERSION} ($(date +%Y-%m-%d))

### 🔧 Änderungen

- Migration zu WoltLab Suite 6.1
- SEO-optimierte URLs: /r/{hash}/
- basis-plugin v${BASIS_VERSION}
- mein-plugin v${MEIN_VERSION}

---
EOF
    echo -e "${GREEN}✓ CHANGELOG.md erstellt${NC}\n"
fi

# Show status
echo -e "${YELLOW}[1/6] Git Status:${NC}"
git status --short
echo ""

# Default commit message
if [ -z "$COMMIT_MESSAGE" ]; then
    COMMIT_MESSAGE="Version ${VERSION} - ${TO_PUSH} – basis v${BASIS_VERSION}, mein v${MEIN_VERSION} – $(date +%Y-%m-%d)"
fi

# Commit
echo -e "${YELLOW}[2/6] Committing changes...${NC}"
git add -A

# Exclude files that shouldn't be committed
if [ -f "CLAUDE.md" ]; then
    git reset HEAD CLAUDE.md 2>/dev/null || true
fi
if ls CLAUDE*.md 1> /dev/null 2>&1; then
    git reset HEAD CLAUDE*.md 2>/dev/null || true
fi

git commit -m "${COMMIT_MESSAGE}"
echo -e "${GREEN}✓ Committed: ${COMMIT_MESSAGE}${NC}\n"

# Push
echo -e "${YELLOW}[3/6] Pushing to remote...${NC}"

# Prüfe ob SSH oder HTTPS verwendet wird
CURRENT_REMOTE=$(git remote get-url origin 2>/dev/null || echo "")
if [[ "$CURRENT_REMOTE" == *"git@github.com"* ]]; then
    echo -e "${BLUE}  Verwende SSH-Authentifizierung${NC}"
    # SSH-Push (sollte automatisch funktionieren wenn SSH-Key konfiguriert ist)
    if git push origin main 2>/dev/null || git push origin master 2>/dev/null || git push; then
        echo -e "${GREEN}✓ Pushed to remote${NC}\n"
    else
        echo -e "${RED}❌ Fehler beim Push via SSH${NC}"
        echo -e "${YELLOW}  Mögliche Ursachen:${NC}"
        echo -e "${YELLOW}    1. SSH-Key nicht bei GitHub hinterlegt${NC}"
        echo -e "${YELLOW}    2. SSH-Key nicht zum SSH-Agent hinzugefügt${NC}"
        echo -e "${YELLOW}    3. Branch-Name stimmt nicht (main/master)${NC}"
        echo ""
        echo -e "${BLUE}  Lösung: Siehe Anleitung am Ende dieses Skripts${NC}"
        exit 1
    fi
else
    echo -e "${BLUE}  Verwende HTTPS-Authentifizierung${NC}"
    echo -e "${YELLOW}  Hinweis: Du wirst nach Username und Personal Access Token gefragt${NC}"
    echo -e "${YELLOW}  Username: Dein GitHub-Benutzername${NC}"
    echo -e "${YELLOW}  Password: Dein Personal Access Token (NICHT dein Passwort!)${NC}"
    echo ""
    if git push origin main 2>/dev/null || git push origin master 2>/dev/null || git push; then
echo -e "${GREEN}✓ Pushed to remote${NC}\n"
    else
        echo -e "${RED}❌ Fehler beim Push via HTTPS${NC}"
        echo -e "${YELLOW}  Mögliche Ursachen:${NC}"
        echo -e "${YELLOW}    1. Personal Access Token fehlt oder ist ungültig${NC}"
        echo -e "${YELLOW}    2. Token hat nicht die erforderlichen Berechtigungen${NC}"
        echo -e "${YELLOW}    3. Branch-Name stimmt nicht (main/master)${NC}"
        echo ""
        echo -e "${BLUE}  Lösung: Siehe Anleitung am Ende dieses Skripts${NC}"
        exit 1
    fi
fi

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
        CHANGELOG_SECTION=$(awk "/^## Version ${VERSION}/,/^## Version /" CHANGELOG.md | sed '$d')
        if [ -z "$CHANGELOG_SECTION" ]; then
            CHANGELOG_SECTION="Version ${VERSION} - ${DATE} - basis-plugin v${BASIS_VERSION}, mein-plugin v${MEIN_VERSION}"
        fi
        git tag -a "${TAG_NAME}" -m "${CHANGELOG_SECTION}"
    else
        git tag -a "${TAG_NAME}" -m "Version ${VERSION} - ${DATE} - basis-plugin v${BASIS_VERSION}, mein-plugin v${MEIN_VERSION}"
    fi
    
    # Push tag
    echo -e "${BLUE}  Pushe Tag...${NC}"
    if git push origin "${TAG_NAME}" 2>/dev/null; then
    echo -e "${GREEN}✓ Release tag ${TAG_NAME} erstellt und gepusht${NC}"
    else
        echo -e "${YELLOW}⚠ Tag konnte nicht gepusht werden (möglicherweise Authentifizierungsproblem)${NC}"
        echo -e "${YELLOW}   Tag wurde lokal erstellt, bitte manuell pushen: git push origin ${TAG_NAME}${NC}"
    fi
fi

# Create GitHub Release
echo -e "${YELLOW}[5/6] Creating GitHub Release...${NC}"

# Check if gh CLI is available
if command -v gh &> /dev/null; then
    # Extract changelog for release notes
    RELEASE_NOTES=""
    if [ -f "CHANGELOG.md" ]; then
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
        
        if [ -z "$RELEASE_NOTES" ] || [ -z "$(echo "$RELEASE_NOTES" | tr -d '[:space:]')" ]; then
            RELEASE_NOTES="Version ${VERSION} - ${DATE}

**basis-plugin:** v${BASIS_VERSION}
**mein-plugin:** v${MEIN_VERSION}

Migration zu WoltLab Suite 6.1 mit SEO-optimierten URLs (/r/{hash}/)."
        fi
    else
        RELEASE_NOTES="Version ${VERSION} - ${DATE}

**basis-plugin:** v${BASIS_VERSION}
**mein-plugin:** v${MEIN_VERSION}

Migration zu WoltLab Suite 6.1 mit SEO-optimierten URLs (/r/{hash}/)."
    fi
    
    # Check if release already exists
    if gh release view "${TAG_NAME}" >/dev/null 2>&1; then
        # Update existing release
        if echo "$RELEASE_NOTES" | gh release edit "${TAG_NAME}" --notes-file - 2>/dev/null; then
            echo -e "${GREEN}✓ GitHub Release ${TAG_NAME} aktualisiert${NC}"
        else
            echo -e "${YELLOW}⚠ GitHub Release ${TAG_NAME} konnte nicht aktualisiert werden${NC}"
            echo -e "${YELLOW}   Möglicherweise musst du dich bei GitHub CLI anmelden: gh auth login${NC}"
        fi
    else
        # Create new release
        if echo "$RELEASE_NOTES" | gh release create "${TAG_NAME}" --title "Version ${VERSION}" --notes-file - 2>/dev/null; then
            echo -e "${GREEN}✓ GitHub Release ${TAG_NAME} erstellt${NC}"
        else
            echo -e "${YELLOW}⚠ GitHub Release ${TAG_NAME} konnte nicht erstellt werden${NC}"
            echo -e "${YELLOW}   Möglicherweise musst du dich bei GitHub CLI anmelden: gh auth login${NC}"
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
echo -e "${GREEN}basis-plugin: ${BASIS_VERSION}${NC}"
echo -e "${GREEN}mein-plugin: ${MEIN_VERSION}${NC}"
echo -e "${GREEN}Tag: ${TAG_NAME}${NC}"
echo -e "${GREEN}Repository: ${GIT_REPO_DISPLAY}${NC}"
echo -e "${GREEN}========================================${NC}\n"
