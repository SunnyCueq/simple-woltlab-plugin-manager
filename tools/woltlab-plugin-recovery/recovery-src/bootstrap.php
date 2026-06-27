<?php

declare(strict_types=1);

/**
 * Einstieg des Recovery-Pakets (nach Stub: Auth + Paket-Check).
 *
 * Erwartet vom Stub gesetzte Variablen:
 * - $recoveryAuthHash (string)
 * - $recoveryIsAuthenticated (bool)
 */

if (!\defined('RECOVERY_PACKAGE_DIR')) {
    \define('RECOVERY_PACKAGE_DIR', __DIR__);
}
if (!\defined('RECOVERY_WCF_ROOT')) {
    \define('RECOVERY_WCF_ROOT', \dirname(__DIR__) . '/');
}

require __DIR__ . '/version.php';
require __DIR__ . '/paths.php';
require __DIR__ . '/lib/Recovery/Log/RecoveryLogger.php';
require __DIR__ . '/lib/Recovery/Constants.php';
require __DIR__ . '/lib/Recovery/Bootstrap/Database.php';
require __DIR__ . '/lib/Recovery/Ui/SetupAssets.php';
require __DIR__ . '/lib/Recovery/Ui/RecoveryIcons.php';
require __DIR__ . '/lib/Recovery/Ui/AcpLayout.php';
require __DIR__ . '/lib/Recovery/Ui/RecoveryAcpMarkup.php';
require __DIR__ . '/lib/Recovery/Ui/RecoveryUi.php';

$authHash = $recoveryAuthHash ?? '';
$isAuthenticated = (bool) ($recoveryIsAuthenticated ?? false);
$authFilename = 'plugin-recovery-auth.php';

$action = (!empty($_GET['action'])) ? (string) $_GET['action'] : '';

if ($action === 'download-sql') {
    $raw = $_POST['sql_b64'] ?? '';
    $sqlContent = \base64_decode(\str_replace(["\n", "\r", ' '], '', (string) $raw), true);
    if ($sqlContent === false || $sqlContent === '') {
        \http_response_code(400);
        echo 'Ungültiger Inhalt.';
        exit;
    }
    $filename = 'recovery-backup-' . \date('Y-m-d-His') . '.sql';
    \header('Content-Type: text/plain; charset=utf-8');
    \header('Content-Disposition: attachment; filename="' . \addslashes($filename) . '"');
    \header('Content-Length: ' . \strlen($sqlContent));
    echo $sqlContent;
    exit;
}

if (!$isAuthenticated) {
    \http_response_code(403);
    \header('Content-Type: text/plain; charset=utf-8');
    echo 'Nicht authentifiziert.';
    exit;
}

require __DIR__ . '/app.php';
