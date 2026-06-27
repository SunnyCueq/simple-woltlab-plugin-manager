#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
VERSION="${1:-2.0.0}"
DIST="$ROOT/dist"
PKG="$DIST/recovery-tool"

echo "==> Recovery Release v${VERSION}"

rm -rf "$PKG"
mkdir -p "$PKG" "$DIST"

rsync -a --exclude='.git' --exclude='.cache' \
  "$ROOT/recovery-src/" "$PKG/"

# manifest.json Version setzen
python3 - "$PKG/manifest.json" "$VERSION" <<'PY'
import json, sys
path, ver = sys.argv[1], sys.argv[2]
data = json.load(open(path, encoding='utf-8'))
data['version'] = ver
data['minStubVersion'] = ver
json.dump(data, open(path, 'w', encoding='utf-8'), indent=4)
print('manifest', ver)
PY

# version.php im Paket
cat > "$PKG/version.php" <<PHP
<?php

declare(strict_types=1);

define('RECOVERY_STUB_VERSION', '${VERSION}');
define('RECOVERY_PACKAGE_VERSION', '${VERSION}');
define('RECOVERY_VERSION', RECOVERY_PACKAGE_VERSION);
define('RECOVERY_GITHUB_REPO', 'benjarogit/sc-woltlab-plugin-recovery');
PHP

# tar.gz
rm -f "$DIST/recovery-${VERSION}.tar.gz"
# Dateien direkt im Archiv-Root (ohne ./ — PharData kann „.“ nicht entpacken)
( cd "$PKG" && tar -czf "$DIST/recovery-${VERSION}.tar.gz" * )

# Stub: Shell + Logger + Auth + CSS inline → eine Release-Datei
STUB_OUT="$DIST/plugin-recovery-tool.php"
python3 - "$ROOT" "$VERSION" "$STUB_OUT" <<'PY'
import hashlib
import re
import sys
from pathlib import Path

root, version, out = Path(sys.argv[1]), sys.argv[2], Path(sys.argv[3])
wizard_css = (root / 'stub/recovery-stub-wizard.css').read_text(encoding='utf-8')
acp_layout_css = (root / 'stub/recovery-acp-layout.css').read_text(encoding='utf-8')
shell = (root / 'stub/recovery-stub-shell.php').read_text(encoding='utf-8')
logger = (root / 'stub/recovery-stub-logger.php').read_text(encoding='utf-8')
auth = (root / 'stub/recovery-stub-auth.php').read_text(encoding='utf-8')
main = (root / 'stub/plugin-recovery-tool.php').read_text(encoding='utf-8')

for pattern in (
    r"require __DIR__ \. '/recovery-stub-shell\.php';\n",
    r"require __DIR__ \. '/recovery-stub-logger\.php';\n",
    r"require __DIR__ \. '/recovery-stub-auth\.php';\n",
):
    main = re.sub(pattern, '', main)


def strip_php_header(src: str) -> str:
    src = re.sub(r'<\?php\s*', '', src, count=1)
    src = re.sub(r"declare\s*\(\s*strict_types\s*=\s*1\s*\)\s*;\s*", '', src, count=1)
    return src.lstrip('\n')


shell = strip_php_header(shell)
logger = strip_php_header(logger)
auth = strip_php_header(auth)
main = strip_php_header(main)

css_fn = (
    "function recoveryStubWizardCss(): string\n"
    "{\n    return <<<'RECOVERY_STUB_WIZARD_CSS'\n"
    + wizard_css
    + "\nRECOVERY_STUB_WIZARD_CSS;\n}\n"
)
shell = re.sub(
    r"function recoveryStubWizardCss\(\): string\s*\{.*?\n\}",
    css_fn,
    shell,
    count=1,
    flags=re.DOTALL,
)

acp_layout_fn = (
    "function recoveryStubAcpLayoutCss(): string\n"
    "{\n    return <<<'RECOVERY_STUB_ACP_LAYOUT_CSS'\n"
    + acp_layout_css
    + "\nRECOVERY_STUB_ACP_LAYOUT_CSS;\n}\n"
)
shell = re.sub(
    r"function recoveryStubAcpLayoutCss\(\): string\s*\{.*?\n\}",
    acp_layout_fn,
    shell,
    count=1,
    flags=re.DOTALL,
)

merged = "<?php\n\ndeclare(strict_types=1);\n\n" + shell + "\n" + logger + "\n" + auth + "\n" + main
merged = re.sub(
    r"define\('RECOVERY_STUB_VERSION',\s*'[^']*'\);",
    f"define('RECOVERY_STUB_VERSION', '{version}');",
    merged,
    count=1,
)
merged = re.sub(
    r"define\('RECOVERY_PACKAGE_VERSION',\s*'[^']*'\);",
    f"define('RECOVERY_PACKAGE_VERSION', '{version}');",
    merged,
    count=1,
)

placeholder = '0' * 64
if "RECOVERY_STUB_INTEGRITY_HASH" not in merged:
    merged = merged.replace(
        f"define('RECOVERY_PACKAGE_VERSION', '{version}');",
        f"define('RECOVERY_PACKAGE_VERSION', '{version}');\n"
        f"define('RECOVERY_STUB_INTEGRITY_HASH', '{placeholder}');",
        1,
    )
else:
    merged = re.sub(
        r"^define\('RECOVERY_STUB_INTEGRITY_HASH',\s*'[^']*'\);",
        f"define('RECOVERY_STUB_INTEGRITY_HASH', '{placeholder}');",
        merged,
        count=1,
        flags=re.MULTILINE,
    )

integrity_pat = r"^define\('RECOVERY_STUB_INTEGRITY_HASH',\s*'[^']*'\);"
canonical = re.sub(
    integrity_pat,
    f"define('RECOVERY_STUB_INTEGRITY_HASH', '{placeholder}');",
    merged,
    count=1,
    flags=re.MULTILINE,
)
integrity = hashlib.sha256(canonical.encode('utf-8')).hexdigest()
merged = re.sub(
    integrity_pat,
    f"define('RECOVERY_STUB_INTEGRITY_HASH', '{integrity}');",
    merged,
    count=1,
    flags=re.MULTILINE,
)

out.write_text(merged, encoding='utf-8')
print('stub merged', out, 'bytes', out.stat().st_size, 'integrity', integrity[:16] + '…')
PY

echo "==> dist/"
ls -la "$DIST/recovery-${VERSION}.tar.gz" "$STUB_OUT"
echo "OK"
