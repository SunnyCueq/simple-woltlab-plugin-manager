<?php
/**
 * WoltLab Suite Recovery Tool - Universal
 *
 * Vereint 4 Recovery-Modi:
 * 1. ACP Repair - Repariert defekte ACP-Menüeinträge
 * 2. Plugin Uninstall - Deinstalliert Plugins komplett
 * 3. User Management - Admin-Passwort & Berechtigungen
 * 4. Cache Clear - Löscht alle Caches und kompilierte Templates
 *
 * @author Sunny C.
 * @version 2.0.0 (package)
 * @requires PHP >= 8.1 (wie WoltLab Suite 6.x; kein künstliches 8.3-Minimum)
 *
 * Eine Datei: ins WoltLab-Hauptverzeichnis legen (neben global.php).
 * Universelles Recovery nach Stressoren wie kaputter Installation: DB gemäß WoltLab-PIP-Zuordnung,
 * Cache/Pfade aller Apps, Option-Konstanten-Fallback für sämtliche Plugins (nicht nur einzelne Pakete).
 */

// ============================================================================
// KONFIGURATION
// ============================================================================

if (!\defined('RECOVERY_PACKAGE_VERSION')) {
    require __DIR__ . '/version.php';
}
if (!\defined('RECOVERY_VERSION')) {
    \define('RECOVERY_VERSION', RECOVERY_PACKAGE_VERSION);
}
define('RECOVERY_BEER_CSS', 'https://cdn.jsdelivr.net/npm/beercss@4.0.21/dist/cdn/beer.min.css');
define('RECOVERY_BEER_JS', 'https://cdn.jsdelivr.net/npm/beercss@4.0.21/dist/cdn/beer.min.js');
define('RECOVERY_BEER_COLORS_JS', 'https://cdn.jsdelivr.net/npm/material-dynamic-colors@1.1.4/dist/cdn/material-dynamic-colors.min.js');
define('RECOVERY_MIN_PHP_VERSION', '8.1.0');

/** @deprecated Alias — siehe recoveryLogDebug() */
function recoveryAgentDebugLogBasename(): string
{
    return recoveryDebugLogBasename();
}

/** @deprecated Alias */
function recoveryAgentDebugLogPath(): string
{
    return recoveryDebugLogPath();
}

/** @deprecated Alias */
function recoveryAgentExposeDebugHeaders(): void
{
    recoveryLogExposeDebugHeaders();
}

/** @deprecated Alias */
function recoveryAgentDebugLog(string $hypothesisId, string $location, string $message, array $data = []): void
{
    recoveryLogExposeDebugHeaders();
    recoveryLogDebug($hypothesisId, $location, $message, $data);
}

\register_shutdown_function(static function (): void {
    $e = \error_get_last();
    if ($e === null) {
        return;
    }
    $fatalTypes = [\E_ERROR, \E_PARSE, \E_CORE_ERROR, \E_COMPILE_ERROR];
    if (!\in_array((int) $e['type'], $fatalTypes, true)) {
        return;
    }
    recoveryLog('error', 'PHP fatal', [
        'type' => $e['type'],
        'message' => $e['message'],
        'file' => $e['file'],
        'line' => $e['line'],
    ]);
    recoveryLogDebug('H-FATAL', 'shutdown', 'php_fatal', [
        'type' => $e['type'],
        'message' => $e['message'],
        'file' => $e['file'],
        'line' => $e['line'],
    ]);
});

recoveryLog('info', 'Recovery-Paket gestartet', [
    'version' => \defined('RECOVERY_VERSION') ? RECOVERY_VERSION : null,
    'php' => \PHP_VERSION,
]);
recoveryLogDebug('H1', 'tool:boot', 'php_version_gate_passed', [
    'phpVersion' => \PHP_VERSION,
    'sapi' => \PHP_SAPI,
    'logDir' => recoveryLogDir(),
    'debugLogPath' => recoveryDebugLogPath(),
]);

\set_exception_handler(static function (\Throwable $e): void {
    recoveryLog('error', 'Uncaught exception: ' . $e->getMessage(), [
        'class' => \get_class($e),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
    ]);
    recoveryLogDebug('H-EXCEPTION', 'tool:uncaught', 'uncaught_exception', [
        'class' => \get_class($e),
        'message' => $e->getMessage(),
        'file' => \basename($e->getFile()),
        'line' => $e->getLine(),
    ]);
    $logHint = RECOVERY_LOG_SUBDIR . '/';
    if (!\headers_sent()) {
        \http_response_code(500);
        \header('Content-Type: text/html; charset=utf-8');
        echo '<!DOCTYPE html><html lang="de"><head><meta charset="utf-8"><title>Recovery Tool – Fehler</title></head><body>';
        echo '<h1>Recovery Tool – interner Fehler</h1>';
        echo '<p>Details in <code>' . \htmlspecialchars($logHint, \ENT_QUOTES, 'UTF-8') . '</code></p>';
        echo '</body></html>';
    }
    exit(1);
});

// Konstanten + DB-Bootstrap: bootstrap.php → lib/Recovery/Constants.php, Bootstrap/Database.php

function recoveryIsDebugEnabled(): bool
{
    if (\defined('RECOVERY_ENABLE_DEBUG') && RECOVERY_ENABLE_DEBUG) {
        return true;
    }

    return isset($_GET['debug']) && $_GET['debug'] === '1';
}

/**
 * @throws \InvalidArgumentException
 */
function recoveryValidatePackageIdentifier(?string $identifier): string
{
    $identifier = \trim((string) $identifier);
    if ($identifier === '') {
        throw new \InvalidArgumentException('Bitte einen Package-Identifier angeben.');
    }
    if (\strlen($identifier) > RECOVERY_PACKAGE_ID_MAX_LEN) {
        throw new \InvalidArgumentException(
            'Package-Identifier ist zu lang (max. ' . RECOVERY_PACKAGE_ID_MAX_LEN . ' Zeichen).'
        );
    }
    if (!\preg_match(RECOVERY_PACKAGE_ID_PATTERN, $identifier)) {
        throw new \InvalidArgumentException(
            'Ungültiger Package-Identifier. Erlaubt sind Buchstaben, Ziffern, Punkt, Unterstrich und Bindestrich.'
        );
    }

    return $identifier;
}

function recoveryValidateSqlTableName(string $table): bool
{
    return (bool) \preg_match('/^[a-zA-Z0-9_]+$/', $table);
}

function recoveryValidateAppDirectoryName(string $dir): bool
{
    // Dots are not valid in WoltLab app directory names (e.g. 'wbb', 'gallery', not 'com.woltlab.wbb')
    return $dir !== '' && (bool) \preg_match('/^[a-zA-Z0-9_-]+$/', $dir);
}

/**
 * @return list<string>
 */
function recoveryGetProtectedDirectoryNames(): array
{
    return [
        // WoltLab core directories
        'wcf',
        'lib',
        'acp',
        'cache',
        'tmp',
        'templates',
        'images',
        'js',
        'style',
        'icons',
        'font',
        'fonts',
        'attachments',
        'media',
        'log',
        'language',
        // Additional protected directories (v1.2.7)
        'admin',
        'install',
        'wcfsetup',
        'setup',
        'upload',
        'uploads',
        'files',
        'core',
        'vendor',
    ];
}

/**
 * Files that must never be deleted during plugin cleanup.
 *
 * @return list<string>
 */
function recoveryGetProtectedFileNames(): array
{
    return [
        'global.php',
        'index.php',
        'config.inc.php',
        'options.inc.php',
        'constants.inc.php',
        'composer.json',
        'composer.lock',
        '.htaccess',
    ];
}

function recoveryFormatUserError(\Throwable $e, string $context = ''): string
{
    $message = $context !== '' ? $context . ': ' : '';
    $message .= $e->getMessage();

    if (recoveryIsDebugEnabled()) {
        $message .= "\n\n" . $e->getTraceAsString();
    }

    return $message;
}

function recoveryRenderExceptionDetails(\Throwable $e): void
{
    if (!recoveryIsDebugEnabled()) {
        return;
    }

    echo '<details><summary>Technische Details (Debug)</summary>';
    echo '<pre class="recoveryLog">' . \htmlspecialchars($e->getTraceAsString()) . '</pre>';
    echo '</details>';
}

function recoveryGetDatabaseSchemaName(\wcf\system\database\Database $db): string
{
    try {
        $statement = $db->prepareStatement('SELECT DATABASE() AS dbName');
        $statement->execute();
        $row = $statement->fetchArray();

        return (string) ($row['dbName'] ?? '');
    } catch (\Throwable $ignored) {
        return '';
    }
}

function recoveryIsUnsafeArchiveRelativePath(string $path): bool
{
    $path = \str_replace('\\', '/', $path);
    if ($path === '' || \str_starts_with($path, '/')) {
        return true;
    }

    foreach (\explode('/', $path) as $segment) {
        if ($segment === '..' || $segment === '') {
            return true;
        }
    }

    return false;
}

function recoveryValidateArchiveFilename(string $filename): bool
{
    return (bool) \preg_match('/\.(tar\.gz|tgz|tar)$/i', $filename);
}

/**
 * @return array{ok: bool, error?: string, packageIdentifier?: string, extractDir?: string, uploadedFile?: string}
 */
function recoveryHandlePackageUpload(array $file): array
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'error' => 'Datei-Upload fehlgeschlagen (Fehlercode ' . (int) ($file['error'] ?? 0) . ').'];
    }

    if (($file['size'] ?? 0) > RECOVERY_MAX_UPLOAD_BYTES) {
        $maxMb = (int) \round(RECOVERY_MAX_UPLOAD_BYTES / 1048576);

        return ['ok' => false, 'error' => "Die Datei ist zu groß (max. {$maxMb} MiB)."];
    }

    $originalName = \basename((string) ($file['name'] ?? ''));
    if (!recoveryValidateArchiveFilename($originalName)) {
        return ['ok' => false, 'error' => 'Ungültiges Archivformat. Erlaubt: .tar, .tar.gz, .tgz'];
    }

    $uploadDir = recoveryWcfPath('uploads');
    if (!\is_dir($uploadDir) && !@\mkdir($uploadDir, 0755, true)) {
        return ['ok' => false, 'error' => 'Upload-Verzeichnis konnte nicht erstellt werden.'];
    }

    $safeName = \preg_replace('/[^a-zA-Z0-9._-]/', '_', $originalName) ?: 'package.tar';
    $uploadedFile = $uploadDir . '/' . $safeName;
    if (!\move_uploaded_file((string) $file['tmp_name'], $uploadedFile)) {
        return ['ok' => false, 'error' => 'Datei konnte nicht gespeichert werden.'];
    }

    $extractDir = $uploadDir . '/extracted_' . \bin2hex(\random_bytes(4));
    if (!\is_dir($extractDir) && !@\mkdir($extractDir, 0755, true)) {
        @\unlink($uploadedFile);

        return ['ok' => false, 'error' => 'Entpack-Verzeichnis konnte nicht erstellt werden.'];
    }

    if (!extractArchive($uploadedFile, $extractDir)) {
        recoveryCleanupUploadWorkspace($uploadDir);

        return ['ok' => false, 'error' => 'Archiv konnte nicht entpackt werden.'];
    }

    $packageXml = findFileInExtractDir($extractDir, '', 'package.xml');
    if (!$packageXml) {
        recoveryCleanupUploadWorkspace($uploadDir);

        return ['ok' => false, 'error' => 'package.xml wurde im Archiv nicht gefunden.'];
    }

    $packageIdentifier = extractPackageIdentifier($packageXml);
    if (!$packageIdentifier) {
        recoveryCleanupUploadWorkspace($uploadDir);

        return ['ok' => false, 'error' => 'package.xml konnte nicht gelesen werden.'];
    }

    try {
        $packageIdentifier = recoveryValidatePackageIdentifier($packageIdentifier);
    } catch (\InvalidArgumentException $e) {
        recoveryCleanupUploadWorkspace($uploadDir);

        return ['ok' => false, 'error' => $e->getMessage()];
    }

    return [
        'ok' => true,
        'packageIdentifier' => $packageIdentifier,
        'extractDir' => $extractDir,
        'uploadedFile' => $uploadedFile,
    ];
}

function recoveryCleanupUploadWorkspace(?string $uploadDir = null): void
{
    $uploadDir ??= recoveryWcfPath('uploads');
    if (!\is_dir($uploadDir)) {
        return;
    }

    $iterator = new \RecursiveIteratorIterator(
        new \RecursiveDirectoryIterator($uploadDir, \RecursiveDirectoryIterator::SKIP_DOTS),
        \RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($iterator as $file) {
        $file->isDir() ? @\rmdir($file->getPathname()) : @\unlink($file->getPathname());
    }
}

/**
 * @param array<string, mixed>|null $packageData
 */
function recoveryResolvePluginDirectory(
    ?array $packageData,
    string $packageIdentifier,
    ?\wcf\system\database\Database $db = null,
    ?int $wcfN = null,
    ?string $extractDir = null
): ?string {
    if ($packageData) {
        $dir = \trim((string) ($packageData['packageDir'] ?? ''), '/\\');
        if ($dir !== '' && recoveryValidateAppDirectoryName($dir)) {
            return $dir;
        }
    }

    if ($db && $wcfN && $packageData && !empty($packageData['packageID'])) {
        try {
            $sql = "SELECT application FROM wcf{$wcfN}_application WHERE packageID = ?";
            $statement = $db->prepareStatement($sql);
            $statement->execute([(int) $packageData['packageID']]);
            $row = $statement->fetchArray();
            $application = \trim((string) ($row['application'] ?? ''));
            if ($application !== '' && recoveryValidateAppDirectoryName($application)) {
                return $application;
            }
        } catch (\Throwable $ignored) {
        }
    }

    if ($extractDir && \is_dir($extractDir)) {
        $packageXml = findFileInExtractDir($extractDir, '', 'package.xml');
        if ($packageXml) {
            $parsed = parsePackageXml($packageXml);
            $application = \trim((string) ($parsed['application'] ?? ''));
            if ($application !== '' && recoveryValidateAppDirectoryName($application)) {
                return $application;
            }
        }
    }

    $parts = \explode('.', $packageIdentifier);
    if (\count($parts) < 2) {
        return null;
    }

    $guess = (string) \end($parts);

    return recoveryValidateAppDirectoryName($guess) ? $guess : null;
}

/**
 * @return list<string>
 */
function recoveryInferAcpMenuSearchPatterns(string $packageIdentifier, ?array $resources = null): array
{
    $patterns = [];

    if ($resources && !empty($resources['acpMenu']['prefix'])) {
        $patterns[] = $resources['acpMenu']['prefix'] . '%';
    }

    if ($resources && !empty($resources['acpMenu']['items'])) {
        $prefix = extractCommonPrefix($resources['acpMenu']['items'], '.');
        if ($prefix !== '') {
            $patterns[] = $prefix . '%';
        }
    }

    $parts = \explode('.', $packageIdentifier);
    $candidates = [];
    if (\count($parts) >= 1) {
        $candidates[] = (string) \end($parts);
    }
    if (\count($parts) >= 2) {
        $candidates[] = $parts[\count($parts) - 2];
    }
    if (\count($parts) >= 3) {
        $candidates[] = $parts[\count($parts) - 3];
    }
    $candidates = \array_values(\array_unique(\array_filter($candidates)));

    foreach ($candidates as $appName) {
        $patterns[] = $appName . '.acp.menu.%';
        $patterns[] = \strtolower($appName) . '.acp.menu.%';
        $patterns[] = $packageIdentifier . '.%';
    }

    return \array_values(\array_unique($patterns));
}

/**
 * @return list<array{menuItem: string, menuItemController: string|null}>
 */
function recoveryFetchAcpMenuItemsByPatterns(
    \wcf\system\database\Database $db,
    int $wcfN,
    array $patterns
): array {
    $items = [];
    $seen = [];

    foreach ($patterns as $pattern) {
        if ($pattern === '' || \strlen($pattern) > 255) {
            continue;
        }

        $sql = "SELECT menuItem, menuItemController FROM wcf{$wcfN}_acp_menu_item WHERE menuItem LIKE ?";
        $statement = $db->prepareStatement($sql);
        $statement->execute([$pattern]);

        while ($row = $statement->fetchArray()) {
            $key = (string) $row['menuItem'];
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $items[] = $row;
        }
    }

    return $items;
}

/**
 * @return array{packageIdentifier?: string, extractDir?: string|null, error?: string}
 */
function recoveryResolvePackageInputFromRequest(string $authHash = ''): array
{
    if (recoveryHasUploadedPackageFile()) {
        $upload = recoveryHandlePackageUpload($_FILES['package_file']);

        if (!$upload['ok']) {
            return ['error' => $upload['error'] ?? 'Upload fehlgeschlagen.'];
        }

        $identifier = $upload['packageIdentifier'];
        $extractDir = $upload['extractDir'] ?? null;
        if ($authHash !== '' && $identifier) {
            recoveryStorePackageContext($authHash, $identifier, $extractDir);
        }

        return [
            'packageIdentifier' => $identifier,
            'extractDir' => $extractDir,
        ];
    }

    $raw = null;
    if (isset($_POST['package_identifier']) && \trim((string) $_POST['package_identifier']) !== '') {
        $raw = \trim((string) $_POST['package_identifier']);
    } elseif (isset($_GET['package_identifier']) && \trim((string) $_GET['package_identifier']) !== '') {
        $raw = \trim((string) $_GET['package_identifier']);
    }

    if ($raw !== null && $raw !== '') {
        try {
            $identifier = recoveryValidatePackageIdentifier($raw);
            $extractDir = recoveryResolveTrustedExtractDir($authHash);
            if ($authHash !== '') {
                recoveryStorePackageContext($authHash, $identifier, $extractDir);
            }

            return ['packageIdentifier' => $identifier, 'extractDir' => $extractDir];
        } catch (\InvalidArgumentException $e) {
            return ['error' => $e->getMessage()];
        }
    }

    if ($authHash !== '') {
        $ctx = recoveryLoadPackageContext($authHash);
        if ($ctx && !empty($ctx['packageIdentifier'])) {
            return [
                'packageIdentifier' => $ctx['packageIdentifier'],
                'extractDir' => $ctx['extractDir'] ?? recoveryResolveTrustedExtractDir($authHash),
            ];
        }
    }

    return [];
}

function recoveryWasPostTruncated(): bool
{
    return $_SERVER['REQUEST_METHOD'] === 'POST'
        && empty($_POST)
        && empty($_FILES)
        && isset($_SERVER['CONTENT_LENGTH'])
        && (int) $_SERVER['CONTENT_LENGTH'] > 0;
}

function recoveryResolveRequestMode(): int
{
    if (isset($_GET['mode'])) {
        return (int) $_GET['mode'];
    }
    if (isset($_POST['mode'])) {
        return (int) $_POST['mode'];
    }

    return RECOVERY_MODE_SELECTION;
}

function recoveryHasUploadedPackageFile(): bool
{
    return isset($_FILES['package_file'])
        && ($_FILES['package_file']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK;
}

function recoveryResolveUninstallStep(): string
{
    if (isset($_POST['uninstall_step'])) {
        return \trim((string) $_POST['uninstall_step']);
    }
    if (isset($_GET['uninstall_step'])) {
        return \trim((string) $_GET['uninstall_step']);
    }

    return '';
}

function recoverySessionBindAuthToken(string $authHash): void
{
    if (!\preg_match('~^[a-f0-9]{40}$~', $authHash)) {
        return;
    }
    recoveryEnsureSession();
    $_SESSION['recovery_auth_token'] = $authHash;
    $_SESSION['recovery_auth_bound_at'] = \time();
}

function recoveryUseSessionAuthUrls(): bool
{
    recoveryEnsureSession();
    $bound = $_SESSION['recovery_auth_token'] ?? null;

    return \is_string($bound) && $bound !== '';
}

function recoveryBuildModeUrl(int $mode, string $authHash, array $params = []): string
{
    $query = \array_merge(['mode' => $mode], $params);
    if (!recoveryUseSessionAuthUrls()) {
        $query = \array_merge(['t' => $authHash], $query);
    }

    return 'plugin-recovery-tool.php?' . \http_build_query($query);
}

function recoveryBuildHomeUrl(string $authHash, array $params = []): string
{
    $query = $params;
    if (!recoveryUseSessionAuthUrls()) {
        $query = \array_merge(['t' => $authHash], $query);
    }
    if ($query === []) {
        return 'plugin-recovery-tool.php';
    }

    return 'plugin-recovery-tool.php?' . \http_build_query($query);
}

function recoveryBuildFullAuthUrl(string $authHash, array $params = []): string
{
    $query = \array_merge(['t' => $authHash], $params);

    return 'plugin-recovery-tool.php?' . \http_build_query($query);
}

function recoveryEnsureSession(): void
{
    if (\session_status() === PHP_SESSION_NONE) {
        \session_start();
    }
}

function recoveryStorePackageContext(string $authHash, string $packageIdentifier, ?string $extractDir): void
{
    recoveryEnsureSession();
    $_SESSION['recovery_pkg'] ??= [];
    $_SESSION['recovery_pkg'][$authHash] = [
        'packageIdentifier' => $packageIdentifier,
        'extractDir' => $extractDir,
        'savedAt' => \time(),
    ];
}

/**
 * @param array<string, mixed> $data
 */
function recoverySessionSetFlash(string $authHash, string $key, array $data): void
{
    recoveryEnsureSession();
    $_SESSION['recovery_flash'] ??= [];
    $_SESSION['recovery_flash'][$authHash] ??= [];
    $_SESSION['recovery_flash'][$authHash][$key] = $data;
}

/**
 * @return array<string, mixed>|null
 */
function recoverySessionPullFlash(string $authHash, string $key): ?array
{
    recoveryEnsureSession();
    $data = $_SESSION['recovery_flash'][$authHash][$key] ?? null;
    if (\is_array($data)) {
        unset($_SESSION['recovery_flash'][$authHash][$key]);
    }

    return \is_array($data) ? $data : null;
}

function recoveryLoadPackageContext(string $authHash): ?array
{
    recoveryEnsureSession();
    $ctx = $_SESSION['recovery_pkg'][$authHash] ?? null;
    if (!$ctx || (\time() - (int) ($ctx['savedAt'] ?? 0)) > 7200) {
        return null;
    }

    if (!empty($ctx['extractDir'])) {
        $uploadBase = \realpath(recoveryWcfPath('uploads'));
        $extractReal = \realpath((string) $ctx['extractDir']);
        if (
            $uploadBase === false
            || $extractReal === false
            || !\str_starts_with($extractReal, $uploadBase . \DIRECTORY_SEPARATOR)
        ) {
            $ctx['extractDir'] = null;
        } else {
            $ctx['extractDir'] = $extractReal;
        }
    }

    return $ctx;
}

function recoveryRenderPostTruncatedWarning(): void
{
    echo '<p class="error"><strong>Upload fehlgeschlagen:</strong> '
        . 'Die Anfrage wurde vom Server abgeschnitten (post_max_size / upload_max_filesize). '
        . 'Bitte kleinere Datei wählen oder die PHP-Limits erhöhen.</p>';
}

function recoveryRenderProcessingError(\Throwable $e): void
{
    echo '<p class="error"><strong>Fehler bei der Verarbeitung:</strong> '
        . \nl2br(\htmlspecialchars(recoveryFormatUserError($e))) . '</p>';
    recoveryRenderExceptionDetails($e);
}

function recoveryRenderWoltLabUiShell(): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;
    ?>
<div id="recovery-snackbar-container" class="snackbarContainer" aria-live="polite"></div>
<dialog id="recoveryConfirmDialog" class="dialog recovery-wfl-dialog" role="alertdialog" aria-labelledby="recoveryConfirmTitle">
    <div class="dialog__document">
        <header class="dialog__header">
            <h2 class="dialog__title" id="recoveryConfirmTitle">Bestätigen</h2>
        </header>
        <div class="dialog__content" id="recoveryConfirmMessage"></div>
        <footer class="dialog__control dialog__control--duo-stacked">
            <button type="button" class="button dialog__control__button--cancel" id="recoveryConfirmCancel">Abbrechen</button>
            <button type="button" class="button button--primary dialog__control__button--primary" id="recoveryConfirmOk">Fortfahren</button>
        </footer>
    </div>
</dialog>
<script>
window.RecoveryUi = (function () {
    var snackbarContainer = null;
    var confirmDialog = null;
    var confirmMessageEl = null;
    var confirmOkBtn = null;
    var pendingConfirm = null;

    function ensureSnackbarContainer() {
        if (!snackbarContainer) {
            snackbarContainer = document.getElementById('recovery-snackbar-container');
        }
        return snackbarContainer;
    }

    function ensureConfirmDialog() {
        if (!confirmDialog) {
            confirmDialog = document.getElementById('recoveryConfirmDialog');
            confirmMessageEl = document.getElementById('recoveryConfirmMessage');
            confirmOkBtn = document.getElementById('recoveryConfirmOk');
            var confirmCancelBtn = document.getElementById('recoveryConfirmCancel');
            if (confirmOkBtn) {
                confirmOkBtn.addEventListener('click', function () {
                    if (confirmDialog) {
                        confirmDialog.close();
                    }
                    if (typeof pendingConfirm === 'function') {
                        var fn = pendingConfirm;
                        pendingConfirm = null;
                        fn();
                    }
                });
            }
            if (confirmCancelBtn) {
                confirmCancelBtn.addEventListener('click', function () {
                    pendingConfirm = null;
                    if (confirmDialog) {
                        confirmDialog.close();
                    }
                });
            }
        }
        return confirmDialog;
    }

    function createFaIcon(name, size, solid) {
        var el = document.createElement('fa-icon');
        el.setAttribute('size', String(size));
        el.setAttribute('name', name);
        if (solid) {
            el.setAttribute('solid', '');
        }
        return el;
    }

    function buildSnackbar(message, type) {
        var el = document.createElement('div');
        el.className = 'snackbar snackbar--' + (type === 'progress' ? 'progress' : 'success');
        el.setAttribute('role', 'status');
        var icon = document.createElement('div');
        icon.className = 'snackbar__icon';
        icon.append(createFaIcon(type === 'progress' ? 'spinner' : 'check', 24, true));
        var msg = document.createElement('div');
        msg.className = 'snackbar__message';
        msg.textContent = message;
        el.append(icon, msg);
        el.addEventListener('click', function () {
            if (type !== 'progress') {
                el.classList.add('snackbar--closing');
                window.setTimeout(function () { el.remove(); }, 240);
            }
        });
        return el;
    }

    function showSuccess(message) {
        var container = ensureSnackbarContainer();
        if (!container) {
            return;
        }
        var el = buildSnackbar(message, 'success');
        container.prepend(el);
        window.setTimeout(function () {
            if (el.parentNode) {
                el.classList.add('snackbar--closing');
                window.setTimeout(function () { el.remove(); }, 240);
            }
        }, 4000);
    }

    function showProgress(message) {
        var container = ensureSnackbarContainer();
        if (!container) {
            return { done: function () {}, close: function () {} };
        }
        var el = buildSnackbar(message, 'progress');
        container.prepend(el);
        return {
            done: function (successMessage) {
                el.classList.remove('snackbar--progress');
                el.classList.add('snackbar--success');
                var iconWrap = el.querySelector('.snackbar__icon');
                if (iconWrap) {
                    iconWrap.textContent = '';
                    iconWrap.append(createFaIcon('check', 24, true));
                }
                if (successMessage) {
                    var msg = el.querySelector('.snackbar__message');
                    if (msg) {
                        msg.textContent = successMessage;
                    }
                }
                window.setTimeout(function () {
                    if (el.parentNode) {
                        el.classList.add('snackbar--closing');
                        window.setTimeout(function () { el.remove(); }, 240);
                    }
                }, 3500);
            },
            close: function () {
                if (el.parentNode) {
                    el.remove();
                }
            }
        };
    }

    function confirm(message, onConfirm, options) {
        var dlg = ensureConfirmDialog();
        if (!dlg || !confirmMessageEl) {
            if (window.confirm(message)) {
                onConfirm();
            }
            return;
        }
        pendingConfirm = onConfirm;
        confirmMessageEl.textContent = message;
        var titleEl = document.getElementById('recoveryConfirmTitle');
        if (options && options.title && titleEl) {
            titleEl.textContent = options.title;
        } else if (titleEl) {
            titleEl.textContent = 'Bestätigen';
        }
        if (options && options.okLabel && confirmOkBtn) {
            confirmOkBtn.textContent = options.okLabel;
        } else if (confirmOkBtn) {
            confirmOkBtn.textContent = 'Fortfahren';
        }
        if (typeof dlg.showModal === 'function') {
            dlg.showModal();
        } else if (window.confirm(message)) {
            onConfirm();
        }
    }

    return { confirm: confirm, showSuccess: showSuccess, showProgress: showProgress };
})();
</script>
<?php
}

function recoveryRenderFlashSnackbarFromQuery(): void
{
    $key = isset($_GET['recovery_snack']) ? (string) $_GET['recovery_snack'] : '';
    if ($key === '') {
        return;
    }
    $messages = [
        'acp_ok' => 'ACP-Notfall-Reparatur abgeschlossen. Bitte ACP testen.',
    ];
    if (!isset($messages[$key])) {
        return;
    }
    $msg = $messages[$key];
    ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (window.RecoveryUi && typeof RecoveryUi.showSuccess === 'function') {
        RecoveryUi.showSuccess(<?= \json_encode($msg, \JSON_UNESCAPED_UNICODE) ?>);
    }
});
</script>
<?php
}

function recoveryFormLoadingScript(): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;
    ?>
<script>
(function () {
    var progressTimer = null;
    var progressSnackbar = null;

    function createFaIcon(name, size, solid) {
        var el = document.createElement('fa-icon');
        el.setAttribute('size', String(size));
        el.setAttribute('name', name);
        if (solid) {
            el.setAttribute('solid', '');
        }
        return el;
    }

    function hideOverlay() {
        if (progressTimer) {
            clearInterval(progressTimer);
            progressTimer = null;
        }
        var stale = document.getElementById('recovery-loading-overlay');
        if (stale) {
            stale.remove();
        }
        if (progressSnackbar && typeof progressSnackbar.close === 'function') {
            progressSnackbar.close();
            progressSnackbar = null;
        }
    }

    function showOverlay(message, stepsText) {
        var container = document.getElementById('content');
        if (!container) {
            return;
        }
        hideOverlay();
        var el = document.createElement('p');
        el.id = 'recovery-loading-overlay';
        el.className = 'info';
        var head = document.createElement('span');
        head.className = 'recovery-loading-head';
        head.append(createFaIcon('spinner', 24, true));
        var strong = document.createElement('strong');
        strong.className = 'recovery-loading-msg';
        strong.textContent = message;
        head.append(strong);
        el.append(head);
        el.append(document.createElement('br'));
        var progressSmall = document.createElement('small');
        var progressBar = document.createElement('progress');
        progressBar.id = 'recovery-loading-progress';
        progressBar.value = 0;
        progressBar.max = 100;
        progressBar.style.width = '300px';
        progressBar.textContent = '0%';
        var pctSpan = document.createElement('span');
        pctSpan.id = 'recovery-loading-pct';
        pctSpan.textContent = '0%';
        progressSmall.append(progressBar, document.createTextNode(' '), pctSpan);
        el.append(progressSmall);
        var stepsEl = document.createElement('small');
        stepsEl.id = 'recovery-loading-steps';
        stepsEl.style.display = 'block';
        stepsEl.style.marginTop = '8px';
        if (stepsText) {
            stepsEl.textContent = stepsText;
        }
        el.append(stepsEl);
        var header = container.querySelector('.contentHeader');
        if (header && header.nextSibling) {
            container.insertBefore(el, header.nextSibling);
        } else {
            container.insertBefore(el, container.firstChild);
        }
        if (window.RecoveryUi && typeof RecoveryUi.showProgress === 'function') {
            progressSnackbar = RecoveryUi.showProgress(message);
        }
        var pct = 0;
        var progressBar = el.querySelector('#recovery-loading-progress');
        var pctEl = el.querySelector('#recovery-loading-pct');
        progressTimer = setInterval(function () {
            if (pct < 96) {
                pct += pct < 50 ? 4 : (pct < 80 ? 2 : 1);
                if (progressBar) {
                    progressBar.value = String(pct);
                }
                if (pctEl) {
                    pctEl.textContent = pct + '%';
                }
            }
        }, 450);
    }

    function bindCopyButtons() {
        document.querySelectorAll('[data-recovery-copy]').forEach(function (btn) {
            if (btn.dataset.recoveryCopyBound === '1') {
                return;
            }
            btn.dataset.recoveryCopyBound = '1';
            btn.addEventListener('click', function () {
                var id = btn.getAttribute('data-recovery-copy');
                var node = id ? document.getElementById(id) : null;
                if (!node) {
                    return;
                }
                var text = node.textContent || '';
                var mergeId = btn.getAttribute('data-recovery-copy-merge');
                if (mergeId) {
                    var mergeEl = document.getElementById(mergeId);
                    var tried = mergeEl && mergeEl.value ? mergeEl.value.trim() : '';
                    if (tried !== '') {
                        text = text.replace(
                            /--- Bereits versucht ---\n[\s\S]*?(?=\n--- |\nForum:|$)/,
                            '--- Bereits versucht ---\n' + tried + '\n'
                        );
                    }
                }
                function doneOk() {
                    btn.classList.add('copied');
                    var oldHtml = btn.innerHTML;
                    btn.innerHTML = '<fa-icon size="16" name="check" solid></fa-icon> Kopiert';
                    var snackMsg = btn.getAttribute('data-recovery-snack') || 'In Zwischenablage kopiert';
                    if (window.RecoveryUi && typeof RecoveryUi.showSuccess === 'function') {
                        RecoveryUi.showSuccess(snackMsg);
                    }
                    setTimeout(function () {
                        btn.classList.remove('copied');
                        btn.innerHTML = oldHtml;
                    }, 2000);
                }
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(text).then(doneOk).catch(function () {
                        window.prompt('Kopieren (Strg+C):', text);
                    });
                } else {
                    window.prompt('Kopieren (Strg+C):', text);
                }
            });
        });
    }

    var dryRunInfoText = 'Dry-Run: Es werden keine Änderungen am Server vorgenommen. Sie sehen nur im Protokoll, was passieren würde — ideal zum Prüfen vor der echten Ausführung.';

    function formRequestsDryRun(form, submitter) {
        if (submitter && (submitter.hasAttribute('data-recovery-dry-run-info') || submitter.name === 'dry_run')) {
            return true;
        }
        if (form.hasAttribute('data-recovery-dry-run-info')) {
            return true;
        }
        var dryFields = form.querySelectorAll('input[name="wizard_dry_run"], input[name="dry_run"], input[name="backup_dry_run"]');
        for (var i = 0; i < dryFields.length; i++) {
            if (dryFields[i].checked) {
                return true;
            }
        }
        return false;
    }

    function bindFileInputs() {
        document.querySelectorAll('.recovery-file-input__native').forEach(function (input) {
            if (input.dataset.recoveryFileBound === '1') {
                return;
            }
            input.dataset.recoveryFileBound = '1';
            var label = document.querySelector('[data-recovery-file-label][for="' + input.id + '"]');
            var btn = input.parentNode ? input.parentNode.querySelector('.recovery-file-input__btn') : null;
            function syncName() {
                if (!label) {
                    return;
                }
                label.textContent = input.files && input.files.length
                    ? input.files[0].name
                    : 'Keine Datei gewählt';
            }
            input.addEventListener('change', syncName);
            if (btn) {
                btn.addEventListener('click', function () { input.click(); });
                btn.addEventListener('keydown', function (ev) {
                    if (ev.key === 'Enter' || ev.key === ' ') {
                        ev.preventDefault();
                        input.click();
                    }
                });
            }
        });
    }

    function bindForms() {
        document.querySelectorAll('form[data-recovery-loading]').forEach(function (form) {
            if (form.dataset.recoveryLoadingBound === '1') {
                return;
            }
            form.dataset.recoveryLoadingBound = '1';
            form.addEventListener('submit', function (ev) {
                var submitter = ev.submitter || null;
                if (formRequestsDryRun(form, submitter) && form.dataset.recoveryDryRunConfirmed !== '1') {
                    ev.preventDefault();
                    var doDrySubmit = function () {
                        form.dataset.recoveryDryRunConfirmed = '1';
                        if (typeof form.requestSubmit === 'function' && submitter) {
                            form.requestSubmit(submitter);
                        } else {
                            form.submit();
                        }
                    };
                    if (window.RecoveryUi && typeof RecoveryUi.confirm === 'function') {
                        RecoveryUi.confirm(dryRunInfoText, doDrySubmit, {
                            title: 'Dry-Run',
                            okLabel: 'Dry-Run starten'
                        });
                    } else if (window.confirm(dryRunInfoText)) {
                        doDrySubmit();
                    }
                    return;
                }
                var confirmMsg = form.getAttribute('data-recovery-confirm');
                if (confirmMsg && form.dataset.recoveryConfirmed !== '1') {
                    ev.preventDefault();
                    var doSubmit = function () {
                        form.dataset.recoveryConfirmed = '1';
                        showOverlay(
                            form.getAttribute('data-recovery-loading') || 'Bitte warten …',
                            form.getAttribute('data-recovery-loading-steps') || ''
                        );
                        if (typeof form.requestSubmit === 'function') {
                            form.requestSubmit();
                        } else {
                            form.submit();
                        }
                    };
                    if (window.RecoveryUi && typeof RecoveryUi.confirm === 'function') {
                        RecoveryUi.confirm(confirmMsg, doSubmit, {
                            title: form.getAttribute('data-recovery-confirm-title') || 'Bestätigen',
                            okLabel: form.getAttribute('data-recovery-confirm-ok') || 'Fortfahren'
                        });
                    } else if (window.confirm(confirmMsg)) {
                        doSubmit();
                    }
                    return;
                }
                if (form.hasAttribute('data-recovery-ajax-backup')) {
                    ev.preventDefault();
                    var fd = new FormData(form);
                    var dryChk = document.querySelector('input[name="backup_dry_run"]');
                    if (dryChk && dryChk.checked) {
                        fd.set('backup_dry_run', '1');
                    }
                    showOverlay(form.getAttribute('data-recovery-loading') || 'Backup läuft …', '');
                    fetch(form.action, { method: 'POST', body: fd, credentials: 'same-origin' })
                        .then(function (r) { return r.text(); })
                        .then(function (html) {
                            hideOverlay();
                            var box = document.getElementById('recovery-backup-ajax-result');
                            if (!box) { return; }
                            var doc = new DOMParser().parseFromString(html, 'text/html');
                            var resultSec = doc.querySelector('.recovery-backup-result, .section .success, .section .warning');
                            box.hidden = false;
                            box.innerHTML = '<strong>Ergebnis</strong><div style="margin-top:8px">' + (resultSec ? resultSec.outerHTML : 'Backup abgeschlossen — Seite für Details aktualisieren.') + '</div>';
                            if (window.RecoveryUi && typeof RecoveryUi.showSuccess === 'function') {
                                RecoveryUi.showSuccess('Backup-Anfrage abgeschlossen');
                            }
                        })
                        .catch(function () {
                            hideOverlay();
                            form.submit();
                        });
                    return;
                }
                showOverlay(
                    form.getAttribute('data-recovery-loading') || 'Bitte warten …',
                    form.getAttribute('data-recovery-loading-steps') || ''
                );
            });
        });
    }

    function bindCmdBlocks() {
        document.querySelectorAll('pre.recovery-cmd-block').forEach(function (block) {
            if (block.dataset.recoverySelectBound === '1') {
                return;
            }
            block.dataset.recoverySelectBound = '1';
            function selectAll() {
                var range = document.createRange();
                range.selectNodeContents(block);
                var sel = window.getSelection();
                if (!sel) { return; }
                sel.removeAllRanges();
                sel.addRange(range);
            }
            block.addEventListener('focus', selectAll);
            block.addEventListener('click', selectAll);
        });
    }

    function init() {
        hideOverlay();
        bindForms();
        bindFileInputs();
        bindCopyButtons();
        bindCmdBlocks();
        if (window.location.search.indexOf('expert=1') !== -1) {
            var expert = document.getElementById('recovery-expert-panel');
            if (expert) {
                expert.open = true;
            }
        }
    }

    window.addEventListener('pageshow', function (ev) {
        hideOverlay();
        if (ev.persisted) {
            document.querySelectorAll('form[data-recovery-loading]').forEach(function (form) {
                delete form.dataset.recoveryConfirmed;
                delete form.dataset.recoveryDryRunConfirmed;
            });
        }
    });

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
</script>
<?php
}

function recoveryRenderFormModeHiddenFields(int $mode, string $authHash): void
{
    echo '<input type="hidden" name="mode" value="' . (int) $mode . '">';
    if (!recoveryUseSessionAuthUrls()) {
        echo '<input type="hidden" name="t" value="' . \htmlspecialchars($authHash, ENT_QUOTES, 'UTF-8') . '">';
    }
}

function recoveryAcpShouldShowInputForm(): bool
{
    if (recoveryWasPostTruncated()) {
        return true;
    }
    if (isset($_POST['confirm_delete']) || isset($_POST['force_cleanup'])) {
        return false;
    }
    if (isset($_GET['package_identifier']) && \trim((string) $_GET['package_identifier']) !== '') {
        return false;
    }
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
        return true;
    }
    if (recoveryHasUploadedPackageFile()) {
        return false;
    }

    return !isset($_POST['package_identifier']) || \trim((string) $_POST['package_identifier']) === '';
}

/**
 * Application-Zeilen mit packageID ≤ 0 oder ohne gültiges Paket (blockiert Frontend/ACP).
 *
 * @return list<array{applicationID: int, packageID: int, application: string}>
 */
function recoveryFindBrokenApplicationRows(\wcf\system\database\Database $db, int $wcfN): array
{
    if ($wcfN < 1 || $wcfN > 99) {
        return [];
    }

    $prefix = "wcf{$wcfN}_";
    $rows = [];
    try {
        $sql = "SELECT a.applicationID, a.packageID, a.application
                FROM {$prefix}application a
                LEFT JOIN {$prefix}package p ON a.packageID = p.packageID
                WHERE a.packageID <= 0 OR p.packageID IS NULL
                ORDER BY a.applicationID";
        $statement = $db->prepareStatement($sql);
        $statement->execute();
        while ($row = $statement->fetchArray()) {
            if (!\is_array($row)) {
                continue;
            }
            $rows[] = [
                'applicationID' => (int) ($row['applicationID'] ?? 0),
                'packageID' => (int) ($row['packageID'] ?? 0),
                'application' => (string) ($row['application'] ?? ''),
            ];
        }
    } catch (\Throwable $ignored) {
    }

    return $rows;
}

/**
 * @return list<array{packageID: int, package: string, packageName: string}>
 */
function recoveryFindUninstallDbPackages(\wcf\system\database\Database $db): array
{
    $n = WCF_N;
    $rows = [];
    try {
        $sql = "SELECT packageID, package, packageName
                FROM wcf{$n}_package
                WHERE package = 'de.sunnyc.wsc.shrinkr'
                   OR package LIKE '%shrinkr%'
                ORDER BY package";
        $statement = $db->prepareStatement($sql);
        $statement->execute();
        while ($row = $statement->fetchArray()) {
            $rows[] = [
                'packageID' => (int) ($row['packageID'] ?? 0),
                'package' => (string) ($row['package'] ?? ''),
                'packageName' => (string) ($row['packageName'] ?? ''),
            ];
        }
    } catch (\Throwable $ignored) {
    }

    return $rows;
}

function recoveryUninstallShouldShowInputForm(string $authHash = ''): bool
{
    if (recoveryWasPostTruncated()) {
        return true;
    }
    if (recoveryResolveUninstallStep() !== '') {
        return false;
    }
    if (isset($_GET['package_identifier']) && \trim((string) $_GET['package_identifier']) !== '') {
        return false;
    }
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
        if ($authHash !== '') {
            $ctx = recoveryLoadPackageContext($authHash);
            if ($ctx && !empty($ctx['packageIdentifier'])) {
                return false;
            }
        }

        return true;
    }
    if (recoveryHasUploadedPackageFile()) {
        return false;
    }

    return !isset($_POST['package_identifier']) || \trim((string) $_POST['package_identifier']) === '';
}

/**
 * POST-Redirect-GET nach erfolgreicher Erstanalyse (vor HTML-Ausgabe).
 */
function recoveryMaybeRedirectUninstallAnalyse(string $authHash): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        return;
    }
    if (recoveryResolveRequestMode() !== RECOVERY_MODE_PLUGIN_UNINSTALL) {
        return;
    }
    if (recoveryResolveUninstallStep() !== '') {
        return;
    }
    if (recoveryWasPostTruncated()) {
        return;
    }
    if (!recoveryHasUploadedPackageFile()
        && (!isset($_POST['package_identifier']) || \trim((string) $_POST['package_identifier']) === '')
    ) {
        return;
    }

    $packageInput = recoveryResolvePackageInputFromRequest($authHash);
    if (isset($packageInput['error']) || empty($packageInput['packageIdentifier'])) {
        return;
    }

    $params = ['package_identifier' => $packageInput['packageIdentifier']];
    if (!empty($packageInput['extractDir'])) {
        $params['extract_dir'] = $packageInput['extractDir'];
    }

    \header('Location: ' . recoveryBuildModeUrl(RECOVERY_MODE_PLUGIN_UNINSTALL, $authHash, $params));
    exit;
}

/**
 * POST-Redirect-GET für „Anzeige aktualisieren“ im Wizard (done-Phase), vor HTML-Ausgabe.
 */
function recoveryMaybeRedirectWizardDisplayRebuild(string $authHash, \wcf\system\database\Database $db): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        return;
    }
    if (recoveryResolveRequestMode() !== RECOVERY_MODE_RECOVERY_WIZARD) {
        return;
    }
    if (empty($_POST['recovery_rebuild_display'])) {
        return;
    }
    if ((string) ($_GET['wizard_phase'] ?? $_POST['wizard_phase'] ?? '') !== 'done') {
        return;
    }

    $wcfDir = \rtrim((string) WCF_DIR, '/\\') . \DIRECTORY_SEPARATOR;
    $rebuildResult = recoveryRebuildDisplayData($wcfDir, $db, WCF_N);

    $state = recoveryWizardLoadState($authHash);
    $lastRun = $state['lastRun'] ?? null;
    if (\is_array($lastRun) && $lastRun !== [] && empty($lastRun['dryRun'])) {
        $needles = [];
        foreach (recoveryExtractUndefinedConstantsFromLog($wcfDir) as $c) {
            $needles[] = (string) ($c['fqName'] ?? '');
        }
        if ($needles !== []) {
            $lastRun['postCheck'] = recoveryWizardPostCheckLogConstants($wcfDir, $needles);
            $state['lastRun'] = $lastRun;
            recoveryWizardSaveState($authHash, $state);
        }
    }

    recoverySessionSetFlash($authHash, 'display_rebuild', $rebuildResult);
    if (\session_status() === PHP_SESSION_ACTIVE) {
        \session_write_close();
    }
    \header(
        'Location: ' . recoveryBuildModeUrl(
            RECOVERY_MODE_RECOVERY_WIZARD,
            $authHash,
            ['wizard_phase' => 'done', 'rebuilt' => '1']
        ),
        true,
        303
    );
    exit;
}

function recoveryResolveTrustedExtractDir(?string $authHash = null): ?string
{
    $postedExtract = $_POST['extract_dir'] ?? $_GET['extract_dir'] ?? null;
    if ($postedExtract) {
        $uploadBase = \realpath(recoveryWcfPath('uploads'));
        $extractReal = \realpath((string) $postedExtract);
        if (
            $uploadBase !== false
            && $extractReal !== false
            && \str_starts_with($extractReal, $uploadBase . \DIRECTORY_SEPARATOR)
            && \is_dir($extractReal)
        ) {
            return $extractReal;
        }
    }

    if ($authHash !== null && $authHash !== '') {
        $ctx = recoveryLoadPackageContext($authHash);
        if (!empty($ctx['extractDir']) && \is_dir((string) $ctx['extractDir'])) {
            return (string) $ctx['extractDir'];
        }
    }

    return null;
}

/**
 * @param array<string, mixed>|null $packageData
 * @return list<array{menuItem: string, menuItemController: string|null}>
 */
function recoveryFetchAcpMenuItemsForPackage(
    \wcf\system\database\Database $db,
    int $wcfN,
    string $packageIdentifier,
    ?array $packageData,
    ?array $resources = null
): array {
    if ($packageData && !empty($packageData['packageID'])) {
        $sql = "SELECT menuItem, menuItemController FROM wcf{$wcfN}_acp_menu_item WHERE packageID = ?";
        $statement = $db->prepareStatement($sql);
        $statement->execute([(int) $packageData['packageID']]);
        $items = [];
        while ($row = $statement->fetchArray()) {
            $items[] = $row;
        }

        return $items;
    }

    if ($resources && !empty($resources['acpMenu']['prefix'])) {
        return recoveryFetchAcpMenuItemsByPatterns($db, $wcfN, [$resources['acpMenu']['prefix'] . '%']);
    }

    return recoveryFetchAcpMenuItemsByPatterns(
        $db,
        $wcfN,
        recoveryInferAcpMenuSearchPatterns($packageIdentifier, $resources)
    );
}

/**
 * @param array<string, mixed>|null $packageData
 */
function recoveryDeleteAcpMenuItemsForPackage(
    \wcf\system\database\Database $db,
    int $wcfN,
    string $packageIdentifier,
    ?array $packageData,
    ?array $resources = null
): int {
    if ($packageData && !empty($packageData['packageID'])) {
        $sql = "DELETE FROM wcf{$wcfN}_acp_menu_item WHERE packageID = ?";
        $statement = $db->prepareStatement($sql);
        $statement->execute([(int) $packageData['packageID']]);

        return $statement->getAffectedRows();
    }

    if ($resources && !empty($resources['acpMenu']['prefix'])) {
        $sql = "DELETE FROM wcf{$wcfN}_acp_menu_item WHERE menuItem LIKE ?";
        $statement = $db->prepareStatement($sql);
        $statement->execute([$resources['acpMenu']['prefix'] . '%']);

        return $statement->getAffectedRows();
    }

    $deletedTotal = 0;
    foreach (recoveryInferAcpMenuSearchPatterns($packageIdentifier, $resources) as $pattern) {
        $sql = "DELETE FROM wcf{$wcfN}_acp_menu_item WHERE menuItem LIKE ?";
        $statement = $db->prepareStatement($sql);
        $statement->execute([$pattern]);
        $deletedTotal += $statement->getAffectedRows();
    }

    return $deletedTotal;
}

/**
 * @return array{deletable: bool, reason: string, relativePath: string|null}
 */
function recoveryEvaluatePluginDirectoryDeletion(
    ?array $packageData,
    string $packageIdentifier,
    ?\wcf\system\database\Database $db = null,
    ?int $wcfN = null,
    ?string $extractDir = null
): array {
    if (!\defined('WCF_DIR')) {
        \define('WCF_DIR', recoveryResolveWcfDir());
    }

    $appDir = recoveryResolvePluginDirectory($packageData, $packageIdentifier, $db, $wcfN, $extractDir);
    if (!$appDir) {
        return [
            'deletable' => false,
            'reason' => 'Kein Plugin-Verzeichnis ermittelt (packageDir / application / package.xml).',
            'relativePath' => null,
        ];
    }

    if (\in_array(\strtolower($appDir), recoveryGetProtectedDirectoryNames(), true)) {
        return [
            'deletable' => false,
            'reason' => 'Geschütztes WoltLab-Verzeichnis: ' . $appDir . '/',
            'relativePath' => $appDir,
        ];
    }

    $wcfRoot = \rtrim(WCF_DIR, '/\\');
    $target = $wcfRoot . '/' . $appDir;

    if (!\is_dir($target)) {
        return [
            'deletable' => false,
            'reason' => 'Verzeichnis nicht vorhanden: ' . $appDir . '/',
            'relativePath' => $appDir,
        ];
    }

    $wcfReal = \realpath($wcfRoot);
    $targetReal = \realpath($target);
    if (
        $wcfReal === false
        || $targetReal === false
        || (!\str_starts_with($targetReal, $wcfReal . \DIRECTORY_SEPARATOR) && $targetReal !== $wcfReal)
    ) {
        return [
            'deletable' => false,
            'reason' => 'Sicherheitsprüfung fehlgeschlagen (Pfad außerhalb von WCF_DIR).',
            'relativePath' => $appDir,
        ];
    }

    return [
        'deletable' => true,
        'reason' => 'Wird entfernt: ' . $appDir . '/',
        'relativePath' => $appDir,
    ];
}


/**
 * @return list<string>
 */
function recoveryCollectOptionConstantNames(\wcf\system\database\Database $db, int $wcfN, ?int $packageID): array
{
    if (!$packageID) {
        return [];
    }

    $constants = [];
    $sql = "SELECT optionName FROM wcf{$wcfN}_option WHERE packageID = ?";
    $statement = $db->prepareStatement($sql);
    $statement->execute([$packageID]);
    while ($row = $statement->fetchArray()) {
        $constants[] = \strtoupper((string) $row['optionName']);
    }

    return $constants;
}

function recoveryDefineMinimalWcfConstants(): void
{
    if (\defined('CACHE_SOURCE_TYPE')) {
        return;
    }

    if (\defined('WCF_DIR') && \is_file(WCF_DIR . 'options.inc.php')) {
        require_once WCF_DIR . 'options.inc.php';
    }

    if (!\defined('CACHE_SOURCE_TYPE')) {
        \define('CACHE_SOURCE_TYPE', 'disk');
    }
}

/**
 * Minimaler WCF-Hilfsfunktionen-Stub-Pfad (ohne global.php / core.functions.php).
 * Database::prepareStatement() ruft bei ENABLE_PRODUCTION_DEBUG_MODE \wcf\getRequestId() auf –
 * wird durch frühes ENABLE_PRODUCTION_DEBUG_MODE=false in recoveryBootstrapDatabase() vermieden.
 */
function recoveryDefineMinimalWcfFunctions(): void
{
    if (!\defined('ENABLE_PRODUCTION_DEBUG_MODE')) {
        \define('ENABLE_PRODUCTION_DEBUG_MODE', false);
    }
}

function recoveryRebuildOptionsIncPhp(): bool
{
    try {
        recoveryDefineMinimalWcfConstants();
        recoveryDefineMinimalWcfFunctions();
        require_once WCF_DIR . 'lib/data/option/OptionEditor.class.php';
        \wcf\data\option\OptionEditor::rebuild();

        return true;
    } catch (\Throwable $ignored) {
        return false;
    }
}

/**
 * @param list<string> $constantNames
 */
function recoveryStripConstantsFromOptionsIncPhp(array $constantNames): void
{
    if (empty($constantNames)) {
        return;
    }

    $file = WCF_DIR . 'options.inc.php';
    if (!\is_file($file) || !\is_writable($file)) {
        return;
    }

    $lines = \file($file, FILE_IGNORE_NEW_LINES);
    if ($lines === false) {
        return;
    }

    $filtered = [];
    foreach ($lines as $line) {
        $remove = false;
        foreach ($constantNames as $constant) {
            if ($constant !== '' && \str_contains($line, $constant)) {
                $remove = true;
                break;
            }
        }
        if (!$remove) {
            $filtered[] = $line;
        }
    }

    \file_put_contents($file, \implode("\n", $filtered) . "\n");
}

/**
 * WoltLab speichert Optionen als Konstanten (UPPERCASE, Punkt zu Unterstrich). Kompilierte ACP-Templates
 * können diese Konstanten ohne defined()-Check nutzen → PHP 8+: Fatal Error wenn options.inc.php
 * unvollständig oder nach beschädigter Deinstallation ohne Option-Zeilen.
 */
function recoveryOptionNameToConstant(string $optionName): string
{
    return \strtoupper(\str_replace('.', '_', $optionName));
}

/**
 * Ausgabe eines Skalars für \define(, …) ohne abschließendes Semikolon.
 */
function recoveryPhpScalarExpressionForOptionType(string $optionType, string $optionValue): string
{
    $t = \strtolower($optionType);

    if ($t === 'boolean' || \str_ends_with($t, 'boolean')) {
        $on = ($optionValue === '1' || \strtolower($optionValue) === 'true'
            || \strtolower($optionValue) === 'yes' || \strtolower($optionValue) === 'on');

        return $on ? '1' : '0';
    }

    if (\str_contains($t, 'integer') || $t === 'negativeinteger' || \str_contains($t, 'bigint')) {
        return (string) (int) $optionValue;
    }

    if (\str_contains($t, 'float') || $t === 'currency'
        || $t === 'number' || \str_contains($t, 'fraction')) {
        $f = (float) $optionValue;
        if (!\is_finite($f)) {
            return '0.0';
        }

        return \rtrim(\rtrim(\sprintf('%.8F', $f), '0'), '.');
    }

    return \var_export((string) $optionValue, true);
}

/**
 * PHP-Schlüsselwörter und typisches Rauschen in kompilierten Templates (keine Plugin-Option-Konstanten).
 *
 * @return array<string, true>
 */
function recoveryGetCompiledTemplateConstantIgnoreList(): array
{
    static $set = null;
    if ($set !== null) {
        return $set;
    }

    $keys = [
        'ABSTRACT', 'ARRAY', 'AS', 'BREAK', 'CALLABLE', 'CASE', 'CATCH', 'CLASS', 'CLONE', 'CONST',
        'CONTINUE', 'DECLARE', 'DEFAULT', 'DIE', 'DO', 'ECHO', 'ELSE', 'ELSEIF', 'EMPTY', 'ENDDECLARE',
        'ENDFOR', 'ENDFOREACH', 'ENDIF', 'ENDSWITCH', 'ENDWHILE', 'ENUM', 'EVAL', 'EXIT', 'EXTENDS',
        'FALSE', 'FINAL', 'FINALLY', 'FN', 'FOR', 'FOREACH', 'FUNCTION', 'GLOBAL', 'GOTO', 'IF',
        'IMPLEMENTS', 'INCLUDE', 'INCLUDE_ONCE', 'INSTANCEOF', 'INSTEADOF', 'INTERFACE', 'ISSET',
        'ITERABLE', 'LIST', 'MATCH', 'MIXED', 'NAMESPACE', 'NEW', 'NULL', 'OBJECT', 'PARENT',
        'PRINT', 'PRIVATE', 'PROTECTED', 'PUBLIC', 'READONLY', 'REQUIRE', 'REQUIRE_ONCE', 'RESOURCE',
        'RETURN', 'SELF', 'STATIC', 'STRING', 'SWITCH', 'THROW', 'TRAIT', 'TRUE', 'TRY', 'UNSET',
        'USE', 'VAR', 'VOID', 'WHILE', 'YIELD', 'YIELD_FROM',
        'HTML', 'SESSION', 'COOKIE', 'REQUEST', 'RESPONSE', 'TEMPLATE', 'LANGUAGE', 'EXCEPTION',
        'CALLBACK', 'CONTEXT', 'HANDLER', 'LISTENER', 'CONTROLLER', 'ACTION', 'PARAMETER',
        'ATTRIBUTE', 'SANITIZE', 'SANITIZED', 'UNSUPPORTED', 'UNKNOWN', 'DEFAULTS', 'BOOLEAN',
        'DOUBLE', 'INTEGER', 'NUMBER', 'PACKAGE', 'HEADER', 'FOOTER', 'STRINGUTIL', 'ARRAYLIST',
        'BASELINE', 'PIPELINE', 'MIDDLEWARE', 'REDIRECT', 'LOCATION', 'SECURITY', 'SIGNATURE',
        'INTERNAL', 'EXTERNAL', 'PRIMARY', 'SECONDARY', 'OFFSETGET', 'OFFSETSET', 'OFFSETUNSET',
        'SERIALIZE', 'UNSERIALIZE', 'INVOKABLE', 'BACKTRACE', 'FILENAME', 'LINENUMBER',
    ];

    $set = \array_fill_keys($keys, true);

    return $set;
}

/**
 * Einzelner Kennzeichner für PHP define('…') mit möglichen Backslashes im Namen (namespaced Konstanten).
 */
function recoveryPhpSingleQuotedDefineNameLiteral(string $name): string
{
    return "'" . \str_replace(['\\', "'"], ['\\\\', "\\'"], $name) . "'";
}

/**
 * Erstes Segment OPTION_KONSTANTE → Kleinbuchstaben (z. B. SHRINKR_ACTIVE → shrinkr), nur bei Unterstrich.
 */
function recoveryLeadingPrefixSegmentLowerFromConstant(string $constant): ?string
{
    if (!\str_contains($constant, '_')) {
        return null;
    }
    $seg = \explode('_', $constant, 2)[0];
    if ($seg === '' || !\preg_match('/^[A-Z][A-Z0-9]*$/', $seg)) {
        return null;
    }

    return \strtolower($seg);
}

/**
 * Liest aus App-Unterverzeichnissen …/lib (rekursiv, PHP-Dateien) Namespace-Zeilen (Plugin-neutral).
 *
 * @return list<string>
 */
function recoveryDiscoverPhpNamespacesInApplicationLibs(string $wcfRoot, int $maxPhpFiles): array
{
    $found = [];
    $filesRead = 0;
    $protectedDirs = \array_flip(recoveryGetProtectedDirectoryNames());
    $wcfRoot = \rtrim(\str_replace('\\', '/', $wcfRoot), '/') . '/';

    foreach (\scandir($wcfRoot) ?: [] as $entry) {
        if ($entry === '.' || $entry === '..') {
            continue;
        }
        if (!recoveryValidateAppDirectoryName($entry) || isset($protectedDirs[$entry])) {
            continue;
        }

        $libDir = $wcfRoot . $entry . '/lib';
        if (!\is_dir($libDir)) {
            continue;
        }

        try {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator(
                    $libDir,
                    \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::CURRENT_AS_PATHNAME
                ),
                \RecursiveIteratorIterator::LEAVES_ONLY
            );
            foreach ($iterator as $pathname) {
                if (!\is_string($pathname) || !\is_file($pathname)) {
                    continue;
                }
                if (\strcasecmp((string) \pathinfo($pathname, \PATHINFO_EXTENSION), 'php') !== 0) {
                    continue;
                }

                $head = @\file_get_contents($pathname, false, null, 0, 12288);
                if ($head === false || $head === '') {
                    continue;
                }
                if (\preg_match('/^\s*namespace\s+([a-zA-Z0-9_\\\\]+)\s*;/m', $head, $matches)) {
                    $found[$matches[1]] = true;
                }
                $filesRead++;
                if ($filesRead >= $maxPhpFiles) {
                    return \array_keys($found);
                }
            }
        } catch (\Throwable $ignored) {
        }
    }

    return \array_keys($found);
}

/**
 * Namespaces, deren Root-Segment (vor erstem \\) zum Konstanten-Präfix passt (shrinkr ↔ shrinkr\\system\\…).
 *
 * @param list<string> $namespaces
 * @return list<string>
 */
function recoveryNamespacesWhoseRootMatchesPrefix(array $namespaces, string $prefixLower): array
{
    $out = [];
    foreach ($namespaces as $ns) {
        $root = \strtolower(\explode('\\', $ns, 2)[0]);
        if ($root === $prefixLower) {
            $out[] = $ns;
        }
    }

    return $out;
}

/**
 * Namespace-Spiegelung nur für Plugin-artige Konstanten (z. B. SHRINKR_* → shrinkr\\…).
 * {@see WCF_*} liefert Präfix „wcf“ — dann würden tausende {@see define()} ins gesamte Core-Namespace
 * geschrieben (Kapazität/Konflikte/Parse-Zeit). Dieselben Ausschlüsse wie bei gefährlichen Globals.
 */
function recoveryShouldEmitNamespaceMirrorDefines(string $constant): bool
{
    if (\str_starts_with($constant, 'WCF_') || \str_starts_with($constant, 'PHP_')
        || \str_starts_with($constant, 'MYSQL_') || \str_starts_with($constant, 'PDO')) {
        return false;
    }

    return true;
}

/**
 * @return list<string>
 */
function recoveryCollectCompiledPhpTemplatePaths(string $wcfRoot, int $maxFiles): array
{
    $paths = [];
    $gatherCap = \max($maxFiles * 5, 1200);

    foreach (recoveryGetFilesystemCacheDirectoryList($wcfRoot) as $dir) {
        $norm = \str_replace('\\', '/', $dir);
        if (!\str_contains($norm, 'templates/compiled')) {
            continue;
        }
        if (!\is_dir($dir)) {
            continue;
        }

        try {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::CURRENT_AS_PATHNAME),
                \RecursiveIteratorIterator::LEAVES_ONLY
            );
            foreach ($iterator as $pathname) {
                if (!\is_string($pathname) || !\is_file($pathname)) {
                    continue;
                }
                if (\strcasecmp((string) \pathinfo($pathname, \PATHINFO_EXTENSION), 'php') !== 0) {
                    continue;
                }
                $paths[] = $pathname;
                if (\count($paths) >= $gatherCap) {
                    break 2;
                }
            }
        } catch (\Throwable $ignored) {
        }
    }

    $scorePath = static function (string $p): int {
        $b = \strtolower(\basename(\str_replace('\\', '/', $p)));
        $s = 4;
        if (\str_contains($b, 'index')) {
            $s -= 3;
        }
        if (\str_contains($b, 'package')) {
            $s -= 2;
        }
        if (\str_contains($b, 'option')) {
            $s -= 1;
        }

        return $s;
    };

    \usort($paths, static function ($a, $b) use ($scorePath): int {
        return $scorePath((string) $a) <=> $scorePath((string) $b);
    });

    if (\count($paths) > $maxFiles) {
        $paths = \array_slice($paths, 0, $maxFiles);
    }

    return $paths;
}

/**
 * Grobes Pattern wie bei WoltLab-Option-Konstanten in Templates (mindestens 6 Zeichen).
 *
 * @return list<string>
 */
function recoveryExtractCandidateConstantsFromPhpSource(string $source): array
{
    if (!\preg_match_all('/\b([A-Z][A-Z0-9_]{5,})\b/', $source, $matches)) {
        return [];
    }

    return $matches[1];
}

function recoveryShouldIgnoreDiscoveredTemplateConstant(string $constant): bool
{
    if ($constant === '' || !\preg_match('/^[A-Z][A-Z0-9_]+$/', $constant)) {
        return true;
    }
    if (\strlen($constant) > 120) {
        return true;
    }
    if (\str_starts_with($constant, 'WCF_') || \str_starts_with($constant, 'PHP_')
        || \str_starts_with($constant, 'MYSQL_') || \str_starts_with($constant, 'PDO')) {
        return true;
    }

    return isset(recoveryGetCompiledTemplateConstantIgnoreList()[$constant]);
}

/**
 * Skalar für „verwaiste“ Konstanten (kein Eintrag mehr in wcf_option), nach Namensheuristik.
 * Schützt das ACP vor Fatal Errors – konservativ (eher 0/leerer String).
 */
function recoveryHeuristicScalarExpressionForOrphanPluginConstant(string $constant): string
{
    if (\preg_match('/_VERSION$/', $constant)) {
        return \var_export('0.0.0', true);
    }
    if (\preg_match('/_PATTERN$/', $constant)) {
        return \var_export('[a-zA-Z0-9_-]{1,64}', true);
    }

    $upper = \strtoupper($constant);
    foreach (['_ENABLED', '_ACTIVE', '_DISABLE', '_VISIBLE', '_ALLOW', '_DENY', '_REQUIRED', '_OPTIONAL', '_DEBUG', '_SHOW', '_HIDE', '_FREE', '_CONFIRM', '_MUST'] as $needle) {
        if (\str_contains($upper, $needle)) {
            return '0';
        }
    }
    foreach (['_URL', '_PATH', '_DIR', '_URI', '_HTML', '_TEXT', '_MESSAGE', '_PREFIX', '_SUFFIX', '_TOKEN', '_HASH', '_ICON', '_CSS', '_JS', '_KEY', '_SECRET', '_EMAIL', '_TITLE', '_DESCRIPTION', '_BODY'] as $needle) {
        if (\str_contains($upper, $needle)) {
            return \var_export('', true);
        }
    }
    foreach (['_COUNT', '_LENGTH', '_LIMIT', '_SIZE', '_TIME', '_DELAY', '_PORT', '_MIN', '_MAX', '_STEP', '_WIDTH', '_HEIGHT', '_TOTAL', '_OFFSET', '_INDEX', '_NUMBER'] as $needle) {
        if (\str_contains($upper, $needle)) {
            return '0';
        }
    }

    return '0';
}

/**
 * Versucht Option-Zeile aus Konstantennamen (Konvention: CONSTANT ↔ option_name kleingeschrieben).
 *
 * @return array{optionValue: string, optionType: string}|null
 */
function recoveryTryFetchOptionRowForConstantGuess(
    \wcf\system\database\Database $db,
    int $wcfN,
    string $constant
): ?array {
    $guess = \strtolower($constant);
    if ($guess === '' || !\preg_match('/^[a-z0-9._]+$/', $guess)) {
        return null;
    }

    try {
        $statement = $db->prepareStatement(
            "SELECT optionValue, optionType FROM wcf{$wcfN}_option WHERE optionName = ?"
        );
        $statement->execute([$guess]);
        $row = $statement->fetchArray();

        return \is_array($row) ? $row : null;
    } catch (\Throwable $ignored) {
        return null;
    }
}

/**
 * Findet in kompilierten WoltLab-Templates vorkommende Kandidaten-Konstanten (plugin-neutral).
 *
 * @return list<string>
 */
function recoveryDiscoverOrphanOptionLikeConstantsFromCompiledTemplates(
    string $wcfRoot,
    int $maxFiles,
    int $maxBytesPerFile,
    array &$detailLog
): array {
    $detailLog = [];
    $paths = recoveryCollectCompiledPhpTemplatePaths($wcfRoot, $maxFiles);
    $detailLog[] = 'Template-Konstanten-Scan: ' . \count($paths) . ' PHP-Dateien unter templates/compiled';

    $found = [];
    foreach ($paths as $path) {
        $content = @\file_get_contents($path, false, null, 0, $maxBytesPerFile);
        if ($content === false || $content === '') {
            continue;
        }
        foreach (recoveryExtractCandidateConstantsFromPhpSource($content) as $c) {
            if (recoveryShouldIgnoreDiscoveredTemplateConstant($c)) {
                continue;
            }
            $found[$c] = true;
        }
    }

    $list = \array_keys($found);
    \sort($list);

    return $list;
}

function recoveryStripPluginRecoveryOptionFallbackBlock(): void
{
    if (!\defined('WCF_DIR')) {
        \define('WCF_DIR', recoveryResolveWcfDir());
    }

    $file = WCF_DIR . 'options.inc.php';
    if (!\is_file($file) || !\is_readable($file)) {
        return;
    }

    $content = (string) \file_get_contents($file);
    $pattern = '~// <plugin-recovery-tool> option constant fallbacks begin.*// <plugin-recovery-tool> option constant fallbacks end\s*~sU';
    $trimmed = \preg_replace($pattern, '', $content);
    if ($trimmed !== null && $trimmed !== $content) {
        \file_put_contents($file, \rtrim($trimmed) . "\n");
    }
}

/**
 * Schreibt einen markierten Fallback-Block in options.inc.php – **plugin-neutral**:
 * 1) alle Zeilen aus {@see wcf{N}_option} (Core + sämtliche Plugins),
 * 2) Konstanten aus kompilierten Templates (Heuristik),
 * 3) zusätzlich **namespaced** {@see define()} für PHP 8 (unqualifizierte Konstanten im Namespace `foo\\bar`
 *    lösen zu `foo\\bar\\CONST`; globales define('CONST') reicht dann nicht — daher Spiegelung nach Präfix-Match).
 * Keine Spiegelung für {@see WCF_*}/{@see PHP_*}/MySQL/PDO (Core-Globals; Präfix „wcf“ würde massenhaft Core-Namespaces treffen).
 */
function recoveryEnsureOptionConstantFallbacks(\wcf\system\database\Database $db, int $wcfN, array &$log): void
{
    if (!\defined('WCF_DIR')) {
        \define('WCF_DIR', recoveryResolveWcfDir());
    }

    $file = WCF_DIR . 'options.inc.php';
    if (!\is_file($file)) {
        $log[] = 'Option-Konstanten-Fallback: options.inc.php nicht gefunden';

        return;
    }
    if (!\is_writable($file)) {
        $log[] = 'Option-Konstanten-Fallback: options.inc.php nicht beschreibbar';

        return;
    }

    recoveryStripPluginRecoveryOptionFallbackBlock();

    /** @var array<string, string> $globalExpr Konstantenname → PHP-Skalarausdruck für define */
    $globalExpr = [];
    $dbBackedCount = 0;

    try {
        $statement = $db->prepareStatement("SELECT optionName, optionValue, optionType FROM wcf{$wcfN}_option");
        $statement->execute();
        while ($row = $statement->fetchArray()) {
            $optionName = (string) ($row['optionName'] ?? '');
            if ($optionName === '' || !\preg_match('/^[a-zA-Z0-9._]+$/', $optionName)) {
                continue;
            }
            $const = recoveryOptionNameToConstant($optionName);
            if ($const === '' || !\preg_match('/^[A-Z0-9_]+$/', $const)) {
                continue;
            }
            if (isset($globalExpr[$const])) {
                continue;
            }
            $type = (string) ($row['optionType'] ?? 'text');
            $value = isset($row['optionValue']) ? (string) $row['optionValue'] : '';

            $globalExpr[$const] = recoveryPhpScalarExpressionForOptionType($type, $value);
            $dbBackedCount++;
        }
    } catch (\Throwable $e) {
        $log[] = 'Option-Konstanten-Fallback: Lesen aus wcf' . $wcfN . '_option fehlgeschlagen (' . $e->getMessage()
            . ') – es werden nur Template-Scan/Fallbacks geschrieben';
    }

    $wcfRoot = \rtrim(WCF_DIR, '/\\') . \DIRECTORY_SEPARATOR;
    $scanDetail = [];
    $orphanCandidates = recoveryDiscoverOrphanOptionLikeConstantsFromCompiledTemplates(
        $wcfRoot,
        450,
        131072,
        $scanDetail
    );
    foreach ($scanDetail as $detailLine) {
        $log[] = 'Option-Konstanten-Fallback: ' . $detailLine;
    }

    $logConstantCount = 0;
    foreach (recoveryExtractUndefinedConstantsFromLog($wcfRoot) as $logConst) {
        $global = (string) ($logConst['globalName'] ?? '');
        if ($global === '' || isset($globalExpr[$global])) {
            continue;
        }
        $guessRow = recoveryTryFetchOptionRowForConstantGuess($db, $wcfN, $global);
        $globalExpr[$global] = \is_array($guessRow)
            ? recoveryPhpScalarExpressionForOptionType(
                (string) ($guessRow['optionType'] ?? 'text'),
                isset($guessRow['optionValue']) ? (string) $guessRow['optionValue'] : ''
            )
            : recoveryHeuristicScalarExpressionForOrphanPluginConstant($global);
        $logConstantCount++;
    }
    if ($logConstantCount > 0) {
        $log[] = 'Option-Konstanten-Fallback: ' . $logConstantCount . ' Konstante(n) aus WoltLab-Log (Undefined constant) ergänzt';
    }

    $templateScanCount = 0;
    $maxOrphanDefines = 300;
    foreach ($orphanCandidates as $const) {
        if (isset($globalExpr[$const])) {
            continue;
        }
        if ($templateScanCount >= $maxOrphanDefines) {
            $log[] = 'Option-Konstanten-Fallback: Template-Scan nach ' . $maxOrphanDefines
                . ' Zusatzkonstanten gestoppt (Obergrenze; Installation hat sehr viele Kandidaten)';

            break;
        }

        $guessRow = recoveryTryFetchOptionRowForConstantGuess($db, $wcfN, $const);
        if (\is_array($guessRow)) {
            $expr = recoveryPhpScalarExpressionForOptionType(
                (string) ($guessRow['optionType'] ?? 'text'),
                isset($guessRow['optionValue']) ? (string) $guessRow['optionValue'] : ''
            );
        } else {
            $expr = recoveryHeuristicScalarExpressionForOrphanPluginConstant($const);
        }

        $globalExpr[$const] = $expr;
        $templateScanCount++;
    }

    $lines = [];
    $lines[] = '// <plugin-recovery-tool> option constant fallbacks begin';
    $lines[] = '// Notfall-Fallback für fehlende Option-Konstanten (Recovery Tool ' . RECOVERY_VERSION . ').';
    $lines[] = '// Alle Plugins: DB + Template-Scan + Namespace-Spiegelung (PHP 8). Block beim nächsten Lauf ersetzt.';

    $sortedConstants = \array_keys($globalExpr);
    \sort($sortedConstants);

    foreach ($sortedConstants as $const) {
        $expr = $globalExpr[$const];
        $lines[] = "if (!\\defined('" . $const . "')) {\n\t\\define('" . $const . "', " . $expr . ');' . "\n}";
    }

    $libNamespaces = recoveryDiscoverPhpNamespacesInApplicationLibs($wcfRoot, 550);
    $log[] = 'Option-Konstanten-Fallback: Namespace-Spiegelung — ' . \count($libNamespaces)
        . ' PHP-Namespaces unter App-lib/ (Präfix-Match zur Konstante, z. B. shrinkr ↔ shrinkr\\\\…)';

    /** @var array<string, true> $fqSeen */
    $fqSeen = [];
    $mirrorCount = 0;
    $maxMirror = 650;
    $maxMirrorPerConstant = 48;
    foreach ($sortedConstants as $const) {
        if (!recoveryShouldEmitNamespaceMirrorDefines($const)) {
            continue;
        }
        $pfx = recoveryLeadingPrefixSegmentLowerFromConstant($const);
        if ($pfx === null) {
            continue;
        }
        $expr = $globalExpr[$const];
        $mirroredForConst = 0;
        foreach (recoveryNamespacesWhoseRootMatchesPrefix($libNamespaces, $pfx) as $ns) {
            if ($mirrorCount >= $maxMirror) {
                $log[] = 'Option-Konstanten-Fallback: Namespace-Spiegelung nach ' . $maxMirror . ' defines gestoppt (Obergrenze)';

                break 2;
            }
            if ($mirroredForConst >= $maxMirrorPerConstant) {
                break;
            }
            $fq = $ns . '\\' . $const;
            if (\strlen($fq) > 240 || isset($fqSeen[$fq])) {
                continue;
            }
            $fqSeen[$fq] = true;
            $lit = recoveryPhpSingleQuotedDefineNameLiteral($fq);
            $lines[] = 'if (!\\defined(' . $lit . ')) {' . "\n\t\\define(" . $lit . ', ' . $expr . ');' . "\n}";
            $mirrorCount++;
            $mirroredForConst++;
        }
    }

    $lines[] = '// <plugin-recovery-tool> option constant fallbacks end';

    $snippet = "\n" . \implode("\n", $lines) . "\n";
    \file_put_contents($file, \rtrim((string) \file_get_contents($file)) . $snippet, \LOCK_EX);

    $totalGlobals = \count($globalExpr);
    $log[] = 'Option-Konstanten-Fallback: options.inc.php ergänzt (globale Konstanten: ' . $totalGlobals
        . ', davon ' . $dbBackedCount . ' aus DB, ' . $templateScanCount . ' aus Template-Scan; '
        . 'Namespace-Spiegel: ' . $mirrorCount . ')';
}

function recoveryExecuteDelete(
    \wcf\system\database\Database $db,
    string $sql,
    array $parameters,
    string $logLabel,
    array &$log
): void {
    $statement = $db->prepareStatement($sql);
    $statement->execute($parameters);
    $log[] = $logLabel . ': ' . $statement->getAffectedRows();
}

function recoveryTryDeleteByPackageId(
    \wcf\system\database\Database $db,
    int $wcfN,
    string $tableName,
    int $packageID,
    string $logLabel,
    array &$log
): void {
    try {
        recoveryExecuteDelete(
            $db,
            "DELETE FROM wcf{$wcfN}_{$tableName} WHERE packageID = ?",
            [$packageID],
            $logLabel,
            $log
        );
    } catch (\Throwable $e) {
        $log[] = $logLabel . ' übersprungen: ' . $e->getMessage();
    }
}

function recoveryTryExecuteDelete(
    \wcf\system\database\Database $db,
    string $sql,
    array $parameters,
    string $logLabel,
    array &$log
): void {
    try {
        recoveryExecuteDelete($db, $sql, $parameters, $logLabel, $log);
    } catch (\Throwable $e) {
        $log[] = $logLabel . ' übersprungen: ' . $e->getMessage();
    }
}

function recoveryTryDeletePackageRequirements(
    \wcf\system\database\Database $db,
    int $wcfN,
    int $packageID,
    array &$log
): void {
    recoveryTryExecuteDelete(
        $db,
        "DELETE FROM wcf{$wcfN}_package_requirement WHERE packageID = ? OR requirement = ?",
        [$packageID, $packageID],
        'Package-Requirements',
        $log
    );
}

/**
 * @param array<string, mixed> $row
 * @return list<string>
 */
function recoveryGuessTableLabelColumns(array $row): array
{
    $preferred = [
        'title', 'name', 'menuItem', 'optionName', 'objectType', 'identifier',
        'package', 'templateName', 'cronjobName', 'eventName', 'languageItem',
    ];
    $cols = [];
    foreach ($preferred as $key) {
        if (\array_key_exists($key, $row)) {
            $cols[] = $key;
        }
    }
    if ($cols === []) {
        foreach (\array_keys($row) as $key) {
            if ($key === 'packageID' || \str_ends_with($key, 'ID')) {
                continue;
            }
            $cols[] = $key;
            if (\count($cols) >= 4) {
                break;
            }
        }
    }

    return \array_slice($cols, 0, 5);
}

/**
 * @param mixed $value
 * @return mixed
 */
function recoverySanitizeJsonValue($value)
{
    if ($value === null || \is_bool($value) || \is_int($value) || \is_float($value)) {
        return $value;
    }
    if (\is_string($value)) {
        if (!\mb_check_encoding($value, 'UTF-8')) {
            $value = \mb_convert_encoding($value, 'UTF-8', 'ISO-8859-1');
        }
        if (\strlen($value) > 8192) {
            return \substr($value, 0, 8192) . '… [' . \strlen($value) . ' Zeichen]';
        }

        return $value;
    }
    if (\is_array($value)) {
        return '[Array]';
    }

    return (string) $value;
}

/**
 * @param array<string, mixed> $row
 * @return array<string, mixed>
 */
function recoverySanitizeRowForJson(array $row): array
{
    $out = [];
    foreach ($row as $key => $value) {
        $out[(string) $key] = recoverySanitizeJsonValue($value);
    }

    return $out;
}

function recoveryJsonResponse(array $data, int $statusCode = 200): void
{
    while (\ob_get_level() > 0) {
        \ob_end_clean();
    }

    $flags = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_INVALID_UTF8_SUBSTITUTE;
    $json = \json_encode($data, $flags);
    if ($json === false) {
        $json = \json_encode(['ok' => false, 'error' => 'JSON-Ausgabe fehlgeschlagen: ' . \json_last_error_msg()], $flags);
        $statusCode = 500;
    }

    \http_response_code($statusCode);
    \header('Content-Type: application/json; charset=utf-8');
    \header('Cache-Control: no-store');
    echo $json;
    exit;
}

function recoveryFormatUserGroupLabel(int $groupID, string $groupName): string
{
    $known = [
        1 => 'Everyone (Alle)',
        2 => 'Registered Users (Registrierte)',
        3 => 'Moderators (Moderatoren)',
        4 => 'Administrators (Administratoren)',
        5 => 'Guests (Gäste)',
        6 => 'Super-Moderators',
    ];
    if (isset($known[$groupID])) {
        return $known[$groupID];
    }

    if (\str_contains($groupName, '.')) {
        return $groupName . ' <small style="color:#9D9D9D">(Sprachvariable)</small>';
    }

    return $groupName;
}

/**
 * Pip-Vorschau: erste Zeilen einer Tabelle mit packageID (AJAX).
 *
 * @return array{columns: list<string>, rows: list<array<string, mixed>>, total: int, table: string, error?: string}
 */
function recoveryFetchPackageIdTablePreview(
    \wcf\system\database\Database $db,
    int $wcfN,
    string $tableName,
    int $packageID,
    int $limit = 30
): array {
    $tableName = \str_replace('`', '', $tableName);
    if (!recoveryValidateSqlTableName($tableName) || $packageID <= 0) {
        return ['columns' => [], 'rows' => [], 'total' => 0, 'table' => $tableName];
    }

    $tableFull = "wcf{$wcfN}_{$tableName}";
    $total = 0;

    try {
        $countStmt = $db->prepareStatement("SELECT COUNT(*) AS cnt FROM {$tableFull} WHERE packageID = ?");
        $countStmt->execute([$packageID]);
        $total = (int) ($countStmt->fetchArray()['cnt'] ?? 0);

        $stmt = $db->prepareStatement("SELECT * FROM {$tableFull} WHERE packageID = ? LIMIT " . (int) $limit);
        $stmt->execute([$packageID]);
        $rows = [];
        while ($row = $stmt->fetchArray()) {
            $rows[] = recoverySanitizeRowForJson($row);
        }

        $columns = $rows !== [] ? recoveryGuessTableLabelColumns($rows[0]) : [];

        return [
            'columns' => $columns,
            'rows' => $rows,
            'total' => $total,
            'table' => $tableFull,
        ];
    } catch (\Throwable $e) {
        return [
            'columns' => [],
            'rows' => [],
            'total' => 0,
            'table' => $tableFull,
            'error' => $e->getMessage(),
        ];
    }
}

/**
 * @return list<string> Tabellennamen ohne wcf{N}_-Präfix
 */
function recoveryDiscoverPackageIdTables(\wcf\system\database\Database $db, int $wcfN): array
{
    if ($wcfN < 1 || $wcfN > 99) {
        return [];
    }

    $schema = recoveryGetDatabaseSchemaName($db);
    if ($schema === '') {
        return [];
    }

    $prefix = "wcf{$wcfN}_";
    $tables = [];

    try {
        $sql = 'SELECT TABLE_NAME FROM information_schema.COLUMNS
                WHERE TABLE_SCHEMA = ? AND TABLE_NAME LIKE ? AND COLUMN_NAME = ?';
        $statement = $db->prepareStatement($sql);
        $statement->execute([$schema, $prefix . '%', 'packageID']);

        while ($row = $statement->fetchArray()) {
            $fullName = (string) ($row['TABLE_NAME'] ?? '');
            if (!\str_starts_with($fullName, $prefix)) {
                continue;
            }

            $shortName = \substr($fullName, \strlen($prefix));
            if ($shortName === '' || $shortName === 'package' || !recoveryValidateSqlTableName($shortName)) {
                continue;
            }

            $tables[] = $shortName;
        }
    } catch (\Throwable $ignored) {
    }

    return \array_values(\array_unique($tables));
}

function recoveryDeleteDirectoryRecursive(string $directory): bool
{
    if (!\is_dir($directory)) {
        return false;
    }

    $iterator = new \RecursiveIteratorIterator(
        new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS),
        \RecursiveIteratorIterator::CHILD_FIRST
    );

    foreach ($iterator as $file) {
        if ($file->isDir()) {
            @\rmdir($file->getPathname());
        } else {
            @\unlink($file->getPathname());
        }
    }

    return @\rmdir($directory);
}

function recoveryDeletePluginFilesOnDisk(
    ?array $packageData,
    string $packageIdentifier,
    array &$log,
    bool $performDelete = false,
    ?\wcf\system\database\Database $db = null,
    ?int $wcfN = null,
    ?string $extractDir = null
): void {
    $evaluation = recoveryEvaluatePluginDirectoryDeletion(
        $packageData,
        $packageIdentifier,
        $db,
        $wcfN,
        $extractDir
    );

    if (!$evaluation['deletable']) {
        $log[] = 'Dateisystem: ' . $evaluation['reason'];

        return;
    }

    $appDir = (string) $evaluation['relativePath'];
    if (!$performDelete) {
        $log[] = 'Dateisystem (Vorschau): ' . $evaluation['reason'];

        return;
    }

    if (!\defined('WCF_DIR')) {
        \define('WCF_DIR', recoveryResolveWcfDir());
    }

    $targetReal = \realpath(\rtrim(WCF_DIR, '/\\') . '/' . $appDir);
    if ($targetReal === false || !\is_dir($targetReal)) {
        $log[] = 'Dateisystem: Verzeichnis nicht mehr vorhanden (' . $appDir . '/)';

        return;
    }

    if (recoveryDeleteDirectoryRecursive($targetReal)) {
        $log[] = 'Dateisystem gelöscht: ' . $appDir . '/';
    } else {
        $log[] = 'Dateisystem: Verzeichnis konnte nicht vollständig gelöscht werden (' . $appDir . '/)';
    }
}

/**
 * @return array{rows: list<array{label: string, count: int, error?: string}>, dropTables: list<string>}
 */
function recoveryPreviewDbCleanupByPackageId(
    \wcf\system\database\Database $db,
    int $wcfN,
    int $packageID,
    string $packageIdentifier
): array {
    $rows = [];
    foreach (recoveryDiscoverPackageIdTables($db, $wcfN) as $table) {
        try {
            $sql = "SELECT COUNT(*) AS cnt FROM wcf{$wcfN}_{$table} WHERE packageID = ?";
            $statement = $db->prepareStatement($sql);
            $statement->execute([$packageID]);
            $row = $statement->fetchArray();
            $count = (int) ($row['cnt'] ?? 0);
            if ($count > 0) {
                $rows[] = ['label' => $table, 'count' => $count];
            }
        } catch (\Throwable $e) {
            $rows[] = ['label' => $table, 'count' => -1, 'error' => $e->getMessage()];
        }
    }

    $dropTables = \function_exists('findPackageTables')
        ? findPackageTables($db, $packageIdentifier, $wcfN)
        : [];

    return ['rows' => $rows, 'dropTables' => $dropTables];
}

function recoveryDisplayDbCleanupPreview(
    \wcf\system\database\Database $db,
    int $wcfN,
    array $packageData,
    string $packageIdentifier,
    ?string $extractDir = null
): void {
    $packageID = (int) $packageData['packageID'];
    $preview = recoveryPreviewDbCleanupByPackageId($db, $wcfN, $packageID, $packageIdentifier);

    echo '<p class="info"><strong>Datenbank-Bereinigung (Package-ID ' . $packageID . '):</strong><br>';
    echo '<small>Auch ohne Package-Archiv werden alle wcf' . $wcfN . '_*-Tabellen mit Spalte <code>packageID</code> bereinigt.</small><br><br>';

    if (!empty($preview['rows'])) {
        echo '<ul>';
        foreach ($preview['rows'] as $row) {
            if (isset($row['error'])) {
                echo '<li><code>wcf' . $wcfN . '_' . \htmlspecialchars($row['label']) . '</code> – Prüfung fehlgeschlagen</li>';
            } else {
                echo '<li><code>wcf' . $wcfN . '_' . \htmlspecialchars($row['label']) . '</code> – '
                    . (int) $row['count'] . ' Einträge</li>';
            }
        }
        echo '</ul>';
    } else {
        echo '<em>Keine packageID-verknüpften Einträge in anderen Tabellen gefunden.</em><br>';
    }

    echo '<br><strong>Package-Eintrag:</strong> wcf' . $wcfN . '_package (1 Zeile)<br>';

    if (!empty($preview['dropTables'])) {
        echo '<br><strong>Zusätzlich DROP TABLE (' . \count($preview['dropTables']) . '):</strong><br><ul>';
        foreach ($preview['dropTables'] as $table) {
            echo '<li><code>' . \htmlspecialchars($table) . '</code></li>';
        }
        echo '</ul>';
    }

    $fsEval = recoveryEvaluatePluginDirectoryDeletion(
        $packageData,
        $packageIdentifier,
        $db,
        $wcfN,
        $extractDir
    );
    echo '<br><strong>Dateisystem:</strong> ';
    if ($fsEval['relativePath']) {
        echo '<code>' . \htmlspecialchars((string) $fsEval['relativePath']) . '/</code> – ';
    }
    echo \htmlspecialchars($fsEval['reason']);
    if ($fsEval['deletable']) {
        echo '<br><small>Entfernung nur nach expliziter Bestätigung im Deinstallationsformular.</small>';
    }

    if ($packageID > 0) {
        $filePaths = recoveryLoadPackageFileLogPaths($db, $wcfN, $packageID);
        echo '<br><br><strong>package_installation_file_log:</strong> ' . \count($filePaths) . ' Datei(en)';
        $sqlPreview = recoveryPreviewSqlRollback($db, $wcfN, $packageID);
        if ($sqlPreview['actions'] !== []) {
            echo '<br><strong>SQL-Rollback (optional):</strong> ' . \count($sqlPreview['actions']) . ' Aktion(en) möglich';
        }
    }

    echo '</div>';
}

function recoverySafeCommitTransaction(\wcf\system\database\Database $db): void
{
    try {
        $db->commitTransaction();
    } catch (\Throwable $e) {
        // MySQL beendet Transaktionen bei DDL (DROP TABLE) implizit.
        if (
            \str_contains($e->getMessage(), 'no active transaction')
            || \str_contains($e->getMessage(), 'Could not commit transaction')
        ) {
            return;
        }

        throw $e;
    }
}

function recoverySafeRollBackTransaction(\wcf\system\database\Database $db): void
{
    try {
        $db->rollBackTransaction();
    } catch (\Throwable $ignored) {
    }
}

/**
 * @return list<int>
 */
function recoveryFetchQueueIdsForPackage(
    \wcf\system\database\Database $db,
    int $wcfN,
    ?int $packageID,
    string $packageIdentifier
): array {
    $queueIds = [];

    try {
        $sql = "SELECT queueID FROM wcf{$wcfN}_package_installation_queue WHERE package = ?";
        $params = [$packageIdentifier];
        if ($packageID !== null) {
            $sql .= ' OR packageID = ?';
            $params[] = $packageID;
        }

        $statement = $db->prepareStatement($sql);
        $statement->execute($params);

        while ($row = $statement->fetchArray()) {
            $queueIds[] = (int) $row['queueID'];
        }
    } catch (\Throwable $ignored) {
    }

    return \array_values(\array_unique($queueIds));
}

/**
 * Entfernt Installations-/Deinstallations-Warteschlangen inkl. Knoten (generisch).
 */
function recoveryCleanupPackageInstallationArtifacts(
    \wcf\system\database\Database $db,
    int $wcfN,
    ?int $packageID,
    string $packageIdentifier,
    array &$log
): void {
    $queueIds = recoveryFetchQueueIdsForPackage($db, $wcfN, $packageID, $packageIdentifier);

    if (!empty($queueIds)) {
        $placeholders = \implode(',', \array_fill(0, \count($queueIds), '?'));

        recoveryExecuteDelete(
            $db,
            "DELETE FROM wcf{$wcfN}_package_installation_node WHERE queueID IN ({$placeholders})",
            $queueIds,
            'Package-Installationsknoten',
            $log
        );

        recoveryExecuteDelete(
            $db,
            "DELETE FROM wcf{$wcfN}_package_installation_form WHERE queueID IN ({$placeholders})",
            $queueIds,
            'Package-Installationsformulare',
            $log
        );
    }

    recoveryExecuteDelete(
        $db,
        "DELETE FROM wcf{$wcfN}_package_installation_queue WHERE package = ?"
            . ($packageID !== null ? ' OR packageID = ?' : ''),
        $packageID !== null ? [$packageIdentifier, $packageID] : [$packageIdentifier],
        'Installationsqueue',
        $log
    );
}

/**
 * Entfernt Update-Metadaten für ein Package (package_update / *_version per CASCADE).
 */
function recoveryCleanupPackageUpdateEntries(
    \wcf\system\database\Database $db,
    int $wcfN,
    string $packageIdentifier,
    array &$log
): void {
    recoveryExecuteDelete(
        $db,
        "DELETE FROM wcf{$wcfN}_package_update WHERE package = ?",
        [$packageIdentifier],
        'Package-Updates',
        $log
    );
}

/**
 * Bereinigt verwaiste Package-Referenzen (ACP-Paketliste, hängende Deinstallation).
 *
 * @return array{log: list<string>, sql: string}
 */
function recoveryRepairOrphanedPackageReferences(
    \wcf\system\database\Database $db,
    int $wcfN
): array {
    if ($wcfN < 1 || $wcfN > 99) {
        throw new \InvalidArgumentException('Ungültige WCF-Instanznummer.');
    }

    $log = [];
    $prefix = "wcf{$wcfN}_";

    recoveryExecuteDelete(
        $db,
        "DELETE FROM {$prefix}application WHERE packageID <= 0",
        [],
        'Applications mit ungültiger packageID (≤ 0)',
        $log
    );

    // Verwaiste Applications (PackageListPage: getPackage() → null; Frontend: package id unknown)
    recoveryExecuteDelete(
        $db,
        "DELETE a FROM {$prefix}application a
         LEFT JOIN {$prefix}package p ON a.packageID = p.packageID
         WHERE p.packageID IS NULL",
        [],
        'Verwaiste Applications',
        $log
    );

    // Verwaiste Installationsqueue (packageID zeigt auf gelöschtes Paket)
    $orphanQueueIds = [];
    try {
        $sql = "SELECT q.queueID FROM {$prefix}package_installation_queue q
                LEFT JOIN {$prefix}package p ON q.packageID = p.packageID
                WHERE q.packageID IS NOT NULL AND p.packageID IS NULL";
        $statement = $db->prepareStatement($sql);
        $statement->execute();

        while ($row = $statement->fetchArray()) {
            $orphanQueueIds[] = (int) $row['queueID'];
        }
    } catch (\Throwable $e) {
        $log[] = 'Verwaiste Queue-Prüfung übersprungen: ' . $e->getMessage();
    }

    if (!empty($orphanQueueIds)) {
        $placeholders = \implode(',', \array_fill(0, \count($orphanQueueIds), '?'));

        recoveryExecuteDelete(
            $db,
            "DELETE FROM {$prefix}package_installation_node WHERE queueID IN ({$placeholders})",
            $orphanQueueIds,
            'Verwaiste Installationsknoten',
            $log
        );

        recoveryExecuteDelete(
            $db,
            "DELETE FROM {$prefix}package_installation_form WHERE queueID IN ({$placeholders})",
            $orphanQueueIds,
            'Verwaiste Installationsformulare',
            $log
        );

        recoveryExecuteDelete(
            $db,
            "DELETE FROM {$prefix}package_installation_queue WHERE queueID IN ({$placeholders})",
            $orphanQueueIds,
            'Verwaiste Installationsqueue',
            $log
        );
    }

    recoveryExecuteDelete(
        $db,
        "DELETE r FROM {$prefix}package_requirement r
         LEFT JOIN {$prefix}package p ON r.packageID = p.packageID
         WHERE p.packageID IS NULL",
        [],
        'Verwaiste Package-Requirements (packageID)',
        $log
    );

    recoveryExecuteDelete(
        $db,
        "DELETE r FROM {$prefix}package_requirement r
         LEFT JOIN {$prefix}package p ON r.requirement = p.packageID
         WHERE p.packageID IS NULL",
        [],
        'Verwaiste Package-Requirements (requirement)',
        $log
    );

    recoveryExecuteDelete(
        $db,
        "DELETE e FROM {$prefix}package_exclusion e
         LEFT JOIN {$prefix}package p ON e.packageID = p.packageID
         WHERE p.packageID IS NULL",
        [],
        'Verwaiste Package-Exclusions',
        $log
    );

    recoveryExecuteDelete(
        $db,
        "DELETE l FROM {$prefix}package_installation_file_log l
         LEFT JOIN {$prefix}package p ON l.packageID = p.packageID
         WHERE p.packageID IS NULL",
        [],
        'Verwaiste Package-File-Logs',
        $log
    );

    recoveryExecuteDelete(
        $db,
        "DELETE pl FROM {$prefix}package_installation_plugin pl
         LEFT JOIN {$prefix}package p ON pl.packageID = p.packageID
         WHERE p.packageID IS NULL",
        [],
        'Verwaiste Package-Installation-Plugins',
        $log
    );

    return [
        'log' => $log,
        'sql' => recoveryGenerateOrphanRepairSql($wcfN),
    ];
}

function recoveryGenerateOrphanRepairSql(int $wcfN): string
{
    $p = "wcf{$wcfN}_";

    return <<<SQL
-- WoltLab Recovery Tool: Paketliste reparieren (verwaiste Referenzen)
-- WCF_N: {$wcfN} – vor Ausführung Backup anlegen!

-- ACP/Frontend: kaputte application (packageID 0 blockiert WCF::initApplications)
DELETE FROM {$p}application WHERE packageID <= 0;

-- ACP-Paketliste: verwaiste application ohne Package-Zeile
DELETE a FROM {$p}application a
LEFT JOIN {$p}package p ON a.packageID = p.packageID
WHERE p.packageID IS NULL;

-- Hängende Deinstallation / Installation (z. B. packageID 3 oder 4 fehlt)
DELETE n FROM {$p}package_installation_node n
INNER JOIN {$p}package_installation_queue q ON n.queueID = q.queueID
LEFT JOIN {$p}package p ON q.packageID = p.packageID
WHERE q.packageID IS NOT NULL AND p.packageID IS NULL;

DELETE f FROM {$p}package_installation_form f
INNER JOIN {$p}package_installation_queue q ON f.queueID = q.queueID
LEFT JOIN {$p}package p ON q.packageID = p.packageID
WHERE q.packageID IS NOT NULL AND p.packageID IS NULL;

DELETE q FROM {$p}package_installation_queue q
LEFT JOIN {$p}package p ON q.packageID = p.packageID
WHERE q.packageID IS NOT NULL AND p.packageID IS NULL;

DELETE r FROM {$p}package_requirement r
LEFT JOIN {$p}package p ON r.packageID = p.packageID
WHERE p.packageID IS NULL;

DELETE r FROM {$p}package_requirement r
LEFT JOIN {$p}package p ON r.requirement = p.packageID
WHERE p.packageID IS NULL;

DELETE e FROM {$p}package_exclusion e
LEFT JOIN {$p}package p ON e.packageID = p.packageID
WHERE p.packageID IS NULL;

SQL;
}

// ============================================================================
// v1.7.0 – Uninstall-Script, file_log, SQL-Rollback, Bootstrap-Rebuild
// ============================================================================

function recoveryPackageAbbreviation(string $package): string
{
    $parts = \explode('.', $package);

    return (string) \array_pop($parts);
}

/**
 * @return array<string, string> application abbreviation => absolute directory with trailing slash
 */
function recoveryBuildApplicationDirectoryMap(\wcf\system\database\Database $db, int $wcfN): array
{
    if (!\defined('WCF_DIR')) {
        \define('WCF_DIR', recoveryResolveWcfDir());
    }

    $map = ['wcf' => \rtrim(WCF_DIR, '/\\') . '/'];

    try {
        $sql = "SELECT p.package, p.packageDir
                FROM wcf{$wcfN}_application a
                INNER JOIN wcf{$wcfN}_package p ON a.packageID = p.packageID";
        $statement = $db->prepareStatement($sql);
        $statement->execute();
        while ($row = $statement->fetchArray()) {
            $abbr = recoveryPackageAbbreviation((string) ($row['package'] ?? ''));
            $packageDir = \trim((string) ($row['packageDir'] ?? ''), '/\\');
            if ($abbr === '' || $packageDir === '') {
                continue;
            }
            $map[$abbr] = \rtrim(WCF_DIR, '/\\') . '/' . $packageDir . '/';
        }
    } catch (\Throwable $ignored) {
    }

    return $map;
}

function recoveryExecutePackageUninstallScript(string $packageIdentifier, array &$log, bool $dryRun = false): bool
{
    if (!\defined('WCF_DIR')) {
        \define('WCF_DIR', recoveryResolveWcfDir());
    }

    $packageIdentifier = recoveryValidatePackageIdentifier($packageIdentifier);
    $script = \rtrim(WCF_DIR, '/\\') . '/acp/uninstall/' . $packageIdentifier . '.php';

    if (!\is_file($script)) {
        $log[] = 'Uninstall-Script: keine Datei ' . $packageIdentifier . '.php';

        return true;
    }

    if ($dryRun) {
        $log[] = '[DRY-RUN] WÜRDE Uninstall-Script ausführen: acp/uninstall/' . $packageIdentifier . '.php';

        return true;
    }

    try {
        include $script;
        $log[] = 'Uninstall-Script ausgeführt: acp/uninstall/' . $packageIdentifier . '.php';

        return true;
    } catch (\Throwable $e) {
        $log[] = 'Uninstall-Script fehlgeschlagen: ' . $e->getMessage();

        return false;
    }
}

/**
 * @return list<string>
 */
function recoveryGetDatabaseTableNames(\wcf\system\database\Database $db, int $wcfN): array
{
    $names = [];
    try {
        $statement = $db->prepareStatement('SHOW TABLES LIKE ?');
        $statement->execute(['wcf' . $wcfN . '_%']);
        while ($row = $statement->fetchArray()) {
            $value = \reset($row);
            if (\is_string($value) && $value !== '') {
                $names[] = $value;
            }
        }
    } catch (\Throwable $ignored) {
    }

    return $names;
}

/**
 * @return list<array{sqlTable: string, sqlColumn: string, sqlIndex: string, isIndex: int, isColumn: int, isForeignKey: int}>
 */
function recoveryFetchSqlLogEntries(\wcf\system\database\Database $db, int $wcfN, int $packageID): array
{
    $sql = "SELECT sqlTable, sqlColumn, sqlIndex,
                   CASE WHEN sqlIndex <> '' THEN 1 ELSE 0 END AS isIndex,
                   CASE WHEN sqlColumn <> '' THEN 1 ELSE 0 END AS isColumn,
                   CASE WHEN SUBSTRING(sqlIndex, -3) = '_fk' THEN 1 ELSE 0 END AS isForeignKey
            FROM wcf{$wcfN}_package_installation_sql_log
            WHERE packageID = ?
            ORDER BY isIndex DESC, isForeignKey DESC, sqlIndex, isColumn DESC, sqlColumn";
    $statement = $db->prepareStatement($sql);
    $statement->execute([$packageID]);
    $entries = [];
    while ($row = $statement->fetchArray()) {
        $entries[] = $row;
    }

    return $entries;
}

/**
 * @return array{actions: list<string>, warnings: list<string>}
 */
function recoveryPreviewSqlRollback(\wcf\system\database\Database $db, int $wcfN, int $packageID): array
{
    $actions = [];
    $warnings = [];
    $entries = recoveryFetchSqlLogEntries($db, $wcfN, $packageID);
    if ($entries === []) {
        return ['actions' => [], 'warnings' => ['Kein SQL-Log für dieses Paket.']];
    }

    $existing = recoveryGetDatabaseTableNames($db, $wcfN);

    foreach ($entries as $entry) {
        $table = (string) ($entry['sqlTable'] ?? '');
        $column = (string) ($entry['sqlColumn'] ?? '');
        $index = (string) ($entry['sqlIndex'] ?? '');

        if ($column !== '') {
            $isDropped = false;
            foreach ($entries as $entry2) {
                if (
                    $table === (string) ($entry2['sqlTable'] ?? '')
                    && ($entry2['sqlColumn'] ?? '') === ''
                    && ($entry2['sqlIndex'] ?? '') === ''
                ) {
                    $isDropped = true;
                }
            }
            if ($isDropped) {
                continue;
            }
        }

        if ($table !== '' && $column === '' && $index === '') {
            $actions[] = 'DROP TABLE `' . $table . '`';
        } elseif (\in_array($table, $existing, true) && $column !== '') {
            $actions[] = 'ALTER TABLE `' . $table . '` DROP COLUMN `' . $column . '`';
        } elseif (\in_array($table, $existing, true) && $index !== '') {
            if (\str_ends_with($index, '_fk')) {
                $actions[] = 'ALTER TABLE `' . $table . '` DROP FOREIGN KEY `' . $index . '`';
            } else {
                $actions[] = 'ALTER TABLE `' . $table . '` DROP INDEX `' . $index . '`';
            }
        }
    }

    if (\count($actions) > 0) {
        $warnings[] = 'Schema-Änderungen sind destruktiv. Vorher Datenbank-Backup anlegen.';
    }

    return ['actions' => $actions, 'warnings' => $warnings];
}

function recoveryExecuteSqlRollback(
    \wcf\system\database\Database $db,
    int $wcfN,
    int $packageID,
    array &$log,
    bool $dryRun = false
): void {
    $preview = recoveryPreviewSqlRollback($db, $wcfN, $packageID);
    $pfx = $dryRun ? '[DRY-RUN] ' : '';

    if ($preview['actions'] === []) {
        $log[] = $pfx . 'SQL-Rollback: keine Aktionen im Log.';

        return;
    }

    foreach ($preview['warnings'] as $warning) {
        $log[] = $pfx . 'SQL-Rollback Hinweis: ' . $warning;
    }

    if ($dryRun) {
        foreach ($preview['actions'] as $action) {
            $log[] = $pfx . 'WÜRDE: ' . $action;
        }

        return;
    }

    $entries = recoveryFetchSqlLogEntries($db, $wcfN, $packageID);
    $existing = recoveryGetDatabaseTableNames($db, $wcfN);

    foreach ($entries as $entry) {
        $table = (string) ($entry['sqlTable'] ?? '');
        $column = (string) ($entry['sqlColumn'] ?? '');
        $index = (string) ($entry['sqlIndex'] ?? '');

        if ($column !== '') {
            $isDropped = false;
            foreach ($entries as $entry2) {
                if (
                    $table === (string) ($entry2['sqlTable'] ?? '')
                    && ($entry2['sqlColumn'] ?? '') === ''
                    && ($entry2['sqlIndex'] ?? '') === ''
                ) {
                    $isDropped = true;
                }
            }
            if ($isDropped) {
                continue;
            }
        }

        try {
            if ($table !== '' && $column === '' && $index === '') {
                $stmt = $db->prepareStatement('DROP TABLE IF EXISTS `' . \str_replace('`', '', $table) . '`');
                $stmt->execute();
                $log[] = 'SQL-Rollback: DROP TABLE ' . $table;
            } elseif (\in_array($table, $existing, true) && $column !== '') {
                $safeTable = \str_replace('`', '', $table);
                $safeColumn = \str_replace('`', '', $column);
                $stmt = $db->prepareStatement('ALTER TABLE `' . $safeTable . '` DROP COLUMN `' . $safeColumn . '`');
                $stmt->execute();
                $log[] = 'SQL-Rollback: Spalte ' . $table . '.' . $column . ' entfernt';
            } elseif (\in_array($table, $existing, true) && $index !== '') {
                $safeTable = \str_replace('`', '', $table);
                $safeIndex = \str_replace('`', '', $index);
                if (\str_ends_with($safeIndex, '_fk')) {
                    $stmt = $db->prepareStatement('ALTER TABLE `' . $safeTable . '` DROP FOREIGN KEY `' . $safeIndex . '`');
                } else {
                    $stmt = $db->prepareStatement('ALTER TABLE `' . $safeTable . '` DROP INDEX `' . $safeIndex . '`');
                }
                $stmt->execute();
                $log[] = 'SQL-Rollback: Index ' . $table . '.' . $index . ' entfernt';
            }
        } catch (\Throwable $e) {
            $log[] = 'SQL-Rollback fehlgeschlagen (' . $table . '): ' . $e->getMessage();
        }
    }

    recoveryTryExecuteDelete(
        $db,
        "DELETE FROM wcf{$wcfN}_package_installation_sql_log WHERE packageID = ?",
        [$packageID],
        'Package SQL-Log (nach Rollback)',
        $log
    );
}

/**
 * @return list<string> relative paths (forward slashes)
 */
function recoveryLoadPackageFileLogPaths(\wcf\system\database\Database $db, int $wcfN, int $packageID): array
{
    $paths = [];
    try {
        $sql = "SELECT application, filename FROM wcf{$wcfN}_package_installation_file_log WHERE packageID = ?";
        $statement = $db->prepareStatement($sql);
        $statement->execute([$packageID]);
        while ($row = $statement->fetchArray()) {
            $application = (string) ($row['application'] ?? 'wcf');
            $filename = (string) ($row['filename'] ?? '');
            if ($filename === '') {
                continue;
            }
            $paths[] = $application . '/' . \ltrim(\str_replace('\\', '/', $filename), '/');
        }
    } catch (\Throwable $ignored) {
    }

    return $paths;
}

function recoveryResolveFileLogAbsolutePath(string $wcfDir, string $application, string $filename, array $appMap): ?string
{
    $filename = \ltrim(\str_replace('\\', '/', $filename), '/');
    if ($filename === '' || \str_contains($filename, '..')) {
        return null;
    }

    $base = $appMap[$application] ?? $appMap['wcf'] ?? null;
    if ($base === null) {
        return null;
    }

    $absolute = \rtrim($base, '/\\') . '/' . $filename;
    $wcfReal = \realpath(\rtrim($wcfDir, '/\\'));
    $fileReal = \realpath($absolute);
    if ($wcfReal === false) {
        return null;
    }
    if ($fileReal !== false) {
        if (!\str_starts_with($fileReal, $wcfReal . \DIRECTORY_SEPARATOR) && $fileReal !== $wcfReal) {
            return null;
        }

        return $fileReal;
    }

    $candidate = \rtrim($base, '/\\') . '/' . $filename;
    $prefix = \rtrim($wcfDir, '/\\') . '/';
    if (!\str_starts_with(\str_replace('\\', '/', $candidate), \str_replace('\\', '/', $prefix))) {
        return null;
    }

    return $candidate;
}

function recoveryDeletePackageFilesFromLog(
    \wcf\system\database\Database $db,
    int $wcfN,
    int $packageID,
    array &$log,
    bool $performDelete = false,
    bool $dryRun = false
): void {
    if (!\defined('WCF_DIR')) {
        \define('WCF_DIR', recoveryResolveWcfDir());
    }

    $wcfDir = \rtrim(WCF_DIR, '/\\') . '/';
    $appMap = recoveryBuildApplicationDirectoryMap($db, $wcfN);
    $pfx = $dryRun ? '[DRY-RUN] ' : '';

    try {
        $sql = "SELECT application, filename FROM wcf{$wcfN}_package_installation_file_log WHERE packageID = ?";
        $statement = $db->prepareStatement($sql);
        $statement->execute([$packageID]);
        $filesByApp = [];
        while ($row = $statement->fetchArray()) {
            $app = (string) ($row['application'] ?? 'wcf');
            $fn = (string) ($row['filename'] ?? '');
            if ($fn === '') {
                continue;
            }
            $filesByApp[$app][] = $fn;
        }
    } catch (\Throwable $e) {
        $log[] = $pfx . 'File-Log: Lesen fehlgeschlagen – ' . $e->getMessage();

        return;
    }

    if ($filesByApp === []) {
        $log[] = $pfx . 'File-Log: keine Einträge für packageID ' . $packageID;

        return;
    }

    $total = 0;
    foreach ($filesByApp as $filenames) {
        $total += \count($filenames);
    }

    if ($dryRun || !$performDelete) {
        $shown = 0;
        foreach ($filesByApp as $application => $filenames) {
            \usort($filenames, static fn(string $a, string $b): int => \strlen($b) <=> \strlen($a));
            foreach ($filenames as $filename) {
                if ($shown >= 20) {
                    break 2;
                }
                $abs = recoveryResolveFileLogAbsolutePath($wcfDir, $application, $filename, $appMap);
                $log[] = $pfx . 'File-Log' . ($performDelete ? '' : ' (Vorschau)')
                    . ': ' . ($abs !== null ? $abs : $application . '/' . $filename);
                $shown++;
            }
        }
        if ($total > 20) {
            $log[] = $pfx . 'File-Log: … und ' . ($total - 20) . ' weitere Datei(en)';
        }
        $log[] = $pfx . 'File-Log gesamt: ' . $total . ' Datei(en)';

        return;
    }

    $deleted = 0;
    foreach ($filesByApp as $application => $filenames) {
        \usort($filenames, static fn(string $a, string $b): int => \strlen($b) <=> \strlen($a));
        foreach ($filenames as $filename) {
            $abs = recoveryResolveFileLogAbsolutePath($wcfDir, $application, $filename, $appMap);
            if ($abs === null || !\is_file($abs)) {
                continue;
            }
            if (@\unlink($abs)) {
                $deleted++;
            }
        }
    }

    $log[] = 'File-Log: ' . $deleted . ' von ' . $total . ' Datei(en) gelöscht';

    recoveryTryExecuteDelete(
        $db,
        "DELETE FROM wcf{$wcfN}_package_installation_file_log WHERE packageID = ?",
        [$packageID],
        'Package-File-Log',
        $log
    );
}

function recoveryRebuildBootstrapLoader(
    \wcf\system\database\Database $db,
    int $wcfN,
    array &$log,
    bool $dryRun = false
): bool
{
    if (!\defined('WCF_DIR')) {
        \define('WCF_DIR', recoveryResolveWcfDir());
    }

    $pfx = $dryRun ? '[DRY-RUN] ' : '';
    $requires = [];

    try {
        $sql = "SELECT package FROM wcf{$wcfN}_package ORDER BY installPriority ASC, package ASC";
        $statement = $db->prepareStatement($sql);
        $statement->execute();
        while ($row = $statement->fetchArray()) {
            $package = (string) ($row['package'] ?? '');
            if ($package === '') {
                continue;
            }
            $bootstrap = WCF_DIR . 'lib/bootstrap/' . $package . '.php';
            if (\is_file($bootstrap)) {
                $requires[] = $package;
            }
        }
    } catch (\Throwable $e) {
        $log[] = $pfx . 'Bootstrap-Rebuild: Paketliste nicht lesbar – ' . $e->getMessage();

        return false;
    }

    $now = (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->format('c');
    $body = "<?php /* {$now} */\n\nreturn [\n";
    foreach ($requires as $package) {
        $body .= "    require(__DIR__ . '/bootstrap/{$package}.php'),\n";
    }
    $body .= "];\n";

    if ($dryRun) {
        $log[] = $pfx . 'WÜRDE lib/bootstrap.php neu schreiben (' . \count($requires) . ' Paket-Bootstrap(s))';

        return true;
    }

    $target = WCF_DIR . 'lib/bootstrap.php';
    $tmp = $target . '.recovery-' . \bin2hex(\random_bytes(4)) . '.tmp';
    if (@\file_put_contents($tmp, $body) === false) {
        $log[] = 'Bootstrap-Rebuild: temporäre Datei konnte nicht geschrieben werden';

        return false;
    }
    if (!@\rename($tmp, $target)) {
        @\unlink($tmp);
        $log[] = 'Bootstrap-Rebuild: lib/bootstrap.php konnte nicht ersetzt werden';

        return false;
    }
    if (\function_exists('opcache_invalidate')) {
        @\opcache_invalidate($target, true);
    } elseif (!\function_exists('opcache_reset')) {
        $log[] = 'Bootstrap-Rebuild: Opcache-Invalidierung nicht verfügbar – ggf. PHP-FPM neu laden';
    }
    $log[] = 'lib/bootstrap.php neu erzeugt (' . \count($requires) . ' Paket-Bootstrap(s))';

    return true;
}

/**
 * @param array{
 *   dryRun?: bool,
 *   sqlRollback?: bool,
 *   deleteFiles?: bool,
 *   rebuildBootstrap?: bool,
 *   runUninstallScript?: bool,
 * } $options
 */
function recoveryRunPreDbRemovalSteps(
    \wcf\system\database\Database $db,
    int $wcfN,
    string $packageIdentifier,
    ?int $packageID,
    array $options,
    array &$log
): void {
    $dryRun = !empty($options['dryRun']);
    $pfx = $dryRun ? '[DRY-RUN] ' : '';

    if (!empty($options['runUninstallScript'])) {
        recoveryExecutePackageUninstallScript($packageIdentifier, $log, $dryRun);
    }

    if (!empty($options['sqlRollback']) && $packageID !== null && $packageID > 0) {
        recoveryExecuteSqlRollback($db, $wcfN, $packageID, $log, $dryRun);
    } elseif (!empty($options['sqlRollback'])) {
        $log[] = $pfx . 'SQL-Rollback übersprungen (keine packageID).';
    }
}

/**
 * @param array{
 *   dryRun?: bool,
 *   deleteFiles?: bool,
 *   rebuildBootstrap?: bool,
 * } $options
 */
function recoveryRunPostDbRemovalSteps(
    \wcf\system\database\Database $db,
    int $wcfN,
    string $packageIdentifier,
    ?array $packageData,
    ?int $packageID,
    array $options,
    array &$log,
    ?string $extractDir = null
): void {
    $dryRun = !empty($options['dryRun']);
    $deleteLog = !empty($options['deleteFilesLog']) || !empty($options['deleteFiles']);
    $deleteDir = !empty($options['deleteFilesDir']) || (!empty($options['deleteFiles']) && empty($options['deleteFilesLog']));
    $performDelete = !$dryRun;

    if ($deleteLog && $packageID !== null && $packageID > 0) {
        recoveryDeletePackageFilesFromLog($db, $wcfN, $packageID, $log, $performDelete, $dryRun);
    }

    if ($deleteDir) {
        recoveryDeletePluginFilesOnDisk(
            $packageData,
            $packageIdentifier,
            $log,
            $performDelete,
            $db,
            $wcfN,
            $extractDir
        );
    }

    if (!empty($options['rebuildBootstrap'])) {
        recoveryRebuildBootstrapLoader($db, $wcfN, $log, $dryRun);
    }

    if ($performDelete && $packageID !== null && $packageID > 0) {
        recoveryTryExecuteDelete(
            $db,
            "DELETE FROM wcf{$wcfN}_event_listener WHERE packageID = ?",
            [$packageID],
            'Event-Listener des Pakets (packageID)',
            $log
        );
        $wcfDir = \defined('WCF_DIR') ? \rtrim((string) \constant('WCF_DIR'), '/\\') . '/' : '';
        if ($wcfDir !== '' && \is_dir($wcfDir)) {
            $purged = recoveryPurgeOrphanedDbEventListeners($wcfDir, $db, $wcfN, $log, null);
            if ($purged > 0) {
                $log[] = '[Nachbereinigung] Zusätzlich ' . $purged . ' verwaiste Event-Listener entfernt.';
            }
        }
    }
}

/**
 * @return list<array{key: string, label: string, status: string, detail: string}>
 */
function recoveryRunSystemChecks(
    string $wcfDir,
    ?\wcf\system\database\Database $db,
    ?int $wcfN,
    ?array $assets = null
): array {
    unset($assets);
    $checks = [];
    $wcfDir = \rtrim($wcfDir, '/\\') . '/';
    $wcfReal = \realpath($wcfDir);

    $phpOk = \PHP_VERSION_ID >= 80100;
    $checks[] = [
        'key' => 'php',
        'label' => 'PHP',
        'status' => $phpOk ? 'ok' : 'error',
        'detail' => \PHP_VERSION . ($phpOk ? '' : ' (min. 8.1 erforderlich)'),
    ];

    $checks[] = [
        'key' => 'wcf_dir',
        'label' => 'WCF_DIR',
        'status' => $wcfReal !== false && \is_dir($wcfReal) ? 'ok' : 'error',
        'detail' => $wcfReal !== false ? $wcfReal : $wcfDir . ' — nicht lesbar',
    ];

    $dbOk = false;
    if ($db !== null && $wcfN !== null) {
        try {
            $stmt = $db->prepareStatement('SELECT 1');
            $stmt->execute();
            $dbOk = true;
        } catch (\Throwable $e) {
            $checks[] = [
                'key' => 'db',
                'label' => 'Datenbank',
                'status' => 'error',
                'detail' => $e->getMessage(),
            ];
        }
    } else {
        $checks[] = [
            'key' => 'db',
            'label' => 'Datenbank',
            'status' => 'warn',
            'detail' => 'Keine Verbindung — Bootstrap/Config prüfen',
        ];
    }
    if ($dbOk) {
        $checks[] = [
            'key' => 'db',
            'label' => 'Datenbank',
            'status' => 'ok',
            'detail' => 'Verbindung OK (wcf' . $wcfN . '_*)',
        ];
    }

    $cacheOk = \is_writable($wcfDir . 'cache');
    $checks[] = [
        'key' => 'cache',
        'label' => 'Cache beschreibbar',
        'status' => $cacheOk ? 'ok' : 'error',
        'detail' => $cacheOk ? 'cache/ ist beschreibbar' : 'cache/ nicht beschreibbar',
    ];

    $logHits = recoveryScanWoltLabLogForRecentErrors($wcfDir, 8);
    $lastError = $logHits !== [] ? (string) $logHits[\count($logHits) - 1] : '';
    $checks[] = [
        'key' => 'log',
        'label' => 'Letzter Log-Fehler',
        'status' => $lastError !== '' ? 'warn' : 'ok',
        'detail' => $lastError !== '' ? $lastError : 'Kein kürzlicher Fehler in log/*.txt',
    ];

    if ($db !== null && $wcfN !== null) {
        $brokenApps = recoveryFindBrokenApplicationRows($db, $wcfN);
        if ($brokenApps !== []) {
            $sample = [];
            foreach (\array_slice($brokenApps, 0, 3) as $row) {
                $sample[] = (string) $row['application'] . ' (packageID ' . (int) $row['packageID'] . ')';
            }
            $checks[] = [
                'key' => 'broken_applications',
                'label' => 'Applications (DB)',
                'status' => 'error',
                'detail' => \count($brokenApps) . ' ungültige Zeile(n) in wcf' . $wcfN
                    . '_application — blockiert oft das Frontend (package id \'0\' is unknown).'
                    . (\count($sample) > 0 ? ' Betroffen: ' . \implode('; ', $sample) . '.' : ''),
            ];
        }

        $logClasses = recoveryExtractMissingClassesFromLog($wcfDir);
        $orphanListeners = recoveryFindOrphanedDbEventListeners($wcfDir, $db, $wcfN, null, $logClasses);
        if ($orphanListeners !== []) {
            $names = [];
            foreach (\array_slice($orphanListeners, 0, 2) as $row) {
                $names[] = (string) ($row['listenerClassName'] ?? '');
            }
            $checks[] = [
                'key' => 'orphan_event_listeners',
                'label' => 'Event-Listener (DB)',
                'status' => 'error',
                'detail' => \count($orphanListeners) . ' Listener ohne ladbare Klasse'
                    . ' (typisch ClassNotFound im ACP).'
                    . ($names !== [] ? ' z. B. ' . $names[0] : ''),
            ];
        }

        $apps = recoveryFetchApplicationDirectoryReport($db, $wcfN, $wcfDir);
        $issueCount = 0;
        foreach ($apps as $app) {
            if ($app['issues'] !== []) {
                ++$issueCount;
            }
        }
        $checks[] = [
            'key' => 'apps',
            'label' => 'Applications (Pfade)',
            'status' => $apps === [] ? 'warn' : ($issueCount === 0 ? 'ok' : 'warn'),
            'detail' => $apps === []
                ? 'Keine Applications in der Datenbank'
                : \count($apps) . ' Application(s), ' . $issueCount . ' mit Abweichungen',
        ];
    }

    return $checks;
}

/**
 * @param list<array{key: string, label: string, status: string, detail: string}> $checks
 */
function recoveryBuildSupportTicketText(
    string $wcfDir,
    ?int $wcfN,
    array $checks,
    ?string $diagnosisSummary = null,
    ?string $alreadyTried = null
): string {
    $lines = [
        '=== WoltLab Plugin Recovery — Support-Info ===',
        '',
        'Recovery Tool: v' . RECOVERY_VERSION,
        'PHP: ' . \PHP_VERSION,
        'WoltLab: ' . (\defined('WCF_VERSION') ? (string) \constant('WCF_VERSION') : 'unbekannt'),
        'WCF_DIR: ' . \rtrim($wcfDir, '/\\'),
    ];
    if ($wcfN !== null) {
        $lines[] = 'WCF_N: ' . $wcfN;
    }
    $lines[] = '';
    $lines[] = '--- System-Check ---';
    foreach ($checks as $check) {
        $icon = match ($check['status']) {
            'ok' => 'OK',
            'warn' => 'Hinweis',
            default => 'Fehler',
        };
        $lines[] = $icon . ' | ' . $check['label'] . ': ' . $check['detail'];
    }
    $lastLog = recoveryGetLastLogHintMessage();
    if ($lastLog !== null && $lastLog !== '') {
        $lines[] = '';
        $lines[] = '--- Letzter Log-Hinweis (Kurz) ---';
        $lines[] = $lastLog;
    }
    $fullLogBlock = recoveryGetLastFullLogBlock($wcfDir);
    if ($fullLogBlock !== null && $fullLogBlock !== '') {
        $lines[] = '';
        $lines[] = '--- Vollständiger Log-Eintrag (letzter Fehler) ---';
        $lines[] = $fullLogBlock;
    }
    if ($diagnosisSummary !== null && $diagnosisSummary !== '') {
        $lines[] = '';
        $lines[] = '--- Diagnose (Wizard) ---';
        $lines[] = $diagnosisSummary;
    }
    $lines[] = '';
    $lines[] = '--- Bereits versucht ---';
    $tried = \trim((string) ($alreadyTried ?? ''));
    $lines[] = $tried !== '' ? $tried : '(bitte ergänzen: z. B. Cache leeren, Bootstrap neutralisiert, Backup erstellt)';
    $lines[] = '';
    $lines[] = 'Forum: https://www.woltlab.com/community/board/1500-fehlermeldungen/';

    return \implode("\n", $lines);
}

function recoveryGetSupportTriedText(string $authHash): string
{
    return \trim((string) ($_SESSION['recovery_support_tried'][$authHash] ?? ''));
}

function recoverySetSupportTriedText(string $authHash, string $text): void
{
    $_SESSION['recovery_support_tried'][$authHash] = $text;
}

/**
 * @param array{key: string, label: string, status: string, detail: string} $check
 */
function recoveryRenderSystemCheckHelpLinks(array $check, string $authHash): void
{
    if (($check['status'] ?? '') === 'ok') {
        return;
    }

    $key = (string) ($check['key'] ?? '');
    $links = [
        ['Datensicherung', recoveryBuildModeUrl(RECOVERY_MODE_BACKUP_GUIDE, $authHash)],
        ['Recovery-Wizard', recoveryBuildModeUrl(RECOVERY_MODE_RECOVERY_WIZARD, $authHash)],
    ];

    $extra = match ($key) {
        'broken_applications' => [
            ['Paketliste reparieren', recoveryBuildModeUrl(RECOVERY_MODE_PACKAGE_LIST_REPAIR, $authHash)],
            ['Kern-Reparatur (Start)', recoveryBuildHomeUrl($authHash) . '#recovery-core-repair'],
        ],
        'orphan_event_listeners' => [
            ['Kern-Reparatur (Start)', recoveryBuildHomeUrl($authHash) . '#recovery-core-repair'],
            ['Recovery-Wizard', recoveryBuildModeUrl(RECOVERY_MODE_RECOVERY_WIZARD, $authHash)],
        ],
        'cache' => [['Cache leeren', recoveryBuildModeUrl(RECOVERY_MODE_CACHE_CLEAR, $authHash)]],
        'log' => [
            ['Kern-Reparatur (Start)', recoveryBuildHomeUrl($authHash) . '#recovery-core-repair'],
            ['Fehlermeldungen (Forum)', 'https://www.woltlab.com/community/board/1500-fehlermeldungen/'],
        ],
        'apps' => [['Verzeichnisstruktur', recoveryBuildModeUrl(RECOVERY_MODE_DIRECTORY_STRUCTURE, $authHash)]],
        'db' => [['Verzeichnisstruktur', recoveryBuildModeUrl(RECOVERY_MODE_DIRECTORY_STRUCTURE, $authHash)]],
        default => [],
    };

    $links = \array_merge($extra, $links);
    if (recoveryUsesNativeAcpUi()) {
        echo '<ul class="buttonGroup small" role="group" aria-label="Empfohlene Schritte">';
        foreach ($links as [$label, $href]) {
            $external = \str_starts_with($href, 'http');
            echo '<li><a href="' . \htmlspecialchars($href) . '" class="button small"'
                . ($external ? ' target="_blank" rel="noopener"' : '')
                . '>' . \htmlspecialchars($label) . '</a></li>';
        }
        echo '</ul>';

        return;
    }

    echo '<div class="recovery-check-actions" role="group" aria-label="Empfohlene Schritte">';
    foreach ($links as [$label, $href]) {
        $external = \str_starts_with($href, 'http');
        echo '<a href="' . \htmlspecialchars($href) . '" class="button small"'
            . ($external ? ' target="_blank" rel="noopener"' : '')
            . '>' . \htmlspecialchars($label) . '</a>';
    }
    echo '</div>';
}

function recoveryRenderSystemCheckPage(
    string $authHash,
    string $wcfDir,
    ?\wcf\system\database\Database $db,
    ?int $wcfN,
    ?array $assets
): void {
    unset($assets);
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['recovery_support_tried_save'])) {
        recoverySetSupportTriedText($authHash, (string) ($_POST['recovery_support_tried'] ?? ''));
        \header('Location: ' . recoveryBuildModeUrl(RECOVERY_MODE_SYSTEM_CHECK, $authHash, ['saved' => '1']));
        exit;
    }
    $checks = recoveryRunSystemChecks($wcfDir, $db, $wcfN);
    $wizardState = recoveryWizardLoadState($authHash);
    $diagSummary = '';
    if (!empty($wizardState['diagnosis']) && \is_array($wizardState['diagnosis'])) {
        $diag = $wizardState['diagnosis'];
        $undef = \count($diag['undefinedConstants'] ?? []);
        $missing = \count($diag['missingBootstrapClasses'] ?? $diag['missingClasses'] ?? []);
        $parts = [];
        if ($undef > 0) {
            $parts[] = $undef . ' undefinierte Konstante(n)';
        }
        if ($missing > 0) {
            $parts[] = $missing . ' fehlende Klasse(n)';
        }
        $diagSummary = $parts !== [] ? \implode(', ', $parts) : 'Wizard-Diagnose ohne kritische Befunde';
    }
    $triedText = recoveryGetSupportTriedText($authHash);
    $ticketText = recoveryBuildSupportTicketText(
        $wcfDir,
        $wcfN,
        $checks,
        $diagSummary !== '' ? $diagSummary : null,
        $triedText
    );
    $statusIcon = static function (string $status): string {
        return match ($status) {
            'ok' => recoveryFaIcon(16, 'circle-check'),
            'warn' => recoveryFaIcon(16, 'triangle-exclamation'),
            default => recoveryFaIcon(16, 'circle-xmark', true),
        };
    };
    $errorCount = 0;
    $warnCount = 0;
    foreach ($checks as $check) {
        $st = (string) ($check['status'] ?? '');
        if ($st === 'error') {
            $errorCount++;
        } elseif ($st === 'warn') {
            $warnCount++;
        }
    }
    $checksBadgeClass = $errorCount > 0 ? 'badgeRed' : ($warnCount > 0 ? 'badgeYellow' : 'badgeGreen');
    if ($errorCount > 0) {
        $checksBadgeText = $errorCount === 1 ? '1 Fehler' : $errorCount . ' Fehler';
    } elseif ($warnCount > 0) {
        $checksBadgeText = $warnCount === 1 ? '1 Hinweis' : $warnCount . ' Hinweise';
    } else {
        $checksBadgeText = 'Bereit';
    }
    ?>
    <?php if (!recoveryUsesNativeAcpUi()): ?>
    <div class="recovery-syscheck-page recovery-content-stack">
    <?php endif; ?>
    <?php if (!recoveryUsesNativeAcpUi()): ?>
    <header class="recovery-intake-hero recovery-intake-hero--compact">
        <span class="recovery-intake-hero__icon" aria-hidden="true"><?= recoveryFaIcon(28, 'stethoscope') ?></span>
        <h1>System-Check</h1>
        <p class="subtitle">
            Voraussetzungen für Recovery-Schritte — wie WoltLab <code>test.php</code>, mit empfohlenen Aktionen bei Abweichungen.
            Unten finden Sie den formatierten Text fürs WoltLab-Fehlermeldungs-Board.
        </p>
    </header>
    <?php endif; ?>

    <?php if (recoveryUsesNativeAcpUi()): ?>
    <section class="section">
        <header class="sectionHeader">
            <h2 class="sectionTitle">Prüfungen</h2>
            <p class="sectionDescription">
                Voraussetzungen für Recovery-Schritte — wie WoltLab <code>test.php</code>.
                Status: <span class="badge <?= $checksBadgeClass ?>"><?= \htmlspecialchars($checksBadgeText) ?></span>
            </p>
        </header>
        <table class="table tableList">
    <?php else: ?>
    <details class="recovery-panel recovery-panel--sysinfo recovery-license-panel recovery-syscheck-checks" open>
        <summary>
            <span class="recovery-license-panel__summary-title"><?= recoveryFaIcon(16, 'list-check') ?> Prüfungen</span>
            <span class="badge <?= $checksBadgeClass ?> recovery-license-panel__summary-badge"><?= \htmlspecialchars($checksBadgeText) ?></span>
        </summary>
        <div class="recovery-panel__body recovery-panel__body--sysinfo">
        <table class="tableList recovery-table-list recovery-data-table recovery-data-table--check recovery-system-check-table">
    <?php endif; ?>
            <colgroup>
                <col class="recovery-syscheck-col-icon">
                <col class="recovery-syscheck-col-label">
                <col class="recovery-syscheck-col-result">
            </colgroup>
            <thead>
                <tr>
                    <th class="columnIcon recovery-syscheck-th-icon" aria-label="Status"><span class="silent">Status</span></th>
                    <th class="recovery-syscheck-th-label">Prüfung</th>
                    <th class="recovery-syscheck-th-result">Ergebnis</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($checks as $check):
                $hasActions = ($check['status'] ?? '') !== 'ok';
                ?>
                <tr class="recovery-syscheck-row recovery-syscheck-row--<?= \htmlspecialchars((string) $check['status']) ?>">
                    <td class="columnIcon recovery-syscheck-icon"><?= $statusIcon((string) $check['status']) ?></td>
                    <td class="recovery-syscheck-label"><strong><?= \htmlspecialchars((string) $check['label']) ?></strong></td>
                    <td class="columnText recovery-syscheck-result">
                        <div class="recovery-check-detail"><?= \nl2br(\htmlspecialchars((string) $check['detail']), false) ?></div>
                        <?php if ($hasActions): ?>
                        <?php recoveryRenderSystemCheckHelpLinks($check, $authHash); ?>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php if (recoveryUsesNativeAcpUi()): ?>
    </section>
    <?php else: ?>
        </div>
    </details>
    <?php endif; ?>

    <?php if (recoveryUsesNativeAcpUi()): ?>
    <section class="section">
        <header class="sectionHeader">
            <h2 class="sectionTitle">Support-Ticket</h2>
            <p class="sectionDescription">
                Formatierter Text fürs
                <a href="https://www.woltlab.com/community/board/1500-fehlermeldungen/" target="_blank" rel="noopener">WoltLab-Fehlermeldungs-Board</a>.
            </p>
        </header>
    <?php else: ?>
    <details class="recovery-panel recovery-panel--sysinfo recovery-license-panel recovery-syscheck-support" open>
        <summary>
            <span class="recovery-license-panel__summary-title"><?= recoveryFaIcon(16, 'life-ring') ?> Support-Ticket</span>
            <span class="badge badgeGreen recovery-license-panel__summary-badge">Kopieren</span>
        </summary>
        <div class="recovery-panel__body recovery-panel__body--sysinfo">
            <div class="recovery-sysinfo-cta-strip" role="note" aria-label="Hinweise zum Support-Text">
    <?php endif; ?>
            <?php if (!recoveryUsesNativeAcpUi()): ?>
                <div class="recovery-sysinfo-cta-strip__block recovery-sysinfo-cta-strip__block--support">
                    <p class="recovery-sysinfo-cta-strip__heading">
                        <?= recoveryFaIcon(16, 'copy') ?>
                        Für das WoltLab-Fehlermeldungs-Board
                    </p>
                    <p class="recovery-sysinfo-cta-strip__text">
                        Kopieren Sie den formatierten Text mit dem Button unten — inklusive Prüfergebnissen
                        und Ihrer Notizen unter „Bereits versucht“. Forum:
                        <a href="https://www.woltlab.com/community/board/1500-fehlermeldungen/" target="_blank" rel="noopener">Fehlermeldungen</a>.
                    </p>
                </div>
            </div>
            <?php endif; ?>
            <pre class="recoveryLog" id="recovery-support-ticket" hidden><?= \htmlspecialchars($ticketText) ?></pre>
            <form method="POST" action="<?= \htmlspecialchars(recoveryBuildModeUrl(RECOVERY_MODE_SYSTEM_CHECK, $authHash)) ?>" id="recovery-support-tried-form">
                <?php recoveryRenderFormModeHiddenFields(RECOVERY_MODE_SYSTEM_CHECK, $authHash); ?>
                <input type="hidden" name="recovery_support_tried_save" value="1">
                <label for="recovery-support-tried" class="recovery-syscheck-support__label"><strong>Bereits versucht</strong></label>
                <p class="recovery-syscheck-support__hint">Wird beim Kopieren unter den Prüfergebnissen eingefügt.</p>
                <textarea id="recovery-support-tried" name="recovery_support_tried" rows="5" class="long recovery-syscheck-support__textarea"><?= \htmlspecialchars($triedText) ?></textarea>
            </form>
            <?php if (isset($_GET['saved'])): ?>
            <?php recoveryRenderAlert('success', 'Notizen gespeichert.'); ?>
            <?php endif; ?>
    <?php if (recoveryUsesNativeAcpUi()): ?>
    </section>
    <?php else: ?>
        </div>
    </details>
    <?php endif; ?>

    <?php
    recoveryRenderActionBar([
        '<button type="button" class="button buttonPrimary" data-recovery-copy="recovery-support-ticket" data-recovery-copy-merge="recovery-support-tried" data-recovery-snack="Support-Text in Zwischenablage kopiert">'
            . (recoveryUsesNativeAcpUi() ? '' : recoveryFaIcon(16, 'copy')) . ' Support-Ticket kopieren</button>',
        '<button type="submit" class="button" form="recovery-support-tried-form">'
            . (recoveryUsesNativeAcpUi() ? '' : recoveryFaIcon(16, 'floppy-disk')) . ' In Session speichern</button>',
        '<a href="' . \htmlspecialchars(recoveryHomeUrl($authHash)) . '" class="button">'
            . (recoveryUsesNativeAcpUi() ? '' : recoveryFaIcon(16, 'house')) . ' Zurück zum Start</a>',
    ]);
    ?>
    <?php if (!recoveryUsesNativeAcpUi()): ?>
    </div>
    <?php endif; ?>
    <?php
}

/**
 * @return list<array{name: string, fqName: string, globalName: string, application: string|null}>
 */
function recoveryExtractUndefinedConstantsFromLog(string $wcfDir, int $maxLogFiles = 5): array
{
    $logDir = \rtrim($wcfDir, '/\\') . '/log';
    if (!\is_dir($logDir)) {
        return [];
    }

    $files = \glob($logDir . '/*.txt') ?: [];
    if ($files === []) {
        return [];
    }

    \usort($files, static function ($a, $b): int {
        return (\filemtime((string) $b) ?: 0) <=> (\filemtime((string) $a) ?: 0);
    });

    /** @var array<string, array{name: string, fqName: string, globalName: string, application: string|null}> $found */
    $found = [];
    foreach (\array_slice($files, 0, $maxLogFiles) as $logFile) {
        $content = @\file_get_contents($logFile);
        if ($content === false || $content === '') {
            continue;
        }
        if (!\preg_match_all('/Undefined constant "([^"]+)"/', $content, $matches)) {
            continue;
        }
        foreach ($matches[1] as $raw) {
            $raw = (string) $raw;
            if ($raw === '' || \str_contains($raw, '$')) {
                continue;
            }
            $globalName = $raw;
            $fqName = $raw;
            $app = null;
            if (\str_contains($raw, '\\')) {
                $parts = \explode('\\', $raw);
                $globalName = (string) \array_pop($parts);
                $app = $parts !== [] ? \strtolower((string) $parts[0]) : null;
            } elseif (\preg_match('/^[A-Z][A-Z0-9_]+$/', $raw)) {
                $app = recoveryLeadingPrefixSegmentLowerFromConstant($raw);
            }
            if ($globalName === '' || !\preg_match('/^[A-Z][A-Z0-9_]+$/', $globalName)) {
                continue;
            }
            $found[$fqName] = [
                'name' => $globalName,
                'fqName' => $fqName,
                'globalName' => $globalName,
                'application' => $app,
            ];
        }
    }

    $list = \array_values($found);
    \usort($list, static fn ($a, $b): int => \strcmp($a['fqName'], $b['fqName']));

    return $list;
}

/**
 * @param list<array{name: string, fqName: string, globalName: string, application: string|null}> $logConstants
 */
function recoveryFilterUndefinedConstantsByApplication(array $logConstants, ?string $scopeApplicationDirectory): array
{
    if ($scopeApplicationDirectory === null || $scopeApplicationDirectory === '') {
        return $logConstants;
    }
    $scope = \strtolower(\trim($scopeApplicationDirectory));

    return \array_values(\array_filter(
        $logConstants,
        static fn (array $c): bool => ($c['application'] ?? null) === null || ($c['application'] ?? '') === $scope
    ));
}

function recoveryCanExecShellCommands(): bool
{
    if (!\function_exists('proc_open') && !\function_exists('exec')) {
        return false;
    }
    $disabled = \array_map('trim', \explode(',', (string) \ini_get('disable_functions')));

    return !\in_array('exec', $disabled, true) && !\in_array('proc_open', $disabled, true);
}

function recoveryResolveBackupStorageDirectory(string $wcfDir): string
{
    $preferred = \rtrim($wcfDir, '/\\') . '/log/recovery/backups';
    if (\is_dir($preferred) || @\mkdir($preferred, 0755, true)) {
        return $preferred;
    }

    $fallback = \dirname(\rtrim($wcfDir, '/\\')) . '/recovery-backups';
    if (!\is_dir($fallback)) {
        @\mkdir($fallback, 0755, true);
    }

    return $fallback;
}

/**
 * @return array{ok: bool, path: string|null, bytes: int, method: string, message: string, dryRun: bool}
 */
function recoveryExecuteBackupDatabase(string $wcfDir, bool $dryRun = false, ?int $maxBytes = null): array
{
    $maxBytes ??= 512 * 1024 * 1024;
    $backupDir = recoveryResolveBackupStorageDirectory($wcfDir);
    $timestamp = \date('Y-m-d-His');
    $target = $backupDir . '/db-' . $timestamp . '.sql';

    if ($dryRun) {
        return [
            'ok' => true,
            'path' => $target,
            'bytes' => 0,
            'method' => 'dry-run',
            'message' => 'Dry-Run: Datenbank-Backup würde nach ' . $target . ' geschrieben.',
            'dryRun' => true,
        ];
    }

    if (!\is_dir($backupDir) || !\is_writable($backupDir)) {
        return [
            'ok' => false,
            'path' => null,
            'bytes' => 0,
            'method' => 'none',
            'message' => 'Backup-Verzeichnis nicht beschreibbar: ' . $backupDir,
            'dryRun' => false,
        ];
    }

    $free = @\disk_free_space($backupDir);
    if ($free !== false && $free < 64 * 1024 * 1024) {
        return [
            'ok' => false,
            'path' => null,
            'bytes' => 0,
            'method' => 'none',
            'message' => 'Zu wenig freier Speicher (< 64 MB) unter ' . $backupDir,
            'dryRun' => false,
        ];
    }

    $hints = recoveryBuildBackupCommandHints($wcfDir);
    if (recoveryCanExecShellCommands() && $hints['dbName'] !== '') {
        $cmd = 'mysqldump -h ' . \escapeshellarg($hints['dbHost'])
            . ' -P 3306 -u ' . \escapeshellarg($hints['dbUser'])
            . ' --single-transaction --skip-lock-tables '
            . \escapeshellarg($hints['dbName'])
            . ' > ' . \escapeshellarg($target) . ' 2>&1';
        $output = [];
        $code = 1;
        if (\function_exists('proc_open')) {
            $proc = @\proc_open($cmd, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, null, null);
            if (\is_resource($proc)) {
                foreach ($pipes as $pipe) {
                    \fclose($pipe);
                }
                $code = (int) \proc_close($proc);
            }
        } else {
            \exec($cmd, $output, $code);
        }
        if ($code === 0 && \is_file($target)) {
            $bytes = (int) \filesize($target);
            if ($bytes > $maxBytes) {
                @\unlink($target);

                return [
                    'ok' => false,
                    'path' => null,
                    'bytes' => $bytes,
                    'method' => 'mysqldump',
                    'message' => 'Backup überschreitet Größenwarnung (' . \round($bytes / 1024 / 1024) . ' MB).',
                    'dryRun' => false,
                ];
            }

            return [
                'ok' => true,
                'path' => $target,
                'bytes' => $bytes,
                'method' => 'mysqldump',
                'message' => 'Datenbank-Backup erstellt (' . \round($bytes / 1024 / 1024, 1) . ' MB).',
                'dryRun' => false,
            ];
        }
    }

    if (!isset($GLOBALS['db']) || !$GLOBALS['db'] instanceof \wcf\system\database\Database || !\defined('WCF_N')) {
        return [
            'ok' => false,
            'path' => null,
            'bytes' => 0,
            'method' => 'pdo-fallback',
            'message' => 'mysqldump nicht verfügbar und PDO-Fallback nicht möglich (keine DB-Verbindung).',
            'dryRun' => false,
        ];
    }

    /** @var \wcf\system\database\Database $db */
    $db = $GLOBALS['db'];
    $wcfN = (int) \constant('WCF_N');
    $schema = recoveryGetDatabaseSchemaName($db);
    $lines = ["-- Recovery Tool DB backup " . \date('c'), 'SET NAMES utf8mb4;', ''];
    try {
        $tables = $db->prepareStatement('SHOW TABLES');
        $tables->execute();
        while ($row = $tables->fetchArray()) {
            $table = (string) (\array_values($row)[0] ?? '');
            if ($table === '' || ($schema !== '' && !\str_starts_with($table, 'wcf' . $wcfN . '_'))) {
                continue;
            }
            $create = $db->prepareStatement('SHOW CREATE TABLE `' . \str_replace('`', '``', $table) . '`');
            $create->execute();
            $createRow = $create->fetchArray();
            $ddl = (string) ($createRow['Create Table'] ?? $createRow['Create View'] ?? '');
            if ($ddl !== '') {
                $lines[] = 'DROP TABLE IF EXISTS `' . $table . '`;';
                $lines[] = $ddl . ';';
            }
        }
        $payload = \implode("\n", $lines) . "\n";
        if (\strlen($payload) > $maxBytes) {
            return [
                'ok' => false,
                'path' => null,
                'bytes' => \strlen($payload),
                'method' => 'pdo-fallback',
                'message' => 'PDO-Schema-Export zu groß — bitte mysqldump per SSH nutzen.',
                'dryRun' => false,
            ];
        }
        \file_put_contents($target, $payload, \LOCK_EX);
        $bytes = (int) \filesize($target);

        return [
            'ok' => true,
            'path' => $target,
            'bytes' => $bytes,
            'method' => 'pdo-schema-only',
            'message' => 'PDO-Fallback: nur Tabellenstruktur exportiert (keine Datenzeilen). Für Vollbackup mysqldump nutzen.',
            'dryRun' => false,
        ];
    } catch (\Throwable $e) {
        return [
            'ok' => false,
            'path' => null,
            'bytes' => 0,
            'method' => 'pdo-fallback',
            'message' => 'PDO-Export fehlgeschlagen: ' . $e->getMessage(),
            'dryRun' => false,
        ];
    }
}

/**
 * @return array{ok: bool, path: string|null, bytes: int, method: string, message: string, dryRun: bool}
 */
function recoveryExecuteBackupFiles(string $wcfDir, bool $dryRun = false, ?int $maxBytes = null): array
{
    $maxBytes ??= 2048 * 1024 * 1024;
    $wcfDir = \rtrim($wcfDir, '/\\');
    $backupDir = recoveryResolveBackupStorageDirectory($wcfDir);
    $timestamp = \date('Y-m-d-His');
    $target = $backupDir . '/files-' . $timestamp . '.tar.gz';

    if ($dryRun) {
        return [
            'ok' => true,
            'path' => $target,
            'bytes' => 0,
            'method' => 'dry-run',
            'message' => 'Dry-Run: Dateisystem-Backup würde nach ' . $target . ' geschrieben.',
            'dryRun' => true,
        ];
    }

    if (!recoveryCanExecShellCommands()) {
        return [
            'ok' => false,
            'path' => null,
            'bytes' => 0,
            'method' => 'none',
            'message' => 'exec/proc_open nicht verfügbar — Dateisystem-Backup nur per SSH (tar) möglich.',
            'dryRun' => false,
        ];
    }

    if (!\is_dir($backupDir) || !\is_writable($backupDir)) {
        return [
            'ok' => false,
            'path' => null,
            'bytes' => 0,
            'method' => 'none',
            'message' => 'Backup-Verzeichnis nicht beschreibbar.',
            'dryRun' => false,
        ];
    }

    $parent = \dirname($wcfDir);
    $base = \basename($wcfDir);
    $cmd = 'tar -czf ' . \escapeshellarg($target) . ' -C ' . \escapeshellarg($parent) . ' '
        . \escapeshellarg($base) . ' 2>&1';
    $code = 1;
    if (\function_exists('proc_open')) {
        $proc = @\proc_open($cmd, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, null, null);
        if (\is_resource($proc)) {
            foreach ($pipes as $pipe) {
                \fclose($pipe);
            }
            $code = (int) \proc_close($proc);
        }
    } else {
        \exec($cmd, $out, $code);
    }

    if ($code !== 0 || !\is_file($target)) {
        return [
            'ok' => false,
            'path' => null,
            'bytes' => 0,
            'method' => 'tar',
            'message' => 'tar-Archiv konnte nicht erstellt werden (Exit ' . $code . ').',
            'dryRun' => false,
        ];
    }

    $bytes = (int) \filesize($target);
    if ($bytes > $maxBytes) {
        @\unlink($target);

        return [
            'ok' => false,
            'path' => null,
            'bytes' => $bytes,
            'method' => 'tar',
            'message' => 'Backup überschreitet Größenwarnung (' . \round($bytes / 1024 / 1024) . ' MB).',
            'dryRun' => false,
        ];
    }

    return [
        'ok' => true,
        'path' => $target,
        'bytes' => $bytes,
        'method' => 'tar',
        'message' => 'Dateisystem-Backup erstellt (' . \round($bytes / 1024 / 1024, 1) . ' MB).',
        'dryRun' => false,
    ];
}

function recoveryFindOptionsIncInExtractDir(string $extractDir): ?string
{
    $extractDir = \rtrim($extractDir, '/\\');
    $candidates = [
        $extractDir . '/options.inc.php',
        $extractDir . '/files/options.inc.php',
    ];
    foreach ($candidates as $path) {
        if (\is_file($path)) {
            return $path;
        }
    }

    try {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($extractDir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );
        foreach ($iterator as $file) {
            if ($file instanceof \SplFileInfo && $file->isFile() && $file->getFilename() === 'options.inc.php') {
                return $file->getPathname();
            }
        }
    } catch (\Throwable $ignored) {
    }

    $extractLog = [];
    $payload = recoveryExtractPackageInstructionTars($extractDir, $extractLog);
    if ($payload !== null) {
        foreach (['app', 'wcf'] as $key) {
            if (empty($payload[$key])) {
                continue;
            }
            $root = \rtrim((string) $payload[$key], '/\\');
            $direct = $root . '/options.inc.php';
            if (\is_file($direct)) {
                return $direct;
            }
            try {
                $iterator = new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS)
                );
                foreach ($iterator as $file) {
                    if ($file instanceof \SplFileInfo && $file->isFile() && $file->getFilename() === 'options.inc.php') {
                        return $file->getPathname();
                    }
                }
            } catch (\Throwable $ignored) {
            }
        }
    }

    return null;
}

function recoveryMergeOptionsIncFromPackage(string $sourceFile, string $wcfDir, array &$log, bool $dryRun = false): bool
{
    $target = \rtrim($wcfDir, '/\\') . '/options.inc.php';
    if (!\is_file($sourceFile) || !\is_readable($sourceFile)) {
        $log[] = '[options.inc.php] Quelle nicht lesbar: ' . $sourceFile;

        return false;
    }
    if (!\is_file($target)) {
        $log[] = '[options.inc.php] Ziel fehlt: ' . $target;

        return false;
    }
    if ($dryRun) {
        $log[] = '[options.inc.php] WÜRDE Inhalt aus Paket mergen: ' . \basename($sourceFile);

        return true;
    }
    if (!\is_writable($target)) {
        $log[] = '[options.inc.php] Ziel nicht beschreibbar.';

        return false;
    }

    $backup = $target . '.recovery-backup-' . \date('Ymd-His') . '.php';
    if (!@\copy($target, $backup)) {
        $log[] = '[options.inc.php] Backup des bestehenden Ziels fehlgeschlagen.';

        return false;
    }

    $source = (string) \file_get_contents($sourceFile);
    $marker = '// <plugin-recovery-tool> merged options.inc.php from package begin';
    $markerEnd = '// <plugin-recovery-tool> merged options.inc.php from package end';
    $existing = (string) \file_get_contents($target);
    $pattern = '~' . \preg_quote($marker, '~') . '.*' . \preg_quote($markerEnd, '~') . '\s*~sU';
    $existing = \preg_replace($pattern, '', $existing) ?? $existing;
    $snippet = "\n" . $marker . "\n" . $source . "\n" . $markerEnd . "\n";
    \file_put_contents($target, \rtrim($existing) . $snippet, \LOCK_EX);
    $log[] = '[options.inc.php] Paket-Inhalt angehängt (Backup: ' . \basename($backup) . ').';

    return true;
}

function recoverySkipApplicationCoreBootstrap(string $wcfDir, string $appDir, array &$log, bool $dryRun = false): bool
{
    $appDir = \trim($appDir, '/\\');
    if ($appDir === '') {
        return false;
    }
    $libDir = \rtrim($wcfDir, '/\\') . '/' . $appDir . '/lib/system';
    if (!\is_dir($libDir)) {
        $log[] = '[App deaktivieren] Kein lib/system unter ' . $appDir;

        return false;
    }

    $coreFiles = \glob($libDir . '/*Core.class.php') ?: [];
    if ($coreFiles === []) {
        $log[] = '[App deaktivieren] Keine *Core.class.php in ' . $appDir;

        return false;
    }

    $changed = false;
    foreach ($coreFiles as $coreFile) {
        $content = (string) @\file_get_contents($coreFile);
        if ($content === '' || \str_contains($content, '// [recovery] Application bootstrap skipped')) {
            continue;
        }
        if (!\preg_match('/function\s+__run\s*\([^)]*\)\s*\{/s', $content, $m, \PREG_OFFSET_CAPTURE)) {
            continue;
        }
        $insertAt = $m[0][1] + \strlen($m[0][0]);
        $patch = "\n\t\t// [recovery] Application bootstrap skipped — ACP/Frontend wieder erreichbar machen\n\t\treturn;\n";
        if ($dryRun) {
            $log[] = '[App deaktivieren] WÜRDE __run() in ' . \basename($coreFile) . ' vorzeitig beenden.';

            return true;
        }
        $backup = $coreFile . '.recovery-backup-' . \date('Ymd-His') . '.php';
        if (!@\copy($coreFile, $backup)) {
            $log[] = '[App deaktivieren] Backup fehlgeschlagen: ' . \basename($coreFile);

            continue;
        }
        $newContent = \substr($content, 0, $insertAt) . $patch . \substr($content, $insertAt);
        if (@\file_put_contents($coreFile, $newContent, \LOCK_EX) !== false) {
            $log[] = '[App deaktivieren] ' . \basename($coreFile) . ' — __run() übersprungen (Backup: ' . \basename($backup) . ').';
            $changed = true;
        }
    }

    return $changed;
}

/**
 * @param list<string> $needles Konstantennamen oder FQ-Namen aus dem Log
 * @return array{stillPresent: list<string>, resolved: list<string>, checkedAt: int}
 */
function recoveryWizardPostCheckLogConstants(string $wcfDir, array $needles): array
{
    $still = [];
    $resolved = [];
    $logDir = \rtrim($wcfDir, '/\\') . '/log';
    $content = '';
    if (\is_dir($logDir)) {
        $files = \glob($logDir . '/*.txt') ?: [];
        \usort($files, static fn ($a, $b): int => (\filemtime((string) $b) ?: 0) <=> (\filemtime((string) $a) ?: 0));
        if ($files !== []) {
            $content = (string) @\file_get_contents($files[0]);
        }
    }

    foreach ($needles as $needle) {
        $needle = (string) $needle;
        if ($needle === '') {
            continue;
        }
        if ($content !== '' && \str_contains($content, 'Undefined constant "' . $needle . '"')) {
            $still[] = $needle;
        } elseif (\defined($needle) || ($needle !== '' && \defined(\str_replace('\\\\', '\\', $needle)))) {
            $resolved[] = $needle;
        } else {
            $parts = \explode('\\', $needle);
            $global = \str_contains($needle, '\\') ? (string) \end($parts) : $needle;
            if ($global !== '' && \defined($global)) {
                $resolved[] = $needle;
            } else {
                $still[] = $needle;
            }
        }
    }

    return ['stillPresent' => $still, 'resolved' => $resolved, 'checkedAt' => \time()];
}

/**
 * @return array{mysqldump: string, tar: string, dbName: string, dbHost: string, dbUser: string, wcfDir: string}
 */
function recoveryBuildBackupCommandHints(string $wcfDir): array
{
    $dbHost = 'localhost';
    $dbUser = '';
    $dbName = '';
    $dbPort = 3306;

    if (\defined('WCF_DIR') && \is_readable(WCF_DIR . 'config.inc.php')) {
        $dbPassword = '';
        $defaultDriverOptions = [];
        /** @noinspection PhpIncludeInspection config setzt $dbHost, $dbUser, $dbName, $dbPort */
        require_once WCF_DIR . 'config.inc.php';
    }

    $wcfDirQuoted = \escapeshellarg(\rtrim($wcfDir, '/\\'));
    $dump = 'mysqldump -h ' . \escapeshellarg($dbHost)
        . ' -P ' . (int) $dbPort
        . ' -u ' . \escapeshellarg($dbUser)
        . ' -p --single-transaction --skip-lock-tables '
        . \escapeshellarg($dbName)
        . ' > backup-' . \date('Y-m-d') . '.sql';

    $tar = 'tar cf backup-' . \date('Y-m-d') . '.tar -C ' . $wcfDirQuoted . ' .';

    return [
        'mysqldump' => $dump,
        'tar' => $tar,
        'dbName' => $dbName,
        'dbHost' => $dbHost,
        'dbUser' => $dbUser,
        'wcfDir' => \rtrim($wcfDir, '/\\'),
    ];
}

function recoveryRenderBackupGuidePage(string $authHash, string $wcfDir): void
{
    $hints = recoveryBuildBackupCommandHints($wcfDir);
    $manualUrl = 'https://manual.woltlab.com/de/backup/';
    $backupDir = recoveryResolveBackupStorageDirectory($wcfDir);
    $canShell = recoveryCanExecShellCommands();
    $dbResult = null;
    $filesResult = null;

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['recovery_backup_action'])) {
        $action = (string) $_POST['recovery_backup_action'];
        $dryRun = !empty($_POST['backup_dry_run']);
        if ($action === 'db') {
            $dbResult = recoveryExecuteBackupDatabase($wcfDir, $dryRun);
            recoveryLog('info', 'Backup DB', $dbResult);
        } elseif ($action === 'files') {
            $filesResult = recoveryExecuteBackupFiles($wcfDir, $dryRun);
            recoveryLog('info', 'Backup files', $filesResult);
        } elseif ($action === 'both') {
            $dbResult = recoveryExecuteBackupDatabase($wcfDir, $dryRun);
            recoveryLog('info', 'Backup DB (both)', $dbResult);
            $filesResult = recoveryExecuteBackupFiles($wcfDir, $dryRun);
            recoveryLog('info', 'Backup files (both)', $filesResult);
        }
    }

    $existingBackups = [];
    if (\is_dir($backupDir)) {
        foreach (\glob($backupDir . '/*') ?: [] as $file) {
            if (\is_file($file)) {
                $existingBackups[] = $file;
            }
        }
        \rsort($existingBackups);
        $existingBackups = \array_slice($existingBackups, 0, 12);
    }
    $backupUrl = recoveryBuildModeUrl(RECOVERY_MODE_BACKUP_GUIDE, $authHash);
    $infoBody = 'Zielverzeichnis: <code>' . \htmlspecialchars($backupDir) . '</code> — '
        . '<a href="' . \htmlspecialchars($manualUrl) . '" target="_blank" rel="noopener">WoltLab-Handbuch: Backup</a>';

    recoveryRenderBackupStepHero(
        'Datensicherung',
        'Datenbank und Dateisystem sichern — dieselben Karten wie im Recovery-Wizard (Schritt 2).'
    );
    recoveryRenderAlert('info', $infoBody, 'Speicherort', true);
    recoveryRenderBackupChoiceCards($authHash, $backupUrl, [
        'includeBoth' => true,
        'ajaxBackup' => true,
        'dryRunFormId' => 'recovery-backup-dryrun',
    ]);

    if (!$canShell) {
        recoveryRenderAlert(
            'warning',
            '<code>exec</code>/<code>proc_open</code> eingeschränkt — DB per PDO; große Datei-Archive ggf. per SSH unten.',
            null,
            true
        );
    }
    ?>

    <div id="recovery-backup-ajax-result" class="recovery-backup-result" hidden></div>

    <?php if ($dbResult !== null || $filesResult !== null): ?>
    <section class="section">
        <header class="sectionHeader"><h2 class="sectionTitle">Ergebnis</h2></header>
        <?php if ($dbResult !== null): ?>
        <?php
            $dbBody = '<strong>Datenbank:</strong> ' . \htmlspecialchars((string) $dbResult['message']);
            if (!empty($dbResult['path']) && \is_file((string) $dbResult['path'])) {
                $dbBody .= '<br><code>' . \htmlspecialchars((string) $dbResult['path']) . '</code>';
            }
            recoveryRenderAlert(!empty($dbResult['ok']) ? 'success' : 'warning', $dbBody, null, true);
        ?>
        <?php endif; ?>
        <?php if ($filesResult !== null): ?>
        <?php
            $filesBody = '<strong>Dateisystem:</strong> ' . \htmlspecialchars((string) $filesResult['message']);
            if (!empty($filesResult['path']) && \is_file((string) $filesResult['path'])) {
                $filesBody .= '<br><code>' . \htmlspecialchars((string) $filesResult['path']) . '</code>';
            }
            recoveryRenderAlert(!empty($filesResult['ok']) ? 'success' : 'warning', $filesBody, null, true);
        ?>
        <?php endif; ?>
    </section>
    <?php endif; ?>

    <?php if ($existingBackups !== []): ?>
    <?php if (recoveryUsesNativeAcpUi()): ?>
    <section class="section">
        <header class="sectionHeader">
            <h2 class="sectionTitle">Vorhandene Backups (<?= \count($existingBackups) ?>)</h2>
        </header>
        <table class="table tableList">
            <thead><tr><th>Datei</th><th>Größe</th></tr></thead>
            <tbody>
            <?php foreach ($existingBackups as $path): ?>
                <tr>
                    <td><code><?= \htmlspecialchars(\basename($path)) ?></code></td>
                    <td><?= \round(((int) \filesize($path)) / 1024 / 1024, 1) ?> MB</td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </section>
    <?php else: ?>
    <details class="recovery-panel">
        <summary>Vorhandene Backups (<?= \count($existingBackups) ?>)</summary>
        <ul class="recovery-next-list" style="margin-top:12px">
        <?php foreach ($existingBackups as $path): ?>
            <li><code><?= \htmlspecialchars(\basename($path)) ?></code> (<?= \round(((int) \filesize($path)) / 1024 / 1024, 1) ?> MB)</li>
        <?php endforeach; ?>
        </ul>
    </details>
    <?php endif; ?>
    <?php endif; ?>

    <?php if (recoveryUsesNativeAcpUi()): ?>
    <section class="section">
        <header class="sectionHeader">
            <h2 class="sectionTitle">SSH-Befehle (manuell)</h2>
        </header>
        <dl>
            <dt>Datenbank</dt>
            <dd><pre class="recoveryLog" id="recovery-cmd-mysqldump" tabindex="0"><?= \htmlspecialchars($hints['mysqldump']) ?></pre></dd>
            <dt>Dateisystem (<code><?= \htmlspecialchars($hints['wcfDir']) ?></code>)</dt>
            <dd><pre class="recoveryLog" id="recovery-cmd-tar" tabindex="0"><?= \htmlspecialchars($hints['tar']) ?></pre></dd>
        </dl>
        <div class="formSubmit">
            <button type="button" class="button" data-recovery-copy="recovery-cmd-mysqldump">mysqldump kopieren</button>
            <button type="button" class="button" data-recovery-copy="recovery-cmd-tar">tar kopieren</button>
        </div>
        <?php recoveryRenderAlert('info', 'Ohne SSH: FTP/SFTP + phpMyAdmin/Hoster-Panel.'); ?>
    </section>
    <?php else: ?>
    <details class="recovery-panel">
        <summary>SSH-Befehle (manuell)</summary>
        <div class="recovery-panel__body">
            <h4 style="margin:0 0 8px">Datenbank</h4>
            <pre class="recovery-cmd-block" id="recovery-cmd-mysqldump" tabindex="0"><?= \htmlspecialchars($hints['mysqldump']) ?></pre>
            <div class="formSubmit">
                <button type="button" class="button" data-recovery-copy="recovery-cmd-mysqldump"><?= recoveryFaIcon(16, 'copy') ?> Befehl kopieren</button>
            </div>
            <h4 style="margin:20px 0 8px">Dateisystem (<code><?= \htmlspecialchars($hints['wcfDir']) ?></code>)</h4>
            <pre class="recovery-cmd-block" id="recovery-cmd-tar" tabindex="0"><?= \htmlspecialchars($hints['tar']) ?></pre>
            <div class="formSubmit">
                <button type="button" class="button" data-recovery-copy="recovery-cmd-tar"><?= recoveryFaIcon(16, 'copy') ?> Befehl kopieren</button>
            </div>
            <p style="margin-top:16px;font-size:13px">Ohne SSH: FTP/SFTP + phpMyAdmin/Hoster-Panel.</p>
        </div>
    </details>
    <?php endif; ?>

    <?php
    $cacheClearUrl = recoveryBuildModeUrl(RECOVERY_MODE_CACHE_CLEAR, $authHash);
    recoveryRenderActionBar([
        '<a href="' . \htmlspecialchars($cacheClearUrl) . '" class="button">' . (recoveryUsesNativeAcpUi() ? '' : recoveryFaIcon(16, 'broom')) . ' Cache leeren</a>',
        '<a href="' . \htmlspecialchars(recoveryHomeUrl($authHash)) . '" class="button buttonPrimary">' . (recoveryUsesNativeAcpUi() ? '' : recoveryFaIcon(16, 'house')) . ' Zurück zum Start</a>',
    ]);
}


/**
 * @return list<array{packageID: int, package: string, packageDir: string, domainName: string, domainPath: string, isTainted: int, dirExists: bool, dirPath: string, issues: list<string>}>
 */
function recoveryFetchApplicationDirectoryReport(
    \wcf\system\database\Database $db,
    int $wcfN,
    string $wcfDir
): array {
    $rows = [];
    $wcfDir = \rtrim($wcfDir, '/\\') . '/';

    try {
        $sql = "SELECT p.packageID, p.package, p.packageDir, a.domainName, a.domainPath, a.isTainted
                FROM wcf{$wcfN}_application a
                INNER JOIN wcf{$wcfN}_package p ON a.packageID = p.packageID
                ORDER BY p.package";
        $statement = $db->prepareStatement($sql);
        $statement->execute();
        while ($row = $statement->fetchArray()) {
            $packageDir = \trim((string) ($row['packageDir'] ?? ''), '/');
            $dirPath = $packageDir === '' ? $wcfDir : $wcfDir . $packageDir . '/';
            $real = \realpath($dirPath);
            $wcfReal = \realpath($wcfDir);
            $dirExists = $real !== false && \is_dir($real);
            $issues = [];
            if (!$dirExists) {
                $issues[] = 'Verzeichnis fehlt auf dem Server';
            } elseif ($wcfReal !== false && $real !== false && !\str_starts_with($real, $wcfReal)) {
                $issues[] = 'Pfad liegt nicht unter WCF_DIR';
            }
            if ((int) ($row['isTainted'] ?? 0) === 1) {
                $issues[] = 'Application ist als tainted markiert';
            }
            $domainPath = (string) ($row['domainPath'] ?? '/');
            if ($domainPath !== '/' && !\str_starts_with($domainPath, '/')) {
                $issues[] = 'domainPath sollte mit / beginnen';
            }
            $rows[] = [
                'packageID' => (int) ($row['packageID'] ?? 0),
                'package' => (string) ($row['package'] ?? ''),
                'packageDir' => $packageDir,
                'domainName' => (string) ($row['domainName'] ?? ''),
                'domainPath' => $domainPath,
                'isTainted' => (int) ($row['isTainted'] ?? 0),
                'dirExists' => $dirExists,
                'dirPath' => $dirExists ? $real : $dirPath,
                'issues' => $issues,
            ];
        }
    } catch (\Throwable $ignored) {
    }

    return $rows;
}

/**
 * Read-only Pfad-Diagnose: WCF-Kernverzeichnisse + DB-Applications.
 *
 * @return list<array{label: string, status: string, detail: string}>
 */
function recoveryRunPathDiagnostics(string $wcfDir, ?\wcf\system\database\Database $db, ?int $wcfN): array
{
    $checks = [];
    $wcfDir = \rtrim($wcfDir, '/\\') . '/';
    $wcfReal = \realpath($wcfDir);

    $checks[] = [
        'label' => 'WCF_DIR (Hauptverzeichnis)',
        'status' => $wcfReal !== false && \is_dir($wcfReal) ? 'ok' : 'error',
        'detail' => $wcfReal !== false ? $wcfReal : $wcfDir . ' — nicht lesbar',
    ];

    foreach ([
        'lib/' => 'PHP-Bibliotheken',
        'cache/' => 'Cache',
        'tmp/' => 'Temporärdateien',
        'log/' => 'Log-Dateien',
        'acp/' => 'Administrationsbereich',
    ] as $sub => $label) {
        $path = $wcfDir . $sub;
        $real = \realpath($path);
        $ok = $real !== false && \is_dir($real);
        $checks[] = [
            'label' => $label . ' (' . $sub . ')',
            'status' => $ok ? 'ok' : 'warn',
            'detail' => $ok ? 'vorhanden' : 'fehlt oder nicht lesbar',
        ];
    }

    $configPath = $wcfDir . 'config.inc.php';
    $checks[] = [
        'label' => 'config.inc.php',
        'status' => \is_readable($configPath) ? 'ok' : 'error',
        'detail' => \is_readable($configPath) ? 'lesbar' : 'fehlt oder nicht lesbar',
    ];

    if (\is_readable($configPath)) {
        $constants = ['RELATIVE_WCF_DIR', 'RELATIVE_WCF_DIR_ALT'];
        foreach ($constants as $const) {
            if (\defined($const)) {
                $checks[] = [
                    'label' => 'Konstante ' . $const,
                    'status' => 'ok',
                    'detail' => (string) \constant($const),
                ];
            }
        }
    }

    if ($db !== null && $wcfN !== null && $wcfN >= 1) {
        $apps = recoveryFetchApplicationDirectoryReport($db, $wcfN, $wcfDir);
        $issueCount = 0;
        foreach ($apps as $app) {
            if ($app['issues'] !== []) {
                ++$issueCount;
            }
        }
        $checks[] = [
            'label' => 'Applications (Datenbank)',
            'status' => $apps === [] ? 'warn' : ($issueCount === 0 ? 'ok' : 'warn'),
            'detail' => $apps === []
                ? 'Keine Einträge in wcf' . $wcfN . '_application'
                : \count($apps) . ' Application(s), ' . $issueCount . ' mit Abweichungen',
        ];
    } else {
        $checks[] = [
            'label' => 'Applications (Datenbank)',
            'status' => 'warn',
            'detail' => 'Datenbank nicht verfügbar — nur Dateisystem geprüft',
        ];
    }

    return $checks;
}

/**
 * @param list<array{package: string, packageDir: string, domainPath: string, dirPath: string}> $apps
 */
function recoveryGenerateDomainPathSqlPreview(array $apps, int $wcfN): string
{
    $lines = ['-- Vorschau: manuell prüfen und nur bei Bedarf in phpMyAdmin ausführen', '-- Kein automatisches UPDATE durch das Recovery Tool', ''];
    foreach ($apps as $app) {
        if ($app['issues'] === [] && $app['dirExists']) {
            continue;
        }
        $pkg = \addslashes((string) $app['package']);
        $dir = \addslashes(\trim((string) $app['packageDir'], '/'));
        $lines[] = '-- ' . $app['package'] . ' → erwarteter Ordner: ' . ($app['dirPath'] ?? '');
        $lines[] = "UPDATE wcf{$wcfN}_package SET packageDir = '{$dir}' WHERE package = '{$pkg}';";
        $lines[] = '';
    }

    return \implode("\n", $lines);
}

/**
 * @param list<array{package: string, packageDir: string, domainPath: string, dirPath: string, issues: list<string>}> $apps
 * @return list<array{package: string, path: string, relativeWcfDir: string|null, hint: string, readable: bool}>
 */
function recoveryScanAppConfigRelativeWcfDir(string $wcfDir, array $apps): array
{
    $wcfDir = \rtrim($wcfDir, '/\\') . '/';
    $rows = [];
    foreach ($apps as $app) {
        $packageDir = \trim((string) ($app['packageDir'] ?? ''), '/');
        if ($packageDir === '') {
            continue;
        }
        $configPath = $wcfDir . $packageDir . '/app.config.inc.php';
        $readable = \is_readable($configPath);
        $current = null;
        if ($readable) {
            $content = (string) @\file_get_contents($configPath);
            if (\preg_match("/define\s*\(\s*['\"]RELATIVE_WCF_DIR['\"]\s*,\s*['\"]([^'\"]*)['\"]\s*\)/", $content, $m)) {
                $current = $m[1];
            }
        }
        $hint = !$readable
            ? 'app.config.inc.php nicht gefunden — nach manuellem Verschieben anlegen/prüfen'
            : ($current === null
                ? 'RELATIVE_WCF_DIR nicht erkannt — Handbuch Schritt 3'
                : 'RELATIVE_WCF_DIR = ' . $current . ' (mit Handbuch abgleichen)');
        $rows[] = [
            'package' => (string) $app['package'],
            'path' => $configPath,
            'relativeWcfDir' => $current,
            'hint' => $hint,
            'readable' => $readable,
        ];
    }

    return $rows;
}


function recoveryRelativePathFromWcfDir(string $wcfDir, string $absolutePath): ?string
{
    $wcfReal = \realpath(\rtrim($wcfDir, '/\\'));
    $dirReal = \realpath($absolutePath);
    if ($wcfReal === false || $dirReal === false || !\is_dir($dirReal)) {
        return null;
    }
    $wcfReal = \str_replace('\\', '/', $wcfReal);
    $dirReal = \str_replace('\\', '/', $dirReal);
    if (!\str_starts_with($dirReal, $wcfReal)) {
        return null;
    }
    $rel = \substr($dirReal, \strlen($wcfReal));

    return \trim($rel, '/');
}

function recoverySuggestRelativeWcfDirForPackageDir(string $packageDir): string
{
    $packageDir = \trim($packageDir, '/');
    if ($packageDir === '') {
        return './';
    }
    $depth = \substr_count($packageDir, '/') + 1;

    return \str_repeat('../', $depth);
}

/**
 * @param list<array{package: string, packageID: int, packageDir: string, dirExists: bool, dirPath: string, issues: list<string>}> $apps
 * @return list<array{package: string, packageID: int, current: string, suggested: string}>
 */
function recoveryCollectPackageDirDbUpdates(array $apps, string $wcfDir): array
{
    $updates = [];
    foreach ($apps as $app) {
        if (empty($app['dirExists']) || empty($app['dirPath'])) {
            continue;
        }
        $suggested = recoveryRelativePathFromWcfDir($wcfDir, (string) $app['dirPath']);
        if ($suggested === null) {
            continue;
        }
        $current = \trim((string) ($app['packageDir'] ?? ''), '/');
        if ($suggested === $current) {
            continue;
        }
        $updates[] = [
            'package' => (string) $app['package'],
            'packageID' => (int) ($app['packageID'] ?? 0),
            'current' => $current,
            'suggested' => $suggested,
        ];
    }

    return $updates;
}

/**
 * @param list<array{package: string, packageID: int, current: string, suggested: string}> $updates
 * @return array{ok: bool, applied: int, log: list<string>}
 */
function recoveryApplyPackageDirDbUpdates(\wcf\system\database\Database $db, int $wcfN, array $updates): array
{
    $log = [];
    $applied = 0;
    foreach ($updates as $u) {
        $pkg = (string) $u['package'];
        $suggested = (string) $u['suggested'];
        $stmt = $db->prepareStatement("UPDATE wcf{$wcfN}_package SET packageDir = ? WHERE package = ?");
        $stmt->execute([$suggested, $pkg]);
        $log[] = "packageDir für {$pkg}: '{$u['current']}' → '{$suggested}'";
        ++$applied;
    }

    return ['ok' => true, 'applied' => $applied, 'log' => $log];
}

/**
 * @param list<array{package: string, path: string, relativeWcfDir: string|null, readable: bool, packageDir?: string}> $appConfigs
 * @return list<array{package: string, path: string, current: string|null, suggested: string}>
 */
function recoveryCollectAppConfigPatches(array $appConfigs, array $appsByPackage): array
{
    $patches = [];
    foreach ($appConfigs as $cfg) {
        if (empty($cfg['readable']) || empty($cfg['path'])) {
            continue;
        }
        $pkg = (string) $cfg['package'];
        $packageDir = '';
        foreach ($appsByPackage as $app) {
            if (($app['package'] ?? '') === $pkg) {
                $packageDir = \trim((string) ($app['packageDir'] ?? ''), '/');
                break;
            }
        }
        $suggested = recoverySuggestRelativeWcfDirForPackageDir($packageDir);
        $current = $cfg['relativeWcfDir'] ?? null;
        if ($current === $suggested) {
            continue;
        }
        $patches[] = [
            'package' => $pkg,
            'path' => (string) $cfg['path'],
            'current' => $current,
            'suggested' => $suggested,
        ];
    }

    return $patches;
}

/**
 * @param list<array{package: string, path: string, current: string|null, suggested: string}> $patches
 * @return array{ok: bool, applied: int, log: list<string>}
 */
function recoveryApplyAppConfigPatches(array $patches): array
{
    $log = [];
    $applied = 0;
    foreach ($patches as $patch) {
        $path = (string) $patch['path'];
        if (!\is_readable($path) || !\is_writable($path)) {
            $log[] = 'Übersprungen (nicht beschreibbar): ' . $path;
            continue;
        }
        $backup = $path . '.recovery-backup';
        if (!\is_file($backup)) {
            @\copy($path, $backup);
            $log[] = 'Backup: ' . $backup;
        }
        $content = (string) \file_get_contents($path);
        $suggested = \addslashes((string) $patch['suggested']);
        $newContent = \preg_replace(
            "/define\s*\(\s*['\"]RELATIVE_WCF_DIR['\"]\s*,\s*['\"][^'\"]*['\"]\s*\)/",
            "define('RELATIVE_WCF_DIR', '{$suggested}')",
            $content,
            1,
            $count
        );
        if ($count < 1) {
            $log[] = 'RELATIVE_WCF_DIR nicht gefunden in ' . $path;
            continue;
        }
        if (@\file_put_contents($path, $newContent) === false) {
            $log[] = 'Schreiben fehlgeschlagen: ' . $path;
            continue;
        }
        $log[] = (string) $patch['package'] . ': RELATIVE_WCF_DIR → ' . $patch['suggested'];
        ++$applied;
    }

    return ['ok' => $applied > 0 || $patches === [], 'applied' => $applied, 'log' => $log];
}

function recoveryBuildDirectoryStructureSqlPreview(array $updates, int $wcfN): string
{
    if ($updates === []) {
        return '-- Keine automatisch ermittelten packageDir-Abweichungen';
    }
    $lines = ['-- Vorschau: nur Zeilen mit erkannten Abweichungen (packageDir vs. Dateisystem)', ''];
    foreach ($updates as $u) {
        $pkg = \addslashes((string) $u['package']);
        $dir = \addslashes((string) $u['suggested']);
        $lines[] = '-- ' . $u['package'] . ": '{$u['current']}' → '{$u['suggested']}'";
        $lines[] = "UPDATE wcf{$wcfN}_package SET packageDir = '{$dir}' WHERE package = '{$pkg}';";
        $lines[] = '';
    }

    return \implode("\n", $lines);
}

function recoveryRenderDirectoryStructurePage(
    string $authHash,
    string $wcfDir,
    \wcf\system\database\Database $db,
    int $wcfN,
    ?array $applyDbResult = null,
    ?array $applyConfigResult = null
): void {
    $apps = recoveryFetchApplicationDirectoryReport($db, $wcfN, $wcfDir);
    $pathChecks = recoveryRunPathDiagnostics($wcfDir, $db, $wcfN);
    $dbUpdates = recoveryCollectPackageDirDbUpdates($apps, $wcfDir);
    $sqlPreview = recoveryBuildDirectoryStructureSqlPreview($dbUpdates, $wcfN);
    $hasSql = $dbUpdates !== [];
    $appConfigs = recoveryScanAppConfigRelativeWcfDir($wcfDir, $apps);
    $configPatches = recoveryCollectAppConfigPatches($appConfigs, $apps);
    $appsWithIssues = \array_values(\array_filter($apps, static fn (array $a): bool => $a['issues'] !== [] || !$a['dirExists']));
    $cacheCleared = isset($_GET['cache_cleared']);
    $dbApplied = isset($_GET['db_applied']);
    $configApplied = isset($_GET['config_applied']);
    if ($dbApplied) {
        $applyDbResult = recoverySessionPullFlash($authHash, 'dir_db') ?? $applyDbResult;
    }
    if ($configApplied) {
        $applyConfigResult = recoverySessionPullFlash($authHash, 'dir_config') ?? $applyConfigResult;
    }
    $manualUrl = 'https://manual.woltlab.com/de/customize-directory-structure/';
    $modeUrl = recoveryBuildModeUrl(RECOVERY_MODE_DIRECTORY_STRUCTURE, $authHash);
    $statusIcon = static function (string $status): string {
        return match ($status) {
            'ok' => recoveryFaIcon(16, 'circle-check'),
            'warn' => recoveryFaIcon(16, 'triangle-exclamation'),
            default => recoveryFaIcon(16, 'circle-xmark', true),
        };
    };
    ?>
    <?php if (!recoveryUsesNativeAcpUi()): ?>
    <?php
        recoveryRenderBackupStepHero(
            'Verzeichnisstruktur',
            'Handbuch-Workflow: Diagnose, SQL, Config, Domain und Cache — Dateiverschiebung bleibt manuell.',
            'folder-tree'
        );
    ?>
    <div class="recovery-content-stack">
    <?php endif; ?>
    <?php
    recoveryRenderAlert(
        'info',
        'Workflow gemäß <a href="' . \htmlspecialchars($manualUrl) . '" target="_blank" rel="noopener"><strong>WoltLab-Handbuch: Verzeichnisstruktur</strong></a>. '
        . 'Dateiverschiebung bleibt manuell; DB, Config, Domain und Cache kann das Tool unterstützen.',
        null,
        true
    );
    recoveryRenderDirectoryStructureDomainSection($authHash, $db, $wcfN, $modeUrl);
    ?>

    <?php if (recoveryUsesNativeAcpUi()): ?>
    <?php recoveryRenderAcpSectionStart(
        'Schritt 1 — Dateien verschieben',
        'Manuelle Checkliste gemäß WoltLab-Handbuch. Danach Schritt 2 (DB) und Schritt 3 (Config).'
    ); ?>
    <ol>
        <li>Backup von Datenbank und Dateien erstellen.</li>
        <li>Ordner per FTP/SSH gemäß Handbuch verschieben (nicht automatisch).</li>
        <li>Danach Schritt 2 (DB) und Schritt 3 (Config) ausführen.</li>
    </ol>
    <table class="table tableList">
    <?php else: ?>
    <details class="recovery-panel" open>
        <summary><strong>Schritt 1 — Dateien verschieben</strong> (manuell, Checkliste)</summary>
        <div class="recovery-panel__body">
            <ol class="recovery-next-list">
                <li>Backup von Datenbank und Dateien erstellen.</li>
                <li>Ordner per FTP/SSH gemäß Handbuch verschieben (nicht automatisch).</li>
                <li>Danach Schritt 2 (DB) und Schritt 3 (Config) ausführen.</li>
            </ol>
            <table class="tableList recovery-table-list recovery-data-table recovery-data-table--check recovery-system-check-table recovery-dir-path-check-table">
    <?php endif; ?>
                <colgroup>
                    <col class="recovery-syscheck-col-icon">
                    <col class="recovery-syscheck-col-label">
                    <col class="recovery-syscheck-col-result">
                </colgroup>
                <thead>
                    <tr>
                        <th class="columnIcon recovery-syscheck-th-icon" aria-label="Status"><span class="silent">Status</span></th>
                        <th class="recovery-syscheck-th-label">Prüfung</th>
                        <th class="recovery-syscheck-th-result">Ergebnis</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($pathChecks as $check): ?>
                <tr class="recovery-syscheck-row recovery-syscheck-row--<?= \htmlspecialchars((string) $check['status']) ?>">
                    <td class="columnIcon recovery-syscheck-icon"><?= $statusIcon((string) $check['status']) ?></td>
                    <td class="recovery-syscheck-label"><?= \htmlspecialchars((string) $check['label']) ?></td>
                    <td class="columnText recovery-syscheck-result"><span class="recovery-check-detail"><?= \htmlspecialchars((string) $check['detail']) ?></span></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
    <?php if (recoveryUsesNativeAcpUi()): ?>
    <?php recoveryRenderAcpSectionEnd(); ?>
    <?php else: ?>
        </div>
    </details>
    <?php endif; ?>

    <?php if ($appsWithIssues !== []): ?>
    <?php if (recoveryUsesNativeAcpUi()): ?>
    <?php recoveryRenderAcpSectionStart('Applications — Abweichungen', (string) \count($appsWithIssues) . ' Application(s) mit Abweichungen'); ?>
    <?php else: ?>
    <details class="recovery-panel" open>
        <summary><strong>Applications — Abweichungen</strong> (<?= \count($appsWithIssues) ?>)</summary>
        <div class="recovery-panel__body">
    <?php endif; ?>
            <table class="<?= \htmlspecialchars(recoveryAcpTableClass(), ENT_QUOTES, 'UTF-8') ?><?= recoveryUsesNativeAcpUi() ? '' : ' listView' ?>">
                <thead>
                    <tr><th>Package</th><th>packageDir</th><th>domainPath</th><th>Ordner</th><th>Hinweise</th></tr>
                </thead>
                <tbody>
                <?php foreach ($appsWithIssues as $app): ?>
                    <tr>
                        <td><code><?= \htmlspecialchars($app['package']) ?></code></td>
                        <td><code><?= \htmlspecialchars($app['packageDir'] ?: '/') ?></code></td>
                        <td><code><?= \htmlspecialchars($app['domainPath']) ?></code></td>
                        <td><?php if ($app['dirExists']): ?><span class="badge badgeGreen">OK</span><?php else: ?><span class="badge badgeRed">fehlt</span><?php endif; ?></td>
                        <td><small><?= \htmlspecialchars(\implode('; ', $app['issues'])) ?></small></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
    <?php if (recoveryUsesNativeAcpUi()): ?>
    <?php recoveryRenderAcpSectionEnd(); ?>
    <?php else: ?>
        </div>
    </details>
    <?php endif; ?>
    <?php endif; ?>

    <?php if ($dbApplied && $applyDbResult !== null): ?>
    <?php
        recoveryRenderAlert(
            !empty($applyDbResult['ok']) ? 'success' : 'warning',
            '<strong>Datenbank:</strong> ' . (int) ($applyDbResult['applied'] ?? 0) . ' Zeile(n) aktualisiert.'
        );
    ?>
    <?php endif; ?>

    <?php
    $needsDb = $hasSql;
    $needsConfig = $configPatches !== [];
    $workflowOkItems = [];
    if (!$needsDb) {
        $workflowOkItems[] = [
            'label' => 'Schritt 2 — Datenbank',
            'status' => 'ok',
            'statusText' => 'Keine Abweichungen',
        ];
    }
    if (!$needsConfig) {
        $workflowOkItems[] = [
            'label' => 'Schritt 3 — Konfiguration (RELATIVE_WCF_DIR)',
            'status' => 'ok',
            'statusText' => 'Keine Anpassung nötig',
        ];
    }
    ?>

    <?php if ($needsDb): ?>
    <?php if (recoveryUsesNativeAcpUi()): ?>
    <?php recoveryRenderAcpSectionStart(
        'Schritt 2 — Datenbank',
        'Nur erkannte Abweichungen — vor Ausführung prüfen und ggf. manuell in phpMyAdmin testen.'
    ); ?>
            <pre class="recoveryLog" id="recovery-path-sql" style="max-height:240px;overflow:auto"><?= \htmlspecialchars($sqlPreview) ?></pre>
            <div class="formSubmit">
                <button type="button" class="button" data-recovery-copy="recovery-path-sql" data-recovery-snack="SQL kopiert">SQL kopieren</button>
                <form method="POST" action="<?= \htmlspecialchars($modeUrl) ?>" style="display:inline"
                    data-recovery-confirm="Nur die oben angezeigten packageDir-Updates in wcf<?= (int) $wcfN ?>_package ausführen?"
                    data-recovery-confirm-title="DB anpassen"
                    data-recovery-confirm-ok="DB anwenden">
                    <?php recoveryRenderFormModeHiddenFields(RECOVERY_MODE_DIRECTORY_STRUCTURE, $authHash); ?>
                    <input type="hidden" name="recovery_apply_directory_db" value="1">
                    <button type="submit" class="button buttonPrimary">DB-Änderungen anwenden</button>
                </form>
            </div>
    <?php recoveryRenderAcpSectionEnd(); ?>
    <?php else: ?>
    <details class="recovery-panel" open>
        <summary><strong>Schritt 2 — Datenbank</strong></summary>
        <div class="recovery-panel__body">
            <p class="recovery-panel__hint">Nur erkannte Abweichungen — vor Ausführung prüfen und ggf. manuell in phpMyAdmin testen.</p>
            <pre class="recovery-cmd-block" id="recovery-path-sql" style="max-height:240px;overflow:auto;margin:0 0 12px"><?= \htmlspecialchars($sqlPreview) ?></pre>
            <div class="formSubmit recovery-formSubmit--center" style="margin:0">
                <button type="button" class="button" data-recovery-copy="recovery-path-sql" data-recovery-snack="SQL kopiert"><?= recoveryFaIcon(16, 'copy') ?> SQL kopieren</button>
                <form method="POST" action="<?= \htmlspecialchars($modeUrl) ?>"
                    data-recovery-confirm="Nur die oben angezeigten packageDir-Updates in wcf<?= (int) $wcfN ?>_package ausführen?"
                    data-recovery-confirm-title="DB anpassen"
                    data-recovery-confirm-ok="DB anwenden">
                    <?php recoveryRenderFormModeHiddenFields(RECOVERY_MODE_DIRECTORY_STRUCTURE, $authHash); ?>
                    <input type="hidden" name="recovery_apply_directory_db" value="1">
                    <button type="submit" class="button buttonPrimary"><?= recoveryFaIcon(16, 'database') ?> DB-Änderungen anwenden</button>
                </form>
            </div>
        </div>
    </details>
    <?php endif; ?>
    <?php endif; ?>

    <?php if ($needsConfig): ?>
    <?php if (recoveryUsesNativeAcpUi()): ?>
    <?php recoveryRenderAcpSectionStart(
        'Schritt 3 — Konfiguration (RELATIVE_WCF_DIR)',
        'Passt <code>RELATIVE_WCF_DIR</code> in <code>app.config.inc.php</code> an den neuen Pfad an '
        . '(<a href="' . \htmlspecialchars($manualUrl) . '" target="_blank" rel="noopener">WoltLab-Handbuch</a>). '
        . 'Pro Datei wird ein Backup <code>*.recovery-backup</code> angelegt.',
        true
    ); ?>
    <?php else: ?>
    <details class="recovery-panel" open>
        <summary><strong>Schritt 3 — Konfiguration (RELATIVE_WCF_DIR)</strong></summary>
        <div class="recovery-panel__body">
            <p class="recovery-panel__hint">
                Passt <code>RELATIVE_WCF_DIR</code> in <code>app.config.inc.php</code> an den neuen Pfad an
                (<a href="<?= \htmlspecialchars($manualUrl) ?>" target="_blank" rel="noopener">WoltLab-Handbuch</a>).
                Pro Datei wird ein Backup <code>*.recovery-backup</code> angelegt.
            </p>
    <?php endif; ?>
        <?php if ($configApplied && $applyConfigResult !== null): ?>
        <?php
            $configFlash = '<strong>Config:</strong> ' . (int) ($applyConfigResult['applied'] ?? 0) . ' Datei(en) angepasst.';
            if (!empty($applyConfigResult['log']) && \is_array($applyConfigResult['log'])) {
                foreach ($applyConfigResult['log'] as $logLine) {
                    $configFlash .= '<br><code>' . \htmlspecialchars((string) $logLine) . '</code>';
                }
            }
            recoveryRenderAlert(!empty($applyConfigResult['ok']) ? 'success' : 'warning', $configFlash, null, true);
        ?>
        <?php endif; ?>
        <table class="<?= recoveryUsesNativeAcpUi() ? 'table tableList' : 'tableList' ?>">
            <thead><tr><th>Package</th><th>Datei</th><th>Vorher</th><th>Nachher</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($configPatches as $patchIdx => $patch): ?>
            <tr>
                <td><code><?= \htmlspecialchars($patch['package']) ?></code></td>
                <td><small><code><?= \htmlspecialchars($patch['path']) ?></code></small></td>
                <td><code><?= \htmlspecialchars((string) ($patch['current'] ?? '—')) ?></code></td>
                <td><code><?= \htmlspecialchars($patch['suggested']) ?></code></td>
                <td>
                    <form method="POST" action="<?= \htmlspecialchars($modeUrl) ?>"
                        data-recovery-confirm="RELATIVE_WCF_DIR in dieser Datei patchen?"
                        data-recovery-confirm-title="Config anpassen"
                        data-recovery-confirm-ok="Config anwenden">
                        <?php recoveryRenderFormModeHiddenFields(RECOVERY_MODE_DIRECTORY_STRUCTURE, $authHash); ?>
                        <input type="hidden" name="recovery_apply_directory_config" value="1">
                        <input type="hidden" name="recovery_config_patch_path" value="<?= \htmlspecialchars($patch['path']) ?>">
                        <button type="submit" class="button buttonPrimary small"><?= recoveryUsesNativeAcpUi() ? '' : recoveryFaIcon(16, 'file-code') ?> Config anwenden</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php if (recoveryUsesNativeAcpUi()): ?>
    <?php recoveryRenderAcpSectionEnd(); ?>
    <?php else: ?>
        </div>
    </details>
    <?php endif; ?>
    <?php endif; ?>

    <?php
    $cacheHint = '<strong>Schritt 4 — Cache leeren:</strong> '
        . ($needsDb || $needsConfig
            ? 'Nach DB- oder Config-Anpassungen Cache leeren und Installation im Browser prüfen.'
            : 'Optional Cache leeren und Installation im Browser prüfen.');
    \ob_start();
    ?>
    <form method="POST" action="<?= \htmlspecialchars($modeUrl) ?>"
        data-recovery-confirm="Cache leeren — nach DB/Config-Anpassungen?"
        data-recovery-confirm-title="Cache leeren">
        <?php recoveryRenderFormModeHiddenFields(RECOVERY_MODE_DIRECTORY_STRUCTURE, $authHash); ?>
        <input type="hidden" name="recovery_clear_cache_after_paths" value="1">
        <button type="submit" class="button"><?= recoveryFaIcon(16, 'broom') ?> Cache jetzt leeren</button>
    </form>
    <a href="<?= \htmlspecialchars(recoveryHomeUrl($authHash)) ?>" class="button"><?= recoveryFaIcon(16, 'house') ?> Zurück zum Start</a>
    <?php
    recoveryRenderWorkflowStatusBlock(
        $workflowOkItems,
        [\ob_get_clean()],
        $cacheHint,
        $cacheCleared ? 'Cache wurde geleert.' : null
    );
    ?>
    <?php if (!recoveryUsesNativeAcpUi()): ?>
    </div>
    <?php endif; ?>
    <?php
}


/**
 * @param array{ok: bool, applied: int, log: list<string>}|null $applyDbResult
 */
function recoveryRenderDirectoryStructureDomainSection(
    string $authHash,
    \wcf\system\database\Database $db,
    int $wcfN,
    string $modeUrl,
    ?array $applyDbResult = null
): void {
    $domainManualUrl = 'https://manual.woltlab.com/de/customize-directory-structure/#domain-andern';
    $request = recoveryDetectCurrentRequestDomain();
    $domainRows = recoveryBuildDomainMismatchReport($db, $wcfN);
    $mismatches = \array_values(\array_filter($domainRows, static fn (array $r): bool => !empty($r['mismatch'])));
    $hasMismatch = $mismatches !== [];
    $domainApplied = isset($_GET['domain_applied']);
    if ($domainApplied) {
        $applyDbResult = recoverySessionPullFlash($authHash, 'dir_domain') ?? $applyDbResult;
    }
    $suggestedUrl = $request['full'];
    $suggestedCookie = $request['host'];
    if (\str_starts_with($suggestedCookie, 'www.')) {
        $suggestedCookie = \substr($suggestedCookie, 4);
    }
    $statusLabel = $hasMismatch
        ? \count($mismatches) . ' Abweichung' . (\count($mismatches) === 1 ? '' : 'en') . ' in der DB'
        : 'DB stimmt mit Aufruf-Domain überein';
    $statusClass = $hasMismatch ? 'recovery-domain-status--warn' : 'recovery-domain-status--match';
    $confirmMessage = $hasMismatch
        ? 'domainName und cookieDomain für alle Applications auf ' . $suggestedUrl . ' setzen und Cache leeren?'
        : 'Domain-Werte erneut auf ' . $suggestedUrl . ' setzen und Cache leeren? (DB stimmt bereits mit der Aufruf-Domain überein.)';

    if (recoveryUsesNativeAcpUi()) {
        $sectionDesc = \htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8')
            . ' — Entspricht dem <a href="' . \htmlspecialchars($domainManualUrl, ENT_QUOTES, 'UTF-8')
            . '" target="_blank" rel="noopener"><strong>WoltLab-Handbuch: Domain ändern</strong></a>. '
            . 'Nur Datenbank; Dateiverschiebung, DNS, Webserver und <code>config.inc.php</code> bleiben manuell.';
        recoveryRenderAcpSectionStart('Domain in der Datenbank setzen', $sectionDesc, true);
        ?>
        <ol>
            <li>Sie rufen das Tool unter Ihrer <strong>Ziel-Domain</strong> auf (z.&nbsp;B. nach Umzug).</li>
            <li>Das Tool liest den Host automatisch aus der aktuellen URL.</li>
            <li>Mit dem Button werden <code>domainName</code> und <code>cookieDomain</code> in der Datenbank gesetzt.</li>
        </ol>
        <dl>
            <dt>Aktuelle Aufruf-URL</dt>
            <dd><code><?= \htmlspecialchars($request['full']) ?></code></dd>
            <dt><code>domainName</code> <small>(alle Zeilen in <code>wcf<?= (int) $wcfN ?>_application</code>)</small></dt>
            <dd><code><?= \htmlspecialchars($suggestedUrl) ?></code></dd>
            <dt><code>cookieDomain</code></dt>
            <dd><code><?= \htmlspecialchars($suggestedCookie) ?></code></dd>
        </dl>
        <?php if ($domainApplied && $applyDbResult !== null): ?>
        <?php
            $domainFlash = '<strong>Letztes Domain-Update:</strong><br>';
            foreach ($applyDbResult['log'] ?? [] as $line) {
                $domainFlash .= \htmlspecialchars((string) $line) . '<br>';
            }
            recoveryRenderAlert(!empty($applyDbResult['ok']) ? 'success' : 'warning', $domainFlash, null, true);
        ?>
        <?php endif; ?>
        <div class="formSubmit">
            <form method="POST" action="<?= \htmlspecialchars($modeUrl) ?>"
                data-recovery-confirm="<?= \htmlspecialchars($confirmMessage, ENT_QUOTES, 'UTF-8') ?>"
                data-recovery-confirm-title="Domain in DB aktualisieren"
                data-recovery-confirm-ok="Domain aktualisieren"
                data-recovery-loading="Domain wird aktualisiert und Cache geleert …">
                <?php recoveryRenderFormModeHiddenFields(RECOVERY_MODE_DIRECTORY_STRUCTURE, $authHash); ?>
                <input type="hidden" name="recovery_apply_domain_db" value="1">
                <input type="hidden" name="recovery_domain_url" value="<?= \htmlspecialchars($suggestedUrl) ?>">
                <input type="hidden" name="recovery_cookie_domain" value="<?= \htmlspecialchars($suggestedCookie) ?>">
                <button type="submit" class="button buttonPrimary">Domain in DB aktualisieren</button>
            </form>
        </div>
        <?php
        recoveryRenderAcpSectionEnd();
        recoveryRenderAcpSectionStart(
            'DB-Stand aller Applications',
            (string) \count($domainRows) . ' Einträge — Abweichungen zur aktuellen Aufruf-Domain sind markiert.'
        );
        ?>
        <table class="table tableList">
            <thead>
                <tr>
                    <th class="columnIcon" aria-label="Status"><span class="silent">Status</span></th>
                    <th>Package</th>
                    <th>domainName (DB)</th>
                    <th>cookieDomain</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($domainRows as $dr): ?>
            <tr>
                <td class="columnIcon"><?= !empty($dr['mismatch']) ? recoveryFaIcon(16, 'triangle-exclamation') : recoveryFaIcon(16, 'circle-check') ?></td>
                <td><code><?= \htmlspecialchars($dr['package']) ?></code></td>
                <td><code><?= \htmlspecialchars($dr['domainName'] !== '' ? $dr['domainName'] : '—') ?></code></td>
                <td><code><?= \htmlspecialchars($dr['cookieDomain'] !== '' ? $dr['cookieDomain'] : '—') ?></code></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php
        recoveryRenderAcpSectionEnd();

        return;
    }
    ?>
    <section class="recovery-panel recovery-domain-panel<?= $hasMismatch ? ' recovery-domain-panel--warn' : '' ?>" aria-labelledby="recovery-domain-heading">
        <header class="recovery-domain-header" id="recovery-domain-heading">
            <strong><?= recoveryFaIcon(16, 'globe') ?> Domain in der Datenbank setzen</strong>
            <span class="recovery-domain-status <?= $statusClass ?>"><?= \htmlspecialchars($statusLabel) ?></span>
        </header>
        <div class="recovery-panel__body">
        <ol class="recovery-domain-flow">
            <li>Sie rufen das Tool unter Ihrer <strong>Ziel-Domain</strong> auf (z.&nbsp;B. nach Umzug).</li>
            <li>Das Tool liest den Host automatisch aus der aktuellen URL.</li>
            <li>Mit dem Button werden <code>domainName</code> und <code>cookieDomain</code> in der Datenbank gesetzt.</li>
        </ol>
        <div class="recovery-domain-current-url">
            <p class="recovery-domain-current-url__label"><strong>Aktuelle Aufruf-URL</strong></p>
            <code class="recovery-domain-current-url__value"><?= \htmlspecialchars($request['full']) ?></code>
        </div>
        <div class="recovery-domain-write-preview">
            <p class="recovery-domain-write-preview__label"><strong>Wird in die Datenbank geschrieben</strong> (alle Zeilen in <code>wcf<?= (int) $wcfN ?>_application</code>)</p>
            <dl class="recovery-domain-write-preview__fields">
                <div class="recovery-domain-write-preview__field">
                    <dt><code>domainName</code></dt>
                    <dd><code class="recovery-domain-write-preview__value"><?= \htmlspecialchars($suggestedUrl) ?></code></dd>
                </div>
                <div class="recovery-domain-write-preview__field">
                    <dt><code>cookieDomain</code></dt>
                    <dd><code class="recovery-domain-write-preview__value"><?= \htmlspecialchars($suggestedCookie) ?></code></dd>
                </div>
            </dl>
        </div>
        <p class="recovery-panel__hint">
            Entspricht dem
            <a href="<?= \htmlspecialchars($domainManualUrl) ?>" target="_blank" rel="noopener"><strong>WoltLab-Handbuch: Domain ändern</strong></a>
            — nur der Datenbank-Teil. Dateiverschiebung, DNS, Webserver und <code>config.inc.php</code> bleiben manuell.
        </p>
        <?php if ($domainApplied && $applyDbResult !== null): ?>
        <?php
            $domainFlashLegacy = '<strong>Letztes Domain-Update:</strong><br>';
            foreach ($applyDbResult['log'] ?? [] as $line) {
                $domainFlashLegacy .= \htmlspecialchars((string) $line) . '<br>';
            }
            recoveryRenderAlert(!empty($applyDbResult['ok']) ? 'success' : 'warning', $domainFlashLegacy, null, true);
        ?>
        <?php endif; ?>
        <form method="POST" action="<?= \htmlspecialchars($modeUrl) ?>"
            class="recovery-domain-action__form"
            data-recovery-confirm="<?= \htmlspecialchars($confirmMessage, ENT_QUOTES, 'UTF-8') ?>"
            data-recovery-confirm-title="Domain in DB aktualisieren"
            data-recovery-confirm-ok="Domain aktualisieren"
            data-recovery-loading="Domain wird aktualisiert und Cache geleert …">
            <?php recoveryRenderFormModeHiddenFields(RECOVERY_MODE_DIRECTORY_STRUCTURE, $authHash); ?>
            <input type="hidden" name="recovery_apply_domain_db" value="1">
            <input type="hidden" name="recovery_domain_url" value="<?= \htmlspecialchars($suggestedUrl) ?>">
            <input type="hidden" name="recovery_cookie_domain" value="<?= \htmlspecialchars($suggestedCookie) ?>">
            <?php
            recoveryRenderActionBar([
                '<button type="submit" class="button buttonPrimary buttonLarge">' . recoveryFaIcon(16, 'globe') . ' Domain in DB aktualisieren</button>',
            ], 'recovery-domain-action__bar');
            ?>
        </form>
        <details class="recovery-domain-details"<?= $hasMismatch ? ' open' : '' ?>>
            <summary>DB-Stand aller Applications (<?= \count($domainRows) ?>)</summary>
            <table class="tableList recovery-table-list">
            <thead><tr><th></th><th>Package</th><th>domainName (DB)</th><th>cookieDomain</th></tr></thead>
            <tbody>
            <?php foreach ($domainRows as $dr): ?>
            <tr>
                <td class="columnIcon"><?= !empty($dr['mismatch']) ? recoveryFaIcon(16, 'triangle-exclamation') : recoveryFaIcon(16, 'circle-check') ?></td>
                <td><code><?= \htmlspecialchars($dr['package']) ?></code></td>
                <td><code><?= \htmlspecialchars($dr['domainName'] !== '' ? $dr['domainName'] : '—') ?></code></td>
                <td><code><?= \htmlspecialchars($dr['cookieDomain'] !== '' ? $dr['cookieDomain'] : '—') ?></code></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </details>
        </div>
    </section>
    <?php
}


/**
 * Generische Vollbereinigung für jedes Plugin (ohne WoltLab-Paket-Deinstaller).
 *
 * @param array<string, mixed>|null $resources
 * @param array<string, mixed> $log
 */
function recoveryPerformFullPluginCleanup(
    \wcf\system\database\Database $db,
    int $wcfN,
    string $packageIdentifier,
    ?array $packageData,
    ?array $resources,
    array &$log,
    bool $deleteFilesOnDisk = false,
    ?string $extractDir = null,
    bool $sqlRollback = false,
    bool $rebuildBootstrap = true,
    bool $dryRun = false,
    bool $runUninstallScript = true
): void {
    $packageIdentifier = recoveryValidatePackageIdentifier($packageIdentifier);

    if ($wcfN < 1 || $wcfN > 99) {
        throw new \InvalidArgumentException('Ungültige WCF-Instanznummer.');
    }

    $packageID = $packageData ? (int) $packageData['packageID'] : null;
    $removalOpts = [
        'dryRun' => $dryRun,
        'sqlRollback' => $sqlRollback,
        'deleteFiles' => $deleteFilesOnDisk,
        'rebuildBootstrap' => $rebuildBootstrap,
        'runUninstallScript' => $runUninstallScript,
    ];

    recoveryRunPreDbRemovalSteps($db, $wcfN, $packageIdentifier, $packageID, $removalOpts, $log);

    $optionConstants = recoveryCollectOptionConstantNames($db, $wcfN, $packageID);
    if ($resources && !empty($resources['options']['items'])) {
        foreach ($resources['options']['items'] as $name) {
            $optionConstants[] = \strtoupper((string) $name);
        }
    }
    $optionConstants = \array_values(\array_unique($optionConstants));

    recoveryCleanupPackageInstallationArtifacts($db, $wcfN, $packageID, $packageIdentifier, $log);
    recoveryCleanupPackageUpdateEntries($db, $wcfN, $packageIdentifier, $log);

    if ($packageID) {
        $packageIdTables = [
            'template_listener' => 'Template-Listener',
            'event_listener' => 'Event-Listener',
            'option' => 'Optionen',
            'acp_menu_item' => 'ACP-Menü',
            'user_group_option' => 'Berechtigungen',
            'cronjob' => 'Cronjobs',
            'object_type' => 'Objekttypen',
            'page' => 'Seiten',
            'language_item' => 'Sprachvariablen',
            'box' => 'Boxen',
            'template' => 'Templates',
            'core_object' => 'Core-Objekte',
            'user_notification_event' => 'Benachrichtigungen',
            'bbcode' => 'BBCodes',
            'smiley' => 'Smileys',
            'application' => 'Application',
            'package_exclusion' => 'Package-Exclusions',
            'package_installation_plugin' => 'Package-Installation-Plugins',
            'package_installation_file_log' => 'Package-File-Log',
        ];

        foreach (recoveryDiscoverPackageIdTables($db, $wcfN) as $discoveredTable) {
            if (!isset($packageIdTables[$discoveredTable])) {
                $packageIdTables[$discoveredTable] = 'DB: ' . $discoveredTable;
            }
        }

        foreach ($packageIdTables as $table => $label) {
            recoveryTryDeleteByPackageId($db, $wcfN, $table, $packageID, $label, $log);
        }

        recoveryTryDeletePackageRequirements($db, $wcfN, $packageID, $log);

        if (!$sqlRollback) {
            recoveryTryExecuteDelete(
                $db,
                "DELETE FROM wcf{$wcfN}_package_installation_sql_log WHERE packageID = ?",
                [$packageID],
                'Package SQL-Log',
                $log
            );
        }

        recoveryTryExecuteDelete(
            $db,
            "DELETE FROM wcf{$wcfN}_package WHERE packageID = ?",
            [$packageID],
            'Package-Eintrag',
            $log
        );
    } else {
        if ($resources && !empty($resources['acpMenu']['prefix'])) {
            recoveryExecuteDelete(
                $db,
                "DELETE FROM wcf{$wcfN}_acp_menu_item WHERE menuItem LIKE ?",
                [$resources['acpMenu']['prefix'] . '%'],
                'ACP-Menü (Analyse)',
                $log
            );
        }

        if ($resources && !empty($resources['options']['prefix'])) {
            recoveryExecuteDelete(
                $db,
                "DELETE FROM wcf{$wcfN}_option WHERE optionName LIKE ?",
                [$resources['options']['prefix'] . '%'],
                'Optionen (Analyse)',
                $log
            );
        }

        if ($resources && !empty($resources['permissions']['prefix'])) {
            recoveryExecuteDelete(
                $db,
                "DELETE FROM wcf{$wcfN}_user_group_option WHERE optionName LIKE ?",
                [$resources['permissions']['prefix'] . '%'],
                'Berechtigungen (Analyse)',
                $log
            );
        }

        if ($resources && !empty($resources['language']['prefix'])) {
            recoveryExecuteDelete(
                $db,
                "DELETE FROM wcf{$wcfN}_language_item WHERE languageItem LIKE ?",
                [$resources['language']['prefix'] . '%'],
                'Sprachvariablen (Analyse)',
                $log
            );
        }

        if ($resources && !empty($resources['cronjobs']['namespace'])) {
            recoveryExecuteDelete(
                $db,
                "DELETE FROM wcf{$wcfN}_cronjob WHERE className LIKE ?",
                [$resources['cronjobs']['namespace'] . '%'],
                'Cronjobs (Analyse)',
                $log
            );
        }

        if ($resources && !empty($resources['objectTypes']['prefix'])) {
            recoveryExecuteDelete(
                $db,
                "DELETE FROM wcf{$wcfN}_object_type WHERE objectType LIKE ?",
                [$resources['objectTypes']['prefix'] . '%'],
                'Objekttypen (Analyse)',
                $log
            );
        }

        $parts = \explode('.', $packageIdentifier);
        $appGuess = \count($parts) >= 2 ? $parts[\count($parts) - 2] : \end($parts);
        if ($appGuess) {
            recoveryExecuteDelete(
                $db,
                "DELETE FROM wcf{$wcfN}_template_listener WHERE listenerName LIKE ?",
                [$appGuess . '%'],
                'Template-Listener (Vermutung)',
                $log
            );
            recoveryExecuteDelete(
                $db,
                "DELETE FROM wcf{$wcfN}_page WHERE identifier LIKE ?",
                [$packageIdentifier . '%'],
                'Seiten (Package-Identifier)',
                $log
            );
        }
    }

    if ($resources && !empty($resources['pageLocations']['items'])) {
        foreach ($resources['pageLocations']['items'] as $identifier) {
            recoveryExecuteDelete(
                $db,
                "DELETE FROM wcf{$wcfN}_page_location WHERE identifier = ?",
                [$identifier],
                'Page Location',
                $log
            );
        }
    }

    if ($resources && !empty($resources['urlRules']['items'])) {
        foreach ($resources['urlRules']['items'] as $pattern) {
            recoveryExecuteDelete(
                $db,
                "DELETE FROM wcf{$wcfN}_url_rule WHERE pattern = ?",
                [$pattern],
                'URL-Regel',
                $log
            );
        }
    }

    $tables = [];
    if ($resources && !empty($resources['tables'])) {
        $tables = $resources['tables'];
    } elseif ($packageData && !empty($packageData['packageDir'])) {
        $tables = \function_exists('findPackageTables')
            ? findPackageTables($db, $packageIdentifier, $wcfN)
            : [];
    } else {
        $tables = \function_exists('findPackageTables')
            ? findPackageTables($db, $packageIdentifier, $wcfN)
            : [];
    }

    foreach ($tables as $table) {
        $safeTable = \str_replace('`', '', (string) $table);
        if (!recoveryValidateSqlTableName($safeTable)) {
            $log[] = 'Tabelle übersprungen (ungültiger Name): ' . $safeTable;

            continue;
        }

        $baseTables = \array_map(
            static fn(string $name): string => \str_replace('`', '', $name),
            getBasePluginTables($wcfN)
        );
        if (\in_array($safeTable, $baseTables, true)) {
            $log[] = 'Tabelle übersprungen (WoltLab-Basistabelle): ' . $safeTable;

            continue;
        }

        $sql = 'DROP TABLE IF EXISTS `' . $safeTable . '`';
        $statement = $db->prepareStatement($sql);
        $statement->execute();
        $log[] = 'Tabelle gelöscht: ' . $safeTable;
    }

    if (recoveryRebuildOptionsIncPhp()) {
        $log[] = 'options.inc.php neu erzeugt';
    } elseif (!empty($optionConstants)) {
        recoveryStripConstantsFromOptionsIncPhp($optionConstants);
        $log[] = 'options.inc.php bereinigt (Plugin-Konstanten entfernt)';
    }

    recoveryRunPostDbRemovalSteps(
        $db,
        $wcfN,
        $packageIdentifier,
        $packageData,
        $packageID,
        $removalOpts,
        $log,
        $extractDir
    );

    if (!$dryRun && ($deleteFilesOnDisk || $rebuildBootstrap)) {
        $optionFbLog = [];
        recoveryEnsureOptionConstantFallbacks($db, $wcfN, $optionFbLog);
        foreach ($optionFbLog as $entry) {
            $log[] = $entry;
        }
        $deletedCacheFiles = clearCompiledTemplates();
        $log[] = 'Cache gelöscht: ' . $deletedCacheFiles . ' Dateien';
    }
}

// ============================================================================
// PIP RESOURCE MAP + DB-COUNTS + SQL-BACKUP (v1.2.7)
// ============================================================================

/**
 * WoltLab PIP → DB-Ressourcen-Matrix.
 * Inspiriert vom offiziellen PackageUninstallationDispatcher und den PIP-Klassen:
 * AbstractXMLPackageInstallationPlugin::uninstall() → DELETE WHERE packageID = ?
 *
 * Quellen:
 *   EventListenerPIP::tableName          = 'event_listener'
 *   TemplateListenerPIP::tableName       = 'template_listener'
 *   OptionPIP::tableName                 = 'option'
 *   UserGroupOptionPIP::tableName        = 'user_group_option'
 *   UserOptionPIP::tableName             = 'user_option'
 *   CronjobPIP::tableName                = 'cronjob'
 *   ObjectTypePIP::tableName             = 'object_type'
 *   BBCodePIP::tableName                 = 'bbcode'
 *   SmileyPIP::tableName                 = 'smiley'
 *   UserMenuPIP::tableName               = 'user_menu_item'
 *   UserNotificationEventPIP::tableName  = 'user_notification_event'
 *   ACLOptionPIP::tableName              = 'acl_option'
 *   BoxPIP::uninstall() → DELETE FROM wcf1_box WHERE … packageID = ?
 *   PagePIP → 'page' (packageID)
 *   MenuPIP, MenuItemPIP → 'menu', 'menu_item' (packageID)
 *
 * @return array<string, array{table: string, col: string, safe: bool, label: string}>
 */
function recoveryGetPipResourceMap(): array
{
    return [
        // ── Core PIPs – tableName explizit in Quellcode ──────────────────────
        'acpMenu'               => ['table' => 'acp_menu_item',              'col' => 'packageID', 'safe' => true,  'label' => 'ACP-Menüeinträge'],
        'eventListener'         => ['table' => 'event_listener',             'col' => 'packageID', 'safe' => true,  'label' => 'Event-Listener'],
        'templateListener'      => ['table' => 'template_listener',          'col' => 'packageID', 'safe' => true,  'label' => 'Template-Listener'],
        'option'                => ['table' => 'option',                     'col' => 'packageID', 'safe' => true,  'label' => 'Optionen (ACP)'],
        'userGroupOption'       => ['table' => 'user_group_option',          'col' => 'packageID', 'safe' => true,  'label' => 'Benutzergruppen-Optionen'],
        'userOption'            => ['table' => 'user_option',                'col' => 'packageID', 'safe' => true,  'label' => 'Benutzer-Optionen'],
        'cronjob'               => ['table' => 'cronjob',                    'col' => 'packageID', 'safe' => true,  'label' => 'Cronjobs'],
        'objectType'            => ['table' => 'object_type',                'col' => 'packageID', 'safe' => true,  'label' => 'Objekttypen'],
        'objectTypeDefinition'  => ['table' => 'object_type_definition',     'col' => 'packageID', 'safe' => true,  'label' => 'Objekttyp-Definitionen'],
        'language'              => ['table' => 'language_item',              'col' => 'packageID', 'safe' => true,  'label' => 'Sprachvariablen'],
        'template'              => ['table' => 'template',                   'col' => 'packageID', 'safe' => true,  'label' => 'Templates (Frontend)'],
        'acpTemplate'           => ['table' => 'acp_template',               'col' => 'packageID', 'safe' => true,  'label' => 'ACP-Templates'],
        'page'                  => ['table' => 'page',                       'col' => 'packageID', 'safe' => true,  'label' => 'Seiten (CMS)'],
        'box'                   => ['table' => 'box',                        'col' => 'packageID', 'safe' => true,  'label' => 'Boxen'],
        'userMenu'              => ['table' => 'user_menu_item',             'col' => 'packageID', 'safe' => true,  'label' => 'Benutzer-Menüeinträge'],
        'userNotificationEvent' => ['table' => 'user_notification_event',    'col' => 'packageID', 'safe' => true,  'label' => 'Benachrichtigungs-Events'],
        'bbcode'                => ['table' => 'bbcode',                     'col' => 'packageID', 'safe' => true,  'label' => 'BBCodes'],
        'smiley'                => ['table' => 'smiley',                     'col' => 'packageID', 'safe' => true,  'label' => 'Smileys'],
        'aclOption'             => ['table' => 'acl_option',                 'col' => 'packageID', 'safe' => true,  'label' => 'ACL-Optionen'],
        'coreObject'            => ['table' => 'core_object',                'col' => 'packageID', 'safe' => true,  'label' => 'Core-Objekte'],
        'clipboardAction'       => ['table' => 'clipboard_action',           'col' => 'packageID', 'safe' => true,  'label' => 'Zwischenablage-Aktionen'],
        'acpSearchProvider'     => ['table' => 'acp_search_provider',        'col' => 'packageID', 'safe' => true,  'label' => 'ACP-Suchanbieter'],
        'mediaProvider'         => ['table' => 'media_provider',             'col' => 'packageID', 'safe' => true,  'label' => 'Media-Anbieter'],
        'menu'                  => ['table' => 'menu',                       'col' => 'packageID', 'safe' => true,  'label' => 'Frontend-Menüs'],
        'menuItem'              => ['table' => 'menu_item',                  'col' => 'packageID', 'safe' => true,  'label' => 'Frontend-Menüeinträge'],
        'pip'                   => ['table' => 'package_installation_plugin','col' => 'packageID', 'safe' => true,  'label' => 'PIPs (package_installation_plugin)'],
        'application'           => ['table' => 'application',              'col' => 'packageID', 'safe' => true,  'label' => 'Application (Frontend-Registrierung)'],
        // ── Spezial-PIPs – kein direkter DB-Tabellen-Eintrag ─────────────────
        'file'                  => ['table' => '',                           'col' => '',          'safe' => false, 'label' => 'Dateien (Dateisystem)'],
        'database'              => ['table' => '',                           'col' => '',          'safe' => false, 'label' => 'Datenbank-Tabellen (DROP TABLE)'],
        'script'                => ['table' => '',                           'col' => '',          'safe' => false, 'label' => 'Install-Script'],
        'sql'                   => ['table' => '',                           'col' => '',          'safe' => false, 'label' => 'Rohe SQL-Anweisungen'],
    ];
}

/**
 * Zählt Datenbankzeilen pro PIP (WHERE packageID = $packageID).
 * Gibt -1 zurück wenn die Tabelle nicht existiert.
 *
 * @return array<string, int>
 */
function recoveryGetPipDbCounts(
    \wcf\system\database\Database $db,
    int $wcfN,
    int $packageID
): array {
    $map = recoveryGetPipResourceMap();
    $counts = [];

    foreach ($map as $pipName => $info) {
        if (!$info['safe'] || $info['col'] !== 'packageID' || $info['table'] === '') {
            continue;
        }

        try {
            $sql = "SELECT COUNT(*) AS cnt FROM wcf{$wcfN}_{$info['table']} WHERE packageID = ?";
            $statement = $db->prepareStatement($sql);
            $statement->execute([$packageID]);
            $row = $statement->fetchArray();
            $counts[$pipName] = (int)($row['cnt'] ?? 0);
        } catch (\Throwable $ignored) {
            $counts[$pipName] = -1;
        }
    }

    return $counts;
}

/**
 * PIP-Map inkl. discovered_* Tabellen für Uninstall-Analyse und -Ausführung.
 *
 * @return array{map: array<string, array{table: string, col: string, safe: bool, label: string}>, counts: array<string, int>}
 */
function recoveryBuildUninstallPipContext(
    \wcf\system\database\Database $db,
    int $wcfN,
    ?int $packageID
): array {
    $pipMap = recoveryGetPipResourceMap();
    $pipCounts = [];
    if ($packageID !== null && $packageID > 0) {
        $pipCounts = recoveryGetPipDbCounts($db, $wcfN, $packageID);
        recoveryMergeDiscoveredPipTables($pipMap, $pipCounts, $db, $wcfN, $packageID);
    }

    return ['map' => $pipMap, 'counts' => $pipCounts];
}

/**
 * Ergänzt pipMap/pipCounts um weitere Tabellen mit packageID-Spalte (z. B. Core-Tabellen).
 *
 * @param array<string, array{table: string, col: string, safe: bool, label: string}> $pipMap
 * @param array<string, int> $pipCounts
 */
function recoveryMergeDiscoveredPipTables(
    array &$pipMap,
    array &$pipCounts,
    \wcf\system\database\Database $db,
    int $wcfN,
    int $packageID
): void {
    $knownTables = [];
    foreach ($pipMap as $info) {
        if ($info['table'] !== '') {
            $knownTables[$info['table']] = true;
        }
    }

    foreach (recoveryDiscoverPackageIdTables($db, $wcfN) as $discTable) {
        if (isset($knownTables[$discTable])) {
            continue;
        }

        $pipKey = 'discovered_' . $discTable;
        try {
            $sql = "SELECT COUNT(*) AS cnt FROM wcf{$wcfN}_{$discTable} WHERE packageID = ?";
            $statement = $db->prepareStatement($sql);
            $statement->execute([$packageID]);
            $row = $statement->fetchArray();
            $pipMap[$pipKey] = [
                'table' => $discTable,
                'col' => 'packageID',
                'safe' => true,
                'label' => 'Weitere DB-Tabelle',
            ];
            $pipCounts[$pipKey] = (int) ($row['cnt'] ?? 0);
        } catch (\Throwable $ignored) {
            $pipCounts[$pipKey] = -1;
        }
    }
}

function recoveryRenderPipCountCell(int $count, string $tableName, int $packageID): string
{
    if ($count <= 0) {
        return '0';
    }

    return '<button type="button" class="recovery-pip-count-btn" data-table="'
        . \htmlspecialchars($tableName, ENT_QUOTES, 'UTF-8') . '" data-package-id="'
        . $packageID . '" title="Einträge dieses Plugins anzeigen">' . $count . '</button>';
}

/**
 * Generiert SQL-INSERT-Backup aller betroffenen Zeilen (WHERE packageID = $packageID).
 * Nur für ausgewählte PIP-Kategorien aus recoveryGetPipResourceMap().
 * Pure PHP – kein mysqldump erforderlich.
 *
 * @param list<string> $selectedPips
 */
function recoveryGenerateSqlBackup(
    \wcf\system\database\Database $db,
    int $wcfN,
    int $packageID,
    array $selectedPips
): string {
    $map = recoveryGetPipResourceMap();
    $out  = "-- ============================================================\n";
    $out .= "-- WoltLab Recovery Tool v" . RECOVERY_VERSION . " – SQL-Backup\n";
    $out .= "-- Package-ID: {$packageID} | WCF_N: {$wcfN}\n";
    $out .= "-- Erstellt: " . \date('Y-m-d H:i:s') . "\n";
    $out .= "-- Nur Zeilen mit packageID = {$packageID} – kein Komplett-Dump!\n";
    $out .= "-- Zum Wiederherstellen: SQL in phpMyAdmin oder CLI ausführen.\n";
    $out .= "-- ============================================================\n\n";

    foreach ($selectedPips as $pipName) {
        if (!isset($map[$pipName])) {
            continue;
        }

        $info = $map[$pipName];
        if (!$info['safe'] || $info['col'] !== 'packageID' || $info['table'] === '') {
            continue;
        }

        $tableFull = "wcf{$wcfN}_{$info['table']}";

        try {
            $statement = $db->prepareStatement("SELECT * FROM {$tableFull} WHERE packageID = ?");
            $statement->execute([$packageID]);

            $rows = [];
            while ($row = $statement->fetchArray()) {
                $rows[] = $row;
            }

            if (empty($rows)) {
                continue;
            }

            $out .= "-- ── {$tableFull} ({$info['label']}) – " . \count($rows) . " Zeile(n) ──\n";

            foreach ($rows as $row) {
                $cols = \array_keys($row);
                $vals = \array_map(static function ($v): string {
                    if ($v === null) {
                        return 'NULL';
                    }
                    return "'" . \addslashes((string)$v) . "'";
                }, \array_values($row));

                $out .= 'INSERT INTO `' . $tableFull . '` (`'
                    . \implode('`, `', $cols) . '`) VALUES ('
                    . \implode(', ', $vals) . ");\n";
            }

            $out .= "\n";
        } catch (\Throwable $e) {
            $out .= "-- Backup für {$tableFull} fehlgeschlagen: " . $e->getMessage() . "\n\n";
        }
    }

    return $out;
}


// ============================================================================
// USER MANAGEMENT HELPERS
// ============================================================================

function recoveryUserHashPassword(string $password): string
{
    // WoltLab Suite 6.x: "Bcrypt:{php_bcrypt_hash}" (wie wsc-recovery.php multifactor-backup)
    return 'Bcrypt:' . \password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
}

function recoveryUserGenerateRandomPassword(int $length = 16): string
{
    return \substr(
        \str_replace(['+', '/', '='], '', \base64_encode(\random_bytes(20))),
        0,
        $length
    );
}

function recoveryUserSearch(\wcf\system\database\Database $db, string $query): array
{
    $n = WCF_N;
    $sql = "SELECT userID, username, email, banned, activationCode, multifactorActive
            FROM wcf{$n}_user
            WHERE username LIKE ? OR email LIKE ?
            ORDER BY userID
            LIMIT 50";
    $stmt = $db->prepareStatement($sql);
    $stmt->execute([$query . '%', $query . '%']);
    $users = [];
    while ($row = $stmt->fetchArray()) {
        $users[] = $row;
    }
    return $users;
}

function recoveryUserGetByID(\wcf\system\database\Database $db, int $userID): ?array
{
    $n = WCF_N;
    $sql = "SELECT userID, username, email, banned, activationCode, multifactorActive
            FROM wcf{$n}_user WHERE userID = ?";
    $stmt = $db->prepareStatement($sql);
    $stmt->execute([$userID]);
    $row = $stmt->fetchArray();
    return $row ?: null;
}

function recoveryUserGetAllGroups(\wcf\system\database\Database $db): array
{
    $n = WCF_N;
    $sql = "SELECT groupID, groupName, groupType FROM wcf{$n}_user_group ORDER BY groupID";
    $stmt = $db->prepareStatement($sql);
    $stmt->execute([]);
    $groups = [];
    while ($row = $stmt->fetchArray()) {
        $groups[] = $row;
    }
    return $groups;
}

function recoveryUserGetGroupIDs(\wcf\system\database\Database $db, int $userID): array
{
    $n = WCF_N;
    $sql = "SELECT groupID FROM wcf{$n}_user_to_group WHERE userID = ?";
    $stmt = $db->prepareStatement($sql);
    $stmt->execute([$userID]);
    $ids = [];
    while ($row = $stmt->fetchArray()) {
        $ids[] = (int)$row['groupID'];
    }
    return $ids;
}

function recoveryUserResetPassword(\wcf\system\database\Database $db, int $userID, string $newPassword): void
{
    $n = WCF_N;
    $hash = recoveryUserHashPassword($newPassword);
    // accessToken leeren → alle Sitzungen ungültig
    $sql = "UPDATE wcf{$n}_user SET password = ?, accessToken = '' WHERE userID = ?";
    $stmt = $db->prepareStatement($sql);
    $stmt->execute([$hash, $userID]);
}

function recoveryUserSetGroups(\wcf\system\database\Database $db, int $userID, array $groupIDs): void
{
    $n = WCF_N;
    // System-Gruppen (Everyone=1, Registered=2) immer behalten
    foreach ([1, 2] as $sys) {
        if (!\in_array($sys, $groupIDs, true)) {
            $groupIDs[] = $sys;
        }
    }
    $groupIDs = \array_unique(\array_map('intval', $groupIDs));

    $sql = "DELETE FROM wcf{$n}_user_to_group WHERE userID = ?";
    $stmt = $db->prepareStatement($sql);
    $stmt->execute([$userID]);

    foreach ($groupIDs as $gid) {
        $sql = "INSERT IGNORE INTO wcf{$n}_user_to_group (userID, groupID) VALUES (?, ?)";
        $stmt = $db->prepareStatement($sql);
        $stmt->execute([$userID, $gid]);
    }
}

function recoveryUserChangeEmail(\wcf\system\database\Database $db, int $userID, string $email): void
{
    $n = WCF_N;
    $sql = "UPDATE wcf{$n}_user SET email = ? WHERE userID = ?";
    $stmt = $db->prepareStatement($sql);
    $stmt->execute([$email, $userID]);
}

function recoveryUserActivate(\wcf\system\database\Database $db, int $userID): void
{
    $n = WCF_N;
    $sql = "UPDATE wcf{$n}_user SET activationCode = 0, banned = 0, banReason = '', banExpires = 0 WHERE userID = ?";
    $stmt = $db->prepareStatement($sql);
    $stmt->execute([$userID]);
}

function recoveryUserDisable2FA(\wcf\system\database\Database $db, int $userID): void
{
    $n = WCF_N;
    $sql = "UPDATE wcf{$n}_user SET multifactorActive = 0 WHERE userID = ?";
    $stmt = $db->prepareStatement($sql);
    $stmt->execute([$userID]);
    // Alle 2FA-Setups (inkl. Backup-Codes) löschen
    $sql = "DELETE FROM wcf{$n}_user_multifactor WHERE userID = ?";
    $stmt = $db->prepareStatement($sql);
    $stmt->execute([$userID]);
}


// UI: lib/Recovery/Ui/SetupAssets.php, AcpLayout.php, recovery-acp-extensions.css

function recoveryRenderBackLink(string $href): void
{
    echo '<a href="' . \htmlspecialchars($href) . '" class="button"><fa-icon size="16" name="arrow-left" solid></fa-icon> Zurück zur Auswahl</a>';
}

function recoveryHomeUrl(string $authHash): string
{
    return recoveryBuildHomeUrl($authHash);
}

/**
 * @return array{display: string, title: ?string}
 */
function recoveryTruncateRuntimeDisplay(string $value, int $maxLen = 72): array
{
    if ($maxLen < 8 || \mb_strlen($value) <= $maxLen) {
        return ['display' => $value, 'title' => null];
    }

    return [
        'display' => \mb_substr($value, 0, $maxLen - 1) . '…',
        'title' => $value,
    ];
}

/**
 * @return array{text: string, class: string}
 */
function recoveryResolveRuntimeInfoStatus(string $label): array
{
    return match ($label) {
        'Recovery-URL' => ['text' => 'Nur Dateiname', 'class' => 'badgeYellow'],
        default => ['text' => 'OK', 'class' => 'badgeGreen'],
    };
}

/**
 * @return list<array{label: string, value: string, truncate?: bool}>
 */
function recoveryBuildRuntimeInfoRows(string $authHash, string $baseUrl): array
{
    $rows = [
        ['label' => 'Recovery-Tool Version', 'value' => RECOVERY_VERSION],
        ['label' => 'PHP-Version', 'value' => \PHP_VERSION],
        ['label' => 'Recovery-URL', 'value' => 'plugin-recovery-tool.php'],
        ['label' => 'Forum-URL', 'value' => $baseUrl, 'truncate' => true],
    ];

    if (\defined('WCF_N')) {
        $rows[] = ['label' => 'WCF_N (Tabellen-Suffix)', 'value' => (string) \constant('WCF_N')];
    }
    if (\defined('WCF_DIR')) {
        $rows[] = [
            'label' => 'WCF-Verzeichnis',
            'value' => \rtrim((string) \constant('WCF_DIR'), '/\\'),
            'truncate' => true,
        ];
    }
    if (\defined('WCF_VERSION')) {
        $rows[] = ['label' => 'WoltLab Suite', 'value' => (string) \constant('WCF_VERSION')];
    }

    return $rows;
}

function recoveryRenderCopyableRow(string $elementId, string $label, string $value, ?string $copyValue = null): void
{
    $elementId = \preg_replace('/[^a-zA-Z0-9_-]/', '', $elementId) ?: 'val';
    $clipboardValue = $copyValue ?? $value;
    $trunc = recoveryTruncateRuntimeDisplay($value);
    $titleAttr = $trunc['title'] !== null ? ' title="' . \htmlspecialchars($trunc['title']) . '"' : '';
    echo '<div class="recovery-copy-row">';
    echo '<span class="recovery-copy-label">' . \htmlspecialchars($label) . '</span>';
    echo '<code class="recovery-copy-value recovery-sysinfo-value" id="recovery-copy-' . \htmlspecialchars($elementId) . '"'
        . $titleAttr . '>' . \htmlspecialchars($trunc['display']) . '</code>';
    if ($copyValue !== null && $copyValue !== $value) {
        echo '<span id="recovery-copy-full-' . \htmlspecialchars($elementId) . '" hidden>'
            . \htmlspecialchars($clipboardValue) . '</span>';
    }
    $copyTarget = ($copyValue !== null && $copyValue !== $value)
        ? 'recovery-copy-full-' . $elementId
        : 'recovery-copy-' . $elementId;
    echo '<button type="button" class="button small recovery-copy-btn recovery-copy-btn--icon" data-recovery-copy="'
        . \htmlspecialchars($copyTarget) . '" title="In Zwischenablage kopieren" aria-label="'
        . \htmlspecialchars($label) . ' kopieren">';
    echo recoveryFaIcon(16, 'copy');
    echo '</button>';
    echo '</div>';
}

function recoveryGetLastLogHintMessage(): ?string
{
    if (!\defined('WCF_DIR')) {
        return null;
    }
    $wcfDir = \rtrim((string) \constant('WCF_DIR'), '/\\') . '/';
    $logHits = recoveryScanWoltLabLogForRecentErrors($wcfDir, 3);
    if ($logHits === []) {
        return null;
    }

    return (string) $logHits[\count($logHits) - 1];
}

/**
 * @param array{compact?: bool, class?: string, icon?: string} $opts
 */
function recoveryRenderInfoHintBox(
    string $title,
    string $bodyHtml,
    ?string $actionUrl = null,
    ?string $actionLabelHtml = null,
    array $opts = []
): void {
    $classes = ['recovery-hint-box'];
    if (!empty($opts['compact'])) {
        $classes[] = 'recovery-hint-box--compact';
    }
    if (!empty($opts['class'])) {
        $classes[] = (string) $opts['class'];
    }
    $icon = isset($opts['icon']) ? (string) $opts['icon'] : 'circle-info';
    ?>
    <aside class="<?= \htmlspecialchars(\implode(' ', $classes), ENT_QUOTES, 'UTF-8') ?>" role="note">
        <div class="recovery-hint-box__icon" aria-hidden="true"><?= recoveryFaIcon(20, $icon) ?></div>
        <div class="recovery-hint-box__content">
            <p class="recovery-hint-box__title"><strong><?= \htmlspecialchars($title) ?></strong></p>
            <p class="recovery-hint-box__text"><?= $bodyHtml ?></p>
            <?php if ($actionUrl !== null && $actionLabelHtml !== null): ?>
            <p class="recovery-hint-box__actions">
                <a href="<?= \htmlspecialchars($actionUrl, ENT_QUOTES, 'UTF-8') ?>" class="button">
                    <?= $actionLabelHtml ?>
                </a>
            </p>
            <?php endif; ?>
        </div>
    </aside>
    <?php
}

function recoveryRenderAcpCacheClearHint(string $authHash): void
{
    recoveryRenderInfoHintBox(
        'ACP lädt nicht oder zeigt einen Fatal Error?',
        'Der Datei-Cache wurde bereits geleert. Bei anhaltenden Template-Fehlern '
        . '(<code>Undefined constant</code> im Log) Cache und kompilierte Templates erneut leeren.',
        recoveryBuildModeUrl(RECOVERY_MODE_CACHE_CLEAR, $authHash),
        '<fa-icon size="16" name="broom" solid></fa-icon> Cache leeren',
        ['icon' => 'triangle-exclamation', 'class' => 'recovery-hint-box--after-result']
    );
}

/**
 * @return list<array{
 *     type: 'info'|'success'|'warning'|'error',
 *     title: string,
 *     body: string,
 *     bodyIsHtml?: bool,
 *     mono?: bool,
 *     id?: string,
 *     meta?: string
 * }>
 */
function recoveryCollectStartPageStatusItems(): array
{
    $items = [];

    if (isset($_GET['auth_ok'])) {
        $items[] = [
            'type' => 'success',
            'title' => 'Anmeldung erfolgreich',
            'body' => 'Wählen Sie unten, was auf Ihrer Installation zutrifft.',
        ];
    }

    if (isset($_GET['package_ok'])) {
        $items[] = [
            'type' => 'success',
            'title' => 'Paket bereit',
            'body' => 'Das Recovery-Paket wurde installiert. Wählen Sie unten den <strong>Recovery-Wizard</strong> '
                . 'oder einen anderen Modus.',
            'bodyIsHtml' => true,
        ];
    }

    $logMessage = recoveryGetLastLogHintMessage();
    if ($logMessage !== null && $logMessage !== '') {
        $items[] = [
            'type' => 'warning',
            'title' => 'Letzter Log-Hinweis',
            'meta' => 'WoltLab log/*.txt',
            'body' => $logMessage,
            'mono' => true,
            'id' => 'recovery-log-hint',
        ];
    }

    return $items;
}

function recoveryRenderLastLogHintBanner(): void
{
    $logMessage = recoveryGetLastLogHintMessage();
    if ($logMessage === null || $logMessage === '') {
        return;
    }

    recoveryRenderStatusFeed([
        [
            'type' => 'warning',
            'title' => 'Letzter Log-Hinweis',
            'meta' => 'WoltLab log/*.txt',
            'body' => $logMessage,
            'mono' => true,
            'id' => 'recovery-log-hint',
        ],
    ]);
}

function recoveryRenderCompactStatusBar(string $authHash, string $baseUrl): void
{
    $hasLogHint = recoveryGetLastLogHintMessage() !== null;
    ?>
    <p class="recovery-status-bar" aria-label="Kurzstatus">
        <span>Tool v<?= \htmlspecialchars(RECOVERY_VERSION) ?></span>
        <span class="recovery-status-sep">·</span>
        <span>PHP <?= \htmlspecialchars(\PHP_VERSION) ?></span>
        <?php if (\defined('WCF_VERSION')): ?>
        <span class="recovery-status-sep">·</span>
        <span>WoltLab <?= \htmlspecialchars((string) \constant('WCF_VERSION')) ?></span>
        <?php endif; ?>
        <?php if ($hasLogHint): ?>
        <span class="recovery-status-sep">·</span>
        <span class="recovery-status-warn">
            <?= recoveryFaIcon(16, 'triangle-exclamation') ?>
            <a href="#recovery-log-hint" class="recovery-status-link">Log-Hinweis anzeigen</a>
        </span>
        <?php endif; ?>
        <span class="recovery-status-sep">·</span>
        <a href="#recovery-sysinfo" class="recovery-status-link">Systeminformationen</a>
    </p>
    <?php
}

function recoveryRenderRuntimeInfoPanel(string $authHash, string $baseUrl, bool $open = false, bool $embedded = false): void
{
    $rows = recoveryBuildRuntimeInfoRows($authHash, $baseUrl);
    $native = recoveryUsesNativeAcpUi();
    $renderContent = static function () use ($rows, $embedded, $native): void {
        if (!$embedded && !$native): ?>
        <div class="recovery-license-card__header">
            <div class="recovery-license-card__title">
                <?= recoveryFaIcon(18, 'server') ?>
                <strong>System-Informationen</strong>
            </div>
            <span class="badge badgeGreen">Bereit</span>
        </div>
        <?php endif; ?>
        <?php if (!$native): ?>
        <div class="recovery-sysinfo-cta-strip" role="note" aria-label="Hinweise zur System-Info-Tabelle">
            <div class="recovery-sysinfo-cta-strip__block recovery-sysinfo-cta-strip__block--support">
                <p class="recovery-sysinfo-cta-strip__heading">
                    <?= recoveryFaIcon(16, 'life-ring') ?>
                    Für Support &amp; Foren
                </p>
                <p class="recovery-sysinfo-cta-strip__text">
                    Kopieren Sie einzelne Werte aus der Tabelle (Button pro Zeile) und fügen Sie sie in
                    Tickets oder E-Mails ein. Die Zeile <strong>Recovery-URL</strong> zeigt nur
                    <code>plugin-recovery-tool.php</code> — <strong>ohne</strong> Ihr Zugangs-Token, damit
                    kein geheimer Link an Dritte gelangt.
                </p>
            </div>
        </div>
        <?php endif; ?>
        <table class="<?= \htmlspecialchars(recoveryAcpTableClass(), ENT_QUOTES, 'UTF-8') ?> recovery-runtime-table">
            <colgroup>
                <col class="recovery-sysinfo-col-label">
                <col class="recovery-sysinfo-col-value">
                <col class="recovery-sysinfo-col-status">
                <col class="recovery-sysinfo-col-action">
            </colgroup>
            <thead>
                <tr>
                    <th class="recovery-sysinfo-th-label">Eigenschaft</th>
                    <th class="recovery-sysinfo-th-value">Wert</th>
                    <th class="recovery-sysinfo-th-status">Status</th>
                    <th class="columnActions recovery-sysinfo-th-action"><span class="silent">Kopieren</span></th>
                </tr>
            </thead>
            <tbody>
            <?php
            $i = 0;
            foreach ($rows as $row):
                $elementId = 'info' . $i++;
                $rawValue = (string) $row['value'];
                $shouldTruncate = !empty($row['truncate']);
                $trunc = $shouldTruncate ? recoveryTruncateRuntimeDisplay($rawValue) : ['display' => $rawValue, 'title' => null];
                $titleAttr = $trunc['title'] !== null ? ' title="' . \htmlspecialchars($trunc['title']) . '"' : '';
                $status = recoveryResolveRuntimeInfoStatus((string) $row['label']);
            ?>
                <tr>
                    <td class="recovery-sysinfo-label"><strong><?= \htmlspecialchars((string) $row['label']) ?></strong></td>
                    <td class="columnText recovery-sysinfo-value-cell">
                        <code class="recovery-sysinfo-value" id="recovery-copy-<?= \htmlspecialchars($elementId) ?>"<?= $titleAttr ?>><?= \htmlspecialchars($trunc['display']) ?></code>
                        <span id="recovery-copy-raw-<?= \htmlspecialchars($elementId) ?>" hidden><?= \htmlspecialchars($rawValue) ?></span>
                    </td>
                    <td class="recovery-sysinfo-status-cell">
                        <?php
                        $statusTitle = ($row['label'] === 'Recovery-URL')
                            ? 'Absichtlich ohne geheimen Link (?t=…), damit Support-Tickets kein Zugangs-Token enthalten.'
                            : '';
                        ?>
                        <span class="badge <?= \htmlspecialchars($status['class']) ?>"<?= $statusTitle !== '' ? ' title="' . \htmlspecialchars($statusTitle) . '"' : '' ?>><?= \htmlspecialchars($status['text']) ?></span>
                    </td>
                    <td class="columnActions recovery-sysinfo-action-cell">
                        <button type="button" class="button small recovery-copy-btn recovery-copy-btn--icon"
                            data-recovery-copy="recovery-copy-raw-<?= \htmlspecialchars($elementId) ?>"
                            title="<?= \htmlspecialchars((string) $row['label']) ?> kopieren"
                            aria-label="<?= \htmlspecialchars((string) $row['label']) ?> kopieren">
                            <?= recoveryFaIcon(16, 'copy') ?>
                        </button>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php
    };
    if ($embedded) {
        $renderContent();

        return;
    }
    ?>
    <details class="recovery-panel" id="recovery-sysinfo"<?= $open ? ' open' : '' ?>>
        <summary><fa-icon size="16" name="circle-info" solid></fa-icon> System-Informationen</summary>
        <div class="recovery-panel__body recovery-panel__body--sysinfo">
            <?php $renderContent(); ?>
        </div>
    </details>
    <?php
}

/**
 * @return array{
 *   offerFix: bool,
 *   brokenApplications: list<array{applicationID: int, packageID: int, application: string}>,
 *   orphanedListeners: list<array{listenerID: int, listenerClassName: string}>,
 *   logClasses: list<string>
 * }
 */
function recoveryDetectCriticalDbIssues(
    string $wcfDir,
    ?\wcf\system\database\Database $db,
    ?int $wcfN
): array {
    $logClasses = recoveryExtractMissingClassesFromLog($wcfDir);
    $brokenApplications = ($db !== null && $wcfN !== null)
        ? recoveryFindBrokenApplicationRows($db, $wcfN)
        : [];
    $orphanedListeners = ($db !== null && $wcfN !== null)
        ? recoveryFindOrphanedDbEventListeners($wcfDir, $db, $wcfN, null, $logClasses)
        : [];

    $offerFix = $brokenApplications !== [] || $orphanedListeners !== [] || $logClasses !== [];

    if (!$offerFix) {
        foreach (recoveryScanWoltLabLogForRecentErrors($wcfDir, 8) as $line) {
            $line = (string) $line;
            if (\str_contains($line, 'ClassNotFound')
                || \str_contains($line, 'Unable to find class')
                || \str_contains($line, "package id '0' is unknown")
                || \str_contains($line, 'application identified by package id')
            ) {
                $offerFix = true;
                break;
            }
        }
    }

    return [
        'offerFix' => $offerFix,
        'brokenApplications' => $brokenApplications,
        'orphanedListeners' => $orphanedListeners,
        'logClasses' => $logClasses,
    ];
}

function recoveryShouldOfferEmergencyClassNotFoundFix(
    string $wcfDir,
    ?\wcf\system\database\Database $db = null,
    ?int $wcfN = null
): bool {
    return recoveryDetectCriticalDbIssues($wcfDir, $db, $wcfN)['offerFix'];
}

/**
 * Behebt typische Post-Deinstall-Fehler: packageID 0, verwaiste Applications, tote Event-Listener, Cache.
 *
 * @return array{
 *   applicationsRepaired: bool,
 *   bootstrapNeutralized: list<string>,
 *   dbEventListenersDeleted: int,
 *   cacheDeleted: int,
 *   log: list<string>,
 *   logClasses: list<string>
 * }
 */
function recoveryApplyCoreDbRepairs(
    string $wcfDir,
    \wcf\system\database\Database $db,
    int $wcfN,
    array &$log
): array {
    $logClasses = recoveryExtractMissingClassesFromLog($wcfDir);
    $applicationsRepaired = false;

    try {
        $orphanResult = recoveryRepairOrphanedPackageReferences($db, $wcfN);
        foreach ($orphanResult['log'] as $entry) {
            $log[] = '[DB] ' . $entry;
        }
        $applicationsRepaired = $orphanResult['log'] !== [];
    } catch (\Throwable $e) {
        $log[] = '[DB] Application-Reparatur fehlgeschlagen: ' . $e->getMessage();
    }

    $bootstrapNeutralized = recoveryNeutralizeBootstrapRegistersForMissingListeners($wcfDir, $log, $logClasses);
    foreach ($logClasses as $fqcn) {
        $extra = recoveryForceNeutralizeBootstrapRegistersForListenerFqcn($wcfDir, $fqcn, $log);
        foreach ($extra as $path) {
            if (!\in_array($path, $bootstrapNeutralized, true)) {
                $bootstrapNeutralized[] = $path;
            }
        }
    }

    $dbDeleted = recoveryPurgeOrphanedDbEventListeners($wcfDir, $db, $wcfN, $log, null, $logClasses);
    $cacheDeleted = clearCompiledTemplates();
    $optionFbLog = [];
    recoveryEnsureOptionConstantFallbacks($db, $wcfN, $optionFbLog);
    foreach ($optionFbLog as $entry) {
        $log[] = '[Cache] ' . $entry;
    }
    $log[] = '[Cache] Cache-Dateien gelöscht: ' . $cacheDeleted;

    return [
        'applicationsRepaired' => $applicationsRepaired,
        'bootstrapNeutralized' => $bootstrapNeutralized,
        'dbEventListenersDeleted' => $dbDeleted,
        'cacheDeleted' => $cacheDeleted,
        'logClasses' => $logClasses,
    ];
}

/**
 * @param array<string, mixed> $result
 * @param list<string> $log
 */
function recoverySessionSetEmergencyFixed(string $authHash, array $result, array $log = []): void
{
    recoveryEnsureSession();
    $_SESSION['recovery_emergency'] ??= [];
    $_SESSION['recovery_emergency'][$authHash] = [
        'at' => \time(),
        'result' => $result,
        'log' => $log,
    ];
}

/**
 * @return array{at: int, result: array<string, mixed>}|null
 */
function recoverySessionGetEmergencyFixed(string $authHash): ?array
{
    recoveryEnsureSession();
    $entry = $_SESSION['recovery_emergency'][$authHash] ?? null;
    if (!\is_array($entry)) {
        return null;
    }
    if (\time() - (int) ($entry['at'] ?? 0) > 7200) {
        unset($_SESSION['recovery_emergency'][$authHash]);

        return null;
    }

    return $entry;
}

/**
 * Ergebnis nach Kern-Reparatur / ACP-Notfall (Redirect mit acp_fixed=1).
 *
 * @return array{result: array<string, mixed>, log: list<string>}|null
 */
function recoveryLoadAcpFixOutcome(string $authHash): ?array
{
    if (!isset($_GET['acp_fixed'])) {
        return null;
    }

    $flash = recoverySessionPullFlash($authHash, 'acp_fix_result');
    if (\is_array($flash) && isset($flash['result']) && \is_array($flash['result'])) {
        return [
            'result' => $flash['result'],
            'log' => \is_array($flash['log'] ?? null) ? $flash['log'] : [],
        ];
    }

    $session = recoverySessionGetEmergencyFixed($authHash);
    if ($session !== null && \is_array($session['result'] ?? null)) {
        return [
            'result' => $session['result'],
            'log' => \is_array($session['log'] ?? null) ? $session['log'] : [],
        ];
    }

    return [
        'result' => [
            'bootstrapNeutralized' => [],
            'dbEventListenersDeleted' => 0,
            'cacheDeleted' => 0,
            'applicationsRepaired' => false,
            'logClasses' => [],
        ],
        'log' => [],
    ];
}

/**
 * @param array<string, mixed> $result
 * @param list<string> $log
 */
function recoveryRenderAcpRecoveredGuidance(
    array $result,
    string $acpUrl,
    string $uninstallUrl,
    string $authHash,
    array $log = []
): void {
    $bootstrapCount = \count($result['bootstrapNeutralized'] ?? []);
    $dbListeners = (int) ($result['dbEventListenersDeleted'] ?? 0);
    $cacheDeleted = (int) ($result['cacheDeleted'] ?? 0);
    $appsRepaired = !empty($result['applicationsRepaired']);
    $logClasses = $result['logClasses'] ?? [];
    $uninstallShrinkrUrl = recoveryBuildModeUrl(RECOVERY_MODE_PLUGIN_UNINSTALL, $authHash, [
        'package_identifier' => 'de.sunnyc.wsc.shrinkr',
    ]);
    $sysCheckUrl = recoveryBuildModeUrl(RECOVERY_MODE_SYSTEM_CHECK, $authHash);
    ?>
    <div class="recovery-run-block recovery-acp-done-panel" id="recovery-acp-done">
        <header class="recovery-acp-done-panel__head">
            <?= recoveryFaIcon(28, 'circle-check') ?>
            <div>
                <h2 class="recovery-run-block__title">ACP-Notfall erledigt</h2>
                <p class="recovery-run-block__desc">
                    Der ACP sollte wieder erreichbar sein. Das war <strong>keine vollständige Deinstallation</strong> —
                    nur Start-Blocker (Bootstrap, DB-Listener, Applications) wurden bereinigt.
                </p>
            </div>
        </header>

        <div class="recovery-acp-stats">
            <div class="recovery-acp-stat">
                <span class="recovery-acp-stat__value"><?= $bootstrapCount ?></span>
                <span class="recovery-acp-stat__label">Bootstrap-Dateien</span>
            </div>
            <div class="recovery-acp-stat">
                <span class="recovery-acp-stat__value"><?= $dbListeners ?></span>
                <span class="recovery-acp-stat__label">Event-Listener (DB)</span>
            </div>
            <div class="recovery-acp-stat">
                <span class="recovery-acp-stat__value"><?= $cacheDeleted ?></span>
                <span class="recovery-acp-stat__label">Cache-Dateien</span>
            </div>
            <div class="recovery-acp-stat">
                <span class="recovery-acp-stat__value"><?= $appsRepaired ? 'Ja' : '—' ?></span>
                <span class="recovery-acp-stat__label">Applications repariert</span>
            </div>
        </div>

        <?php if ($logClasses !== []): ?>
        <p class="recovery-acp-done-panel__loghint">
            <?= recoveryFaIcon(16, 'file-lines') ?>
            Aus dem Log erkannt: <code><?= \htmlspecialchars((string) $logClasses[0]) ?></code>
            <?php if (\count($logClasses) > 1): ?>
            <span class="recovery-acp-done-panel__more">(+<?= \count($logClasses) - 1 ?> weitere)</span>
            <?php endif; ?>
        </p>
        <?php endif; ?>

        <p class="recovery-rec-section-label">Was Sie jetzt tun sollten</p>
        <ol class="recovery-rec-next">
            <li><a href="<?= \htmlspecialchars($acpUrl) ?>" class="button buttonPrimary" target="_blank" rel="noopener"><?= recoveryFaIcon(16, 'gauge-high') ?> ACP öffnen</a> und prüfen, ob das Dashboard lädt.</li>
            <li>Steht das Plugin noch unter <strong>Pakete</strong> im ACP?
                <strong>Ja</strong> → dort deinstallieren oder
                <a href="<?= \htmlspecialchars($uninstallUrl) ?>">Plugin entfernen</a> im Recovery Tool.
                <strong>Nein</strong> → Reste mit
                <a href="<?= \htmlspecialchars($uninstallShrinkrUrl) ?>">Plugin entfernen</a>
                (<code>de.sunnyc.wsc.shrinkr</code>, DB + optional <code>shrinkr/</code>).
            </li>
            <li><strong>Nicht</strong> den Recovery-Wizard nutzen, um fehlende Plugin-Dateien wiederherzustellen — das wäre eine Neuinstallation, keine Entfernung.</li>
            <li>Optional: <a href="<?= \htmlspecialchars($sysCheckUrl) ?>">System-Check</a> erneut ausführen.</li>
            <li>Recovery Tool und Auth-Datei vom Server löschen, wenn alles erledigt ist.</li>
        </ol>

        <?php if ($log !== []): ?>
        <details class="recovery-panel recovery-panel--compact">
            <summary><?= recoveryFaIcon(16, 'list') ?> Protokoll der Reparatur</summary>
            <div class="recovery-panel__body">
                <pre class="recoveryLog recovery-log-pre--tall"><?= \htmlspecialchars(\implode("\n", $log)) ?></pre>
            </div>
        </details>
        <?php endif; ?>
    </div>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        var el = document.getElementById('recovery-acp-done');
        if (el) {
            el.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
    });
    </script>
    <?php
}

function recoveryRenderBreadcrumb(int $mode, string $authHash): void
{
    if ($mode === RECOVERY_MODE_SELECTION) {
        return;
    }

    $home = recoveryHomeUrl($authHash);
    $items = [['href' => $home, 'label' => 'Start', 'current' => false]];

    $labels = [
        RECOVERY_MODE_SYSTEM_CHECK => 'System-Check',
        RECOVERY_MODE_BACKUP_GUIDE => 'Datensicherung',
        RECOVERY_MODE_DIRECTORY_STRUCTURE => 'Verzeichnisstruktur',
        RECOVERY_MODE_PLUGIN_UNINSTALL => 'Plugin entfernen',
        RECOVERY_MODE_CACHE_CLEAR => 'Cache leeren',
        RECOVERY_MODE_RECOVERY_WIZARD => 'Recovery-Wizard',
        RECOVERY_MODE_USER_MANAGEMENT => 'Admin-Zugang',
        RECOVERY_MODE_ACP_REPAIR => 'ACP Repair',
        RECOVERY_MODE_PACKAGE_LIST_REPAIR => 'Paketliste reparieren',
        RECOVERY_MODE_PACKAGE_FILE_REPAIR => 'Datei-Reparatur',
    ];

    if (recoveryIsExpertToolMode($mode) && !recoveryUsesNativeAcpUi()) {
        $expertHome = recoveryBuildHomeUrl($authHash, ['expert' => '1']);
        $items[] = ['href' => $expertHome, 'label' => 'Experten-Modi', 'current' => false];
    }

    if (isset($labels[$mode])) {
        $items[] = ['href' => null, 'label' => $labels[$mode], 'current' => true];
    }

    if (recoveryUsesNativeAcpUi()) {
        recoveryRenderAcpBreadcrumb($items);

        return;
    }

    echo '<nav class="recovery-breadcrumb" aria-label="Unternavigation"><ol class="recovery-breadcrumb__list">';
    foreach ($items as $item) {
        echo '<li class="recovery-breadcrumb__item">';
        if (!empty($item['current'])) {
            echo '<span class="' . recoveryChipClass(false, true) . '" aria-current="page">'
                . \htmlspecialchars((string) $item['label']) . '</span>';
        } else {
            echo '<a class="' . recoveryChipClass() . '" href="' . \htmlspecialchars((string) $item['href']) . '">'
                . \htmlspecialchars((string) $item['label']) . '</a>';
        }
        echo '</li>';
    }
    echo '</ol></nav>';
}

function recoveryRenderPageNavigation(int $mode, string $authHash, string $baseUrl): void
{
    if (recoveryUsesNativeAcpUi()) {
        recoveryRenderAcpPageMenu($mode, $authHash);
        recoveryRenderDeferredPageMainOpen();
        recoveryRenderBreadcrumb($mode, $authHash);

        return;
    }

    echo '<div class="recovery-page-nav">';
    recoveryRenderGlobalNav($mode, $authHash, $baseUrl);
    recoveryRenderExpertSubNav($mode, $authHash);
    recoveryRenderBreadcrumb($mode, $authHash);
    echo '</div>';
}

function recoveryRenderExpertModesGrid(string $authHash): void
{
    $modes = [
        [RECOVERY_MODE_BACKUP_GUIDE, 'database', 'Datensicherung', 'DB- und Datei-Backup erstellen', 'recovery-mode-button--badge-success'],
        [RECOVERY_MODE_PLUGIN_UNINSTALL, 'trash-can', 'Plugin entfernen', 'DB + Dateien gezielt deinstallieren', 'recovery-mode-button--badge-danger'],
        [RECOVERY_MODE_CACHE_CLEAR, 'broom', 'Cache leeren', 'Templates & Option-Fallback', 'recovery-mode-button--badge-neutral'],
        [RECOVERY_MODE_ACP_REPAIR, 'wrench', 'ACP Repair', 'Defekte ACP-Menüeinträge eines Plugins', 'recovery-mode-button--badge-info'],
        [RECOVERY_MODE_PACKAGE_LIST_REPAIR, 'list-check', 'Paketliste reparieren', 'Verwaiste Queue-/Application-Einträge', 'recovery-mode-button--badge-warning'],
        [RECOVERY_MODE_PACKAGE_FILE_REPAIR, 'file-circle-plus', 'Datei-Reparatur', 'Fehlende Klassen aus Paket-Archiv', 'recovery-mode-button--badge-info'],
    ];
    ?>
    <div class="mode-grid recovery-expert-grid">
    <?php foreach ($modes as [$mode, $icon, $title, $desc, $badgeClass]): ?>
        <a href="<?= \htmlspecialchars(recoveryBuildModeUrl($mode, $authHash)) ?>" class="mode-button <?= \htmlspecialchars($badgeClass) ?>">
            <?= recoveryFaIcon(24, $icon) ?>
            <strong><?= \htmlspecialchars($title) ?></strong>
            <span><?= \htmlspecialchars($desc) ?></span>
        </a>
    <?php endforeach; ?>
    </div>
    <?php
}

/**
 * @return array{0: string, 1: string} Titel und optionale Beschreibung für contentHeader
 */
function recoveryResolveModePageHeader(int $mode): array
{
    return match ($mode) {
        RECOVERY_MODE_PLUGIN_UNINSTALL => [
            'Plugin entfernen',
            'Deinstalliert Plugins komplett — per-Ressource-Auswahl, SQL-Backup &amp; Dry-Run',
        ],
        RECOVERY_MODE_CACHE_CLEAR => [
            'Cache leeren',
            'Löscht alle Caches und kompilierte Templates',
        ],
        RECOVERY_MODE_SYSTEM_CHECK => [
            'System-Check',
            '',
        ],
        RECOVERY_MODE_BACKUP_GUIDE => [
            'Datensicherung',
            'Datenbank- und Dateisystem-Backup direkt im Tool',
        ],
        RECOVERY_MODE_DIRECTORY_STRUCTURE => [
            'Verzeichnisstruktur',
            'Handbuch-Workflow: Diagnose, SQL, Config, Domain, Cache',
        ],
        RECOVERY_MODE_RECOVERY_WIZARD => [
            'Recovery-Wizard',
            'Geführte Diagnose, Plan und Ausführung',
        ],
        RECOVERY_MODE_ACP_REPAIR => ['ACP Repair', 'Defekte ACP-Menüeinträge eines Plugins'],
        RECOVERY_MODE_USER_MANAGEMENT => ['Admin-Konto', 'Benutzer und Berechtigungen in der Datenbank'],
        RECOVERY_MODE_PACKAGE_LIST_REPAIR => ['Paketliste reparieren', 'Verwaiste Queue- und Application-Einträge'],
        RECOVERY_MODE_PACKAGE_FILE_REPAIR => ['Plugin-Dateien reparieren', 'Fehlende Klassen aus Paket-Archiv'],
        RECOVERY_MODE_SELECTION => [
            'Plugin Recovery Tool',
            'Geführte Hilfe bei Notfällen — Situation wählen und Recovery-Wizard oder Experten-Modus starten.',
        ],
        default => ['Plugin Recovery Tool', 'Geführte Hilfe bei Notfällen auf Ihrer WoltLab-Installation'],
    };
}

/**
 * @return array{host: string, scheme: string, full: string}
 */
function recoveryDetectCurrentRequestDomain(): array
{
    $host = \strtolower(\trim((string) ($_SERVER['HTTP_HOST'] ?? 'localhost')));
    $host = \preg_replace('/:\d+$/', '', $host) ?: $host;
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || ((string) ($_SERVER['SERVER_PORT'] ?? '') === '443');
    $scheme = $https ? 'https' : 'http';

    return [
        'host' => $host,
        'scheme' => $scheme,
        'full' => $scheme . '://' . $host,
    ];
}

/**
 * @return list<array{packageID: int, package: string, domainName: string, cookieDomain: string, mismatch: bool}>
 */
function recoveryBuildDomainMismatchReport(\wcf\system\database\Database $db, int $wcfN): array
{
    $request = recoveryDetectCurrentRequestDomain();
    $currentHost = $request['host'];
    $rows = [];

    try {
        $sql = "SELECT a.packageID, p.package, a.domainName, a.cookieDomain
                FROM wcf{$wcfN}_application a
                INNER JOIN wcf{$wcfN}_package p ON a.packageID = p.packageID
                ORDER BY p.package";
        $statement = $db->prepareStatement($sql);
        $statement->execute();
        while ($row = $statement->fetchArray()) {
            $domainName = \strtolower(\trim((string) ($row['domainName'] ?? '')));
            $cookieDomain = \strtolower(\trim((string) ($row['cookieDomain'] ?? '')));
            $domainHost = $domainName !== '' ? (\preg_replace('#^https?://#i', '', $domainName)) : '';
            $domainHost = \preg_replace('/:\d+$/', '', $domainHost) ?: $domainHost;
            $mismatch = $domainHost !== '' && $domainHost !== $currentHost;
            $rows[] = [
                'packageID' => (int) ($row['packageID'] ?? 0),
                'package' => (string) ($row['package'] ?? ''),
                'domainName' => (string) ($row['domainName'] ?? ''),
                'cookieDomain' => (string) ($row['cookieDomain'] ?? ''),
                'domainHost' => $domainHost,
                'mismatch' => $mismatch,
            ];
        }
    } catch (\Throwable $ignored) {
    }

    return $rows;
}

/**
 * @return array{ok: bool, applied: int, log: list<string>}
 */
function recoveryApplyDomainToDatabase(
    \wcf\system\database\Database $db,
    int $wcfN,
    string $newDomainUrl,
    string $cookieDomain
): array {
    $log = [];
    $applied = 0;
    $newDomainUrl = \rtrim($newDomainUrl, '/');
    if ($newDomainUrl === '' || !\preg_match('#^https?://#i', $newDomainUrl)) {
        return ['ok' => false, 'applied' => 0, 'log' => ['Ungültige Domain-URL.']];
    }

    try {
        $sql = "UPDATE wcf{$wcfN}_application SET domainName = ?, cookieDomain = ?";
        $statement = $db->prepareStatement($sql);
        $statement->execute([$newDomainUrl, $cookieDomain]);
        $applied = $statement->getAffectedRows();
        $log[] = "wcf{$wcfN}_application: domainName → {$newDomainUrl}, cookieDomain → {$cookieDomain} ({$applied} Zeile(n))";
    } catch (\Throwable $e) {
        return ['ok' => false, 'applied' => 0, 'log' => ['DB-Fehler: ' . $e->getMessage()]];
    }

    return ['ok' => true, 'applied' => $applied, 'log' => $log];
}

/**
 * @return array{ok: bool, deleted: int, log: list<string>, method?: string}
 */
function recoveryRebuildDisplayData(string $wcfDir, \wcf\system\database\Database $db, int $wcfN): array
{
    $log = [];
    $clearResult = recoveryClearAllCaches($wcfDir);
    $log = \array_merge($log, $clearResult['log']);
    recoveryEnsureOptionConstantFallbacks($db, $wcfN, $log);
    recoveryLog('info', 'recoveryRebuildDisplayData', [
        'deleted' => $clearResult['deleted'],
        'method' => $clearResult['method'] ?? 'unknown',
    ]);

    return [
        'ok' => true,
        'deleted' => $clearResult['deleted'],
        'log' => $log,
        'method' => $clearResult['method'] ?? 'unknown',
    ];
}

/**
 * @return array{packageID: int, package: string, packageName: string, packageDir: string, isApplication: int}|null
 */
function recoveryLookupPackageInDatabase(\wcf\system\database\Database $db, string $packageIdentifier): ?array
{
    $n = WCF_N;
    $candidates = \array_values(\array_unique(\array_filter([
        $packageIdentifier,
        \strtolower($packageIdentifier),
    ])));

    foreach ($candidates as $candidate) {
        try {
            $sql = "SELECT packageID, package, packageName, packageDir, isApplication
                    FROM wcf{$n}_package WHERE package = ? LIMIT 1";
            $statement = $db->prepareStatement($sql);
            $statement->execute([$candidate]);
            $row = $statement->fetchArray();
            if (\is_array($row) && !empty($row['packageID'])) {
                return $row;
            }
        } catch (\Throwable $ignored) {
        }
    }

    if (\str_contains($packageIdentifier, '.')) {
        $suffix = (string) \array_slice(\explode('.', $packageIdentifier), -1)[0];
        if ($suffix !== '') {
            try {
                $sql = "SELECT packageID, package, packageName, packageDir, isApplication
                        FROM wcf{$n}_package WHERE package LIKE ? ORDER BY package LIMIT 5";
                $statement = $db->prepareStatement($sql);
                $statement->execute(['%.' . $suffix]);
                while ($row = $statement->fetchArray()) {
                    if (\is_array($row) && !empty($row['packageID'])) {
                        return $row;
                    }
                }
            } catch (\Throwable $ignored) {
            }
        }
    }

    return null;
}

/**
 * @return list<int>
 */
function recoveryExpertToolModes(): array
{
    return [
        RECOVERY_MODE_BACKUP_GUIDE,
        RECOVERY_MODE_PLUGIN_UNINSTALL,
        RECOVERY_MODE_CACHE_CLEAR,
        RECOVERY_MODE_ACP_REPAIR,
        RECOVERY_MODE_PACKAGE_LIST_REPAIR,
        RECOVERY_MODE_PACKAGE_FILE_REPAIR,
    ];
}

function recoveryIsExpertToolMode(int $mode): bool
{
    return \in_array($mode, recoveryExpertToolModes(), true);
}

function recoveryRenderGlobalNav(int $mode, string $authHash, string $baseUrl): void
{
    $acpUrl = $baseUrl . 'acp/';
    $links = [
        ['href' => recoveryHomeUrl($authHash), 'label' => 'Start', 'icon' => 'house', 'mode' => RECOVERY_MODE_SELECTION],
        ['href' => recoveryBuildModeUrl(RECOVERY_MODE_SYSTEM_CHECK, $authHash), 'label' => 'System-Check', 'icon' => 'stethoscope', 'mode' => RECOVERY_MODE_SYSTEM_CHECK],
        ['href' => recoveryBuildModeUrl(RECOVERY_MODE_DIRECTORY_STRUCTURE, $authHash), 'label' => 'Verzeichnisstruktur', 'icon' => 'folder-tree', 'mode' => RECOVERY_MODE_DIRECTORY_STRUCTURE],
    ];

    if (recoveryUsesNativeAcpUi()) {
        echo '<nav class="tabMenu recovery-main-tabMenu" aria-label="Hauptnavigation"><ul>';
        foreach ($links as $link) {
            $href = (string) $link['href'];
            $linkMode = (int) $link['mode'];
            $isActive = $mode === $linkMode;
            echo '<li' . ($isActive ? ' class="active"' : '') . '>';
            echo '<a href="' . \htmlspecialchars($href) . '"' . ($isActive ? ' aria-current="page"' : '') . '>'
                . \htmlspecialchars($link['label']) . '</a></li>';
        }
        echo '<li class="recovery-tabMenu-acp"><a href="' . \htmlspecialchars($acpUrl) . '">Zum ACP</a></li>';
        echo '</ul></nav>';

        return;
    }

    echo '<nav class="contentNavigation recovery-global-nav" aria-label="Hauptnavigation">';
    echo '<div class="recovery-chip-bar">';
    foreach ($links as $link) {
        $href = (string) $link['href'];
        $linkMode = (int) $link['mode'];
        $isActive = $mode === $linkMode;
        $ariaCurrent = $isActive ? ' aria-current="page"' : '';
        echo '<a href="' . \htmlspecialchars($href) . '" class="' . recoveryChipClass($isActive) . '"' . $ariaCurrent . '>'
            . recoveryFaIcon(16, $link['icon']) . ' <span>' . \htmlspecialchars($link['label']) . '</span></a>';
    }
    echo '<a href="' . \htmlspecialchars($acpUrl) . '" class="' . recoveryChipClass(false, false, ['recovery-chip--accent', 'recovery-chip--end']) . '">'
        . recoveryFaIcon(16, 'gauge-high') . ' <span>Zum ACP</span></a>';
    echo '</div></nav>';
}

function recoveryRenderExpertSubNav(int $mode, string $authHash): void
{
    if (!recoveryIsExpertToolMode($mode)) {
        return;
    }

    $tools = [
        [RECOVERY_MODE_BACKUP_GUIDE, 'database', 'Datensicherung', 'recovery-mode-button--badge-success'],
        [RECOVERY_MODE_PLUGIN_UNINSTALL, 'trash-can', 'Plugin entfernen', 'recovery-mode-button--badge-danger'],
        [RECOVERY_MODE_CACHE_CLEAR, 'broom', 'Cache leeren', 'recovery-mode-button--badge-neutral'],
        [RECOVERY_MODE_ACP_REPAIR, 'wrench', 'ACP Repair', 'recovery-mode-button--badge-info'],
        [RECOVERY_MODE_PACKAGE_LIST_REPAIR, 'list-check', 'Paketliste', 'recovery-mode-button--badge-warning'],
        [RECOVERY_MODE_PACKAGE_FILE_REPAIR, 'file-circle-plus', 'Datei-Reparatur', 'recovery-mode-button--badge-info'],
    ];

    if (recoveryUsesNativeAcpUi()) {
        echo '<nav class="tabMenu recovery-expert-tabMenu" aria-label="Experten-Werkzeuge"><ul>';
        foreach ($tools as [$toolMode, $icon, $label, $badgeClass]) {
            unset($badgeClass, $icon);
            $href = recoveryBuildModeUrl($toolMode, $authHash);
            $isActive = $mode === $toolMode;
            echo '<li' . ($isActive ? ' class="active"' : '') . '>';
            echo '<a href="' . \htmlspecialchars($href) . '"' . ($isActive ? ' aria-current="page"' : '') . '>'
                . \htmlspecialchars($label) . '</a></li>';
        }
        echo '</ul></nav>';

        return;
    }

    echo '<div class="recovery-toolbar-row" role="navigation" aria-label="Experten-Werkzeuge">';
    echo '<span class="recovery-toolbar-row__label">' . recoveryFaIcon(14, 'screwdriver-wrench') . ' Werkzeuge</span>';
    echo '<div class="recovery-chip-bar recovery-chip-bar--grow">';
    foreach ($tools as [$toolMode, $icon, $label, $badgeClass]) {
        unset($badgeClass);
        $href = recoveryBuildModeUrl($toolMode, $authHash);
        $isActive = $mode === $toolMode;
        $ariaCurrent = $isActive ? ' aria-current="page"' : '';
        echo '<a href="' . \htmlspecialchars($href) . '" class="' . recoveryChipClass($isActive) . '"' . $ariaCurrent . '>'
            . recoveryFaIcon(14, $icon) . ' <span>' . \htmlspecialchars($label) . '</span></a>';
    }
    echo '</div></div>';
}


// ============================================================================
// HELPER FUNKTIONEN
// ============================================================================

/**
 * Extrahiert Package-Identifier aus package.xml
 */
function extractPackageIdentifier($packageXmlPath) {
    if (!file_exists($packageXmlPath) || !is_file($packageXmlPath)) {
        return null;
    }

    $xml = simplexml_load_file($packageXmlPath);
    if ($xml === false) {
        return null;
    }

    $package = (string)$xml['name'];
    return $package ?: null;
}

/**
 * Entfernt unsichere Pfade nach dem Entpacken (Path-Traversal).
 */
function recoverySanitizeExtractedArchive(string $destination): void
{
    if (!\is_dir($destination)) {
        return;
    }

    $baseReal = \realpath($destination);
    if ($baseReal === false) {
        return;
    }

    $iterator = new \RecursiveIteratorIterator(
        new \RecursiveDirectoryIterator($destination, \RecursiveDirectoryIterator::SKIP_DOTS),
        \RecursiveIteratorIterator::CHILD_FIRST
    );

    foreach ($iterator as $file) {
        $pathname = $file->getPathname();
        $relative = \ltrim(\str_replace('\\', '/', \substr($pathname, \strlen($baseReal))), '/');
        if (recoveryIsUnsafeArchiveRelativePath($relative)) {
            $file->isDir() ? @\rmdir($pathname) : @\unlink($pathname);
        }
    }
}

/**
 * Entpackt TAR/TAR.GZ Archive (mit nachträglicher Pfad-Validierung).
 */
function extractArchive($archivePath, $destination) {
    if (!\is_file($archivePath) || !recoveryValidateArchiveFilename(\basename($archivePath))) {
        return false;
    }

    try {
        if (!\is_dir($destination) && !@\mkdir($destination, 0755, true)) {
            return false;
        }

        $phar = new \PharData($archivePath);
        $phar->extractTo($destination, null, true);
        recoverySanitizeExtractedArchive($destination);

        return true;
    } catch (\Throwable $ignored) {
        return false;
    }
}

/**
 * Findet alle Tabellen eines Plugins anhand des Präfixes
 */
function findPackageTables($db, $packageIdentifier, $wcfN = null) {
    try {
        $packageIdentifier = recoveryValidatePackageIdentifier($packageIdentifier);
    } catch (\InvalidArgumentException $ignored) {
        return [];
    }

    $parts = \explode('.', $packageIdentifier);
    $appNames = [];
    if (\count($parts) >= 2) {
        $appNames[] = $parts[\count($parts) - 2];
    }
    $appNames[] = (string) \end($parts);
    $appNames = \array_values(\array_unique(\array_filter($appNames)));

    $sql = 'SHOW TABLES';
    $statement = $db->prepareStatement($sql);
    $statement->execute();

    $tables = [];
    $allBaseTables = [];
    if ($wcfN !== null && $wcfN >= 1 && $wcfN <= 99) {
        $allBaseTables = getBasePluginTables((int) $wcfN);
    } else {
        for ($n = 1; $n <= 10; $n++) {
            $allBaseTables = \array_merge($allBaseTables, getBasePluginTables($n));
        }
    }
    $allBaseTables = \array_unique($allBaseTables);

    while ($row = $statement->fetchArray()) {
        $tableName = (string) \reset($row);
        if (!recoveryValidateSqlTableName($tableName)) {
            continue;
        }

        if (\in_array($tableName, $allBaseTables, true)) {
            continue;
        }

        foreach ($appNames as $appName) {
            if ($appName === '' || !\preg_match('/^[a-zA-Z0-9._-]+$/', $appName)) {
                continue;
            }

            if (\preg_match('/^' . \preg_quote($appName, '/') . '\d+_/i', $tableName)) {
                $tables[] = $tableName;
                break;
            }
            if (\preg_match('/^' . \preg_quote($appName, '/') . '_/i', $tableName)) {
                $tables[] = $tableName;
                break;
            }
            if (\preg_match('/^' . \preg_quote($appName, '/') . '\d+/i', $tableName)) {
                $tables[] = $tableName;
                break;
            }
            if (\stripos($tableName, $appName) === 0) {
                $tables[] = $tableName;
                break;
            }
        }
    }

    return \array_values(\array_unique($tables));
}

/**
 * Inhalt eines Verzeichnisses rekursiv löschen (das Verzeichnis selbst bleibt erhalten).
 */
function recoveryDeleteDirectoryContentsRecursive(string $dir): int
{
    if (!\is_dir($dir)) {
        return 0;
    }

    $deletedFiles = 0;
    try {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::CURRENT_AS_FILEINFO),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $fileinfo) {
            $path = $fileinfo->getPathname();
            if ($fileinfo->isDir()) {
                @\rmdir($path);
            } else {
                @\unlink($path);
            }
            $deletedFiles++;
        }
    } catch (\Throwable $ignored) {
    }

    return $deletedFiles;
}

/**
 * @return list<string>
 */
function recoveryGetFilesystemCacheDirectoryList(string $wcfRoot): array
{
    $wcfRoot = \rtrim(\str_replace('\\', '/', $wcfRoot), '/') . '/';
    $dirs = [
        $wcfRoot . 'tmp',
        $wcfRoot . 'cache',
        $wcfRoot . 'templates/compiled',
        $wcfRoot . 'acp/templates/compiled',
    ];

    $protectedDirs = \array_flip(recoveryGetProtectedDirectoryNames());

    foreach (\scandir($wcfRoot) ?: [] as $name) {
        if ($name === '.' || $name === '..') {
            continue;
        }
        if (!recoveryValidateAppDirectoryName($name) || isset($protectedDirs[$name])) {
            continue;
        }

        $subdir = $wcfRoot . \str_replace('\\', '/', $name);
        if (!\is_dir($subdir)) {
            continue;
        }

        foreach (['templates/compiled', 'acp/templates/compiled'] as $rel) {
            $candidate = $subdir . '/' . $rel;
            if (\is_dir($candidate)) {
                $dirs[] = \rtrim($candidate, '/');
            }
        }
    }

    return \array_values(\array_unique($dirs));
}

function recoveryIsNativeWcfCacheClearAvailable(): bool
{
    try {
        if (!\defined('WCF_DIR')) {
            \define('WCF_DIR', recoveryResolveWcfDir());
        }

        recoveryDefineMinimalWcfConstants();

        return \is_file(WCF_DIR . 'lib/system/cache/CacheHandler.class.php')
            && \is_file(WCF_DIR . 'lib/system/template/TemplateEngine.class.php');
    } catch (\Throwable $ignored) {
        return false;
    }
}

function recoveryCountFilesInCacheDirectories(string $wcfRoot): int
{
    $count = 0;
    foreach (recoveryGetFilesystemCacheDirectoryList($wcfRoot) as $dir) {
        if (!\is_dir($dir)) {
            continue;
        }
        try {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::CURRENT_AS_FILEINFO)
            );
            foreach ($iterator as $fileinfo) {
                if ($fileinfo->isFile()) {
                    $count++;
                }
            }
        } catch (\Throwable $ignored) {
        }
    }

    return $count;
}

/**
 * @return list<string>
 */
function recoveryGetAppCompiledTemplateDirs(string $wcfRoot): array
{
    $wcfRoot = \rtrim(\str_replace('\\', '/', $wcfRoot), '/') . '/';
    $core = [
        $wcfRoot . 'templates/compiled',
        $wcfRoot . 'acp/templates/compiled',
    ];
    $dirs = [];

    foreach (recoveryGetFilesystemCacheDirectoryList($wcfRoot) as $dir) {
        $norm = \rtrim(\str_replace('\\', '/', $dir), '/');
        if (\in_array($norm, $core, true)) {
            continue;
        }
        if (\str_contains($norm, 'templates/compiled')) {
            $dirs[] = $dir;
        }
    }

    return $dirs;
}

/**
 * WoltLab-Cache-API (CacheHandler, TemplateEngine) — dieselbe Basis wie ACP „Cache leeren“.
 *
 * @return array{ok: bool, deleted: int, log: list<string>, error?: string}
 */
function recoveryTryNativeWcfCacheClear(string $wcfDir): array
{
    $log = [];
    $deleted = 0;

    if (!\defined('WCF_DIR')) {
        \define('WCF_DIR', \rtrim($wcfDir, '/\\') . '/');
    }

    try {
        recoveryDefineMinimalWcfConstants();
        recoveryDefineMinimalWcfFunctions();

        $beforeCount = recoveryCountFilesInCacheDirectories(WCF_DIR);

        \wcf\system\cache\CacheHandler::getInstance()->flushAll();
        $log[] = 'WoltLab CacheHandler::flushAll() — cache/';

        \wcf\system\template\TemplateEngine::deleteCompiledTemplates();
        \wcf\system\template\ACPTemplateEngine::deleteCompiledACPTemplates();
        $log[] = 'WoltLab TemplateEngine — templates/compiled/ und acp/templates/compiled/';

        foreach (recoveryGetAppCompiledTemplateDirs(WCF_DIR) as $appCompileDir) {
            $compileDir = \rtrim(\str_replace('\\', '/', $appCompileDir), '/') . '/';
            \wcf\system\template\TemplateEngine::deleteCompiledTemplates($compileDir);
            $log[] = 'WoltLab TemplateEngine — ' . $compileDir;
        }

        $afterCount = recoveryCountFilesInCacheDirectories(WCF_DIR);
        $deleted = \max(0, $beforeCount - $afterCount);
        $log[] = 'Entfernte Cache-Dateien: ' . $deleted;

        return ['ok' => true, 'deleted' => $deleted, 'log' => $log];
    } catch (\Throwable $e) {
        $log[] = 'WoltLab-Cache-API fehlgeschlagen: ' . $e->getMessage();

        return ['ok' => false, 'deleted' => $deleted, 'log' => $log, 'error' => $e->getMessage()];
    }
}

/**
 * Leert Caches: zuerst WoltLab-API (wenn ladbar), sonst manuelles Löschen der Cache-Verzeichnisse.
 *
 * @return array{ok: bool, deleted: int, log: list<string>, method: string}
 */
function recoveryClearAllCaches(?string $wcfDir = null): array
{
    if ($wcfDir === null) {
        $wcfDir = recoveryResolveWcfDir();
    }

    if (!\defined('WCF_DIR')) {
        \define('WCF_DIR', \rtrim($wcfDir, '/\\') . '/');
    }

    if (recoveryIsNativeWcfCacheClearAvailable()) {
        $native = recoveryTryNativeWcfCacheClear($wcfDir);
        if ($native['ok']) {
            return [
                'ok' => true,
                'deleted' => $native['deleted'],
                'log' => \array_merge(
                    ['Modus: WoltLab-Cache-API (wie ACP → Wartung → Cache leeren)'],
                    $native['log']
                ),
                'method' => 'native',
            ];
        }
    }

    $deleted = clearCompiledTemplates();
    $log = [
        'Modus: Manueller Fallback — ACP oft nicht erreichbar, daher direktes Leeren der Cache-Verzeichnisse',
        'Cache/Templates geleert: ' . $deleted . ' Datei(en)',
    ];

    return ['ok' => true, 'deleted' => $deleted, 'log' => $log, 'method' => 'filesystem'];
}

/**
 * Löscht kompilierte Templates und Datei-Caches per Filesystem (Fallback ohne WCF/CacheHandler).
 * Inkl. Anwendungen im Installations-Stamm wie z.&nbsp;B. shrinkr/acp/templates/compiled.
 */
function clearCompiledTemplates(): int
{
    if (!\defined('WCF_DIR')) {
        \define('WCF_DIR', recoveryResolveWcfDir());
    }

    $wcfRoot = \rtrim(WCF_DIR, '/\\') . DIRECTORY_SEPARATOR;

    $deletedTotal = 0;
    foreach (recoveryGetFilesystemCacheDirectoryList($wcfRoot) as $dir) {
        $deletedTotal += recoveryDeleteDirectoryContentsRecursive(\str_replace('/', DIRECTORY_SEPARATOR, $dir));
    }

    return $deletedTotal;
}

// ============================================================================
// PLUGIN-DATEIEN REPARIEREN (fehlende Klassen aus Bootstrap + Paket-Archiv)
// ============================================================================

/**
 * @return array{package: string, applicationDirectory: string}|null
 */
function recoveryParsePackageMetaFromExtractDir(string $extractDir): ?array
{
    $packageXml = findFileInExtractDir($extractDir, '', 'package.xml');
    if (!$packageXml) {
        return null;
    }

    $xml = @\simplexml_load_file($packageXml);
    if ($xml === false) {
        return null;
    }

    $package = \trim((string) ($xml['name'] ?? ''));
    $applicationDirectory = '';
    if (isset($xml->packageinformation->applicationdirectory)) {
        $applicationDirectory = \trim((string) $xml->packageinformation->applicationdirectory);
    }

    if ($applicationDirectory === '' && $package !== '') {
        $parts = \explode('.', $package);
        $guess = (string) \end($parts);
        if (recoveryValidateAppDirectoryName($guess)) {
            $applicationDirectory = $guess;
        }
    }

    if ($package === '') {
        return null;
    }

    return [
        'package' => $package,
        'applicationDirectory' => $applicationDirectory,
    ];
}

/**
 * Entpackt files.tar / files_wcf.tar aus einem Plugin-Archiv in Unterordner.
 *
 * @return array{package: string, applicationDirectory: string, appRoot: string|null, wcfRoot: string|null}|null
 */
function recoveryExtractPackageInstructionTars(string $extractDir, array &$log): ?array
{
    $meta = recoveryParsePackageMetaFromExtractDir($extractDir);
    if ($meta === null) {
        $log[] = 'package.xml konnte nicht gelesen werden.';

        return null;
    }

    $payload = [
        'package' => $meta['package'],
        'applicationDirectory' => $meta['applicationDirectory'],
        'appRoot' => null,
        'wcfRoot' => null,
    ];

    $instructions = recoveryDiscoverFileInstructionTarsFromPackageXml($extractDir);
    foreach ($instructions as $instr) {
        $tarName = (string) $instr['tar'];
        $target = (string) $instr['target'];
        $tarPath = findFileInExtractDir($extractDir, '', $tarName, [$tarName]);
        if (!$tarPath || !\is_file($tarPath)) {
            $log[] = $tarName . ' im Archiv nicht gefunden (package.xml-Instruction).';

            continue;
        }
        $subdir = ($target === 'wcf') ? '_recovery_payload_wcf' : '_recovery_payload_app';
        $root = recoveryExtractPayloadRootForTar($extractDir, $tarPath, $subdir, $log);
        if ($root === null) {
            continue;
        }
        if ($target === 'wcf') {
            $payload['wcfRoot'] = $root;
            $log[] = $tarName . ' → WCF-Root entpackt.';
        } else {
            $payload['appRoot'] = $root;
            $log[] = $tarName . ' → App-Root entpackt.';
        }
    }

    if ($payload['appRoot'] === null && $payload['wcfRoot'] === null) {
        $log[] = 'Keine nutzbaren file-Instructions aus package.xml.';

        return null;
    }

    return $payload;
}

/**
 * WoltLab-Klassenname → lib/…/*.class.php (erstes Segment = App, z. B. shrinkr).
 *
 * @return array{application: string, relative: string}|null
 */
function recoveryClassNameToLibRelativePath(string $className): ?array
{
    $className = \ltrim($className, '\\');
    if (!\preg_match('/^[a-z][a-z0-9_\\\\]*\\\\[A-Za-z][A-Za-z0-9_]+$/', $className)) {
        return null;
    }

    $parts = \explode('\\', $className);
    $application = (string) \array_shift($parts);
    $shortClass = (string) \array_pop($parts);
    $middle = $parts !== [] ? \implode('/', $parts) . '/' : '';

    return [
        'application' => $application,
        'relative' => 'lib/' . $middle . $shortClass . '.class.php',
    ];
}

/**
 * @return list<string> FQCN aus lib/bootstrap/*.php (::class-Referenzen)
 */
function recoveryCollectBootstrapReferencedClasses(string $wcfDir): array
{
    $bootstrapDir = \rtrim($wcfDir, '/\\') . '/lib/bootstrap';
    if (!\is_dir($bootstrapDir)) {
        return [];
    }

    $classes = [];
    foreach (\glob($bootstrapDir . '/*.php') ?: [] as $bootstrapFile) {
        $content = @\file_get_contents($bootstrapFile);
        if ($content === false || $content === '') {
            continue;
        }
        if (!\preg_match_all(
            '/\\\\?([a-z][a-z0-9_\\\\]*\\\\[A-Za-z][A-Za-z0-9_]+)::class/',
            $content,
            $matches
        )) {
            continue;
        }
        foreach ($matches[1] as $raw) {
            $cn = \ltrim(\str_replace('\\\\', '\\', (string) $raw), '\\');
            if ($cn !== '') {
                $classes[$cn] = true;
            }
        }
    }

    $list = \array_keys($classes);
    \sort($list);

    return $list;
}

function recoveryIsPluginClassLoadable(string $className): bool
{
    $className = \ltrim($className, '\\');
    if ($className === '') {
        return false;
    }

    try {
        return \class_exists($className, true);
    } catch (\Throwable $ignored) {
        return false;
    }
}

function recoveryIsPluginClassFilePresent(string $wcfDir, string $className): bool
{
    $className = \ltrim($className, '\\');
    $map = recoveryClassNameToLibRelativePath($className);

    if ($map !== null) {
        $wcfRoot = \rtrim($wcfDir, '/\\') . \DIRECTORY_SEPARATOR;
        if ($map['application'] === 'wcf') {
            $path = $wcfRoot . \str_replace('/', \DIRECTORY_SEPARATOR, $map['relative']);
        } else {
            if (!recoveryValidateAppDirectoryName($map['application'])) {
                return \recoveryIsPluginClassLoadable($className);
            }
            $path = $wcfRoot . $map['application'] . \DIRECTORY_SEPARATOR
                . \str_replace('/', \DIRECTORY_SEPARATOR, $map['relative']);
        }

        if (!\is_file($path)) {
            return false;
        }
    }

    return \recoveryIsPluginClassLoadable($className);
}

/**
 * Absoluter Pfad zur .class.php einer Plugin-Klasse (wcf oder App-Verzeichnis).
 */
function recoveryGetPluginClassFilePath(string $wcfDir, string $className): ?string
{
    $className = \ltrim($className, '\\');
    $map = recoveryClassNameToLibRelativePath($className);
    if ($map === null) {
        return null;
    }

    $wcfRoot = \rtrim($wcfDir, '/\\') . \DIRECTORY_SEPARATOR;
    if ($map['application'] === 'wcf') {
        return $wcfRoot . \str_replace('/', \DIRECTORY_SEPARATOR, $map['relative']);
    }

    if (!recoveryValidateAppDirectoryName($map['application'])) {
        return null;
    }

    return $wcfRoot . $map['application'] . \DIRECTORY_SEPARATOR
        . \str_replace('/', \DIRECTORY_SEPARATOR, $map['relative']);
}

/**
 * Klassennamen aus WoltLab-Log (ClassNotFound / Unable to find class).
 *
 * @return list<string>
 */
function recoveryExtractMissingClassesFromLog(string $wcfDir, int $maxLogFiles = 5): array
{
    $logDir = \rtrim($wcfDir, '/\\') . '/log';
    if (!\is_dir($logDir)) {
        return [];
    }

    $files = \glob($logDir . '/*.txt') ?: [];
    if ($files === []) {
        return [];
    }

    \usort($files, static function ($a, $b): int {
        return (\filemtime((string) $b) ?: 0) <=> (\filemtime((string) $a) ?: 0);
    });

    $classes = [];
    foreach (\array_slice($files, 0, $maxLogFiles) as $logFile) {
        $content = @\file_get_contents($logFile);
        if ($content === false || $content === '') {
            continue;
        }
        if (\preg_match_all("/Unable to find class '([^']+)'/i", $content, $matches)) {
            foreach ($matches[1] as $raw) {
                $cn = \ltrim((string) $raw, '\\');
                if ($cn !== '') {
                    $classes[$cn] = true;
                }
            }
        }
    }

    $list = \array_keys($classes);
    \sort($list);

    return $list;
}

/**
 * Soll ein PSR-14-register()-Listener deaktiviert werden?
 * Ja bei fehlender Datei, nicht ladbarer Klasse oder wenn das Log die Klasse als fehlend meldet.
 *
 * @param list<string> $logForcedClasses
 */
function recoveryBootstrapListenerNeedsNeutralization(
    string $wcfDir,
    string $listener,
    array $logForcedClasses = []
): bool {
    $listener = \ltrim($listener, '\\');
    if ($listener === '') {
        return false;
    }

    foreach ($logForcedClasses as $forced) {
        if ($listener === \ltrim((string) $forced, '\\')) {
            return true;
        }
    }

    return !recoveryIsPluginClassFilePresent($wcfDir, $listener);
}

/**
 * @return list<string> absolute Pfade geänderter Bootstrap-Dateien
 */
function recoveryWriteBootstrapContentWithBackup(string $bootstrapFile, string $newContent, array &$log): bool
{
    $bak = $bootstrapFile . '.recovery-backup-' . \date('YmdHis') . '-' . \substr(\sha1((string) \random_bytes(8)), 0, 8) . '.php';
    if (!@\copy($bootstrapFile, $bak)) {
        $log[] = '[Bootstrap] Backup fehlgeschlagen, überspringe ' . \basename($bootstrapFile);

        return false;
    }
    if (@\file_put_contents($bootstrapFile, $newContent) === false) {
        $log[] = '[Bootstrap] Schreiben fehlgeschlagen: ' . \basename($bootstrapFile);
        @\copy($bak, $bootstrapFile);

        return false;
    }

    $log[] = '[Bootstrap] Aktualisiert: ' . \basename($bootstrapFile) . ' (Backup: ' . \basename($bak) . ')';

    return true;
}

/**
 * Kommentiert register()-Blöcke aus, die eine Listener-FQCN enthalten (Fallback).
 *
 * @return list<string>
 */
function recoveryForceNeutralizeBootstrapRegistersForListenerFqcn(string $wcfDir, string $listenerFqcn, array &$log): array
{
    $modified = [];
    $listenerFqcn = \ltrim($listenerFqcn, '\\');
    if ($listenerFqcn === '') {
        return $modified;
    }

    $bootstrapDir = \rtrim($wcfDir, '/\\') . '/lib/bootstrap';
    if (!\is_dir($bootstrapDir)) {
        return $modified;
    }

    $short = (string) (\array_slice(\explode('\\', $listenerFqcn), -1)[0] ?? '');
    $patterns = [\preg_quote($listenerFqcn, '~')];
    if ($short !== '' && $short !== $listenerFqcn) {
        $patterns[] = \preg_quote($short, '~');
    }

    foreach (\glob($bootstrapDir . '/*.php') ?: [] as $bootstrapFile) {
        $content = @\file_get_contents($bootstrapFile);
        if ($content === false || $content === '') {
            continue;
        }

        $hasNeedle = \str_contains($content, $listenerFqcn)
            || ($short !== '' && \str_contains($content, $short));
        if (!$hasNeedle) {
            continue;
        }

        $newContent = $content;
        $fileChanged = false;

        foreach ($patterns as $escaped) {
            $rx = '~EventHandler::getInstance\(\)->register\s*\([^;]*' . $escaped . '[^;]*\)\s*;~s';
            $replaced = \preg_replace_callback(
                $rx,
                static function (array $m) use ($listenerFqcn, &$log, $bootstrapFile): string {
                    $full = $m[0];
                    if (\str_contains($full, '// [recovery]')) {
                        return $full;
                    }
                    $log[] = '[Bootstrap] Notfall-Deaktivierung für ' . $listenerFqcn
                        . ' in ' . \basename((string) $bootstrapFile);

                    $header = '// Recovery Tool ' . RECOVERY_VERSION . ': EventHandler::register deaktiviert (Notfall): '
                        . $listenerFqcn . "\n";
                    $lines = \preg_split('/\r\n|\r|\n/', $full) ?: [];
                    $out = $header;
                    foreach ($lines as $line) {
                        $out .= '// [recovery] ' . $line . "\n";
                    }

                    return \rtrim($out, "\n");
                },
                $newContent
            );
            if ($replaced !== null && $replaced !== $newContent) {
                $newContent = $replaced;
                $fileChanged = true;
            }
        }

        if (!$fileChanged) {
            continue;
        }

        if (recoveryWriteBootstrapContentWithBackup($bootstrapFile, $newContent, $log)) {
            $modified[] = $bootstrapFile;
        }
    }

    return $modified;
}

/**
 * Notfall: ACP-ClassNotFound aus Log + Bootstrap + DB + Cache (ein Klick).
 *
 * @return array{
 *   bootstrapNeutralized: list<string>,
 *   dbEventListenersDeleted: int,
 *   cacheDeleted: int,
 *   logClasses: list<string>
 * }
 */
function recoveryEmergencyFixAcpClassNotFound(
    string $wcfDir,
    \wcf\system\database\Database $db,
    int $wcfN,
    array &$log
): array {
    $log[] = '[Notfall] Starte Kern-Reparatur (Applications, Event-Listener, Bootstrap, Cache) …';
    $result = recoveryApplyCoreDbRepairs($wcfDir, $db, $wcfN, $log);
    $log[] = '[Notfall] Log-Klassen: ' . ($result['logClasses'] === []
        ? 'keine erkannt'
        : \implode(', ', $result['logClasses']));

    return [
        'applicationsRepaired' => $result['applicationsRepaired'],
        'bootstrapNeutralized' => $result['bootstrapNeutralized'],
        'dbEventListenersDeleted' => $result['dbEventListenersDeleted'],
        'cacheDeleted' => $result['cacheDeleted'],
        'logClasses' => $result['logClasses'],
    ];
}

/**
 * Listener-Klassen aus EventHandler::getInstance()->register(Event::class, Listener::class).
 *
 * @return list<string>
 */
function recoveryCollectBootstrapPsr14RegisterListenerClasses(string $wcfDir): array
{
    $bootstrapDir = \rtrim($wcfDir, '/\\') . '/lib/bootstrap';
    if (!\is_dir($bootstrapDir)) {
        return [];
    }

    $rx = '~EventHandler::getInstance\(\)->register\s*\(\s*.+?\s*,\s*((?:\\\\?[A-Za-z_][\w\\\\]*)+)\s*::class\s*\)\s*;~s';
    $listeners = [];

    foreach (\glob($bootstrapDir . '/*.php') ?: [] as $bootstrapFile) {
        $content = @\file_get_contents($bootstrapFile);
        if ($content === false || $content === '') {
            continue;
        }
        if (!\preg_match_all($rx, $content, $matches, \PREG_SET_ORDER)) {
            continue;
        }
        foreach ($matches as $m) {
            $listener = \ltrim((string) ($m[1] ?? ''), '\\');
            if ($listener !== '') {
                $listeners[$listener] = true;
            }
        }
    }

    $list = \array_keys($listeners);
    \sort($list);

    return $list;
}

/**
 * Event-Listener in der DB, deren listenerClassName keine .class.php auf dem Server hat
 * (typisch: ACP ClassNotFound in EventHandler::getPsr14Listeners).
 *
 * @return list<array{listenerID: int, listenerClassName: string}>
 */
function recoveryFindOrphanedDbEventListeners(
    string $wcfDir,
    \wcf\system\database\Database $db,
    int $wcfN,
    ?string $scopeApplicationDirectory = null,
    ?array $logForcedClasses = null
): array {
    if ($logForcedClasses === null) {
        $logForcedClasses = recoveryExtractMissingClassesFromLog($wcfDir);
    }

    $orphaned = [];
    try {
        $sql = "SELECT listenerID, listenerClassName FROM wcf{$wcfN}_event_listener";
        $statement = $db->prepareStatement($sql);
        $statement->execute();
        while ($row = $statement->fetchArray()) {
            $class = \trim((string) ($row['listenerClassName'] ?? ''));
            if ($class === '') {
                continue;
            }
            if ($scopeApplicationDirectory !== null && $scopeApplicationDirectory !== '') {
                $prefix = \str_replace('/', '\\', $scopeApplicationDirectory) . '\\';
                if (!\str_starts_with(\str_replace('/', '\\', $class), $prefix)) {
                    continue;
                }
            }
            if (recoveryBootstrapListenerNeedsNeutralization($wcfDir, $class, $logForcedClasses)) {
                $orphaned[] = [
                    'listenerID' => (int) ($row['listenerID'] ?? 0),
                    'listenerClassName' => $class,
                ];
            }
        }
    } catch (\Throwable $ignored) {
    }

    return $orphaned;
}

/**
 * @return int Anzahl gelöschter Zeilen
 */
function recoveryPurgeOrphanedDbEventListeners(
    string $wcfDir,
    \wcf\system\database\Database $db,
    int $wcfN,
    array &$log,
    ?string $scopeApplicationDirectory = null,
    ?array $logForcedClasses = null
): int {
    $orphaned = \recoveryFindOrphanedDbEventListeners(
        $wcfDir,
        $db,
        $wcfN,
        $scopeApplicationDirectory,
        $logForcedClasses
    );
    if ($orphaned === []) {
        return 0;
    }

    $deleted = 0;
    foreach ($orphaned as $row) {
        $listenerId = (int) ($row['listenerID'] ?? 0);
        if ($listenerId <= 0) {
            continue;
        }
        try {
            $sql = "DELETE FROM wcf{$wcfN}_event_listener WHERE listenerID = ?";
            $statement = $db->prepareStatement($sql);
            $statement->execute([$listenerId]);
            $deleted++;
            $log[] = '[Event-Listener DB] Entfernt: ' . ($row['listenerClassName'] ?? '?')
                . ' (listenerID ' . $listenerId . ')';
        } catch (\Throwable $e) {
            $log[] = '[Event-Listener DB] Löschen fehlgeschlagen (listenerID ' . $listenerId . '): '
                . $e->getMessage();
        }
    }

    return $deleted;
}

/**
 * Klassen, die in Bootstrap registriert sind, deren .class.php auf dem Server fehlt.
 *
 * @return list<string>
 */
function recoveryFindMissingBootstrapClasses(string $wcfDir): array
{
    $logForced = recoveryExtractMissingClassesFromLog($wcfDir);
    $candidates = \array_merge(
        recoveryCollectBootstrapReferencedClasses($wcfDir),
        recoveryCollectBootstrapPsr14RegisterListenerClasses($wcfDir)
    );
    $candidates = \array_values(\array_unique($candidates));

    $missing = [];
    foreach ($candidates as $class) {
        if (recoveryBootstrapListenerNeedsNeutralization($wcfDir, $class, $logForced)) {
            $missing[] = $class;
        }
    }

    return $missing;
}

/**
 * @param list<string> $fqcnList
 * @return array<string, list<string>> App-Präfix (z. B. shrinkr) → Klassen
 */
function recoveryGroupFqcnByApplicationPrefix(array $fqcnList): array
{
    $groups = [];
    foreach ($fqcnList as $cn) {
        $app = \explode('\\', $cn, 2)[0] ?? 'unbekannt';
        $groups[$app][] = $cn;
    }
    \ksort($groups);

    return $groups;
}

/**
 * @param list<string> $fqcnList
 * @return list<string>
 */
function recoveryFilterFqcnByApplicationPrefix(array $fqcnList, string $applicationDirectory): array
{
    $applicationDirectory = \trim($applicationDirectory);
    if ($applicationDirectory === '') {
        return $fqcnList;
    }

    $needle = \strtolower($applicationDirectory) . '\\';

    return \array_values(\array_filter(
        $fqcnList,
        static fn (string $cn): bool => \str_starts_with(\strtolower($cn), $needle)
    ));
}

function recoveryGuessApplicationFromPackageIdentifier(string $packageIdentifier): string
{
    $packageIdentifier = \trim($packageIdentifier);
    if ($packageIdentifier === '') {
        return '';
    }

    $parts = \explode('.', $packageIdentifier);
    $guess = (string) \end($parts);

    return recoveryValidateAppDirectoryName($guess) ? $guess : '';
}

/**
 * Kopiert fehlende Klassen + Bootstrap aus Paket-Payload ins Installationsverzeichnis.
 *
 * @param array{package: string, applicationDirectory: string, appRoot: string|null, wcfRoot: string|null} $payload
 * @param list<string> $missingClasses
 * @return list<string> relative Pfade der kopierten Dateien
 */
function recoveryRepairMissingPluginFilesFromPayload(
    string $wcfDir,
    array $payload,
    array $missingClasses,
    array &$log
): array {
    $copied = [];
    $wcfRoot = \rtrim($wcfDir, '/\\') . \DIRECTORY_SEPARATOR;
    $expectedApp = (string) ($payload['applicationDirectory'] ?? '');

    foreach ($missingClasses as $class) {
        $map = recoveryClassNameToLibRelativePath($class);
        if ($map === null) {
            continue;
        }

        if ($map['application'] === 'wcf') {
            $srcRoot = $payload['wcfRoot'] ?? null;
            $destRoot = $wcfRoot;
        } else {
            if ($expectedApp !== '' && $map['application'] !== $expectedApp) {
                $log[] = 'Übersprungen (andere App): ' . $class;

                continue;
            }
            $srcRoot = $payload['appRoot'] ?? null;
            $destRoot = $wcfRoot . $map['application'] . \DIRECTORY_SEPARATOR;
        }

        if ($srcRoot === null) {
            $log[] = 'Kein Paket-Root für: ' . $class;

            continue;
        }

        $rel = \str_replace('\\', '/', $map['relative']);
        $src = \rtrim($srcRoot, '/\\') . '/' . $rel;
        $dest = \rtrim($destRoot, '/\\') . \DIRECTORY_SEPARATOR . \str_replace('/', \DIRECTORY_SEPARATOR, $rel);

        if (!\is_file($src)) {
            $log[] = 'Im Paket nicht gefunden: ' . $rel;

            continue;
        }

        $destDir = \dirname($dest);
        if (!\is_dir($destDir) && !@\mkdir($destDir, 0755, true)) {
            $log[] = 'Zielverzeichnis nicht anlegbar: ' . $destDir;

            continue;
        }

        if (@\copy($src, $dest)) {
            $copied[] = $rel;
            $log[] = 'Kopiert: ' . $map['application'] . '/' . $rel;
        } else {
            $log[] = 'Kopieren fehlgeschlagen: ' . $dest;
        }
    }

    $packageId = (string) ($payload['package'] ?? '');
    if ($packageId !== '' && !empty($payload['wcfRoot'])) {
        $bootstrapName = $packageId . '.php';
        $srcBootstrap = \rtrim((string) $payload['wcfRoot'], '/\\') . '/lib/bootstrap/' . $bootstrapName;
        $destBootstrap = $wcfRoot . 'lib/bootstrap/' . $bootstrapName;
        if (\is_file($srcBootstrap)) {
            $bootstrapDir = \dirname($destBootstrap);
            if (!\is_dir($bootstrapDir) && !@\mkdir($bootstrapDir, 0755, true)) {
                $log[] = 'lib/bootstrap/ nicht anlegbar.';
            } elseif (@\copy($srcBootstrap, $destBootstrap)) {
                $copied[] = 'lib/bootstrap/' . $bootstrapName;
                $log[] = 'Bootstrap synchronisiert: lib/bootstrap/' . $bootstrapName;
            }
        }
    }

    return $copied;
}

// ============================================================================
// RECOVERY-WIZARD (Diagnose → Plan → Ausführung, halbautomatisch)
// ============================================================================

/**
 * Ermittelt file-Instructions aus package.xml (wie build.sh parse_package_instructions).
 *
 * @return list<array{tar: string, target: string}>
 */
function recoveryDiscoverFileInstructionTarsFromPackageXml(string $extractDir): array
{
    $packageXml = findFileInExtractDir($extractDir, '', 'package.xml');
    if (!$packageXml) {
        return [['tar' => 'files.tar', 'target' => 'app'], ['tar' => 'files_wcf.tar', 'target' => 'wcf']];
    }

    $parsed = parsePackageXml($packageXml);
    $found = [];
    $hasDefaultAppFile = false;

    if (\is_array($parsed) && !empty($parsed['instructions'])) {
        foreach ($parsed['instructions'] as $instr) {
            $type = (string) ($instr['type'] ?? '');
            if ($type !== 'file') {
                continue;
            }
            $path = \trim((string) ($instr['path'] ?? ''));
            $app = \trim((string) ($instr['application'] ?? ''));
            if ($path === '') {
                if ($app === 'wcf') {
                    $found['files_wcf.tar'] = 'wcf';
                } else {
                    $hasDefaultAppFile = true;
                }
                continue;
            }
            if (!\preg_match('/\.(tar|tar\.gz|tgz)$/i', $path)) {
                continue;
            }
            $target = ($app === 'wcf') ? 'wcf' : 'app';
            $found[$path] = $target;
        }
    }

    if ($hasDefaultAppFile || $found === []) {
        $found['files.tar'] = 'app';
    }
    if (!isset($found['files_wcf.tar'])) {
        $wcfTar = findFileInExtractDir($extractDir, '', 'files_wcf.tar', ['files_wcf.tar']);
        if ($wcfTar) {
            $found['files_wcf.tar'] = 'wcf';
        }
    }

    $out = [];
    foreach ($found as $tar => $target) {
        $out[] = ['tar' => $tar, 'target' => $target];
    }

    return $out;
}

/**
 * @param list<array{tar: string, target: string}> $instructions
 */
function recoveryExtractPayloadRootForTar(string $extractDir, string $tarFile, string $destSubdir, array &$log): ?string
{
    $dest = $extractDir . '/' . $destSubdir;
    if (!\is_dir($dest) && !@\mkdir($dest, 0755, true)) {
        $log[] = 'Entpack-Ziel nicht anlegbar: ' . $destSubdir;

        return null;
    }
    if (!extractArchive($tarFile, $dest)) {
        $log[] = 'Archiv konnte nicht entpackt werden: ' . \basename($tarFile);

        return null;
    }

    return $dest;
}

/**
 * Zählt EventHandler::getInstance()->register(Event::class, Listener::class)-Aufrufe in lib/bootstrap/*.php,
 * deren Listener-.class.php auf dem Server fehlt (typischer ACP-ClassNotFound nach kaputter Installation).
 */
function recoveryCountNeutralizableBootstrapRegisters(string $wcfDir, ?array $logForcedClasses = null): int
{
    if ($logForcedClasses === null) {
        $logForcedClasses = recoveryExtractMissingClassesFromLog($wcfDir);
    }

    $n = 0;
    $bootstrapDir = \rtrim($wcfDir, '/\\') . '/lib/bootstrap';
    if (!\is_dir($bootstrapDir)) {
        return 0;
    }

    $rx = '~EventHandler::getInstance\(\)->register\s*\(\s*.+?\s*,\s*((?:\\\\?[A-Za-z_][\w\\\\]*)+)\s*::class\s*\)\s*;~s';

    foreach (\glob($bootstrapDir . '/*.php') ?: [] as $path) {
        $content = @\file_get_contents($path);
        if ($content === false || $content === '') {
            continue;
        }
        if (!\preg_match_all($rx, $content, $matches, \PREG_SET_ORDER)) {
            continue;
        }
        foreach ($matches as $m) {
            $listener = \ltrim($m[1], '\\');
            if (recoveryBootstrapListenerNeedsNeutralization($wcfDir, $listener, $logForcedClasses)) {
                $n++;
            }
        }
    }

    return $n;
}

/**
 * Kommentiert betroffene register()-Aufrufe zeilenweise mit // aus (kein Blockkommentar, kein Stern-Slash).
 * Legt pro geänderter Datei ein Backup mit Suffix .recovery-backup-*.php an.
 *
 * @return list<string> absolute Pfade der geänderten Bootstrap-Dateien
 */
function recoveryNeutralizeBootstrapRegistersForMissingListeners(
    string $wcfDir,
    array &$log,
    ?array $logForcedClasses = null
): array {
    if ($logForcedClasses === null) {
        $logForcedClasses = recoveryExtractMissingClassesFromLog($wcfDir);
    }

    $modified = [];
    $bootstrapDir = \rtrim($wcfDir, '/\\') . '/lib/bootstrap';
    if (!\is_dir($bootstrapDir)) {
        $log[] = '[Bootstrap] Kein Verzeichnis lib/bootstrap.';

        return $modified;
    }

    $rx = '~EventHandler::getInstance\(\)->register\s*\(\s*.+?\s*,\s*((?:\\\\?[A-Za-z_][\w\\\\]*)+)\s*::class\s*\)\s*;~s';

    foreach (\glob($bootstrapDir . '/*.php') ?: [] as $bootstrapFile) {
        $content = @\file_get_contents($bootstrapFile);
        if ($content === false || $content === '') {
            continue;
        }

        $newContent = \preg_replace_callback(
            $rx,
            function (array $m) use ($wcfDir, &$log, $bootstrapFile, $logForcedClasses): string {
                $full = $m[0];
                $listener = \ltrim($m[1], '\\');
                if (!recoveryBootstrapListenerNeedsNeutralization($wcfDir, $listener, $logForcedClasses)) {
                    return $full;
                }
                if (\str_contains($full, '// [recovery]')) {
                    return $full;
                }
                $log[] = '[Bootstrap] Deaktiviere Register für nicht ladbare Klasse '
                    . $listener . ' in ' . \basename((string) $bootstrapFile);

                $header = '// Recovery Tool ' . RECOVERY_VERSION . ': EventHandler::register deaktiviert — Klasse nicht ladbar: '
                    . $listener . "\n";
                $lines = \preg_split('/\r\n|\r|\n/', $full) ?: [];
                $out = $header;
                foreach ($lines as $line) {
                    $out .= '// [recovery] ' . $line . "\n";
                }

                return \rtrim($out, "\n");
            },
            $content
        );

        if ($newContent === null || $newContent === $content) {
            continue;
        }

        if (recoveryWriteBootstrapContentWithBackup($bootstrapFile, $newContent, $log)) {
            $modified[] = $bootstrapFile;
        }
    }

    foreach ($logForcedClasses as $fqcn) {
        $extra = recoveryForceNeutralizeBootstrapRegistersForListenerFqcn($wcfDir, $fqcn, $log);
        foreach ($extra as $path) {
            if (!\in_array($path, $modified, true)) {
                $modified[] = $path;
            }
        }
    }

    return $modified;
}

/**
 * System-Diagnose für Wizard Schritt 1.
 *
 * @return array{
 *   missingBootstrapClasses: list<string>,
 *   orphanApplicationCount: int,
 *   logExcerpts: list<string>,
 *   bootstrapNeutralizeCandidates: int,
 *   orphanedDbEventListeners: list<array{listenerID: int, listenerClassName: string}>,
 *   suggestedActions: array{orphans: bool, files: bool, neutralizeBootstrap: bool, dbEventListeners: bool, cache: bool}
 * }
 */
function recoveryBuildSystemDiagnosis(
    string $wcfDir,
    \wcf\system\database\Database $db,
    int $wcfN,
    ?string $scopeApplicationDirectory = null
): array {
    $missing = recoveryFindMissingBootstrapClasses($wcfDir);
    if ($scopeApplicationDirectory !== null && $scopeApplicationDirectory !== '') {
        $missing = recoveryFilterFqcnByApplicationPrefix($missing, $scopeApplicationDirectory);
    }
    $orphanedDbListeners = recoveryFindOrphanedDbEventListeners($wcfDir, $db, $wcfN, $scopeApplicationDirectory);
    $orphanCount = 0;
    try {
        $sql = "SELECT COUNT(*) AS c FROM wcf{$wcfN}_application a
                LEFT JOIN wcf{$wcfN}_package p ON a.packageID = p.packageID
                WHERE p.packageID IS NULL";
        $statement = $db->prepareStatement($sql);
        $statement->execute();
        $row = $statement->fetchArray();
        $orphanCount = (int) ($row['c'] ?? 0);
    } catch (\Throwable $ignored) {
    }

    $logExcerpts = recoveryScanWoltLabLogForRecentErrors($wcfDir, 50);
    $logReportedMissingClasses = recoveryExtractMissingClassesFromLog($wcfDir);
    $neutralizeCandidates = recoveryCountNeutralizableBootstrapRegisters($wcfDir, $logReportedMissingClasses);
    $undefinedConstants = recoveryExtractUndefinedConstantsFromLog($wcfDir);
    if ($scopeApplicationDirectory !== null && $scopeApplicationDirectory !== '') {
        $undefinedConstants = recoveryFilterUndefinedConstantsByApplication($undefinedConstants, $scopeApplicationDirectory);
    }
    $optionsIncExists = \is_file(\rtrim($wcfDir, '/\\') . '/options.inc.php');
    $optionsIncWritable = $optionsIncExists && \is_writable(\rtrim($wcfDir, '/\\') . '/options.inc.php');

    return [
        'missingBootstrapClasses' => $missing,
        'orphanApplicationCount' => $orphanCount,
        'logExcerpts' => $logExcerpts,
        'logReportedMissingClasses' => $logReportedMissingClasses,
        'undefinedConstants' => $undefinedConstants,
        'optionsIncExists' => $optionsIncExists,
        'optionsIncWritable' => $optionsIncWritable,
        'bootstrapNeutralizeCandidates' => $neutralizeCandidates,
        'orphanedDbEventListeners' => $orphanedDbListeners,
        'suggestedActions' => [
            'orphans' => $orphanCount > 0,
            'files' => $missing !== [],
            'neutralizeBootstrap' => $neutralizeCandidates > 0 || $logReportedMissingClasses !== [],
            'dbEventListeners' => $orphanedDbListeners !== [],
            'cache' => true,
            'restoreOptionsInc' => $undefinedConstants !== [] && $optionsIncWritable,
            'optionConstantFallbacks' => $undefinedConstants !== [],
            'disableApplication' => $undefinedConstants !== [] && $scopeApplicationDirectory !== null && $scopeApplicationDirectory !== '',
        ],
    ];
}

function recoveryFormatCode(string $text): string
{
    return '<code>' . \htmlspecialchars($text, ENT_QUOTES, 'UTF-8') . '</code>';
}

/**
 * @param list<string> $texts
 */
function recoveryFormatCodeList(array $texts): string
{
    $parts = [];
    foreach ($texts as $text) {
        $text = (string) $text;
        if ($text !== '') {
            $parts[] = recoveryFormatCode($text);
        }
    }

    return \implode(', ', $parts);
}

/** @param string $html Vom Tool erzeugtes Markup — nur für recoveryBuild*-Strings. */
function recoveryEchoTrustedHtml(string $html): void
{
    echo $html;
}

/**
 * Verständliche Empfehlungen aus der Diagnose (für Wizard Schritt Diagnose & Plan).
 *
 * @return array{
 *   severity: string,
 *   headline: string,
 *   summary: string,
 *   steps: list<array{key: string, title: string, why: string, recommended: bool, required: bool, count: int}>,
 *   afterAcp: list<string>,
 *   logHint: string|null
 * }
 */
function recoveryBuildWizardRecommendations(array $diag, ?string $packageLabel = null): array
{
    $missing = \count($diag['missingBootstrapClasses'] ?? []);
    $neutral = (int) ($diag['bootstrapNeutralizeCandidates'] ?? 0);
    $orphDb = \count($diag['orphanedDbEventListeners'] ?? []);
    $orphans = (int) ($diag['orphanApplicationCount'] ?? 0);
    $logExcerpts = $diag['logExcerpts'] ?? [];
    $undefinedConstants = $diag['undefinedConstants'] ?? [];
    $undefCount = \count($undefinedConstants);

    $logClassNotFound = false;
    $logClassName = null;
    foreach ($logExcerpts as $line) {
        if (\str_contains((string) $line, 'ClassNotFound') || \str_contains((string) $line, 'Unable to find class')) {
            $logClassNotFound = true;
            if (\preg_match("/class '([^']+)'/i", (string) $line, $m)) {
                $logClassName = $m[1];
            }
            break;
        }
    }

    $logUndefinedConstant = false;
    foreach ($logExcerpts as $line) {
        if (\str_contains((string) $line, 'Undefined constant')) {
            $logUndefinedConstant = true;
            break;
        }
    }

    $severity = 'ok';
    if ($undefCount > 0 || $logUndefinedConstant) {
        $severity = 'critical';
    } elseif ($neutral > 0 || $orphDb > 0 || $missing > 0 || $logClassNotFound) {
        $severity = 'critical';
    } elseif ($orphans > 0) {
        $severity = 'warning';
    }

    $pkgHint = $packageLabel !== null && $packageLabel !== ''
        ? ' für ' . recoveryFormatCode($packageLabel)
        : '';

    $headline = match ($severity) {
        'critical' => 'Das ACP ist voraussichtlich wegen Plugin-Resten blockiert',
        'warning' => 'Kein schwerer Dateifehler — DB-Bereinigung empfohlen',
        default => 'Keine kritischen Fehler im gewählten Umfang',
    };

    $summary = match ($severity) {
        'critical' => ($undefCount > 0 || $logUndefinedConstant)
            ? 'Das Log meldet <strong>fehlende PHP-Konstanten</strong> (z.&nbsp;B. in <code>options.inc.php</code> oder Paket-Konstanten). '
                . 'Die Anwendung startet trotzdem und bricht beim Bootstrap oder in kompilierten Templates ab — '
                . 'Dateiwiederherstellung allein reicht oft nicht. Konstanten aus Paket/Log ergänzen oder Application vorübergehend deaktivieren.'
            : 'Typisch: Beim Aufruf von <code>/acp/</code> bricht das Dashboard ab, weil PHP eine Plugin-Klasse laden '
                . 'soll, die fehlt oder nicht ladbar ist. Zuerst den ACP wieder startfähig machen (Bootstrap/DB/Cache), '
                . 'danach das Plugin sauber deinstallieren.',
        'warning' => 'Auf dem Server wurden vor allem verwaiste Datenbankeinträge gefunden. '
            . 'Das kann die Paketliste oder Deinstallation stören.',
        default => 'Im geprüften Umfang wurden keine fehlenden Klassen oder kaputten Listener gefunden. '
            . 'Ein Cache-Leeren kann trotzdem helfen, wenn der ACP aus anderen Gründen hängt.',
    };

    $steps = [];

    if ($orphans > 0) {
        $steps[] = [
            'key' => 'orphans',
            'title' => '1. Paketliste bereinigen',
            'why' => $orphans . ' Application(s) in der DB ohne gültiges Paket — kann die ACP-Paketliste oder Deinstallation blockieren.',
            'recommended' => true,
            'required' => false,
            'count' => $orphans,
        ];
    }

    if ($missing > 0) {
        $steps[] = [
            'key' => 'files',
            'title' => '2. Fehlende Plugin-Dateien aus dem Paket-Archiv kopieren',
            'why' => $missing . ' Klasse(n) sind in Bootstrap registriert, die .class.php fehlt auf dem Server'
                . $pkgHint . '. Dafür wird das hochgeladene .tar.gz benötigt.',
            'recommended' => true,
            'required' => $missing > 0,
            'count' => $missing,
        ];
    }

    if ($neutral > 0) {
        $steps[] = [
            'key' => 'neutralizeBootstrap',
            'title' => '3. Bootstrap neutralisieren (ACP-Notfall)',
            'why' => $neutral . ' PSR-14-<code>EventHandler::register()</code>-Zeile(n) verweisen auf nicht ladbare Klassen '
                . 'oder auf Klassen, die das WoltLab-Log als fehlend meldet '
                . '(z.&nbsp;B. <code>BoxCollectingShrinkrDashboardListener</code>). '
                . 'Diese werden in <code>lib/bootstrap/*.php</code> auskommentiert (Backup neben der Datei).',
            'recommended' => true,
            'required' => true,
            'count' => $neutral,
        ];
    }

    if ($undefCount > 0) {
        $sample = \array_slice(\array_map(static fn (array $c): string => $c['fqName'] ?? $c['globalName'] ?? '', $undefinedConstants), 0, 4);
        $steps[] = [
            'key' => 'optionConstants',
            'title' => 'Fehlende Konstanten (options.inc.php / Paket)',
            'why' => $undefCount . ' Konstante(n) im Log: '
                . recoveryFormatCodeList($sample)
                . ($undefCount > 4 ? ' …' : '')
                . ' — ' . recoveryFormatCode('options.inc.php') . ' aus Paket mergen und/oder Fallback-Block schreiben (Schritt Cache).',
            'recommended' => true,
            'required' => true,
            'count' => $undefCount,
        ];
    }

    if ($orphDb > 0) {
        $steps[] = [
            'key' => 'dbEventListeners',
            'title' => '4. DB Event-Listener entfernen',
            'why' => $orphDb . ' Eintrag/Einträge in <code>wcf*_event_listener</code> zeigen auf fehlende Klassen '
                . '(Listener nur in der Datenbank, nicht in Bootstrap).',
            'recommended' => true,
            'required' => $orphDb > 0 && $neutral === 0,
            'count' => $orphDb,
        ];
    }

    if ($logClassNotFound && $neutral === 0 && $orphDb === 0 && $missing === 0) {
        $steps[] = [
            'key' => 'hint',
            'title' => 'Hinweis: Log meldet ClassNotFound, Diagnose zeigt 0',
            'why' => 'Mögliche Ursachen: Diagnose nur für ein Paket gefiltert, Klasse ist ladbar aber defekt, '
                . 'oder Fehler kommt aus gecachten Daten. Versuchen Sie „gesamten Server prüfen“ in Schritt 1 '
                . 'oder Experten-Modus „Plugin-Dateien reparieren“.'
                . ($logClassName ? ' Log-Klasse: ' . recoveryFormatCode($logClassName) . '.' : ''),
            'recommended' => true,
            'required' => false,
            'count' => 1,
        ];
    }

    $steps[] = [
        'key' => 'cache',
        'title' => (empty($steps) ? '1' : (string) (\count($steps) + 1)) . '. Cache leeren',
        'why' => 'Entfernt kompilierte Templates und aktualisiert <code>options.inc.php</code>-Fallbacks. '
            . 'Nach Änderungen an Dateien oder DB immer ausführen.',
        'recommended' => true,
        'required' => false,
        'count' => 0,
    ];

    $afterAcp = [
        'ACP im Browser öffnen: <code>/acp/</code> — prüfen ob das Dashboard lädt.',
        'Wenn der ACP läuft: Modus <strong>Plugin Uninstall</strong> (Startseite oder Experten) für vollständige Entfernung.',
        'Recovery Tool und Auth-Datei vom Server löschen, wenn alles erledigt ist.',
    ];

    $logHint = match (true) {
        $undefCount > 0 => 'Im WoltLab-Log: Undefined constant — fehlende Paket-/Options-Konstanten.',
        $logClassNotFound => 'Im WoltLab-Log wurde kürzlich eine ClassNotFound-Meldung gefunden.',
        default => null,
    };

    return [
        'severity' => $severity,
        'headline' => $headline,
        'summary' => $summary,
        'steps' => $steps,
        'afterAcp' => $afterAcp,
        'logHint' => $logHint,
    ];
}

function recoveryRenderWizardRecommendationsPanel(array $rec): void
{
    $severity = (string) ($rec['severity'] ?? 'ok');
    if ($severity === 'warn') {
        $severity = 'warning';
    }
    $summaryClass = 'recovery-rec-summary--' . \preg_replace('/[^a-z]/', '', $severity);
    ?>
    <div class="recovery-rec-panel recovery-rec-panel--<?= \htmlspecialchars($severity) ?>" aria-labelledby="recovery-rec-heading">
        <h3 class="recovery-rec-panel__title" id="recovery-rec-heading">
            <?= recoveryFaIcon(16, 'lightbulb') ?> <?= \htmlspecialchars($rec['headline'] ?? '') ?>
        </h3>
        <div class="recovery-rec-summary <?= \htmlspecialchars($summaryClass) ?>">
            <?php recoveryEchoTrustedHtml($rec['summary'] ?? ''); ?>
        </div>
        <?php if (!empty($rec['logHint'])): ?>
        <div class="recovery-rec-loghint">
            <?= recoveryFaIcon(16, 'triangle-exclamation') ?> <?= \htmlspecialchars($rec['logHint']) ?>
        </div>
        <?php endif; ?>
        <?php if (!empty($rec['steps'])): ?>
        <p class="recovery-rec-section-label">Empfohlene Reihenfolge im nächsten Schritt:</p>
        <ol class="recovery-rec-steps">
        <?php foreach ($rec['steps'] as $step): ?>
            <li class="recovery-rec-step recovery-rec-step--<?= !empty($step['required']) ? 'required' : 'optional' ?>">
                <div class="recovery-rec-step__head">
                    <?php if (!empty($step['required'])): ?>
                    <span class="recovery-rec-badge recovery-rec-badge--required">Wichtig</span>
                    <?php elseif (!empty($step['recommended'])): ?>
                    <span class="recovery-rec-badge recovery-rec-badge--recommended">Empfohlen</span>
                    <?php endif; ?>
                    <strong class="recovery-rec-step__title"><?= \htmlspecialchars($step['title'] ?? '') ?></strong>
                </div>
                <div class="recovery-rec-step__why"><?php recoveryEchoTrustedHtml($step['why'] ?? ''); ?></div>
            </li>
        <?php endforeach; ?>
        </ol>
        <?php endif; ?>
        <?php if (!empty($rec['afterAcp'])): ?>
        <p class="recovery-rec-section-label recovery-rec-section-label--after">Danach (wenn der ACP wieder lädt):</p>
        <ol class="recovery-rec-next">
        <?php foreach ($rec['afterAcp'] as $item): ?>
            <li><?php recoveryEchoTrustedHtml($item); ?></li>
        <?php endforeach; ?>
        </ol>
        <?php endif; ?>
    </div>
    <?php
}

/**
 * @param list<string> $logExcerpts
 */
/**
 * @param list<string> $missingClasses
 */
/**
 * @param list<array{fqName?: string, name?: string, globalName?: string}> $undefConsts
 */
function recoveryRenderUndefinedConstantsList(array $undefConsts): void
{
    if ($undefConsts === []) {
        return;
    }
    ?>
    <section class="section">
        <header class="sectionHeader">
            <h2 class="sectionTitle">Fehlende Konstanten (Log)</h2>
            <p class="sectionDescription">Separate Kategorie — typisch fehlt <code>options.inc.php</code> oder Paket-Konstanten.</p>
        </header>
        <table class="<?= recoveryAcpTableClass() ?>">
            <thead>
                <tr><th>Konstante</th><th>Application</th></tr>
            </thead>
            <tbody>
            <?php foreach ($undefConsts as $uc): ?>
                <tr>
                    <td><code><?= \htmlspecialchars((string) ($uc['fqName'] ?? '')) ?></code></td>
                    <td><?php if (!empty($uc['application'])): ?><span class="badge"><?= \htmlspecialchars((string) $uc['application']) ?></span><?php else: ?>—<?php endif; ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php if (!recoveryUsesNativeAcpUi()): ?>
        <ul class="recovery-tag-list" style="margin-top:12px">
        <?php foreach ($undefConsts as $uc): ?>
            <li><span class="badge badgeYellow"><code><?= \htmlspecialchars((string) ($uc['fqName'] ?? '')) ?></code></span></li>
        <?php endforeach; ?>
        </ul>
        <?php endif; ?>
    </section>
    <?php
}

/**
 * @param array<string, mixed> $lastRun
 */
function recoveryRenderWizardLastRunSummary(array $lastRun): void
{
    if ($lastRun === []) {
        return;
    }

    $rows = recoveryBuildWizardDoneLastRunSummaryRows($lastRun);
    recoveryRenderWizardSummaryTable(
        $rows,
        'Letzte Ausführung',
        'Kurzüberblick der letzten Wizard-Ausführung.'
    );
}

/**
 * Done-Seite „Letzte Ausführung“ (v2.6.7-Layout: Zahlen rechts, Badges nur für Modus/Post-Check/ja-nein).
 *
 * @param array<string, mixed> $lastRun
 * @return list<array{label: string, hint: string, value: string, badge?: string, boolean?: bool}>
 */
function recoveryBuildWizardDoneLastRunSummaryRows(array $lastRun): array
{
    $dryRun = !empty($lastRun['dryRun']);
    $postCheck = \is_array($lastRun['postCheck'] ?? null) ? $lastRun['postCheck'] : null;
    $stillPresent = $postCheck !== null ? \count($postCheck['stillPresent'] ?? []) : 0;

    return [
        [
            'label' => 'Modus',
            'hint' => 'Dry-Run zeigt nur eine Vorschau ohne Änderungen.',
            'value' => $dryRun ? 'Dry-Run' : 'Live',
            'badge' => $dryRun ? 'badgeYellow' : 'badgeGreen',
        ],
        [
            'label' => 'Kopierte Dateien',
            'hint' => 'Dateien aus dem Paket ins WCF-Verzeichnis übernommen.',
            'value' => (string) \count($lastRun['copiedFiles'] ?? []),
        ],
        [
            'label' => 'Bootstrap-Dateien angepasst',
            'hint' => 'Event-Listener in bootstrap/*.php neutralisiert (leere Klassen).',
            'value' => (string) \count($lastRun['bootstrapNeutralized'] ?? []),
        ],
        [
            'label' => 'DB Event-Listener entfernt',
            'hint' => 'Einträge in wcf_event_listener für das Paket gelöscht.',
            'value' => (string) (int) ($lastRun['dbEventListenersDeleted'] ?? 0),
        ],
        [
            'label' => 'Cache-Dateien gelöscht',
            'hint' => 'Kompilierte Cache-Dateien im WCF-Cache-Verzeichnis entfernt.',
            'value' => (string) (int) ($lastRun['cacheDeleted'] ?? 0),
        ],
        [
            'label' => 'options.inc.php aus Paket',
            'hint' => 'Fehlende Konstanten aus dem Paket in options.inc.php eingetragen.',
            'value' => !empty($lastRun['optionsIncMerged']) ? 'ja' : 'nein',
            'boolean' => true,
        ],
        [
            'label' => 'Post-Check (Log)',
            'hint' => 'Undefined-constant-Fehler im aktuellen WoltLab-Log nach der Ausführung.',
            'value' => $dryRun ? '—' : ($stillPresent > 0 ? $stillPresent . ' offen' : 'keine'),
            'badge' => $dryRun ? '' : ($stillPresent > 0 ? 'badgeRed' : 'badgeGreen'),
        ],
    ];
}

/**
 * Vier Kern-Kennzahlen nach Wizard-Ausführung (Run- und Done-Übersicht).
 *
 * @param array<string, mixed> $result
 * @return list<array{label: string, hint: string, value: string, badge?: string}>
 */
function recoveryBuildWizardRunMetricRows(array $result): array
{
    $copied = \count($result['copiedFiles'] ?? []);
    $bootstrap = \count($result['bootstrapNeutralized'] ?? []);
    $dbEv = (int) ($result['dbEventListenersDeleted'] ?? 0);
    $cache = (int) ($result['cacheDeleted'] ?? 0);

    return [
        [
            'label' => 'Kopierte Dateien',
            'hint' => $copied > 0 ? 'Aus Paket-Archiv wiederhergestellt' : 'Schritt nicht aktiv oder nichts zu kopieren',
            'value' => (string) $copied,
            'badge' => $copied > 0 ? 'badgeGreen' : '',
        ],
        [
            'label' => 'Bootstrap angepasst',
            'hint' => $bootstrap > 0 ? 'register()-Aufrufe auskommentiert' : 'Keine Bootstrap-Dateien geändert',
            'value' => (string) $bootstrap,
            'badge' => $bootstrap > 0 ? 'badgeYellow' : 'badgeGreen',
        ],
        [
            'label' => 'DB Event-Listener',
            'hint' => $dbEv > 0 ? 'Verwaiste Listener aus DB entfernt' : 'Keine DB-Listener gelöscht',
            'value' => (string) $dbEv,
            'badge' => $dbEv > 0 ? 'badgeYellow' : 'badgeGreen',
        ],
        [
            'label' => 'Cache gelöscht',
            'hint' => $cache > 0 ? 'Kompilierte Templates / Cache-Dateien' : 'Cache-Schritt ohne Löschungen',
            'value' => (string) $cache,
            'badge' => $cache > 0 ? 'badgeGreen' : '',
        ],
    ];
}

/**
 * Wizard Done-Phase: strukturierte Aufräum-Section (Checkliste, Primäraktion, Hilfsaktionen).
 */
function recoveryRenderWizardCleanupSection(
    string $authHash,
    string $wizardUrl,
    string $recoveryBaseUrl,
    string $packageId,
    bool $wizardSuccess
): void {
    $uninstallUrl = recoveryBuildModeUrl(
        RECOVERY_MODE_PLUGIN_UNINSTALL,
        $authHash,
        $packageId !== '' ? ['package_identifier' => $packageId] : []
    );
    $cacheUrl = recoveryBuildModeUrl(RECOVERY_MODE_CACHE_CLEAR, $authHash, ['return' => 'wizard']);
    $pkgListUrl = recoveryBuildModeUrl(RECOVERY_MODE_PACKAGE_LIST_REPAIR, $authHash);
    $acpUrl = $recoveryBaseUrl . 'acp/';
    $homeUrl = recoveryHomeUrl($authHash);
    $doneUrl = recoveryBuildModeUrl(RECOVERY_MODE_RECOVERY_WIZARD, $authHash, ['wizard_phase' => 'done']);

    $acpStepBody = '<a href="' . \htmlspecialchars($acpUrl, ENT_QUOTES, 'UTF-8') . '" target="_blank" rel="noopener">ACP öffnen</a>'
        . ' — lädt das Dashboard ohne Fehler?';
    if (!$wizardSuccess) {
        $acpStepBody .= ' Falls nicht: <a href="' . \htmlspecialchars($wizardUrl . '&wizard_phase=package', ENT_QUOTES, 'UTF-8')
            . '" class="recovery-link-accent">Wizard von vorn</a> oder '
            . '<a href="' . \htmlspecialchars($homeUrl, ENT_QUOTES, 'UTF-8') . '" class="recovery-link-accent">Experten-Modi</a>.';
    }

    $uninstallStepBody = $packageId !== ''
        ? 'Paket <code>' . \htmlspecialchars($packageId, ENT_QUOTES, 'UTF-8') . '</code> vollständig deinstallieren (DB + Dateien).'
        : 'Defektes Plugin über <a href="' . \htmlspecialchars($uninstallUrl, ENT_QUOTES, 'UTF-8')
            . '" class="recovery-link-accent">Plugin Uninstall</a> im Recovery Tool entfernen.';

    ob_start();
    ?>
    <form method="POST" action="<?= \htmlspecialchars($doneUrl) ?>" class="recovery-inline-form"
        data-recovery-loading="Cache und Templates werden geleert …"
        title="Leert Cache, kompilierte Templates und Option-Fallback — Seite wird neu geladen (kein ACP nötig).">
        <?php recoveryRenderFormModeHiddenFields(RECOVERY_MODE_RECOVERY_WIZARD, $authHash); ?>
        <input type="hidden" name="wizard_phase" value="done">
        <input type="hidden" name="recovery_rebuild_display" value="1">
        <button type="submit" class="button"
            title="Leert Cache, kompilierte Templates und Option-Fallback — entspricht dem, was Sie im Notfall ohne erreichbares ACP brauchen (nicht ACP rebuild-data).">
            <?= recoveryFaIcon(16, 'rotate') ?> Status neu prüfen
        </button>
    </form>
    <?php
    $rebuildForm = (string) ob_get_clean();
    ?>
    <div class="recovery-run-block recovery-cleanup-section">
        <h2 class="recovery-run-block__title"><?= recoveryFaIcon(16, 'broom') ?> Aufräumen</h2>
        <p class="recovery-run-block__desc">ACP prüfen, Plugin entfernen, Recovery Tool vom Server löschen.</p>

        <?php
        try {
            recoveryRenderBrokenApplicationsAlert(\wcf\system\WCF::getDB(), WCF_N, $authHash, true);
        } catch (\Throwable $ignored) {
        }
        ?>

        <?php if ($wizardSuccess && $packageId !== ''): ?>
        <?php
        recoveryRenderAlert(
            'success',
            'Wenn das ACP wieder läuft, ist das Plugin <code>' . \htmlspecialchars($packageId, ENT_QUOTES, 'UTF-8')
                . '</code> vermutlich noch als Paket installiert. Entfernen Sie es jetzt gezielt — danach Recovery Tool löschen.',
            'ACP läuft?',
            true
        );
        ?>
        <?php elseif (!$wizardSuccess): ?>
        <?php
        recoveryRenderAlert(
            'info',
            'Prüfen Sie das ACP. Bei anhaltenden Problemen Wizard erneut starten oder Experten-Modi auf der Startseite nutzen.',
            'Nächster Schritt',
            false
        );
        ?>
        <?php endif; ?>

        <?php
        recoveryRenderStepList([
            ['title' => 'ACP testen', 'bodyHtml' => $acpStepBody],
            ['title' => 'Plugin entfernen', 'bodyHtml' => $uninstallStepBody],
            [
                'title' => 'Recovery Tool löschen',
                'bodyHtml' => 'Auth-Datei, <code>recovery-tool/</code> und <code>plugin-recovery-tool.php</code> vom Webspace entfernen (Sicherheit).',
            ],
        ]);
        ?>

        <?php if ($packageId !== ''): ?>
        <div class="recovery-cleanup-primary">
            <a href="<?= \htmlspecialchars($uninstallUrl) ?>" class="button buttonPrimary">
                <?= recoveryFaIcon(16, 'trash-can') ?> Plugin deinstallieren
            </a>
        </div>
        <?php endif; ?>

        <p class="recovery-cleanup-secondary-label">Optional — Cache, Paketliste oder Status neu prüfen (ohne ACP)</p>
        <?php
        recoveryRenderActionBar([
            '<a href="' . \htmlspecialchars($cacheUrl) . '" class="button">' . recoveryFaIcon(16, 'broom') . ' Cache leeren</a>',
            '<a href="' . \htmlspecialchars($pkgListUrl) . '" class="button">' . recoveryFaIcon(16, 'list') . ' Paketliste reparieren</a>',
            $rebuildForm,
        ], 'recovery-action-bar--cleanup-secondary');
        ?>
    </div>
    <?php
}

function recoveryRenderWizardPostCheckActions(
    string $authHash,
    string $wizardUrl,
    ?string $scopeApplication
): void {
    $scopeAttr = $scopeApplication !== null && $scopeApplication !== ''
        ? ' value="1" checked'
        : '';
    ?>
    <div class="recovery-post-check-actions">
        <form method="POST" action="<?= \htmlspecialchars($wizardUrl) ?>" class="recovery-inline-form"
            data-recovery-loading="Konstanten-Fallback wird ausgeführt …">
            <?php recoveryRenderFormModeHiddenFields(RECOVERY_MODE_RECOVERY_WIZARD, $authHash); ?>
            <input type="hidden" name="wizard_phase" value="run">
            <input type="hidden" name="wizard_execute" value="1">
            <input type="hidden" name="do_restore_options_inc" value="1">
            <input type="hidden" name="do_option_constants" value="1">
            <input type="hidden" name="do_cache" value="1">
            <button type="submit" class="button buttonPrimary"><?= recoveryFaIcon(16, 'rotate') ?> Konstanten-Fallback erneut</button>
        </form>
        <?php if ($scopeApplication !== null && $scopeApplication !== ''): ?>
        <form method="POST" action="<?= \htmlspecialchars($wizardUrl) ?>" class="recovery-inline-form"
            data-recovery-confirm="Application temporär deaktivieren?"
            data-recovery-confirm-title="Application deaktivieren">
            <?php recoveryRenderFormModeHiddenFields(RECOVERY_MODE_RECOVERY_WIZARD, $authHash); ?>
            <input type="hidden" name="wizard_phase" value="run">
            <input type="hidden" name="wizard_execute" value="1">
            <input type="hidden" name="do_disable_application"<?= $scopeAttr ?>>
            <input type="hidden" name="do_cache" value="1">
            <button type="submit" class="button"><?= recoveryFaIcon(16, 'ban') ?> Application deaktivieren</button>
        </form>
        <?php endif; ?>
        <a href="<?= \htmlspecialchars($wizardUrl . '&wizard_phase=plan') ?>" class="button"><?= recoveryFaIcon(16, 'list-check') ?> Zum Plan-Schritt</a>
    </div>
    <?php
}

function recoveryRenderWizardMissingClassesDetails(array $missingClasses): void
{
    if ($missingClasses === []) {
        return;
    }

    recoveryRenderPanelStart('', [
        'title' => 'Fehlende Klassen (' . \count($missingClasses) . ') — Details',
        'open' => true,
    ]);
    if (recoveryUsesNativeAcpUi()) {
        ?>
        <table class="table tableList">
            <thead>
                <tr><th>Application</th><th>Klasse</th></tr>
            </thead>
            <tbody>
            <?php foreach (recoveryGroupFqcnByApplicationPrefix($missingClasses) as $app => $classes): ?>
                <?php foreach ($classes as $cn): ?>
                <tr>
                    <td><code><?= \htmlspecialchars($app) ?></code></td>
                    <td><code><?= \htmlspecialchars($cn) ?></code></td>
                </tr>
                <?php endforeach; ?>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php
    } else {
        foreach (recoveryGroupFqcnByApplicationPrefix($missingClasses) as $app => $classes) {
            ?>
        <div class="recovery-missing-classes-group">
            <p class="recovery-missing-classes-app"><strong>App <code><?= \htmlspecialchars($app) ?></code></strong></p>
            <ul class="recovery-file-list recovery-file-list--classes">
            <?php foreach ($classes as $cn): ?>
                <li><code><?= \htmlspecialchars($cn) ?></code></li>
            <?php endforeach; ?>
            </ul>
        </div>
            <?php
        }
    }
    recoveryRenderPanelEnd();
}

function recoveryRenderLogExcerptsPanel(array $logExcerpts, string $panelId = 'wizard-log'): void
{
    if ($logExcerpts === []) {
        return;
    }
    $text = \implode("\n", $logExcerpts);
    recoveryRenderPanelStart('', [
        'title' => 'Log-Auszug (WoltLab log/*.txt)',
        'description' => 'Letzte relevante Zeilen — oft steht hier die exakte fehlende Klasse.',
    ]);
    ?>
            <button type="button" class="button small recovery-copy-btn" data-recovery-copy="<?= \htmlspecialchars($panelId) ?>">
                <?= recoveryUsesNativeAcpUi() ? '' : recoveryFaIcon(16, 'copy') ?> Gesamten Log-Auszug kopieren
            </button>
            <pre class="<?= recoveryUsesNativeAcpUi() ? 'monospace' : 'recoveryLog recovery-log-pre--tall' ?>" id="<?= \htmlspecialchars($panelId) ?>" style="max-height:320px;overflow:auto"><?= \htmlspecialchars($text) ?></pre>
    <?php
    recoveryRenderPanelEnd();
}

/**
 * @param array<string, mixed> $result
 * @param array<string, mixed> $plan
 * @return list<string>
 */
function recoveryBuildWizardRunInterpretation(array $result, array $plan): array
{
    if (!empty($result['dryRun']) || !empty($plan['dryRun'])) {
        return [
            'Dry-Run abgeschlossen — es wurden keine Dateien oder Datenbankeinträge geändert.',
            'Prüfen Sie das Protokoll unten. Wenn die Vorschau passt, Dry-Run deaktivieren und erneut ausführen.',
        ];
    }

    $lines = [];
    $copied = \count($result['copiedFiles'] ?? []);
    $bootstrap = \count($result['bootstrapNeutralized'] ?? []);
    $dbEv = (int) ($result['dbEventListenersDeleted'] ?? 0);
    $cache = (int) ($result['cacheDeleted'] ?? 0);

    if (!empty($plan['neutralizeBootstrap']) && $bootstrap === 0) {
        $lines[] = 'Bootstrap neutralisieren: Keine Änderung — entweder waren alle Listener bereits in Ordnung oder keine register()-Zeile betroffen.';
    } elseif ($bootstrap > 0) {
        $lines[] = 'Bootstrap: ' . $bootstrap . ' Datei(en) angepasst — ACP sollte nicht mehr an diesen fehlenden Listenern scheitern.';
    }

    if (!empty($plan['dbEventListeners']) && $dbEv === 0) {
        $lines[] = 'DB Event-Listener: Keine Einträge entfernt — Tabelle enthält (im Filter) keine Listener mit fehlender Klasse.';
    } elseif ($dbEv > 0) {
        $lines[] = 'DB: ' . $dbEv . ' Event-Listener gelöscht — Dashboard-Listener aus der Datenbank entfernt.';
    }

    if (!empty($plan['files']) && $copied === 0) {
        $lines[] = 'Dateien: Nichts kopiert — Paket-Archiv fehlt in der Session oder Klassen nicht im Archiv gefunden.';
    } elseif ($copied > 0) {
        $lines[] = 'Dateien: ' . $copied . ' Datei(en) wiederhergestellt.';
    }

    if ($cache > 0) {
        $lines[] = 'Cache: ' . $cache . ' Dateien gelöscht — bitte ACP jetzt testen.';
    }

    if (!empty($result['optionsIncMerged'])) {
        $lines[] = 'options.inc.php: Inhalt aus dem Paket-Archiv wurde angehängt (Backup der alten Datei liegt neben options.inc.php).';
    }

    if (!empty($result['applicationBootstrapSkipped'])) {
        $lines[] = 'Application: Core-__run() wurde vorübergehend übersprungen — nur Notlösung bis Konstanten/Paket repariert sind.';
    }

    $postCheck = $result['postCheck'] ?? null;
    if (\is_array($postCheck) && ($postCheck['stillPresent'] ?? []) !== []) {
        $lines[] = 'Post-Check: Im WoltLab-Log erscheinen weiterhin fehlende Konstanten — ACP/Frontend vermutlich noch defekt.';
    } elseif (\is_array($postCheck) && ($postCheck['stillPresent'] ?? []) === [] && ($postCheck['resolved'] ?? []) !== []) {
        $lines[] = 'Post-Check: Gemeldete Konstanten-Fehler im Log nicht mehr sichtbar — bitte ACP testen.';
    }

    if ($lines === []) {
        $lines[] = 'Es wurden keine reparierenden Schritte ausgeführt oder alle Schritte ohne Wirkung.';
    }

    return $lines;
}

/**
 * @return list<string>
 */
function recoveryScanWoltLabLogForRecentErrors(string $wcfDir, int $maxLines = 40): array
{
    $logDir = \rtrim($wcfDir, '/\\') . '/log';
    if (!\is_dir($logDir)) {
        return [];
    }

    $files = \glob($logDir . '/*.txt') ?: [];
    if ($files === []) {
        return [];
    }

    \usort($files, static function ($a, $b): int {
        return (\filemtime((string) $b) ?: 0) <=> (\filemtime((string) $a) ?: 0);
    });

    $content = @\file_get_contents($files[0]);
    if ($content === false || $content === '') {
        return [];
    }

    $lines = \preg_split('/\r\n|\r|\n/', $content) ?: [];
    $hits = [];
    $needles = ['ClassNotFoundException', 'Undefined constant', 'Fatal error', 'Error Message:'];

    foreach (\array_slice($lines, -500) as $line) {
        $line = \trim((string) $line);
        if ($line === '') {
            continue;
        }
        foreach ($needles as $needle) {
            if (\str_contains($line, $needle)) {
                $hits[] = $line;
                break;
            }
        }
        if (\count($hits) >= $maxLines) {
            break;
        }
    }

    return \array_slice($hits, -$maxLines);
}

function recoveryWizardLoadState(string $authHash): array
{
    recoveryEnsureSession();

    return $_SESSION['recovery_wizard'][$authHash] ?? [];
}

function recoveryWizardSaveState(string $authHash, array $state): void
{
    recoveryEnsureSession();
    $_SESSION['recovery_wizard'][$authHash] = \array_merge(
        $_SESSION['recovery_wizard'][$authHash] ?? [],
        $state
    );
}

/**
 * Paket-Archiv aus Session (Paket-Kontext + Wizard-State + POST).
 */
function recoveryResolveWizardExtractDir(string $authHash): ?string
{
    $fromPost = recoveryResolveTrustedExtractDir($authHash);
    if ($fromPost !== null) {
        return $fromPost;
    }

    $wizard = recoveryWizardLoadState($authHash);
    $stored = isset($wizard['extractDir']) ? (string) $wizard['extractDir'] : '';
    if ($stored !== '' && \is_dir($stored)) {
        $uploadBase = \realpath(recoveryWcfPath('uploads'));
        $extractReal = \realpath($stored);
        if (
            $uploadBase !== false
            && $extractReal !== false
            && \str_starts_with($extractReal, $uploadBase . \DIRECTORY_SEPARATOR)
        ) {
            return $extractReal;
        }
    }

    return null;
}

/**
 * @param array{orphans?: bool, files?: bool, neutralizeBootstrap?: bool, dbEventListeners?: bool, cache?: bool, extractDir?: string|null, classes?: list<string>, scopeApplication?: string|null, dryRun?: bool} $plan
 * @return array{
 *   copiedFiles: list<string>,
 *   cacheDeleted: int,
 *   bootstrapNeutralized: list<string>,
 *   dbEventListenersDeleted: int,
 *   optionsIncMerged: bool,
 *   applicationBootstrapSkipped: bool,
 *   postCheck: array{stillPresent: list<string>, resolved: list<string>, checkedAt: int}|null,
 *   dryRun: bool
 * }
 */
function recoveryWizardExecutePlan(
    string $wcfDir,
    \wcf\system\database\Database $db,
    int $wcfN,
    array $plan,
    array &$log
): array {
    $dryRun = !empty($plan['dryRun']);
    $pfx = $dryRun ? '[DRY-RUN] ' : '';
    $copiedFiles = [];
    $cacheDeleted = 0;
    $bootstrapNeutralized = [];
    $dbEventListenersDeleted = 0;
    $optionsIncMerged = false;
    $applicationBootstrapSkipped = false;
    $scopeApp = isset($plan['scopeApplication']) && (string) $plan['scopeApplication'] !== ''
        ? (string) $plan['scopeApplication']
        : null;

    if ($dryRun) {
        $log[] = $pfx . 'Keine Änderungen am Server — nur Vorschau.';
    }

    if (!empty($plan['restoreOptionsInc'])) {
        $extractDir = isset($plan['extractDir']) ? (string) $plan['extractDir'] : '';
        if ($extractDir === '' || !\is_dir($extractDir)) {
            $log[] = $pfx . '[options.inc.php] Kein Paket-Archiv — Merge übersprungen.';
        } else {
            $src = recoveryFindOptionsIncInExtractDir($extractDir);
            if ($src === null) {
                $log[] = $pfx . '[options.inc.php] Keine options.inc.php im Paket gefunden.';
            } else {
                $optionsIncMerged = recoveryMergeOptionsIncFromPackage($src, $wcfDir, $log, $dryRun);
            }
        }
    }

    if (!empty($plan['disableApplication']) && $scopeApp !== null) {
        if ($dryRun) {
            $log[] = $pfx . '[App deaktivieren] WÜRDE Core-__run() für App ' . $scopeApp . ' überspringen.';
            $applicationBootstrapSkipped = true;
        } else {
            $applicationBootstrapSkipped = recoverySkipApplicationCoreBootstrap($wcfDir, $scopeApp, $log, false);
        }
    }

    if (!empty($plan['orphans'])) {
        if ($dryRun) {
            $log[] = $pfx . 'WÜRDE: Verwaiste Paket-Applications in der DB bereinigen.';
        } else {
            $orphanResult = recoveryRepairOrphanedPackageReferences($db, $wcfN);
            foreach ($orphanResult['log'] as $entry) {
                $log[] = '[Paketliste] ' . $entry;
            }
        }
    }

    if (!empty($plan['files'])) {
        $extractDir = isset($plan['extractDir']) ? (string) $plan['extractDir'] : '';
        if ($extractDir === '' || !\is_dir($extractDir)) {
            $log[] = $pfx . '[Dateien] Kein gültiges Paket-Archiv in der Session – Schritt übersprungen.';
        } else {
            $extractLog = [];
            $payload = recoveryExtractPackageInstructionTars($extractDir, $extractLog);
            foreach ($extractLog as $entry) {
                $log[] = $pfx . '[Dateien] ' . $entry;
            }
            if ($payload !== null) {
                $classes = $plan['classes'] ?? recoveryFindMissingBootstrapClasses($wcfDir);
                $classes = \is_array($classes) ? $classes : [];
                if ($dryRun) {
                    foreach ($classes as $cn) {
                        $log[] = $pfx . '[Dateien] WÜRDE kopieren: ' . $cn;
                    }
                } else {
                    $copiedFiles = recoveryRepairMissingPluginFilesFromPayload(
                        $wcfDir,
                        $payload,
                        $classes,
                        $log
                    );
                }
            }
        }
    }

    if (!empty($plan['neutralizeBootstrap'])) {
        if ($dryRun) {
            $n = recoveryCountNeutralizableBootstrapRegisters($wcfDir);
            $log[] = $pfx . '[Bootstrap] WÜRDE ' . $n . ' register()-Aufruf(e) auskommentieren.';
        } else {
            $bootstrapNeutralized = recoveryNeutralizeBootstrapRegistersForMissingListeners($wcfDir, $log);
            if ($bootstrapNeutralized === []) {
                $log[] = '[Bootstrap] Keine Register geändert — ggf. bereits neutralisiert oder Muster nicht erkannt.';
            }
        }
    }

    if (!empty($plan['dbEventListeners'])) {
        $orphaned = recoveryFindOrphanedDbEventListeners($wcfDir, $db, $wcfN, $scopeApp);
        if ($dryRun) {
            foreach ($orphaned as $row) {
                $log[] = $pfx . '[Event-Listener DB] WÜRDE löschen: '
                    . ($row['listenerClassName'] ?? '?') . ' (ID ' . (int) ($row['listenerID'] ?? 0) . ')';
            }
            if ($orphaned === []) {
                $log[] = $pfx . '[Event-Listener DB] Keine Einträge zum Entfernen.';
            }
        } else {
            $dbEventListenersDeleted = recoveryPurgeOrphanedDbEventListeners($wcfDir, $db, $wcfN, $log, $scopeApp);
            if ($dbEventListenersDeleted === 0) {
                $log[] = '[Event-Listener DB] Keine Einträge mit fehlender Klasse gefunden.';
            }
        }
    }

    if (!empty($plan['cache']) || !empty($plan['optionConstantFallbacks'])) {
        if ($dryRun) {
            $log[] = $pfx . '[Cache] WÜRDE kompilierte Templates löschen und options.inc.php-Fallback aktualisieren.';
        } else {
            if (!empty($plan['cache'])) {
                $cacheDeleted = clearCompiledTemplates();
                $log[] = '[Cache] Gelöschte Cache-Dateien: ' . $cacheDeleted;
            }
            $optionFbLog = [];
            recoveryEnsureOptionConstantFallbacks($db, $wcfN, $optionFbLog);
            foreach ($optionFbLog as $entry) {
                $log[] = '[Cache] ' . $entry;
            }
        }
    }

    $postCheck = null;
    if (!$dryRun) {
        $needles = [];
        foreach (recoveryExtractUndefinedConstantsFromLog($wcfDir) as $c) {
            $needles[] = (string) ($c['fqName'] ?? '');
        }
        if ($needles !== []) {
            $postCheck = recoveryWizardPostCheckLogConstants($wcfDir, $needles);
            if ($postCheck['stillPresent'] !== []) {
                $log[] = '[Post-Check] WARNUNG: Im aktuellen Log weiterhin Undefined constant: '
                    . \implode(', ', $postCheck['stillPresent']);
            } else {
                $log[] = '[Post-Check] Keine der zuvor gemeldeten Undefined-constant-Fehler im neuesten Log.';
            }
        }
    }

    return [
        'copiedFiles' => $copiedFiles,
        'cacheDeleted' => $cacheDeleted,
        'bootstrapNeutralized' => $bootstrapNeutralized,
        'dbEventListenersDeleted' => $dbEventListenersDeleted,
        'optionsIncMerged' => $optionsIncMerged,
        'applicationBootstrapSkipped' => $applicationBootstrapSkipped,
        'postCheck' => $postCheck,
        'dryRun' => $dryRun,
    ];
}

/**
 * @param list<string> $labels
 */
function recoveryRenderWizardPhaseSteps(int $activeIndex, array $labels): void
{
    if (recoveryUsesNativeAcpUi()) {
        echo '<div class="section tabMenuContainer" role="navigation" aria-label="Wizard-Fortschritt">';
        echo '<nav class="tabMenu"><ul>';
        foreach ($labels as $i => $label) {
            $liClass = $i === $activeIndex ? ' class="active"' : '';
            echo '<li' . $liClass . '><span>' . \htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</span></li>';
        }
        echo '</ul></nav></div>';

        return;
    }

    echo '<div class="recovery-surface recovery-progress" role="navigation" aria-label="Wizard-Fortschritt">';
    echo '<div class="wizardSteps recovery-wizard-nav">';
    foreach ($labels as $i => $label) {
        $cls = 'wizardStep';
        if ($i < $activeIndex) {
            $cls .= ' completed';
        } elseif ($i === $activeIndex) {
            $cls .= ' active';
        }
        echo '<div class="' . $cls . '">';
        if ($i < $activeIndex) {
            echo '<div class="wizardStepNumber" aria-hidden="true"></div>';
        } else {
            echo '<div class="wizardStepNumber">' . ($i + 1) . '</div>';
        }
        echo '<div class="wizardStepLabel">' . \htmlspecialchars($label) . '</div>';
        echo '</div>';
    }
    echo '</div></div>';
}

/**
 * WoltLab-ACP-Stil Datei-Upload.
 *
 * @param array<string, string> $attrs extra input attributes (e.g. accept, required)
 */
function recoveryRenderFileInput(string $id, string $name, string $label, array $attrs = []): void
{
    $accept = isset($attrs['accept']) ? ' accept="' . \htmlspecialchars($attrs['accept'], ENT_QUOTES, 'UTF-8') . '"' : '';
    $required = !empty($attrs['required']) ? ' required' : '';
    unset($attrs['accept'], $attrs['required']);
    $extra = '';
    foreach ($attrs as $k => $v) {
        $extra .= ' ' . \htmlspecialchars((string) $k, ENT_QUOTES, 'UTF-8') . '="'
            . \htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8') . '"';
    }
    if (recoveryUsesNativeAcpUi()) {
        ?>
        <dl>
            <dt><label for="<?= \htmlspecialchars($id, ENT_QUOTES, 'UTF-8') ?>"><?= \htmlspecialchars($label) ?></label></dt>
            <dd>
                <input type="file" name="<?= \htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>" id="<?= \htmlspecialchars($id, ENT_QUOTES, 'UTF-8') ?>"<?= $accept ?><?= $required ?><?= $extra ?>>
            </dd>
        </dl>
        <?php

        return;
    }
    ?>
    <div class="recovery-file-field">
        <label for="<?= \htmlspecialchars($id, ENT_QUOTES, 'UTF-8') ?>"><strong><?= \htmlspecialchars($label) ?></strong></label>
        <div class="recovery-file-input">
            <input type="file" name="<?= \htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>" id="<?= \htmlspecialchars($id, ENT_QUOTES, 'UTF-8') ?>" class="recovery-file-input__native"<?= $accept ?><?= $required ?><?= $extra ?>>
            <span class="button recovery-file-input__btn" tabindex="0" role="button"><?= recoveryFaIcon(16, 'folder-open') ?> Durchsuchen …</span>
            <span class="recovery-file-input__name" data-recovery-file-label for="<?= \htmlspecialchars($id, ENT_QUOTES, 'UTF-8') ?>">Keine Datei gewählt</span>
        </div>
    </div>
    <?php
}

/**
 * @return array{status: string, httpCode: int|null, label: string, detail: string}
 */
function recoveryProbeAcpReachability(string $wcfDir, string $baseUrl): array
{
    $acpIndex = \rtrim($wcfDir, '/\\') . '/acp/index.php';
    if (!\is_file($acpIndex)) {
        return [
            'status' => 'error',
            'httpCode' => null,
            'label' => 'ACP nicht gefunden',
            'detail' => 'Datei acp/index.php fehlt im WoltLab-Verzeichnis.',
        ];
    }

    $url = \rtrim($baseUrl, '/') . '/acp/';
    $ctx = \stream_context_create([
        'http' => [
            'method' => 'GET',
            'timeout' => 10,
            'ignore_errors' => true,
            'follow_location' => 2,
            'header' => "User-Agent: WoltLab-Plugin-Recovery/" . RECOVERY_VERSION . "\r\n",
        ],
        'ssl' => [
            'verify_peer' => true,
            'verify_peer_name' => true,
        ],
    ]);

    $body = @\file_get_contents($url, false, $ctx);
    $httpCode = null;
    if (isset($http_response_header[0]) && \preg_match('/\s(\d{3})\s/', (string) $http_response_header[0], $m)) {
        $httpCode = (int) $m[1];
    }

    if ($httpCode === null) {
        return [
            'status' => 'warn',
            'httpCode' => null,
            'label' => 'HTTP-Check nicht möglich',
            'detail' => 'ACP-Dateien vorhanden — bitte manuell im Browser testen.',
        ];
    }

    if ($httpCode >= 200 && $httpCode < 400) {
        $fatalInBody = \is_string($body) && (
            \str_contains($body, 'Fatal error')
            || \str_contains($body, 'ClassNotFoundException')
            || \str_contains($body, 'Undefined constant')
        );

        if ($fatalInBody) {
            return [
                'status' => 'error',
                'httpCode' => $httpCode,
                'label' => 'ACP antwortet mit PHP-Fehler',
                'detail' => 'HTTP ' . $httpCode . ' — Seite enthält vermutlich noch einen Fatal Error.',
            ];
        }

        return [
            'status' => 'ok',
            'httpCode' => $httpCode,
            'label' => 'ACP erreichbar',
            'detail' => 'HTTP ' . $httpCode . ' — Login-Seite oder Dashboard scheint zu laden.',
        ];
    }

    return [
        'status' => 'error',
        'httpCode' => $httpCode,
        'label' => 'ACP nicht erreichbar',
        'detail' => 'HTTP ' . $httpCode . ' — prüfen Sie Webserver, .htaccess und PHP-Fehlerlog.',
    ];
}

/**
 * @param list<string> $execLog
 * @return array{
 *   warnings: list<string>,
 *   optionConstantCount: int|null,
 *   optionConstantDetail: string|null
 * }
 */
function recoveryParseWizardRunLogInsights(array $execLog): array
{
    $warnings = [];
    $optionConstantCount = null;
    $optionConstantDetail = null;

    foreach ($execLog as $line) {
        $line = (string) $line;
        if (
            \str_contains($line, 'WARNUNG')
            || \str_contains($line, 'fehlgeschlagen')
            || \str_contains($line, 'übersprungen')
            || \str_contains($line, 'nicht beschreibbar')
            || \str_contains($line, 'nicht gefunden')
        ) {
            $warnings[] = $line;
        }
        if (\preg_match(
            '/Option-Konstanten-Fallback: options\.inc\.php ergänzt \(globale Konstanten: (\d+), davon (\d+) aus DB, (\d+) aus Template-Scan; Namespace-Spiegel: (\d+)\)/',
            $line,
            $m
        )) {
            $optionConstantCount = (int) $m[1];
            $optionConstantDetail = $m[1] . ' Konstanten (DB: ' . $m[2] . ', Templates: ' . $m[3]
                . ', Namespace-Spiegel: ' . $m[4] . ')';
        }
    }

    return [
        'warnings' => \array_values(\array_unique($warnings)),
        'optionConstantCount' => $optionConstantCount,
        'optionConstantDetail' => $optionConstantDetail,
    ];
}

/**
 * @param array<string, mixed> $result
 * @param array<string, mixed> $plan
 * @return list<array{label: string, selected: bool, status: string, detail: string, icon: string}>
 */
function recoveryBuildWizardRunStepRows(array $result, array $plan): array
{
    $dryRun = !empty($result['dryRun']) || !empty($plan['dryRun']);
    $copied = \count($result['copiedFiles'] ?? []);
    $bootstrap = \count($result['bootstrapNeutralized'] ?? []);
    $dbEv = (int) ($result['dbEventListenersDeleted'] ?? 0);
    $cache = (int) ($result['cacheDeleted'] ?? 0);

    $row = static function (
        bool $selected,
        string $label,
        string $icon,
        string $detail,
        string $status
    ) use ($dryRun): array {
        if (!$selected) {
            return [
                'label' => $label,
                'selected' => false,
                'status' => 'neutral',
                'detail' => 'Nicht ausgewählt',
                'icon' => $icon,
            ];
        }
        if ($dryRun) {
            return [
                'label' => $label,
                'selected' => true,
                'status' => 'warn',
                'detail' => 'Dry-Run: ' . $detail,
                'icon' => $icon,
            ];
        }

        return [
            'label' => $label,
            'selected' => true,
            'status' => $status,
            'detail' => $detail,
            'icon' => $icon,
        ];
    };

    return [
        $row(
            !empty($plan['orphans']),
            'Paketliste reparieren',
            'list',
            'Verwaiste Paket-Applications in der DB bereinigen',
            'ok'
        ),
        $row(
            !empty($plan['files']),
            'Plugin-Dateien wiederherstellen',
            'file-arrow-up',
            $copied > 0 ? $copied . ' Datei(en) aus Paket-Archiv kopiert' : 'Keine Dateien kopiert (Archiv fehlt oder Klassen nicht gefunden)',
            $copied > 0 ? 'ok' : 'warn'
        ),
        $row(
            !empty($plan['neutralizeBootstrap']),
            'Bootstrap neutralisieren',
            'code',
            $bootstrap > 0 ? $bootstrap . ' register()-Aufruf(e) auskommentiert' : 'Keine Bootstrap-Register geändert',
            $bootstrap > 0 ? 'warn' : 'ok'
        ),
        $row(
            !empty($plan['dbEventListeners']),
            'DB Event-Listener bereinigen',
            'database',
            $dbEv > 0 ? $dbEv . ' Listener mit fehlender Klasse entfernt' : 'Keine verwaisten DB-Listener gefunden',
            $dbEv > 0 ? 'warn' : 'ok'
        ),
        $row(
            !empty($plan['restoreOptionsInc']),
            'options.inc.php mergen',
            'file-code',
            !empty($result['optionsIncMerged']) ? 'Inhalt aus Paket angehängt (Backup neben options.inc.php)' : 'Merge nicht ausgeführt oder Paket ohne options.inc.php',
            !empty($result['optionsIncMerged']) ? 'ok' : 'neutral'
        ),
        $row(
            !empty($plan['optionConstantFallbacks']) || !empty($plan['cache']),
            'Option-Konstanten & Cache',
            'broom',
            ($cache > 0 ? $cache . ' Cache-Dateien gelöscht' : 'Cache-Schritt ohne Löschungen')
                . (!empty($plan['optionConstantFallbacks']) ? ' · Fallback-Block in options.inc.php' : ''),
            $cache > 0 || !empty($plan['optionConstantFallbacks']) ? 'ok' : 'neutral'
        ),
        $row(
            !empty($plan['disableApplication']),
            'Application Bootstrap überspringen',
            'ban',
            !empty($result['applicationBootstrapSkipped'])
                ? 'Core-__run() für App temporär umgangen (Notlösung)'
                : 'Schritt nicht angewendet',
            !empty($result['applicationBootstrapSkipped']) ? 'warn' : 'neutral'
        ),
    ];
}

/**
 * @param array<string, mixed> $result
 * @param array<string, mixed> $plan
 * @param list<string> $execLog
 * @param array<string, mixed> $wizardState
 * @param list<string> $interpretation
 */
function recoveryRenderWizardRunSummary(
    string $authHash,
    string $wizardUrl,
    string $recoveryBaseUrl,
    string $wcfDir,
    array $result,
    array $plan,
    array $execLog,
    array $wizardState,
    array $interpretation
): void {
    $dryRun = !empty($result['dryRun']);
    $postCheck = \is_array($result['postCheck'] ?? null) ? $result['postCheck'] : null;
    $postStill = $postCheck !== null ? ($postCheck['stillPresent'] ?? []) : [];
    $postResolved = $postCheck !== null ? ($postCheck['resolved'] ?? []) : [];
    $packageLabel = (string) ($wizardState['packageLabel'] ?? '');
    $scopeApp = isset($wizardState['scopeApplication']) ? (string) $wizardState['scopeApplication'] : '';
    $logInsights = recoveryParseWizardRunLogInsights($execLog);
    $acpProbe = $dryRun ? null : recoveryProbeAcpReachability($wcfDir, $recoveryBaseUrl);
    $recentLogErrors = recoveryScanWoltLabLogForRecentErrors($wcfDir, 8);
    $stepRows = recoveryBuildWizardRunStepRows($result, $plan);

    $copied = \count($result['copiedFiles'] ?? []);
    $bootstrap = \count($result['bootstrapNeutralized'] ?? []);
    $dbEv = (int) ($result['dbEventListenersDeleted'] ?? 0);
    $cache = (int) ($result['cacheDeleted'] ?? 0);

    $executedCount = \count(\array_filter($stepRows, static fn(array $r): bool => $r['selected']));
    $statusIcon = static function (string $status): string {
        return match ($status) {
            'ok' => recoveryFaIcon(16, 'circle-check'),
            'warn' => recoveryFaIcon(16, 'triangle-exclamation'),
            'error' => recoveryFaIcon(16, 'circle-xmark'),
            default => recoveryFaIcon(16, 'circle', true),
        };
    };

    recoveryRenderBackupStepHero(
        'Schritt 5 — Ausführung',
        'Ergebnis der ausgeführten Reparatur-Schritte auf Ihrer Installation.',
        'play'
    );

    $nativeRunUi = recoveryUsesNativeAcpUi();
    if (!$nativeRunUi) {
        echo '<div class="recovery-wizard-stack">';
    }

    recoveryRenderWizardCompletionAlert(
        'run',
        $dryRun,
        $postStill !== [],
        false,
        $packageLabel,
        $scopeApp,
        $executedCount
    );

    recoveryRenderWizardSummaryTable(
        recoveryBuildWizardRunMetricRows($result),
        'Kennzahlen',
        'Ergebnis der ausgeführten Reparatur-Schritte auf einen Blick.'
    );

    if ($interpretation !== []) {
        $interpBody = '<ul class="recovery-step-list">';
        foreach ($interpretation as $line) {
            $interpBody .= '<li>' . \htmlspecialchars((string) $line) . '</li>';
        }
        $interpBody .= '</ul>';
        recoveryRenderAlert('info', $interpBody, 'Einordnung', true);
    }

    if ($nativeRunUi) {
        recoveryRenderAcpSectionStart(
            'Ausgeführte Schritte',
            'Was im Plan ausgewählt war und welches Ergebnis jeder Schritt hatte.'
        );
    } else {
        ?>
    <div class="recovery-run-block">
        <h2 class="recovery-run-block__title"><?= recoveryFaIcon(16, 'list-check') ?> Ausgeführte Schritte</h2>
        <p class="recovery-run-block__desc">Was im Plan ausgewählt war und welches Ergebnis jeder Schritt hatte.</p>
        <?php
    }
    ?>
        <table class="<?= $nativeRunUi ? 'table tableList' : 'tableList recovery-table-list recovery-data-table recovery-data-table--check recovery-system-check-table' ?>">
            <colgroup>
                <col class="recovery-syscheck-col-icon">
                <col class="recovery-syscheck-col-label">
                <col class="recovery-syscheck-col-result">
            </colgroup>
            <thead>
                <tr>
                    <th class="columnIcon recovery-syscheck-th-icon" aria-label="Status"><span class="silent">Status</span></th>
                    <th class="recovery-syscheck-th-label">Schritt</th>
                    <th class="recovery-syscheck-th-result">Ergebnis</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($stepRows as $stepRow):
                if (!$stepRow['selected']) {
                    continue;
                }
                $stepStatus = (string) $stepRow['status'];
                $badge = match ($stepStatus) {
                    'ok' => 'badgeGreen',
                    'warn' => 'badgeYellow',
                    'error' => 'badgeRed',
                    default => 'badge',
                };
                $badgeLabel = match ($stepStatus) {
                    'ok' => 'Erledigt',
                    'warn' => 'Hinweis',
                    'error' => 'Fehler',
                    default => '—',
                };
            ?>
                <tr class="recovery-syscheck-row recovery-syscheck-row--<?= \htmlspecialchars($stepStatus !== '' ? $stepStatus : 'neutral') ?>">
                    <td class="columnIcon recovery-syscheck-icon"><?= $statusIcon($stepStatus) ?></td>
                    <td class="recovery-syscheck-label"><strong><?= \htmlspecialchars((string) $stepRow['label']) ?></strong></td>
                    <td class="columnText recovery-syscheck-result">
                        <div class="recovery-check-detail"><?= \htmlspecialchars((string) $stepRow['detail']) ?></div>
                        <span class="badge <?= $badge ?>"><?= \htmlspecialchars($badgeLabel) ?></span>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php
    if ($nativeRunUi) {
        recoveryRenderAcpSectionEnd();
        recoveryRenderAcpSectionStart('Post-Check', 'Schnellprüfung nach der Ausführung — ohne vollständiges Log.');
    } else {
        ?>
    </div>

    <div class="recovery-run-block recovery-run-block--post-check">
        <h2 class="recovery-run-block__title"><?= recoveryFaIcon(16, 'shield-halved') ?> Post-Check</h2>
        <p class="recovery-run-block__desc">Schnellprüfung nach der Ausführung — ohne vollständiges Log.</p>
        <?php
    }
    ?>
        <table class="<?= $nativeRunUi ? 'table tableList' : 'tableList recovery-table-list recovery-data-table recovery-data-table--check recovery-system-check-table' ?>">
            <colgroup>
                <col class="recovery-syscheck-col-icon">
                <col class="recovery-syscheck-col-label">
                <col class="recovery-syscheck-col-result">
            </colgroup>
            <thead>
                <tr>
                    <th class="columnIcon recovery-syscheck-th-icon" aria-label="Status"><span class="silent">Status</span></th>
                    <th class="recovery-syscheck-th-label">Prüfung</th>
                    <th class="recovery-syscheck-th-result">Ergebnis</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($acpProbe !== null):
                $acpStatus = (string) $acpProbe['status'];
            ?>
                <tr class="recovery-syscheck-row recovery-syscheck-row--<?= \htmlspecialchars($acpStatus) ?>">
                    <td class="columnIcon recovery-syscheck-icon"><?= $statusIcon($acpStatus) ?></td>
                    <td class="recovery-syscheck-label"><strong>ACP-Erreichbarkeit</strong></td>
                    <td class="columnText recovery-syscheck-result">
                        <div class="recovery-check-detail">
                            <div class="recovery-check-detail__head">
                                <span class="badge <?= match ($acpStatus) {
                                    'ok' => 'badgeGreen',
                                    'warn' => 'badgeYellow',
                                    default => 'badgeRed',
                                } ?>"><?= \htmlspecialchars((string) $acpProbe['label']) ?></span>
                                <span class="recovery-check-detail__text"><?= \htmlspecialchars((string) $acpProbe['detail']) ?></span>
                            </div>
                            <p class="recovery-check-detail__action">
                                <a href="<?= \htmlspecialchars($recoveryBaseUrl . 'acp/') ?>" target="_blank" rel="noopener">ACP öffnen</a>
                                — Dashboard ohne Fatal Error?
                            </p>
                        </div>
                    </td>
                </tr>
            <?php endif; ?>
                <tr class="recovery-syscheck-row recovery-syscheck-row--<?= $dryRun ? 'neutral' : ($postStill !== [] ? 'error' : 'ok') ?>">
                    <td class="columnIcon recovery-syscheck-icon"><?= $statusIcon($dryRun ? 'neutral' : ($postStill !== [] ? 'error' : 'ok')) ?></td>
                    <td class="recovery-syscheck-label"><strong>Undefined-constant (Log)</strong></td>
                    <td class="columnText recovery-syscheck-result">
                        <div class="recovery-check-detail">
                        <?php if ($dryRun): ?>
                            <div class="recovery-check-detail__head">
                                <span class="badge">Dry-Run — nicht geprüft</span>
                                <span class="recovery-check-detail__text">Log-Prüfung erst nach echter Ausführung.</span>
                            </div>
                        <?php elseif ($postStill !== []): ?>
                            <div class="recovery-check-detail__head">
                                <span class="badge badgeRed"><?= \count($postStill) ?> weiterhin gemeldet</span>
                                <span class="recovery-check-detail__text">Undefined-constant-Fehler im neuesten Log.</span>
                            </div>
                            <div class="recovery-check-detail__tags">
                            <?php foreach (\array_slice($postStill, 0, 4) as $c): ?>
                                <code class="recovery-run-const-tag"><?= \htmlspecialchars((string) $c) ?></code>
                            <?php endforeach; ?>
                            <?php if (\count($postStill) > 4): ?>
                                <span class="recovery-check-detail__more">… und <?= \count($postStill) - 4 ?> weitere</span>
                            <?php endif; ?>
                            </div>
                            <?php recoveryRenderWizardPostCheckActions($authHash, $wizardUrl, $scopeApp !== '' ? $scopeApp : null); ?>
                        <?php elseif ($postResolved !== []): ?>
                            <div class="recovery-check-detail__head">
                                <span class="badge badgeGreen">Nicht mehr im neuesten Log</span>
                                <span class="recovery-check-detail__text"><?= \count($postResolved) ?> zuvor gemeldete Konstante(n) scheinen behoben.</span>
                            </div>
                            <p class="recovery-check-detail__action">
                                <a href="<?= \htmlspecialchars(recoveryBuildModeUrl(RECOVERY_MODE_SYSTEM_CHECK, $authHash)) ?>">System-Check</a>
                                für Gesamtübersicht
                            </p>
                        <?php else: ?>
                            <div class="recovery-check-detail__head">
                                <span class="badge badgeGreen">Keine bekannten Konstanten-Fehler</span>
                                <span class="recovery-check-detail__text">Vor der Ausführung keine Undefined-constant-Einträge im Log gefunden.</span>
                            </div>
                            <p class="recovery-check-detail__action">
                                <a href="<?= \htmlspecialchars(recoveryBuildModeUrl(RECOVERY_MODE_SYSTEM_CHECK, $authHash)) ?>">System-Check</a>
                                für Gesamtübersicht
                            </p>
                        <?php endif; ?>
                        </div>
                    </td>
                </tr>
            <?php if ($logInsights['optionConstantCount'] !== null): ?>
                <tr class="recovery-syscheck-row recovery-syscheck-row--ok">
                    <td class="columnIcon recovery-syscheck-icon"><?= $statusIcon('ok') ?></td>
                    <td class="recovery-syscheck-label"><strong>Option-Konstanten</strong></td>
                    <td class="columnText recovery-syscheck-result">
                        <div class="recovery-check-detail">
                            <div class="recovery-check-detail__head">
                                <span class="badge badgeGreen"><?= (int) $logInsights['optionConstantCount'] ?> ergänzt</span>
                                <span class="recovery-check-detail__text"><?= \htmlspecialchars((string) ($logInsights['optionConstantDetail'] ?? '')) ?></span>
                            </div>
                        </div>
                    </td>
                </tr>
            <?php endif; ?>
            <?php if ($recentLogErrors !== [] && !$dryRun): ?>
                <tr class="recovery-syscheck-row recovery-syscheck-row--warn">
                    <td class="columnIcon recovery-syscheck-icon"><?= $statusIcon('warn') ?></td>
                    <td class="recovery-syscheck-label"><strong>Letzte Log-Hinweise</strong></td>
                    <td class="columnText recovery-syscheck-result">
                        <?php if ($nativeRunUi): ?>
                        <ul class="recovery-run-log-hints">
                        <?php foreach ($recentLogErrors as $logLine): ?>
                            <li><code><?= \htmlspecialchars((string) $logLine) ?></code></li>
                        <?php endforeach; ?>
                        </ul>
                        <?php else: ?>
                        <details class="recovery-panel recovery-panel--inline">
                            <summary><?= \count($recentLogErrors) ?> relevante Zeile(n) aus WoltLab log/*.txt</summary>
                            <div class="recovery-panel__body">
                                <ul class="recovery-run-log-hints">
                                <?php foreach ($recentLogErrors as $logLine): ?>
                                    <li><code><?= \htmlspecialchars((string) $logLine) ?></code></li>
                                <?php endforeach; ?>
                                </ul>
                            </div>
                        </details>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    <?php
    if ($nativeRunUi) {
        recoveryRenderAcpSectionEnd();
    } else {
        echo '</div>';
    }

    if ($copied > 0) {
        recoveryRenderPanelStart('', ['title' => 'Kopierte Dateien (' . $copied . ')']);
        echo '<ul class="recovery-file-list">';
        foreach ($result['copiedFiles'] as $file) {
            echo '<li><code>' . \htmlspecialchars((string) $file) . '</code></li>';
        }
        echo '</ul>';
        recoveryRenderPanelEnd();
    }
    if ($bootstrap > 0) {
        recoveryRenderPanelStart('', ['title' => 'Bootstrap-Dateien angepasst (' . $bootstrap . ')']);
        echo '<ul class="recovery-file-list">';
        foreach ($result['bootstrapNeutralized'] as $file) {
            echo '<li><code>' . \htmlspecialchars((string) $file) . '</code></li>';
        }
        echo '</ul>';
        recoveryRenderPanelEnd();
    }
    if ($logInsights['warnings'] !== []) {
        recoveryRenderPanelStart('', [
            'title' => 'Hinweise & Warnungen (' . \count($logInsights['warnings']) . ')',
            'open' => true,
        ]);
        echo '<ul class="recovery-run-log-hints">';
        foreach (\array_slice($logInsights['warnings'], 0, 12) as $warnLine) {
            echo '<li>' . \htmlspecialchars((string) $warnLine) . '</li>';
        }
        echo '</ul>';
        recoveryRenderPanelEnd();
    }

    if ($nativeRunUi) {
        recoveryRenderAcpSectionStart('Nächste Schritte', 'ACP testen, Cache prüfen, Plugin entfernen und Recovery Tool aufräumen.');
    } else {
        ?>
    <div class="recovery-run-block">
        <h2 class="recovery-run-block__title"><?= recoveryFaIcon(16, 'route') ?> Nächste Schritte</h2>
        <?php
    }
        $runPkgId = $packageLabel;
        $runUninstallUrl = recoveryBuildModeUrl(
            RECOVERY_MODE_PLUGIN_UNINSTALL,
            $authHash,
            $runPkgId !== '' ? ['package_identifier' => $runPkgId] : []
        );
        recoveryRenderStepList([
            [
                'title' => 'ACP testen',
                'bodyHtml' => '<a href="' . \htmlspecialchars($recoveryBaseUrl . 'acp/', ENT_QUOTES, 'UTF-8') . '" target="_blank" rel="noopener">ACP öffnen</a> — Dashboard ohne Fatal Error?',
            ],
            [
                'title' => 'Cache prüfen',
                'bodyHtml' => '<a href="' . \htmlspecialchars(recoveryBuildModeUrl(RECOVERY_MODE_CACHE_CLEAR, $authHash, ['return' => 'wizard']), ENT_QUOTES, 'UTF-8') . '">Cache leeren</a> falls Anzeige veraltet wirkt',
            ],
            [
                'title' => 'Plugin entfernen',
                'bodyHtml' => 'ACP ok → <a href="' . \htmlspecialchars($runUninstallUrl, ENT_QUOTES, 'UTF-8') . '">Plugin vollständig entfernen</a>',
            ],
            [
                'title' => 'System-Check',
                'bodyHtml' => '<a href="' . \htmlspecialchars(recoveryBuildModeUrl(RECOVERY_MODE_SYSTEM_CHECK, $authHash), ENT_QUOTES, 'UTF-8') . '">System-Check</a> für Gesamtübersicht',
            ],
            [
                'title' => 'Recovery Tool löschen',
                'body' => 'Auth-Datei, recovery-tool/ und plugin-recovery-tool.php vom Webspace entfernen (Sicherheit).',
            ],
        ]);
    if ($nativeRunUi) {
        recoveryRenderAcpSectionEnd();
    } else {
        echo '</div>';
    }

    recoveryRenderPanelStart('', ['title' => 'Technisches Protokoll']);
    ?>
            <button type="button" class="button small recovery-copy-btn" data-recovery-copy="wizard-exec-log">
                <?= $nativeRunUi ? '' : recoveryFaIcon(16, 'copy') ?> Protokoll kopieren
            </button>
            <pre class="<?= $nativeRunUi ? 'monospace' : 'recovery-cmd-block recovery-log-pre--tall' ?>" id="wizard-exec-log" style="max-height:360px;overflow:auto"><?php
                foreach ($execLog as $line) {
                    echo \htmlspecialchars((string) $line) . "\n";
                }
            ?></pre>
    <?php
    recoveryRenderPanelEnd();

    if (!$nativeRunUi) {
        echo '</div>';
    }
    recoveryRenderActionBar([
        '<a href="' . \htmlspecialchars($wizardUrl . '&wizard_phase=done', ENT_QUOTES, 'UTF-8') . '" class="button buttonPrimary">Weiter zur Zusammenfassung</a>',
        '<a href="' . \htmlspecialchars($recoveryBaseUrl . 'acp/', ENT_QUOTES, 'UTF-8') . '" class="button" target="_blank" rel="noopener">'
            . ($nativeRunUi ? '' : recoveryFaIcon(16, 'gauge-high')) . ' ACP testen</a>',
        '<a href="' . \htmlspecialchars(recoveryBuildModeUrl(RECOVERY_MODE_SYSTEM_CHECK, $authHash), ENT_QUOTES, 'UTF-8') . '" class="button">'
            . ($nativeRunUi ? '' : recoveryFaIcon(16, 'stethoscope')) . ' System-Check</a>',
    ]);
}

/**
 * @param list<array{label: string, value: int|string, status: string}> $metrics status: ok|warn|error|neutral
 */
function recoveryRenderDiagnosisMetricGrid(array $metrics): void
{
    $icon = static function (string $status): string {
        return match ($status) {
            'ok' => 'circle-check',
            'warn' => 'triangle-exclamation',
            'error' => 'circle-xmark',
            default => 'chart-simple',
        };
    };
    echo '<div class="acpDashboardBoxContainer">';
    foreach ($metrics as $metric) {
        $status = (string) ($metric['status'] ?? 'neutral');
        $mod = $status !== 'neutral' && $status !== 'ok' ? ' acpDashboardBox--' . \htmlspecialchars($status) : '';
        echo '<div class="acpDashboardBox' . $mod . '">';
        echo '<span class="acpDashboardBox__icon" aria-hidden="true">' . recoveryFaIcon(20, $icon($status)) . '</span>';
        echo '<p class="acpDashboardBox__title">' . \htmlspecialchars((string) $metric['label']) . '</p>';
        echo '<p class="acpDashboardBox__value">' . \htmlspecialchars((string) $metric['value']) . '</p>';
        echo '</div>';
    }
    echo '</div>';
}

function recoveryGetLastFullLogBlock(string $wcfDir): ?string
{
    $logDir = \rtrim($wcfDir, '/\\') . '/log';
    if (!\is_dir($logDir)) {
        return null;
    }

    $files = \glob($logDir . '/*.txt') ?: [];
    if ($files === []) {
        return null;
    }

    \usort($files, static function ($a, $b): int {
        return (\filemtime((string) $b) ?: 0) <=> (\filemtime((string) $a) ?: 0);
    });

    foreach ($files as $logFile) {
        $content = @\file_get_contents($logFile);
        if ($content === false || $content === '') {
            continue;
        }
        $content = \str_replace(["\r\n", "\r"], "\n", $content);
        if (!\preg_match_all(
            '/<<<<<<<<([a-f0-9]{40})<<<<\n(.*?)\n<<<<\n/s',
            $content,
            $matches,
            \PREG_SET_ORDER
        )) {
            continue;
        }
        $last = $matches[\count($matches) - 1];
        $id = $last[1];
        $body = \rtrim((string) $last[2]);

        return '<<<<<<<<' . $id . "<<<<\n" . $body . "\n<<<<\n";
    }

    return null;
}

/** @deprecated Alias */
function recoveryCleanupRecoveryDebugLogs(): void
{
    recoveryCleanupRecoveryLogs();
}

function cleanupRecoveryAuxiliaryFiles(): void
{
    recoveryLog('info', 'Recovery Tool Cleanup gestartet');
    recoveryCleanupAllAuxiliaryFiles();
    recoveryLog('info', 'Recovery Tool Cleanup abgeschlossen');
}

/** @deprecated Verwende cleanupRecoveryAuxiliaryFiles() */
function cleanupRecoveryFiles(): void
{
    cleanupRecoveryAuxiliaryFiles();
    @\unlink(recoveryWcfPath('plugin-recovery-tool.php'));
}

// ============================================================================
// PACKAGE-RESSOURCEN ANALYSE FUNKTIONEN
// ============================================================================

/**
 * Findet längstes gemeinsames Präfix aus einem Array von Strings
 */
function extractCommonPrefix($items, $separator = '.') {
    if (empty($items)) {
        return '';
    }

    $prefix = $items[0];
    foreach ($items as $item) {
        while (substr($item, 0, strlen($prefix)) !== $prefix) {
            $prefix = substr($prefix, 0, -1);
            if (empty($prefix)) {
                return '';
            }
        }
    }

    // Finde letztes Trennzeichen
    $lastSep = max(strrpos($prefix, '.'), strrpos($prefix, '_'));
    if ($lastSep !== false) {
        $prefix = substr($prefix, 0, $lastSep + 1);
    }

    return $prefix;
}

/**
 * Extrahiert Namespace aus PHP-Klassenname
 */
function extractNamespace($phpClass) {
    $parts = explode('\\', $phpClass);
    if (count($parts) > 1) {
        return $parts[0] . '\\';
    }
    return '';
}

/**
 * Gibt Liste bekannter WoltLab Basis-Tabellen zurück
 */
function getBasePluginTables($wcfN) {
    return [
        "wcf{$wcfN}_package",
        "wcf{$wcfN}_user",
        "wcf{$wcfN}_user_group",
        "wcf{$wcfN}_user_group_option",
        "wcf{$wcfN}_option",
        "wcf{$wcfN}_option_category",
        "wcf{$wcfN}_language",
        "wcf{$wcfN}_language_item",
        "wcf{$wcfN}_acp_menu_item",
        "wcf{$wcfN}_cronjob",
        "wcf{$wcfN}_object_type",
        "wcf{$wcfN}_page_location",
        "wcf{$wcfN}_url_rule",
        "wcf{$wcfN}_package_installation_queue",
        "wcf{$wcfN}_package_installation_file_log",
        "wcf{$wcfN}_package_installation_plugin",
    ];
}

/**
 * Findet Datei in verschiedenen möglichen Verzeichnissen
 */
function findFileInExtractDir($extractDir, $application, $filename, $possiblePaths = []) {
    // Standard-Pfade wenn keine angegeben
    if (empty($possiblePaths)) {
        $possiblePaths = [
            $filename, // Root direkt
            '', // Root (leer bedeutet filename direkt)
            "files_{$application}/acp/{$filename}",
            "files_{$application}/{$filename}",
        ];
    }

    foreach ($possiblePaths as $path) {
        if (empty($path)) {
            $fullPath = $extractDir . '/' . $filename;
        } else {
            $fullPath = $extractDir . '/' . ltrim($path, '/');
        }
        
        // Prüfe ob es eine Datei ist (nicht ein Verzeichnis)
        if (file_exists($fullPath) && is_file($fullPath)) {
            return $fullPath;
        }
    }

    return null;
}

/**
 * Ermittelt WCF_N Nummer
 */
function detectWcfN($db, $packageIdentifier, $extractDir = null) {
    // Primär: Aus Datenbank
    for ($n = 1; $n <= 10; $n++) {
        try {
            $sql = "SELECT packageID FROM wcf{$n}_package WHERE package = ?";
            $statement = $db->prepareStatement($sql);
            $statement->execute([$packageIdentifier]);
            if ($statement->fetchArray()) {
                return $n;
            }
        } catch (\Throwable $e) {
            // Tabelle existiert nicht, weiter mit nächstem N
        }
    }

    // Fallback: Aus Tabellennamen in Install-Dateien
    if ($extractDir) {
        $packageXml = findFileInExtractDir($extractDir, '', 'package.xml');
        if ($packageXml) {
            $xml = simplexml_load_file($packageXml);
            if ($xml) {
                $instructions = $xml->xpath('//instructions[@type="install"]/instruction[@type="database"]');
                if (!empty($instructions)) {
                    $dbPath = (string)$instructions[0]['path'];
                    $dbFile = findFileInExtractDir($extractDir, '', $dbPath);
                    if ($dbFile && file_exists($dbFile)) {
                        $content = file_get_contents($dbFile);
                        // Suche nach Pattern: prefix{N}_tablename
                        if (preg_match('/[a-z]+(\d+)_/i', $content, $matches)) {
                            return (int)$matches[1];
                        }
                    }
                }
            }
        }
    }

    // Default: 1
    return 1;
}

/**
 * Parst package.xml und extrahiert Metadaten
 */
function parsePackageXml($packageXmlPath) {
    if (!file_exists($packageXmlPath) || !is_file($packageXmlPath)) {
        return null;
    }

    $xml = simplexml_load_file($packageXmlPath);
    if ($xml === false) {
        return null;
    }

    $result = [
        'package' => (string)$xml['name'],
        'application' => (string)$xml['application'] ?: '',
        'instructions' => []
    ];

    // Finde alle Instructions
    $instructions = $xml->xpath('//instructions[@type="install"]/instruction');
    foreach ($instructions as $instruction) {
        $result['instructions'][] = [
            'type' => (string)$instruction['type'],
            'application' => (string)$instruction['application'] ?: '',
            'path' => (string)$instruction['path'] ?: ''
        ];
    }

    return $result;
}

/**
 * Findet Datenbank-Tabellen aus Install-Datei
 */
function findDatabaseTables($extractDir, $packageIdentifier, $wcfN) {
    $tables = [];
    $baseTables = getBasePluginTables($wcfN);

    $packageXml = findFileInExtractDir($extractDir, '', 'package.xml');
    if (!$packageXml) {
        return $tables;
    }

    $xml = simplexml_load_file($packageXml);
    if (!$xml) {
        return $tables;
    }

    // Finde database instruction
    $instructions = $xml->xpath('//instructions[@type="install"]/instruction[@type="database"]');
    if (empty($instructions)) {
        return $tables;
    }

    $dbPath = (string)$instructions[0]['path'];
    $dbFile = findFileInExtractDir($extractDir, '', $dbPath);
    if (!$dbFile || !file_exists($dbFile)) {
        return $tables;
    }

    $content = file_get_contents($dbFile);
    // Suche nach DatabaseTable::create('tabellenname')
    if (preg_match_all("/DatabaseTable::create\(['\"]([^'\"]+)['\"]\)/", $content, $matches)) {
        foreach ($matches[1] as $tableName) {
            // Filtere Basis-Tabellen aus
            if (!in_array($tableName, $baseTables)) {
                $tables[] = $tableName;
            }
        }
    }

    return array_unique($tables);
}

/**
 * Findet Optionen aus option.xml
 */
function findOptions($extractDir, $application) {
    $options = [];
    $possiblePaths = [
        'option.xml',
        "files_{$application}/acp/option/option.xml",
        "files_{$application}/option.xml",
    ];

    $optionFile = findFileInExtractDir($extractDir, $application, 'option.xml', $possiblePaths);
    if (!$optionFile) {
        return ['prefix' => '', 'count' => 0, 'items' => []];
    }

    $xml = simplexml_load_file($optionFile);
    if (!$xml) {
        return ['prefix' => '', 'count' => 0, 'items' => []];
    }

    $optionNames = [];
    foreach ($xml->xpath('//option[@name]') as $option) {
        $name = (string)$option['name'];
        $optionNames[] = $name;
        $options[] = $name;
    }

    $prefix = extractCommonPrefix($optionNames, '_');
    return [
        'prefix' => $prefix,
        'count' => count($options),
        'items' => $options
    ];
}

/**
 * Findet User Group Options (Permissions) aus userGroupOption.xml
 */
function findUserGroupOptions($extractDir, $application) {
    $options = [];
    $possiblePaths = [
        'userGroupOption.xml',
        "files_{$application}/acp/userGroupOption/userGroupOption.xml",
        "files_{$application}/userGroupOption.xml",
    ];

    $optionFile = findFileInExtractDir($extractDir, $application, 'userGroupOption.xml', $possiblePaths);
    if (!$optionFile) {
        return ['prefix' => '', 'count' => 0, 'items' => []];
    }

    $xml = simplexml_load_file($optionFile);
    if (!$xml) {
        return ['prefix' => '', 'count' => 0, 'items' => []];
    }

    $optionNames = [];
    foreach ($xml->xpath('//option[@name]') as $option) {
        $name = (string)$option['name'];
        $optionNames[] = $name;
        $options[] = $name;
    }

    $prefix = extractCommonPrefix($optionNames, '.');
    return [
        'prefix' => $prefix,
        'count' => count($options),
        'items' => $options
    ];
}

/**
 * Findet Cronjobs aus package.xml oder separaten XML-Dateien
 */
function findCronjobs($extractDir, $packageXmlPath) {
    $cronjobs = [];
    $classes = [];

    // Suche in package.xml
    if (file_exists($packageXmlPath) && is_file($packageXmlPath)) {
        $xml = simplexml_load_file($packageXmlPath);
        if ($xml) {
            foreach ($xml->xpath('//cronjob[@className]') as $cronjob) {
                $className = (string)$cronjob['className'];
                $classes[] = $className;
            }
        }
    }

    // Suche in separaten XML-Dateien
    $cronjobDir = $extractDir . '/acp/cronjob';
    if (is_dir($cronjobDir)) {
        $files = glob($cronjobDir . '/*.xml');
        foreach ($files as $file) {
            if (is_file($file)) {
                $xml = simplexml_load_file($file);
                if ($xml) {
                    foreach ($xml->xpath('//cronjob[@className]') as $cronjob) {
                        $className = (string)$cronjob['className'];
                        $classes[] = $className;
                    }
                }
            }
        }
    }

    $namespace = '';
    if (!empty($classes)) {
        $namespace = extractNamespace($classes[0]);
    }

    return [
        'namespace' => $namespace,
        'count' => count($classes),
        'classes' => $classes
    ];
}

/**
 * Findet ACP-Menü-Einträge aus acpMenu.xml
 */
function findAcpMenuItems($extractDir, $application) {
    $items = [];
    $possiblePaths = [
        'acpMenu.xml',
        "files_{$application}/acp/menu/acpMenu.xml",
        "files_{$application}/acpMenu.xml",
    ];

    $menuFile = findFileInExtractDir($extractDir, $application, 'acpMenu.xml', $possiblePaths);
    if (!$menuFile) {
        return ['prefix' => '', 'count' => 0, 'items' => []];
    }

    $xml = simplexml_load_file($menuFile);
    if (!$xml) {
        return ['prefix' => '', 'count' => 0, 'items' => []];
    }

    $menuNames = [];
    foreach ($xml->xpath('//acpmenuitem[@name]') as $menuItem) {
        $name = (string)$menuItem['name'];
        $menuNames[] = $name;
        $items[] = $name;
    }

    $prefix = extractCommonPrefix($menuNames, '.');
    return [
        'prefix' => $prefix,
        'count' => count($items),
        'items' => $items
    ];
}

/**
 * Findet Sprachvariablen aus language/*.xml
 */
function findLanguageItems($extractDir, $application) {
    $items = [];
    $possiblePaths = [
        'language',
        "files_{$application}/language",
    ];

    $languageDir = null;
    foreach ($possiblePaths as $path) {
        $fullPath = $extractDir . '/' . ltrim($path, '/');
        if (is_dir($fullPath)) {
            $languageDir = $fullPath;
            break;
        }
    }

    if (!$languageDir) {
        return ['prefix' => '', 'count' => 0, 'items' => []];
    }

    $xmlFiles = glob($languageDir . '/*.xml');
    $itemNames = [];
    foreach ($xmlFiles as $xmlFile) {
        if (is_file($xmlFile)) {
            $xml = simplexml_load_file($xmlFile);
            if ($xml) {
                foreach ($xml->xpath('//item[@name]') as $item) {
                    $name = (string)$item['name'];
                    if (!in_array($name, $itemNames)) {
                        $itemNames[] = $name;
                        $items[] = $name;
                    }
                }
            }
        }
    }

    $prefix = extractCommonPrefix($itemNames, '.');
    return [
        'prefix' => $prefix,
        'count' => count($items),
        'items' => $items
    ];
}

/**
 * Findet Objekttypen aus objectType.xml
 */
function findObjectTypes($extractDir, $application) {
    $types = [];
    $possiblePaths = [
        'objectType.xml',
        "files_{$application}/acp/objectType/objectType.xml",
        "files_{$application}/objectType.xml",
    ];

    $typeFile = findFileInExtractDir($extractDir, $application, 'objectType.xml', $possiblePaths);
    if (!$typeFile) {
        return ['prefix' => '', 'count' => 0, 'items' => []];
    }

    $xml = simplexml_load_file($typeFile);
    if (!$xml) {
        return ['prefix' => '', 'count' => 0, 'items' => []];
    }

    $typeNames = [];
    foreach ($xml->xpath('//type[@name]') as $type) {
        $name = (string)$type['name'];
        $typeNames[] = $name;
        $types[] = $name;
    }

    $prefix = extractCommonPrefix($typeNames, '.');
    return [
        'prefix' => $prefix,
        'count' => count($types),
        'items' => $types
    ];
}

/**
 * Findet Page Locations aus pageLocation.xml
 */
function findPageLocations($extractDir, $application) {
    $locations = [];
    $possiblePaths = [
        'pageLocation.xml',
        "files_{$application}/acp/page/pageLocation.xml",
        "files_{$application}/pageLocation.xml",
    ];

    $locationFile = findFileInExtractDir($extractDir, $application, 'pageLocation.xml', $possiblePaths);
    if (!$locationFile) {
        return ['count' => 0, 'items' => []];
    }

    $xml = simplexml_load_file($locationFile);
    if (!$xml) {
        return ['count' => 0, 'items' => []];
    }

    foreach ($xml->xpath('//pagelocation[@identifier]') as $location) {
        $identifier = (string)$location['identifier'];
        $locations[] = $identifier;
    }

    return [
        'count' => count($locations),
        'items' => $locations
    ];
}

/**
 * Findet URL Rules aus urlRule.xml
 */
function findUrlRules($extractDir, $application) {
    $rules = [];
    $possiblePaths = [
        'urlRule.xml',
        "files_{$application}/acp/page/urlRule.xml",
        "files_{$application}/urlRule.xml",
    ];

    $ruleFile = findFileInExtractDir($extractDir, $application, 'urlRule.xml', $possiblePaths);
    if (!$ruleFile) {
        return ['count' => 0, 'items' => []];
    }

    $xml = simplexml_load_file($ruleFile);
    if (!$xml) {
        return ['count' => 0, 'items' => []];
    }

    foreach ($xml->xpath('//pattern') as $pattern) {
        $patternText = trim((string)$pattern);
        if (!empty($patternText)) {
            $rules[] = $patternText;
        }
    }

    return [
        'count' => count($rules),
        'items' => $rules
    ];
}

/**
 * Hauptfunktion: Analysiert Package und identifiziert alle Ressourcen
 */
function analyzePackageResources($extractDir, $packageIdentifier, $db) {
    $resources = [
        'tables' => [],
        'options' => ['prefix' => '', 'count' => 0, 'items' => []],
        'permissions' => ['prefix' => '', 'count' => 0, 'items' => []],
        'cronjobs' => ['namespace' => '', 'count' => 0, 'classes' => []],
        'acpMenu' => ['prefix' => '', 'count' => 0, 'items' => []],
        'language' => ['prefix' => '', 'count' => 0, 'items' => []],
        'objectTypes' => ['prefix' => '', 'count' => 0, 'items' => []],
        'pageLocations' => ['count' => 0, 'items' => []],
        'urlRules' => ['count' => 0, 'items' => []]
    ];

    if (!is_dir($extractDir)) {
        return $resources;
    }

    // Parse package.xml
    $packageXml = findFileInExtractDir($extractDir, '', 'package.xml');
    if (!$packageXml) {
        return $resources;
    }

    $packageData = parsePackageXml($packageXml);
    if (!$packageData) {
        return $resources;
    }

    $application = $packageData['application'] ?: '';
    $wcfN = detectWcfN($db, $packageIdentifier, $extractDir);

    // Finde alle Ressourcen
    $resources['tables'] = findDatabaseTables($extractDir, $packageIdentifier, $wcfN);
    $resources['options'] = findOptions($extractDir, $application);
    $resources['permissions'] = findUserGroupOptions($extractDir, $application);
    $resources['cronjobs'] = findCronjobs($extractDir, $packageXml);
    $resources['acpMenu'] = findAcpMenuItems($extractDir, $application);
    $resources['language'] = findLanguageItems($extractDir, $application);
    $resources['objectTypes'] = findObjectTypes($extractDir, $application);
    $resources['pageLocations'] = findPageLocations($extractDir, $application);
    $resources['urlRules'] = findUrlRules($extractDir, $application);
    $resources['wcfN'] = $wcfN;

    return $resources;
}

/**
 * Generiert SQL-Statements für Cleanup
 */
function generateCleanupSql($resources, $wcfN) {
    $sql = "-- WoltLab Plugin Cleanup SQL\n";
    $sql .= "-- Generated automatically from package analysis\n";
    $sql .= "-- WCF_N: {$wcfN}\n\n";

    // Tabellen
    if (!empty($resources['tables'])) {
        $sql .= "-- Tabellen\n";
        foreach ($resources['tables'] as $table) {
            $sql .= "DROP TABLE IF EXISTS `" . addslashes($table) . "`;\n";
        }
        $sql .= "\n";
    }

    // Optionen
    if (!empty($resources['options']['prefix'])) {
        $sql .= "-- Optionen\n";
        $prefix = addslashes($resources['options']['prefix']);
        $sql .= "DELETE FROM wcf{$wcfN}_option WHERE optionName LIKE '{$prefix}%';\n\n";
    }

    // Permissions
    if (!empty($resources['permissions']['prefix'])) {
        $sql .= "-- Permissions (User Group Options)\n";
        $prefix = addslashes($resources['permissions']['prefix']);
        $sql .= "DELETE FROM wcf{$wcfN}_user_group_option WHERE optionName LIKE '{$prefix}%';\n\n";
    }

    // Cronjobs
    if (!empty($resources['cronjobs']['namespace'])) {
        $sql .= "-- Cronjobs\n";
        $namespace = addslashes($resources['cronjobs']['namespace']);
        $sql .= "DELETE FROM wcf{$wcfN}_cronjob WHERE className LIKE '{$namespace}%';\n\n";
    }

    // ACP-Menü
    if (!empty($resources['acpMenu']['prefix'])) {
        $sql .= "-- ACP-Menü-Einträge\n";
        $prefix = addslashes($resources['acpMenu']['prefix']);
        $sql .= "DELETE FROM wcf{$wcfN}_acp_menu_item WHERE menuItem LIKE '{$prefix}%';\n\n";
    }

    // Sprachvariablen
    if (!empty($resources['language']['prefix'])) {
        $sql .= "-- Sprachvariablen\n";
        $prefix = addslashes($resources['language']['prefix']);
        $sql .= "DELETE FROM wcf{$wcfN}_language_item WHERE languageItem LIKE '{$prefix}%';\n\n";
    }

    // Objekttypen
    if (!empty($resources['objectTypes']['prefix'])) {
        $sql .= "-- Objekttypen\n";
        $prefix = addslashes($resources['objectTypes']['prefix']);
        $sql .= "DELETE FROM wcf{$wcfN}_object_type WHERE objectType LIKE '{$prefix}%';\n\n";
    }

    // Page Locations
    if (!empty($resources['pageLocations']['items'])) {
        $sql .= "-- Page Locations\n";
        foreach ($resources['pageLocations']['items'] as $identifier) {
            $id = addslashes($identifier);
            $sql .= "DELETE FROM wcf{$wcfN}_page_location WHERE identifier = '{$id}';\n";
        }
        $sql .= "\n";
    }

    // URL Rules
    if (!empty($resources['urlRules']['items'])) {
        $sql .= "-- URL Rules\n";
        foreach ($resources['urlRules']['items'] as $pattern) {
            $pat = addslashes($pattern);
            $sql .= "DELETE FROM wcf{$wcfN}_url_rule WHERE pattern = '{$pat}';\n";
        }
        $sql .= "\n";
    }

    return $sql;
}

/**
 * Zeigt Vorschau der gefundenen Ressourcen
 */
function displayResourcePreview($resources, $wcfN, $packageIdentifier) {
    echo '<p class="info"><strong>Gefundene Ressourcen aus Package-Datei:</strong><br>';
    echo '<small>WCF_N: ' . htmlspecialchars($wcfN) . '</small><br><br>';

    $hasResources = false;

    // Tabellen
    if (!empty($resources['tables'])) {
        $hasResources = true;
        echo '<strong>Datenbank-Tabellen (' . count($resources['tables']) . '):</strong><br>';
        echo '<ul>';
        foreach ($resources['tables'] as $table) {
            echo '<li><code>' . htmlspecialchars($table) . '</code></li>';
        }
        echo '</ul><br>';
    }

    // Optionen
    if (!empty($resources['options']['prefix'])) {
        $hasResources = true;
        echo '<strong>Optionen (' . $resources['options']['count'] . '):</strong> ';
        echo 'Präfix: <code>' . htmlspecialchars($resources['options']['prefix']) . '%</code><br><br>';
    }

    // Permissions
    if (!empty($resources['permissions']['prefix'])) {
        $hasResources = true;
        echo '<strong>Permissions (' . $resources['permissions']['count'] . '):</strong> ';
        echo 'Präfix: <code>' . htmlspecialchars($resources['permissions']['prefix']) . '%</code><br><br>';
    }

    // Cronjobs
    if (!empty($resources['cronjobs']['namespace'])) {
        $hasResources = true;
        echo '<strong>Cronjobs (' . $resources['cronjobs']['count'] . '):</strong> ';
        echo 'Namespace: <code>' . htmlspecialchars($resources['cronjobs']['namespace']) . '%</code><br><br>';
    }

    // ACP-Menü
    if (!empty($resources['acpMenu']['prefix'])) {
        $hasResources = true;
        echo '<strong>ACP-Menü-Einträge (' . $resources['acpMenu']['count'] . '):</strong> ';
        echo 'Präfix: <code>' . htmlspecialchars($resources['acpMenu']['prefix']) . '%</code><br><br>';
    }

    // Sprachvariablen
    if (!empty($resources['language']['prefix'])) {
        $hasResources = true;
        echo '<strong>Sprachvariablen (' . $resources['language']['count'] . '):</strong> ';
        echo 'Präfix: <code>' . htmlspecialchars($resources['language']['prefix']) . '%</code><br><br>';
    }

    // Objekttypen
    if (!empty($resources['objectTypes']['prefix'])) {
        $hasResources = true;
        echo '<strong>Objekttypen (' . $resources['objectTypes']['count'] . '):</strong> ';
        echo 'Präfix: <code>' . htmlspecialchars($resources['objectTypes']['prefix']) . '%</code><br><br>';
    }

    // Page Locations
    if (!empty($resources['pageLocations']['items'])) {
        $hasResources = true;
        echo '<strong>Page Locations (' . $resources['pageLocations']['count'] . '):</strong><br>';
        echo '<ul>';
        foreach ($resources['pageLocations']['items'] as $identifier) {
            echo '<li><code>' . htmlspecialchars($identifier) . '</code></li>';
        }
        echo '</ul><br>';
    }

    // URL Rules
    if (!empty($resources['urlRules']['items'])) {
        $hasResources = true;
        echo '<strong>URL Rules (' . $resources['urlRules']['count'] . '):</strong><br>';
        echo '<ul>';
        foreach ($resources['urlRules']['items'] as $pattern) {
            echo '<li><code>' . htmlspecialchars($pattern) . '</code></li>';
        }
        echo '</ul><br>';
    }

    if (!$hasResources) {
        echo '<em>Keine zusätzlichen Ressourcen in Package-Datei gefunden.</em><br>';
    }

    echo '</div>';
}

$action = (!empty($_GET['action'])) ? (string) $_GET['action'] : '';
if ($action === 'pip-preview') {
    if (!$isAuthenticated) {
        recoveryJsonResponse(['ok' => false, 'error' => 'Nicht authentifiziert.'], 403);
    }
    try {
        $db = recoveryBootstrapDatabase();
        $packageID = (int) ($_GET['package_id'] ?? 0);
        $tableName = \str_replace('`', '', (string) ($_GET['table'] ?? ''));
        $preview = recoveryFetchPackageIdTablePreview($db, WCF_N, $tableName, $packageID);
        if (isset($preview['error'])) {
            recoveryJsonResponse(['ok' => false, 'error' => $preview['error']], 500);
        }
        recoveryJsonResponse(['ok' => true] + $preview);
    } catch (\Throwable $e) {
        recoveryJsonResponse(['ok' => false, 'error' => $e->getMessage()], 500);
    }
}

$mode = recoveryResolveRequestMode();
$recoveryBootstrapError = null;
$recoveryDb = null;

if ($isAuthenticated) {
    recoverySessionBindAuthToken($authHash);
    try {
        $recoveryDb = recoveryBootstrapDatabase();
        recoveryMaybeRedirectUninstallAnalyse($authHash);
        recoveryMaybeRedirectWizardDisplayRebuild($authHash, $recoveryDb);
    } catch (\Throwable $e) {
        $recoveryBootstrapError = $e;
    }
}

// #region agent log
recoveryAgentDebugLog('H5', 'tool:main', 'pre_render', [
    'authenticated' => $isAuthenticated,
    'bootstrapError' => $recoveryBootstrapError !== null ? \get_class($recoveryBootstrapError) : null,
    'mode' => $mode,
]);
// #endregion

[$recoveryPageTitle, $recoveryPageDescription] = recoveryResolveModePageHeader($mode);
$recoveryUnifiedModes = [
    RECOVERY_MODE_SELECTION,
    RECOVERY_MODE_SYSTEM_CHECK,
    RECOVERY_MODE_DIRECTORY_STRUCTURE,
    RECOVERY_MODE_RECOVERY_WIZARD,
    RECOVERY_MODE_PLUGIN_UNINSTALL,
];
$recoveryUsesUnifiedLayout = \in_array($mode, $recoveryUnifiedModes, true);
$recoveryBodyClass = match ($mode) {
    RECOVERY_MODE_RECOVERY_WIZARD => 'recovery-page-wizard recovery-page-unified',
    RECOVERY_MODE_PLUGIN_UNINSTALL => 'recovery-page-plugin-uninstall recovery-page-unified',
    default => $recoveryUsesUnifiedLayout ? 'recovery-page-unified' : '',
};
$recoveryContentTitle = recoveryUsesNativeAcpUi() ? $recoveryPageTitle : ($recoveryUsesUnifiedLayout ? '' : $recoveryPageTitle);
$recoveryContentDescription = recoveryUsesNativeAcpUi()
    ? $recoveryPageDescription
    : ($recoveryUsesUnifiedLayout ? '' : $recoveryPageDescription);
$recoveryBodyClass .= recoveryUsesNativeAcpUi() ? ' recovery-uses-acp-ui' : '';
recoveryRenderPageStart(
    'Plugin Recovery Tool — ' . $recoveryPageTitle,
    $recoveryContentTitle,
    null,
    null,
    $recoveryContentDescription,
    $recoveryBodyClass
);

if (!$isAuthenticated) {
    echo '<p class="error"><strong>Nicht authentifiziert.</strong> Bitte über <code>plugin-recovery-tool.php</code> (Stub) starten.</p>';
    recoveryRenderPageEnd();
    exit;
}

// Ab hier ist der User authentifiziert

if ($recoveryBootstrapError !== null) {
    echo '<p class="error"><strong>Bootstrap-Fehler:</strong> '
        . \nl2br(\htmlspecialchars(recoveryFormatUserError($recoveryBootstrapError))) . '</p>';
    recoveryRenderExceptionDetails($recoveryBootstrapError);
    recoveryRenderPageEnd();
    exit;
}

$recoveryBaseUrl = recoveryGetSiteBaseUrl();
try {
    $db = \wcf\system\WCF::getDB();
} catch (\Throwable $e) {
    echo '<p class="error"><strong>Datenbank nicht verfügbar:</strong> '
        . \nl2br(\htmlspecialchars(recoveryFormatUserError($e))) . '</div>';
    recoveryRenderExceptionDetails($e);
    recoveryRenderPageEnd();
    exit;
}
$wcfDirMain = \rtrim((string) WCF_DIR, '/\\') . '/';
$emergencyAcpResult = null;
$emergencyAcpLog = [];

if (
    $mode === RECOVERY_MODE_SELECTION
    && ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST'
    && !empty($_POST['emergency_acp_fix'])
) {
    recoveryEnsureSession();
    try {
        $emergencyAcpResult = recoveryEmergencyFixAcpClassNotFound($wcfDirMain, $db, WCF_N, $emergencyAcpLog);
        recoverySessionSetEmergencyFixed($authHash, $emergencyAcpResult, $emergencyAcpLog);
        recoverySessionSetFlash($authHash, 'acp_fix_result', [
            'result' => $emergencyAcpResult,
            'log' => $emergencyAcpLog,
        ]);
        if (\session_status() === PHP_SESSION_ACTIVE) {
            \session_write_close();
        }
        \header(
            'Location: ' . recoveryBuildHomeUrl($authHash, [
                'mode' => RECOVERY_MODE_SELECTION,
                'acp_fixed' => '1',
                'recovery_snack' => 'acp_ok',
            ]),
            true,
            303
        );
        exit;
    } catch (\Throwable $e) {
        $emergencyAcpResult = [
            'error' => recoveryFormatUserError($e),
            'bootstrapNeutralized' => [],
            'dbEventListenersDeleted' => 0,
            'cacheDeleted' => 0,
            'logClasses' => [],
        ];
    }
}

$emergencyFixedSession = recoverySessionGetEmergencyFixed($authHash);
$acpFixOutcome = ($mode === RECOVERY_MODE_SELECTION) ? recoveryLoadAcpFixOutcome($authHash) : null;
if ($acpFixOutcome === null && $emergencyAcpResult !== null && empty($emergencyAcpResult['error'])) {
    $acpFixOutcome = ['result' => $emergencyAcpResult, 'log' => $emergencyAcpLog];
}

recoveryRenderPageNavigation($mode, $authHash, $recoveryBaseUrl);

// Modus-Routing (lib/Recovery/Modes/*.php)
require __DIR__ . '/lib/Recovery/router.php';

recoveryRenderPageEnd();
