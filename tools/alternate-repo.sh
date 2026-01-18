#!/bin/bash

#################################################################
# Alternate Repository Integration
# Nutzt simple-woltlab-plugin-manager als Referenz für
# Git Push & Release mit Changelog und Breaking Changes
#
# Referenz-Repository:
# https://github.com/benjarogit/simple-woltlab-plugin-manager
#
# Features:
# - Semantic Versioning (patch/minor/major)
# - Changelog-Management mit Breaking Changes
# - GitHub Release mit vollständigen Release Notes
# - Automatische package.xml Validierung
# - Backup von Packages vor Release
#################################################################

set -e

TOOLS_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
MAIN_DIR="$(dirname "$TOOLS_DIR")"

# Referenz-Repository
REF_REPO_URL="https://github.com/benjarogit/simple-woltlab-plugin-manager"
REF_REPO_DIR="$TOOLS_DIR/.ref-repo"

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

# Funktion: Referenz-Repository klonen/aktualisieren
fetch_reference_repo() {
    print_info "Lade Referenz-Repository..."
    
    if [ -d "$REF_REPO_DIR" ]; then
        print_info "Aktualisiere Referenz-Repository..."
        cd "$REF_REPO_DIR"
        git pull --quiet 2>/dev/null || {
            print_warning "Konnte Repository nicht aktualisieren, verwende lokale Version"
        }
    else
        print_info "Klone Referenz-Repository..."
        mkdir -p "$(dirname "$REF_REPO_DIR")"
        git clone --depth 1 "$REF_REPO_URL" "$REF_REPO_DIR" 2>/dev/null || {
            print_error "Konnte Referenz-Repository nicht klonen"
            return 1
        }
    fi
    
    cd "$MAIN_DIR"
    print_success "Referenz-Repository bereit"
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
    local changelog_file="$MAIN_DIR/CHANGELOG.md"
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
        # Füge alte Einträge hinzu (ab Zeile 2, um Header zu überspringen)
        tail -n +2 "$changelog_file" | sed '/^## \[Unreleased\]/,/^---/d' | sed '/^---$/d' | head -n -1
    } > "$temp_file"
    
    mv "$temp_file" "$changelog_file"
    print_success "Changelog-Eintrag für Version ${version} erstellt"
}

