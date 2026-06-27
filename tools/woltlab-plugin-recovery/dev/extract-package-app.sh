#!/usr/bin/env bash
# Erzeugt recovery-src/app.php aus dem Monolith (ohne Auth-Block).
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
MONOLITH="$ROOT/dev/legacy-monolith.php"
OUT="$ROOT/recovery-src/app.php"

if [[ ! -f "$MONOLITH" ]]; then
    echo "Monolith fehlt: $MONOLITH" >&2
    exit 1
fi

# Zeilen 1–5470 (vor Auth), dann 5588–Ende (nach Auth).
{
    sed -n '1,5470p' "$MONOLITH"
    sed -n '5588,$p' "$MONOLITH"
} > "$OUT.tmp"

# PHP-Gate (Stub) und alte Versionszeile entfernen.
sed -i \
    -e '/^if (\\PHP_VERSION_ID < 80100)/,/^}$/d' \
    -e "s/@version 1\.8\.2/@version 2.0.0 (package)/" \
    -e "s/define('RECOVERY_VERSION', '1\.8\.2');/define('RECOVERY_VERSION', RECOVERY_PACKAGE_VERSION);/" \
    "$OUT.tmp"

# __DIR__-Pfade auf WCF-Root / Paket-Verzeichnis umstellen.
sed -i \
    -e "s|__DIR__ \. '/uploads'|recoveryWcfPath('uploads')|g" \
    -e "s|__DIR__ \. '/plugin-recovery-auth.php'|recoveryWcfPath('plugin-recovery-auth.php')|g" \
    -e "s|__DIR__ \. '/plugin-recovery-tool.php'|recoveryWcfPath('plugin-recovery-tool.php')|g" \
    -e "s|__DIR__ \. '/log/recovery-tool-\([^']*\)'|recoveryWcfPath('log/recovery-tool-\1')|g" \
    -e "s|__DIR__ \. '/log/plugin-recovery-\([^']*\)'|recoveryWcfPath('log/plugin-recovery-\1')|g" \
    -e "s|__DIR__ \. '/plugin-recovery.php'|recoveryWcfPath('plugin-recovery.php')|g" \
    -e "s|__DIR__ \. '/universal-recovery.php'|recoveryWcfPath('universal-recovery.php')|g" \
    -e "s|__DIR__ \. '/acp-repair.php'|recoveryWcfPath('acp-repair.php')|g" \
    -e "s|__DIR__ \. '/wsc-recovery.php'|recoveryWcfPath('wsc-recovery.php')|g" \
    -e "s|__DIR__ \. '/recovery-tool.php'|recoveryWcfPath('recovery-tool.php')|g" \
    -e "s|__DIR__ \. '/plugin-recovery-auth.php'|recoveryWcfPath('plugin-recovery-auth.php')|g" \
    -e "s|__DIR__ \. '/uploads'|recoveryWcfPath('uploads')|g" \
    "$OUT.tmp"

# recoveryResolveWcfDir: RECOVERY_WCF_ROOT zuerst
python3 - "$OUT.tmp" <<'PY'
import sys
path = sys.argv[1]
text = open(path, encoding='utf-8').read()
old = """function recoveryResolveWcfDir(): string
{
    foreach ([__DIR__, \\dirname(__DIR__), \\dirname(__DIR__, 2)] as $dir) {
        if (\\is_file($dir . '/global.php') && \\is_file($dir . '/config.inc.php')) {
            return \\rtrim($dir, '/') . '/';
        }
    }"""
new = """function recoveryResolveWcfDir(): string
{
    $preferred = recoveryWcfRoot();
    if (\\is_file($preferred . 'global.php') && \\is_file($preferred . 'config.inc.php')) {
        return $preferred;
    }
    foreach ([\\dirname(RECOVERY_PACKAGE_DIR), \\dirname(__DIR__), __DIR__, \\dirname(__DIR__, 2)] as $dir) {
        if (\\is_file($dir . '/global.php') && \\is_file($dir . '/config.inc.php')) {
            return \\rtrim($dir, '/') . '/';
        }
    }"""
if old not in text:
    print('WARN: recoveryResolveWcfDir pattern not found', file=sys.stderr)
else:
    text = text.replace(old, new)
open(path, 'w', encoding='utf-8').write(text)
PY

mv "$OUT.tmp" "$OUT"
echo "Written: $OUT ($(wc -l < "$OUT") lines)"
