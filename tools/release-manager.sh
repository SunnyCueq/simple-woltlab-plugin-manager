#!/usr/bin/env bash
# SWPM Maintainer-Release: optional commit → CHANGELOG prüfen → Tag vX.Y.Z → push → GitHub-Release (CI).
#
# Usage:
#   ./tools/release-manager.sh 1.2.6
#   ./tools/release-manager.sh                    # Version aus CHANGELOG (erstes untagged)
#   ./tools/release-manager.sh --commit -m "…" 1.2.6
#   ./tools/release-manager.sh --dry-run 1.2.6
#
# GitHub-Release: .github/workflows/release.yml (Tag-Push v*.*.*).
# Nicht verwechseln mit ./tools.sh push (Plugin-Repos) oder alter manager-push.sh (CalVer).

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"
cd "$ROOT"

# shellcheck source=common.sh
source "$SCRIPT_DIR/common.sh"

RELEASE_BRANCH="${SWPM_RELEASE_BRANCH:-main}"
DRY_RUN=0
ASSUME_YES=0
LOCAL_RELEASE=0
SKIP_TESTS=0
SKIP_PUSH=0
DO_COMMIT=0
COMMIT_MESSAGE=""
VERSION_RAW=""

usage() {
    cat <<'EOF'
Usage: ./tools/release-manager.sh [options] [<X.Y.Z|vX.Y.Z>]

SWPM selbst releasen (SemVer-Tag → origin → CI release.yml).

Ohne Version: erstes "## Version X.Y.Z" in CHANGELOG.md, für das vX.Y.Z noch nicht existiert.

Options:
  --commit          Änderungen committen (sichere Staging-Regeln, siehe unten)
  -m, --message     Commit-Message (mit --commit; Default: Release X.Y.Z: …)
  --dry-run         Nur prüfen, nichts taggen/pushen
  --yes, -y         Ohne Rückfrage committen/taggen/pushen
  --local-release   Nach Tag-Push zusätzlich publish-manager-release.sh (gh)
  --skip-tests      tools/tests/run-tests.sh überspringen
  --skip-push       Nur lokalen Tag setzen (kein git push)
  -h, --help        Diese Hilfe

Mit --commit werden nie gestaged: .cursor/, .audit/, plugin-One-Off-Skripte,
CLAUDE*.md, WoltLab-Spiegel (woltlab-docs/, …), Plugin-Ordner-Inhalte, .env.

Lokale Overrides: tools/manager-push.env
EOF
}

while [ $# -gt 0 ]; do
    case "$1" in
        --dry-run) DRY_RUN=1; shift ;;
        --yes | -y) ASSUME_YES=1; shift ;;
        --local-release) LOCAL_RELEASE=1; shift ;;
        --skip-tests) SKIP_TESTS=1; shift ;;
        --skip-push) SKIP_PUSH=1; shift ;;
        --commit) DO_COMMIT=1; shift ;;
        -m | --message)
            shift
            [ $# -gt 0 ] || { print_error "-m/--message braucht Text"; exit 2; }
            COMMIT_MESSAGE="$1"
            shift
            ;;
        -h | --help) usage; exit 0 ;;
        --) shift; break ;;
        -*) print_error "Unbekannte Option: $1"; usage >&2; exit 2 ;;
        *)
            if [ -n "$VERSION_RAW" ]; then
                print_error "Nur eine Version angeben: $1"
                exit 2
            fi
            VERSION_RAW="$1"
            shift
            ;;
    esac
done

if [ -f "$SCRIPT_DIR/manager-push.env" ]; then
    # shellcheck disable=SC1090
    source "$SCRIPT_DIR/manager-push.env"
fi

changelog_section_for_version() {
    local ver="$1"
    awk -v ver="$ver" '
        BEGIN { capturing = 0 }
        /^## Version / {
            if (capturing) exit
            if ($0 ~ ("^## Version " ver "([^0-9]|$)")) {
                capturing = 1
                next
            }
            next
        }
        capturing { print }
    ' CHANGELOG.md
}

