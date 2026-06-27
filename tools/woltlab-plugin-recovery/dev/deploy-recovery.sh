#!/usr/bin/env bash
# Stub (+ optional lokales Paket) ins WoltLab-Hauptverzeichnis kopieren (Entwicklung).
set -euo pipefail

DEV_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(dirname "$DEV_DIR")"
TOOLS_DIR="$(dirname "$REPO_ROOT")"
VERSION="${RECOVERY_VERSION:-2.0.0}"

TARGET=""

if [ -f "$TOOLS_DIR/.env" ]; then
	# shellcheck disable=SC1091
	source "$TOOLS_DIR/.env"
	TARGET="${WOLTLAB_PUBLIC_DIR:-${WOLTLAB_INSTALL_PATH:-${WOLTLAB_PATH:-}}}"
fi

if [ -n "${1:-}" ]; then
	TARGET="$1"
fi

if [ -z "$TARGET" ]; then
	echo "Verwendung: $0 /pfad/zur/woltlab-installation"
	echo "Oder WOLTLAB_PUBLIC_DIR in tools/.env setzen."
	exit 1
fi

TARGET="${TARGET%/}"

if [ ! -f "$TARGET/global.php" ]; then
	echo "Fehler: global.php nicht gefunden in $TARGET"
	exit 1
fi

if [ -x "$DEV_DIR/validate-php-syntax.sh" ]; then
	"$DEV_DIR/validate-php-syntax.sh" || true
fi

if [ ! -f "$REPO_ROOT/dist/plugin-recovery-tool.php" ]; then
	"$DEV_DIR/build-release.sh" "$VERSION"
fi

cp -v "$REPO_ROOT/dist/plugin-recovery-tool.php" "$TARGET/plugin-recovery-tool.php"

if [ "${DEPLOY_PACKAGE:-1}" = "1" ] && [ -f "$REPO_ROOT/dist/recovery-${VERSION}.tar.gz" ]; then
	rm -rf "$TARGET/recovery-tool"
	mkdir -p "$TARGET/recovery-tool"
	tar -xzf "$REPO_ROOT/dist/recovery-${VERSION}.tar.gz" -C "$TARGET" --strip-components=0
	echo "Paket entpackt nach: $TARGET/recovery-tool/"
fi

echo ""
echo "Recovery Tool v${VERSION} (Stub + Paket) in: $TARGET"
echo "Aufruf: …/plugin-recovery-tool.php"
echo "Auth-Datei: plugin-recovery-auth.php (vom Tool erzeugt)"
echo "DEPLOY_PACKAGE=0 — nur Stub kopieren"