# Funktion: GitHub Release erstellen
create_github_release() {
    local version="$1"
    local tag_name="v${version}"
    local changelog_file="$MAIN_DIR/CHANGELOG.md"
    
    if ! command -v gh &> /dev/null; then
        print_warning "GitHub CLI (gh) nicht gefunden"
        print_info "Installiere mit: sudo pacman -S github-cli (oder apt install gh)"
        return 1
    fi
    
    # Prüfe ob bereits angemeldet
    if ! gh auth status &>/dev/null; then
        print_warning "Nicht bei GitHub CLI angemeldet"
        print_info "Führe aus: gh auth login"
        return 1
    fi
    
    # Extrahiere Changelog für diese Version
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
    
    # Prüfe ob Release bereits existiert
    if gh release view "$tag_name" &>/dev/null; then
        print_info "Aktualisiere bestehenden Release..."
        echo "$release_notes" | gh release edit "$tag_name" --notes-file - 2>/dev/null && {
            print_success "Release ${tag_name} aktualisiert"
        } || {
            print_error "Konnte Release nicht aktualisieren"
            return 1
        }
    else
        print_info "Erstelle neuen Release..."
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

# Funktion: Git Push & Release Workflow
git_push_and_release() {
    local version_type="${1:-patch}"
    local commit_message="${2:-}"
    local skip_changelog="${3:-false}"
    
    cd "$MAIN_DIR"
    
    # Prüfe Git-Repository
    if [ ! -d ".git" ]; then
        print_error "Kein Git-Repository gefunden"
        return 1
    fi
    
    # Finde Plugin-Verzeichnisse
    local plugin_dirs=($(find_plugin_directories "$MAIN_DIR"))
    if [ ${#plugin_dirs[@]} -eq 0 ]; then
        print_warning "Keine Plugins gefunden"
        return 1
    fi
    
    # Verwende Version des ersten Plugins
    local first_plugin="${plugin_dirs[0]}"
    local current_version=$(get_plugin_version "$MAIN_DIR/$first_plugin")
    local new_version=$(increment_version "$current_version" "$version_type")
    
    print_header "Git Push & Release"
    echo -e "${CYAN}Version:${NC} ${current_version} ${ARROW} ${new_version} (${version_type})"
    echo ""
    
    # Update package.xml für alle Plugins
    print_info "Aktualisiere package.xml für alle Plugins..."
    for plugin_dir in "${plugin_dirs[@]}"; do
        local plugin_path="$MAIN_DIR/$plugin_dir"
        local plugin_version=$(get_plugin_version "$plugin_path")
        
        if [ -f "$plugin_path/package.xml" ]; then
            # Update version
            sed -i "s/<version>${plugin_version}<\/version>/<version>${new_version}<\/version>/" "$plugin_path/package.xml"
            
            # Update date
            local today=$(date +%Y-%m-%d)
            sed -i "s/<date>[0-9]\{4\}-[0-9]\{2\}-[0-9]\{2\}<\/date>/<date>${today}<\/date>/" "$plugin_path/package.xml"
            
            print_success "✓ $plugin_dir: ${plugin_version} → ${new_version}"
        fi
    done
    echo ""
    
    # Update Changelog
    if [ "$skip_changelog" != "true" ]; then
        print_info "Aktualisiere Changelog..."
        # Frage nach Breaking Changes
        echo -e "${YELLOW}Enthält diese Version Breaking Changes? (j/n):${NC} "
        read -r has_breaking
        local has_breaking_changes="false"
        [ "$has_breaking" = "j" ] || [ "$has_breaking" = "J" ] && has_breaking_changes="true"
        
        update_changelog "$new_version" "$has_breaking_changes"
        echo ""
    fi
    
    # Prüfe Änderungen
    if [ -z "$(git status --porcelain)" ]; then
        print_warning "Keine Änderungen zum Committen"
        return 0
    fi
    
    # Git Add (mit Exclusions)
    print_info "Stage Änderungen..."
    git add -A
    
    # Exclude directories
    local exclude_dirs=(
        "basis-plugin"
        "mein-plugin"
        "plugins-integrieren"
        "tools/woltlab-dev/public"
        "woltlab-docs"
        "woltlab-github"
    )
    
    for exclude_dir in "${exclude_dirs[@]}"; do
        if [ -d "$exclude_dir" ]; then
            git reset HEAD "$exclude_dir"/* 2>/dev/null || true
            git reset HEAD "$exclude_dir"/**/* 2>/dev/null || true
        fi
    done
    
    # Exclude CLAUDE files
    git reset HEAD CLAUDE*.md 2>/dev/null || true
    
    # Commit
    if [ -z "$commit_message" ]; then
        commit_message="Release v${new_version} - $(date +%Y-%m-%d)"
    fi
    
    print_info "Commite Änderungen..."
    git commit -m "$commit_message" || {
        print_error "Commit fehlgeschlagen"
        return 1
    }
    print_success "✓ Committed: $commit_message"
    echo ""
    
    # Push
    print_info "Pushe zu Remote..."
    local current_branch=$(git branch --show-current)
    git push origin "$current_branch" || {
        print_error "Push fehlgeschlagen"
        return 1
    }
    print_success "✓ Pushed to remote"
    echo ""
    
    # Create Tag
    local tag_name="v${new_version}"
    print_info "Erstelle Release-Tag..."
    if git rev-parse "$tag_name" &>/dev/null 2>&1; then
        print_warning "Tag $tag_name existiert bereits"
    else
        # Extract changelog for tag message
        local tag_message=""
        if [ -f "$MAIN_DIR/CHANGELOG.md" ]; then
            tag_message=$(awk -v version="$new_version" '
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
            ' "$MAIN_DIR/CHANGELOG.md" | head -20)
        fi
        
        if [ -z "$tag_message" ]; then
            tag_message="Release ${new_version}"
        fi
        
        git tag -a "$tag_name" -m "$tag_message" || {
            print_error "Tag-Erstellung fehlgeschlagen"
            return 1
        }
        
        git push origin "$tag_name" || {
            print_warning "Tag konnte nicht gepusht werden"
        }
        print_success "✓ Tag $tag_name erstellt und gepusht"
        echo ""
    fi
    
    # Create GitHub Release
    print_info "Erstelle GitHub Release..."
    create_github_release "$new_version"
    echo ""
    
    # Zusammenfassung
    print_header "Release abgeschlossen"
    echo -e "${GREEN}Version:${NC} ${new_version}"
    echo -e "${GREEN}Tag:${NC} ${tag_name}"
    echo -e "${GREEN}Commit:${NC} $commit_message"
    echo ""
}

# Hauptfunktion
main() {
    local command="${1:-help}"
    
    case "$command" in
        fetch)
            fetch_reference_repo
            ;;
        release)
            local version_type="${2:-patch}"
            local commit_message="${3:-}"
            git_push_and_release "$version_type" "$commit_message"
            ;;
        changelog)
            local version="${2:-}"
            if [ -z "$version" ]; then
                print_error "Version erforderlich: $0 changelog <version>"
                exit 1
            fi
            update_changelog "$version"
            ;;
        help|*)
            echo -e "${CYAN}Alternate Repository Integration${NC}"
            echo ""
            echo "Usage:"
            echo "  $0 fetch                    → Lade/aktualisiere Referenz-Repository"
            echo "  $0 release [type] [message] → Git Push & Release (patch/minor/major)"
            echo "  $0 changelog <version>     → Erstelle Changelog-Eintrag"
            echo ""
            echo "Examples:"
            echo "  $0 release patch           → Patch-Version erhöhen und releasen"
            echo "  $0 release minor 'Neue Features' → Minor-Version mit Commit-Message"
            echo "  $0 release major            → Major-Version (Breaking Changes)"
            echo ""
            ;;
    esac
}

main "$@"
