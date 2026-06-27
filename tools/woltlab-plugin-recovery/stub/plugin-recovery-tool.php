<?php

declare(strict_types=1);

/**
 * WoltLab Plugin Recovery Tool — Stub (v2.0)
 *
 * Upload ins WoltLab-Hauptverzeichnis. Auth bleibt separat (plugin-recovery-auth.php).
 * Nach Auth wird recovery-{VERSION}.tar.gz von GitHub geladen und nach recovery-tool/ entpackt.
 *
 * @version 2.2.1
 */

define('RECOVERY_STUB_VERSION', '2.7.42');
define('RECOVERY_PACKAGE_VERSION', '2.7.48');
define('RECOVERY_STUB_INTEGRITY_HASH', '0000000000000000000000000000000000000000000000000000000000000000');
define('RECOVERY_MIN_PHP_VERSION', '8.1.0');
define('RECOVERY_GITHUB_REPO', 'benjarogit/sc-woltlab-plugin-recovery');
define('RECOVERY_AUTH_FILENAME', 'plugin-recovery-auth.php');
define('RECOVERY_PACKAGE_DIR_NAME', 'recovery-tool');

require __DIR__ . '/recovery-stub-shell.php';
require __DIR__ . '/recovery-stub-logger.php';
require __DIR__ . '/recovery-stub-auth.php';

if (\PHP_VERSION_ID < 80100) {
    \header('Content-Type: text/html; charset=utf-8');
    \http_response_code(500);
    echo '<!DOCTYPE html><html lang="de"><head><meta charset="utf-8"><title>Recovery Tool</title></head><body>';
    echo '<h1>PHP-Version zu alt</h1><p>Mindestens <strong>PHP 8.1</strong> erforderlich. Aktuell: <code>'
        . \htmlspecialchars(\PHP_VERSION) . '</code></p></body></html>';
    exit;
}

function recoveryStubWcfRoot(): string
{
    foreach ([__DIR__, \dirname(__DIR__), \dirname(__DIR__, 2)] as $dir) {
        if (\is_file($dir . '/global.php') && \is_file($dir . '/config.inc.php')) {
            return \rtrim($dir, '/\\') . '/';
        }
    }

    return \rtrim(__DIR__, '/\\') . '/';
}

function recoveryStubPackageDir(): string
{
    return recoveryStubWcfRoot() . RECOVERY_PACKAGE_DIR_NAME . '/';
}

function recoveryStubReleaseDownloadUrl(string $version): string
{
    return 'https://github.com/' . RECOVERY_GITHUB_REPO
        . '/releases/download/v' . $version . '/recovery-' . $version . '.tar.gz';
}

function recoveryStubReleasePageUrl(string $version): string
{
    return 'https://github.com/' . RECOVERY_GITHUB_REPO . '/releases/tag/v' . $version;
}

/**
 * WoltLab-Temp wie FileUtil::getTempFolder() — WCF_DIR/tmp/
 */
function recoveryStubDownloadCacheDir(): string
{
    $dir = recoveryStubWcfRoot() . 'tmp/';
    if (\is_file($dir)) {
        @\unlink($dir);
    }
    if (!\is_dir($dir) && !@\mkdir($dir, 0777, true)) {
        return \rtrim(\sys_get_temp_dir(), '/\\') . '/';
    }
    if (\is_dir($dir) && !\is_writable($dir)) {
        @\chmod($dir, 0777);
    }

    return \rtrim($dir, '/\\') . '/';
}

/**
 * @return array{ok: true, data: string}|array{ok: false, error: string}
 */
