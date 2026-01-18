#!/bin/bash

#################################################################
# Push zum alternativen Repository (Plugin-Manager Template)
# 
# Repository: https://github.com/benjarogit/simple-woltlab-plugin-manager
# 
# Diese Datei ist NUR für Änderungen am Plugin-Manager selbst,
# die auf das alternative Repository gepusht werden sollen.
# 
# Usage:
#   ./tools/push-to-alternate-repo.sh patch    → Patch-Version + Release
#   ./tools/push-to-alternate-repo.sh minor    → Minor-Version + Release
#   ./tools/push-to-alternate-repo.sh major    → Major-Version + Release
#   ./tools/push-to-alternate-repo.sh "Nachricht" → mit custom Commit-Message
#
# Features:
# - Semantic Versioning (patch/minor/major)
# - Changelog-Management mit Breaking Changes (Keep a Changelog Format)
# - GitHub Release mit vollständigen Release Notes
# - Push zu alternativem Repository
#################################################################

set -e

TOOLS_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
MAIN_DIR="$(dirname "$TOOLS_DIR")"

# Alternatives Repository
ALT_REPO_URL="https://github.com/benjarogit/simple-woltlab-plugin-manager"
ALT_REPO_DIR="$MAIN_DIR/.alt-repo"

# Lade gemeinsame Funktionen
if [ -f "$TOOLS_DIR/common.sh" ]; then
    source "$TOOLS_DIR/common.sh"
else
    RED='\033[0;31m'
    GREEN='\033[0;32m'
    YELLOW='\033[1;33m'
    BLUE='\033[0;34m'
    CYAN='\033[0;36m'
    NC='\033[0m'
fi

# Parameter parsen
VERSION_TYPE="${1:-}"
COMMIT_MESSAGE="${2:-}"

# Funktion: Aktuelle Version aus Git Tags ermitteln
get_current_version() {
    local repo_dir="$1"
    cd "$repo_dir"
    local latest_tag=$(git describe --tags --abbrev=0 2>/dev/null || echo "")
    if [ -n "$latest_tag" ]; then
        echo "${latest_tag#v}"
    else
        echo "0.0.0"
    fi
}

# Funktion: Semantic Versioning - Version erhöhen
increment_version() {
    local version="$1"
    local type="${2:-patch}"
    
    IFS='.' read -ra VERSION_PARTS <<< "$version"
    local major=${VERSION_PARTS[0]:-0}
    local minor=${VERSION_PARTS[1]:-0}
    local patch=${VERSION_PARTS[2]:-0}
    
    case "$type" in
        major)
            major=$((major + 1))
            minor=0
            patch=0
            ;;
        minor)
            minor=$((minor + 1))
            patch=0
            ;;
        patch|*)
            patch=$((patch + 1))
            ;;
    esac
    
    echo "${major}.${minor}.${patch}"
}

