#!/usr/bin/env bash

#################################################################
# WoltLab-Referenzen auf Version X synchronisieren
# Pfad: tools/update-woltlab-version.sh [OPTIONEN] [VERSION]
#
# Aktualisiert:
#   - woltlab-core   (optional: Download ZIP)
#   - woltlab-github (Git: Branch VERSION, z. B. origin/6.2)
#   - woltlab-docs   (Git: Branch VERSION)
#   - woltlab-d-ts   (Git: Branch VERSION)
#
# Beispiele:
#   ./update-woltlab-version.sh 6.2
#   ./update-woltlab-version.sh --refs-only 6.2
#   ./sync-woltlab-references.sh
#
# Automatisierung (Cron, wöchentlich Sonntag 04:00):
#   0 4 * * 0 /pfad/zum/plugin-manager/tools/sync-woltlab-references.sh >> ~/.cache/woltlab-refs-sync.log 2>&1
#################################################################

set -e

readonly TOOLS_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
readonly MAIN_DIR="$(dirname "$TOOLS_DIR")"

if [ -f "$TOOLS_DIR/common.sh" ]; then
    # shellcheck source=common.sh
    source "$TOOLS_DIR/common.sh"
else
    echo "common.sh nicht gefunden."
    exit 1
fi

SKIP_CORE=0
VERSION=""

while [ $# -gt 0 ]; do
    case "$1" in
        --refs-only | --no-core)
            SKIP_CORE=1
            shift
            ;;
        -h | --help)
            echo "Verwendung: $0 [--refs-only] [VERSION]"
            echo ""
            echo "  --refs-only   Nur Git-Spiegel (docs, github, d.ts), kein woltlab-core-Download"
            echo "  VERSION       z. B. 6.2 (Standard bei sync-woltlab-references.sh: 6.2)"
            exit 0
            ;;
        *)
            VERSION="$1"
            shift
            ;;
    esac
done

if [ -z "$VERSION" ]; then
    if [ -t 0 ]; then
        echo ""
        echo -e "${CYAN}WoltLab-Referenzen synchronisieren${NC}"
        echo ""
        echo "Verwendung: $0 [--refs-only] <VERSION>"
        echo "Beispiel:   $0 6.2"
        echo ""
        echo "Quellen:"
        echo "  - https://docs.woltlab.com/<VERSION>/"
        echo "  - https://github.com/WoltLab/WCF/tree/<VERSION>"
        echo "  - https://github.com/WoltLab/d.ts/tree/<VERSION>"
        echo ""
        read -rp "Version (z. B. 6.2): " VERSION
        VERSION=$(echo "$VERSION" | tr -d ' ')
    else
        VERSION="6.2"
    fi
fi

if [ -z "$VERSION" ]; then
    print_error "Keine Version angegeben."
    exit 1
fi

BRANCH="$VERSION"

sync_git_mirror() {
    local name="$1"
    local dir="$2"
    local branch="$3"

    if [ ! -d "$dir/.git" ]; then
        print_warning "$name: kein Git-Repository – übersprungen."
        return 0
    fi

    print_info "$name: fetch origin/$branch …"
    if ! git -C "$dir" fetch origin "refs/heads/${branch}:refs/remotes/origin/${branch}" --tags --prune 2>/dev/null; then
        print_warning "$name: fetch fehlgeschlagen."
        return 1
    fi

    if ! git -C "$dir" rev-parse "origin/${branch}" >/dev/null 2>&1; then
        print_warning "$name: Branch origin/$branch nicht gefunden."
        return 1
    fi

    git -C "$dir" config core.fileMode false
    git -C "$dir" checkout -B "$branch" "origin/${branch}"
    git -C "$dir" reset --hard "origin/${branch}"

    local commit
    commit=$(git -C "$dir" log -1 --oneline)
    print_success "$name: $commit"
}

echo ""
print_section "WoltLab $VERSION synchronisieren" "Hauptmenü" "Update Version"
echo ""

step=1
total=4
if [ "$SKIP_CORE" -eq 1 ]; then
    total=3
fi

# ─── 1) woltlab-core (Download) ───
if [ "$SKIP_CORE" -eq 0 ]; then
    if [ -d "$MAIN_DIR/woltlab-core" ]; then
        print_info "${step}/${total} woltlab-core: Lade Suite $VERSION herunter …"
        if [ -x "$TOOLS_DIR/download-woltlab-core.sh" ]; then
            "$TOOLS_DIR/download-woltlab-core.sh" "$VERSION" || print_warning "Core-Download fehlgeschlagen."
        else
            chmod +x "$TOOLS_DIR/download-woltlab-core.sh" 2>/dev/null
            "$TOOLS_DIR/download-woltlab-core.sh" "$VERSION" || print_warning "Core-Download fehlgeschlagen."
        fi
        if [ -f "$MAIN_DIR/woltlab-core/WCFSetup.tar.gz" ]; then
            print_success "woltlab-core: bereit"
        fi
    else
        print_warning "woltlab-core/ nicht gefunden – übersprungen."
    fi
    echo ""
    step=$((step + 1))
fi

# ─── Git-Spiegel ───
print_info "${step}/${total} woltlab-github …"
sync_git_mirror "woltlab-github" "$MAIN_DIR/woltlab-github" "$BRANCH" || true
echo ""
step=$((step + 1))

print_info "${step}/${total} woltlab-docs …"
sync_git_mirror "woltlab-docs" "$MAIN_DIR/woltlab-docs" "$BRANCH" || true
echo ""
step=$((step + 1))

print_info "${step}/${total} woltlab-d-ts …"
sync_git_mirror "woltlab-d-ts" "$MAIN_DIR/woltlab-d-ts" "$BRANCH" || true
echo ""

print_success "Synchronisation auf WoltLab $VERSION abgeschlossen."
echo ""