detect_release_version() {
    local ver tag
    while IFS= read -r ver; do
        [ -n "$ver" ] || continue
        ver="${ver%%–*}"
        ver="${ver%%-*}"
        ver="$(echo "$ver" | sed 's/[[:space:]]*$//')"
        tag="v${ver}"
        if ! git rev-parse "$tag" >/dev/null 2>&1; then
            echo "$ver"
            return 0
        fi
    done < <(awk '/^## Version [0-9]/ {print $3}' CHANGELOG.md 2>/dev/null)
    return 1
}

swpm_release_stage_safe() {
    local path
    git add -A

    # Verzeichnis-Inhalte (Ordner bleiben, Inhalt nicht committen)
    local exclude_dirs=(
        "tools/woltlab-dev/public"
        "woltlab-docs"
        "woltlab-github"
        "woltlab-core"
        "woltlab-d-ts"
        "basis-plugin"
        "mein-plugin"
        "plugins-integrieren"
        ".cursor"
        ".audit"
        "tools/copywriting/.venv"
        "tools/runtime"
        "site"
        ".venv-docs"
    )
    for path in "${exclude_dirs[@]}"; do
        if [ -e "$path" ]; then
            git reset HEAD -- "$path" 2>/dev/null || true
            git reset HEAD -- "$path"/* 2>/dev/null || true
            git reset HEAD -- "$path"/**/* 2>/dev/null || true
        fi
    done

    # Einzeldateien / Muster
    local exclude_files=(
        ".cursorignore"
        "CLAUDE.md"
        "tools/manager-push.env"
        "tools/swpm-force-remove-shrinkr.php"
        "tools/swpm-install-once.php"
        "tools/swpm-uninstall-once.php"
        "tools/uninstall-package-once.php"
    )
    for path in "${exclude_files[@]}"; do
        [ -e "$path" ] && git reset HEAD -- "$path" 2>/dev/null || true
    done
    while IFS= read -r -d '' path; do
        git reset HEAD -- "$path" 2>/dev/null || true
    done < <(find . -maxdepth 1 -name 'CLAUDE*.md' -print0 2>/dev/null || true)

    # Nur letzte 5 TARs pro Plugin-Ordner wären gitpush-Thema — Manager-Release: keine releases/*.tar.gz
    git reset HEAD -- releases/ 2>/dev/null || true
    while IFS= read -r -d '' path; do
        git reset HEAD -- "$path" 2>/dev/null || true
    done < <(find releases -name '*.tar.gz' -print0 2>/dev/null || true)
}

has_tracked_changes() {
    ! git diff --quiet || ! git diff --cached --quiet
}

resolve_git_identity() {
    local name email
    name="${GIT_AUTHOR_NAME:-$(git log -1 --format='%an' 2>/dev/null || true)}"
    email="${GIT_AUTHOR_EMAIL:-$(git log -1 --format='%ae' 2>/dev/null || true)}"
    if [ -z "$name" ] || [ -z "$email" ]; then
        name="$(git config user.name 2>/dev/null || true)"
        email="$(git config user.email 2>/dev/null || true)"
    fi
    if [ -z "$name" ] || [ -z "$email" ]; then
        print_error "Git-Identität unbekannt — GIT_AUTHOR_NAME/EMAIL setzen oder git config user.*"
        exit 1
    fi
    printf '%s|%s' "$name" "$email"
}

check_no_cursor_trailers() {
    local range="${1:-HEAD}"
    if ! command -v rg >/dev/null 2>&1; then
        return 0
    fi
    if git log "$range" --format='%B---' 2>/dev/null | rg -qi \
        'co-authored-by|cursoragent@cursor\.com|cursor agent|^made with cursor|via cursor'; then
        print_error "Cursor-Spur in Commit-Messages — vor Push bereinigen."
        exit 1
    fi
}

if [ ! -f CHANGELOG.md ]; then
    print_error "CHANGELOG.md fehlt."
    exit 1
fi

if ! git rev-parse --is-inside-work-tree >/dev/null 2>&1; then
    print_error "Kein Git-Repository."
    exit 1
fi

current_branch="$(git branch --show-current 2>/dev/null || true)"
if [ "$current_branch" != "$RELEASE_BRANCH" ]; then
    print_error "Branch ist '${current_branch:-?}', erwartet '${RELEASE_BRANCH}'."
    exit 1
fi