function recoveryStubHttpDownload(string $url): array
{
    if (\function_exists('curl_init')) {
        $ch = \curl_init($url);
        if ($ch === false) {
            return ['ok' => false, 'error' => 'cURL konnte nicht initialisiert werden.'];
        }
        \curl_setopt_array($ch, [
            \CURLOPT_RETURNTRANSFER => true,
            \CURLOPT_FOLLOWLOCATION => true,
            \CURLOPT_MAXREDIRS => 10,
            \CURLOPT_CONNECTTIMEOUT => 30,
            \CURLOPT_TIMEOUT => 300,
            \CURLOPT_USERAGENT => 'WoltLab-Plugin-Recovery/' . RECOVERY_STUB_VERSION,
            \CURLOPT_SSL_VERIFYPEER => true,
            \CURLOPT_SSL_VERIFYHOST => 2,
        ]);
        $data = \curl_exec($ch);
        $httpCode = (int) \curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = \curl_error($ch);
        \curl_close($ch);
        if ($data === false) {
            return ['ok' => false, 'error' => 'cURL: ' . ($curlError !== '' ? $curlError : 'Unbekannter Fehler')];
        }
        if ($httpCode !== 200) {
            return ['ok' => false, 'error' => 'HTTP-Status ' . $httpCode . ' beim Download.'];
        }

        return ['ok' => true, 'data' => $data];
    }

    if (!\ini_get('allow_url_fopen')) {
        return [
            'ok' => false,
            'error' => 'allow_url_fopen ist deaktiviert und cURL nicht verfügbar. Bitte manuell installieren.',
        ];
    }

    $context = \stream_context_create([
        'http' => [
            'method' => 'GET',
            'follow_location' => 1,
            'max_redirects' => 10,
            'timeout' => 300,
            'header' => 'User-Agent: WoltLab-Plugin-Recovery/' . RECOVERY_STUB_VERSION . "\r\n",
        ],
        'ssl' => [
            'verify_peer' => true,
            'verify_peer_name' => true,
        ],
    ]);
    $data = @\file_get_contents($url, false, $context);
    if ($data === false) {
        $last = \error_get_last();

        return [
            'ok' => false,
            'error' => 'Download fehlgeschlagen'
                . ($last['message'] ?? '' ? ' (' . $last['message'] . ')' : '') . '.',
        ];
    }

    return ['ok' => true, 'data' => $data];
}

function recoveryStubIsGzipArchive(string $data): bool
{
    return \strlen($data) >= 2 && $data[0] === "\x1f" && $data[1] === "\x8b";
}

function recoveryStubReadInstalledVersion(): ?string
{
    $manifest = recoveryStubPackageDir() . 'manifest.json';
    if (!\is_file($manifest)) {
        $versionPhp = recoveryStubPackageDir() . 'version.php';
        if (\is_file($versionPhp)) {
            require $versionPhp;
            if (\defined('RECOVERY_PACKAGE_VERSION')) {
                return (string) RECOVERY_PACKAGE_VERSION;
            }
        }

        return null;
    }
    $json = \json_decode((string) \file_get_contents($manifest), true);
    if (!\is_array($json) || empty($json['version'])) {
        return null;
    }

    return (string) $json['version'];
}

function recoveryStubPackageReady(): bool
{
    $bootstrap = recoveryStubPackageDir() . 'bootstrap.php';
    if (!\is_file($bootstrap)) {
        return false;
    }
    $installed = recoveryStubReadInstalledVersion();
    if ($installed === null) {
        return false;
    }

    return \version_compare($installed, RECOVERY_PACKAGE_VERSION, '>=');
}

