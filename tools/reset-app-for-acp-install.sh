#!/usr/bin/env bash
# Entfernt halbfertige Plugin-Installation + verwaistes App-Verzeichnis im Docker-Container.
#
# Hintergrund: WoltLab-Deinstallation löscht nur Dateien aus wcf1_package_installation_file_log.
# Dateien aus docker cp landen nie im Log → App-Ordner bleibt mit global.php.
# ACP-Neuinstallation scheitert dann mit „Das angegebene Verzeichnis enthält bereits eine App.“
#
# Usage:
#   ./tools/reset-app-for-acp-install.sh [plugin-dir] [--remove-path /var/www/html/extra.php ...]
# Danach: ./tools/prepare-acp-install.sh [plugin-dir] → ACP-Upload

set -euo pipefail

readonly SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
readonly MAIN_DIR="$(dirname "$SCRIPT_DIR")"
readonly CONTAINER="${WOLTLAB_DOCKER_CONTAINER:-woltlab-web}"

# shellcheck source=swpm-package-resolve.sh
source "$SCRIPT_DIR/swpm-package-resolve.sh"

PLUGIN_ARG="${WOLTLAB_PLUGIN_DIR:-basis-plugin}"
REMOVE_PATHS=()

if [ $# -gt 0 ] && [[ "$1" != --* ]]; then
    PLUGIN_ARG="$1"
    shift
fi

while [ $# -gt 0 ]; do
    case "$1" in
        --remove-path)
            shift
            [ $# -gt 0 ] || { echo "--remove-path braucht ein Argument" >&2; exit 1; }
            REMOVE_PATHS+=("$1")
            ;;
        -h|--help)
            sed -n '2,12p' "$0"
            exit 0
            ;;
        *)
            REMOVE_PATHS+=("$1")
            ;;
    esac
    shift
done

if ! swpm_load_plugin_context "$PLUGIN_ARG" "$SCRIPT_DIR" "$MAIN_DIR"; then
    exit 1
fi

APP_DIR="/var/www/html/${SWPM_APP_ABBREV}"

if ! docker ps --format '{{.Names}}' | grep -qx "$CONTAINER"; then
    echo "Container '$CONTAINER' läuft nicht." >&2
    exit 1
fi

echo "ℹ Bereinige DB (Queue, Formulare, Paket $SWPM_PACKAGE_ID) …"
docker exec -i -u www-data \
    -e "SWPM_PACKAGE=$SWPM_PACKAGE_ID" \
    "$CONTAINER" php <<'PHP'
<?php
require '/var/www/html/global.php';

use wcf\data\application\ApplicationEditor;
use wcf\data\package\PackageEditor;
use wcf\system\cache\builder\PackageCacheBuilder;
use wcf\system\WCF;

$packageName = getenv('SWPM_PACKAGE') ?: '';
if ($packageName === '') {
    fwrite(STDERR, "SWPM_PACKAGE fehlt\n");
    exit(1);
}

$db = WCF::getDB();

$stmt = $db->prepareStatement('SELECT queueID FROM wcf1_package_installation_queue WHERE package = ?');
$stmt->execute([$packageName]);
$queueIDs = $stmt->fetchAll(\PDO::FETCH_COLUMN);
if ($queueIDs !== []) {
    $cb = new \wcf\system\database\util\PreparedStatementConditionBuilder();
    $cb->add('queueID IN (?)', [$queueIDs]);
    $del = $db->prepareStatement('DELETE FROM wcf1_package_installation_form ' . $cb);
    $del->execute($cb->getParameters());
    echo '  forms deleted: ' . $del->getAffectedRows() . "\n";
}

$stmt = $db->prepareStatement('DELETE FROM wcf1_package_installation_queue WHERE package = ?');
$stmt->execute([$packageName]);
echo '  queues deleted: ' . $stmt->getAffectedRows() . "\n";

$stmt = $db->prepareStatement('SELECT * FROM wcf1_package WHERE package = ?');
$stmt->execute([$packageName]);
$row = $stmt->fetchArray();
if ($row === false) {
    echo "  kein Paketeintrag in DB\n";
    exit(0);
}

$packageID = (int) $row['packageID'];
$fileLog = $db->prepareStatement('SELECT COUNT(*) FROM wcf1_package_installation_file_log WHERE packageID = ?');
$fileLog->execute([$packageID]);
$fileCount = (int) $fileLog->fetchSingleColumn();

$incomplete = $row['packageDir'] === '' || $fileCount === 0;
if (!$incomplete) {
    fwrite(STDERR, '  Paket ' . $packageID . ' wirkt vollständig installiert (packageDir gesetzt, ' . $fileCount . " Datei-Log-Einträge).\n");
    fwrite(STDERR, "  Für saubere Neuinstallation zuerst im ACP deinstallieren, dann dieses Skript erneut.\n");
    exit(1);
}

echo '  entferne unvollständiges Paket ' . $packageID . ' (packageDir="' . $row['packageDir'] . '", fileLog=' . $fileCount . ") …\n";

foreach (['wcf1_package_requirement', 'wcf1_package_exclusion', 'wcf1_package_installation_file_log'] as $table) {
    $s = $db->prepareStatement("DELETE FROM {$table} WHERE packageID = ?");
    $s->execute([$packageID]);
}

ApplicationEditor::deleteAll([$packageID]);
PackageEditor::deleteAll([$packageID]);

$stmt = $db->prepareStatement(
    'DELETE FROM wcf1_language_item WHERE languageItem IN (?, ?)'
);
$stmt->execute([
    'wcf.acp.package.packageName.package' . $packageID,
    'wcf.acp.package.packageDescription.package' . $packageID,
]);

PackageCacheBuilder::getInstance()->reset();
echo "  DB bereinigt\n";
PHP

echo "ℹ Entferne verwaistes App-Verzeichnis ${APP_DIR}/ …"
docker exec "$CONTAINER" rm -rf "$APP_DIR"
if docker exec "$CONTAINER" test -d "$APP_DIR" 2>/dev/null; then
    echo "✗ ${APP_DIR}/ konnte nicht entfernt werden." >&2
    exit 1
fi
echo "✓ ${APP_DIR}/ entfernt"

for extra in "${REMOVE_PATHS[@]}"; do
    if docker exec "$CONTAINER" test -e "$extra" 2>/dev/null; then
        docker exec "$CONTAINER" rm -rf "$extra"
        echo "✓ $extra entfernt"
    fi
done

echo ""
echo "✓ Reset abgeschlossen. Nächster Schritt:"
echo "  ./tools/prepare-acp-install.sh $PLUGIN_ARG"
echo "  → ACP Paket hochladen (Neuinstallation, ${APP_DIR}/ ist leer)"
