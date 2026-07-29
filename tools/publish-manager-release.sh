#!/usr/bin/env bash
# Create/update a GitHub Release for this repo from a SemVer tag.
# Notes = CHANGELOG.md section for that version + Compare/commit list (no invented changelog).
#
# Usage:
#   ./tools/publish-manager-release.sh              # tag from GITHUB_REF or exact tag on HEAD
#   ./tools/publish-manager-release.sh v1.2.2
#   ./tools/publish-manager-release.sh 1.2.2
#
# CI: .github/workflows/release.yml (push of tag v*.*.*)

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"
cd "$ROOT"

RAW="${1:-${GITHUB_REF_NAME:-}}"
if [ -z "$RAW" ]; then
    RAW="$(git describe --tags --exact-match HEAD 2>/dev/null || true)"
fi
if [ -z "$RAW" ]; then
    echo "Usage: $0 <vX.Y.Z|X.Y.Z>  (or set GITHUB_REF_NAME / checkout a tag)" >&2
    exit 1
fi

TAG_NAME="$RAW"
[[ "$TAG_NAME" == v* ]] || TAG_NAME="v${TAG_NAME}"
VERSION="${TAG_NAME#v}"

# Skip CalVer leftovers (v2026.02.17) — manager releases are 1.x / 2.x …
if [[ "$VERSION" =~ ^20[0-9]{2}\. ]]; then
    echo "Skip: CalVer-like tag ${TAG_NAME} (no manager release)." >&2
    exit 0
fi

if ! command -v gh >/dev/null 2>&1; then
    echo "gh CLI required." >&2
    exit 1
fi

if [ ! -f CHANGELOG.md ]; then
    echo "CHANGELOG.md missing." >&2
    exit 1
fi

# Body under "## Version X.Y.Z …" until the next "## Version "
NOTES="$(awk -v ver="$VERSION" '
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
' CHANGELOG.md)"

# Trim leading/trailing blank lines
NOTES="$(printf '%s\n' "$NOTES" | sed -e '/./,$!d' | awk '
    { lines[NR] = $0 }
    END {
        end = NR
        while (end > 0 && lines[end] ~ /^[[:space:]]*$/) end--
        for (i = 1; i <= end; i++) print lines[i]
    }
')"

if [ -z "$(printf '%s' "$NOTES" | sed '/^$/d')" ]; then
    echo "No CHANGELOG.md section for Version ${VERSION}. Add it before tagging." >&2
    exit 2
fi

REPO_BASE="$(gh repo view --json url -q .url 2>/dev/null || true)"
if [ -z "$REPO_BASE" ]; then
    REPO_BASE="$(git remote get-url origin 2>/dev/null | sed 's/\.git$//' | sed 's|^git@github.com:|https://github.com/|')"
fi
REPO_BASE="${REPO_BASE%.git}"
REPO_BASE="${REPO_BASE%/}"

find_previous_version_tag() {
    local current_tag="$1"
    local prev=""
    if git rev-parse "$current_tag" >/dev/null 2>&1; then
        prev=$(git describe --tags --abbrev=0 --match 'v[0-9]*' "${current_tag}^" 2>/dev/null || true)
    else
        prev=$(git describe --tags --abbrev=0 --match 'v[0-9]*' HEAD 2>/dev/null || true)
        if [ "$prev" = "$current_tag" ]; then
            prev=$(git describe --tags --abbrev=0 --match 'v[0-9]*' "${current_tag}^" 2>/dev/null || true)
        fi
    fi
    while [ -n "$prev" ]; do
        local pv="${prev#v}"
        if [[ "$pv" =~ ^20[0-9]{2}\. ]]; then
            prev=$(git describe --tags --abbrev=0 --match 'v[0-9]*' "${prev}^" 2>/dev/null || true)
            continue
        fi
        break
    done
    if [ -n "$prev" ] && [ "$prev" != "$current_tag" ]; then
        echo "$prev"
    fi
}

append_release_git_refs() {
    local notes="$1"
    local tag_name="$2"
    local repo_base="$3"
    local max_commits="${4:-15}"
    local prev_tag range_end appendix="" compare_url commit_lines total shown more
    [ -n "$repo_base" ] || { printf '%s' "$notes"; return 0; }
    prev_tag=$(find_previous_version_tag "$tag_name")
    range_end="$tag_name"
    git rev-parse "$tag_name" >/dev/null 2>&1 || range_end="HEAD"
    appendix=$'\n\n---\n\n'
    if [ -n "$prev_tag" ]; then
        compare_url="${repo_base}/compare/${prev_tag}...${tag_name}"
        appendix+="## Changes since ${prev_tag}"$'\n\n'
        appendix+="**Full diff:** ${compare_url}"$'\n'
        total=$(git rev-list --count "${prev_tag}..${range_end}" 2>/dev/null || echo 0)
        if [ "${total:-0}" -gt 0 ] 2>/dev/null; then
            commit_lines=$(git log --oneline --no-decorate "${prev_tag}..${range_end}" 2>/dev/null | head -n "$max_commits" || true)
            shown=$(printf '%s\n' "$commit_lines" | grep -c . || true)
            appendix+=$'\n'"Commits (${shown}"
            if [ "$total" -gt "$max_commits" ]; then
                more=$((total - max_commits))
                appendix+="; ${more} more on Compare"
            fi
            appendix+="):"$'\n\n'"\`\`\`"$'\n'"${commit_lines}"$'\n'"\`\`\`"$'\n'
        fi
    else
        appendix+="## Changes"$'\n\n'
        appendix+="**Tag:** ${repo_base}/releases/tag/${tag_name}"$'\n'
    fi
    printf '%s%s' "$notes" "$appendix"
}

RELEASE_NOTES="$(append_release_git_refs "$NOTES" "$TAG_NAME" "$REPO_BASE" 15)"
TITLE="Plugin-Manager v${VERSION}"

if gh release view "$TAG_NAME" >/dev/null 2>&1; then
    printf '%s\n' "$RELEASE_NOTES" | gh release edit "$TAG_NAME" --title "$TITLE" --notes-file -
    echo "Updated release ${TAG_NAME}"
else
    printf '%s\n' "$RELEASE_NOTES" | gh release create "$TAG_NAME" --title "$TITLE" --notes-file -
    echo "Created release ${TAG_NAME}"
fi

gh release view "$TAG_NAME" --json url -q .url
