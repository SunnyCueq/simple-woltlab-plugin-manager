#!/bin/bash

#################################################################
# WoltLab Git Push - Multi-Plugin Support
# Erkennt automatisch, welches Plugin geändert wurde
# Pusht den gesamten Workspace zum konfigurierten origin-Repository
# (origin wird im Setup oder per „git remote add origin <URL>“ gesetzt)
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

#=====================================
# KONFIGURATION
#=====================================
readonly TOOLS_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
readonly MAIN_DIR="$(dirname "$TOOLS_DIR")"

#=====================================
# QUELLEN
#=====================================
if [ ! -f "$TOOLS_DIR/common.sh" ]; then
    echo "Fehler: common.sh nicht gefunden in $TOOLS_DIR" >&2
    exit 1
fi
source "$TOOLS_DIR/common.sh"

cd "${MAIN_DIR}"

#=====================================
# HILFSFUNKTIONEN
#=====================================
normalize_plugin_path() {
    local plugin_path="$1"
    if [[ "$plugin_path" =~ ^/ ]]; then
        # Bereits vollständiger Pfad
        echo "$plugin_path"
    else
        # Relativer Pfad - füge MAIN_DIR hinzu
        echo "$MAIN_DIR/$plugin_path"
    fi
}

#=====================================
# HAUPTLOGIK
#=====================================
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

# Fallback: GIT_REPO_URL aus tools/.env lesen, wenn noch kein origin gesetzt
if [ -z "$GIT_REPO_SSH" ] && [ -f "$TOOLS_DIR/.env" ]; then
    GIT_REPO_URL=$(grep -E "^GIT_REPO_URL=" "$TOOLS_DIR/.env" 2>/dev/null | cut -d= -f2- | tr -d '\r\n' | sed 's/^[[:space:]]*//;s/[[:space:]]*$//')
    if [ -n "$GIT_REPO_URL" ]; then
        GIT_REPO_DISPLAY=$(echo "$GIT_REPO_URL" | sed 's/\.git$//' | sed 's|^git@github.com:|https://github.com/|')
        if [[ "$GIT_REPO_URL" =~ ^git@ ]]; then
            GIT_REPO_SSH="$GIT_REPO_URL"
            GIT_REPO_HTTPS=$(echo "$GIT_REPO_URL" | sed 's/^git@github.com:/https:\/\/github.com\//')
        else
            GIT_REPO_HTTPS="$GIT_REPO_URL"
            GIT_REPO_SSH=$(echo "$GIT_REPO_URL" | sed 's|^https://github.com/|git@github.com:|')
        fi
    fi
fi

# Zeige Header
print_header "Git Push - Multi-Plugin"

# Zeige Plugin-Informationen
if [ "$PLUGIN_COUNT" -gt 0 ]; then
    print_list "Gefundene Plugins (${PLUGIN_COUNT})"
    for plugin_dir in "${PLUGIN_DIRS[@]}"; do
        plugin_path=$(normalize_plugin_path "$plugin_dir")
        version=$(get_plugin_version "$plugin_path")
        name=$(get_plugin_name "$plugin_path")
        print_list_item "•" "${name} ${YELLOW}(v${version})${NC}"
    done
    echo ""
fi

print_info "Repository: ${GIT_REPO_DISPLAY}"
echo ""

