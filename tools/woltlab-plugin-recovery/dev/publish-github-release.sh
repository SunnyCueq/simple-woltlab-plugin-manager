#!/usr/bin/env bash
# Publish stub + recovery tarball to benjarogit/sc-woltlab-plugin-recovery
set -euo pipefail

VERSION="${1:?Usage: publish-github-release.sh VERSION}"
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
CLONE="${RECOVERY_GITHUB_CLONE:-/tmp/sc-woltlab-plugin-recovery}"
REPO="benjarogit/sc-woltlab-plugin-recovery"

if ! gh auth status >/dev/null 2>&1; then
  echo "GitHub nicht authentifiziert. Bitte ausführen: gh auth login -h github.com" >&2
  exit 1
fi

"$ROOT/dev/build-release.sh" "$VERSION"

STUB="$ROOT/dist/plugin-recovery-tool.php"
TAR="$ROOT/dist/recovery-${VERSION}.tar.gz"
test -f "$STUB" && test -f "$TAR"

if [[ ! -d "$CLONE/.git" ]]; then
  git clone "https://github.com/${REPO}.git" "$CLONE"
fi

cp "$STUB" "$CLONE/plugin-recovery-tool.php"
cd "$CLONE"
git add plugin-recovery-tool.php
if git diff --cached --quiet; then
  echo "master: plugin-recovery-tool.php unverändert"
else
  git -c user.name="benjarogit" -c user.email="benjarogit@users.noreply.github.com" \
    commit -m "Sync master with v${VERSION} stub."
fi
git push origin master

if gh release view "v${VERSION}" --repo "$REPO" >/dev/null 2>&1; then
  gh release upload "v${VERSION}" --repo "$REPO" --clobber "$STUB" "$TAR"
  echo "Release v${VERSION} Assets aktualisiert."
else
  gh release create "v${VERSION}" --repo "$REPO" --title "v${VERSION}" \
    --notes "WoltLab Plugin Recovery v${VERSION}" \
    "$STUB" "$TAR"
  echo "Release v${VERSION} erstellt."
fi

echo "https://github.com/${REPO}/releases/tag/v${VERSION}"
