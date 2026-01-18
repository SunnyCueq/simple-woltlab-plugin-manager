#!/bin/bash

#################################################################
# WoltLab Git Push - Multi-Plugin Support
# Erkennt automatisch, welches Plugin geändert wurde
# Pusht den gesamten Workspace zum Haupt-Repository
#
# Haupt-Repository: https://github.com/benjarogit/urlshort-featured-links
#
# Usage:
#   ./tools/gitpush.sh                    → auto-detect + push
#   ./tools/gitpush.sh "Nachricht"       → mit custom Commit-Message
#   ./tools/gitpush.sh <plugin-name>     → spezifisches Plugin pushen
#   ./tools/gitpush.sh all               → alle Plugins pushen
#
# Voraussetzungen:
#   - SSH-Key bei GitHub hinterlegt (empfohlen) ODER
#   - Personal Access Token (PAT) für HTTPS-Authentifizierung
#################################################################

set -e

TOOLS_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
MAIN_DIR="$(dirname "$TOOLS_DIR")"

# Lade gemeinsame Funktionen
if [ -f "$TOOLS_DIR/common.sh" ]; then
    source "$TOOLS_DIR/common.sh"
else
    # Fallback
    RED='\033[0;31m'
    GREEN='\033[0;32m'
    YELLOW='\033[1;33m'
    BLUE='\033[0;34m'
    CYAN='\033[0;36m'
    NC='\033[0m'
    
    get_plugin_version() {
        local plugin_dir="$1"
        if [ -f "$plugin_dir/package.xml" ]; then
            grep -oP '<version>\K[^<]+' "$plugin_dir/package.xml" 2>/dev/null || echo "unknown"
        else
            echo "unknown"
        fi
    }
    
    find_plugin_directories() {
        local main_dir="$1"
        local plugins=()
        for dir in "${main_dir}"/*; do
            if [ -d "$dir" ] && [ -f "$dir/package.xml" ]; then
                plugins+=("$(basename "$dir")")
            fi
        done
        printf '%s\n' "${plugins[@]}"
    }
fi

cd "${MAIN_DIR}"

# Finde alle Plugin-Verzeichnisse
PLUGIN_DIRS=($(find_plugin_directories "$MAIN_DIR"))
PLUGIN_COUNT=${#PLUGIN_DIRS[@]}

# Parameter parsen
TARGET="${1:-auto}"
COMMIT_MESSAGE="${2:-}"

# Git Repository URLs aus bestehendem Repository lesen (falls vorhanden)
if [ -d ".git" ] && git remote get-url origin >/dev/null 2>&1; then
    CURRENT_REMOTE=$(git remote get-url origin)
    GIT_REPO_DISPLAY=$(echo "$CURRENT_REMOTE" | sed 's/\.git$//' | sed 's/^git@github.com:/https:\/\/github.com\//')
    if [[ "$CURRENT_REMOTE" =~ ^git@ ]]; then
        GIT_REPO_SSH="$CURRENT_REMOTE"
        GIT_REPO_HTTPS=$(echo "$CURRENT_REMOTE" | sed 's/^git@github.com:/https:\/\/github.com\//')
    else
        GIT_REPO_HTTPS="$CURRENT_REMOTE"
        GIT_REPO_SSH=$(echo "$CURRENT_REMOTE" | sed 's|^https://github.com/|git@github.com:|')
    fi
else
    # Fallback: Versuche aus .git/config zu lesen oder verwende Standard
    GIT_REPO_SSH=""
    GIT_REPO_HTTPS=""
    GIT_REPO_DISPLAY="<nicht konfiguriert>"
fi

# Zeige Header
print_header "Git Push - Multi-Plugin"

# Zeige Plugin-Informationen
if [ "$PLUGIN_COUNT" -gt 0 ]; then
    echo -e "${GREEN}Gefundene Plugins (${PLUGIN_COUNT}):${NC}"
    for plugin_dir in "${PLUGIN_DIRS[@]}"; do
        local version=$(get_plugin_version "$MAIN_DIR/$plugin_dir")
        local name=$(get_plugin_name "$MAIN_DIR/$plugin_dir")
        echo -e "   ${CYAN}•${NC} ${name} ${YELLOW}(v${version})${NC}"
    done
    echo ""
fi

echo -e "${BLUE}Repository: ${GIT_REPO_DISPLAY}${NC}"
echo ""

# Funktion: Git-Repository initialisieren oder klonen
setup_git_repository() {
    if [ -d ".git" ]; then
        echo -e "${GREEN}✓ Git-Repository gefunden${NC}"
        
        # Remote prüfen und setzen
        if git remote get-url origin >/dev/null 2>&1; then
            CURRENT_REMOTE=$(git remote get-url origin)
            echo -e "${BLUE}  Aktueller Remote: ${CURRENT_REMOTE}${NC}"
            echo -e "${GREEN}✓ Remote URL ist konfiguriert${NC}"
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
    CHANGED_PLUGINS=()
    CHANGED_ROOT=0
    
    # Prüfe Änderungen in jedem Plugin
    for plugin_dir in "${PLUGIN_DIRS[@]}"; do
        local changed=$(git diff --name-only HEAD -- "$MAIN_DIR/$plugin_dir" 2>/dev/null | wc -l)
        if [ "$changed" -gt 0 ]; then
            CHANGED_PLUGINS+=("$plugin_dir")
        fi
    done
    
    # Prüfe Root-Änderungen (außer Plugin-Verzeichnissen)
    local root_pattern=""
    for plugin_dir in "${PLUGIN_DIRS[@]}"; do
        root_pattern="${root_pattern}${root_pattern:+|}${plugin_dir}"
    done
    if [ -n "$root_pattern" ]; then
        CHANGED_ROOT=$(git status --porcelain 2>/dev/null | grep -vE "^[^ ]* (${root_pattern})" | wc -l)
    else
        CHANGED_ROOT=$(git status --porcelain 2>/dev/null | wc -l)
    fi

    if [ ${#CHANGED_PLUGINS[@]} -eq 0 ] && [ "$CHANGED_ROOT" -eq 0 ]; then
        print_warning "Keine Änderungen erkannt"
        exit 0
    fi

    # Auto-Detection: Welches Plugin wurde geändert?
    if [ ${#CHANGED_PLUGINS[@]} -eq 1 ]; then
        TARGET="${CHANGED_PLUGINS[0]}"
    elif [ ${#CHANGED_PLUGINS[@]} -gt 1 ]; then
        TARGET="all"
    else
        TARGET="all"
    fi

    echo -e "${YELLOW}Erkannte Änderungen:${NC}"
    for plugin_dir in "${CHANGED_PLUGINS[@]}"; do
        local name=$(get_plugin_name "$MAIN_DIR/$plugin_dir")
        echo -e "   ${CYAN}•${NC} ${name}"
    done
    [ "$CHANGED_ROOT" -gt 0 ] && echo -e "   ${CYAN}•${NC} Root-Dateien (Skripte, README etc.)"
    echo ""
fi

# Ziel bestimmen
TO_PUSH_PLUGINS=()
case "$TARGET" in
    all|auto)
        TO_PUSH_PLUGINS=("${PLUGIN_DIRS[@]}")
        TO_PUSH="Alle Plugins"
        ;;
    *)
        # Prüfe ob TARGET ein gültiges Plugin-Verzeichnis ist
        if [[ " ${PLUGIN_DIRS[@]} " =~ " ${TARGET} " ]]; then
            TO_PUSH_PLUGINS=("$TARGET")
            local name=$(get_plugin_name "$MAIN_DIR/$TARGET")
            TO_PUSH="$name"
        elif [ -z "$COMMIT_MESSAGE" ]; then
            # Wenn TARGET eine Commit-Message ist (kein bekannter Parameter)
            COMMIT_MESSAGE="$TARGET"
            TARGET="auto"
            TO_PUSH_PLUGINS=("${PLUGIN_DIRS[@]}")
            TO_PUSH="Alle Plugins"
        else
            TO_PUSH_PLUGINS=("${PLUGIN_DIRS[@]}")
            TO_PUSH="Alle Plugins"
        fi
        ;;
esac

echo -e "${YELLOW}Push-Ziel: ${TO_PUSH}${NC}\n"

# Check if there are changes
if [ -z "$(git status --porcelain)" ]; then
    echo -e "${YELLOW}⚠ Keine Änderungen zum Committen${NC}"
    exit 0
fi

# Verwende Version des ersten Plugins als Hauptversion (nur für Commit-Message)
if [ ${#TO_PUSH_PLUGINS[@]} -gt 0 ]; then
    VERSION=$(get_plugin_version "$MAIN_DIR/${TO_PUSH_PLUGINS[0]}")
else
    VERSION="unknown"
fi
DATE=$(date +%Y-%m-%d)

# Show status
echo -e "${YELLOW}[1/6] Git Status:${NC}"
git status --short
echo ""

# Default commit message
if [ -z "$COMMIT_MESSAGE" ]; then
    PLUGIN_VERSIONS=""
    for plugin_dir in "${TO_PUSH_PLUGINS[@]}"; do
        local version=$(get_plugin_version "$MAIN_DIR/$plugin_dir")
        local name=$(get_plugin_name "$MAIN_DIR/$plugin_dir")
        PLUGIN_VERSIONS="${PLUGIN_VERSIONS}${name} v${version}, "
    done
    PLUGIN_VERSIONS="${PLUGIN_VERSIONS%, }"  # Entferne letztes Komma
    COMMIT_MESSAGE="Version ${VERSION} - ${TO_PUSH} (${PLUGIN_VERSIONS}) – ${DATE}"
fi

# Commit
echo -e "${YELLOW}[2/6] Committing changes...${NC}"

# WICHTIG: Bestimmte Verzeichnisse NICHT committen
# WICHTIG: Verzeichnisse bleiben erhalten, aber INHALT wird ignoriert
EXCLUDE_DIRS=(
    "basis-plugin"
    "mein-plugin"
    "plugins-integrieren"
    "tools/woltlab-dev/public"
    "woltlab-docs"
    "woltlab-github"
)

# Füge alle Änderungen hinzu
git add -A

# Entferne INHALT der ausgeschlossenen Verzeichnisse wieder aus dem Staging
for exclude_dir in "${EXCLUDE_DIRS[@]}"; do
    if [ -d "$exclude_dir" ]; then
        git reset HEAD "$exclude_dir"/* 2>/dev/null || true
        git reset HEAD "$exclude_dir"/**/* 2>/dev/null || true
        if type debug_info &>/dev/null; then
            debug_info "gitpush" "excluded content of directory: $exclude_dir"
        fi
        echo -e "${YELLOW}  ⚠ Inhalt ausgeschlossen: ${exclude_dir}/*${NC}"
        echo -e "${BLUE}     (Verzeichnis bleibt erhalten für README, .gitignore etc.)${NC}"
    fi
done

# Exclude files that shouldn't be committed
if [ -f "CLAUDE.md" ]; then
    git reset HEAD CLAUDE.md 2>/dev/null || true
fi
if ls CLAUDE*.md 1> /dev/null 2>&1; then
    git reset HEAD CLAUDE*.md 2>/dev/null || true
fi

# Prüfe ob noch etwas zum Committen übrig ist
if [ -z "$(git status --porcelain)" ]; then
    print_warning "Keine Änderungen zum Committen (alle ausgeschlossen)"
    exit 0
fi

git commit -m "${COMMIT_MESSAGE}"
echo -e "${GREEN}✓ Committed: ${COMMIT_MESSAGE}${NC}\n"

# Push
echo -e "${YELLOW}[3/6] Pushing to remote...${NC}"

# Prüfe ob SSH oder HTTPS verwendet wird
CURRENT_REMOTE=$(git remote get-url origin 2>/dev/null || echo "")
if [[ "$CURRENT_REMOTE" == *"git@github.com"* ]]; then
    echo -e "${BLUE}  Verwende SSH-Authentifizierung${NC}"
    if git push origin main 2>/dev/null || git push origin master 2>/dev/null || git push; then
        echo -e "${GREEN}✓ Pushed to remote${NC}\n"
    else
        echo -e "${RED}❌ Fehler beim Push via SSH${NC}"
        echo -e "${YELLOW}  Mögliche Ursachen:${NC}"
        echo -e "${YELLOW}    1. SSH-Key nicht bei GitHub hinterlegt${NC}"
        echo -e "${YELLOW}    2. SSH-Key nicht zum SSH-Agent hinzugefügt${NC}"
        echo -e "${YELLOW}    3. Branch-Name stimmt nicht (main/master)${NC}"
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
    # Create annotated tag
    PLUGIN_INFO=""
    for plugin_dir in "${TO_PUSH_PLUGINS[@]}"; do
        local version=$(get_plugin_version "$MAIN_DIR/$plugin_dir")
        local name=$(get_plugin_name "$MAIN_DIR/$plugin_dir")
        PLUGIN_INFO="${PLUGIN_INFO}${name} v${version}, "
    done
    PLUGIN_INFO="${PLUGIN_INFO%, }"
    git tag -a "${TAG_NAME}" -m "Version ${VERSION} - ${DATE} - ${PLUGIN_INFO}"
    
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
            RELEASE_NOTES="Version ${VERSION} - ${DATE}"$'\n\n'
            for plugin_dir in "${TO_PUSH_PLUGINS[@]}"; do
                local version=$(get_plugin_version "$MAIN_DIR/$plugin_dir")
                local name=$(get_plugin_name "$MAIN_DIR/$plugin_dir")
                RELEASE_NOTES="${RELEASE_NOTES}**${name}:** v${version}"$'\n'
            done
        fi
    else
        RELEASE_NOTES="Version ${VERSION} - ${DATE}"$'\n\n'
        for plugin_dir in "${TO_PUSH_PLUGINS[@]}"; do
            local version=$(get_plugin_version "$MAIN_DIR/$plugin_dir")
            local name=$(get_plugin_name "$MAIN_DIR/$plugin_dir")
            RELEASE_NOTES="${RELEASE_NOTES}**${name}:** v${version}"$'\n'
        done
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
for plugin_dir in "${TO_PUSH_PLUGINS[@]}"; do
    local version=$(get_plugin_version "$MAIN_DIR/$plugin_dir")
    local name=$(get_plugin_name "$MAIN_DIR/$plugin_dir")
    echo -e "${GREEN}${name}: v${version}${NC}"
done
echo -e "${GREEN}Tag: ${TAG_NAME}${NC}"
echo -e "${GREEN}Repository: ${GIT_REPO_DISPLAY}${NC}"
echo -e "${GREEN}========================================${NC}\n"
