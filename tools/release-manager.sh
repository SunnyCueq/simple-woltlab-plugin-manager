#!/usr/bin/env bash
# SWPM Maintainer-Release: CHANGELOG prüfen → Tag vX.Y.Z → push → GitHub-Release (CI).
#
# Usage:
#   ./tools/release-manager.sh 1.2.5
#   ./tools/release-manager.sh v1.2.5 --yes
#   ./tools/release-manager.sh --dry-run 1.2.5
#
# Voraussetzungen:
#   - Abschnitt "## Version X.Y.Z" in CHANGELOG.md (committed auf HEAD)
#   - Branch main (oder SWPM_RELEASE_BRANCH)
#   - Tag vX.Y.Z existiert noch nicht
#
# GitHub-Release: Workflow .github/workflows/release.yml (Tag-Push).
# Optional lokal: --local-release (braucht gh).

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
VERSION_RAW=""

usage() {
    cat <<'EOF'
Usage: ./tools/release-manager.sh [options] <X.Y.Z|vX.Y.Z>

SWPM selbst releasen (nicht Plugin-Repos — dafür ./tools.sh push).

Options:
  --dry-run         Nur prüfen, nichts taggen/pushen
  --yes, -y         Ohne Rückfrage taggen und pushen
  --local-release   Nach Tag-Push zusätzlich publish-manager-release.sh (gh)
  --skip-tests      tools/tests/run-tests.sh überspringen
  --skip-push       Nur lokalen Tag setzen (kein git push)
  -h, --help        Diese Hilfe

Ablauf:
  1. CHANGELOG.md enthält "## Version X.Y.Z"
  2. Optional: Toolkit-Tests
  3. Annotierter Tag vX.Y.Z auf HEAD
  4. git push origin main + git push origin vX.Y.Z
  5. CI release.yml erstellt GitHub-Release aus CHANGELOG

Lokale Overrides (optional): tools/manager-push.env
EOF
}

while [ $# -gt 0 ]; do
    case "$1" in
        --dry-run) DRY_RUN=1; shift ;;
        --yes | -y) ASSUME_YES=1; shift ;;
        --local-release) LOCAL_RELEASE=1; shift ;;
        --skip-tests) SKIP_TESTS=1; shift ;;
        --skip-push) SKIP_PUSH=1; shift ;;
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

if [ -z "$VERSION_RAW" ]; then
    print_error "Version fehlt (z. B. ./tools/release-manager.sh 1.2.5)"
    usage >&2
    exit 2
fi

TAG_NAME="$VERSION_RAW"
[[ "$TAG_NAME" == v* ]] || TAG_NAME="v${TAG_NAME}"
VERSION="${TAG_NAME#v}"

if [[ ! "$VERSION" =~ ^[0-9]+\.[0-9]+\.[0-9]+([[:space:]]+(Alpha|Beta|RC|dev)[[:space:]]+[0-9]+)?$ ]]; then
    print_error "Ungültige SemVer-Version: ${VERSION}"
    exit 2
fi

if [[ "$VERSION" =~ ^20[0-9]{2}\. ]]; then
    print_error "CalVer-ähnliche Version ${VERSION} — SWPM nutzt SemVer (1.x.y)."
    exit 2
fi

if [ ! -f CHANGELOG.md ]; then
    print_error "CHANGELOG.md fehlt."
    exit 1
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

NOTES="$(changelog_section_for_version "$VERSION")"
if [ -z "$(printf '%s\n' "$NOTES" | sed '/^$/d')" ]; then
    print_error "Kein CHANGELOG-Abschnitt für Version ${VERSION}."
    echo "  Erwartet: ## Version ${VERSION} – YYYY-MM-DD" >&2
    exit 2
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

if ! git diff --quiet || ! git diff --cached --quiet; then
    print_error "Working tree nicht sauber — erst committen."
    git status -sb >&2 || true
    exit 1
fi

if git rev-parse "$TAG_NAME" >/dev/null 2>&1; then
    print_error "Tag ${TAG_NAME} existiert bereits."
    exit 1
fi

if ! git merge-base --is-ancestor HEAD "origin/${RELEASE_BRANCH}" 2>/dev/null \
    && ! git rev-parse "origin/${RELEASE_BRANCH}" >/dev/null 2>&1; then
  :
elif git rev-parse "origin/${RELEASE_BRANCH}" >/dev/null 2>&1; then
    local_behind="$(git rev-list --count HEAD.."origin/${RELEASE_BRANCH}" 2>/dev/null || echo 0)"
    if [ "${local_behind:-0}" -gt 0 ]; then
        print_error "HEAD hinter origin/${RELEASE_BRANCH} — bitte pullen."
        exit 1
    fi
fi

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

identity="$(resolve_git_identity)"
GIT_AUTHOR_NAME="${identity%%|*}"
GIT_AUTHOR_EMAIL="${identity#*|}"
GIT_COMMITTER_NAME="$GIT_AUTHOR_NAME"
GIT_COMMITTER_EMAIL="$GIT_AUTHOR_EMAIL"

echo ""
echo -e "${BOLD}SWPM Release ${TAG_NAME}${RESET}"
echo -e "  ${DIM}Branch:${RESET} ${RELEASE_BRANCH}"
echo -e "  ${DIM}HEAD:${RESET}   $(git rev-parse --short HEAD) $(git log -1 --format='%s')"
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
    print_success "[dry-run] CHANGELOG OK, Tag ${TAG_NAME} würde auf HEAD gesetzt und gepusht."
    exit 0
fi

if [ "$ASSUME_YES" -eq 0 ]; then
    read -r -p "Tag ${TAG_NAME} erstellen und pushen? (j/N): " ok
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

git fetch origin "$RELEASE_BRANCH" 2>/dev/null || true

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
