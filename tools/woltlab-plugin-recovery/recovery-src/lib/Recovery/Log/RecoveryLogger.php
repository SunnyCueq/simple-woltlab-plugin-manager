<?php

declare(strict_types=1);

\define('RECOVERY_LOG_SUBDIR', 'log/recovery');
\define('RECOVERY_DEBUG_LOG_PREFIX', 'recovery-tool-');

function recoveryLogDir(): string
{
    $root = recoveryWcfRoot();
    $dir = \rtrim($root, '/\\') . '/' . RECOVERY_LOG_SUBDIR . '/';
    if (\is_file($dir)) {
        @\unlink($dir);
    }
    if (!\is_dir($dir)) {
        @\mkdir($dir, 0775, true);
    }
    if (\is_dir($dir) && !\is_writable($dir)) {
        @\chmod($dir, 0775);
    }

    return $dir;
}

function recoveryDebugLogBasename(): string
{
    return 'debug-' . \date('Y-m-d') . '.ndjson';
}

function recoveryTextLogBasename(): string
{
    return 'recovery-' . \date('Y-m-d') . '.log';
}

function recoveryDebugLogPath(): string
{
    static $resolved = null;
    if ($resolved !== null) {
        return $resolved;
    }

    $fromEnv = \getenv('RECOVERY_AGENT_LOG_PATH');
    if (\is_string($fromEnv) && $fromEnv !== '') {
        return $resolved = $fromEnv;
    }

    return $resolved = recoveryLogDir() . recoveryDebugLogBasename();
}

function recoveryTextLogPath(): string
{
    return recoveryLogDir() . recoveryTextLogBasename();
}

/**
 * @param array<string, mixed> $context
 */
function recoveryLog(string $level, string $message, array $context = []): void
{
    $line = \sprintf(
        "[%s] [%s] %s",
        \date('Y-m-d H:i:s'),
        \strtoupper($level),
        $message
    );
    if ($context !== []) {
        $encoded = \json_encode($context, \JSON_UNESCAPED_UNICODE | \JSON_INVALID_UTF8_SUBSTITUTE);
        if ($encoded !== false) {
            $line .= ' ' . $encoded;
        }
    }
    @\file_put_contents(recoveryTextLogPath(), $line . "\n", \FILE_APPEND | \LOCK_EX);
}

/** @param array<string, mixed> $data */
function recoveryLogDebug(string $hypothesisId, string $location, string $message, array $data = []): void
{
    recoveryLog('debug', $message, ['hypothesisId' => $hypothesisId, 'location' => $location] + $data);

    $path = recoveryDebugLogPath();
    $payload = [
        'hypothesisId' => $hypothesisId,
        'location' => $location,
        'message' => $message,
        'data' => $data,
        'timestamp' => (int) \round(\microtime(true) * 1000),
        'phpVersion' => \PHP_VERSION,
        'recoveryVersion' => \defined('RECOVERY_VERSION') ? RECOVERY_VERSION : null,
    ];
    $line = \json_encode($payload, \JSON_UNESCAPED_UNICODE | \JSON_INVALID_UTF8_SUBSTITUTE);
    if ($line === false) {
        @\error_log('[recovery] ndjson_encode_failed');

        return;
    }
    if (@\file_put_contents($path, $line . "\n", \FILE_APPEND | \LOCK_EX) === false) {
        @\error_log('[recovery] ndjson_write_failed path=' . $path);
    }
}

function recoveryLogExposeDebugHeaders(): void
{
    static $sent = false;
    if ($sent || \headers_sent()) {
        return;
    }
    $sent = true;
    \header('X-WFL-Recovery-Log-Dir-B64: ' . \base64_encode(recoveryLogDir()));
    \header('X-WFL-Recovery-Debug-Log-B64: ' . \base64_encode(recoveryDebugLogPath()));
}

/**
 * Entfernt log/recovery/ und Legacy-NDJSON-Dateien in log/.
 */
function recoveryCleanupRecoveryLogs(): void
{
    $root = recoveryWcfRoot();

    $recoveryLogDir = \rtrim($root, '/\\') . '/' . RECOVERY_LOG_SUBDIR;
    if (\is_dir($recoveryLogDir)) {
        recoveryRemoveDirectoryRecursive($recoveryLogDir);
    }

    $legacyPatterns = [
        $root . 'log/recovery-tool-*.ndjson',
        $root . 'log/plugin-recovery-*.ndjson',
        $root . 'log/debug-*.ndjson',
    ];
    foreach ($legacyPatterns as $pattern) {
        foreach (\glob($pattern) ?: [] as $file) {
            if (\is_file($file)) {
                @\unlink($file);
            }
        }
    }

    $legacyBeside = \defined('RECOVERY_PACKAGE_DIR')
        ? RECOVERY_PACKAGE_DIR . '/plugin-recovery-agent-debug.ndjson'
        : $root . 'recovery-tool/plugin-recovery-agent-debug.ndjson';
    if (\is_file($legacyBeside)) {
        @\unlink($legacyBeside);
    }

    $besideLogDir = \defined('RECOVERY_PACKAGE_DIR') ? RECOVERY_PACKAGE_DIR . '/log/' : '';
    if ($besideLogDir !== '' && \is_dir($besideLogDir)) {
        recoveryRemoveDirectoryRecursive($besideLogDir);
    }
}

/**
 * Alle Recovery-Artefakte außer plugin-recovery-tool.php (wird per Shutdown gelöscht).
 */
function recoveryCleanupAllAuxiliaryFiles(): void
{
    recoveryCleanupRecoveryLogs();

    $root = recoveryWcfRoot();
    $paths = [
        $root . 'plugin-recovery-auth.php',
        $root . 'plugin-recovery.php',
        $root . 'universal-recovery.php',
        $root . 'acp-repair.php',
        $root . 'wsc-recovery.php',
        $root . 'recovery-tool.php',
        $root . 'uploads/.recovery-cache',
    ];

    foreach ($paths as $path) {
        if (\is_file($path)) {
            @\unlink($path);
        } elseif (\is_dir($path)) {
            recoveryRemoveDirectoryRecursive($path);
        }
    }

    $tmpDir = \rtrim($root, '/\\') . '/tmp/';
    if (\is_dir($tmpDir)) {
        foreach (\glob($tmpDir . 'recovery-*.tar.gz') ?: [] as $archive) {
            if (\is_file($archive)) {
                @\unlink($archive);
            }
        }
    }

    if (\defined('RECOVERY_PACKAGE_DIR') && \is_dir(RECOVERY_PACKAGE_DIR)) {
        recoveryRemoveDirectoryRecursive(RECOVERY_PACKAGE_DIR);
    } elseif (\is_dir($root . 'recovery-tool')) {
        recoveryRemoveDirectoryRecursive($root . 'recovery-tool');
    }
}

if (!\function_exists('recoveryRemoveDirectoryRecursive')) {
    function recoveryRemoveDirectoryRecursive(string $dir): void
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
}
