#!/usr/bin/env php
<?php

/**
 * DEV-FALLBACK: One-shot WoltLab package install/update (non-interactive).
 *
 * Standard für lokale Tests ist ACP-Upload (siehe tools/docs/ACP-PACKAGE-INSTALL.de.md).
 * Dieses Skript nur wenn der ACP-Datei-Dialog nicht nutzbar ist (z. B. CI).
 *
 * Usage: WCF_SESSION_ID=<admin-session> php install-package-once.php /path/to/package.tar.gz
 */

if (\PHP_SAPI !== 'cli') {
    \http_response_code(400);
    exit(1);
}

if (\function_exists('posix_getuid') && \posix_getuid() === 0) {
    \fwrite(\STDERR, "Refusing to execute as root.\n");
    exit(1);
}

if ($argc < 2 || !\is_readable($argv[1])) {
    \fwrite(\STDERR, "Usage: WCF_SESSION_ID=<session> php install-package-once.php <package.tar.gz>\n");
    exit(1);
}

$archivePath = $argv[1];

\define('PACKAGE_ID', 1);
\define('ENABLE_BENCHMARK', 0);

require_once(__DIR__ . '/app.config.inc.php');
require_once(WCF_DIR . 'lib/system/WCF.class.php');

use wcf\data\package\installation\queue\PackageInstallationQueue;
use wcf\data\package\installation\queue\PackageInstallationQueueEditor;
use wcf\data\package\Package;
use wcf\data\session\SessionEditor;
use wcf\system\package\PackageInstallationDispatcher;
use wcf\system\package\validation\PackageValidationManager;
use wcf\system\WCF;
use wcf\util\FileUtil;

final class PackageInstallBootstrap extends WCF
{
    public function __construct()
    {
        if (!\defined('TMP_DIR')) {
            \define('TMP_DIR', FileUtil::getTempFolder());
        }

        $this->initDB();
        $this->loadOptions();
        $this->initSession();
        $this->initLanguage();
        $this->initTPL();
        $this->initCoreObjects();
        $this->initApplications();
        $this->runBootstrappers();
    }

    protected function initSession(): void
    {
        parent::initSession();

        if (empty($_ENV['WCF_SESSION_ID'])) {
            \fwrite(\STDERR, "WCF_SESSION_ID required (admin session).\n");
            exit(1);
        }

        WCF::getSession()->delete();
        WCF::getSession()->load(SessionEditor::class, $_ENV['WCF_SESSION_ID']);

        if (!WCF::getUser()->userID) {
            \fwrite(\STDERR, "Invalid WCF_SESSION_ID.\n");
            exit(1);
        }
    }
}

new PackageInstallBootstrap();

$session = WCF::getSession();
if (
    !$session->getPermission('admin.configuration.package.canInstallPackage')
    && !$session->getPermission('admin.configuration.package.canUpdatePackage')
) {
    \fwrite(\STDERR, "Session lacks package install/update permission.\n");
    exit(1);
}

$tmpArchive = FileUtil::getTemporaryFilename(
    'package_',
    \preg_replace('!^.*(?=\.(?:tar\.gz|tgz|tar)$)!i', '', \basename($archivePath))
);

if (!@\copy($archivePath, $tmpArchive)) {
    \fwrite(\STDERR, "Failed to copy archive to temp.\n");
    exit(1);
}

if (!PackageValidationManager::getInstance()->validate($tmpArchive, false)) {
    \fwrite(\STDERR, "Package validation failed.\n");
    exit(1);
}

$validationArchive = PackageValidationManager::getInstance()->getPackageValidationArchive();
$packageInfo = $validationArchive->getArchive()->getPackageInfo('name');
$packageName = $validationArchive->getArchive()->getLocalizedPackageInfo('packageName');
$isApplication = $validationArchive->getArchive()->getPackageInfo('isApplication');

$existingPackage = null;
$sql = "SELECT * FROM wcf1_package WHERE package = ?";
$statement = WCF::getDB()->prepare($sql);
$statement->execute([$packageInfo]);
$row = $statement->fetchArray();
if ($row) {
    $existingPackage = new Package(null, $row);
}

$action = $existingPackage !== null ? 'update' : 'install';
$processNo = PackageInstallationQueue::getNewProcessNo();

$queue = PackageInstallationQueueEditor::create([
    'processNo' => $processNo,
    'userID' => WCF::getUser()->userID,
    'package' => $packageInfo,
    'packageName' => $packageName,
    'packageID' => $existingPackage?->packageID,
    'archive' => $tmpArchive,
    'action' => $action,
    'isApplication' => $isApplication ? '1' : '0',
]);

$dispatcher = new PackageInstallationDispatcher($queue);
$dispatcher->updatePackage();
$dispatcher->nodeBuilder->purgeNodes();
$dispatcher->nodeBuilder->buildNodes();

$node = $dispatcher->nodeBuilder->getNextNode();
$steps = 0;

while ($node !== '') {
    $step = $dispatcher->install($node);
    $node = $step->getNode();
    $steps++;

    if ($steps > 10000) {
        \fwrite(\STDERR, "Abort: too many installation steps.\n");
        exit(1);
    }
}

\fwrite(\STDOUT, "OK: {$action} {$packageInfo} ({$steps} steps)\n");