# Funktion: Changelog-Eintrag erstellen/aktualisieren
update_changelog() {
    local version="$1"
    local changelog_file="$ALT_REPO_DIR/CHANGELOG.md"
    local date=$(date +%Y-%m-%d)
    local has_breaking_changes="${2:-false}"
    
    # Prüfe ob Changelog existiert
    if [ ! -f "$changelog_file" ]; then
        cat > "$changelog_file" <<EOF
# Changelog

Alle wichtigen Änderungen an diesem Projekt werden in dieser Datei dokumentiert.

Das Format basiert auf [Keep a Changelog](https://keepachangelog.com/de/1.0.0/),
und dieses Projekt folgt [Semantic Versioning](https://semver.org/lang/de/).

## [Unreleased]

### Added
- Initiale Version

---

EOF
    fi
    
    # Prüfe ob Version bereits existiert
    if grep -q "^## \[${version}\]" "$changelog_file"; then
        print_warning "Changelog-Eintrag für Version ${version} existiert bereits"
        return 0
    fi
    
    # Erstelle neuen Eintrag
    local temp_file=$(mktemp)
    {
        echo "# Changelog"
        echo ""
        echo "Alle wichtigen Änderungen an diesem Projekt werden in dieser Datei dokumentiert."
        echo ""
        echo "Das Format basiert auf [Keep a Changelog](https://keepachangelog.com/de/1.0.0/),"
        echo "und dieses Projekt folgt [Semantic Versioning](https://semver.org/lang/de/)."
        echo ""
        echo "## [Unreleased]"
        echo ""
        echo "---"
        echo ""
        echo "## [${version}] - ${date}"
        echo ""
        if [ "$has_breaking_changes" = "true" ]; then
            echo "### ⚠️ BREAKING CHANGES"
            echo ""
            echo "- **TODO:** Breaking Changes hier dokumentieren"
            echo ""
        fi
        echo "### Added"
        echo ""
        echo "- **TODO:** Neue Features hier dokumentieren"
        echo ""
        echo "### Changed"
        echo ""
        echo "- **TODO:** Änderungen hier dokumentieren"
        echo ""
        echo "### Fixed"
        echo ""
        echo "- **TODO:** Bugfixes hier dokumentieren"
        echo ""
        echo "### Deprecated"
        echo ""
        echo "- **TODO:** Veraltete Features hier dokumentieren"
        echo ""
        echo "### Removed"
        echo ""
        echo "- **TODO:** Entfernte Features hier dokumentieren"
        echo ""
        echo "### Security"
        echo ""
        echo "- **TODO:** Sicherheits-Updates hier dokumentieren"
        echo ""
        echo "---"
        echo ""
        tail -n +2 "$changelog_file" | sed '/^## \[Unreleased\]/,/^---/d' | sed '/^---$/d' | head -n -1
    } > "$temp_file"
    
    mv "$temp_file" "$changelog_file"
    print_success "Changelog-Eintrag für Version ${version} erstellt"
}

# Funktion: GitHub Release erstellen
create_github_release() {
    local version="$1"
    local tag_name="v${version}"
    local changelog_file="$ALT_REPO_DIR/CHANGELOG.md"
    
    if ! command -v gh &> /dev/null; then
        print_warning "GitHub CLI (gh) nicht gefunden"
        return 1
    fi
    
    if ! gh auth status &>/dev/null; then
        print_warning "Nicht bei GitHub CLI angemeldet"
        return 1
    fi
    
    # Extrahiere Changelog
    local release_notes=""
    if [ -f "$changelog_file" ]; then
        release_notes=$(awk -v version="$version" '
            /^## \[/ {
                if (found) exit
                if ($0 ~ "\\[" version "\\]") {
                    found = 1
                    next
                }
            }
            found {
                if (/^## \[/) exit
                if (/^---$/) exit
                print
            }
        ' "$changelog_file")
    fi
    
    if [ -z "$release_notes" ] || [ -z "$(echo "$release_notes" | tr -d '[:space:]')" ]; then
        release_notes="Release ${version}"
    fi
    
    cd "$ALT_REPO_DIR"
    
    if gh release view "$tag_name" &>/dev/null; then
        echo "$release_notes" | gh release edit "$tag_name" --notes-file - 2>/dev/null && {
            print_success "Release ${tag_name} aktualisiert"
        } || {
            print_error "Konnte Release nicht aktualisieren"
            return 1
        }
    else
        echo "$release_notes" | gh release create "$tag_name" \
            --title "Version ${version}" \
            --notes-file - 2>/dev/null && {
            print_success "Release ${tag_name} erstellt"
        } || {
            print_error "Konnte Release nicht erstellen"
            return 1
        }
    fi
}

# Hauptfunktion
print_header "Push zum alternativen Repository"

# Prüfe ob alternatives Repository existiert
if [ ! -d "$ALT_REPO_DIR" ]; then
    print_info "Klone alternatives Repository..."
    mkdir -p "$(dirname "$ALT_REPO_DIR")"
    git clone "$ALT_REPO_URL" "$ALT_REPO_DIR" || {
        print_error "Konnte alternatives Repository nicht klonen"
        exit 1
    }
fi

cd "$ALT_REPO_DIR"

# Update Repository
print_info "Aktualisiere alternatives Repository..."
git pull origin main 2>/dev/null || git pull origin master 2>/dev/null || {
    print_warning "Konnte Repository nicht aktualisieren"
}

# ============================================================
# Kopiere Workspace-Inhalte zum alternativen Repository
# ============================================================

print_info "Bereinige alte Dateien und Verzeichnisse..."

# Liste der zu löschenden alten Dateien/Verzeichnisse
OLD_FILES=(
    "scripts"
    "templates"
    "example-plugin"
    "install.sh"
    "README.md"
    "README_DE.md"
    "README_EN.md"
    "LICENSE"
    "CONTRIBUTING.md"
)

# Lösche alte Dateien/Verzeichnisse (außer .git und neuen Verzeichnissen)
for item in "${OLD_FILES[@]}"; do
    if [ -e "$ALT_REPO_DIR/$item" ]; then
        rm -rf "$ALT_REPO_DIR/$item" 2>/dev/null || true
        print_info "Entfernt: $item"
    fi
done

# Lösche alte docs/ (wird durch tools/docs/ ersetzt)
if [ -d "$ALT_REPO_DIR/docs" ] && [ ! -d "$ALT_REPO_DIR/tools/docs" ]; then
    rm -rf "$ALT_REPO_DIR/docs" 2>/dev/null || true
    print_info "Entfernt: docs/ (wird durch tools/docs/ ersetzt)"
fi

print_success "✓ Alte Dateien entfernt"
echo ""

print_info "Kopiere Workspace-Inhalte..."

# 1. Kopiere Tools komplett
print_info "Kopiere tools/ Verzeichnis..."
if command -v rsync &> /dev/null; then
    rsync -av --delete \
        --exclude='.git' \
        --exclude='woltlab-dev/public' \
        "$TOOLS_DIR/" "$ALT_REPO_DIR/tools/" || {
        print_error "Fehler beim Kopieren von tools/"
        exit 1
    }
else
    # Fallback: cp -r
    rm -rf "$ALT_REPO_DIR/tools" 2>/dev/null || true
    cp -r "$TOOLS_DIR" "$ALT_REPO_DIR/" || {
        print_error "Fehler beim Kopieren von tools/"
        exit 1
    }
    # Entferne woltlab-dev/public falls vorhanden (wird später als leeres Verzeichnis erstellt)
    rm -rf "$ALT_REPO_DIR/tools/woltlab-dev/public" 2>/dev/null || true
fi
print_success "✓ tools/ kopiert"

# 2. Kopiere Root-Dateien
print_info "Kopiere Root-Dateien..."
if [ -f "$MAIN_DIR/tools.sh" ]; then
    cp "$MAIN_DIR/tools.sh" "$ALT_REPO_DIR/" || {
        print_error "Fehler beim Kopieren von tools.sh"
        exit 1
    }
    print_success "✓ tools.sh kopiert"
fi

if [ -f "$MAIN_DIR/.gitignore" ]; then
    cp "$MAIN_DIR/.gitignore" "$ALT_REPO_DIR/" || {
        print_error "Fehler beim Kopieren von .gitignore"
        exit 1
    }
    print_success "✓ .gitignore kopiert"
fi

if [ -f "$MAIN_DIR/woltlab-development.code-workspace" ]; then
    cp "$MAIN_DIR/woltlab-development.code-workspace" "$ALT_REPO_DIR/" 2>/dev/null || {
        print_warning "Konnte woltlab-development.code-workspace nicht kopieren (optional)"
    }
    print_success "✓ woltlab-development.code-workspace kopiert"
fi

# 3. Erstelle leere Verzeichnisse mit .gitkeep
print_info "Erstelle leere Verzeichnisse..."

# Plugin-Verzeichnisse
mkdir -p "$ALT_REPO_DIR/basis-plugin"
mkdir -p "$ALT_REPO_DIR/mein-plugin"
mkdir -p "$ALT_REPO_DIR/plugins-integrieren"

# Lösche alle Inhalte in Plugin-Verzeichnissen (falls vorhanden)
find "$ALT_REPO_DIR/basis-plugin" -mindepth 1 ! -name '.gitkeep' -delete 2>/dev/null || true
find "$ALT_REPO_DIR/mein-plugin" -mindepth 1 ! -name '.gitkeep' -delete 2>/dev/null || true
find "$ALT_REPO_DIR/plugins-integrieren" -mindepth 1 ! -name '.gitkeep' -delete 2>/dev/null || true

# Erstelle .gitkeep
touch "$ALT_REPO_DIR/basis-plugin/.gitkeep"
touch "$ALT_REPO_DIR/mein-plugin/.gitkeep"
touch "$ALT_REPO_DIR/plugins-integrieren/.gitkeep"
print_success "✓ Plugin-Verzeichnisse erstellt (leer)"

# WoltLab-Verzeichnisse
mkdir -p "$ALT_REPO_DIR/woltlab-docs"
mkdir -p "$ALT_REPO_DIR/woltlab-github"

# Lösche alle Inhalte in WoltLab-Verzeichnissen (falls vorhanden)
find "$ALT_REPO_DIR/woltlab-docs" -mindepth 1 ! -name '.gitkeep' -delete 2>/dev/null || true
find "$ALT_REPO_DIR/woltlab-github" -mindepth 1 ! -name '.gitkeep' -delete 2>/dev/null || true

# Erstelle .gitkeep
touch "$ALT_REPO_DIR/woltlab-docs/.gitkeep"
touch "$ALT_REPO_DIR/woltlab-github/.gitkeep"
print_success "✓ WoltLab-Verzeichnisse erstellt (leer)"

# DDEV Public (nur wenn woltlab-dev/ existiert)
if [ -d "$ALT_REPO_DIR/tools/woltlab-dev" ]; then
    mkdir -p "$ALT_REPO_DIR/tools/woltlab-dev/public"
    
    # Lösche alle Inhalte in DDEV Public (falls vorhanden)
    find "$ALT_REPO_DIR/tools/woltlab-dev/public" -mindepth 1 ! -name '.gitkeep' -delete 2>/dev/null || true
    
    # Erstelle .gitkeep
    touch "$ALT_REPO_DIR/tools/woltlab-dev/public/.gitkeep"
    print_success "✓ tools/woltlab-dev/public erstellt (leer)"
else
    print_warning "tools/woltlab-dev/ existiert nicht, überspringe public/"
fi

echo ""

# Prüfe ob Semantic Versioning angegeben
if [[ "$VERSION_TYPE" =~ ^(patch|minor|major)$ ]]; then
    CURRENT_VERSION=$(get_current_version "$ALT_REPO_DIR")
    NEW_VERSION=$(increment_version "$CURRENT_VERSION" "$VERSION_TYPE")
    
    echo -e "${CYAN}Version:${NC} ${CURRENT_VERSION} ${ARROW} ${NEW_VERSION} (${VERSION_TYPE})"
    echo ""
    
    # Update Changelog
    print_info "Aktualisiere Changelog..."
    has_breaking_changes="false"
    if [ "$VERSION_TYPE" = "major" ]; then
        echo -e "${YELLOW}Enthält diese Version Breaking Changes? (j/n):${NC} "
        read -r has_breaking
        [ "$has_breaking" = "j" ] || [ "$has_breaking" = "J" ] && has_breaking_changes="true"
    fi
    
    update_changelog "$NEW_VERSION" "$has_breaking_changes"
    echo ""
    
    VERSION="$NEW_VERSION"
else
    if [ -z "$COMMIT_MESSAGE" ] && [ -n "$VERSION_TYPE" ]; then
        COMMIT_MESSAGE="$VERSION_TYPE"
        VERSION_TYPE=""
    fi
    VERSION=$(get_current_version "$ALT_REPO_DIR")
fi

# Prüfe Änderungen
if [ -z "$(git status --porcelain)" ]; then
    if [ -n "$VERSION_TYPE" ]; then
        print_warning "Keine Änderungen zum Committen"
        echo -e "${YELLOW}Möchtest du trotzdem ein Release erstellen? (j/n):${NC} "
        read -r continue_release
        [ "$continue_release" != "j" ] && [ "$continue_release" != "J" ] && exit 0
    else
        print_warning "Keine Änderungen zum Committen"
        exit 0
    fi
fi

# Git Status
echo -e "${YELLOW}[1/5] Git Status:${NC}"
git status --short
echo ""

# Git Add
print_info "Stage Änderungen..."
git add -A

# Commit
if [ -z "$COMMIT_MESSAGE" ]; then
    if [ -n "$VERSION_TYPE" ]; then
        COMMIT_MESSAGE="Release v${VERSION} (${VERSION_TYPE}) – $(date +%Y-%m-%d)"
    else
        COMMIT_MESSAGE="Update – $(date +%Y-%m-%d)"
    fi
fi

echo -e "${YELLOW}[2/5] Committing changes...${NC}"
git commit -m "$COMMIT_MESSAGE" || {
    print_error "Commit fehlgeschlagen"
    exit 1
}
print_success "✓ Committed: $COMMIT_MESSAGE"
echo ""

# Push
echo -e "${YELLOW}[3/5] Pushing to remote...${NC}"
CURRENT_BRANCH=$(git branch --show-current)
git push origin "$CURRENT_BRANCH" || {
    print_error "Push fehlgeschlagen"
    exit 1
}
print_success "✓ Pushed to remote"
echo ""

# Create Tag (nur wenn Version erhöht wurde)
if [ -n "$VERSION_TYPE" ]; then
    TAG_NAME="v${VERSION}"
    echo -e "${YELLOW}[4/5] Creating release tag...${NC}"
    
    if git rev-parse "$TAG_NAME" &>/dev/null 2>&1; then
        print_warning "Tag $TAG_NAME existiert bereits"
    else
        tag_message=""
        if [ -f "$ALT_REPO_DIR/CHANGELOG.md" ]; then
            tag_message=$(awk -v version="$VERSION" '
                /^## \[/ {
                    if (found) exit
                    if ($0 ~ "\\[" version "\\]") {
                        found = 1
                        next
                    }
                }
                found {
                    if (/^## \[/) exit
                    if (/^---$/) exit
                    print
                }
            ' "$ALT_REPO_DIR/CHANGELOG.md" | head -20)
        fi
        
        [ -z "$tag_message" ] && tag_message="Release ${VERSION}"
        
        git tag -a "$TAG_NAME" -m "$tag_message" || {
            print_error "Tag-Erstellung fehlgeschlagen"
            exit 1
        }
        
        git push origin "$TAG_NAME" || {
            print_warning "Tag konnte nicht gepusht werden"
        }
        print_success "✓ Tag $TAG_NAME erstellt und gepusht"
    fi
    echo ""
    
    # Create GitHub Release
    echo -e "${YELLOW}[5/5] Creating GitHub Release...${NC}"
    create_github_release "$VERSION"
    echo ""
    
    print_header "Release abgeschlossen"
    echo -e "${GREEN}Version:${NC} ${VERSION}"
    echo -e "${GREEN}Tag:${NC} ${TAG_NAME}"
    echo -e "${GREEN}Repository:${NC} ${ALT_REPO_URL}"
else
    print_header "Push abgeschlossen"
    echo -e "${GREEN}Commit:${NC} $COMMIT_MESSAGE"
    echo -e "${GREEN}Repository:${NC} ${ALT_REPO_URL}"
fi
echo ""
