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
 * @version 1.8.2
 * @requires PHP >= 8.1 (wie WoltLab Suite 6.x; kein künstliches 8.3-Minimum)
 *
 * Eine Datei: ins WoltLab-Hauptverzeichnis legen (neben global.php).
 * Universelles Recovery nach Stressoren wie kaputter Installation: DB gemäß WoltLab-PIP-Zuordnung,
 * Cache/Pfade aller Apps, Option-Konstanten-Fallback für sämtliche Plugins (nicht nur einzelne Pakete).
 */

// ============================================================================
// KONFIGURATION
// ============================================================================

define('RECOVERY_VERSION', '1.8.2');
define('RECOVERY_BEER_CSS', 'https://cdn.jsdelivr.net/npm/beercss@4.0.21/dist/cdn/beer.min.css');
define('RECOVERY_BEER_JS', 'https://cdn.jsdelivr.net/npm/beercss@4.0.21/dist/cdn/beer.min.js');
define('RECOVERY_BEER_COLORS_JS', 'https://cdn.jsdelivr.net/npm/material-dynamic-colors@1.1.4/dist/cdn/material-dynamic-colors.min.js');
define('RECOVERY_DEBUG_LOG_PREFIX', 'recovery-tool-');
define('RECOVERY_MIN_PHP_VERSION', '8.1.0');

if (\PHP_VERSION_ID < 80100) {
    \header('Content-Type: text/html; charset=utf-8');
    \http_response_code(500);
    echo '<!DOCTYPE html><html lang="de"><head><meta charset="utf-8"><title>Recovery Tool</title></head><body>';
    echo '<h1>PHP-Version zu alt</h1>';
    echo '<p>Dieses Recovery Tool benötigt mindestens <strong>PHP 8.1</strong> (wie WoltLab Suite 6.x).</p>';
    echo '<p>Aktuell: <code>' . \htmlspecialchars(\PHP_VERSION) . '</code></p>';
    echo '<p>Bitte PHP beim Hoster auf 8.1 oder neuer stellen.</p>';
    echo '</body></html>';
    exit;
}

// #region agent log
/**
 * NDJSON-Debug: optional RECOVERY_AGENT_LOG_PATH, sonst WoltLab log/ (recovery-tool-YYYY-MM-DD.ndjson).
 * Vor WCF-Auflösung: __DIR__/log/, danach sys_get_temp_dir().
 */
function recoveryAgentDebugLogBasename(): string
{
    return RECOVERY_DEBUG_LOG_PREFIX . \date('Y-m-d') . '.ndjson';
}

function recoveryEnsureLogDirectory(string $dir): bool
{
    $dir = \rtrim(\str_replace('\\', '/', $dir), '/') . '/';
    if ($dir === '/') {
        return false;
    }
    if (\is_dir($dir)) {
        return @\is_writable($dir);
    }

    return @\mkdir($dir, 0775, true) || \is_dir($dir);
}

/**
 * WCF-Hauptverzeichnis nur für Log-Pfad (ohne Exception, ohne Bootstrap).
 */
function recoveryResolveWcfDirForLogging(): ?string
{
    static $cached = false;
    static $result = null;
    if ($cached) {
        return $result;
    }
    $cached = true;

    if (\defined('WCF_DIR')) {
        return $result = \rtrim((string) \constant('WCF_DIR'), '/\\') . '/';
    }

    foreach ([__DIR__, \dirname(__DIR__), \dirname(__DIR__, 2)] as $dir) {
        if (\is_file($dir . '/global.php') && \is_file($dir . '/config.inc.php')) {
            return $result = \rtrim($dir, '/') . '/';
        }
    }

    return $result = null;
}

function recoveryAgentDebugLogPath(): string
{
    static $resolved = null;
    if ($resolved !== null) {
        return $resolved;
    }

    $fromEnv = \getenv('RECOVERY_AGENT_LOG_PATH');
    if (\is_string($fromEnv) && $fromEnv !== '') {
        return $resolved = $fromEnv;
    }

    $basename = recoveryAgentDebugLogBasename();

    $wcfDir = recoveryResolveWcfDirForLogging();
    if ($wcfDir !== null) {
        $logDir = \rtrim($wcfDir, '/\\') . '/log/';
        if (recoveryEnsureLogDirectory($logDir)) {
            return $resolved = $logDir . $basename;
        }
    }

    $besideLogDir = __DIR__ . '/log/';
    if (recoveryEnsureLogDirectory($besideLogDir)) {
        return $resolved = $besideLogDir . $basename;
    }

    $tmp = \rtrim((string) \sys_get_temp_dir(), \DIRECTORY_SEPARATOR)
        . \DIRECTORY_SEPARATOR . RECOVERY_DEBUG_LOG_PREFIX
        . \substr(\sha1(__DIR__), 0, 16) . '_' . \date('Y-m-d') . '.ndjson';

    return $resolved = $tmp;
}

function recoveryAgentExposeDebugHeaders(): void
{
    static $sent = false;
    if ($sent || \headers_sent()) {
        return;
    }
    $sent = true;
    \header('X-WFL-Recovery-Agent-Log-B64: ' . \base64_encode(recoveryAgentDebugLogPath()));
}

/** @internal NDJSON-Zeilen für gezieltes Debugging (Hypothesen / Fatals). */
function recoveryAgentDebugLog(string $hypothesisId, string $location, string $message, array $data = []): void
{
    recoveryAgentExposeDebugHeaders();
    $path = recoveryAgentDebugLogPath();
    $payload = [
        'sessionId' => '54d5f7',
        'hypothesisId' => $hypothesisId,
        'location' => $location,
        'message' => $message,
        'data' => $data,
        'timestamp' => (int) \round(\microtime(true) * 1000),
        'runId' => $_SERVER['RECOVERY_DEBUG_RUN'] ?? 'pre-fix',
    ];
    $line = \json_encode($payload, \JSON_UNESCAPED_UNICODE | \JSON_INVALID_UTF8_SUBSTITUTE);
    if ($line === false) {
        @\error_log('[wfl-recovery-agent] ndjson_encode_failed');

        return;
    }
    $wrote = @\file_put_contents($path, $line . "\n", \FILE_APPEND | \LOCK_EX);
    if ($wrote === false) {
        @\error_log('[wfl-recovery-agent] ndjson_write_failed path=' . $path);
    }
}