if [ -z "$VERSION_RAW" ]; then
    if ! VERSION_RAW="$(detect_release_version)"; then
        print_error "Keine Version ermittelbar — CHANGELOG ## Version X.Y.Z fehlt oder Tag existiert schon."
        echo "  Aufruf: ./tools/release-manager.sh 1.2.6" >&2
        exit 2
    fi
    print_info "Version aus CHANGELOG: ${VERSION_RAW}"
fi

TAG_NAME="$VERSION_RAW"
[[ "$TAG_NAME" == v* ]] || TAG_NAME="v${TAG_NAME}"
VERSION="${TAG_NAME#v}"

if [[ ! "$VERSION" =~ ^[0-9]+\.[0-9]+\.[0-9]+([[:space:]]+(Alpha|Beta|RC|dev)[[:space:]]+[0-9]+)?$ ]]; then
    print_error "Ungültige SemVer-Version: ${VERSION}"
    exit 2
fi

if [[ "$VERSION" =~ ^20[0-9]{2}\. ]]; then
    print_error "CalVer-ähnliche Version ${VERSION} — SWPM nutzt SemVer (1.x.y), nicht vYYYY.MM.DD."
    exit 2
fi

NOTES="$(changelog_section_for_version "$VERSION")"
if [ -z "$(printf '%s\n' "$NOTES" | sed '/^$/d')" ]; then
    print_error "Kein CHANGELOG-Abschnitt für Version ${VERSION}."
    echo "  Erwartet: ## Version ${VERSION} – YYYY-MM-DD" >&2
    exit 2
fi

if git rev-parse "$TAG_NAME" >/dev/null 2>&1; then
    print_error "Tag ${TAG_NAME} existiert bereits."
    exit 1
fi

if has_tracked_changes; then
    if [ "$DO_COMMIT" -eq 1 ]; then
        if [ -z "$COMMIT_MESSAGE" ]; then
            first_bullet="$(printf '%s\n' "$NOTES" | sed -n 's/^- \*\*\([^*]*\)\*\*.*/\1/p;q')"
            if [ -n "$first_bullet" ]; then
                COMMIT_MESSAGE="Release ${VERSION}: ${first_bullet}."
            else
                COMMIT_MESSAGE="Release ${VERSION}."
            fi
        fi
        if [ "$DRY_RUN" -eq 1 ]; then
            echo -e "${DIM}[dry-run] würde committen: ${COMMIT_MESSAGE}${RESET}"
        else
            echo "== Commit (sicheres Staging) =="
            swpm_release_stage_safe
            if ! has_tracked_changes && [ -z "$(git status --porcelain)" ]; then
                print_warning "Nach Staging nichts zu committen."
            else
                if [ -z "$(git diff --cached --name-only)" ]; then
                    print_error "Nur ausgeschlossene Dateien geändert — nichts committbar."
                    git status -sb >&2 || true
                    exit 1
                fi
                identity="$(resolve_git_identity)"
                GIT_AUTHOR_NAME="${identity%%|*}" GIT_AUTHOR_EMAIL="${identity#*|}" \
                GIT_COMMITTER_NAME="${identity%%|*}" GIT_COMMITTER_EMAIL="${identity#*|}" \
                git commit -m "$COMMIT_MESSAGE"
                print_success "Committed: ${COMMIT_MESSAGE}"
            fi
            echo ""
        fi
    else
        print_error "Uncommittete Änderungen an getrackten Dateien."
        git diff --name-only >&2 || true
        git diff --cached --name-only >&2 || true
        echo "" >&2
        echo "  Committen und releasen: ./tools/release-manager.sh --commit -m \"…\" ${VERSION}" >&2
        echo "  Oder manuell committen, dann ohne --commit erneut starten." >&2
        exit 1
    fi
elif [ "$DO_COMMIT" -eq 1 ]; then
    print_warning "--commit gesetzt, aber keine Änderungen an getrackten Dateien."
fi

git fetch origin "$RELEASE_BRANCH" 2>/dev/null || true
if git rev-parse "origin/${RELEASE_BRANCH}" >/dev/null 2>&1; then
    local_behind="$(git rev-list --count HEAD.."origin/${RELEASE_BRANCH}" 2>/dev/null || echo 0)"
    if [ "${local_behind:-0}" -gt 0 ]; then
        print_error "HEAD hinter origin/${RELEASE_BRANCH} — bitte pullen."
        exit 1
    fi