function recoveryStubRemoveDirectory(string $dir): void
{
    if (!\is_dir($dir)) {
        return;
    }
    $it = new \RecursiveIteratorIterator(
        new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
        \RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($it as $f) {
        $f->isDir() ? @\rmdir($f->getPathname()) : @\unlink($f->getPathname());
    }
    @\rmdir($dir);
}

function recoveryStubCopyDirectory(string $src, string $dst): void
{
    if (!\is_dir($dst) && !@\mkdir($dst, 0755, true)) {
        return;
    }
    $it = new \RecursiveIteratorIterator(
        new \RecursiveDirectoryIterator($src, \RecursiveDirectoryIterator::SKIP_DOTS),
        \RecursiveIteratorIterator::SELF_FIRST
    );
    foreach ($it as $item) {
        $target = $dst . \substr($item->getPathname(), \strlen($src));
        if ($item->isDir()) {
            if (!\is_dir($target)) {
                @\mkdir($target, 0755, true);
            }
        } else {
            @\copy($item->getPathname(), $target);
        }
    }
}

function recoveryStubMoveItem(string $src, string $dst): void
{
    if (\is_dir($src)) {
        if (\is_dir($dst)) {
            recoveryStubRemoveDirectory($dst);
        }
        if (!@\rename($src, $dst)) {
            recoveryStubCopyDirectory($src, $dst);
            recoveryStubRemoveDirectory($src);
        }

        return;
    }

    if (!@\rename($src, $dst)) {
        @\copy($src, $dst);
        @\unlink($src);
    }
}

function recoveryStubPharRelativePath(string $archive, \SplFileInfo $entry): string
{
    $path = \str_replace('\\', '/', $entry->getPathname());
    $prefix = 'phar://' . $archive . '/';
    if (\str_starts_with($path, $prefix)) {
        return \substr($path, \strlen($prefix));
    }

    return \ltrim($path, './');
}

function recoveryStubArchiveUsesPackagePrefix(string $archive): bool
{
    if (!\class_exists(\PharData::class, false)) {
        return true;
    }

    try {
        $phar = new \PharData($archive);
        foreach (new \RecursiveIteratorIterator($phar, \RecursiveIteratorIterator::SELF_FIRST) as $entry) {
            if (!$entry->isFile()) {
                continue;
            }
            $path = recoveryStubPharRelativePath($archive, $entry);

            return \str_starts_with($path, RECOVERY_PACKAGE_DIR_NAME . '/');
        }
    } catch (\Throwable $ignored) {
    }

    return false;
}

function recoveryStubValidateArchive(string $archive): ?string
{
    if (!\class_exists(\PharData::class, false)) {
        return null;
    }

    try {
        $phar = new \PharData($archive);
        foreach (new \RecursiveIteratorIterator($phar) as $entry) {
            if (!$entry->isFile()) {
                continue;
            }
            $path = recoveryStubPharRelativePath($archive, $entry);
            if ($path === 'bootstrap.php' || \str_ends_with($path, '/bootstrap.php')) {
                return null;
            }
        }
    } catch (\Throwable $e) {
        return 'Archiv ungültig: ' . $e->getMessage();
    }

    return 'Archiv enthält keine bootstrap.php.';
}

function recoveryStubFlattenNestedPackageDir(string $destination): void
{
    $nested = \rtrim($destination, '/\\') . '/' . RECOVERY_PACKAGE_DIR_NAME;
    if (!\is_dir($nested) || !\is_file($nested . '/bootstrap.php')) {
        return;
    }

    foreach (new \DirectoryIterator($nested) as $item) {
        if ($item->isDot()) {
            continue;
        }
        recoveryStubMoveItem($item->getPathname(), \rtrim($destination, '/\\') . '/' . $item->getFilename());
    }
    recoveryStubRemoveDirectory($nested);
}

/**
 * @return array{ok: bool, error?: string}
 */
function recoveryStubExtractTarGz(string $archive, string $destination): array
{
    $destination = \rtrim($destination, '/\\') . '/';
    if (!\is_dir($destination) && !@\mkdir($destination, 0755, true)) {
        return ['ok' => false, 'error' => 'Zielverzeichnis konnte nicht angelegt werden.'];
    }

    $archiveError = recoveryStubValidateArchive($archive);
    if ($archiveError !== null) {
        return ['ok' => false, 'error' => $archiveError];
    }

    $stripPrefix = recoveryStubArchiveUsesPackagePrefix($archive);

    if (\class_exists(\PharData::class, false)) {
        try {
            $phar = new \PharData($archive);
            $files = [];
            foreach (new \RecursiveIteratorIterator($phar) as $entry) {
                if (!$entry->isFile()) {
                    continue;
                }
                $relative = recoveryStubPharRelativePath($archive, $entry);
                if ($relative === '' || \str_contains($relative, '..')) {
                    continue;
                }
                $files[] = $relative;
            }
            if ($files === []) {
                return ['ok' => false, 'error' => 'Archiv enthält keine Dateien.'];
            }
            $phar->extractTo($destination, $files, true);
            if ($stripPrefix) {
                recoveryStubFlattenNestedPackageDir($destination);
            }
        } catch (\Throwable $e) {
            return ['ok' => false, 'error' => 'PharData: ' . $e->getMessage()];
        }
    } else {
        $tar = \trim((string) \shell_exec('command -v tar 2>/dev/null'));
        if ($tar === '') {
            return ['ok' => false, 'error' => 'Weder Phar noch tar verfügbar.'];
        }
        $cmd = \escapeshellarg($tar) . ' -xzf ' . \escapeshellarg($archive)
            . ($stripPrefix ? ' --strip-components=1' : '')
            . ' -C ' . \escapeshellarg($destination) . ' 2>&1';
        \exec($cmd, $out, $code);
        if ($code !== 0) {
            return ['ok' => false, 'error' => 'tar exit ' . $code . ': ' . \implode("\n", $out)];
        }
    }

    if (!\is_file($destination . 'bootstrap.php')) {
        $found = [];
        if (\is_dir($destination)) {
            foreach (new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($destination, \RecursiveDirectoryIterator::SKIP_DOTS)
            ) as $file) {
                if ($file->isFile() && $file->getFilename() === 'bootstrap.php') {
                    $found[] = \str_replace($destination, '', $file->getPathname());
                }
            }
        }

        $hint = $found !== []
            ? ' Gefunden unter: ' . \implode(', ', $found) . ' (Verschachtelung konnte nicht aufgelöst werden).'
            : ' Bitte <code>recovery-tool/</code> leeren und erneut versuchen oder manuell entpacken.';

        return ['ok' => false, 'error' => 'Paket unvollständig (bootstrap.php fehlt).' . $hint];
    }

    return ['ok' => true];
}