\register_shutdown_function(static function (): void {
    $path = recoveryAgentDebugLogPath();
    $e = \error_get_last();
    if ($e === null) {
        return;
    }
    $fatalTypes = [\E_ERROR, \E_PARSE, \E_CORE_ERROR, \E_COMPILE_ERROR];
    if (!\in_array((int) $e['type'], $fatalTypes, true)) {
        return;
    }
    @\error_log('[wfl-recovery-agent] fatal type=' . $e['type'] . ' msg=' . $e['message'] . ' @ ' . $e['file'] . ':' . $e['line']);
    $line = \json_encode([
        'sessionId' => '54d5f7',
        'hypothesisId' => 'H-FATAL',
        'location' => 'shutdown',
        'message' => 'php_fatal',
        'data' => [
            'type' => $e['type'],
            'message' => $e['message'],
            'file' => $e['file'],
            'line' => $e['line'],
        ],
        'timestamp' => (int) \round(\microtime(true) * 1000),
        'runId' => $_SERVER['RECOVERY_DEBUG_RUN'] ?? 'pre-fix',
    ], \JSON_UNESCAPED_UNICODE | \JSON_INVALID_UTF8_SUBSTITUTE);
    if ($line !== false) {
        @\file_put_contents($path, $line . "\n", \FILE_APPEND | \LOCK_EX);
    }
});
recoveryAgentDebugLog('H1', 'tool:boot', 'php_version_gate_passed', [
    'phpVersion' => \PHP_VERSION,
    'sapi' => \PHP_SAPI,
    'debugLogPath' => recoveryAgentDebugLogPath(),
]);
@\error_log('[wfl-recovery-agent] ndjson_path=' . recoveryAgentDebugLogPath());
\set_exception_handler(static function (\Throwable $e): void {
    recoveryAgentDebugLog('H-EXCEPTION', 'tool:uncaught', 'uncaught_exception', [
        'class' => \get_class($e),
        'message' => $e->getMessage(),
        'file' => \basename($e->getFile()),
        'line' => $e->getLine(),
    ]);
    @\error_log('[wfl-recovery-agent] uncaught ' . \get_class($e) . ': ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
    $logHint = 'log/' . recoveryAgentDebugLogBasename();
    if (!\headers_sent()) {
        \http_response_code(500);
        \header('Content-Type: text/html; charset=utf-8');
        echo '<!DOCTYPE html><html lang="de"><head><meta charset="utf-8"><title>Recovery Tool – Fehler</title></head><body>';
        echo '<h1>Recovery Tool – interner Fehler</h1>';
        echo '<p>Es ist ein unerwarteter Fehler aufgetreten. Details stehen in der Debug-Logdatei im WoltLab-Verzeichnis:</p>';
        echo '<p><code>' . \htmlspecialchars($logHint, \ENT_QUOTES, 'UTF-8') . '</code></p>';
        echo '<p>Zusätzlich: Host-<code>error_log</code> nach „wfl-recovery-agent“ durchsuchen.</p>';
        echo '</body></html>';
    }
    exit(1);
});
// #endregion

define('RECOVERY_MODE_SELECTION', 0);
define('RECOVERY_MODE_ACP_REPAIR', 1);
define('RECOVERY_MODE_PLUGIN_UNINSTALL', 2);
define('RECOVERY_MODE_USER_MANAGEMENT', 3);
define('RECOVERY_MODE_CACHE_CLEAR', 4);
define('RECOVERY_MODE_PACKAGE_LIST_REPAIR', 5);
define('RECOVERY_MODE_PACKAGE_FILE_REPAIR', 6);
define('RECOVERY_MODE_RECOVERY_WIZARD', 7);
define('RECOVERY_MODE_SYSTEM_CHECK', 8);
define('RECOVERY_MODE_BACKUP_GUIDE', 9);
define('RECOVERY_MODE_DIRECTORY_STRUCTURE', 10);

/** Stack-Traces nur bei true oder ?debug=1 (mit gültigem Auth-Token). */
define('RECOVERY_ENABLE_DEBUG', false);
define('RECOVERY_PACKAGE_ID_MAX_LEN', 191);
define('RECOVERY_PACKAGE_ID_PATTERN', '/^[a-zA-Z0-9._-]+$/');
define('RECOVERY_MAX_UPLOAD_BYTES', 104857600); // 100 MiB

$authHash = '';
$authFilename = 'plugin-recovery-auth.php';

// ============================================================================
// RECOVERY CORE (Minimal-Bootstrap + generische Plugin-Bereinigung)
// ============================================================================

function recoveryResolveWcfDir(): string
{
    foreach ([__DIR__, \dirname(__DIR__), \dirname(__DIR__, 2)] as $dir) {
        if (\is_file($dir . '/global.php') && \is_file($dir . '/config.inc.php')) {
            return \rtrim($dir, '/') . '/';
        }
    }

    throw new \RuntimeException(
        'WoltLab nicht gefunden. Legen Sie nur plugin-recovery-tool.php ins Hauptverzeichnis (neben global.php).'
    );
}

/**
 * @return \wcf\system\database\Database
 */
function recoveryBootstrapDatabase()
{
    if (!\defined('WCF_DIR')) {
        \define('WCF_DIR', recoveryResolveWcfDir());
    }

    require_once WCF_DIR . 'lib/system/api/autoload.php';

    // core.functions.php registriert WCF::destruct → CacheHandler ohne vollständige Konstanten.
    if (!\defined('NO_IMPORTS')) {
        \define('NO_IMPORTS', true);
    }

    require_once WCF_DIR . 'lib/system/WCF.class.php';

    // Vor options.inc.php: verhindert Aufruf von wcf\getRequestId() ohne core.functions.php (NO_IMPORTS).
    if (!\defined('ENABLE_PRODUCTION_DEBUG_MODE')) {
        \define('ENABLE_PRODUCTION_DEBUG_MODE', false);
    }

    recoveryDefineMinimalWcfConstants();
    recoveryDefineMinimalWcfFunctions();

    // Nur WoltLab-Autoloader – kein global.php / kein WCF-Vollbootstrap.
    if (!\defined('RECOVERY_WCF_AUTOLOAD')) {
        \define('RECOVERY_WCF_AUTOLOAD', true);
        \spl_autoload_register([\wcf\system\WCF::class, 'autoload'], true, true);
    }

    $dbHost = $dbUser = $dbPassword = $dbName = '';
    $dbPort = 0;
    $defaultDriverOptions = [];
    require WCF_DIR . 'config.inc.php';

    $db = new \wcf\system\database\MySQLDatabase(
        $dbHost,
        $dbUser,
        $dbPassword,
        $dbName,
        $dbPort,
        false,
        false,
        $defaultDriverOptions
    );

    if (!\defined('WCF_N')) {
        \define('WCF_N', recoveryDetectWcfN($db));
    }

    if (!\defined('PACKAGE_ID')) {
        \define('PACKAGE_ID', 1);
    }

    recoveryInjectDatabaseIntoWcf($db);

    return $db;
}

function recoveryDetectWcfN(\wcf\system\database\Database $db): int
{
    for ($n = 1; $n <= 10; $n++) {
        try {
            $sql = "SELECT packageID FROM wcf{$n}_package WHERE package = ?";
            $statement = $db->prepareStatement($sql);
            $statement->execute(['com.woltlab.wcf']);
            if ($statement->fetchArray()) {
                return $n;
            }
        } catch (\Throwable $ignored) {
        }
    }

    return 1;
}

function recoveryInjectDatabaseIntoWcf(\wcf\system\database\Database $db): void
{
    $reflection = new \ReflectionClass(\wcf\system\WCF::class);
    $property = $reflection->getProperty('dbObj');
    $property->setAccessible(true);
    $property->setValue(null, $db);
}

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

    $uploadDir = __DIR__ . '/uploads';
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
    $uploadDir ??= __DIR__ . '/uploads';
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

function recoveryBuildModeUrl(int $mode, string $authHash, array $params = []): string
{
    $query = \array_merge(['mode' => $mode, 't' => $authHash], $params);

    return 'plugin-recovery-tool.php?' . \http_build_query($query);
}

function recoveryBuildHomeUrl(string $authHash, array $params = []): string
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

function recoveryLoadPackageContext(string $authHash): ?array
{
    recoveryEnsureSession();
    $ctx = $_SESSION['recovery_pkg'][$authHash] ?? null;
    if (!$ctx || (\time() - (int) ($ctx['savedAt'] ?? 0)) > 7200) {
        return null;
    }

    if (!empty($ctx['extractDir'])) {
        $uploadBase = \realpath(__DIR__ . '/uploads');
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
    echo '<div class="alert alert-error"><strong>Upload fehlgeschlagen:</strong> '
        . 'Die Anfrage wurde vom Server abgeschnitten (post_max_size / upload_max_filesize). '
        . 'Bitte kleinere Datei wählen oder die PHP-Limits erhöhen.</div>';
}

function recoveryRenderProcessingError(\Throwable $e): void
{
    echo '<div class="alert alert-error"><strong>Fehler bei der Verarbeitung:</strong> '
        . \nl2br(\htmlspecialchars(recoveryFormatUserError($e))) . '</div>';
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

    function buildSnackbar(message, type) {
        var el = document.createElement('div');
        el.className = 'snackbar snackbar--' + (type === 'progress' ? 'progress' : 'success');
        el.setAttribute('role', 'status');
        var icon = document.createElement('div');
        icon.className = 'snackbar__icon';
        icon.innerHTML = type === 'progress'
            ? '<i class="fa-solid fa-spinner fa-spin" aria-hidden="true"></i>'
            : '<i class="fa-solid fa-check" aria-hidden="true"></i>';
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
                var icon = el.querySelector('.snackbar__icon');
                if (icon) {
                    icon.innerHTML = '<i class="fa-solid fa-check" aria-hidden="true"></i>';
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
        var container = document.querySelector('.container');
        if (!container) {
            return;
        }
        hideOverlay();
        var el = document.createElement('div');
        el.id = 'recovery-loading-overlay';
        el.className = 'recovery-loading';
        el.style.display = 'block';
        el.innerHTML = '<div class="recovery-loading-msg"></div>'
            + '<div class="recovery-loading-pct" id="recovery-loading-pct">0 %</div>'
            + '<div class="recovery-loading-track"><div class="recovery-loading-fill" id="recovery-loading-fill"></div></div>'
            + '<div class="recovery-loading-steps" id="recovery-loading-steps"></div>';
        el.querySelector('.recovery-loading-msg').textContent = message;
        var stepsEl = el.querySelector('#recovery-loading-steps');
        if (stepsEl && stepsText) {
            stepsEl.textContent = stepsText;
        }
        container.insertBefore(el, container.firstChild);
        if (window.RecoveryUi && typeof RecoveryUi.showProgress === 'function') {
            progressSnackbar = RecoveryUi.showProgress(message);
        }
        var pct = 0;
        var fill = el.querySelector('#recovery-loading-fill');
        var pctEl = el.querySelector('#recovery-loading-pct');
        progressTimer = setInterval(function () {
            if (pct < 96) {
                pct += pct < 50 ? 4 : (pct < 80 ? 2 : 1);
                if (fill) {
                    fill.style.width = pct + '%';
                    fill.style.transform = 'none';
                    fill.style.animation = 'none';
                }
                if (pctEl) {
                    pctEl.textContent = pct + ' %';
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
                function doneOk() {
                    btn.classList.add('copied');
                    var oldHtml = btn.innerHTML;
                    btn.innerHTML = '<i class="fa-solid fa-check"></i> Kopiert';
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

    function bindForms() {
        document.querySelectorAll('form[data-recovery-loading]').forEach(function (form) {
            if (form.dataset.recoveryLoadingBound === '1') {
                return;
            }
            form.dataset.recoveryLoadingBound = '1';
            form.addEventListener('submit', function (ev) {
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
                showOverlay(
                    form.getAttribute('data-recovery-loading') || 'Bitte warten …',
                    form.getAttribute('data-recovery-loading-steps') || ''
                );
            });
        });
    }

    function init() {
        hideOverlay();
        bindForms();
        bindCopyButtons();
        if (window.location.search.indexOf('expert=1') !== -1) {
            var expert = document.getElementById('recovery-expert-panel');
            if (expert) {
                expert.open = true;
            }
        }
    }

    window.addEventListener('pageshow', function (ev) {
        if (ev.persisted) {
            hideOverlay();
            document.querySelectorAll('form[data-recovery-loading]').forEach(function (form) {
                delete form.dataset.recoveryConfirmed;
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
    echo '<input type="hidden" name="t" value="' . \htmlspecialchars($authHash, ENT_QUOTES, 'UTF-8') . '">';
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

function recoveryUninstallShouldShowInputForm(): bool
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

function recoveryResolveTrustedExtractDir(?string $authHash = null): ?string
{
    $postedExtract = $_POST['extract_dir'] ?? $_GET['extract_dir'] ?? null;
    if ($postedExtract) {
        $uploadBase = \realpath(__DIR__ . '/uploads');
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

function recoveryGetSiteBaseUrl(): string
{
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $script = $_SERVER['SCRIPT_NAME'] ?? '/plugin-recovery-tool.php';
    $base = \rtrim(\str_replace('\\', '/', \dirname($script)), '/');

    return $scheme . '://' . $host . ($base === '' || $base === '.' ? '' : $base) . '/';
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

    echo '<div class="alert alert-info"><strong>Datenbank-Bereinigung (Package-ID ' . $packageID . '):</strong><br>';
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

    // Verwaiste Applications (PackageListPage: getPackage() → null)
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

-- ACP-Paketliste: tainted application ohne Package-Zeile
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
    $checks = [];
    $phpOk = \PHP_VERSION_ID >= 80100;
    $checks[] = [
        'key' => 'php',
        'label' => 'PHP-Version',
        'status' => $phpOk ? 'ok' : 'error',
        'detail' => \PHP_VERSION . ($phpOk ? '' : ' (min. 8.1)'),
    ];

    $writable = [
        'cache/' => \is_writable($wcfDir . 'cache'),
        'tmp/' => \is_writable($wcfDir . 'tmp'),
        'lib/bootstrap.php' => \is_writable($wcfDir . 'lib/bootstrap.php') || \is_writable($wcfDir . 'lib/'),
    ];
    foreach ($writable as $path => $ok) {
        $checks[] = [
            'key' => 'write_' . $path,
            'label' => 'Schreibrecht ' . $path,
            'status' => $ok ? 'ok' : 'error',
            'detail' => $ok ? 'beschreibbar' : 'nicht beschreibbar',
        ];
    }

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
    }
    if ($dbOk) {
        $checks[] = [
            'key' => 'db',
            'label' => 'Datenbank',
            'status' => 'ok',
            'detail' => 'Verbindung OK (wcf' . $wcfN . '_*)',
        ];
    }

    $assets ??= recoveryGetSetupAssets();
    $checks[] = [
        'key' => 'wcfsetup_css',
        'label' => 'WCFSetup.css',
        'status' => ($assets['WCFSetup.css'] ?? '') !== '' ? 'ok' : 'warn',
        'detail' => ($assets['WCFSetup.css'] ?? '') !== '' ? 'lokal verfügbar' : 'nicht lesbar',
    ];
    $faLocal = !empty($assets['fontAwesomeLocal']);
    $checks[] = [
        'key' => 'fontawesome',
        'label' => 'Font Awesome',
        'status' => $faLocal ? 'ok' : 'warn',
        'detail' => $faLocal ? 'lokal (WCF v7)' : 'CDN-Fallback',
    ];

    $logHits = recoveryScanWoltLabLogForRecentErrors($wcfDir, 5);
    $classNotFound = false;
    foreach ($logHits as $line) {
        if (\str_contains((string) $line, 'ClassNotFound') || \str_contains((string) $line, 'Unable to find class')) {
            $classNotFound = true;
            break;
        }
    }
    $checks[] = [
        'key' => 'log',
        'label' => 'WoltLab-Log (ClassNotFound)',
        'status' => $classNotFound ? 'warn' : 'ok',
        'detail' => $classNotFound ? 'kürzlich ClassNotFound im Log' : 'kein ClassNotFound in den letzten Zeilen',
    ];

    return $checks;
}

function recoveryRenderSystemCheckPage(
    string $authHash,
    string $wcfDir,
    ?\wcf\system\database\Database $db,
    ?int $wcfN,
    ?array $assets
): void {
    $checks = recoveryRunSystemChecks($wcfDir, $db, $wcfN, $assets);
    $statusIcon = static function (string $status): string {
        return match ($status) {
            'ok' => '<i class="fa-solid fa-circle-check" style="color:#3c3"></i>',
            'warn' => '<i class="fa-solid fa-triangle-exclamation" style="color:#c93"></i>',
            default => '<i class="fa-solid fa-circle-xmark" style="color:#c33"></i>',
        };
    };
    ?>
    <h1><i class="fa-solid fa-stethoscope"></i> System-Check</h1>
    <p class="subtitle">Kurzprüfung der Voraussetzungen für Recovery-Schritte (wie WoltLab test.php).</p>
    <table class="table" style="margin-top:16px">
        <thead><tr><th style="width:40px"></th><th>Prüfung</th><th>Ergebnis</th></tr></thead>
        <tbody>
        <?php foreach ($checks as $check): ?>
            <tr>
                <td><?= $statusIcon((string) $check['status']) ?></td>
                <td><?= \htmlspecialchars((string) $check['label']) ?></td>
                <td><small><?= \htmlspecialchars((string) $check['detail']) ?></small></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <details class="recovery-info-panel" style="margin-top:20px">
        <summary>Technische Details</summary>
        <ul style="margin:12px 0 0 20px">
            <li>Recovery Tool v<?= \htmlspecialchars(RECOVERY_VERSION) ?></li>
            <li>WCF_DIR: <code><?= \htmlspecialchars($wcfDir) ?></code></li>
            <?php if ($wcfN !== null): ?>
            <li>WCF_N: <?= (int) $wcfN ?></li>
            <?php endif; ?>
        </ul>
    </details>
    <p style="margin-top:20px">
        <a href="<?= \htmlspecialchars(recoveryHomeUrl($authHash)) ?>" class="button"><i class="fa-solid fa-house"></i> Zurück zum Start</a>
    </p>
    <?php
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
    ?>
    <header class="recovery-beer-header">
        <h1>Datensicherung</h1>
        <p>Empfehlungen aus dem <a href="<?= \htmlspecialchars($manualUrl) ?>" target="_blank" rel="noopener">WoltLab-Handbuch</a> — vor Plugin-Entfernung oder SQL-Rollback immer Backup.</p>
    </header>

    <article class="border round medium-padding margin-bottom-medium">
        <h2 class="medium">Checkliste</h2>
        <ul class="recovery-next-list">
            <li>Datenbank <strong>und</strong> Dateisystem sichern — gleicher Zeitpunkt.</li>
            <li>Backups regelmäßig prüfen (vollständig, wiederherstellbar).</li>
            <li>Vor Updates oder Recovery-Schritten mit Datenverlust: neues Backup.</li>
            <li>Bei Hoster-Backups: zusätzlich eigene Sicherung anlegen.</li>
        </ul>
    </article>

    <article class="border round medium-padding margin-bottom-medium">
        <h2 class="medium">SSH — Datenbank</h2>
        <p><small>Passwort wird interaktiv abgefragt (<code>-p</code>). Werte aus <code>config.inc.php</code>.</small></p>
        <pre class="recovery-code-block" id="recovery-cmd-mysqldump"><?= \htmlspecialchars($hints['mysqldump']) ?></pre>
        <button type="button" class="secondary" data-recovery-copy-target="recovery-cmd-mysqldump">
            <i class="fa-solid fa-copy"></i> Befehl kopieren
        </button>
    </article>

    <article class="border round medium-padding margin-bottom-medium">
        <h2 class="medium">SSH — Dateisystem</h2>
        <p><small>Tarball des WoltLab-Hauptverzeichnisses (<code><?= \htmlspecialchars($hints['wcfDir']) ?></code>).</small></p>
        <pre class="recovery-code-block" id="recovery-cmd-tar"><?= \htmlspecialchars($hints['tar']) ?></pre>
        <button type="button" class="secondary" data-recovery-copy-target="recovery-cmd-tar">
            <i class="fa-solid fa-copy"></i> Befehl kopieren
        </button>
    </article>

    <article class="border round medium-padding margin-bottom-medium">
        <h2 class="medium">Ohne SSH</h2>
        <p>FTP/SFTP: alle Installationsdateien herunterladen (Binärmodus). Datenbank z.&nbsp;B. über phpMyAdmin oder Hoster-Panel exportieren.</p>
    </article>

    <nav class="row right-align">
        <a href="<?= \htmlspecialchars(recoveryHomeUrl($authHash)) ?>" class="button">Zurück zum Start</a>
    </nav>
    <?php
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

function recoveryRenderDirectoryStructurePage(
    string $authHash,
    string $wcfDir,
    \wcf\system\database\Database $db,
    int $wcfN
): void {
    $apps = recoveryFetchApplicationDirectoryReport($db, $wcfN, $wcfDir);
    $manualUrl = 'https://manual.woltlab.com/de/customize-directory-structure/';
    ?>
    <header class="recovery-beer-header">
        <h1>Verzeichnisstruktur &amp; Applications</h1>
        <p>
            Übersicht aus <code>wcf<?= (int) $wcfN ?>_application</code> und <code>package</code>.
            Änderungen nur mit Admin-Kenntnissen —
            <a href="<?= \htmlspecialchars($manualUrl) ?>" target="_blank" rel="noopener">Handbuch: Verzeichnisstruktur ändern</a>.
        </p>
    </header>

    <article class="border round medium-padding margin-bottom-medium">
        <h2 class="medium">Hinweis</h2>
        <p>Domain-Änderungen erkennt WoltLab oft automatisch beim ersten ACP-Aufruf. Struktur-Umzüge (Core nach <code>/core/</code>, App ins Root) erfordern Dateiverschiebung, DB-Anpassung und Cache-Leerung — siehe Handbuch.</p>
    </article>

    <?php if ($apps === []): ?>
    <article class="border round medium-padding">
        <p><em>Keine Applications in der Datenbank gefunden.</em></p>
    </article>
    <?php else: ?>
    <div class="responsive medium-margin">
        <table class="border">
            <thead>
                <tr>
                    <th>Package</th>
                    <th>packageDir</th>
                    <th>domainPath</th>
                    <th>Ordner</th>
                    <th>Hinweise</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($apps as $app): ?>
                <tr>
                    <td><code><?= \htmlspecialchars($app['package']) ?></code></td>
                    <td><code><?= \htmlspecialchars($app['packageDir'] ?: '/') ?></code></td>
                    <td><code><?= \htmlspecialchars($app['domainPath']) ?></code></td>
                    <td><?= $app['dirExists'] ? '<span class="green-text">OK</span>' : '<span class="red-text">fehlt</span>' ?></td>
                    <td><small><?= $app['issues'] !== [] ? \htmlspecialchars(\implode('; ', $app['issues'])) : '—' ?></small></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <nav class="row right-align margin-top-large">
        <a href="<?= \htmlspecialchars(recoveryHomeUrl($authHash)) ?>" class="button">Zurück zum Start</a>
    </nav>
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

// ============================================================================
// UI (WoltLab WCFSetup.css – wie Rescue Mode / offizielles Recovery Tool)
// ============================================================================

/**
 * Setup-Assets für die Recovery-Oberfläche.
 *
 * Früher: komplette CSS/PNG als data:-URI (base64) — bei großen Dateien oder knappem memory_limit
 * häufig **fatal / HTTP 500 ohne Ausgabe** auf Shared Hosting.
 *
 * @return array{WCFSetup.css: string, woltlabSuite.png: string, fontAwesomeCss: string, fontAwesomeLocal: bool}
 */
function recoveryGetSetupAssets(): array
{
    // #region agent log
    recoveryAgentDebugLog('H4', 'recoveryGetSetupAssets', 'enter', ['wcfDirDefined' => \defined('WCF_DIR')]);
    // #endregion
    if (!\defined('WCF_DIR')) {
        try {
            \define('WCF_DIR', recoveryResolveWcfDir());
        } catch (\Throwable $ignored) {
            // #region agent log
            recoveryAgentDebugLog('H4', 'recoveryGetSetupAssets', 'wcf_resolve_failed', ['exceptionClass' => \get_class($ignored)]);
            // #endregion

            return [
                'WCFSetup.css' => '',
                'woltlabSuite.png' => '',
                'fontAwesomeCss' => '',
                'fontAwesomeLocal' => false,
            ];
        }
    }

    $assets = [
        'WCFSetup.css' => '',
        'woltlabSuite.png' => '',
        'fontAwesomeCss' => '',
        'fontAwesomeLocal' => false,
    ];
    $cssPath = WCF_DIR . 'acp/style/setup/WCFSetup.css';
    $imgPath = WCF_DIR . 'acp/images/woltlabSuite.png';
    $faPath = WCF_DIR . 'icon/font-awesome/v7/css/all.min.css';

    if (\is_readable($cssPath)) {
        $assets['WCFSetup.css'] = 'acp/style/setup/WCFSetup.css';
    }
    if (\is_readable($imgPath)) {
        $assets['woltlabSuite.png'] = 'acp/images/woltlabSuite.png';
    }
    if (\is_readable($faPath)) {
        $assets['fontAwesomeCss'] = 'icon/font-awesome/v7/css/all.min.css';
        $assets['fontAwesomeLocal'] = true;
    }

    // #region agent log
    recoveryAgentDebugLog('H4', 'recoveryGetSetupAssets', 'exit', [
        'cssHrefLen' => \strlen($assets['WCFSetup.css']),
        'pngHrefLen' => \strlen($assets['woltlabSuite.png']),
        'wcfDirTail' => \substr(\str_replace('\\', '/', (string) (\defined('WCF_DIR') ? \constant('WCF_DIR') : '')), -48),
    ]);
    // #endregion

    return $assets;
}

function recoveryRenderStandaloneMessage(string $documentTitle, string $contentTitle, string $bodyHtml): void
{
    $assets = recoveryGetSetupAssets();
    recoveryRenderPageStart($documentTitle, $contentTitle, $assets);
    echo $bodyHtml;
    recoveryRenderPageEnd($assets);
}

function recoveryRenderPageStart(string $documentTitle, string $contentTitle = '', ?array $assets = null): void
{
    // #region agent log
    recoveryAgentDebugLog('H4', 'recoveryRenderPageStart', 'enter', ['assetsProvided' => $assets !== null]);
    // #endregion
    try {
        $assets ??= recoveryGetSetupAssets();
    } catch (\Throwable $ignored) {
        // #region agent log
        recoveryAgentDebugLog('H4', 'recoveryRenderPageStart', 'getAssets_throw', ['exceptionClass' => \get_class($ignored)]);
        // #endregion
        $assets = [
            'WCFSetup.css' => '',
            'woltlabSuite.png' => '',
            'fontAwesomeCss' => '',
            'fontAwesomeLocal' => false,
        ];
    }
    $faHref = ($assets['fontAwesomeCss'] ?? '') !== ''
        ? (string) $assets['fontAwesomeCss']
        : 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css';
    $faIntegrity = ($assets['fontAwesomeLocal'] ?? false)
        ? ''
        : ' integrity="sha512-Evv84Mr4kqVGRNSgIGL/F/aIDqQb7xQ2vcrdIwxfjThSH8CSR7PBEakCr51Ck+w+/U6swU2Im1vVX0SVk9ABhg==" crossorigin="anonymous" referrerpolicy="no-referrer"';
    ?>
<!DOCTYPE html>
<html lang="de" data-recovery-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="robots" content="noindex">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= \htmlspecialchars($documentTitle) ?></title>
    <link rel="stylesheet" href="<?= \htmlspecialchars(RECOVERY_BEER_CSS) ?>">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined&amp;display=swap">
    <script>
    (function () {
        var k = 'recoveryTheme', t = localStorage.getItem(k) || 'dark';
        document.documentElement.setAttribute('data-recovery-theme', t);
        document.documentElement.setAttribute('data-theme', t === 'light' ? 'light' : 'dark');
    })();
    </script>
    <link rel="stylesheet" href="<?= \htmlspecialchars($faHref) ?>"<?= $faIntegrity ?>>
    <style>
        :root, html[data-recovery-theme="dark"] {
            --recovery-bg: #2D2D2D;
            --recovery-text: #c0c0c0;
            --recovery-card: #3D3D3D;
            --recovery-border: #444444;
            --recovery-heading: #fff;
            --recovery-muted: #9D9D9D;
            --recovery-link: #6EC2FF;
            --recovery-input-bg: #2D2D2D;
        }
        html[data-recovery-theme="light"] {
            --recovery-bg: #f0f0f0;
            --recovery-text: #333;
            --recovery-card: #fff;
            --recovery-border: #ddd;
            --recovery-heading: #333;
            --recovery-muted: #666;
            --recovery-link: #369;
            --recovery-input-bg: #fff;
        }
        /* WCFSetup.css neutralisieren – einfaches Container-Layout beibehalten */
        .pageHeaderContainer, .pageHeaderFacade, .pageHeader, .pageHeaderLogo,
        .pageContainer, #pageContainer, .pageNavigation,
        #acpPageContentContainer, .acpPageContentContainer,
        .layoutBoundary, .contentHeader, .contentTitle, .content,
        #pageFooter, .pageFooter, .pageFooterCopyright, .copyright {
            background: transparent !important;
            border: none !important;
            box-shadow: none !important;
            margin: 0 !important;
            padding: 0 !important;
            max-width: none !important;
            min-height: 0 !important;
        }
        .section, .sectionHeader, .sectionTitle, .sectionDescription,
        .formSubmit, .recoveryModeGrid, .recoveryModeCard, .recoveryBackLink {
            background: transparent !important;
            border: none !important;
            box-shadow: none !important;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            line-height: 1.5;
        }
        .recovery-shell {
            max-width: 1024px;
            margin: 0 auto;
            padding: 24px 16px 48px;
        }
        .recovery-beer-header { margin-bottom: 24px; }
        .recovery-beer-header h1 { margin: 0 0 8px; font-weight: 400; }
        .recovery-beer-header p { margin: 0; opacity: 0.85; }
        .recovery-code-block {
            display: block; overflow-x: auto; padding: 12px 14px; margin: 12px 0;
            font-size: 13px; border-radius: 8px; white-space: pre-wrap; word-break: break-all;
        }
        .margin-bottom-medium { margin-bottom: 20px; }
        .margin-top-large { margin-top: 28px; }
        .container {
            max-width: 980px;
            margin: 0 auto;
            padding: 0;
        }
        .recovery-theme-bar {
            display: flex; flex-wrap: wrap; gap: 6px; align-items: center;
            margin-bottom: 16px; font-size: 12px; color: var(--recovery-muted);
        }
        .recovery-theme-btn {
            background: transparent; border: 1px solid var(--recovery-border);
            color: var(--recovery-text); border-radius: 4px; padding: 4px 10px;
            cursor: pointer; font-size: 12px;
        }
        .recovery-theme-btn.is-active { background: rgba(51,102,153,.35); border-color: #369; }
        footer {
            max-width: 980px;
            margin: 20px auto 0;
            padding: 10px 0;
            text-align: right;
            color: #9D9D9D;
            font-size: 13px;
        }
        footer a { color: inherit; text-decoration: none; }
        footer a:hover { color: #fff; }
        h1 { color: var(--recovery-heading); margin-bottom: 10px; font-size: 32px; font-weight: 300; }
        h2 { color: var(--recovery-heading); margin: 40px 0 10px 0; font-size: 24px; font-weight: 300; }
        .subtitle { color: var(--recovery-muted); margin-bottom: 30px; font-size: 14px; }
        code { color: var(--recovery-heading); font-family: SFMono-Regular, Menlo, Monaco, Consolas, monospace; word-break: break-word; }
        html[data-recovery-theme="light"] code { color: #369; background: #f0f4ff; padding: 1px 5px; border-radius: 2px; }
        .mode-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }
        .mode-button {
            display: block;
            padding: 20px;
            background: rgba(0, 0, 0, .125);
            border: 1px solid #444444;
            border-radius: 3px;
            text-decoration: none;
            color: #c0c0c0;
            text-align: center;
            transition: all 0.2s;
            cursor: pointer;
        }
        .mode-button:hover { background: rgba(0, 0, 0, .25); border-color: #666; }
        .mode-button strong { display: block; font-size: 18px; margin-bottom: 8px; color: #fff; }
        .mode-button span { font-size: 13px; color: #9D9D9D; }
        .recovery-card {
            background: rgba(0, 0, 0, .125); border: 1px solid #444; border-radius: 3px;
            padding: 20px; margin-bottom: 20px;
        }
        .recovery-option-cards {
            display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; margin-bottom: 24px;
        }
        @media (max-width: 720px) { .recovery-option-cards { grid-template-columns: 1fr; } }
        .recovery-option-card h3 { margin: 0 0 12px; font-size: 16px; color: #fff; }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 8px; font-weight: 600; color: var(--recovery-heading); }
        input[type="text"], input[type="password"], textarea, select {
            width: 100%; padding: 10px; border: 1px solid var(--recovery-border); border-radius: 3px;
            font-size: 14px; background: var(--recovery-input-bg); color: var(--recovery-text);
        }
        input[type="file"] {
            width: 100%; padding: 10px; border: 1px dashed var(--recovery-border); border-radius: 3px;
            background: var(--recovery-input-bg); color: var(--recovery-text);
        }
        button, .button {
            background: #369; color: white; padding: 12px 24px; border: none; border-radius: 3px;
            font-size: 14px; font-weight: 600; cursor: pointer; transition: background 0.2s;
            display: inline-block; text-decoration: none;
        }
        button:hover, .button:hover { background: #258; }
        .btn-danger { background: #c33; }
        .btn-danger:hover { background: #a22; }
        .btn-success { background: #3c3; }
        .btn-success:hover { background: #2a2; }
        .alert { padding: 15px 20px; margin-bottom: 20px; border-radius: 3px; color: #fff; }
        .alert-success { background: rgba(60, 204, 60, 0.3); border: 1px solid #3c3; }
        .alert-error { background: rgba(204, 51, 51, 0.3); border: 1px solid #c33; }
        .alert-info { background: rgba(51, 102, 153, 0.3); border: 1px solid #369; }
        .alert-warning { background: rgba(204, 153, 51, 0.3); border: 1px solid #c93; }
        .back-link { display: inline-block; margin-bottom: 20px; color: #fff; text-decoration: none; }
        .back-link:hover { text-decoration: underline; }
        pre {
            background: #2D2D2D; padding: 15px; border-radius: 3px; overflow-x: auto;
            font-size: 13px; color: #c0c0c0; border: 1px solid #444444;
        }
        pre.recoveryLog { max-height: 340px; }
        small { color: #9D9D9D; }
        .table, table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        .table th, .table td, table th, table td {
            padding: 10px 20px; text-align: left; border-bottom: 1px solid #444444; color: #c0c0c0;
        }
        .table th, table th { border-top: 1px solid #444444; border-bottom-width: 2px; font-weight: 600; color: #fff; }
        .table tbody tr:nth-child(odd), table tbody tr:nth-child(odd) { background: rgba(0, 0, 0, .125); }
        .table tbody tr:hover, table tbody tr:hover { background: rgba(0, 0, 0, .25); }
        hr { border: none; border-top: 1px solid #444444; margin: 30px 0; }

        /* ── Wizard Step Indicator ─────────────────────────────────────── */
        .wizardSteps { display: flex; align-items: flex-start; margin: 0 0 30px; padding: 0; }
        .wizardStep { display: flex; flex-direction: column; align-items: center; position: relative; flex: 1; }
        .wizardStep::after { content: ''; position: absolute; top: 20px; left: 50%; width: 100%; height: 2px; background: #444; z-index: 0; }
        .wizardStep:last-child::after { display: none; }
        .wizardStepNumber { width: 40px; height: 40px; border-radius: 50%; background: #444; color: #9D9D9D; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 16px; position: relative; z-index: 1; border: 2px solid #444; transition: background .3s, border-color .3s, color .3s; }
        .wizardStep.active .wizardStepNumber { background: #369; color: #fff; border-color: #369; }
        .wizardStep.completed .wizardStepNumber { background: #3c3; color: transparent; border-color: #3c3; }
        .wizardStep.completed .wizardStepNumber::after { content: '✓'; color: #fff; font-size: 16px; font-weight: 700; position: absolute; }
        .wizardStepLabel { margin-top: 8px; font-size: 12px; color: #9D9D9D; text-align: center; line-height: 1.3; }
        .wizardStep.active .wizardStepLabel { color: #fff; font-weight: 600; }
        .wizardStep.completed .wizardStepLabel { color: #5d5; }
        .wizardPanel { display: none; }
        .wizardPanel.active { display: block; }
        .recovery-loading {
            display: none;
            padding: 24px 20px;
            margin: 20px 0;
            text-align: center;
            color: #9D9D9D;
            background: rgba(51, 102, 153, 0.12);
            border: 1px solid #369;
            border-radius: 3px;
        }
        .recovery-loading-msg { display: block; font-size: 15px; color: #e8e8e8; margin-bottom: 6px; }
        .recovery-loading-track {
            height: 6px;
            background: rgba(0, 0, 0, .35);
            border-radius: 3px;
            overflow: hidden;
            margin-top: 14px;
            max-width: 520px;
            margin-left: auto;
            margin-right: auto;
        }
        .recovery-loading-fill {
            height: 100%;
            width: 42%;
            background: linear-gradient(90deg, #369, #6EC2FF);
            border-radius: 3px;
            animation: recovery-indeterminate 1.35s ease-in-out infinite;
        }
        @keyframes recovery-indeterminate {
            0% { transform: translateX(-110%); }
            100% { transform: translateX(320%); }
        }
        @keyframes recovery-spin { to { transform: rotate(360deg); } }

        .recovery-pip-count-btn {
            background: transparent; border: none; color: #fc6; font-weight: 700;
            cursor: pointer; text-decoration: underline; font-size: inherit; padding: 0;
        }
        .recovery-pip-count-btn:hover { color: #fff; }
        .recovery-pip-count-btn--zero { color: #888; cursor: default; text-decoration: none; }
        #recoveryPipPreviewModal {
            position: fixed; inset: 0; z-index: 10000; display: flex; align-items: center;
            justify-content: center; padding: 20px; background: rgba(0, 0, 0, 0.65);
        }
        #recoveryPipPreviewModal[hidden] { display: none !important; }
        .recovery-pip-preview-dialog {
            width: 100%; max-width: 820px; max-height: 85vh; overflow: auto;
            background: #3D3D3D; border: 1px solid #555; border-radius: 4px; padding: 20px;
        }
        .recovery-pip-preview-dialog h3 { margin: 0 0 12px; color: #fff; font-size: 18px; }
        .recovery-dryrun-quick { display: none; margin-top: 14px; }

        @media (prefers-color-scheme: light) {
            body { background: #f0f0f0; color: #333; }
            .container { background: #fff; box-shadow: 0 1px 4px rgba(0,0,0,.1); }
            footer { color: #777; }
            footer a:hover { color: #369; }
            h1, h2 { color: #333; }
            .subtitle { color: #666; }
            code { color: #369; background: #f0f4ff; padding: 1px 5px; border-radius: 2px; }
            .mode-button { background: #f9f9f9; border-color: #ddd; color: #333; }
            .mode-button:hover { background: #f0f4ff; border-color: #369; }
            .mode-button strong { color: #333; }
            .mode-button span { color: #666; }
            label { color: #333; }
            input[type="text"], input[type="password"], textarea, select { background: #fff; border-color: #ccc; color: #333; }
            input[type="file"] { background: #fff; border-color: #ccc; color: #333; }
            .alert { color: #333; }
            .alert-info { background: rgba(51,102,153,.08); border-color: #369; }
            .alert-success { background: rgba(51,153,51,.08); border-color: #3a3; }
            .alert-error { background: rgba(204,51,51,.08); border-color: #c33; }
            .alert-warning { background: rgba(200,120,40,.08); border-color: #c83; }
            .back-link { color: #369; }
            pre { background: #fafafa; border-color: #ddd; color: #333; }
            .table th, .table td, table th, table td { border-color: #ddd; color: #333; }
            .table th, table th { color: #555; border-top-color: #ddd; }
            .table tbody tr:nth-child(odd), table tbody tr:nth-child(odd) { background: rgba(0,0,0,.03); }
            .table tbody tr:hover, table tbody tr:hover { background: rgba(0,0,0,.06); }
            hr { border-top-color: #ddd; }
            small { color: #777; }
            .recovery-global-nav { border-bottom-color: #ddd; }
            .recovery-nav-link { color: #369; }
            .wizardStep::after { background: #ddd; }
            .wizardStepNumber { background: #e0e0e0; color: #888; border-color: #ddd; }
            .wizardStep.active .wizardStepNumber { background: #369; color: #fff; border-color: #369; }
            .wizardStep.completed .wizardStepNumber { background: #3a3; border-color: #3a3; }
            .wizardStepLabel { color: #888; }
            .wizardStep.active .wizardStepLabel { color: #369; font-weight: 600; }
            .wizardStep.completed .wizardStepLabel { color: #3a3; }
        }
        .recovery-global-nav {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 12px 20px;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 1px solid #444444;
        }
        .recovery-nav-link {
            color: #fff;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
        }
        .recovery-nav-link:hover { text-decoration: underline; }
        .recovery-nav-acp { margin-left: auto; }
        .recovery-breadcrumb {
            font-size: 13px; color: #9D9D9D; margin: 0 0 20px; line-height: 1.6;
        }
        .recovery-breadcrumb a { color: #6EC2FF; text-decoration: none; }
        .recovery-breadcrumb a:hover { text-decoration: underline; }
        .recovery-breadcrumb strong { color: #e8e8e8; }
        .recovery-intake-hero { margin-bottom: 28px; }
        .recovery-intake-hero h1 { margin-bottom: 8px; }
        .recovery-scenario-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 16px;
            margin-bottom: 28px;
        }
        @media (min-width: 720px) {
            .recovery-scenario-grid { grid-template-columns: repeat(2, 1fr); }
            .recovery-scenario-card--primary { grid-column: 1 / -1; }
        }
        .recovery-scenario-card {
            display: block;
            padding: 22px 24px;
            background: rgba(0, 0, 0, .2);
            border: 2px solid #444;
            border-radius: 6px;
            text-decoration: none;
            color: inherit;
            transition: border-color .2s, background .2s, transform .15s;
        }
        .recovery-scenario-card:hover {
            border-color: #666;
            background: rgba(0, 0, 0, .3);
            transform: translateY(-1px);
        }
        .recovery-scenario-card--primary {
            border-color: #369;
            background: rgba(51, 102, 153, .15);
        }
        .recovery-scenario-card--primary:hover { border-color: #6EC2FF; }
        .recovery-scenario-icon {
            font-size: 28px; color: #6EC2FF; margin-bottom: 12px; display: block;
        }
        .recovery-scenario-card--primary .recovery-scenario-icon { color: #fff; }
        .recovery-scenario-card h2 {
            margin: 0 0 10px; font-size: 20px; color: #fff; font-weight: 700;
        }
        .recovery-scenario-card p {
            margin: 0 0 14px; font-size: 14px; line-height: 1.55; color: #b8b8b8;
        }
        .recovery-scenario-cta {
            font-size: 13px; font-weight: 700; color: #6EC2FF; text-transform: uppercase;
            letter-spacing: .03em;
        }
        .recovery-scenario-card--primary .recovery-scenario-cta { color: #fff; }
        .recovery-info-panel {
            background: rgba(0, 0, 0, .15);
            border: 1px solid #444;
            border-radius: 6px;
            padding: 18px 20px;
            margin-bottom: 24px;
        }
        .recovery-info-panel h2 {
            margin: 0 0 6px; font-size: 16px; color: #fff;
        }
        .recovery-info-panel .recovery-info-hint {
            margin: 0 0 16px; font-size: 13px; color: #9D9D9D; line-height: 1.5;
        }
        .recovery-info-panel--drawer summary {
            cursor: pointer; font-size: 15px; font-weight: 600; color: #fff;
            list-style: none; margin: 0 0 8px;
        }
        .recovery-info-panel--drawer summary::-webkit-details-marker { display: none; }
        .recovery-status-bar {
            margin: 0 0 20px; font-size: 13px; color: #9D9D9D; line-height: 1.5;
        }
        .recovery-status-sep { margin: 0 6px; color: #555; }
        .recovery-status-warn { color: #fc6; }
        .recovery-status-link { color: #6EC2FF; text-decoration: none; }
        .recovery-status-link:hover { text-decoration: underline; }
        .recovery-info-grid { display: grid; gap: 10px; }
        .recovery-copy-row {
            display: grid;
            grid-template-columns: minmax(120px, 28%) 1fr auto;
            gap: 10px 12px;
            align-items: center;
            padding: 10px 12px;
            background: rgba(0, 0, 0, .2);
            border-radius: 4px;
            border: 1px solid #3a3a3a;
        }
        @media (max-width: 640px) {
            .recovery-copy-row { grid-template-columns: 1fr; }
        }
        .recovery-copy-label { font-size: 12px; color: #9D9D9D; font-weight: 600; }
        .recovery-copy-value {
            font-size: 13px; color: #e0e0e0; word-break: break-all;
            margin: 0;
        }
        .recovery-copy-btn {
            background: #444; color: #fff; border: none; border-radius: 4px;
            padding: 8px 12px; font-size: 12px; font-weight: 600; cursor: pointer;
            white-space: nowrap;
        }
        .recovery-copy-btn:hover { background: #555; }
        .recovery-copy-btn.copied { background: #3a3; }
        .recovery-expert-panel {
            margin-top: 8px; border: 1px solid #444; border-radius: 6px;
            background: rgba(0, 0, 0, .08);
        }
        .recovery-expert-panel > summary {
            cursor: pointer; padding: 16px 20px; font-weight: 700; color: #c0c0c0;
            list-style: none; user-select: none;
        }
        .recovery-expert-panel > summary::-webkit-details-marker { display: none; }
        .recovery-expert-panel > summary::before {
            content: '▸ '; color: #6EC2FF;
        }
        .recovery-expert-panel[open] > summary::before { content: '▾ '; }
        .recovery-expert-panel[open] > summary { color: #fff; border-bottom: 1px solid #444; }
        .recovery-expert-body { padding: 20px; }
        .recovery-expert-body .mode-grid { margin-bottom: 0; }
        .recovery-loading-steps {
            font-size: 13px; color: #9D9D9D; margin-top: 10px; max-width: 520px;
            margin-left: auto; margin-right: auto; text-align: left;
        }
        .recovery-loading-pct {
            font-size: 12px; color: #6EC2FF; margin-top: 8px; font-variant-numeric: tabular-nums;
        }
        .recovery-rec-panel {
            border-radius: 6px; padding: 18px 20px; margin-bottom: 20px;
            border: 1px solid #444; background: rgba(0, 0, 0, .18);
        }
        .recovery-rec-panel--critical { border-color: #c33; background: rgba(204, 51, 51, .12); }
        .recovery-rec-panel--warning { border-color: #c93; background: rgba(204, 153, 51, .1); }
        .recovery-rec-panel--ok { border-color: #3a3; background: rgba(51, 153, 51, .1); }
        .recovery-rec-panel h2 { margin: 0 0 10px; font-size: 17px; color: #fff; }
        .recovery-rec-panel .recovery-rec-summary {
            margin: 0 0 16px; font-size: 14px; line-height: 1.6; color: #d0d0d0;
        }
        .recovery-rec-steps { list-style: none; margin: 0; padding: 0; }
        .recovery-rec-step {
            padding: 12px 14px; margin-bottom: 10px; border-radius: 4px;
            background: rgba(0, 0, 0, .22); border-left: 4px solid #555;
        }
        .recovery-rec-step--required { border-left-color: #fc6; }
        .recovery-rec-step--optional { border-left-color: #369; }
        .recovery-rec-step strong { color: #fff; display: block; margin-bottom: 4px; }
        .recovery-rec-step p { margin: 0; font-size: 13px; line-height: 1.55; color: #b0b0b0; }
        .recovery-rec-badge {
            display: inline-block; font-size: 11px; font-weight: 700; text-transform: uppercase;
            letter-spacing: .04em; padding: 2px 8px; border-radius: 3px; margin-right: 8px;
        }
        .recovery-rec-badge--required { background: #c93; color: #1a1a1a; }
        .recovery-rec-badge--recommended { background: #369; color: #fff; }
        .recovery-rec-badge--optional { background: #555; color: #eee; }
        .recovery-next-list { margin: 12px 0 0 20px; color: #c0c0c0; line-height: 1.6; }
        .recovery-step-help {
            margin: 6px 0 0 28px; padding: 10px 12px; font-size: 13px; line-height: 1.5;
            color: #9D9D9D; background: rgba(0,0,0,.15); border-radius: 4px; border-left: 3px solid #369;
        }

        /* Font Awesome Icon-Abstände */
        .fa-solid, .fas { margin-right: 6px; }
        .alert .fa-solid, p.info .fa-solid, p.error .fa-solid, p.success .fa-solid, p.warning .fa-solid { flex-shrink: 0; }
        .mode-button .fa-solid, .recoveryModeCard .fa-solid { display: block; font-size: 28px; margin: 0 auto 10px; }
        button .fa-solid, .button .fa-solid { margin-right: 6px; }

        /* WoltLab Snackbar + Dialog (ACP-ähnlich, standalone) */
        :root {
            --wcfContentBackground: var(--recovery-card);
            --wcfContentBorderInner: var(--recovery-border);
            --wcfContentText: var(--recovery-text);
            --wcfBoxShadow: 0 2px 8px rgba(0, 0, 0, 0.35);
            --wcfStatusSuccessBackground: rgba(51, 153, 51, 0.2);
            --wcfStatusSuccessBorder: #3a3;
            --wcfStatusSuccessText: #e8ffe8;
            --wcfStatusInfoBackground: rgba(51, 102, 153, 0.25);
            --wcfStatusInfoBorder: #369;
            --wcfStatusInfoText: #e8f4ff;
            --wcfBorderRadiusContainer: 4px;
        }
        .snackbarContainer {
            align-items: start;
            bottom: 20px;
            display: flex;
            flex-direction: column;
            gap: 10px;
            left: 20px;
            position: fixed;
            z-index: 10050;
            pointer-events: none;
        }
        .snackbarContainer .snackbar { pointer-events: auto; }
        @keyframes recoverySnackbarIn {
            0% { opacity: 0; transform: translateX(-100%); }
            50% { opacity: 1; transform: translateX(-50%); }
            100% { opacity: 1; transform: translateX(0); }
        }
        @keyframes recoverySnackbarOut {
            0% { opacity: 1; transform: translateX(0); }
            100% { opacity: 0; transform: translateX(-100%); }
        }
        .snackbar {
            animation: recoverySnackbarIn 0.12s ease-in-out;
            background-color: var(--wcfContentBackground);
            border: 1px solid var(--wcfContentBorderInner);
            border-radius: 4px;
            box-shadow: var(--wcfBoxShadow);
            color: var(--wcfContentText);
            display: flex;
            min-width: 220px;
            overflow: hidden;
            padding: 0 5px;
            user-select: none;
        }
        .snackbar--closing { animation: recoverySnackbarOut 0.24s ease-in-out forwards; }
        .snackbar--success {
            background-color: var(--wcfStatusSuccessBackground);
            border-color: var(--wcfStatusSuccessBorder);
            color: var(--wcfStatusSuccessText);
        }
        .snackbar--progress {
            background-color: var(--wcfStatusInfoBackground);
            border-color: var(--wcfStatusInfoBorder);
            color: var(--wcfStatusInfoText);
        }
        .snackbar__icon {
            align-items: center;
            display: flex;
            justify-content: center;
            width: 36px;
        }
        .snackbar__message { flex: 1 0 auto; padding: 10px 5px 10px 0; }
        .recovery-wfl-dialog.dialog {
            background-color: var(--recovery-card);
            border: 1px solid var(--recovery-border);
            color: var(--recovery-text);
            max-width: min(500px, 92vw);
            min-width: 0;
            padding: 0;
        }
        .recovery-wfl-dialog .dialog__document { padding: 20px; }
        .recovery-wfl-dialog .dialog__title {
            color: var(--recovery-heading);
            font-size: 18px;
            font-weight: 600;
            margin: 0;
        }
        .recovery-wfl-dialog .dialog__content {
            line-height: 1.55;
            margin-top: 12px;
        }
        .recovery-wfl-dialog .dialog__control {
            column-gap: 10px;
            display: flex;
            flex-wrap: wrap;
            justify-content: flex-end;
            margin-top: 24px;
        }
        .recovery-wfl-dialog .button--primary { background: #369; }
        .recovery-wfl-dialog::backdrop { background: rgba(0, 0, 0, 0.55); }
    </style>
</head>
<body>
<main class="responsive recovery-shell">
<div class="recovery-theme-bar row middle-align small-space" role="group" aria-label="Darstellung">
    <span class="small">Theme</span>
    <button type="button" class="chip tiny" data-recovery-set-theme="light">Hell</button>
    <button type="button" class="chip tiny" data-recovery-set-theme="dark">Dunkel</button>
    <button type="button" class="chip tiny" data-recovery-set-theme="system">System</button>
</div>
<div class="container">
<script>
(function () {
    var key = 'recoveryTheme';
    function resolved(theme) {
        if (theme === 'system') {
            return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
        }
        return theme === 'light' ? 'light' : 'dark';
    }
    function beerTheme(resolved) {
        document.documentElement.setAttribute('data-theme', resolved === 'light' ? 'light' : 'dark');
    }
    function apply(theme) {
        var resolved = theme === 'system'
            ? (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light')
            : (theme === 'light' ? 'light' : 'dark');
        document.documentElement.setAttribute('data-recovery-theme', theme);
        beerTheme(resolved);
        document.querySelectorAll('[data-recovery-set-theme]').forEach(function (btn) {
            btn.classList.toggle('active', btn.getAttribute('data-recovery-set-theme') === theme);
        });
    }
    var stored = localStorage.getItem(key) || 'dark';
    apply(stored);
    document.querySelectorAll('[data-recovery-set-theme]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var t = btn.getAttribute('data-recovery-set-theme') || 'dark';
            localStorage.setItem(key, t);
            apply(t);
        });
    });
    if (stored === 'system') {
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', function () {
            if (localStorage.getItem(key) === 'system') { apply('system'); }
        });
    }
    document.querySelectorAll('[data-recovery-copy-target]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var id = btn.getAttribute('data-recovery-copy-target');
            var el = id ? document.getElementById(id) : null;
            if (!el) { return; }
            navigator.clipboard.writeText(el.textContent || '').then(function () {
                btn.classList.add('copied');
                setTimeout(function () { btn.classList.remove('copied'); }, 1500);
            });
        });
    });
})();
</script>
<?php
}

function recoveryRenderPageEnd(?array $assets = null): void
{
    try {
        $assets ??= recoveryGetSetupAssets();
    } catch (\Throwable $ignored) {
        $assets ??= [
            'WCFSetup.css' => '',
            'woltlabSuite.png' => '',
            'fontAwesomeCss' => '',
            'fontAwesomeLocal' => false,
        ];
    }
    $baseUrl = '';
    try {
        $baseUrl = recoveryGetSiteBaseUrl();
    } catch (\Throwable $ignored) {
    }
    ?>
</div>
<footer class="center-align small-margin">
    <a href="https://github.com/benjarogit/sc-woltlab-plugin-recovery" target="_blank" rel="noopener"><i class="fa-solid fa-screwdriver-wrench"></i> Plugin Recovery Tool</a> &copy; <?= \date('Y') ?> Sunny C.
    <?php if ($baseUrl !== ''): ?>
    | <a href="<?= \htmlspecialchars($baseUrl) ?>">Installation</a>
    <?php endif; ?>
    | <a href="https://manual.woltlab.com/de/recovery-tool/" target="_blank" rel="noopener">WoltLab Recovery</a>
    | <a href="https://www.beercss.com/" target="_blank" rel="noopener">Beer CSS</a>
</footer>
</main>
<?php
    recoveryRenderWoltLabUiShell();
    recoveryFormLoadingScript();
?>
<script type="module" src="<?= \htmlspecialchars(RECOVERY_BEER_JS) ?>"></script>
<script type="module" src="<?= \htmlspecialchars(RECOVERY_BEER_COLORS_JS) ?>"></script>
</body>
</html>
<?php
}

function recoveryRenderBackLink(string $href): void
{
    echo '<a href="' . \htmlspecialchars($href) . '" class="back-link"><i class="fa-solid fa-arrow-left"></i> Zurück zur Auswahl</a>';
}

function recoveryHomeUrl(string $authHash): string
{
    return recoveryBuildHomeUrl($authHash);
}

/**
 * @return list<array{label: string, value: string, mono?: bool}>
 */
function recoveryBuildRuntimeInfoRows(string $authHash, string $baseUrl): array
{
    $rows = [
        ['label' => 'Recovery-Tool Version', 'value' => RECOVERY_VERSION],
        ['label' => 'PHP-Version', 'value' => \PHP_VERSION],
        ['label' => 'Auth-Token (URL)', 'value' => recoveryHomeUrl($authHash)],
        ['label' => 'Forum-URL', 'value' => $baseUrl],
    ];

    if (\defined('WCF_N')) {
        $rows[] = ['label' => 'WCF_N (Tabellen-Suffix)', 'value' => (string) \constant('WCF_N')];
    }
    if (\defined('WCF_DIR')) {
        $rows[] = ['label' => 'WCF-Verzeichnis', 'value' => \rtrim((string) \constant('WCF_DIR'), '/\\')];
    }
    if (\defined('WCF_VERSION')) {
        $rows[] = ['label' => 'WoltLab Suite', 'value' => (string) \constant('WCF_VERSION')];
    }

    $wcfDir = \defined('WCF_DIR') ? \rtrim((string) \constant('WCF_DIR'), '/\\') . '/' : null;
    if ($wcfDir !== null) {
        $logHits = recoveryScanWoltLabLogForRecentErrors($wcfDir, 3);
        if ($logHits !== []) {
            $rows[] = ['label' => 'Letzter Log-Hinweis', 'value' => $logHits[\count($logHits) - 1]];
        }
    }

    return $rows;
}

function recoveryRenderCopyableRow(string $elementId, string $label, string $value): void
{
    $elementId = \preg_replace('/[^a-zA-Z0-9_-]/', '', $elementId) ?: 'val';
    echo '<div class="recovery-copy-row">';
    echo '<span class="recovery-copy-label">' . \htmlspecialchars($label) . '</span>';
    echo '<code class="recovery-copy-value" id="recovery-copy-' . \htmlspecialchars($elementId) . '">'
        . \htmlspecialchars($value) . '</code>';
    echo '<button type="button" class="recovery-copy-btn" data-recovery-copy="recovery-copy-'
        . \htmlspecialchars($elementId) . '" title="In Zwischenablage kopieren">'
        . '<i class="fa-solid fa-copy"></i> Kopieren</button>';
    echo '</div>';
}

function recoveryRenderCompactStatusBar(string $authHash, string $baseUrl): void
{
    $rows = recoveryBuildRuntimeInfoRows($authHash, $baseUrl);
    $logHint = '';
    foreach ($rows as $row) {
        if (($row['label'] ?? '') === 'Letzter Log-Hinweis') {
            $raw = (string) ($row['value'] ?? '');
            if (\str_contains($raw, 'ClassNotFound') || \str_contains($raw, 'Unable to find class')) {
                $logHint = 'ClassNotFound im Log';
            } elseif ($raw !== '') {
                $logHint = \mb_strlen($raw) > 48 ? \mb_substr($raw, 0, 45) . '…' : $raw;
            }
            break;
        }
    }
    ?>
    <p class="recovery-status-bar" aria-label="Kurzstatus">
        <span>Tool v<?= \htmlspecialchars(RECOVERY_VERSION) ?></span>
        <span class="recovery-status-sep">·</span>
        <span>PHP <?= \htmlspecialchars(\PHP_VERSION) ?></span>
        <?php if ($logHint !== ''): ?>
        <span class="recovery-status-sep">·</span>
        <span class="recovery-status-warn"><i class="fa-solid fa-triangle-exclamation"></i> <?= \htmlspecialchars($logHint) ?></span>
        <?php endif; ?>
        <span class="recovery-status-sep">·</span>
        <a href="#recovery-sysinfo" class="recovery-status-link">Systeminformationen</a>
    </p>
    <?php
}

function recoveryRenderRuntimeInfoPanel(string $authHash, string $baseUrl, bool $open = false): void
{
    $rows = recoveryBuildRuntimeInfoRows($authHash, $baseUrl);
    ?>
    <details class="recovery-info-panel recovery-info-panel--drawer" id="recovery-sysinfo"<?= $open ? ' open' : '' ?>>
        <summary><i class="fa-solid fa-circle-info"></i> System-Informationen</summary>
        <p class="recovery-info-hint">
            Für Support-Anfragen — per <strong>Kopieren</strong> in die Zwischenablage.
        </p>
        <div class="recovery-info-grid">
        <?php
        $i = 0;
    foreach ($rows as $row) {
        recoveryRenderCopyableRow('info' . $i++, (string) $row['label'], (string) $row['value']);
    }
    ?>
        </div>
    </details>
    <?php
}

function recoveryShouldOfferEmergencyClassNotFoundFix(string $wcfDir): bool
{
    if (recoveryExtractMissingClassesFromLog($wcfDir) !== []) {
        return true;
    }

    foreach (recoveryScanWoltLabLogForRecentErrors($wcfDir, 8) as $line) {
        if (\str_contains((string) $line, 'ClassNotFound') || \str_contains((string) $line, 'Unable to find class')) {
            return true;
        }
    }

    return false;
}

/**
 * @param array{bootstrapNeutralized?: list<string>, dbEventListenersDeleted?: int, cacheDeleted?: int, logClasses?: list<string>} $result
 */
function recoverySessionSetEmergencyFixed(string $authHash, array $result): void
{
    recoveryEnsureSession();
    $_SESSION['recovery_emergency'] ??= [];
    $_SESSION['recovery_emergency'][$authHash] = [
        'at' => \time(),
        'result' => $result,
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
 * @param array<string, mixed> $result
 */
function recoveryRenderAcpRecoveredGuidance(array $result, string $acpUrl, string $uninstallUrl): void
{
    $bootstrapCount = \count($result['bootstrapNeutralized'] ?? []);
    ?>
    <section class="recovery-rec-panel recovery-rec-panel--ok" style="margin-bottom:20px" aria-labelledby="recovery-acp-ok-heading">
        <h2 id="recovery-acp-ok-heading"><i class="fa-solid fa-circle-check"></i> ACP-Notfall erledigt</h2>
        <p style="margin:0 0 12px;line-height:1.55">
            Der ACP sollte wieder erreichbar sein
            (<?= $bootstrapCount ?> Bootstrap-Datei(en) angepasst, Cache geleert).
            Das war <strong>keine vollständige Deinstallation</strong> — nur der Absturz beim Start wurde behoben.
        </p>
        <p style="margin:0 0 10px;font-weight:600;color:#fff">Was Sie jetzt tun sollten:</p>
        <ol class="recovery-next-list" style="margin:0 0 14px">
            <li><a href="<?= \htmlspecialchars($acpUrl) ?>" style="color:#6EC2FF">ACP öffnen</a> und prüfen, ob alles lädt.</li>
            <li>Steht das Plugin noch unter <strong>Pakete</strong>?
                <ul style="margin:6px 0 0 18px">
                    <li><strong>Ja</strong> → dort normal deinstallieren <em>oder</em>
                        <a href="<?= \htmlspecialchars($uninstallUrl) ?>" style="color:#6EC2FF">Plugin entfernen</a> im Recovery Tool.</li>
                    <li><strong>Nein</strong> (nur WoltLab Core sichtbar) → Reste per
                        <a href="<?= \htmlspecialchars($uninstallUrl) ?>" style="color:#6EC2FF">Plugin entfernen</a>
                        mit Paket-ID <code>de.sunnyc.wsc.shrinkr</code> bereinigen (DB + optional Ordner <code>shrinkr/</code>).</li>
                </ul>
            </li>
            <li><strong>Nicht</strong> den Wizard nutzen, um fehlende Dateien wiederherzustellen — das wäre das Gegenteil einer Entfernung.</li>
            <li>Recovery Tool und Auth-Datei vom Server löschen, wenn alles erledigt ist.</li>
        </ol>
    </section>
    <?php
}

function recoveryRenderBreadcrumb(int $mode, string $authHash): void
{
    $home = recoveryHomeUrl($authHash);
    $parts = ['<a href="' . \htmlspecialchars($home) . '">Start</a>'];

    $labels = [
        RECOVERY_MODE_RECOVERY_WIZARD => 'Recovery-Wizard',
        RECOVERY_MODE_USER_MANAGEMENT => 'Admin-Konto',
        RECOVERY_MODE_PLUGIN_UNINSTALL => 'Plugin entfernen',
        RECOVERY_MODE_ACP_REPAIR => 'ACP Repair',
        RECOVERY_MODE_CACHE_CLEAR => 'Cache leeren',
        RECOVERY_MODE_PACKAGE_LIST_REPAIR => 'Paketliste',
        RECOVERY_MODE_PACKAGE_FILE_REPAIR => 'Dateien reparieren',
        RECOVERY_MODE_SYSTEM_CHECK => 'System-Check',
        RECOVERY_MODE_BACKUP_GUIDE => 'Datensicherung',
        RECOVERY_MODE_DIRECTORY_STRUCTURE => 'Verzeichnisstruktur',
    ];

    if (isset($labels[$mode])) {
        $parts[] = '<strong>' . \htmlspecialchars($labels[$mode]) . '</strong>';
    }

    if (\count($parts) < 2) {
        return;
    }

    echo '<nav class="recovery-breadcrumb" aria-label="Brotkrumen">'
        . \implode(' <span aria-hidden="true">›</span> ', $parts)
        . '</nav>';
}

function recoveryRenderExpertModesGrid(string $authHash): void
{
    ?>
    <div class="mode-grid">
        <a href="<?= \htmlspecialchars(recoveryBuildModeUrl(RECOVERY_MODE_ACP_REPAIR, $authHash)) ?>" class="mode-button">
            <i class="fa-solid fa-wrench"></i>
            <strong>ACP Repair</strong>
            <span>Defekte ACP-Menüeinträge eines Plugins</span>
        </a>
        <a href="<?= \htmlspecialchars(recoveryBuildModeUrl(RECOVERY_MODE_PLUGIN_UNINSTALL, $authHash)) ?>" class="mode-button">
            <i class="fa-solid fa-trash-can"></i>
            <strong>Plugin Uninstall</strong>
            <span>DB + Dateien — ohne Wizard</span>
        </a>
        <a href="<?= \htmlspecialchars(recoveryBuildModeUrl(RECOVERY_MODE_PACKAGE_LIST_REPAIR, $authHash)) ?>" class="mode-button">
            <i class="fa-solid fa-list-check"></i>
            <strong>Paketliste reparieren</strong>
            <span>Verwaiste Queue-/Application-Einträge</span>
        </a>
        <a href="<?= \htmlspecialchars(recoveryBuildModeUrl(RECOVERY_MODE_PACKAGE_FILE_REPAIR, $authHash)) ?>" class="mode-button">
            <i class="fa-solid fa-file-circle-plus"></i>
            <strong>Plugin-Dateien reparieren</strong>
            <span>Fehlende Klassen aus Paket-Archiv</span>
        </a>
        <a href="<?= \htmlspecialchars(recoveryBuildModeUrl(RECOVERY_MODE_CACHE_CLEAR, $authHash)) ?>" class="mode-button">
            <i class="fa-solid fa-broom"></i>
            <strong>Cache Clear</strong>
            <span>Nur Cache + Option-Fallback</span>
        </a>
        <a href="<?= \htmlspecialchars(recoveryBuildModeUrl(RECOVERY_MODE_RECOVERY_WIZARD, $authHash)) ?>" class="mode-button" style="border-color:#369">
            <i class="fa-solid fa-route"></i>
            <strong>Recovery-Wizard</strong>
            <span>Geführte Diagnose (empfohlen)</span>
        </a>
        <a href="<?= \htmlspecialchars(recoveryBuildModeUrl(RECOVERY_MODE_SYSTEM_CHECK, $authHash)) ?>" class="mode-button">
            <i class="fa-solid fa-stethoscope"></i>
            <strong>System-Check</strong>
            <span>PHP, Rechte, DB, Assets</span>
        </a>
        <a href="<?= \htmlspecialchars(recoveryBuildModeUrl(RECOVERY_MODE_BACKUP_GUIDE, $authHash)) ?>" class="mode-button">
            <i class="fa-solid fa-database"></i>
            <strong>Datensicherung</strong>
            <span>Backup-Befehle &amp; Checkliste</span>
        </a>
        <a href="<?= \htmlspecialchars(recoveryBuildModeUrl(RECOVERY_MODE_DIRECTORY_STRUCTURE, $authHash)) ?>" class="mode-button">
            <i class="fa-solid fa-folder-tree"></i>
            <strong>Verzeichnisstruktur</strong>
            <span>Applications &amp; Pfade prüfen</span>
        </a>
    </div>
    <?php
}

function recoveryRenderGlobalNav(int $mode, string $authHash, string $baseUrl): void
{
    $acpUrl = $baseUrl . 'acp/';
    echo '<nav class="recovery-global-nav" aria-label="Recovery-Navigation">';
    if ($mode !== RECOVERY_MODE_SELECTION) {
        echo '<a href="' . \htmlspecialchars(recoveryHomeUrl($authHash)) . '" class="recovery-nav-link">'
            . '<i class="fa-solid fa-house"></i> Zurück zum Start</a>';
    }
    echo '<a href="' . \htmlspecialchars($acpUrl) . '" class="recovery-nav-link recovery-nav-acp">'
        . '<i class="fa-solid fa-gauge-high"></i> Zum ACP</a>';
    if ($mode === RECOVERY_MODE_SELECTION) {
        echo '<a href="#recovery-sysinfo" class="recovery-nav-link">'
            . '<i class="fa-solid fa-circle-info"></i> Systeminfo</a>';
    }
    echo '<a href="' . \htmlspecialchars(recoveryBuildModeUrl(RECOVERY_MODE_SYSTEM_CHECK, $authHash)) . '" class="recovery-nav-link">'
        . '<i class="fa-solid fa-stethoscope"></i> System-Check</a>';
    echo '<a href="' . \htmlspecialchars(recoveryBuildModeUrl(RECOVERY_MODE_BACKUP_GUIDE, $authHash)) . '" class="recovery-nav-link">'
        . '<i class="fa-solid fa-database"></i> Backup</a>';
    echo '<a href="' . \htmlspecialchars(recoveryBuildModeUrl(RECOVERY_MODE_DIRECTORY_STRUCTURE, $authHash)) . '" class="recovery-nav-link">'
        . '<i class="fa-solid fa-folder-tree"></i> Pfade</a>';
    echo '</nav>';
}


// ============================================================================
// AUTHENTIFIZIERUNG (wie WoltLab wsc-recovery.php)
// ============================================================================

// #region agent log
recoveryAgentDebugLog('H3', 'tool:auth', 'before_branch', [
    'method' => $_SERVER['REQUEST_METHOD'] ?? '',
    'hasTokenParam' => isset($_REQUEST['t']),
    'tokenLooksValid' => !empty($_REQUEST['t']) && (bool) \preg_match('~^[a-f0-9]{40}$~', (string) $_REQUEST['t']),
]);
// #endregion

// Hash generieren bei erstem Aufruf
if (empty($_REQUEST['t']) || !preg_match('~^[a-f0-9]{40}$~', $_REQUEST['t'])) {
    // #region agent log
    recoveryAgentDebugLog('H3', 'tool:auth', 'branch_redirect_new_token', []);
    // #endregion
    $authHash = bin2hex(random_bytes(20));
    header("Location: plugin-recovery-tool.php?t={$authHash}");
    exit;
} else {
    $authHash = $_REQUEST['t'];
    // #region agent log
    recoveryAgentDebugLog('H3', 'tool:auth', 'branch_using_existing_token', ['tokenLen' => \strlen((string) $authHash)]);
    // #endregion
}

$action = (!empty($_GET['action'])) ? $_GET['action'] : '';

// Auth-Datei zum Download bereitstellen
if ($action === 'download-auth-file') {
    $expiresTimestamp = time() + 86400; // +1 Tag
    $content = "<?php exit; /* --- NICHT BEARBEITEN --- */ ?>\n{$expiresTimestamp}\n{$authHash}";

    header('Content-type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . $authFilename . '"');
    header('Content-Length: ' . strlen($content));
    header('Connection: close');

    echo $content;
    exit;
}

// SQL-Backup-Download (base64-kodierter Inhalt aus POST)
if ($action === 'download-sql') {
    $raw = $_POST['sql_b64'] ?? '';
    $sqlContent = \base64_decode(\str_replace(["\n", "\r", ' '], '', (string)$raw), true);
    if ($sqlContent === false || $sqlContent === '') {
        http_response_code(400);
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

// Auth-Datei prüfen (fester Dateiname in __DIR__, kein Benutzer-Pfad)
$isAuthenticated = false;
$authFilePath = __DIR__ . '/plugin-recovery-auth.php';
if (\is_file($authFilePath) && \is_readable($authFilePath)) {
    $authFileContent = \file_get_contents($authFilePath);
    if ($authFileContent !== false) {
        $lines = \explode("\n", $authFileContent);

        if (\count($lines) >= 3) {
            $expiresTimestamp = (int) $lines[1];
            $storedHash = \trim($lines[2]);

            if ($expiresTimestamp > \time() && \hash_equals($storedHash, $authHash)) {
                $isAuthenticated = true;
            }
        }
    }
}

// Cleanup-Action: Hilfsdateien löschen, Tool per Shutdown entfernen, Weiterleitung ins ACP
if ($action === 'cleanup') {
    $cleanupAcpUrl = recoveryGetSiteBaseUrl() . 'acp/';
    cleanupRecoveryAuxiliaryFiles();
    \register_shutdown_function(static function (): void {
        @\unlink(__DIR__ . '/plugin-recovery-tool.php');
    });
    \header('Location: ' . $cleanupAcpUrl);
    exit;
}

// Auth-Status (JSON) für Auto-Fortsetzung nach Upload der Auth-Datei
if ($action === 'auth-status') {
    \header('Content-Type: application/json; charset=utf-8');
    echo \json_encode(['ok' => $isAuthenticated]);
    exit;
}

// Vorschau: Zeilen einer Tabelle mit packageID (Plugin Uninstall Schritt 1)
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

/**
 * Löscht kompilierte Templates und Datei-Caches per Filesystem (ohne WCF/CacheHandler).
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

    $path = recoveryGetPluginClassFilePath($wcfDir, $listener);
    if ($path !== null && !\is_file($path)) {
        return true;
    }

    return !\recoveryIsPluginClassLoadable($listener);
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
    $logClasses = recoveryExtractMissingClassesFromLog($wcfDir);
    $log[] = '[Notfall-ACP] Log-Klassen: ' . ($logClasses === [] ? 'keine erkannt' : \implode(', ', $logClasses));

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
    $log[] = '[Notfall-ACP] Cache-Dateien gelöscht: ' . $cacheDeleted;

    return [
        'bootstrapNeutralized' => $bootstrapNeutralized,
        'dbEventListenersDeleted' => $dbDeleted,
        'cacheDeleted' => $cacheDeleted,
        'logClasses' => $logClasses,
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
 * Ermittelt file-Instructions aus package.xml (angelehnt an wspackager / build.sh parse_package_instructions).
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

    return [
        'missingBootstrapClasses' => $missing,
        'orphanApplicationCount' => $orphanCount,
        'logExcerpts' => $logExcerpts,
        'logReportedMissingClasses' => $logReportedMissingClasses,
        'bootstrapNeutralizeCandidates' => $neutralizeCandidates,
        'orphanedDbEventListeners' => $orphanedDbListeners,
        'suggestedActions' => [
            'orphans' => $orphanCount > 0,
            'files' => $missing !== [],
            'neutralizeBootstrap' => $neutralizeCandidates > 0 || $logReportedMissingClasses !== [],
            'dbEventListeners' => $orphanedDbListeners !== [],
            'cache' => true,
        ],
    ];
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

    $severity = 'ok';
    if ($neutral > 0 || $orphDb > 0 || $missing > 0 || $logClassNotFound) {
        $severity = 'critical';
    } elseif ($orphans > 0) {
        $severity = 'warning';
    }

    $pkgHint = $packageLabel !== null && $packageLabel !== ''
        ? ' für <code>' . \htmlspecialchars($packageLabel, ENT_QUOTES, 'UTF-8') . '</code>'
        : '';

    $headline = match ($severity) {
        'critical' => 'Das ACP ist voraussichtlich wegen Plugin-Resten blockiert',
        'warning' => 'Kein schwerer Dateifehler — DB-Bereinigung empfohlen',
        default => 'Keine kritischen Fehler im gewählten Umfang',
    };

    $summary = match ($severity) {
        'critical' => 'Typisch: Beim Aufruf von <code>/acp/</code> bricht das Dashboard ab, weil PHP eine Plugin-Klasse laden '
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
                . ($logClassName ? ' Log-Klasse: <code>' . \htmlspecialchars($logClassName, ENT_QUOTES, 'UTF-8') . '</code>.' : ''),
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

    $logHint = $logClassNotFound
        ? 'Im WoltLab-Log wurde kürzlich eine ClassNotFound-Meldung gefunden.'
        : null;

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
    $cls = 'recovery-rec-panel recovery-rec-panel--' . \htmlspecialchars($rec['severity'] ?? 'ok');
    ?>
    <section class="<?= $cls ?>" aria-labelledby="recovery-rec-heading">
        <h2 id="recovery-rec-heading"><i class="fa-solid fa-lightbulb"></i> <?= \htmlspecialchars($rec['headline'] ?? '') ?></h2>
        <p class="recovery-rec-summary"><?= $rec['summary'] ?? '' ?></p>
        <?php if (!empty($rec['logHint'])): ?>
        <p class="recovery-rec-summary" style="margin-top:-8px;color:#fc6"><i class="fa-solid fa-triangle-exclamation"></i> <?= \htmlspecialchars($rec['logHint']) ?></p>
        <?php endif; ?>
        <?php if (!empty($rec['steps'])): ?>
        <p style="margin:0 0 10px;font-weight:600;color:#fff;font-size:14px">Empfohlene Reihenfolge im nächsten Schritt:</p>
        <ul class="recovery-rec-steps">
        <?php foreach ($rec['steps'] as $step): ?>
            <li class="recovery-rec-step <?= !empty($step['required']) ? 'recovery-rec-step--required' : 'recovery-rec-step--optional' ?>">
                <?php if (!empty($step['required'])): ?>
                <span class="recovery-rec-badge recovery-rec-badge--required">Wichtig</span>
                <?php elseif (!empty($step['recommended'])): ?>
                <span class="recovery-rec-badge recovery-rec-badge--recommended">Empfohlen</span>
                <?php else: ?>
                <span class="recovery-rec-badge recovery-rec-badge--optional">Optional</span>
                <?php endif; ?>
                <strong><?= $step['title'] ?? '' ?></strong>
                <p><?= $step['why'] ?? '' ?></p>
            </li>
        <?php endforeach; ?>
        </ul>
        <?php endif; ?>
        <?php if (!empty($rec['afterAcp'])): ?>
        <p style="margin:16px 0 6px;font-weight:600;color:#fff;font-size:14px">Danach (wenn der ACP wieder lädt):</p>
        <ol class="recovery-next-list">
        <?php foreach ($rec['afterAcp'] as $item): ?>
            <li><?= $item ?></li>
        <?php endforeach; ?>
        </ol>
        <?php endif; ?>
    </section>
    <?php
}

/**
 * @param list<string> $logExcerpts
 */
/**
 * @param list<string> $missingClasses
 */
function recoveryRenderWizardMissingClassesDetails(array $missingClasses): void
{
    if ($missingClasses === []) {
        return;
    }
    ?>
    <details class="recovery-info-panel" style="margin-bottom:16px">
        <summary style="cursor:pointer;font-weight:600;color:#fff">
            Fehlende Klassen (<?= \count($missingClasses) ?>) — Details
        </summary>
        <?php foreach (recoveryGroupFqcnByApplicationPrefix($missingClasses) as $app => $classes): ?>
        <p style="margin:12px 0 4px"><strong>App <code><?= \htmlspecialchars($app) ?></code></strong></p>
        <ul style="margin:0 0 8px 20px;font-size:13px"><?php foreach ($classes as $cn): ?>
            <li><code><?= \htmlspecialchars($cn) ?></code></li>
        <?php endforeach; ?></ul>
        <?php endforeach; ?>
    </details>
    <?php
}

function recoveryRenderLogExcerptsPanel(array $logExcerpts, string $panelId = 'wizard-log'): void
{
    if ($logExcerpts === []) {
        return;
    }
    $text = \implode("\n", $logExcerpts);
    ?>
    <details class="recovery-info-panel" style="margin-bottom:16px">
        <summary style="cursor:pointer;font-weight:700;color:#fff">Log-Auszug (WoltLab <code>log/*.txt</code>)</summary>
        <p style="margin:12px 0 8px;font-size:13px;color:#9D9D9D">
            Letzte relevante Zeilen — oft steht hier die exakte fehlende Klasse.
        </p>
        <button type="button" class="recovery-copy-btn" data-recovery-copy="<?= \htmlspecialchars($panelId) ?>" style="margin-bottom:10px">
            <i class="fa-solid fa-copy"></i> Gesamten Log-Auszug kopieren
        </button>
        <pre class="recoveryLog" id="<?= \htmlspecialchars($panelId) ?>"><?= \htmlspecialchars($text) ?></pre>
    </details>
    <?php
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
        $uploadBase = \realpath(__DIR__ . '/uploads');
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
 * @return array{copiedFiles: list<string>, cacheDeleted: int, bootstrapNeutralized: list<string>, dbEventListenersDeleted: int, dryRun: bool}
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
    $scopeApp = isset($plan['scopeApplication']) && (string) $plan['scopeApplication'] !== ''
        ? (string) $plan['scopeApplication']
        : null;

    if ($dryRun) {
        $log[] = $pfx . 'Keine Änderungen am Server — nur Vorschau.';
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

    if (!empty($plan['cache'])) {
        if ($dryRun) {
            $log[] = $pfx . '[Cache] WÜRDE kompilierte Templates löschen und options.inc.php-Fallback aktualisieren.';
        } else {
            $cacheDeleted = clearCompiledTemplates();
            $optionFbLog = [];
            recoveryEnsureOptionConstantFallbacks($db, $wcfN, $optionFbLog);
            $log[] = '[Cache] Gelöschte Cache-Dateien: ' . $cacheDeleted;
            foreach ($optionFbLog as $entry) {
                $log[] = '[Cache] ' . $entry;
            }
        }
    }

    return [
        'copiedFiles' => $copiedFiles,
        'cacheDeleted' => $cacheDeleted,
        'bootstrapNeutralized' => $bootstrapNeutralized,
        'dbEventListenersDeleted' => $dbEventListenersDeleted,
        'dryRun' => $dryRun,
    ];
}

/**
 * @param list<string> $labels
 */
function recoveryRenderWizardPhaseSteps(int $activeIndex, array $labels): void
{
    echo '<div class="wizardSteps" style="margin-bottom:24px">';
    foreach ($labels as $i => $label) {
        $cls = 'wizardStep';
        if ($i < $activeIndex) {
            $cls .= ' completed';
        } elseif ($i === $activeIndex) {
            $cls .= ' active';
        }
        echo '<div class="' . $cls . '">';
        echo '<div class="wizardStepNumber">' . ($i + 1) . '</div>';
        echo '<div class="wizardStepLabel">' . \htmlspecialchars($label) . '</div>';
        echo '</div>';
    }
    echo '</div>';
}

/**
 * Entfernt angelegte Recovery-NDJSON-Debug-Logs (WCF log/, Tool log/, Legacy neben Tool).
 */
function recoveryCleanupRecoveryDebugLogs(): void
{
    $globPatterns = [
        __DIR__ . '/log/recovery-tool-*.ndjson',
        __DIR__ . '/log/plugin-recovery-*.ndjson',
    ];

    $wcfDir = recoveryResolveWcfDirForLogging();
    if ($wcfDir !== null) {
        $wcfLogDir = \rtrim($wcfDir, '/\\') . '/log/';
        $globPatterns[] = $wcfLogDir . 'recovery-tool-*.ndjson';
        $globPatterns[] = $wcfLogDir . 'plugin-recovery-*.ndjson';
    }

    foreach ($globPatterns as $pattern) {
        foreach (\glob($pattern) ?: [] as $file) {
            if (\is_file($file)) {
                @\unlink($file);
            }
        }
    }

    $legacyBeside = __DIR__ . '/plugin-recovery-agent-debug.ndjson';
    if (\is_file($legacyBeside)) {
        @\unlink($legacyBeside);
    }

    $besideLogDir = __DIR__ . '/log/';
    if (\is_dir($besideLogDir) && (\glob($besideLogDir . '*') ?: []) === []) {
        @\rmdir($besideLogDir);
    }
}

/**
 * Löscht Recovery-Hilfsdateien (nicht plugin-recovery-tool.php – das erfolgt per Shutdown).
 */
function cleanupRecoveryAuxiliaryFiles(): void
{
    recoveryCleanupRecoveryDebugLogs();

    $files = [
        __DIR__ . '/plugin-recovery.php',
        __DIR__ . '/universal-recovery.php',
        __DIR__ . '/acp-repair.php',
        __DIR__ . '/wsc-recovery.php',
        __DIR__ . '/recovery-tool.php',
        __DIR__ . '/plugin-recovery-auth.php',
        __DIR__ . '/uploads',
    ];

    foreach ($files as $file) {
        if (\is_file($file)) {
            @\unlink($file);
        } elseif (\is_dir($file)) {
            $it = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($file, \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::CHILD_FIRST
            );
            foreach ($it as $f) {
                $f->isDir() ? @\rmdir($f) : @\unlink($f);
            }
            @\rmdir($file);
        }
    }
}

/** @deprecated Verwende cleanupRecoveryAuxiliaryFiles() */
function cleanupRecoveryFiles(): void
{
    cleanupRecoveryAuxiliaryFiles();
    @\unlink(__DIR__ . '/plugin-recovery-tool.php');
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
    echo '<div class="alert alert-info"><strong>Gefundene Ressourcen aus Package-Datei:</strong><br>';
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

$mode = recoveryResolveRequestMode();
$recoveryBootstrapError = null;
$recoveryDb = null;

if ($isAuthenticated) {
    try {
        $recoveryDb = recoveryBootstrapDatabase();
        recoveryMaybeRedirectUninstallAnalyse($authHash);
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

recoveryRenderPageStart('Plugin Recovery Tool', 'Plugin Recovery Tool');

// Auth-Screen anzeigen wenn nicht authentifiziert
if (!$isAuthenticated) {
?>
    <h1>Plugin Recovery Tool</h1>
    <p class="subtitle">Authentifizierung erforderlich</p>

    <div class="wizardSteps" id="authWizardSteps">
        <div class="wizardStep active">
            <div class="wizardStepNumber">1</div>
            <div class="wizardStepLabel">Auth-Datei laden</div>
        </div>
        <div class="wizardStep">
            <div class="wizardStepNumber">2</div>
            <div class="wizardStepLabel">Hochladen</div>
        </div>
        <div class="wizardStep">
            <div class="wizardStepNumber">3</div>
            <div class="wizardStepLabel">Starten</div>
        </div>
    </div>

    <div class="wizardPanel active" id="wp1">
        <div class="alert alert-info">
            <strong><i class="fa-solid fa-file-arrow-down"></i> Schritt 1: Auth-Datei herunterladen</strong><br>
            Laden Sie die Authentifizierungsdatei herunter. Sie enthält ein einmaliges Token
            und wird benötigt, um Ihren Zugriff auf dieses Tool zu verifizieren.
        </div>
        <a href="?action=download-auth-file&amp;t=<?= htmlspecialchars($authHash) ?>" class="button" id="downloadBtn">
            <i class="fa-solid fa-file-arrow-down"></i> plugin-recovery-auth.php herunterladen
        </a>
    </div>

    <div class="wizardPanel" id="wp2">
        <div class="alert alert-info">
            <strong><i class="fa-solid fa-file-arrow-up"></i> Schritt 2: Datei hochladen</strong><br>
            Laden Sie die heruntergeladene Datei <code><?= htmlspecialchars($authFilename) ?></code> in dasselbe
            Verzeichnis hoch, in dem sich diese <code>plugin-recovery-tool.php</code> befindet.<br><br>
            <small>Nutzen Sie FTP, SFTP oder den Dateimanager Ihres Hosters. Das Tool erkennt den Upload automatisch.</small>
        </div>
        <button type="button" class="button" id="uploadedBtn">
            <i class="fa-solid fa-circle-check"></i> Ich habe die Datei hochgeladen
        </button>
        <span id="pollStatus" style="display:inline-block;margin-left:14px;color:#9D9D9D;font-size:13px;"></span>
    </div>

    <div class="wizardPanel" id="wp3">
        <div class="alert alert-success">
            <strong><i class="fa-solid fa-circle-check"></i> Authentifizierung erfolgreich!</strong><br>
            Die Auth-Datei wurde erkannt. Sie können das Recovery Tool jetzt starten.
        </div>
        <a href="?t=<?= htmlspecialchars($authHash) ?>&amp;auth_ok=1" class="button btn-success" style="font-size:16px;padding:16px 32px;">
            <i class="fa-solid fa-rocket"></i> Recovery Tool starten
        </a>
    </div>

    <div class="alert alert-error" style="margin-top: 30px;">
        <i class="fa-solid fa-shield-halved"></i> <strong>Sicherheitshinweis:</strong><br>
        Löschen Sie beide Dateien (<code>plugin-recovery-tool.php</code> und <code><?= htmlspecialchars($authFilename) ?></code>)
        nach der Verwendung. Diese Dateien können ein Sicherheitsrisiko darstellen!
    </div>

    <script>
    (function () {
        var authToken = <?= \json_encode($authHash) ?>;
        var pollInterval = null;

        function goToStep(n) {
            document.querySelectorAll('#authWizardSteps .wizardStep').forEach(function (el, i) {
                el.classList.remove('active', 'completed');
                if (i + 1 < n) { el.classList.add('completed'); }
                if (i + 1 === n) { el.classList.add('active'); }
            });
            document.querySelectorAll('.wizardPanel').forEach(function (el, i) {
                el.classList.toggle('active', i + 1 === n);
            });
        }

        document.getElementById('downloadBtn').addEventListener('click', function () {
            setTimeout(function () { goToStep(2); }, 800);
        });

        document.getElementById('uploadedBtn').addEventListener('click', function () {
            document.getElementById('pollStatus').textContent = 'Prüfe Upload\u2026';
            startAuthPolling();
        });

        function startAuthPolling() {
            if (pollInterval) { clearInterval(pollInterval); }
            pollInterval = setInterval(function () {
                fetch('?action=auth-status&t=' + encodeURIComponent(authToken))
                    .then(function (r) { return r.json(); })
                    .then(function (data) {
                        if (data.ok) {
                            clearInterval(pollInterval);
                            goToStep(3);
                        } else {
                            document.getElementById('pollStatus').textContent = 'Datei noch nicht gefunden \u2013 prüfe erneut\u2026';
                        }
                    })
                    .catch(function () {});
            }, 2000);
        }
    }());
    </script>
<?php
    recoveryRenderPageEnd();
    exit;
}

// Ab hier ist der User authentifiziert

if ($recoveryBootstrapError !== null) {
    echo '<div class="alert alert-error"><strong>Bootstrap-Fehler:</strong> '
        . \nl2br(\htmlspecialchars(recoveryFormatUserError($recoveryBootstrapError))) . '</div>';
    recoveryRenderExceptionDetails($recoveryBootstrapError);
    recoveryRenderPageEnd();
    exit;
}

$recoveryBaseUrl = recoveryGetSiteBaseUrl();
try {
    $db = \wcf\system\WCF::getDB();
} catch (\Throwable $e) {
    echo '<div class="alert alert-error"><strong>Datenbank nicht verfügbar:</strong> '
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
        recoverySessionSetEmergencyFixed($authHash, $emergencyAcpResult);
        if (\session_status() === PHP_SESSION_ACTIVE) {
            \session_write_close();
        }
        \header(
            'Location: ' . recoveryBuildHomeUrl($authHash, [
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
if ($emergencyFixedSession !== null && isset($_GET['acp_fixed'])) {
    $emergencyAcpResult = $emergencyFixedSession['result'];
}

recoveryRenderGlobalNav($mode, $authHash, $recoveryBaseUrl);
recoveryRenderBreadcrumb($mode, $authHash);

?>

<?php
// ============================================================================
// MODUS 0: START / SZENARIO-AUSWAHL
// ============================================================================

if ($mode === RECOVERY_MODE_SELECTION) {
    recoveryRenderFlashSnackbarFromQuery();
    $wizardStartUrl = recoveryBuildModeUrl(RECOVERY_MODE_RECOVERY_WIZARD, $authHash);
    $adminUrl = recoveryBuildModeUrl(RECOVERY_MODE_USER_MANAGEMENT, $authHash);
    $uninstallUrl = recoveryBuildModeUrl(RECOVERY_MODE_PLUGIN_UNINSTALL, $authHash);
    $expertOpen = !empty($_GET['expert']);
    $sysinfoOpen = !empty($_GET['sysinfo']);
    $acpUrl = $recoveryBaseUrl . 'acp/';
    $showEmergencyFix = recoveryShouldOfferEmergencyClassNotFoundFix($wcfDirMain)
        && $emergencyFixedSession === null;
?>
    <?php if (isset($_GET['auth_ok'])): ?>
    <div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> <strong>Anmeldung erfolgreich.</strong> Wählen Sie unten, was auf Ihrer Installation zutrifft.</div>
    <?php endif; ?>

    <header class="recovery-intake-hero">
        <h1>WoltLab Recovery Tool</h1>
        <p class="subtitle" style="max-width:720px">
            Geführte Hilfe bei Notfällen — zuerst die Situation wählen. Für defekte Plugins und ein nicht erreichbares ACP
            ist der <strong>Recovery-Wizard</strong> der empfohlene Weg (Diagnose → Plan → Ausführung).
        </p>
    </header>

    <?php recoveryRenderCompactStatusBar($authHash, $recoveryBaseUrl); ?>
    <?php recoveryRenderRuntimeInfoPanel($authHash, $recoveryBaseUrl, $sysinfoOpen); ?>

    <?php if ($emergencyAcpResult !== null && !empty($emergencyAcpResult['error'])): ?>
    <div class="alert alert-error" style="margin-bottom:20px">
        <strong>Notfall-Reparatur fehlgeschlagen:</strong> <?= \htmlspecialchars((string) $emergencyAcpResult['error']) ?>
    </div>
    <?php elseif ($emergencyFixedSession !== null || ($emergencyAcpResult !== null && empty($emergencyAcpResult['error']))): ?>
    <?php
        $guidanceResult = \is_array($emergencyAcpResult) ? $emergencyAcpResult : ($emergencyFixedSession['result'] ?? []);
        recoveryRenderAcpRecoveredGuidance($guidanceResult, $acpUrl, $uninstallUrl);
    ?>
    <?php endif; ?>

    <?php if ($showEmergencyFix): ?>
    <section class="recovery-scenario-card" style="margin-bottom:24px;border-color:#c60;background:rgba(204,102,0,0.08)">
        <h2 style="margin:0 0 8px;font-size:17px"><i class="fa-solid fa-bolt"></i> Sofort: ACP zeigt ClassNotFound</h2>
        <p style="margin:0 0 10px;color:#ccc;font-size:14px;line-height:1.5">
            <strong>Automatisch erkannt</strong> — im WoltLab-Log steht eine <code>ClassNotFoundException</code>
            (siehe Kurzstatus oben). Ein Klick deaktiviert die betroffenen Bootstrap-<code>register()</code>-Aufrufe,
            bereinigt DB-Listener und leert den Cache.
        </p>
        <form method="POST" action="<?= \htmlspecialchars(recoveryBuildHomeUrl($authHash)) ?>"
            data-recovery-loading="Notfall-Reparatur läuft (Bootstrap, DB, Cache) …"
            data-recovery-confirm="Bootstrap-Register werden auskommentiert (mit Backup), DB-Listener gelöscht, Cache geleert. Fortfahren?"
            data-recovery-confirm-title="ACP ClassNotFound beheben"
            data-recovery-confirm-ok="Jetzt beheben">
            <?php recoveryRenderFormModeHiddenFields(RECOVERY_MODE_SELECTION, $authHash); ?>
            <input type="hidden" name="emergency_acp_fix" value="1">
            <button type="submit" class="btn-danger"><i class="fa-solid fa-bolt"></i> ACP ClassNotFound jetzt beheben</button>
        </form>
    </section>
    <?php endif; ?>

    <h2 style="margin:0 0 16px;font-size:18px;color:#fff">Was ist passiert?</h2>
    <div class="recovery-scenario-grid">
        <a href="<?= \htmlspecialchars($wizardStartUrl) ?>" class="recovery-scenario-card recovery-scenario-card--primary">
            <i class="fa-solid fa-route recovery-scenario-icon" aria-hidden="true"></i>
            <h2>Plugin defekt / ACP nicht erreichbar</h2>
            <p>
                Wenn <code>/acp/</code> nicht lädt: Schritt für Schritt Diagnose, Reparatur, optional Dateien aus dem
                Paket-Archiv. <strong>ACP läuft bereits?</strong> Dann nicht den Wizard für „Dateien wiederherstellen“ —
                stattdessen Plugin entfernen (rechte Karte).
            </p>
            <span class="recovery-scenario-cta">Recovery-Wizard starten →</span>
        </a>

        <a href="<?= \htmlspecialchars($adminUrl) ?>" class="recovery-scenario-card">
            <i class="fa-solid fa-user-shield recovery-scenario-icon" aria-hidden="true"></i>
            <h2>Admin-Zugang wiederherstellen</h2>
            <p>
                Passwort vergessen, kein Zugang zum ACP, Administrator-Rechte nötig — Benutzerkonto und Berechtigungen
                direkt in der Datenbank anpassen.
            </p>
            <span class="recovery-scenario-cta">User Management →</span>
        </a>

        <a href="<?= \htmlspecialchars($uninstallUrl) ?>" class="recovery-scenario-card">
            <i class="fa-solid fa-trash-can recovery-scenario-icon" aria-hidden="true"></i>
            <h2>Plugin gezielt entfernen</h2>
            <p>
                Wie Deinstallieren im ACP, aber direkt über DB + optional Dateien — wenn das ACP wieder geht oder das
                Paket in der Paketliste fehlt, Reste aber noch da sind (<code>de.sunnyc.wsc.shrinkr</code> usw.).
            </p>
            <span class="recovery-scenario-cta">Plugin Uninstall →</span>
        </a>
    </div>

    <details class="recovery-expert-panel" id="recovery-expert-panel"<?= $expertOpen ? ' open' : '' ?>>
        <summary>Experten: Einzelmodi manuell (optional)</summary>
        <div class="recovery-expert-body">
            <p style="margin:0 0 16px;color:#9D9D9D;font-size:14px;line-height:1.55">
                Nur nutzen, wenn Sie genau wissen, welcher Schritt nötig ist. Für die meisten Fälle reicht der
                <a href="<?= \htmlspecialchars($wizardStartUrl) ?>" style="color:#6EC2FF">Recovery-Wizard</a>.
            </p>
            <?php recoveryRenderExpertModesGrid($authHash); ?>
        </div>
    </details>

    <div class="alert alert-info" style="margin-top:24px">
        <i class="fa-solid fa-circle-info"></i>
        <strong>Hinweis:</strong> Das Tool arbeitet direkt auf dem Server (Datenbank &amp; Dateien). Nur im Notfall verwenden.
        Nach erfolgreicher Recovery alle Recovery-Dateien vom Webspace entfernen.
    </div>

    <div class="alert alert-warning" style="margin-top: 20px;">
        <i class="fa-solid fa-triangle-exclamation"></i> <strong>Fertig mit Recovery?</strong><br>
        Wenn alles wieder funktioniert, Recovery Tool und Auth-Datei löschen.<br><br>
        <a href="?action=cleanup&amp;t=<?= \htmlspecialchars($authHash) ?>" class="button btn-danger" onclick="return confirm('ACHTUNG: Das Recovery Tool wird entfernt (Auth-Datei, Uploads, diese PHP-Datei) und Sie werden ins ACP weitergeleitet. Fortfahren?')">
            <i class="fa-solid fa-xmark"></i> Recovery Tool vollständig entfernen
        </a>
    </div>

<?php
}

elseif ($mode === RECOVERY_MODE_ACP_REPAIR) {
?>
    <h1>ACP Repair</h1>
    <p class="subtitle">Repariert defekte ACP-Menüeinträge eines Plugins</p>

<?php
    if (recoveryWasPostTruncated()) {
        recoveryRenderPostTruncatedWarning();
    }

    $acpModeUrl = recoveryBuildModeUrl(RECOVERY_MODE_ACP_REPAIR, $authHash);

    if (recoveryAcpShouldShowInputForm()) {
?>
    <form method="POST" enctype="multipart/form-data" action="<?= \htmlspecialchars($acpModeUrl) ?>" data-recovery-loading="Paket wird analysiert …">
        <?php recoveryRenderFormModeHiddenFields(RECOVERY_MODE_ACP_REPAIR, $authHash); ?>
        <div class="form-group">
            <label>Option 1: Package-Identifier manuell eingeben</label>
            <input type="text" name="package_identifier" placeholder="z.B. de.example.my-plugin" autocomplete="off">
            <small style="display: block; margin-top: 5px;">
                Der eindeutige Bezeichner des Plugins, dessen ACP-Menüeinträge repariert werden sollen.
            </small>
        </div>
        <button type="submit"><i class="fa-solid fa-wrench"></i> Mit Identifier reparieren</button>
    </form>

    <hr>

    <form method="POST" enctype="multipart/form-data" action="<?= \htmlspecialchars($acpModeUrl) ?>" data-recovery-loading="Paket wird hochgeladen und analysiert …">
        <?php recoveryRenderFormModeHiddenFields(RECOVERY_MODE_ACP_REPAIR, $authHash); ?>
        <div class="form-group">
            <label>Option 2: Package-Datei hochladen (.tar, .tar.gz, .tgz – max. 100 MiB)</label>
            <input type="file" name="package_file" accept=".tar,.tar.gz,.tgz" required>
        </div>
        <button type="submit"><i class="fa-solid fa-wrench"></i> Mit Datei reparieren</button>
    </form>
<?php
    } else {
        echo '<div id="recovery-loading-overlay" class="recovery-loading" style="display:block">Paket wird analysiert …</div>';
        try {
        $packageInput = recoveryResolvePackageInputFromRequest($authHash);
        if (isset($packageInput['error'])) {
            echo '<div class="alert alert-error"><strong>Fehler:</strong> '
                . \htmlspecialchars($packageInput['error']) . '</div>';
        }

        $packageIdentifier = $packageInput['packageIdentifier'] ?? null;
        $extractDir = $packageInput['extractDir'] ?? recoveryResolveTrustedExtractDir();

        if ($packageIdentifier) {
            $resources = null;
            if ($extractDir && \is_dir($extractDir)) {
                $resources = analyzePackageResources($extractDir, $packageIdentifier, $db);
                if ($resources && !empty($resources['acpMenu']['prefix'])) {
                    displayResourcePreview($resources, $resources['wcfN'], $packageIdentifier);
                }
            }

            // Package suchen
            $sql = "SELECT packageID, package, packageName, packageDir, isApplication
                    FROM wcf" . WCF_N . "_package
                    WHERE package = ?";
            $statement = $db->prepareStatement($sql);
            $statement->execute([$packageIdentifier]);
            $packageData = $statement->fetchArray();

            $wcfN = $resources ? (int) $resources['wcfN'] : WCF_N;

            if (!$packageData && !isset($_POST['force_cleanup'])) {
                $menuItems = recoveryFetchAcpMenuItemsForPackage($db, $wcfN, $packageIdentifier, null, $resources);
                $foundPatterns = recoveryInferAcpMenuSearchPatterns($packageIdentifier, $resources);
                $menuCount = \count($menuItems);

                echo '<div class="alert alert-info"><strong>Warnung:</strong> Plugin nicht in Datenbank gefunden.<br>';
                echo 'Dies kann bedeuten, dass die Installation fehlgeschlagen ist.<br><br>';

                if ($menuCount > 0) {
                    echo '<strong>Gefundene ACP-Menüeinträge (' . $menuCount . '):</strong><br>';
                    if ($foundPatterns !== []) {
                        echo '<small>Suchmuster: ' . \htmlspecialchars(\implode(', ', $foundPatterns)) . '</small><br><br>';
                    }
                    echo '<table><thead><tr><th>Menu Item</th><th>Controller</th></tr></thead><tbody>';
                    foreach ($menuItems as $item) {
                        echo '<tr><td>' . \htmlspecialchars($item['menuItem']) . '</td>';
                        echo '<td>' . \htmlspecialchars($item['menuItemController'] ?: '-') . '</td></tr>';
                    }
                    echo '</tbody></table><br>';
                    echo '<form method="POST" enctype="multipart/form-data" action="' . \htmlspecialchars(recoveryBuildModeUrl(RECOVERY_MODE_ACP_REPAIR, $authHash)) . '">';
                    echo '<input type="hidden" name="mode" value="' . RECOVERY_MODE_ACP_REPAIR . '">';
                    echo '<input type="hidden" name="t" value="' . \htmlspecialchars($authHash, ENT_QUOTES, 'UTF-8') . '">';
                    echo '<input type="hidden" name="package_identifier" value="' . \htmlspecialchars($packageIdentifier) . '">';
                    if ($extractDir) {
                        echo '<input type="hidden" name="extract_dir" value="' . \htmlspecialchars($extractDir) . '">';
                    }
                    echo '<input type="hidden" name="force_cleanup" value="1">';
                    echo '<button type="submit" class="btn-danger"><i class="fa-solid fa-trash-can"></i> Diese ' . $menuCount . ' Menüeinträge löschen</button>';
                    echo '</form>';
                } else {
                    echo '<strong>Keine ACP-Menüeinträge mit den ermittelten Mustern gefunden.</strong><br>';
                    echo 'Es gibt nichts zu bereinigen.';
                }
                echo '</div>';
            } else {
                $menuItems = recoveryFetchAcpMenuItemsForPackage($db, $wcfN, $packageIdentifier, $packageData ?: null, $resources);

                if (empty($menuItems)) {
                    echo '<div class="alert alert-info">';
                    echo '<strong>Keine ACP-Menüeinträge gefunden</strong><br>';
                    echo 'Für dieses Plugin existieren keine ACP-Menüeinträge in der Datenbank.';
                    echo '</div>';
                } elseif (!isset($_POST['confirm_delete'])) {
                    echo '<div class="alert alert-info">';
                    echo '<strong>Gefundene ACP-Menüeinträge (' . \count($menuItems) . '):</strong>';
                    echo '<table><thead><tr><th>Menu Item</th><th>Controller</th></tr></thead><tbody>';
                    foreach ($menuItems as $item) {
                        echo '<tr><td>' . \htmlspecialchars($item['menuItem']) . '</td>';
                        echo '<td>' . \htmlspecialchars($item['menuItemController'] ?: '-') . '</td></tr>';
                    }
                    echo '</tbody></table>';
                    echo '<form method="POST" enctype="multipart/form-data" action="' . \htmlspecialchars(recoveryBuildModeUrl(RECOVERY_MODE_ACP_REPAIR, $authHash)) . '">';
                    echo '<input type="hidden" name="mode" value="' . RECOVERY_MODE_ACP_REPAIR . '">';
                    echo '<input type="hidden" name="t" value="' . \htmlspecialchars($authHash, ENT_QUOTES, 'UTF-8') . '">';
                    echo '<input type="hidden" name="package_identifier" value="' . \htmlspecialchars($packageIdentifier) . '">';
                    if ($extractDir) {
                        echo '<input type="hidden" name="extract_dir" value="' . \htmlspecialchars($extractDir) . '">';
                    }
                    if (!$packageData) {
                        echo '<input type="hidden" name="force_cleanup" value="1">';
                    }
                    echo '<input type="hidden" name="confirm_delete" value="1">';
                    echo '<button type="submit" class="btn-danger"><i class="fa-solid fa-trash-can"></i> Alle löschen</button>';
                    echo '</form>';
                    echo '</div>';
                } else {
                    $extractDir = recoveryResolveTrustedExtractDir();
                    if ($extractDir && \is_dir($extractDir)) {
                        $resources = analyzePackageResources($extractDir, $packageIdentifier, $db);
                    }
                    $wcfN = $resources ? (int) $resources['wcfN'] : WCF_N;

                    $db->beginTransaction();
                    try {
                        $deletedCount = recoveryDeleteAcpMenuItemsForPackage(
                            $db,
                            $wcfN,
                            $packageIdentifier,
                            $packageData ?: null,
                            $resources
                        );
                        clearCompiledTemplates();
                        $optionFbLog = [];
                        recoveryEnsureOptionConstantFallbacks($db, $wcfN, $optionFbLog);
                        $db->commitTransaction();
                        recoveryCleanupUploadWorkspace();

                        echo '<div class="alert alert-success">';
                        echo '<strong>✓ ACP-Repair erfolgreich!</strong><br>';
                        echo 'Gelöschte Menüeinträge: ' . $deletedCount . '<br>';
                        echo 'Cache wurde geleert.<br>';
                        foreach ($optionFbLog as $fbEntry) {
                            echo \htmlspecialchars($fbEntry) . '<br>';
                        }
                        echo '</div>';
                    } catch (\Throwable $e) {
                        recoverySafeRollBackTransaction($db);
                        echo '<div class="alert alert-error">';
                        echo '<strong>Fehler:</strong> ' . \nl2br(\htmlspecialchars(recoveryFormatUserError($e)));
                        recoveryRenderExceptionDetails($e);
                        echo '</div>';
                    }
                }
            }
        } else {
            echo '<div class="alert alert-error">';
            echo '<strong>Fehler:</strong> Kein Package-Identifier konnte ermittelt werden. Bitte versuchen Sie es erneut.';
            echo '</div>';
        }
        } catch (\Throwable $e) {
            recoveryRenderProcessingError($e);
        }
        $loadingEl = 'var o=document.getElementById("recovery-loading-overlay");if(o){o.style.display="none";}';
        echo '<script>' . $loadingEl . '</script>';
        echo '<p style="margin-top:24px"><a href="' . \htmlspecialchars($acpModeUrl) . '" class="back-link"><i class="fa-solid fa-arrow-left"></i> Neue Analyse starten</a></p>';
    }
}

// ============================================================================
// MODUS 2: PLUGIN UNINSTALL (v1.2.7 – 3-Schritt-Flow mit Backup & Dry-Run)
// ============================================================================

elseif ($mode === RECOVERY_MODE_PLUGIN_UNINSTALL) {
?>
    <h1>Plugin Uninstall</h1>
    <p class="subtitle">Deinstalliert Plugin komplett – per-Ressource-Auswahl, SQL-Backup &amp; Dry-Run</p>

<?php
    if (recoveryWasPostTruncated()) {
        recoveryRenderPostTruncatedWarning();
    }

    $uninstallModeUrl = recoveryBuildModeUrl(RECOVERY_MODE_PLUGIN_UNINSTALL, $authHash);
    $uninstallStep = recoveryResolveUninstallStep();
    $showEntryForms = recoveryUninstallShouldShowInputForm();

    if ($showEntryForms) {
?>
    <div class="wizardSteps">
        <div class="wizardStep active">
            <div class="wizardStepNumber">1</div>
            <div class="wizardStepLabel">Analyse &amp; Auswahl</div>
        </div>
        <div class="wizardStep">
            <div class="wizardStepNumber">2</div>
            <div class="wizardStepLabel">Backup</div>
        </div>
        <div class="wizardStep">
            <div class="wizardStepNumber">3</div>
            <div class="wizardStepLabel">Ausführen</div>
        </div>
    </div>

    <form method="POST" enctype="multipart/form-data" action="<?= \htmlspecialchars($uninstallModeUrl) ?>" data-recovery-loading="Paket wird analysiert …">
        <?php recoveryRenderFormModeHiddenFields(RECOVERY_MODE_PLUGIN_UNINSTALL, $authHash); ?>
        <div class="form-group">
            <label>Option 1: Package-Identifier manuell eingeben</label>
            <input type="text" name="package_identifier" placeholder="z.B. de.example.my-plugin" autocomplete="off">
            <small style="display:block;margin-top:5px">Der eindeutige Package-Identifier (Reverse-Domain-Notation).</small>
        </div>
        <button type="submit"><i class="fa-solid fa-magnifying-glass"></i> Analysieren</button>
    </form>

    <hr>

    <form method="POST" enctype="multipart/form-data" action="<?= \htmlspecialchars($uninstallModeUrl) ?>" data-recovery-loading="Paket wird hochgeladen und analysiert …">
        <?php recoveryRenderFormModeHiddenFields(RECOVERY_MODE_PLUGIN_UNINSTALL, $authHash); ?>
        <div class="form-group">
            <label>Option 2: Package-Datei hochladen (.tar, .tar.gz, .tgz – max. 100 MiB)</label>
            <input type="file" name="package_file" accept=".tar,.tar.gz,.tgz" required>
            <small style="display:block;margin-top:5px">package.xml wird automatisch ausgelesen – DB-Analyse folgt.</small>
        </div>
        <button type="submit"><i class="fa-solid fa-magnifying-glass"></i> Analysieren</button>
    </form>
<?php
    } else {
        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && $uninstallStep === '') {
            echo '<div id="recovery-loading-overlay" class="recovery-loading" style="display:block">Paket wird analysiert …</div>';
        }
        try {
        $packageInput = recoveryResolvePackageInputFromRequest($authHash);
        if (isset($packageInput['error'])) {
            echo '<div class="alert alert-error"><strong>Fehler:</strong> '
                . \htmlspecialchars($packageInput['error']) . '</div>';
        } else {
            $packageIdentifier = $packageInput['packageIdentifier'] ?? null;
            $extractDir        = $packageInput['extractDir'] ?? recoveryResolveTrustedExtractDir();

            if (!$packageIdentifier) {
                echo '<div class="alert alert-error"><strong>Fehler:</strong> Kein Package-Identifier ermittelt. Bitte erneut versuchen.</div>';
            } else {
                // Package in DB suchen
                $sql = "SELECT packageID, package, packageName, packageDir, isApplication
                        FROM wcf" . WCF_N . "_package WHERE package = ?";
                $statement = $db->prepareStatement($sql);
                $statement->execute([$packageIdentifier]);
                $packageData = $statement->fetchArray() ?: null;
                $packageID   = $packageData ? (int)$packageData['packageID'] : null;

                // Ressourcen aus Archiv (falls vorhanden)
                $resources = null;
                if ($extractDir && \is_dir($extractDir)) {
                    $resources = analyzePackageResources($extractDir, $packageIdentifier, $db);
                }
                $wcfN = $resources ? (int)$resources['wcfN'] : WCF_N;

                // ── SCHRITT 1: ANALYSE + AUSWAHL ──────────────────────────────
                if ($uninstallStep === '') {
?>
    <div class="wizardSteps">
        <div class="wizardStep active">
            <div class="wizardStepNumber">1</div>
            <div class="wizardStepLabel">Analyse &amp; Auswahl</div>
        </div>
        <div class="wizardStep">
            <div class="wizardStepNumber">2</div>
            <div class="wizardStepLabel">Backup</div>
        </div>
        <div class="wizardStep">
            <div class="wizardStepNumber">3</div>
            <div class="wizardStepLabel">Ausführen</div>
        </div>
    </div>
<?php
                    // Paket-Info-Box
                    echo '<div class="alert alert-info">';
                    echo '<strong>Paket:</strong> <code>' . \htmlspecialchars($packageIdentifier) . '</code><br>';
                    if ($packageData) {
                        echo '<strong>Status:</strong> In Datenbank gefunden (ID: <strong>' . $packageID . '</strong>)<br>';
                        echo '<strong>Name:</strong> ' . \htmlspecialchars($packageData['packageName']) . '<br>';
                        echo '<strong>WCF_N:</strong> ' . $wcfN;
                    } else {
                        echo '<strong>Status:</strong> <em>Nicht in Datenbank gefunden</em> – Installation fehlgeschlagen?<br>';
                        echo '<small>Ohne packageID sind nur Tabellen-Drops und Datei-Löschungen möglich.</small>';
                    }
                    echo '</div>';

                    // PIP-Counts aus DB (+ weitere Tabellen mit packageID-Spalte)
                    $pipMap    = recoveryGetPipResourceMap();
                    $pipCounts = $packageID ? recoveryGetPipDbCounts($db, $wcfN, $packageID) : [];
                    if ($packageID) {
                        recoveryMergeDiscoveredPipTables($pipMap, $pipCounts, $db, $wcfN, $packageID);
                    }

                    // Plugin-eigene Tabellen ermitteln
                    $customTables = [];
                    if ($resources && !empty($resources['tables'])) {
                        $customTables = $resources['tables'];
                    } else {
                        $customTables = findPackageTables($db, $packageIdentifier, $wcfN);
                    }

                    // Dateisystem prüfen
                    $fsEval = recoveryEvaluatePluginDirectoryDeletion(
                        $packageData, $packageIdentifier, $db, $wcfN, $extractDir
                    );

                    echo '<form method="POST" enctype="multipart/form-data" action="' . \htmlspecialchars(recoveryBuildModeUrl(RECOVERY_MODE_PLUGIN_UNINSTALL, $authHash)) . '">';
                    echo '<input type="hidden" name="mode" value="' . RECOVERY_MODE_PLUGIN_UNINSTALL . '">';
                    echo '<input type="hidden" name="t" value="' . \htmlspecialchars($authHash, ENT_QUOTES, 'UTF-8') . '">';
                    echo '<input type="hidden" name="package_identifier" value="' . \htmlspecialchars($packageIdentifier) . '">';
                    if ($extractDir) {
                        echo '<input type="hidden" name="extract_dir" value="' . \htmlspecialchars($extractDir) . '">';
                    }
                    echo '<input type="hidden" name="uninstall_step" value="1">';

                    // Dry-Run Toggle
                    echo '<article class="border round medium-padding margin-bottom-medium" style="border-color:var(--error,#c62828)">';
                    echo '<p style="margin:0"><strong><i class="fa-solid fa-database"></i> Vor dem Entfernen:</strong> ';
                    echo '<a href="' . \htmlspecialchars(recoveryBuildModeUrl(RECOVERY_MODE_BACKUP_GUIDE, $authHash)) . '">Datensicherung</a> ';
                    echo 'anlegen (DB + Dateien) — siehe <a href="https://manual.woltlab.com/de/backup/" target="_blank" rel="noopener">WoltLab-Handbuch</a>.</p>';
                    echo '</article>';

                    echo '<div class="alert alert-warning" style="margin-bottom:20px">';
                    echo '<label style="cursor:pointer"><input type="checkbox" name="dry_run" id="recoveryDryRunToggle" value="1" style="margin-right:6px">';
                    echo '<strong>Dry-Run-Modus:</strong> Zeigt was gelöscht WÜRDE, ohne tatsächliche Änderungen vorzunehmen</label>';
                    echo '<div id="recoveryDryRunQuick" class="recovery-dryrun-quick">';
                    echo '<button type="submit" class="button" style="margin-top:12px"><i class="fa-solid fa-play"></i> Dry-Run jetzt starten</button>';
                    echo '<br><small>Startet direkt mit Dry-Run – ohne nach unten zu scrollen.</small>';
                    echo '</div>';
                    echo '</div>';

                    // ── DB-Einträge nach packageID ────────────────────────────
                    if ($packageID) {
                        $hasSafeRows = false;
                        foreach ($pipCounts as $cnt) {
                            if ($cnt > 0) { $hasSafeRows = true; break; }
                        }

                        echo '<h2 style="margin-bottom:10px">DB-Einträge nach packageID</h2>';
                        echo '<p style="margin-bottom:12px"><small>Nur Einträge mit <code>packageID = ' . $packageID . '</code> werden gelöscht – keine Massenlöschungen. '
                            . 'Klicken Sie auf die <strong>Zahl</strong> in „Einträge“, um die betroffenen Zeilen zu sehen.</small></p>';
                        echo '<table class="table">';
                        echo '<thead><tr>';
                        echo '<th style="width:36px"><input type="checkbox" id="chkAllPip" title="Alle aus/abwählen"></th>';
                        echo '<th>Kategorie (PIP)</th><th>Tabelle</th><th style="text-align:right">Einträge</th>';
                        echo '</tr></thead><tbody>';

                        foreach ($pipMap as $pip => $info) {
                            if (!$info['safe'] || $info['col'] !== 'packageID' || $info['table'] === '') {
                                continue;
                            }
                            $count = $pipCounts[$pip] ?? 0;
                            if ($count < 0) {
                                // Tabelle existiert nicht
                                echo '<tr style="opacity:.4">';
                                echo '<td><input type="checkbox" name="pip_select[]" value="' . \htmlspecialchars($pip) . '" disabled></td>';
                                echo '<td>' . \htmlspecialchars($info['label']) . '</td>';
                                echo '<td><code>wcf' . $wcfN . '_' . \htmlspecialchars($info['table']) . '</code></td>';
                                echo '<td style="text-align:right"><small>–</small></td>';
                                echo '</tr>';
                            } else {
                                $checked = $count > 0 ? ' checked' : '';
                                $dim     = $count === 0 ? ' style="opacity:.55"' : '';
                                echo '<tr' . $dim . '>';
                                echo '<td><input type="checkbox" name="pip_select[]" value="' . \htmlspecialchars($pip) . '"' . $checked . '></td>';
                                echo '<td>' . \htmlspecialchars($info['label']) . '</td>';
                                echo '<td><code>wcf' . $wcfN . '_' . \htmlspecialchars($info['table']) . '</code></td>';
                                echo '<td style="text-align:right">'
                                    . recoveryRenderPipCountCell($count, $info['table'], $packageID) . '</td>';
                                echo '</tr>';
                            }
                        }
                        echo '</tbody></table>';
                        echo '<div id="recoveryPipPreviewModal" hidden>';
                        echo '<div class="recovery-pip-preview-dialog" role="dialog" aria-modal="true">';
                        echo '<h3 id="recoveryPipPreviewTitle">Einträge</h3>';
                        echo '<div id="recoveryPipPreviewBody"></div>';
                        echo '<p style="margin-top:16px"><button type="button" class="button" id="recoveryPipPreviewClose">Schließen</button></p>';
                        echo '</div></div>';
                        echo '<script>
                            (function () {
                                var authToken = ' . \json_encode($authHash) . ';
                                var dryToggle = document.getElementById("recoveryDryRunToggle");
                                var dryQuick = document.getElementById("recoveryDryRunQuick");
                                if (dryToggle && dryQuick) {
                                    dryToggle.addEventListener("change", function () {
                                        dryQuick.style.display = dryToggle.checked ? "block" : "none";
                                    });
                                }
                                var counts = ' . \json_encode($pipCounts) . ';
                                var allChecked = Object.values(counts).some(function (v) { return v > 0; });
                                var chkAllPip = document.getElementById("chkAllPip");
                                if (chkAllPip) {
                                    chkAllPip.checked = allChecked;
                                    chkAllPip.addEventListener("change", function () {
                                        document.querySelectorAll("input[name=\\"pip_select[]\\"]:not(:disabled)").forEach(function (c) {
                                            c.checked = chkAllPip.checked;
                                        });
                                    });
                                }
                                var modal = document.getElementById("recoveryPipPreviewModal");
                                var modalBody = document.getElementById("recoveryPipPreviewBody");
                                var modalTitle = document.getElementById("recoveryPipPreviewTitle");
                                var modalClose = document.getElementById("recoveryPipPreviewClose");
                                function escapeHtml(s) {
                                    var d = document.createElement("div");
                                    d.textContent = s;
                                    return d.innerHTML;
                                }
                                function closeModal() { if (modal) { modal.hidden = true; } }
                                if (modalClose) { modalClose.addEventListener("click", closeModal); }
                                if (modal) {
                                    modal.addEventListener("click", function (e) {
                                        if (e.target === modal) { closeModal(); }
                                    });
                                }
                                document.querySelectorAll(".recovery-pip-count-btn").forEach(function (btn) {
                                    btn.addEventListener("click", function () {
                                        var table = btn.getAttribute("data-table");
                                        var packageId = btn.getAttribute("data-package-id");
                                        if (!table || !packageId) { return; }
                                        modalTitle.textContent = "Lade …";
                                        modalBody.innerHTML = "<p>Bitte warten …</p>";
                                        modal.hidden = false;
                                        var previewUrl = new URL(window.location.href);
                                        previewUrl.search = "";
                                        previewUrl.searchParams.set("action", "pip-preview");
                                        previewUrl.searchParams.set("t", authToken);
                                        previewUrl.searchParams.set("table", table);
                                        previewUrl.searchParams.set("package_id", packageId);
                                        fetch(previewUrl.toString(), { credentials: "same-origin" })
                                            .then(function (r) {
                                                return r.text().then(function (text) {
                                                    if (!text) {
                                                        throw new Error("Leere Server-Antwort (HTTP " + r.status + ")");
                                                    }
                                                    try {
                                                        return JSON.parse(text);
                                                    } catch (parseErr) {
                                                        throw new Error("Keine gültige JSON-Antwort: " + text.substring(0, 200));
                                                    }
                                                });
                                            })
                                            .then(function (data) {
                                                if (!data.ok) {
                                                    modalBody.innerHTML = "<p class=\\"alert alert-error\\">"
                                                        + escapeHtml(data.error || "Fehler") + "</p>";
                                                    return;
                                                }
                                                modalTitle.textContent = data.table + " (" + data.total + " Einträge)";
                                                if (!data.rows || data.rows.length === 0) {
                                                    modalBody.innerHTML = "<p><em>Keine Zeilen gefunden.</em></p>";
                                                    return;
                                                }
                                                var html = "<p><small>Vorschau (max. " + data.rows.length
                                                    + " von " + data.total + "):</small></p>";
                                                html += "<table class=\\"table\\"><thead><tr>";
                                                (data.columns || []).forEach(function (c) {
                                                    html += "<th>" + escapeHtml(c) + "</th>";
                                                });
                                                html += "</tr></thead><tbody>";
                                                data.rows.forEach(function (row) {
                                                    html += "<tr>";
                                                    (data.columns || []).forEach(function (c) {
                                                        var val = row[c];
                                                        if (val === null || val === undefined) { val = "—"; }
                                                        else if (String(val).length > 120) {
                                                            val = String(val).substring(0, 117) + "…";
                                                        }
                                                        html += "<td><code>" + escapeHtml(String(val)) + "</code></td>";
                                                    });
                                                    html += "</tr>";
                                                });
                                                html += "</tbody></table>";
                                                modalBody.innerHTML = html;
                                            })
                                            .catch(function (err) {
                                                modalBody.innerHTML = "<p class=\\"alert alert-error\\">"
                                                    + escapeHtml(String(err)) + "</p>";
                                            });
                                    });
                                });
                            })();
                        </script>';
                    } else {
                        echo '<div class="alert alert-warning">Keine packageID – DB-Einträge per packageID nicht analysierbar.</div>';
                    }

                    // ── Plugin-eigene Tabellen (DROP TABLE) ───────────────────
                    echo '<h2 style="margin:24px 0 10px">Plugin-eigene Tabellen (DROP TABLE)</h2>';
                    if (!empty($customTables)) {
                        echo '<table class="table">';
                        echo '<thead><tr><th style="width:36px">&#x2713;</th><th>Tabellenname</th><th style="text-align:right">Einträge</th></tr></thead>';
                        echo '<tbody>';
                        foreach ($customTables as $table) {
                            $safeTable = \str_replace('`', '', (string)$table);
                            if (!recoveryValidateSqlTableName($safeTable)) {
                                continue;
                            }
                            $cnt = '?';
                            try {
                                $st = $db->prepareStatement('SELECT COUNT(*) AS c FROM `' . $safeTable . '`');
                                $st->execute();
                                $cnt = (int)($st->fetchArray()['c'] ?? 0);
                            } catch (\Throwable $ignored) {}
                            echo '<tr>';
                            echo '<td><input type="checkbox" name="drop_tables[]" value="' . \htmlspecialchars($safeTable) . '" checked></td>';
                            echo '<td><code>' . \htmlspecialchars($safeTable) . '</code></td>';
                            echo '<td style="text-align:right">' . $cnt . '</td>';
                            echo '</tr>';
                        }
                        echo '</tbody></table>';
                    } else {
                        echo '<p style="color:#999"><em>Keine plugin-eigenen Tabellen gefunden.</em></p>';
                    }

                    $fileLogCount = $packageID ? \count(recoveryLoadPackageFileLogPaths($db, $wcfN, $packageID)) : 0;
                    $sqlPreviewStep = $packageID ? recoveryPreviewSqlRollback($db, $wcfN, $packageID) : ['actions' => []];

                    if ($packageID) {
                        echo '<h2 style="margin:24px 0 10px"><i class="fa-solid fa-gears"></i> Erweiterte Schritte</h2>';
                        echo '<div class="alert alert-info" style="margin-bottom:12px">';
                        echo '<label style="cursor:pointer;display:block;margin-bottom:8px">';
                        echo '<input type="checkbox" name="rebuild_bootstrap" value="1" checked> ';
                        echo '<strong>lib/bootstrap.php neu erzeugen</strong> (empfohlen)</label>';
                        if ($fileLogCount > 0) {
                            echo '<label style="cursor:pointer;display:block;margin-bottom:8px">';
                            echo '<input type="checkbox" name="delete_files_log" value="1" checked> ';
                            echo '<strong>Dateien aus file_log löschen</strong> (' . $fileLogCount . ')</label>';
                        }
                        if ($sqlPreviewStep['actions'] !== []) {
                            echo '<label style="cursor:pointer;display:block">';
                            echo '<input type="checkbox" name="sql_rollback" value="1"> ';
                            echo '<strong>SQL-Schema zurücksetzen</strong> (' . \count($sqlPreviewStep['actions']) . ' Aktionen — optional)</label>';
                            echo '<br><small style="margin-top:6px;display:block">Destruktiv — nur mit DB-Backup.</small>';
                        }
                        echo '</div>';
                    }

                    // ── Dateisystem ───────────────────────────────────────────
                    echo '<h2 style="margin:24px 0 10px"><i class="fa-solid fa-folder-open"></i> Dateisystem (Verzeichnis-Fallback)</h2>';
                    if ($fsEval['deletable']) {
                        echo '<div class="alert alert-warning">';
                        echo '<label style="cursor:pointer"><input type="checkbox" name="delete_files_dir" value="1"';
                        if ($fileLogCount === 0) { echo ' checked'; }
                        echo '> ';
                        echo 'Plugin-Verzeichnis <code>' . \htmlspecialchars((string)$fsEval['relativePath']) . '/</code> auf dem Server löschen</label>';
                        echo '<br><small style="margin-top:6px;display:block">Zusätzlich zu file_log oder als Fallback.</small>';
                        echo '</div>';
                    } else {
                        echo '<div class="alert alert-info"><strong>Dateisystem:</strong> ' . \htmlspecialchars($fsEval['reason']) . '</div>';
                    }

                    echo '<div style="margin-top:28px">';
                    echo '<button type="submit" class="btn-danger"><i class="fa-solid fa-play"></i> Weiter: Backup &amp; Ausführen</button>';
                    echo '</div>';
                    echo '</form>';

                // ── SCHRITT 2: BACKUP ─────────────────────────────────────────
                } elseif ($uninstallStep === '1') {
                    $isDryRun      = isset($_POST['dry_run']) && $_POST['dry_run'] === '1';
                    $selectedPips  = \is_array($_POST['pip_select'] ?? null)  ? (array)$_POST['pip_select']  : [];
                    $dropTables    = \is_array($_POST['drop_tables'] ?? null)  ? (array)$_POST['drop_tables']  : [];
                    $deleteFilesLog = !empty($_POST['delete_files_log']) && $_POST['delete_files_log'] === '1';
                    $deleteFilesDir = !empty($_POST['delete_files_dir']) && $_POST['delete_files_dir'] === '1';
                    $deleteFilesLegacy = !empty($_POST['delete_files']) && $_POST['delete_files'] === '1';
                    $deleteFiles   = $deleteFilesLog || $deleteFilesDir || $deleteFilesLegacy;
                    $sqlRollback   = !empty($_POST['sql_rollback']) && $_POST['sql_rollback'] === '1';
                    $rebuildBootstrap = !isset($_POST['rebuild_bootstrap']) || $_POST['rebuild_bootstrap'] === '1';

                    // Eingaben validieren
                    $pipMap    = recoveryGetPipResourceMap();
                    $validPips = \array_values(\array_filter($selectedPips, fn($p) => isset($pipMap[$p]) && $pipMap[$p]['safe'] && $pipMap[$p]['table'] !== ''));
                    $validDropTables = [];
                    foreach ($dropTables as $t) {
                        $s = \str_replace('`', '', (string)$t);
                        if (recoveryValidateSqlTableName($s)) {
                            $validDropTables[] = $s;
                        }
                    }
?>
    <div class="wizardSteps">
        <div class="wizardStep completed">
            <div class="wizardStepNumber">1</div>
            <div class="wizardStepLabel">Analyse &amp; Auswahl</div>
        </div>
        <div class="wizardStep active">
            <div class="wizardStepNumber">2</div>
            <div class="wizardStepLabel">Backup</div>
        </div>
        <div class="wizardStep">
            <div class="wizardStepNumber">3</div>
            <div class="wizardStepLabel"><?= $isDryRun ? 'Dry-Run' : 'Ausführen' ?></div>
        </div>
    </div>
<?php
                    // SQL-Backup generieren
                    $backupSql = '';
                    if ($packageID && !empty($validPips)) {
                        $backupSql = recoveryGenerateSqlBackup($db, $wcfN, $packageID, $validPips);
                    }

                    if ($backupSql !== '') {
                        $backupB64 = \base64_encode($backupSql);
                        echo '<h2><i class="fa-solid fa-database"></i> SQL-Backup der betroffenen Zeilen</h2>';
                        echo '<div class="alert alert-info">';
                        echo '<strong>Backup für packageID = ' . $packageID . '</strong><br>';
                        echo '<small>Enthält alle Zeilen aus den ausgewählten Tabellen – bitte vor dem Ausführen herunterladen oder kopieren.</small>';
                        echo '<br><br>';
                        // Server-seitiger Download via POST
                        echo '<form method="POST" action="?action=download-sql&amp;t=' . \htmlspecialchars($authHash) . '" style="display:inline;margin-right:10px">';
                        echo '<input type="hidden" name="sql_b64" value="' . \htmlspecialchars($backupB64) . '">';
                        echo '<button type="submit" class="button"><i class="fa-solid fa-download"></i> SQL-Backup herunterladen (.sql)</button>';
                        echo '</form>';
                        // Client-seitiger JS-Download (Fallback)
                        echo '<button type="button" class="button" id="recoveryJsSqlDownload" style="margin-left:8px">';
                        echo '<i class="fa-solid fa-download"></i> JS-Download</button>';
                        echo '<script>(function(){var el=document.getElementById("recoveryJsSqlDownload");';
                        echo 'if(!el){return;}el.addEventListener("click",function(){';
                        echo 'var s=atob(' . \json_encode($backupB64) . ');';
                        echo 'var b=new Blob([s],{type:"text/plain;charset=utf-8"});';
                        echo 'var a=document.createElement("a");a.href=URL.createObjectURL(b);';
                        echo 'a.download="recovery-backup-' . \date('Y-m-d-His') . '.sql";document.body.appendChild(a);';
                        echo 'a.click();document.body.removeChild(a);URL.revokeObjectURL(a.href);});})();</script>';
                        echo '<br><br>';
                        echo '<details><summary style="cursor:pointer">SQL-Inhalt anzeigen (' . \number_format(\strlen($backupSql)) . ' Bytes)</summary>';
                        echo '<textarea style="width:100%;height:220px;margin-top:10px;font-size:12px;font-family:monospace;background:#2D2D2D;color:#c0c0c0;border:1px solid #444;padding:10px;border-radius:3px;box-sizing:border-box" readonly>';
                        echo \htmlspecialchars(\substr($backupSql, 0, 50000)) . (\strlen($backupSql) > 50000 ? "\n-- [gekürzt …]" : '');
                        echo '</textarea>';
                        echo '</details>';
                        echo '</div>';
                    } else {
                        echo '<div class="alert alert-info">';
                        echo '<strong>Kein SQL-Backup erforderlich</strong><br>';
                        if (!$packageID) {
                            echo '<small>Ohne packageID können keine Zeilen gesichert werden.</small>';
                        } else {
                            echo '<small>Keine Zeilen in den ausgewählten Tabellen gefunden.</small>';
                        }
                        echo '</div>';
                    }

                    // Zusammenfassung der geplanten Aktionen
                    echo '<h2 style="margin-top:24px"><i class="fa-solid fa-database"></i> Geplante Aktionen ' . ($isDryRun ? '<span style="color:#c93">(Dry-Run)</span>' : '') . '</h2>';
                    echo '<div class="alert ' . ($isDryRun ? 'alert-warning' : 'alert-error') . '">';
                    if ($isDryRun) {
                        echo '<strong>&#128065; Dry-Run – keine Änderungen werden vorgenommen</strong><br><br>';
                    }

                    if (!empty($validPips) && $packageID) {
                        echo '<strong>DB-Löschungen (WHERE packageID = ' . $packageID . '):</strong><br>';
                        foreach ($validPips as $pip) {
                            echo '&bull; <code>wcf' . $wcfN . '_' . \htmlspecialchars($pipMap[$pip]['table']) . '</code> – ' . \htmlspecialchars($pipMap[$pip]['label']) . '<br>';
                        }
                        echo '&bull; <code>wcf' . $wcfN . '_package</code> – Package-Eintrag (ID ' . $packageID . ')<br>';
                        echo '&bull; Package-Queue, Requirements, SQL-Log, File-Log<br><br>';
                    } elseif (empty($validPips)) {
                        echo '<em>Keine DB-Kategorien ausgewählt.</em><br><br>';
                    }

                    if (!empty($validDropTables)) {
                        echo '<strong>DROP TABLE:</strong><br>';
                        foreach ($validDropTables as $t) {
                            echo '&bull; <code>' . \htmlspecialchars($t) . '</code><br>';
                        }
                        echo '<br>';
                    }

                    if ($packageID) {
                        echo '<strong>Uninstall-Script:</strong> acp/uninstall/' . \htmlspecialchars($packageIdentifier) . '.php '
                            . (\is_file(\rtrim(WCF_DIR, '/\\') . '/acp/uninstall/' . $packageIdentifier . '.php') ? '(vorhanden)' : '(nicht vorhanden)') . '<br>';
                        if ($sqlRollback) {
                            $sp = recoveryPreviewSqlRollback($db, $wcfN, $packageID);
                            echo '<strong>SQL-Rollback:</strong> ' . \count($sp['actions']) . ' Aktion(en)<br>';
                        }
                        if ($rebuildBootstrap) {
                            echo '<strong>Bootstrap:</strong> lib/bootstrap.php wird neu erzeugt<br>';
                        }
                    }
                    if ($deleteFilesLog) {
                        echo '<strong>File-Log:</strong> ' . \count(recoveryLoadPackageFileLogPaths($db, $wcfN, (int)$packageID)) . ' Datei(en)<br>';
                    }
                    if ($deleteFilesDir) {
                        $fsEval2 = recoveryEvaluatePluginDirectoryDeletion($packageData, $packageIdentifier, $db, $wcfN, $extractDir);
                        if ($fsEval2['deletable']) {
                            echo '<strong>Verzeichnis:</strong> <code>' . \htmlspecialchars((string)$fsEval2['relativePath']) . '/</code><br>';
                        }
                    }
                    echo '</div>';

                    // Formular mit allen Selektionen als Hidden-Inputs → Step 3 (Execute)
                    echo '<form method="POST" enctype="multipart/form-data" action="' . \htmlspecialchars(recoveryBuildModeUrl(RECOVERY_MODE_PLUGIN_UNINSTALL, $authHash)) . '">';
                    echo '<input type="hidden" name="mode" value="' . RECOVERY_MODE_PLUGIN_UNINSTALL . '">';
                    echo '<input type="hidden" name="t" value="' . \htmlspecialchars($authHash, ENT_QUOTES, 'UTF-8') . '">';
                    echo '<input type="hidden" name="package_identifier" value="' . \htmlspecialchars($packageIdentifier) . '">';
                    if ($extractDir) {
                        echo '<input type="hidden" name="extract_dir" value="' . \htmlspecialchars($extractDir) . '">';
                    }
                    echo '<input type="hidden" name="uninstall_step" value="2">';
                    if ($isDryRun) {
                        echo '<input type="hidden" name="dry_run" value="1">';
                    }
                    if ($deleteFilesLog) {
                        echo '<input type="hidden" name="delete_files_log" value="1">';
                    }
                    if ($deleteFilesDir) {
                        echo '<input type="hidden" name="delete_files_dir" value="1">';
                    }
                    if ($sqlRollback) {
                        echo '<input type="hidden" name="sql_rollback" value="1">';
                    }
                    if ($rebuildBootstrap) {
                        echo '<input type="hidden" name="rebuild_bootstrap" value="1">';
                    }
                    foreach ($validPips as $pip) {
                        echo '<input type="hidden" name="pip_select[]" value="' . \htmlspecialchars($pip) . '">';
                    }
                    foreach ($validDropTables as $t) {
                        echo '<input type="hidden" name="drop_tables[]" value="' . \htmlspecialchars($t) . '">';
                    }
                    $btnLabel = $isDryRun ? '<i class="fa-solid fa-play"></i> Dry-Run starten' : '<i class="fa-solid fa-trash-can"></i> Jetzt ausführen (nicht rückgängig!)';
                    $btnClass = $isDryRun ? 'button' : 'button btn-danger';
                    echo '<button type="submit" class="' . $btnClass . '">' . $btnLabel . '</button>';
                    echo '</form>';

                // ── SCHRITT 3: AUSFÜHREN ──────────────────────────────────────
                } elseif ($uninstallStep === '2') {
                    $isDryRun      = !empty($_POST['dry_run']) && $_POST['dry_run'] === '1';
                    $selectedPips  = \is_array($_POST['pip_select'] ?? null)  ? (array)$_POST['pip_select']  : [];
                    $dropTables    = \is_array($_POST['drop_tables'] ?? null)  ? (array)$_POST['drop_tables']  : [];
                    $deleteFilesLog = !empty($_POST['delete_files_log']) && $_POST['delete_files_log'] === '1';
                    $deleteFilesDir = !empty($_POST['delete_files_dir']) && $_POST['delete_files_dir'] === '1';
                    $deleteFilesLegacy = !empty($_POST['delete_files']) && $_POST['delete_files'] === '1';
                    $deleteFiles   = $deleteFilesLog || $deleteFilesDir || $deleteFilesLegacy;
                    $sqlRollback   = !empty($_POST['sql_rollback']) && $_POST['sql_rollback'] === '1';
                    $rebuildBootstrap = !isset($_POST['rebuild_bootstrap']) || $_POST['rebuild_bootstrap'] === '1';

                    $pipMap    = recoveryGetPipResourceMap();
                    $validPips = \array_values(\array_filter($selectedPips, fn($p) => isset($pipMap[$p]) && $pipMap[$p]['safe'] && $pipMap[$p]['table'] !== ''));
                    $validDropTables = [];
                    foreach ($dropTables as $t) {
                        $s = \str_replace('`', '', (string)$t);
                        if (recoveryValidateSqlTableName($s)) {
                            $validDropTables[] = $s;
                        }
                    }
?>
    <div class="wizardSteps">
        <div class="wizardStep completed">
            <div class="wizardStepNumber">1</div>
            <div class="wizardStepLabel">Analyse &amp; Auswahl</div>
        </div>
        <div class="wizardStep completed">
            <div class="wizardStepNumber">2</div>
            <div class="wizardStepLabel">Backup</div>
        </div>
        <div class="wizardStep active">
            <div class="wizardStepNumber">3</div>
            <div class="wizardStepLabel"><?= $isDryRun ? 'Dry-Run' : 'Ausführen' ?></div>
        </div>
    </div>
<?php
                    $log = [];
                    $removalOpts = [
                        'dryRun' => $isDryRun,
                        'sqlRollback' => $sqlRollback,
                        'deleteFilesLog' => $deleteFilesLog || ($deleteFilesLegacy && !$deleteFilesDir),
                        'deleteFilesDir' => $deleteFilesDir || $deleteFilesLegacy,
                        'rebuildBootstrap' => $rebuildBootstrap,
                        'runUninstallScript' => true,
                    ];

                    try {
                        recoveryRunPreDbRemovalSteps(
                            $db,
                            $wcfN,
                            $packageIdentifier,
                            $packageID ?: null,
                            $removalOpts,
                            $log
                        );

                        // ── DB-Bereinigung nach packageID ─────────────────────
                        if ($packageID && !empty($validPips)) {
                            foreach ($validPips as $pip) {
                                $info = $pipMap[$pip];
                                if ($isDryRun) {
                                    try {
                                        $st = $db->prepareStatement("SELECT COUNT(*) AS cnt FROM wcf{$wcfN}_{$info['table']} WHERE packageID = ?");
                                        $st->execute([$packageID]);
                                        $r = $st->fetchArray();
                                        $log[] = '[DRY-RUN] WÜRDE LÖSCHEN: wcf' . $wcfN . '_' . $info['table'] . ' – ' . (int)($r['cnt'] ?? 0) . ' Einträge';
                                    } catch (\Throwable $e) {
                                        $log[] = '[DRY-RUN] ' . $info['label'] . ': Tabelle nicht vorhanden';
                                    }
                                } else {
                                    recoveryTryDeleteByPackageId($db, $wcfN, $info['table'], $packageID, $info['label'], $log);
                                }
                            }
                        }

                        // ── Package-Infrastruktur ─────────────────────────────
                        if ($packageID) {
                            if ($isDryRun) {
                                $log[] = '[DRY-RUN] WÜRDE LÖSCHEN: Package-Queue, Nodes, Forms, Requirements, SQL-Log, Package-Eintrag';
                            } else {
                                recoveryCleanupPackageInstallationArtifacts($db, $wcfN, $packageID, $packageIdentifier, $log);
                                recoveryCleanupPackageUpdateEntries($db, $wcfN, $packageIdentifier, $log);
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
                            }
                        }

                        // ── Plugin-eigene Tabellen droppen ────────────────────
                        foreach ($validDropTables as $table) {
                            if ($isDryRun) {
                                $log[] = '[DRY-RUN] WÜRDE DROP TABLE: ' . $table;
                            } else {
                                try {
                                    $stmt = $db->prepareStatement('DROP TABLE IF EXISTS `' . $table . '`');
                                    $stmt->execute();
                                    $log[] = 'Tabelle gelöscht: ' . $table;
                                } catch (\Throwable $e) {
                                    $log[] = 'DROP TABLE fehlgeschlagen (' . $table . '): ' . $e->getMessage();
                                }
                            }
                        }

                        recoveryRunPostDbRemovalSteps(
                            $db,
                            $wcfN,
                            $packageIdentifier,
                            $packageData,
                            $packageID ?: null,
                            $removalOpts,
                            $log,
                            $extractDir
                        );

                        // ── options.inc.php + Cache ───────────────────────────
                        if (!$isDryRun) {
                            $optionConstants = recoveryCollectOptionConstantNames($db, $wcfN, $packageID);
                            if (recoveryRebuildOptionsIncPhp()) {
                                $log[] = 'options.inc.php neu erzeugt';
                            } elseif (!empty($optionConstants)) {
                                recoveryStripConstantsFromOptionsIncPhp($optionConstants);
                                $log[] = 'options.inc.php bereinigt (' . \count($optionConstants) . ' Konstanten entfernt)';
                            }
                            recoveryEnsureOptionConstantFallbacks($db, $wcfN, $log);
                            $deletedCacheFiles = clearCompiledTemplates();
                            $log[] = 'Cache gelöscht: ' . $deletedCacheFiles . ' Dateien';
                            recoveryCleanupUploadWorkspace();
                        }

                        // ── Ergebnis anzeigen ─────────────────────────────────
                        $resultClass = $isDryRun ? 'alert-warning' : 'alert-success';
                        echo '<div class="alert ' . $resultClass . '">';
                        echo '<strong>' . ($isDryRun ? '&#128065; Dry-Run abgeschlossen – keine Änderungen vorgenommen' : '&#10003; Plugin-Bereinigung abgeschlossen!') . '</strong><br><br>';
                        echo '<strong>Protokoll:</strong><br>';
                        foreach ($log as $entry) {
                            echo '&bull; ' . \htmlspecialchars($entry) . '<br>';
                        }

                        echo '</div>';

                        if (!$isDryRun) {
                            $cacheAgainUrl = \htmlspecialchars(
                                recoveryBuildModeUrl(RECOVERY_MODE_CACHE_CLEAR, $authHash),
                                ENT_QUOTES,
                                'UTF-8'
                            );
                            echo '<div class="alert alert-info">';
                            echo '<strong>ACP lädt nicht oder zeigt Fatal Error?</strong><br>';
                            echo 'Zusätzlich wurde <code>options.inc.php</code> mit einem markierten Fallback-Block ergänzt (<code>if (!defined(&#8230;)) define(&#8230;)</code>) für <strong>alle</strong> Optionen aus der DB plus Konstanten-Erkennung aus kompilierten Templates. ';
                            echo 'Nach Plugin-Problemen bleiben oft <em>kompilierte Templates</em> ohne passende globale Konstanten (Log: '
                                . '<code>Undefined constant &quot;&hellip;&quot;</code>). ';
                            echo 'Das Tool hat den Datei-Cache geleert; bei Bedarf <strong>Caches erneut leeren:</strong> Modus Cache Clear.';
                            echo '<br><br><a href="' . $cacheAgainUrl . '" class="button">';
                            echo '<i class="fa-solid fa-broom"></i> Cache Clear öffnen</a>';
                            echo '<br><br><small>Plugin-Fix: Konstanten immer mit <code>defined(\'CONST\')</code> oder Standardwert in Templates nutzen. '
                                . 'Nach fehlgeschlagener Installation hilft häufig manuell: <code>acp/templates/compiled/</code> leeren.';
                            echo '</small></div>';
                        }

                    } catch (\Throwable $e) {
                        echo '<div class="alert alert-error">';
                        echo '<strong>Fehler bei Deinstallation:</strong><br>';
                        echo \nl2br(\htmlspecialchars(recoveryFormatUserError($e)));
                        recoveryRenderExceptionDetails($e);
                        echo '</div>';
                    }
                } else {
                    echo '<div class="alert alert-error"><strong>Fehler:</strong> Unbekannter Wizard-Schritt (uninstall_step='
                        . \htmlspecialchars($uninstallStep) . ').</div>';
                }
            }
        }
        } catch (\Throwable $e) {
            recoveryRenderProcessingError($e);
        }
        echo '<script>var o=document.getElementById("recovery-loading-overlay");if(o){o.style.display="none";}</script>';
        echo '<p style="margin-top:24px"><a href="' . \htmlspecialchars($uninstallModeUrl) . '" class="back-link"><i class="fa-solid fa-arrow-left"></i> Neue Analyse starten</a></p>';
    }
}

// ============================================================================
// MODUS 3: USER MANAGEMENT
// ============================================================================

elseif ($mode === RECOVERY_MODE_USER_MANAGEMENT) {
    $umBaseUrl = '?mode=' . RECOVERY_MODE_USER_MANAGEMENT . '&t=' . \htmlspecialchars($authHash);
    $umUid     = isset($_GET['um_uid']) ? (int)$_GET['um_uid'] : 0;
    $umMessages = [];
    $umErrors   = [];

    // --- POST-Aktionen verarbeiten ---
    if ($umUid > 0 && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $umAction = $_POST['um_action'] ?? '';
        try {
            switch ($umAction) {
                case 'reset_password':
                    $newPwd = recoveryUserGenerateRandomPassword();
                    recoveryUserResetPassword($db, $umUid, $newPwd);
                    $umMessages[] = 'Passwort wurde auf <code>' . \htmlspecialchars($newPwd) . '</code> gesetzt. Bitte sofort notieren!';
                    break;

                case 'reset_password_custom':
                    $customPwd = \trim($_POST['custom_password'] ?? '');
                    if ($customPwd === '') {
                        $umErrors[] = 'Bitte ein Passwort eingeben.';
                    } elseif (\strlen($customPwd) < 8) {
                        $umErrors[] = 'Passwort muss mindestens 8 Zeichen lang sein.';
                    } else {
                        recoveryUserResetPassword($db, $umUid, $customPwd);
                        $umMessages[] = 'Passwort wurde erfolgreich gesetzt.';
                    }
                    break;

                case 'set_groups':
                    $groupIDs = isset($_POST['group_ids']) && \is_array($_POST['group_ids'])
                        ? \array_map('intval', $_POST['group_ids'])
                        : [];
                    recoveryUserSetGroups($db, $umUid, $groupIDs);
                    $umMessages[] = 'Gruppenmitgliedschaften wurden aktualisiert.';
                    break;

                case 'add_admin':
                    $currentGIDs = recoveryUserGetGroupIDs($db, $umUid);
                    if (!\in_array(4, $currentGIDs, true)) {
                        $currentGIDs[] = 4;
                        recoveryUserSetGroups($db, $umUid, $currentGIDs);
                        $umMessages[] = 'Benutzer wurde zur Administrator-Gruppe (ID&nbsp;4) hinzugefügt.';
                    } else {
                        $umMessages[] = 'Benutzer ist bereits in der Administrator-Gruppe.';
                    }
                    break;

                case 'change_email':
                    $newEmail = \trim($_POST['new_email'] ?? '');
                    if ($newEmail === '' || !\filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
                        $umErrors[] = 'Bitte eine gültige E-Mail-Adresse eingeben.';
                    } else {
                        recoveryUserChangeEmail($db, $umUid, $newEmail);
                        $umMessages[] = 'E-Mail-Adresse auf <code>' . \htmlspecialchars($newEmail) . '</code> geändert.';
                    }
                    break;

                case 'activate':
                    recoveryUserActivate($db, $umUid);
                    $umMessages[] = 'Benutzer wurde aktiviert und Sperre aufgehoben.';
                    break;

                case 'disable_2fa':
                    recoveryUserDisable2FA($db, $umUid);
                    $umMessages[] = 'Zwei-Faktor-Authentifizierung wurde deaktiviert und alle 2FA-Setups gelöscht.';
                    break;
            }
        } catch (\Throwable $e) {
            $umErrors[] = 'Fehler: ' . \htmlspecialchars(recoveryFormatUserError($e));
            recoveryRenderExceptionDetails($e);
        }
    }
?>
    <h1>User Management</h1>
    <p class="subtitle">Benutzersuche, Passwort-Reset, Gruppen, E-Mail &amp; Kontoverwaltung</p>

<?php if ($umUid > 0):
    $umUser = recoveryUserGetByID($db, $umUid);
    if ($umUser === null): ?>
    <div class="alert alert-error">Benutzer mit ID <code><?= $umUid ?></code> nicht gefunden.</div>
    <a href="<?= $umBaseUrl ?>" class="back-link"><i class="fa-solid fa-arrow-left"></i> Zurück zur Suche</a>
<?php else:
    $currentGroupIDs = recoveryUserGetGroupIDs($db, (int)$umUser['userID']);
?>
    <a href="<?= $umBaseUrl ?>" class="back-link"><i class="fa-solid fa-arrow-left"></i> Anderen Benutzer suchen</a>

    <h2>Benutzer: <code><?= \htmlspecialchars($umUser['username']) ?></code> <small style="font-size:16px; color:#9D9D9D;">(ID&nbsp;<?= (int)$umUser['userID'] ?>)</small></h2>

    <table style="margin-bottom: 24px;">
        <tbody>
            <tr><th style="width:160px">Benutzername</th><td><?= \htmlspecialchars($umUser['username']) ?></td></tr>
            <tr><th>E-Mail</th><td><?= \htmlspecialchars($umUser['email']) ?></td></tr>
            <tr><th>Status</th><td>
                <?php if ($umUser['banned']): ?>
                    <span style="color:#e74c3c">&#9632; Gesperrt</span>
                <?php elseif ($umUser['activationCode'] != 0): ?>
                    <span style="color:#f39c12">&#9632; Aktivierung ausstehend</span>
                <?php else: ?>
                    <span style="color:#00bc8c">&#9632; Aktiv</span>
                <?php endif; ?>
            </td></tr>
            <tr><th>2FA</th><td><?= $umUser['multifactorActive'] ? '<span style="color:#f39c12">Aktiv</span>' : '<span style="color:#9D9D9D">Inaktiv</span>' ?></td></tr>
            <tr><th>Gruppen</th><td><?= \implode(', ', $currentGroupIDs) ?></td></tr>
        </tbody>
    </table>

    <?php foreach ($umErrors as $err): ?>
    <div class="alert alert-error"><?= $err ?></div>
    <?php endforeach; ?>
    <?php foreach ($umMessages as $msg): ?>
    <div class="alert alert-success"><?= $msg ?></div>
    <?php endforeach; ?>

    <!-- ── Passwort zurücksetzen ──────────────────────────────────────── -->
    <h2><i class="fa-solid fa-key"></i> Passwort zurücksetzen</h2>
    <p style="margin:0 0 16px; font-size:13px; color:#9D9D9D;">Wie im <a href="https://manual.woltlab.com/de/recovery-tool/" target="_blank" rel="noopener">offiziellen WoltLab Recovery Tool</a>: zufälliges Passwort bestätigen oder ein eigenes setzen.</p>
    <div class="recovery-option-cards">
        <div class="recovery-option-card recovery-card">
            <h3><i class="fa-solid fa-dice"></i> Zufälliges Passwort</h3>
            <p style="margin:0 0 16px; font-size:13px; color:#9D9D9D;">Wird nach dem Setzen <strong>einmalig</strong> angezeigt – bitte sofort notieren.</p>
            <form method="POST" action="<?= $umBaseUrl ?>&amp;um_uid=<?= (int)$umUser['userID'] ?>">
                <input type="hidden" name="um_action" value="reset_password">
                <button type="submit" class="btn-danger"><i class="fa-solid fa-key"></i> Zufälliges Passwort setzen</button>
            </form>
        </div>
        <div class="recovery-option-card recovery-card">
            <h3><i class="fa-solid fa-pen"></i> Eigenes Passwort</h3>
            <form method="POST" action="<?= $umBaseUrl ?>&amp;um_uid=<?= (int)$umUser['userID'] ?>">
                <input type="hidden" name="um_action" value="reset_password_custom">
                <div class="form-group" style="margin-bottom:16px;">
                    <label for="um_custom_pwd">Neues Passwort (min. 8 Zeichen)</label>
                    <input type="password" id="um_custom_pwd" name="custom_password" autocomplete="new-password" placeholder="Passwort eingeben">
                </div>
                <button type="submit"><i class="fa-solid fa-key"></i> Passwort setzen</button>
            </form>
        </div>
    </div>

    <!-- ── E-Mail ändern ──────────────────────────────────────────────── -->
    <h2><i class="fa-solid fa-envelope"></i> E-Mail-Adresse ändern</h2>
    <form method="POST" action="<?= $umBaseUrl ?>&amp;um_uid=<?= (int)$umUser['userID'] ?>">
        <input type="hidden" name="um_action" value="change_email">
        <div class="form-group">
            <label for="um_email">Neue E-Mail-Adresse</label>
            <input type="text" id="um_email" name="new_email" value="<?= \htmlspecialchars($umUser['email']) ?>" placeholder="neue@email.de">
        </div>
        <button type="submit"><i class="fa-solid fa-envelope"></i> E-Mail ändern</button>
    </form>

    <!-- ── Konto aktivieren / Sperre aufheben ────────────────────────── -->
    <?php if ($umUser['banned'] || $umUser['activationCode'] != 0): ?>
    <h2><i class="fa-solid fa-user"></i> Konto aktivieren &amp; Sperre aufheben</h2>
    <p style="margin-bottom:12px; font-size:13px; color:#9D9D9D;">
        Setzt <code>activationCode&nbsp;=&nbsp;0</code>, <code>banned&nbsp;=&nbsp;0</code> und löscht den Sperr-Grund.
    </p>
    <form method="POST" action="<?= $umBaseUrl ?>&amp;um_uid=<?= (int)$umUser['userID'] ?>">
        <input type="hidden" name="um_action" value="activate">
        <button type="submit" class="btn-success"><i class="fa-solid fa-circle-check"></i> Benutzer aktivieren &amp; entsperren</button>
    </form>
    <?php endif; ?>

    <!-- ── 2FA deaktivieren ───────────────────────────────────────────── -->
    <?php if ($umUser['multifactorActive']): ?>
    <h2><i class="fa-solid fa-shield-halved"></i> Zwei-Faktor-Authentifizierung deaktivieren</h2>
    <p style="margin-bottom:12px; font-size:13px; color:#9D9D9D;">
        Löscht alle 2FA-Setups (inkl. Backup-Codes) und setzt <code>multifactorActive&nbsp;=&nbsp;0</code>.
    </p>
    <form method="POST" action="<?= $umBaseUrl ?>&amp;um_uid=<?= (int)$umUser['userID'] ?>">
        <input type="hidden" name="um_action" value="disable_2fa">
        <button type="submit" class="btn-danger"><i class="fa-solid fa-shield-halved"></i> 2FA deaktivieren</button>
    </form>
    <?php endif; ?>

    <!-- ── Schnell zur Administrator-Gruppe ──────────────────────────── -->
    <h2><i class="fa-solid fa-users-gear"></i> Administrator-Gruppe (ID&nbsp;4)</h2>
    <?php if (\in_array(4, $currentGroupIDs, true)): ?>
    <div class="alert alert-info">Benutzer ist bereits in der Administrator-Gruppe (ID&nbsp;4).</div>
    <?php else: ?>
    <p style="margin-bottom:12px; font-size:13px; color:#9D9D9D;">
        Fügt den Benutzer direkt zur WoltLab-Standard-Administrator-Gruppe (groupID&nbsp;4) hinzu.
    </p>
    <form method="POST" action="<?= $umBaseUrl ?>&amp;um_uid=<?= (int)$umUser['userID'] ?>">
        <input type="hidden" name="um_action" value="add_admin">
        <button type="submit" class="btn-success"><i class="fa-solid fa-users-gear"></i> Zur Administrator-Gruppe hinzufügen</button>
    </form>
    <?php endif; ?>

    <!-- ── Alle Gruppen verwalten ─────────────────────────────────────── -->
    <h2><i class="fa-solid fa-sliders"></i> Alle Gruppen verwalten</h2>
    <?php $allGroups = recoveryUserGetAllGroups($db); ?>
    <form method="POST" action="<?= $umBaseUrl ?>&amp;um_uid=<?= (int)$umUser['userID'] ?>">
        <input type="hidden" name="um_action" value="set_groups">
        <table>
            <thead>
                <tr>
                    <th style="width:1px"></th>
                    <th style="width:55px">ID</th>
                    <th>Gruppe</th>
                    <th style="width:80px">Typ</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($allGroups as $grp):
                $gid      = (int)$grp['groupID'];
                $isSystem = \in_array($gid, [1, 2], true);
                $isMember = \in_array($gid, $currentGroupIDs, true);
                $groupType = (int) $grp['groupType'];
                if ($groupType === 1) {
                    $gType = 'System';
                } elseif ($groupType === 4) {
                    $gType = 'Admin';
                } else {
                    $gType = 'Normal';
                }
            ?>
                <tr>
                    <td style="text-align:center;">
                        <input type="checkbox" name="group_ids[]" id="grp_<?= $gid ?>"
                            value="<?= $gid ?>"
                            <?= $isMember ? 'checked' : '' ?>
                            <?= $isSystem ? 'disabled' : '' ?>>
                        <?php if ($isSystem): ?>
                        <input type="hidden" name="group_ids[]" value="<?= $gid ?>">
                        <?php endif; ?>
                    </td>
                    <td><?= $gid ?></td>
                    <td><label for="grp_<?= $gid ?>"><?= recoveryFormatUserGroupLabel($gid, (string) $grp['groupName']) ?></label></td>
                    <td><small><?= $gType ?></small></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <button type="submit" style="margin-top:15px;"><i class="fa-solid fa-sliders"></i> Gruppen speichern</button>
    </form>

<?php endif; // $umUser !== null

else: // $umUid === 0 → Suchmaske ?>

    <?php foreach ($umErrors as $err): ?>
    <div class="alert alert-error"><?= $err ?></div>
    <?php endforeach; ?>
    <?php foreach ($umMessages as $msg): ?>
    <div class="alert alert-success"><?= $msg ?></div>
    <?php endforeach; ?>

    <h2><i class="fa-solid fa-magnifying-glass"></i> Benutzer suchen</h2>
    <form method="POST" action="<?= $umBaseUrl ?>">
        <div class="form-group">
            <label for="um_search">Benutzername oder E-Mail (Präfix-Suche, max. 50 Treffer)</label>
            <input type="text" id="um_search" name="um_search"
                value="<?= \htmlspecialchars($_POST['um_search'] ?? '') ?>"
                placeholder="z.&thinsp;B. Admin oder admin@example.com" autofocus>
        </div>
        <button type="submit"><i class="fa-solid fa-magnifying-glass"></i> Suchen</button>
    </form>

    <?php
    $umSearchQuery = \trim($_POST['um_search'] ?? '');
    if ($umSearchQuery !== ''):
        try {
            $umResults = recoveryUserSearch($db, $umSearchQuery);
        } catch (\Throwable $e) {
            $umResults = [];
            echo '<div class="alert alert-error">Suchfehler: ' . \htmlspecialchars($e->getMessage()) . '</div>';
        }
    ?>

    <?php if (empty($umResults)): ?>
    <div class="alert alert-info" style="margin-top:20px;">
        Keine Benutzer für <code><?= \htmlspecialchars($umSearchQuery) ?></code> gefunden.
    </div>
    <?php else: ?>
    <table style="margin-top:20px;">
        <thead>
            <tr>
                <th style="width:55px">ID</th>
                <th>Benutzername</th>
                <th>E-Mail</th>
                <th style="width:100px">Status</th>
                <th style="width:55px">2FA</th>
                <th style="width:1px"></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($umResults as $umRow): ?>
            <tr>
                <td><?= (int)$umRow['userID'] ?></td>
                <td><?= \htmlspecialchars($umRow['username']) ?></td>
                <td><?= \htmlspecialchars($umRow['email']) ?></td>
                <td>
                    <?php if ($umRow['banned']): ?>
                        <span style="color:#e74c3c">Gesperrt</span>
                    <?php elseif ($umRow['activationCode'] != 0): ?>
                        <span style="color:#f39c12">Inaktiv</span>
                    <?php else: ?>
                        <span style="color:#00bc8c">Aktiv</span>
                    <?php endif; ?>
                </td>
                <td><?= $umRow['multifactorActive'] ? '<span style="color:#f39c12">Ja</span>' : 'Nein' ?></td>
                <td>
                    <a href="<?= $umBaseUrl ?>&amp;um_uid=<?= (int)$umRow['userID'] ?>" class="button" style="padding:5px 12px; font-size:13px;">Bearbeiten</a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
    <?php endif; // $umSearchQuery !== '' ?>

<?php endif; // $umUid > 0 ?>
<?php
}

// ============================================================================
// MODUS 4: CACHE CLEAR
// ============================================================================

elseif ($mode === RECOVERY_MODE_CACHE_CLEAR) {
?>
    <h1>Cache Clear</h1>
    <p class="subtitle">Löscht alle Caches und kompilierte Templates</p>

<?php
    if (!isset($_POST['confirm_clear'])) {
?>
    <div class="alert alert-warning">
        <strong>Folgende Verzeichnisse werden geleert:</strong><br>
        tmp/<br>
        cache/<br>
        templates/compiled/<br>
        acp/templates/compiled/<br>
        sowie bei installierten Anwendungen z.&nbsp;B. <code>shrinkr/templates/compiled/</code> und <code>shrinkr/acp/templates/compiled/</code>.<br><br>
        <strong>Hinweis:</strong> Anschließend werden fehlende Option-<code>define()</code>s aus der Datenbank <strong>aller Plugins</strong> sowie aus kompilierten Templates ermittelt und sicher in <code>options.inc.php</code> nachgetragen (gegen Fatal Error „Undefined constant“ im ACP).
    </div>

    <form method="POST">
        <input type="hidden" name="confirm_clear" value="1">
        <button type="submit" class="btn-danger"><i class="fa-solid fa-broom"></i> Cache jetzt löschen</button>
    </form>
<?php
    } else {
        $deletedFiles = clearCompiledTemplates();
        $optionFbLog = [];
        recoveryEnsureOptionConstantFallbacks($db, WCF_N, $optionFbLog);

        echo '<div class="alert alert-success">';
        echo '<strong>Cache erfolgreich geleert.</strong><br>';
        echo 'Gelöschte Dateien: ' . $deletedFiles . '<br>';
        foreach ($optionFbLog as $fbEntry) {
            echo \htmlspecialchars($fbEntry) . '<br>';
        }
        echo '</div>';
    }
}

// ============================================================================
// MODUS 5: PAKETLISTE REPARIEREN
// ============================================================================

elseif ($mode === RECOVERY_MODE_PACKAGE_LIST_REPAIR) {
?>
    <h1>Paketliste reparieren</h1>
    <p class="subtitle">Entfernt verwaiste Datenbankeinträge, die die ACP-Paketliste oder Deinstallation blockieren</p>

<?php
    $orphanSql = recoveryGenerateOrphanRepairSql(WCF_N);

    if (!isset($_POST['confirm_repair'])) {
?>
    <div class="alert alert-warning">
        <strong>Typische Symptome:</strong><br>
        • ACP-Paketliste: <code>Attempt to read property "packageID" on null</code><br>
        • Deinstallation: <code>assert($package !== null)</code> bei hängender Queue<br><br>
        <strong>Bereinigt (generisch, alle Plugins):</strong> verwaiste <code>application</code>-Zeilen,
        <code>package_installation_queue</code> / <code>node</code> / <code>form</code>,
        <code>package_requirement</code>, <code>package_exclusion</code>, File-Logs.
    </div>

    <details style="margin: 1rem 0;">
        <summary>SQL für phpMyAdmin / manuelle Ausführung (WCF_N=<?= (int) WCF_N ?>)</summary>
        <pre class="recoveryLog"><?= \htmlspecialchars($orphanSql) ?></pre>
    </details>

    <form method="POST">
        <input type="hidden" name="confirm_repair" value="1">
        <button type="submit" class="btn-danger"><i class="fa-solid fa-list-check"></i> Verwaiste Einträge jetzt bereinigen</button>
    </form>
<?php
    } else {
        try {
            $result = recoveryRepairOrphanedPackageReferences($db, WCF_N);
            $deletedFiles = clearCompiledTemplates();
            $optionFbLog = [];
            recoveryEnsureOptionConstantFallbacks($db, WCF_N, $optionFbLog);

            echo '<div class="alert alert-success">';
            echo '<strong>Paketliste-Reparatur abgeschlossen.</strong><br><br>';
            foreach ($result['log'] as $entry) {
                echo '• ' . \htmlspecialchars($entry) . '<br>';
            }
            echo '<br>Cache-Dateien gelöscht: ' . (int) $deletedFiles . '<br>';
            foreach ($optionFbLog as $fbEntry) {
                echo \htmlspecialchars($fbEntry) . '<br>';
            }
            echo '</div>';
        } catch (\Throwable $e) {
            echo '<div class="alert alert-error"><strong>Fehler:</strong> '
                . \htmlspecialchars(recoveryFormatUserError($e)) . '</div>';
            recoveryRenderExceptionDetails($e);
        }
    }
}

// ============================================================================
// MODUS 6: PLUGIN-DATEIEN REPARIEREN
// ============================================================================

elseif ($mode === RECOVERY_MODE_PACKAGE_FILE_REPAIR) {
    $fileRepairUrl = recoveryBuildModeUrl(RECOVERY_MODE_PACKAGE_FILE_REPAIR, $authHash);
    $wcfDir = \rtrim(WCF_DIR, '/\\') . \DIRECTORY_SEPARATOR;
    $liveMissing = recoveryFindMissingBootstrapClasses($wcfDir);
?>
    <h1>Plugin-Dateien reparieren</h1>
    <p class="subtitle">Fehlende PHP-Klassen (Bootstrap-Registrierung) aus hochgeladenem Paket wiederherstellen</p>

<?php
    if (recoveryWasPostTruncated()) {
        recoveryRenderPostTruncatedWarning();
    }

    if (isset($_POST['confirm_file_repair'])) {
        $repairLog = [];
        $extractDir = recoveryResolveTrustedExtractDir($authHash);
        if ($extractDir === null) {
            echo '<div class="alert alert-error"><strong>Kein gültiges Paket-Archiv in der Session.</strong> Bitte erneut hochladen.</div>';
        } else {
            $payload = recoveryExtractPackageInstructionTars($extractDir, $repairLog);
            if ($payload === null) {
                echo '<div class="alert alert-error"><strong>Paket konnte nicht ausgewertet werden.</strong><br>';
                foreach ($repairLog as $line) {
                    echo \htmlspecialchars($line) . '<br>';
                }
                echo '</div>';
            } else {
                $toRestore = $liveMissing;
                if (isset($_POST['repair_classes']) && \is_array($_POST['repair_classes'])) {
                    $toRestore = [];
                    foreach ($_POST['repair_classes'] as $cn) {
                        $cn = \trim((string) $cn);
                        if ($cn !== '') {
                            $toRestore[] = $cn;
                        }
                    }
                }
                $copied = recoveryRepairMissingPluginFilesFromPayload($wcfDir, $payload, $toRestore, $repairLog);
                $deletedFiles = clearCompiledTemplates();
                $optionFbLog = [];
                recoveryEnsureOptionConstantFallbacks($db, WCF_N, $optionFbLog);
                recoveryCleanupUploadWorkspace();

                echo '<div class="alert alert-success">';
                echo '<strong>Reparatur abgeschlossen.</strong><br>';
                echo 'Kopierte Dateien: ' . \count($copied) . '<br>';
                echo 'Cache-Dateien gelöscht: ' . (int) $deletedFiles . '<br><br>';
                foreach ($repairLog as $line) {
                    echo '• ' . \htmlspecialchars($line) . '<br>';
                }
                foreach ($optionFbLog as $fbEntry) {
                    echo \htmlspecialchars($fbEntry) . '<br>';
                }
                echo '<br><strong>Bitte ACP erneut testen.</strong>';
                echo '</div>';
            }
        }
    } elseif (isset($_POST['analyze_file_repair']) || recoveryHasUploadedPackageFile()) {
        $packageInput = recoveryResolvePackageInputFromRequest($authHash);
        if (isset($packageInput['error'])) {
            echo '<div class="alert alert-error">' . \htmlspecialchars($packageInput['error']) . '</div>';
        } elseif (empty($packageInput['extractDir'])) {
            echo '<div class="alert alert-error">Kein Entpack-Verzeichnis.</div>';
        } else {
            $analyzeLog = [];
            $payload = recoveryExtractPackageInstructionTars($packageInput['extractDir'], $analyzeLog);
            if ($payload === null) {
                echo '<div class="alert alert-error"><strong>Paket konnte nicht ausgewertet werden.</strong><br>';
                foreach ($analyzeLog as $line) {
                    echo \htmlspecialchars($line) . '<br>';
                }
                echo '</div>';
            } else {
            $missingNow = recoveryFindMissingBootstrapClasses($wcfDir);
?>
    <div class="alert alert-info">
        <strong>Analyse</strong> für Paket <code><?= \htmlspecialchars((string) ($payload['package'] ?? $packageInput['packageIdentifier'] ?? '')) ?></code>
        (App: <code><?= \htmlspecialchars((string) ($payload['applicationDirectory'] ?? '')) ?></code>)<br>
        <?php foreach ($analyzeLog as $line) {
            echo \htmlspecialchars($line) . '<br>';
        } ?>
    </div>

    <form method="POST" action="<?= \htmlspecialchars($fileRepairUrl) ?>">
        <?php recoveryRenderFormModeHiddenFields(RECOVERY_MODE_PACKAGE_FILE_REPAIR, $authHash); ?>
        <input type="hidden" name="extract_dir" value="<?= \htmlspecialchars((string) $packageInput['extractDir']) ?>">
        <input type="hidden" name="confirm_file_repair" value="1">

        <h2>Fehlende Klassen auf dem Server</h2>
        <?php if ($missingNow === []): ?>
        <p>Keine fehlenden Bootstrap-Klassen erkannt. Sie können trotzdem Bootstrap aus dem Paket synchronisieren.</p>
        <?php else: ?>
        <ul>
        <?php foreach ($missingNow as $cn): ?>
            <li><label><input type="checkbox" name="repair_classes[]" value="<?= \htmlspecialchars($cn) ?>" checked>
                <code><?= \htmlspecialchars($cn) ?></code></label></li>
        <?php endforeach; ?>
        </ul>
        <?php endif; ?>

        <button type="submit" class="btn-danger"><i class="fa-solid fa-screwdriver-wrench"></i> Dateien jetzt wiederherstellen + Cache leeren</button>
    </form>
<?php
        }
        }
    } else {
?>
    <div class="alert alert-warning">
        <strong>Typisches Symptom:</strong> ACP zeigt <code>ClassNotFoundException</code> für eine Klasse, die im Bootstrap
        (<code>lib/bootstrap/de.*.php</code>) registriert ist, deren <code>.class.php</code> auf dem Server fehlt
        (z.&nbsp;B. nach partiellem Löschen von <code>shrinkr/lib/</code>).<br><br>
        Das Tool liest <code>lib/bootstrap/*.php</code>, findet fehlende Klassen und kopiert sie aus Ihrem
        <strong>Paket-Archiv</strong> (<code>files.tar</code> / <code>files_wcf.tar</code>), leert danach den Cache und räumt Uploads auf.
    </div>

    <?php if ($liveMissing !== []): ?>
    <div class="alert alert-error">
        <strong>Aktuell fehlend (Live-Scan):</strong>
        <ul style="margin:8px 0 0 20px">
        <?php foreach ($liveMissing as $cn): ?>
            <li><code><?= \htmlspecialchars($cn) ?></code></li>
        <?php endforeach; ?>
        </ul>
    </div>
    <?php else: ?>
    <div class="alert alert-success">Live-Scan: keine fehlenden Bootstrap-Klassen gefunden (ACP-Fehler kann andere Ursache haben).</div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" action="<?= \htmlspecialchars($fileRepairUrl) ?>">
        <?php recoveryRenderFormModeHiddenFields(RECOVERY_MODE_PACKAGE_FILE_REPAIR, $authHash); ?>
        <input type="hidden" name="analyze_file_repair" value="1">
        <label for="package_file">Paket-Archiv (.tar.gz) — z.&nbsp;B. de.sunnyc.wsc.shrinkr_v1.0.17.tar.gz</label>
        <input type="file" name="package_file" id="package_file" accept=".tar,.tar.gz,.tgz" required>
        <p style="margin-top:8px"><small>Optional Paket-ID:</small></p>
        <input type="text" name="package_identifier" placeholder="de.sunnyc.wsc.shrinkr" style="max-width:420px">
        <p style="margin-top:16px">
            <button type="submit" class="button"><i class="fa-solid fa-magnifying-glass"></i> Paket analysieren &amp; Vorschau</button>
        </p>
    </form>
<?php
    }
}

// ============================================================================
// MODUS 7: RECOVERY-WIZARD (halbautomatisch)
// ============================================================================

elseif ($mode === RECOVERY_MODE_RECOVERY_WIZARD) {
    $wizardUrl = recoveryBuildModeUrl(RECOVERY_MODE_RECOVERY_WIZARD, $authHash);
    $wcfDir = \rtrim(WCF_DIR, '/\\') . \DIRECTORY_SEPARATOR;
    $phase = (string) ($_POST['wizard_phase'] ?? $_GET['wizard_phase'] ?? 'package');
    $phaseIndex = match ($phase) {
        'diagnose' => 1,
        'plan' => 2,
        'run' => 3,
        'done' => 3,
        default => 0,
    };

    recoveryRenderWizardPhaseSteps($phaseIndex, ['Paket', 'Diagnose', 'Plan', 'Ausführung']);
?>
    <h1>Recovery-Wizard</h1>
    <p class="subtitle">Geführte Reparatur: Paket festlegen → Diagnose → Schritte wählen → ausführen. Sie behalten die Kontrolle.</p>

    <div class="alert alert-info" style="margin-bottom:20px">
        <strong>Ziel:</strong> ACP wieder erreichbar machen und defektes Plugin anschließend sauber entfernen.
        <a href="<?= \htmlspecialchars(recoveryHomeUrl($authHash)) ?>" style="color:#6EC2FF;margin-left:8px">← Andere Situation wählen</a>
    </div>

<?php
    $wizardEmergencyFixed = recoverySessionGetEmergencyFixed($authHash);
    if ($wizardEmergencyFixed !== null) {
        $suggestedPkg = '';
        foreach (recoveryExtractMissingClassesFromLog($wcfDir) as $cn) {
            if (\preg_match('/^([a-z0-9]+)\\\\/', (string) $cn, $m)) {
                $suggestedPkg = 'de.sunnyc.wsc.' . $m[1];
                break;
            }
        }
        $removeUrl = recoveryBuildModeUrl(RECOVERY_MODE_PLUGIN_UNINSTALL, $authHash, $suggestedPkg !== '' ? ['package_identifier' => $suggestedPkg] : []);
        ?>
    <div class="alert alert-warning" style="margin-bottom:20px">
        <strong>ACP-Notfall wurde bereits ausgeführt.</strong> Dateien nicht wiederherstellen — Plugin vollständig entfernen.
        <a href="<?= \htmlspecialchars($removeUrl) ?>" class="button" style="margin-left:12px;margin-top:8px;display:inline-block">
            <i class="fa-solid fa-trash-can"></i> Plugin entfernen
        </a>
    </div>
        <?php
    }

    if ($phase === 'run' && isset($_POST['wizard_execute'])) {
        if (recoveryHasUploadedPackageFile()) {
            $upload = recoveryHandlePackageUpload($_FILES['package_file']);
            if ($upload['ok'] && !empty($upload['extractDir'])) {
                recoveryStorePackageContext($authHash, (string) $upload['packageIdentifier'], $upload['extractDir']);
            }
        }
        $wizardState = recoveryWizardLoadState($authHash);
        $scopeForRun = isset($wizardState['scopeApplication']) ? (string) $wizardState['scopeApplication'] : '';
        $plan = [
            'orphans' => !empty($_POST['do_orphans']),
            'files' => !empty($_POST['do_files']) && $wizardEmergencyFixed === null,
            'neutralizeBootstrap' => !empty($_POST['do_neutralize_bootstrap']),
            'dbEventListeners' => !empty($_POST['do_db_event_listeners']),
            'cache' => !empty($_POST['do_cache']),
            'extractDir' => recoveryResolveWizardExtractDir($authHash),
            'scopeApplication' => $scopeForRun !== '' ? $scopeForRun : null,
            'dryRun' => !empty($_POST['wizard_dry_run']),
            'classes' => isset($_POST['repair_classes']) && \is_array($_POST['repair_classes'])
                ? \array_values(\array_filter(\array_map('strval', $_POST['repair_classes'])))
                : [],
        ];
        $execLog = [];
        $result = recoveryWizardExecutePlan($wcfDir, $db, WCF_N, $plan, $execLog);
        recoveryWizardSaveState($authHash, ['lastRun' => $result, 'lastPlan' => $plan]);
        $runInterp = recoveryBuildWizardRunInterpretation($result, $plan);
?>
    <div class="alert <?= !empty($result['dryRun']) ? 'alert-warning' : 'alert-success' ?>">
        <strong><?= !empty($result['dryRun']) ? 'Dry-Run abgeschlossen (keine Änderungen).' : 'Ausführung abgeschlossen.' ?></strong><br>
        Kopierte Dateien: <?= \count($result['copiedFiles'] ?? []) ?><br>
        Bootstrap angepasst (fehlende Listener auskommentiert): <?= \count($result['bootstrapNeutralized'] ?? []) ?> Datei(en)<br>
        DB Event-Listener entfernt: <?= (int) ($result['dbEventListenersDeleted'] ?? 0) ?><br>
        Cache-Dateien gelöscht: <?= (int) ($result['cacheDeleted'] ?? 0) ?>
    </div>
    <section class="recovery-rec-panel recovery-rec-panel--ok" style="margin-top:16px">
        <h2><i class="fa-solid fa-circle-check"></i> Was bedeutet das?</h2>
        <ul class="recovery-next-list">
        <?php foreach ($runInterp as $line): ?>
            <li><?= \htmlspecialchars($line) ?></li>
        <?php endforeach; ?>
        </ul>
        <p style="margin:14px 0 0;font-size:14px;color:#d0d0d0">
            <strong>Nächster Schritt:</strong> ACP testen. Wenn die Seite lädt, Plugin über
            <a href="<?= \htmlspecialchars(recoveryBuildModeUrl(RECOVERY_MODE_PLUGIN_UNINSTALL, $authHash)) ?>" style="color:#6EC2FF">Plugin Uninstall</a>
            vollständig entfernen.
        </p>
    </section>
    <p style="margin:12px 0 6px;font-weight:600;color:#fff">Technisches Protokoll</p>
    <button type="button" class="recovery-copy-btn" data-recovery-copy="wizard-exec-log" style="margin-bottom:8px">
        <i class="fa-solid fa-copy"></i> Protokoll kopieren
    </button>
    <pre class="recoveryLog" id="wizard-exec-log" style="max-height:320px;overflow:auto;margin-top:4px"><?php
        foreach ($execLog as $line) {
            echo \htmlspecialchars($line) . "\n";
        }
    ?></pre>
    <p style="margin-top:16px">
        <a href="<?= \htmlspecialchars($wizardUrl . '&wizard_phase=done') ?>" class="button">Weiter zur Zusammenfassung</a>
        <a href="<?= \htmlspecialchars($recoveryBaseUrl . 'acp/') ?>" class="button" style="margin-left:8px">ACP testen</a>
    </p>
<?php
    } elseif ($phase === 'done') {
        $state = recoveryWizardLoadState($authHash);
        $lastRun = $state['lastRun'] ?? [];
        $lastPlan = $state['lastPlan'] ?? [];
?>
    <div class="alert alert-info">
        <strong>Wizard abgeschlossen.</strong> Prüfen Sie den ACP. Bei Erfolg das defekte Plugin deinstallieren und das Recovery Tool entfernen.
    </div>
    <?php if ($lastRun !== []): ?>
    <section class="recovery-info-panel">
        <h2>Letzte Ausführung — Kurzüberblick</h2>
        <table class="table" style="width:100%">
            <tr><th>Kopierte Dateien</th><td><?= \count($lastRun['copiedFiles'] ?? []) ?></td></tr>
            <tr><th>Bootstrap-Dateien angepasst</th><td><?= \count($lastRun['bootstrapNeutralized'] ?? []) ?></td></tr>
            <tr><th>DB Event-Listener entfernt</th><td><?= (int) ($lastRun['dbEventListenersDeleted'] ?? 0) ?></td></tr>
            <tr><th>Cache-Dateien gelöscht</th><td><?= (int) ($lastRun['cacheDeleted'] ?? 0) ?></td></tr>
        </table>
    </section>
    <?php endif; ?>
    <section class="recovery-rec-panel recovery-rec-panel--ok">
        <h2><i class="fa-solid fa-list-check"></i> Checkliste</h2>
        <ol class="recovery-next-list">
            <li><a href="<?= \htmlspecialchars($recoveryBaseUrl . 'acp/') ?>" style="color:#6EC2FF">ACP öffnen</a> — lädt das Dashboard ohne Fehler?</li>
            <li>Ja → <a href="<?= \htmlspecialchars(recoveryBuildModeUrl(RECOVERY_MODE_PLUGIN_UNINSTALL, $authHash)) ?>" style="color:#6EC2FF">Plugin Uninstall</a> für vollständige Entfernung</li>
            <li>Nein → Wizard <a href="<?= \htmlspecialchars($wizardUrl . '&wizard_phase=package') ?>" style="color:#6EC2FF">von vorn</a> oder Experten-Modi auf der <a href="<?= \htmlspecialchars(recoveryHomeUrl($authHash)) ?>" style="color:#6EC2FF">Startseite</a></li>
            <li>Fertig → Recovery Tool vom Server löschen (Sicherheit)</li>
        </ol>
    </section>
    <p><a href="<?= \htmlspecialchars($recoveryBaseUrl . 'acp/') ?>" class="button"><i class="fa-solid fa-gauge-high"></i> Zum ACP</a></p>
    <p style="margin-top:12px"><a href="<?= \htmlspecialchars($wizardUrl . '&wizard_phase=package') ?>">Wizard von vorn beginnen</a></p>
<?php
    } elseif ($phase === 'plan' || isset($_POST['wizard_to_plan'])) {
        if (recoveryHasUploadedPackageFile()) {
            $upload = recoveryHandlePackageUpload($_FILES['package_file']);
            if ($upload['ok'] && !empty($upload['extractDir'])) {
                recoveryStorePackageContext($authHash, (string) $upload['packageIdentifier'], $upload['extractDir']);
            }
        }
        $state = recoveryWizardLoadState($authHash);
        $scopeApp = isset($state['scopeApplication']) ? (string) $state['scopeApplication'] : null;
        $scopeApp = $scopeApp !== '' ? $scopeApp : null;
        $diag = recoveryBuildSystemDiagnosis($wcfDir, $db, WCF_N, $scopeApp);
        recoveryWizardSaveState($authHash, \array_merge($state, ['diagnosis' => $diag]));
        $suggest = \array_merge([
            'orphans' => false,
            'files' => false,
            'neutralizeBootstrap' => false,
            'dbEventListeners' => false,
            'cache' => true,
        ], $diag['suggestedActions'] ?? []);
        $missing = $diag['missingBootstrapClasses'] ?? recoveryFindMissingBootstrapClasses($wcfDir);
        if ($scopeApp !== null) {
            $missing = recoveryFilterFqcnByApplicationPrefix($missing, $scopeApp);
        }
        $pkgCtx = recoveryLoadPackageContext($authHash);
        $extractDir = recoveryResolveWizardExtractDir($authHash);
        $sessionPackageId = (string) ($pkgCtx['packageIdentifier'] ?? $state['packageLabel'] ?? '');
        if ($extractDir !== null) {
            recoveryWizardSaveState($authHash, ['extractDir' => $extractDir]);
        }
        $wizardRec = recoveryBuildWizardRecommendations($diag, $sessionPackageId !== '' ? $sessionPackageId : null);
        $acpAlreadyFixed = recoverySessionGetEmergencyFixed($authHash) !== null;
        $recByKey = [];
        foreach ($wizardRec['steps'] as $rs) {
            if (isset($rs['key'])) {
                $recByKey[(string) $rs['key']] = $rs;
            }
        }
?>
    <?php if ($acpAlreadyFixed): ?>
    <div class="alert alert-warning" style="margin-bottom:16px">
        <strong>ACP läuft bereits?</strong> Dann <em>keine</em> fehlenden Plugin-Dateien wiederherstellen (Schritt 2 abwählen).
        Stattdessen <a href="<?= \htmlspecialchars(recoveryBuildModeUrl(RECOVERY_MODE_PLUGIN_UNINSTALL, $authHash)) ?>" style="color:#6EC2FF">Plugin entfernen</a>.
    </div>
    <?php endif; ?>

    <details class="recovery-info-panel" style="margin-bottom:16px">
        <summary style="cursor:pointer;font-weight:600;color:#fff">Empfehlungen aus der Diagnose</summary>
        <div style="margin-top:12px">
        <?php recoveryRenderWizardRecommendationsPanel($wizardRec); ?>
        </div>
    </details>

    <form method="POST" enctype="multipart/form-data" action="<?= \htmlspecialchars($wizardUrl) ?>"
        data-recovery-loading="Recovery-Schritte werden ausgeführt …"
        data-recovery-loading-steps="Reihenfolge: Paketliste → Dateien → Bootstrap → DB-Listener → Cache. Bitte nicht abbrechen.">
        <?php recoveryRenderFormModeHiddenFields(RECOVERY_MODE_RECOVERY_WIZARD, $authHash); ?>
        <input type="hidden" name="wizard_phase" value="run">
        <input type="hidden" name="wizard_execute" value="1">
        <?php if ($extractDir): ?>
        <input type="hidden" name="extract_dir" value="<?= \htmlspecialchars($extractDir) ?>">
        <?php endif; ?>

        <h2>Schritte auswählen (Reihenfolge bei Ausführung)</h2>
        <ol style="margin:0 0 16px 20px;color:#9D9D9D">
            <li>Paketliste bereinigen (verwaiste DB-Einträge)</li>
            <li>Fehlende Plugin-Dateien aus Archiv</li>
            <li>Bootstrap: fehlende PSR-14-<code>EventHandler::register</code>-Aufrufe auskommentieren (ACP-Notfall)</li>
            <li>DB: Event-Listener mit fehlender Klasse entfernen (ACP-Dashboard)</li>
            <li>Cache leeren + Option-Konstanten-Fallback</li>
        </ol>

        <p><label><input type="checkbox" name="do_orphans" value="1" <?= !empty($suggest['orphans']) ? 'checked' : '' ?>>
            <strong>1. Paketliste reparieren</strong> (<?= (int) ($diag['orphanApplicationCount'] ?? 0) ?> verwaiste Applications)</label></p>
        <?php if (isset($recByKey['orphans'])): ?>
        <div class="recovery-step-help"><?= $recByKey['orphans']['why'] ?? '' ?></div>
        <?php endif; ?>

        <p><label><input type="checkbox" name="do_files" value="1" <?= !empty($suggest['files']) && !$acpAlreadyFixed ? 'checked' : '' ?> <?= $acpAlreadyFixed ? 'disabled' : '' ?>>
            <strong>2. Plugin-Dateien wiederherstellen</strong> (<?= \count($missing) ?> fehlende Klassen)<?= $acpAlreadyFixed ? ' — nach ACP-Notfall deaktiviert' : '' ?></label></p>
        <?php if (isset($recByKey['files'])): ?>
        <div class="recovery-step-help"><?= $recByKey['files']['why'] ?? '' ?></div>
        <?php endif; ?>

        <?php
            $neutralCand = (int) ($diag['bootstrapNeutralizeCandidates'] ?? recoveryCountNeutralizableBootstrapRegisters($wcfDir));
        ?>
        <p><label><input type="checkbox" name="do_neutralize_bootstrap" value="1" <?= !empty($suggest['neutralizeBootstrap']) ? 'checked' : '' ?>>
            <strong>3. Bootstrap neutralisieren</strong> — betrifft <strong><?= $neutralCand ?></strong> PSR-14-<code>register()</code>-Aufruf(e)
            (nicht ladbar oder laut WoltLab-Log fehlend; zeilenweise auskommentiert; Backup <code>*.recovery-backup-*.php</code>).
            <em>Behebt typisch <code>ClassNotFoundException</code> beim ACP-Dashboard.</em></label></p>
        <?php if (isset($recByKey['neutralizeBootstrap'])): ?>
        <div class="recovery-step-help"><?= $recByKey['neutralizeBootstrap']['why'] ?? '' ?></div>
        <?php endif; ?>

        <?php
            $orphDb = $diag['orphanedDbEventListeners']
                ?? recoveryFindOrphanedDbEventListeners($wcfDir, $db, WCF_N, $scopeApp);
        ?>
        <p><label><input type="checkbox" name="do_db_event_listeners" value="1" <?= !empty($suggest['dbEventListeners']) ? 'checked' : '' ?>>
            <strong>4. DB Event-Listener bereinigen</strong> — <strong><?= \count($orphDb) ?></strong> Eintrag/Einträge in
            <code>wcf<?= (int) WCF_N ?>_event_listener</code> zeigen auf fehlende Klassen
            <em>(laut Log z.&nbsp;B. <code>BoxCollectingShrinkrDashboardListener</code> — das behebt den ACP-Dashboard-Fehler).</em></label></p>
        <?php if (isset($recByKey['dbEventListeners'])): ?>
        <div class="recovery-step-help"><?= $recByKey['dbEventListeners']['why'] ?? '' ?></div>
        <?php endif; ?>

        <?php if ($missing !== []): ?>
        <details style="margin:8px 0 12px 24px">
            <summary style="cursor:pointer;color:#9D9D9D">Klassen für Schritt 2 auswählen (<?= \count($missing) ?>)</summary>
            <ul style="margin:8px 0 0 8px">
            <?php foreach ($missing as $cn): ?>
                <li><label><input type="checkbox" name="repair_classes[]" value="<?= \htmlspecialchars($cn) ?>" checked>
                    <code><?= \htmlspecialchars($cn) ?></code></label></li>
            <?php endforeach; ?>
            </ul>
        </details>
        <?php endif; ?>

        <?php if ($extractDir): ?>
        <p class="alert alert-success" style="margin:12px 0">
            <strong>Paket-Archiv aus Schritt 1 aktiv</strong><?php if ($sessionPackageId !== ''): ?>
            — <code><?= \htmlspecialchars($sessionPackageId) ?></code><?php endif; ?>
            (Upload muss nicht erneut gewählt werden.)
        </p>
        <?php elseif ($sessionPackageId !== ''): ?>
        <p class="alert alert-info" style="margin:12px 0">
            Paket-ID gespeichert: <code><?= \htmlspecialchars($sessionPackageId) ?></code>.
            Für <strong>Schritt 2 (Dateien)</strong> bitte das Archiv unten nachreichen.
        </p>
        <label for="wizard_package_file">Paket-Archiv (.tar.gz)</label>
        <input type="file" name="package_file" id="wizard_package_file" accept=".tar,.tar.gz,.tgz">
        <?php else: ?>
        <div class="alert alert-warning" style="margin:12px 0">
            Für Schritt 2: <strong>Paket-Archiv erneut hochladen</strong> — Standard-WoltLab-<code>.tar.gz</code>
            mit <code>package.xml</code> und <code>files.tar</code> (egal ob WS-Packager, Simple Plugin Manager oder manuell).
        </div>
        <label for="wizard_package_file">Paket (.tar.gz)</label>
        <input type="file" name="package_file" id="wizard_package_file" accept=".tar,.tar.gz,.tgz">
        <?php endif; ?>

        <p><label><input type="checkbox" name="do_cache" value="1" checked>
            <strong>5. Cache leeren</strong> + options.inc.php-Fallback (empfohlen)</label></p>
        <?php if (isset($recByKey['cache'])): ?>
        <div class="recovery-step-help"><?= $recByKey['cache']['why'] ?? '' ?></div>
        <?php endif; ?>

        <p style="margin:14px 0;padding:12px;background:rgba(0,0,0,.2);border-radius:6px;border:1px solid #444">
            <label style="cursor:pointer">
                <input type="checkbox" name="wizard_dry_run" value="1">
                <strong>Dry-Run:</strong> Zeigt im Protokoll, was passieren würde — ohne Änderungen am Server.
            </label>
        </p>

        <p style="margin-top:20px">
            <button type="submit" class="btn-danger"><i class="fa-solid fa-play"></i> Ausgewählte Schritte jetzt ausführen</button>
            <a href="<?= \htmlspecialchars($wizardUrl . '&wizard_phase=diagnose') ?>" class="back-link" style="margin-left:12px">Zurück zur Diagnose</a>
            <a href="<?= \htmlspecialchars($wizardUrl . '&wizard_phase=package') ?>" class="back-link" style="margin-left:12px">Paket ändern</a>
        </p>
    </form>
<?php
    }

    $wizardUploadError = null;
    if (isset($_POST['wizard_to_diagnose'])) {
        $phase = 'diagnose';
        $scopeApp = null;
        $packageLabel = '';
        $fullServerScan = !empty($_POST['wizard_full_scan']);

        if (!$fullServerScan) {
            if (recoveryHasUploadedPackageFile()) {
                $upload = recoveryHandlePackageUpload($_FILES['package_file']);
                if (!$upload['ok']) {
                    $wizardUploadError = (string) ($upload['error'] ?? 'Upload fehlgeschlagen.');
                    $phase = 'package';
                } elseif (!empty($upload['extractDir'])) {
                    recoveryStorePackageContext($authHash, (string) $upload['packageIdentifier'], $upload['extractDir']);
                    $meta = recoveryParsePackageMetaFromExtractDir((string) $upload['extractDir']);
                    $packageLabel = (string) ($meta['package'] ?? $upload['packageIdentifier'] ?? '');
                    $scopeApp = (string) ($meta['applicationDirectory'] ?? '');
                    recoveryWizardSaveState($authHash, [
                        'extractDir' => (string) $upload['extractDir'],
                        'packageLabel' => $packageLabel,
                    ]);
                }
            } elseif (!empty($_POST['package_identifier'])) {
                $packageLabel = \trim((string) $_POST['package_identifier']);
                if (\preg_match(RECOVERY_PACKAGE_ID_PATTERN, $packageLabel)) {
                    recoveryStorePackageContext($authHash, $packageLabel, null);
                    $scopeApp = recoveryGuessApplicationFromPackageIdentifier($packageLabel);
                } else {
                    $wizardUploadError = 'Ungültige Paket-ID.';
                    $phase = 'package';
                }
            } else {
                $wizardUploadError = 'Bitte Paket-Archiv hochladen, Paket-ID eingeben oder „gesamten Server prüfen“ wählen.';
                $phase = 'package';
            }
        }

        if ($phase === 'diagnose') {
            $diag = recoveryBuildSystemDiagnosis(
                $wcfDir,
                $db,
                WCF_N,
                $fullServerScan ? null : ($scopeApp !== '' && $scopeApp !== null ? $scopeApp : null)
            );
            recoveryWizardSaveState($authHash, [
                'diagnosis' => $diag,
                'scopeApplication' => $fullServerScan ? null : ($scopeApp !== '' ? $scopeApp : null),
                'packageLabel' => $packageLabel,
                'fullServerScan' => $fullServerScan,
            ]);
        }
    }

    if ($phase === 'package') {
?>
    <div class="alert alert-info">
        <strong>Schritt 1 — Paket festlegen</strong><br>
        Damit Diagnose und Dateiwiederherstellung gezielt für Ihr Plugin laufen.
    </div>
    <section class="recovery-info-panel">
        <h2>Was passiert danach?</h2>
        <ol class="recovery-next-list">
            <li><strong>Diagnose</strong> — Bootstrap, Datenbank und Log auf fehlende Klassen prüfen.</li>
            <li><strong>Plan</strong> — empfohlene Schritte mit Erklärung; Sie setzen die Häkchen.</li>
            <li><strong>Ausführung</strong> — nur gewählte Aktionen, mit Fortschrittsanzeige.</li>
        </ol>
        <p style="margin:12px 0 0;font-size:13px;color:#9D9D9D">
            <strong>.tar.gz-Archiv</strong> mit <code>package.xml</code> + <code>files.tar</code> (Standard-WoltLab-Paket, beliebiger Packager).
            <strong>Nur Paket-ID</strong> filtert die Diagnose, reicht aber nicht zum Kopieren von Dateien.
        </p>
    </section>

    <?php if ($wizardUploadError !== null): ?>
    <div class="alert alert-error"><?= \htmlspecialchars($wizardUploadError) ?></div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" action="<?= \htmlspecialchars($wizardUrl) ?>" data-recovery-loading="Paket wird hochgeladen und Diagnose vorbereitet …">
        <?php recoveryRenderFormModeHiddenFields(RECOVERY_MODE_RECOVERY_WIZARD, $authHash); ?>
        <input type="hidden" name="wizard_phase" value="diagnose">
        <input type="hidden" name="wizard_to_diagnose" value="1">

        <label for="wizard_package_file"><strong>Paket-Archiv</strong> (.tar, .tar.gz, .tgz) — empfohlen</label>
        <input type="file" name="package_file" id="wizard_package_file" accept=".tar,.tar.gz,.tgz">

        <p style="margin-top:16px"><label for="wizard_package_identifier">oder nur Paket-ID</label></p>
        <input type="text" name="package_identifier" id="wizard_package_identifier" placeholder="de.vendor.meinplugin" style="max-width:420px">

        <p style="margin-top:20px">
            <button type="submit" class="button"><i class="fa-solid fa-arrow-right"></i> Weiter zur Diagnose</button>
        </p>
    </form>

    <form method="POST" action="<?= \htmlspecialchars($wizardUrl) ?>" style="margin-top:12px" data-recovery-loading="Live-Diagnose läuft (Bootstrap-Scan kann einige Sekunden dauern) …">
        <?php recoveryRenderFormModeHiddenFields(RECOVERY_MODE_RECOVERY_WIZARD, $authHash); ?>
        <input type="hidden" name="wizard_phase" value="diagnose">
        <input type="hidden" name="wizard_to_diagnose" value="1">
        <input type="hidden" name="wizard_full_scan" value="1">
        <button type="submit" class="back-link" style="background:none;border:none;color:#6EC2FF;cursor:pointer;padding:0">
            Ohne Paket — gesamten Server prüfen (alle Plugins)
        </button>
    </form>
<?php
    } elseif ($phase === 'diagnose') {
        $state = recoveryWizardLoadState($authHash);
        if (empty($state['diagnosis'])):
?>
    <div class="alert alert-warning">Noch keine Diagnose. Bitte zuerst Schritt 1 (Paket) ausführen.</div>
    <p><a href="<?= \htmlspecialchars($wizardUrl . '&wizard_phase=package') ?>" class="button">Zu Schritt 1 — Paket</a></p>
<?php
        else:
        $scopeForDiag = (string) ($state['scopeApplication'] ?? '');
        $diag = recoveryBuildSystemDiagnosis(
            $wcfDir,
            $db,
            WCF_N,
            $scopeForDiag !== '' ? $scopeForDiag : null
        );
        $fullServerScan = !empty($state['fullServerScan']);
        $packageLabel = (string) ($state['packageLabel'] ?? '');
        $scopeApp = (string) ($state['scopeApplication'] ?? '');
?>
    <div class="alert alert-info">
        <strong>Schritt 2 — Diagnose</strong><br>
        <?php if ($fullServerScan): ?>
        Es wird der <strong>gesamte Server</strong> geprüft (alle Bootstrap-Registrierungen auf diesem System).
        <?php elseif ($packageLabel !== ''): ?>
        Gefiltert für Paket <code><?= \htmlspecialchars($packageLabel) ?></code><?php if ($scopeApp !== ''): ?>
        — App <code><?= \htmlspecialchars($scopeApp) ?></code><?php endif; ?>.
        Das Tool zeigt nur fehlende Klassen, deren Namespace zu dieser App passt (nicht „erraten“, sondern gefiltert).
        <?php else: ?>
        Live-Scan: Bootstrap-Einträge vs. vorhandene <code>.class.php</code> auf dem Server.
        <?php endif; ?>
    </div>

    <h2>Ergebnis (aktueller Server-Stand)</h2>
    <table class="table" style="width:100%;margin-bottom:16px">
        <tr><th>Fehlende Bootstrap-Klassen auf dem Server</th><td><?= \count($diag['missingBootstrapClasses']) ?></td></tr>
        <tr><th>Verwaiste Applications (DB)</th><td><?= (int) $diag['orphanApplicationCount'] ?></td></tr>
        <tr><th>PSR-14 Bootstrap-Register (fehlende Listener)</th><td><?= (int) ($diag['bootstrapNeutralizeCandidates'] ?? recoveryCountNeutralizableBootstrapRegisters($wcfDir)) ?> betroffen</td></tr>
        <tr><th>DB Event-Listener (fehlende Klasse)</th><td><?= \count($diag['orphanedDbEventListeners'] ?? recoveryFindOrphanedDbEventListeners($wcfDir, $db, WCF_N, $scopeApp !== '' ? $scopeApp : null)) ?> betroffen</td></tr>
    </table>

    <?php
        $diagRec = recoveryBuildWizardRecommendations($diag, $packageLabel !== '' ? $packageLabel : null);
    ?>
    <details class="recovery-info-panel" style="margin-bottom:16px">
        <summary style="cursor:pointer;font-weight:600;color:#fff">Empfehlungen &amp; Hinweise</summary>
        <div style="margin-top:12px">
        <?php recoveryRenderWizardRecommendationsPanel($diagRec); ?>
        </div>
    </details>
    <?php recoveryRenderLogExcerptsPanel($diag['logExcerpts'] ?? [], 'wizard-diag-log'); ?>

    <?php if ($diag['missingBootstrapClasses'] === []): ?>
    <div class="alert alert-success">
        Keine fehlenden Bootstrap-Klassen (im gewählten Umfang) gefunden. Sie können trotzdem fortfahren
        (z.&nbsp;B. Cache leeren oder Paketliste bereinigen).
    </div>
    <?php else: ?>
    <div class="alert alert-error" style="margin-bottom:12px">
        <strong><?= \count($diag['missingBootstrapClasses']) ?> fehlende Klassen</strong> im gewählten Umfang
        (Bootstrap-Registrierung, aber keine <code>.class.php</code> auf dem Server).
    </div>
    <?php recoveryRenderWizardMissingClassesDetails($diag['missingBootstrapClasses']); ?>
    <?php endif; ?>

    <?php
        $diagExtractDir = recoveryResolveWizardExtractDir($authHash);
    ?>
    <form method="POST" action="<?= \htmlspecialchars($wizardUrl) ?>" data-recovery-loading="Plan &amp; Auswahl wird geladen …">
        <?php recoveryRenderFormModeHiddenFields(RECOVERY_MODE_RECOVERY_WIZARD, $authHash); ?>
        <input type="hidden" name="wizard_phase" value="plan">
        <input type="hidden" name="wizard_to_plan" value="1">
        <?php if ($diagExtractDir): ?>
        <input type="hidden" name="extract_dir" value="<?= \htmlspecialchars($diagExtractDir) ?>">
        <?php endif; ?>
        <button type="submit" class="button"><i class="fa-solid fa-arrow-right"></i> Weiter — Plan &amp; Auswahl</button>
    </form>
<?php
        endif;
    }
}

// ============================================================================
// MODUS 8: SYSTEM-CHECK
// ============================================================================

elseif ($mode === RECOVERY_MODE_SYSTEM_CHECK) {
    $wcfDirCheck = \rtrim(WCF_DIR, '/\\') . \DIRECTORY_SEPARATOR;
    $assetsCheck = recoveryGetSetupAssets();
    recoveryRenderSystemCheckPage($authHash, $wcfDirCheck, $db, WCF_N, $assetsCheck);
}

// ============================================================================
// MODUS 9: DATENSICHERUNG (Handbuch)
// ============================================================================

elseif ($mode === RECOVERY_MODE_BACKUP_GUIDE) {
    recoveryRenderBackupGuidePage($authHash, \rtrim(WCF_DIR, '/\\') . \DIRECTORY_SEPARATOR);
}

// ============================================================================
// MODUS 10: VERZEICHNISSTRUKTUR (Handbuch)
// ============================================================================

elseif ($mode === RECOVERY_MODE_DIRECTORY_STRUCTURE) {
    recoveryRenderDirectoryStructurePage(
        $authHash,
        \rtrim(WCF_DIR, '/\\') . \DIRECTORY_SEPARATOR,
        $db,
        WCF_N
    );
}

?>
<?php
recoveryRenderPageEnd();