fi

identity="$(resolve_git_identity)"
GIT_AUTHOR_NAME="${identity%%|*}"
GIT_AUTHOR_EMAIL="${identity#*|}"
GIT_COMMITTER_NAME="$GIT_AUTHOR_NAME"
GIT_COMMITTER_EMAIL="$GIT_AUTHOR_EMAIL"

echo ""
echo -e "${BOLD}SWPM Release ${TAG_NAME}${RESET}"
echo -e "  ${DIM}Branch:${RESET} ${RELEASE_BRANCH}"
echo -e "  ${DIM}HEAD:${RESET}   $(git rev-parse --short HEAD) $(git log -1 --format='%s')"
echo -e "  ${DIM}Remote:${RESET} origin (nicht manager)"
echo ""

if [ "$SKIP_TESTS" -eq 0 ] && [ -x "$SCRIPT_DIR/tests/run-tests.sh" ]; then
    if [ "$DRY_RUN" -eq 1 ]; then
        echo -e "${DIM}[dry-run] würde tools/tests/run-tests.sh ausführen${RESET}"
    else
        echo "== Toolkit-Tests =="
        "$SCRIPT_DIR/tests/run-tests.sh"
        echo ""
    fi
fi

if [ "$DRY_RUN" -eq 1 ]; then
    print_success "[dry-run] CHANGELOG OK, Tag ${TAG_NAME} würde auf HEAD gesetzt und nach origin gepusht."
    exit 0
fi

if [ "$ASSUME_YES" -eq 0 ]; then
    read -r -p "Tag ${TAG_NAME} erstellen und nach origin pushen? (j/N): " ok
    ok=${ok:-n}
    if [[ ! "$ok" =~ ^[jJyY] ]]; then
        echo "Abgebrochen."
        exit 0
    fi
fi

GIT_AUTHOR_NAME="$GIT_AUTHOR_NAME" GIT_AUTHOR_EMAIL="$GIT_AUTHOR_EMAIL" \
GIT_COMMITTER_NAME="$GIT_COMMITTER_NAME" GIT_COMMITTER_EMAIL="$GIT_COMMITTER_EMAIL" \
git tag -a "$TAG_NAME" -m "Release ${VERSION}"

print_success "Tag ${TAG_NAME} lokal erstellt."

if [ "$SKIP_PUSH" -eq 1 ]; then
    echo "Push übersprungen (--skip-push). Danach:"
    echo "  git push origin ${RELEASE_BRANCH}"
    echo "  git push origin ${TAG_NAME}"
    exit 0
fi

ahead="$(git rev-list --count "origin/${RELEASE_BRANCH}"..HEAD 2>/dev/null || echo 0)"
if [ "${ahead:-0}" -gt 0 ]; then
    check_no_cursor_trailers "origin/${RELEASE_BRANCH}..HEAD"
    echo "Pushe ${ahead} Commit(s) nach origin/${RELEASE_BRANCH} …"
    git push origin "HEAD:${RELEASE_BRANCH}"
else
    echo -e "${DIM}Keine neuen Commits auf origin/${RELEASE_BRANCH}.${RESET}"
fi

echo "Pushe Tag ${TAG_NAME} …"
git push origin "$TAG_NAME"

print_success "Tag ${TAG_NAME} gepusht — GitHub Action release.yml legt das Release an."

repo_url=""
if command -v gh >/dev/null 2>&1; then
    repo_url="$(gh repo view --json url -q .url 2>/dev/null || true)"
fi
if [ -z "$repo_url" ]; then
    repo_url="$(git remote get-url origin 2>/dev/null | sed 's/\.git$//' | sed 's|^git@github.com:|https://github.com/|' || true)"
fi
repo_url="${repo_url%.git}"
if [ -n "$repo_url" ]; then
    echo "  Actions: ${repo_url}/actions/workflows/release.yml"
    echo "  Release: ${repo_url}/releases/tag/${TAG_NAME}"
fi

if [ "$LOCAL_RELEASE" -eq 1 ]; then
    if ! command -v gh >/dev/null 2>&1; then
        print_error "--local-release braucht gh CLI."
        exit 1
    fi
    echo ""
    "$SCRIPT_DIR/publish-manager-release.sh" "$TAG_NAME"
fi