# Funktion: Git-Repository initialisieren oder klonen
setup_git_repository() {
    if [ -d ".git" ]; then
        print_success "Git-Repository gefunden"
        
        # Remote prüfen und setzen
        if git remote get-url origin >/dev/null 2>&1; then
            CURRENT_REMOTE=$(git remote get-url origin)
            print_info "Aktueller Remote: ${CURRENT_REMOTE}"
            print_success "Remote URL ist konfiguriert"
        else
            print_warning "Kein Remote gefunden, füge hinzu..."
            git remote add origin "${GIT_REPO_SSH}"
            print_success "Remote ${GIT_REPO_SSH} hinzugefügt"
        fi
    else
        print_warning "Kein Git-Repository gefunden"
        print_info "Initialisiere Git-Repository..."
        
        # Prüfe ob SSH-Key verfügbar ist
        if ssh -T git@github.com 2>&1 | grep -q "successfully authenticated"; then
            print_success "SSH-Key für GitHub gefunden"
            USE_SSH=true
        else
            print_warning "SSH-Key für GitHub nicht gefunden oder nicht konfiguriert"
            print_info "Verwende HTTPS (Personal Access Token erforderlich)"
            USE_SSH=false
        fi
        
        # Repository klonen oder initialisieren
        if [ "$USE_SSH" = true ]; then
            print_info "Klone Repository via SSH..."
            if git clone "${GIT_REPO_SSH}" . 2>/dev/null; then
                print_success "Repository erfolgreich geklont"
            else
                print_error "Fehler beim Klonen via SSH"
                print_warning "Versuche HTTPS..."
                git clone "${GIT_REPO_HTTPS}" . || {
                    print_error "Fehler beim Klonen"
                    print_warning "Initialisiere neues Repository stattdessen..."
                    git init
                    git remote add origin "${GIT_REPO_SSH}"
                }
            fi
        else
            print_info "Klone Repository via HTTPS..."
            print_warning "Hinweis: Du wirst nach Username und Personal Access Token gefragt"
            if git clone "${GIT_REPO_HTTPS}" . 2>/dev/null; then
                print_success "Repository erfolgreich geklont"
            else
                print_error "Fehler beim Klonen"
                print_warning "Initialisiere neues Repository stattdessen..."
                git init
                git remote add origin "${GIT_REPO_HTTPS}"
                print_warning "WICHTIG: Du musst später beim Push dein Personal Access Token eingeben"
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
        changed=$(git diff --name-only HEAD -- "$MAIN_DIR/$plugin_dir" 2>/dev/null | wc -l)
        if [ "$changed" -gt 0 ]; then
            CHANGED_PLUGINS+=("$plugin_dir")
        fi
    done
    
    # Prüfe Root-Änderungen (außer Plugin-Verzeichnissen)
    root_pattern=""
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

    print_list "Erkannte Änderungen"
    for plugin_dir in "${CHANGED_PLUGINS[@]}"; do
        plugin_path=$(normalize_plugin_path "$plugin_dir")
        name=$(get_plugin_name "$plugin_path")
        print_list_item "•" "${name}"
    done
    [ "$CHANGED_ROOT" -gt 0 ] && print_list_item "•" "Root-Dateien (Skripte, README etc.)"
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
            name=$(get_plugin_name "$MAIN_DIR/$TARGET")
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
    print_warning "Keine Änderungen zum Committen"
    exit 0
fi

# Verwende Version des ersten Plugins als Hauptversion (erforderlich)
if [ ${#TO_PUSH_PLUGINS[@]} -eq 0 ]; then
    print_error "Keine Plugins zum Pushen gefunden"
    exit 1
fi

# Version aus package.xml lesen - diese MUSS immer vorhanden sein
# package.xml ist Teil des Plugin-Quellcodes und wird niemals gelöscht
PLUGIN_PATH=$(normalize_plugin_path "${TO_PUSH_PLUGINS[0]}")
VERSION=$(get_plugin_version "$PLUGIN_PATH")
if [ -z "$VERSION" ] || [ "$VERSION" = "" ] || [ "$VERSION" = "unknown" ]; then
    print_error "KRITISCHER FEHLER: Version konnte nicht aus ${TO_PUSH_PLUGINS[0]}/package.xml gelesen werden"
    print_error "Gelesene Version: '${VERSION}'"
    print_error "Pfad: $PLUGIN_PATH/package.xml"
    print_error ""
    print_error "Die package.xml MUSS immer vorhanden sein, da sie Teil des Plugin-Quellcodes ist."
    print_error "Wenn package.xml fehlt, ist das ein schwerwiegender Fehler im Workflow!"
    exit 1
fi
DATE=$(date +%Y-%m-%d)

# Show status
print_info "[1/6] Git Status:"
git status --short
echo ""

# Default commit message
if [ -z "$COMMIT_MESSAGE" ]; then
    PLUGIN_VERSIONS=""
    for plugin_dir in "${TO_PUSH_PLUGINS[@]}"; do
        plugin_path=$(normalize_plugin_path "$plugin_dir")
        version=$(get_plugin_version "$plugin_path")
        name=$(get_plugin_name "$plugin_path")
        PLUGIN_VERSIONS="${PLUGIN_VERSIONS}${name} v${version}, "
    done
    PLUGIN_VERSIONS="${PLUGIN_VERSIONS%, }"  # Entferne letztes Komma
    COMMIT_MESSAGE="Version ${VERSION} - ${TO_PUSH} (${PLUGIN_VERSIONS}) – ${DATE}"
fi

# Commit
print_info "[2/6] Committing changes..."

# WICHTIG: Bestimmte Verzeichnisse NICHT committen
# WICHTIG: Verzeichnisse bleiben erhalten, aber INHALT wird ignoriert
# Nur Core-DEV, Docs und GitHub-Repo ausschließen - Plugins werden hochgeladen!
EXCLUDE_DIRS=(
    "tools/woltlab-dev/public"  # Core DDEV (nur Ordnerstruktur behalten)
    "woltlab-docs"               # WoltLab Dokumentation (nur Ordnerstruktur behalten)
    "woltlab-github"             # WoltLab GitHub Repo (nur Ordnerstruktur behalten)
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
        print_warning "Inhalt ausgeschlossen: ${exclude_dir}/*"
        print_info "(Verzeichnis bleibt erhalten für README, .gitignore etc.)"
    fi
done

# Exclude files that shouldn't be committed
if [ -f "CLAUDE.md" ]; then
    git reset HEAD CLAUDE.md 2>/dev/null || true
fi
if ls CLAUDE*.md 1> /dev/null 2>&1; then
    git reset HEAD CLAUDE*.md 2>/dev/null || true
fi

# Finde und füge neuestes TAR-File hinzu (nur das aktuellste pro Plugin)
LATEST_TAR=""
if [ ${#TO_PUSH_PLUGINS[@]} -gt 0 ]; then
    for plugin_dir in "${TO_PUSH_PLUGINS[@]}"; do
        plugin_path=$(normalize_plugin_path "$plugin_dir")
        # Finde neuestes TAR-File für dieses Plugin (plattformkompatibel)
        if command_exists stat; then
            # GNU/Linux: Verwende find mit -printf (sortiert nach Modifikationszeit)
            PLUGIN_TAR=$(find "${plugin_path}" -maxdepth 1 -name "*.tar.gz" -type f -printf '%T@ %p\n' 2>/dev/null | sort -rn | head -n 1 | cut -d' ' -f2-)
        else
            # Fallback: Verwende ls -t (sortiert nach Modifikationszeit)
            PLUGIN_TAR=$(ls -t "${plugin_path}"/*.tar.gz 2>/dev/null | head -n 1)
        fi
        if [ -n "$PLUGIN_TAR" ] && [ -f "$PLUGIN_TAR" ]; then
            git add -f "$PLUGIN_TAR"  # -f um .gitignore zu überschreiben
            # Nur das neueste TAR-File behalten (basierend auf Modifikationszeit)
            if [ -z "$LATEST_TAR" ] || [ "$PLUGIN_TAR" -nt "$LATEST_TAR" ]; then
                LATEST_TAR="$PLUGIN_TAR"
            fi
            print_success "TAR-File hinzugefügt: $(basename "$PLUGIN_TAR")"
        fi
    done
    if [ -n "$LATEST_TAR" ]; then
        print_info "Neuestes TAR-File für Release: $(basename "$LATEST_TAR")"
    fi
fi

# Prüfe ob noch etwas zum Committen übrig ist
if [ -z "$(git status --porcelain)" ]; then
    print_warning "Keine Änderungen zum Committen (alle ausgeschlossen)"
    exit 0
fi

git commit -m "${COMMIT_MESSAGE}"
print_success "Committed: ${COMMIT_MESSAGE}"
echo ""

# Push
print_info "[3/6] Pushing to remote..."

# Prüfe ob SSH oder HTTPS verwendet wird
CURRENT_REMOTE=$(git remote get-url origin 2>/dev/null || echo "")
if [[ "$CURRENT_REMOTE" == *"git@github.com"* ]]; then
    print_info "Verwende SSH-Authentifizierung"
    if git push origin main 2>/dev/null || git push origin master 2>/dev/null || git push; then
        print_success "Pushed to remote"
        echo ""
    else
        print_error "Fehler beim Push via SSH"
        print_warning "Mögliche Ursachen:"
        print_list_item "1." "SSH-Key nicht bei GitHub hinterlegt"
        print_list_item "2." "SSH-Key nicht zum SSH-Agent hinzugefügt"
        print_list_item "3." "Branch-Name stimmt nicht (main/master)"
        exit 1
    fi
else
    print_info "Verwende HTTPS-Authentifizierung"
    print_warning "Hinweis: Du wirst nach Username und Personal Access Token gefragt"
    print_info "Username: Dein GitHub-Benutzername"
    print_info "Password: Dein Personal Access Token (NICHT dein Passwort!)"
    echo ""
    if git push origin main 2>/dev/null || git push origin master 2>/dev/null || git push; then
        print_success "Pushed to remote"
        echo ""
    else
        print_error "Fehler beim Push via HTTPS"
        print_warning "Mögliche Ursachen:"
        print_list_item "1." "Personal Access Token fehlt oder ist ungültig"
        print_list_item "2." "Token hat nicht die erforderlichen Berechtigungen"
        print_list_item "3." "Branch-Name stimmt nicht (main/master)"
        exit 1
    fi
fi

# Create release tag
TAG_NAME="v${VERSION}"
print_info "[4/6] Creating release tag..."

# Check if tag already exists
if git rev-parse "${TAG_NAME}" >/dev/null 2>&1; then
    print_warning "Tag ${TAG_NAME} existiert bereits"
    print_warning "Überspringe Tag-Erstellung"
else
    # Create annotated tag
    PLUGIN_INFO=""
    for plugin_dir in "${TO_PUSH_PLUGINS[@]}"; do
        plugin_path=$(normalize_plugin_path "$plugin_dir")
        version=$(get_plugin_version "$plugin_path")
        name=$(get_plugin_name "$plugin_path")
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
                plugin_path=$(normalize_plugin_path "$plugin_dir")
                version=$(get_plugin_version "$plugin_path")
                name=$(get_plugin_name "$plugin_path")
                RELEASE_NOTES="${RELEASE_NOTES}**${name}:** v${version}"$'\n'
            done
        fi
    else
        RELEASE_NOTES="Version ${VERSION} - ${DATE}"$'\n\n'
        for plugin_dir in "${TO_PUSH_PLUGINS[@]}"; do
            plugin_path=$(normalize_plugin_path "$plugin_dir")
            version=$(get_plugin_version "$plugin_path")
            name=$(get_plugin_name "$plugin_path")
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
    
    # Upload neuestes TAR-File to release (nur das aktuellste)
    if [ -n "$LATEST_TAR" ] && [ -f "$LATEST_TAR" ]; then
        echo -e "${BLUE}  Lade neuestes TAR-File hoch...${NC}"
        if gh release upload "${TAG_NAME}" "$LATEST_TAR" --clobber 2>/dev/null; then
            echo -e "${GREEN}✓ TAR-File zum Release hochgeladen: $(basename "$LATEST_TAR")${NC}"
        else
            echo -e "${YELLOW}⚠ TAR-File konnte nicht hochgeladen werden: $(basename "$LATEST_TAR")${NC}"
        fi
    fi
else
    echo -e "${YELLOW}⚠ GitHub CLI (gh) nicht gefunden${NC}"
    echo -e "${YELLOW}   Installiere mit: sudo pacman -S github-cli (oder apt install gh)${NC}"
    echo -e "${YELLOW}   Oder erstelle Release manuell auf GitHub${NC}"
fi

echo ""
print_section "Git Push & Release abgeschlossen" "Hauptmenü" "Git"
print_success "Git Push & Release abgeschlossen!"
print_info "Version: ${VERSION}"
for plugin_dir in "${TO_PUSH_PLUGINS[@]}"; do
    plugin_path=$(normalize_plugin_path "$plugin_dir")
    version=$(get_plugin_version "$plugin_path")
    name=$(get_plugin_name "$plugin_path")
    print_info "${name}: v${version}"
done
print_info "Tag: ${TAG_NAME}"
print_info "Repository: ${GIT_REPO_DISPLAY}"
echo ""