/**
 * @return array{ok: bool, error?: string}
 */
function recoveryStubInstallPackage(string $version): array
{
    @\set_time_limit(300);
    recoveryStubLog('info', 'Paket-Installation gestartet', ['version' => $version]);

    $url = recoveryStubReleaseDownloadUrl($version);
    $dest = recoveryStubPackageDir();
    $archive = recoveryStubDownloadCacheDir() . 'recovery-' . $version . '.tar.gz';

    $download = recoveryStubHttpDownload($url);
    if (!$download['ok']) {
        recoveryStubLog('error', 'Download fehlgeschlagen', ['error' => $download['error'], 'url' => $url]);

        return [
            'ok' => false,
            'error' => $download['error'] . ' Bitte '
                . '<a href="' . \htmlspecialchars($url) . '">recovery-' . $version . '.tar.gz</a> '
                . 'manuell nach <code>' . RECOVERY_PACKAGE_DIR_NAME . '/</code> entpacken.',
        ];
    }

    $data = $download['data'];
    if (!recoveryStubIsGzipArchive($data)) {
        return [
            'ok' => false,
            'error' => 'Ungültige Antwort vom Server (kein gzip-Archiv). Bitte manuell installieren.',
        ];
    }

    if (@\file_put_contents($archive, $data) === false) {
        return ['ok' => false, 'error' => 'Archiv konnte nicht gespeichert werden: ' . \htmlspecialchars($archive)];
    }

    if (\is_dir($dest)) {
        recoveryStubRemoveDirectory($dest);
    }
    if (!@\mkdir($dest, 0755, true)) {
        @\unlink($archive);

        return ['ok' => false, 'error' => 'Verzeichnis ' . RECOVERY_PACKAGE_DIR_NAME . '/ konnte nicht erstellt werden.'];
    }

    $extract = recoveryStubExtractTarGz($archive, $dest);
    @\unlink($archive);

    if ($extract['ok']) {
        recoveryStubLog('info', 'Paket erfolgreich installiert', ['dest' => $dest]);
    } else {
        recoveryStubLog('error', 'Paket-Entpacken fehlgeschlagen', ['error' => $extract['error'] ?? '']);
    }

    return $extract;
}

function recoveryStubCleanupAuxiliary(): void
{
    recoveryStubLog('info', 'Stub-Cleanup gestartet');
    recoveryStubCleanupAllRecoveryArtifacts();
    recoveryStubLog('info', 'Stub-Cleanup abgeschlossen');
}

recoveryStubLogRequestStarted();
recoveryStubLogExposeHeaders();

$integrityResult = recoveryStubVerifyIntegrityDetailed();
if (!$integrityResult['ok']) {
    recoveryStubLogError('Integritätsprüfung fehlgeschlagen', $integrityResult);
    recoveryStubLogAction('integrity_denied', $integrityResult);
    recoveryStubRenderIntegrityError(
        (string) ($integrityResult['message'] ?? 'Integritätsprüfung fehlgeschlagen.'),
        (string) ($integrityResult['logDir'] ?? recoveryStubLogDir())
    );
    exit;
}
recoveryStubLogDebug('bootstrap', 'integrity_ok', ['buildId' => recoveryStubBuildId()]);

// --- Token / Sitzung ---
recoveryStubEnsurePhpSession();
$authHash = null;
if (!empty($_REQUEST['t']) && recoveryStubAssertValidToken((string) $_REQUEST['t'])) {
    $authHash = (string) $_REQUEST['t'];
} else {
    $sessionToken = recoveryStubGetSessionAuthToken();
    if ($sessionToken !== null && recoveryStubIsAuthenticated($sessionToken)) {
        $authHash = $sessionToken;
    }
}
if ($authHash === null) {
    $authHash = \bin2hex(\random_bytes(20));
    recoveryStubCreatePendingSession($authHash);
    recoveryStubLogAction('session_start', ['tokenPrefix' => \substr($authHash, 0, 8)]);
    \header('Location: plugin-recovery-tool.php?t=' . $authHash);
    exit;
}
$action = (!empty($_REQUEST['action'])) ? (string) $_REQUEST['action'] : '';

