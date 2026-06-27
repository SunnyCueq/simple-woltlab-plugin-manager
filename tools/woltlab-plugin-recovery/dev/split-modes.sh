#!/usr/bin/env bash
# Teilt den Modus-Router aus recovery-src/app.php in lib/Recovery/Modes/*.php
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
APP="$ROOT/recovery-src/app.php"
MODES="$ROOT/recovery-src/lib/Recovery/Modes"
ROUTER="$ROOT/recovery-src/lib/Recovery/router.php"

mkdir -p "$MODES"

extract_block() {
    local name="$1" start="$2" end="$3" prefix="$4"
    local out="$MODES/${name}.php"
    {
        echo '<?php'
        echo '/** Recovery mode: '"$name"' — included by lib/Recovery/router.php */'
        echo 'declare(strict_types=1);'
        echo ''
        sed -n "${start},${end}p" "$APP" | sed "s/^elseif/if/"
    } > "$out"
    echo "  $out ($(wc -l < "$out") lines)"
}

# Zeilen aus app.php (nach extract-package-app.sh)
extract_block selection 8217 8335 'if'
extract_block acp_repair 8336 8525 'elseif'
extract_block plugin_uninstall 8526 9278 'elseif'
extract_block user_management 9279 9597 'elseif'
extract_block cache_clear 9598 9639 'elseif'
extract_block package_list_repair 9640 9697 'elseif'
extract_block package_file_repair 9698 9844 'elseif'
extract_block recovery_wizard 9845 10329 'elseif'
extract_block system_check 10330 10339 'elseif'
extract_block backup_guide 10340 10347 'elseif'
extract_block directory_structure 10348 10355 'elseif'

cat > "$ROUTER" <<'PHP'
<?php

declare(strict_types=1);

/**
 * Modus-Routing (eine Datei pro Modus).
 *
 * @var int $mode
 * @var string $authHash
 * @var \wcf\system\database\Database $db
 * @var string $recoveryBaseUrl
 * @var string $wcfDirMain
 * @var array|null $emergencyAcpResult
 * @var array|null $emergencyFixedSession
 */

$modesDir = __DIR__ . '/Modes';

if ($mode === RECOVERY_MODE_SELECTION) {
    require $modesDir . '/selection.php';
} elseif ($mode === RECOVERY_MODE_ACP_REPAIR) {
    require $modesDir . '/acp_repair.php';
} elseif ($mode === RECOVERY_MODE_PLUGIN_UNINSTALL) {
    require $modesDir . '/plugin_uninstall.php';
} elseif ($mode === RECOVERY_MODE_USER_MANAGEMENT) {
    require $modesDir . '/user_management.php';
} elseif ($mode === RECOVERY_MODE_CACHE_CLEAR) {
    require $modesDir . '/cache_clear.php';
} elseif ($mode === RECOVERY_MODE_PACKAGE_LIST_REPAIR) {
    require $modesDir . '/package_list_repair.php';
} elseif ($mode === RECOVERY_MODE_PACKAGE_FILE_REPAIR) {
    require $modesDir . '/package_file_repair.php';
} elseif ($mode === RECOVERY_MODE_RECOVERY_WIZARD) {
    require $modesDir . '/recovery_wizard.php';
} elseif ($mode === RECOVERY_MODE_SYSTEM_CHECK) {
    require $modesDir . '/system_check.php';
} elseif ($mode === RECOVERY_MODE_BACKUP_GUIDE) {
    require $modesDir . '/backup_guide.php';
} elseif ($mode === RECOVERY_MODE_DIRECTORY_STRUCTURE) {
    require $modesDir . '/directory_structure.php';
}
PHP

# Modus-Block in app.php durch Router ersetzen
python3 - "$APP" "$ROUTER" <<'PY'
import sys
app_path, router_marker = sys.argv[1], 'MODUS 0: START'
lines = open(app_path, encoding='utf-8').readlines()
start = None
for i, line in enumerate(lines):
    if 'MODUS 0: START / SZENARIO-AUSWAHL' in line:
        start = i - 2  # vor <?php und Kommentarblock
        break
if start is None:
    raise SystemExit('mode block start not found')
end = None
for i in range(len(lines) - 1, -1, -1):
    if 'recoveryRenderPageEnd' in lines[i]:
        end = i - 2  # vor ?> und recoveryRenderPageEnd
        break
if end is None:
    raise SystemExit('page end not found')
replacement = [
    '\n',
    '<?php\n',
    '// Modus-Routing (lib/Recovery/Modes/*.php)\n',
    "require __DIR__ . '/lib/Recovery/router.php';\n",
    '\n',
    '?>\n',
    '<?php\n',
]
new_lines = lines[:start] + replacement + lines[end:]
open(app_path, 'w', encoding='utf-8').writelines(new_lines)
print(f'Trimmed app.php: removed lines {start+1}-{end}, now {len(new_lines)} lines')
PY

echo "Router: $ROUTER"
