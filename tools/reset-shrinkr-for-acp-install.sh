#!/usr/bin/env bash
# Entfernt halbfertige Shr1nkr-Installation + verwaistes App-Verzeichnis.
#
# Hintergrund: WoltLab-Deinstallation löscht nur Dateien aus wcf1_package_installation_file_log.
# Dateien aus docker cp landen nie im Log → /var/www/html/shrinkr/ bleibt mit global.php.
# ACP-Neuinstallation scheitert dann mit „Das angegebene Verzeichnis enthält bereits eine App.“
#
# Usage: ./tools/reset-shrinkr-for-acp-install.sh [--keep-dev-scripts]
# Danach: ./tools/prepare-acp-install.sh basis-plugin → ACP-Upload

set -euo pipefail

readonly SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
readonly CONTAINER="${WOLTLAB_DOCKER_CONTAINER:-woltlab-web}"
readonly PACKAGE="de.sunnyc.wsc.shrinkr"
KEEP_DEV_SCRIPTS=0

for arg in "$@"; do
    case "$arg" in
        --keep-dev-scripts) KEEP_DEV_SCRIPTS=1 ;;
        -h|--help)
            sed -n '2,12p' "$0"
            exit 0
            ;;
        *)
            echo "Unbekanntes Argument: $arg (nur --keep-dev-scripts)" >&2
            exit 1
            ;;
    esac
done

if ! docker ps --format '{{.Names}}' | grep -qx "$CONTAINER"; then
    echo "Container '$CONTAINER' läuft nicht." >&2
    exit 1
fi

echo "ℹ Bereinige DB (Queue, Formulare, Paket $PACKAGE) …"
docker exec -i -u www-data "$CONTAINER" php <<'PHP'
<?php
require '/var/www/html/global.php';

use wcf\data\application\ApplicationEditor;
use wcf\data\package\PackageEditor;
use wcf\system\cache\builder\PackageCacheBuilder;
use wcf\system\WCF;

$db = WCF::getDB();
$packageName = 'de.sunnyc.wsc.shrinkr';

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
    \fwrite(\STDERR, '  Paket ' . $packageID . ' wirkt vollständig installiert (packageDir gesetzt, ' . $fileCount . " Datei-Log-Einträge).\n");
    \fwrite(\STDERR, "  Für saubere Neuinstallation zuerst im ACP deinstallieren, dann dieses Skript erneut.\n");
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

echo "ℹ Entferne verwaistes App-Verzeichnis /var/www/html/shrinkr/ …"
docker exec "$CONTAINER" rm -rf /var/www/html/shrinkr
if docker exec "$CONTAINER" test -d /var/www/html/shrinkr 2>/dev/null; then
    echo "✗ /var/www/html/shrinkr/ konnte nicht entfernt werden." >&2
    exit 1
fi
echo "✓ /var/www/html/shrinkr/ entfernt"

if [ "$KEEP_DEV_SCRIPTS" -eq 0 ]; then
    for devScript in shrinkr-max-test.php shrinkr-cron-run.php; do
        if docker exec "$CONTAINER" test -f "/var/www/html/$devScript" 2>/dev/null; then
            docker exec "$CONTAINER" rm -f "/var/www/html/$devScript"
            echo "✓ /var/www/html/$devScript entfernt"
        fi
    done
fi

echo ""
echo "✓ Reset abgeschlossen. Nächster Schritt:"
echo "  ./tools/prepare-acp-install.sh basis-plugin"
echo "  → ACP Paket hochladen (Neuinstallation, Verzeichnis /var/www/html/shrinkr/ ist jetzt leer)"