if ($action === 'download-auth-file') {
    recoveryStubLogAction('auth_download');
    $ajax = !empty($_REQUEST['ajax']);
    $generated = recoveryStubGenerateAuthFileContent($authHash);
    if (!$generated['ok']) {
        recoveryStubLogError('Auth-Datei konnte nicht erstellt werden', ['error' => $generated['error'] ?? '']);
        if ($ajax) {
            \header('Content-Type: application/json; charset=utf-8');
            echo \json_encode([
                'ok' => false,
                'message' => (string) ($generated['error'] ?? 'Auth-Datei konnte nicht erstellt werden.'),
            ], \JSON_UNESCAPED_UNICODE);
            exit;
        }
        recoveryStubRenderAuthWizard($authHash, (string) ($generated['error'] ?? ''));
        exit;
    }
    $content = $generated['content'];
    if ($ajax) {
        \header('Content-Type: application/json; charset=utf-8');
        echo \json_encode([
            'ok' => true,
            'filename' => RECOVERY_AUTH_FILENAME,
            'content' => \base64_encode($content),
            'step' => 2,
        ], \JSON_UNESCAPED_UNICODE);
        exit;
    }
    \header('Content-type: application/octet-stream');
    \header('Content-Disposition: attachment; filename="' . RECOVERY_AUTH_FILENAME . '"');
    \header('Content-Length: ' . (string) \strlen($content));
    echo $content;
    exit;
}

$isAuthenticated = recoveryStubIsAuthenticated($authHash);

if ($action === 'auth-status') {
    $reason = null;
    $details = null;
    if (!$isAuthenticated) {
        $check = recoveryStubValidateAuthFile($authHash);
        $reason = (string) ($check['reason'] ?? 'unknown');
        recoveryStubLogAction('auth_status_pending', ['reason' => $reason]);
        $details = recoveryStubAuthFailureDetails($reason);
    } else {
        recoveryStubLogAction('auth_status_ok');
    }
    \header('Content-Type: application/json; charset=utf-8');
    echo \json_encode([
        'ok' => $isAuthenticated,
        'reason' => $isAuthenticated ? null : $reason,
        'message' => $isAuthenticated ? null : recoveryStubAuthFailureMessage($reason),
        'details' => $details,
    ], \JSON_UNESCAPED_UNICODE);
    exit;
}

if ($action === 'cleanup') {
    recoveryStubLogAction('cleanup');
    recoveryStubCleanupAuxiliary();
    \register_shutdown_function(static function (): void {
        @\unlink(__DIR__ . '/plugin-recovery-tool.php');
    });
    \header('Location: ' . recoveryStubWcfRoot() . 'acp/');
    exit;
}

if ($action === 'install-package' && $isAuthenticated) {
    recoveryStubLogAction('install_package', ['version' => RECOVERY_PACKAGE_VERSION]);
    $result = recoveryStubInstallPackage(RECOVERY_PACKAGE_VERSION);
    if ($result['ok']) {
        \header('Location: plugin-recovery-tool.php?t=' . \urlencode($authHash) . '&package_ok=1');
        exit;
    }
    recoveryStubRenderPackageInstallPage($authHash, (string) ($result['error'] ?? ''));
    exit;
}

if (!$isAuthenticated) {
    if (recoveryStubLoadAuthState(recoveryStubPendingAuthPath($authHash)) === null
        && recoveryStubLoadAuthState(recoveryStubBoundAuthPath($authHash)) === null) {
        recoveryStubCreatePendingSession($authHash);
    }
    $authHint = null;
    $authReason = null;
    if (\is_file(recoveryStubWcfRoot() . RECOVERY_AUTH_FILENAME)) {
        $check = recoveryStubValidateAuthFile($authHash);
        if (!$check['ok'] && isset($check['reason'])) {
            $authReason = (string) $check['reason'];
            $authHint = recoveryStubAuthFailureMessage($authReason);
        }
    }
    recoveryStubLogAction('auth_wizard', ['hint' => $authHint, 'reason' => $authReason]);
    recoveryStubRenderAuthWizard($authHash, $authHint, $authReason);
    exit;
}

if (!recoveryStubPackageReady()) {
    recoveryStubLogAction('package_install_page');
    recoveryStubRenderPackageInstallPage($authHash);
    exit;
}

recoveryStubLogAction('package_bootstrap');
// --- Paket laden ---
\define('RECOVERY_WCF_ROOT', recoveryStubWcfRoot());
\define('RECOVERY_PACKAGE_DIR', recoveryStubPackageDir());
$recoveryAuthHash = $authHash;
$recoveryIsAuthenticated = true;
require recoveryStubPackageDir() . 'bootstrap.php';
