<?php

declare(strict_types=1);

\define('RECOVERY_STUB_LOG_SUBDIR', 'log/recovery');

function recoveryStubLogDir(): string
{
    static $resolved = null;
    if ($resolved !== null) {
        return $resolved;
    }

    $dir = \rtrim(recoveryStubWcfRoot(), '/\\') . '/' . RECOVERY_STUB_LOG_SUBDIR . '/';
    if (\is_file($dir)) {
        @\unlink($dir);
    }
    if (!\is_dir($dir)) {
        @\mkdir($dir, 0775, true);
    }
    if (\is_dir($dir)) {
        if (!\is_writable($dir)) {
            @\chmod($dir, 0775);
        }
        if (\is_writable($dir)) {
            return $resolved = $dir;
        }
    }

    $fallback = \rtrim(\sys_get_temp_dir(), '/\\') . '/woltlab-recovery-stub/';
    if (!\is_dir($fallback)) {
        @\mkdir($fallback, 0775, true);
    }

    return $resolved = $fallback;
}

function recoveryStubLogPath(string $basename): string
{
    return recoveryStubLogDir() . $basename;
}

/**
 * @return array<string, mixed>
 */
function recoveryStubRequestContext(): array
{
    $token = isset($_REQUEST['t']) ? (string) $_REQUEST['t'] : '';

    return [
        'method' => $_SERVER['REQUEST_METHOD'] ?? 'GET',
        'action' => isset($_REQUEST['action']) ? (string) $_REQUEST['action'] : null,
        'tokenPrefix' => $token !== '' ? \substr($token, 0, 8) : null,
        'uri' => $_SERVER['REQUEST_URI'] ?? '',
        'remoteAddr' => $_SERVER['REMOTE_ADDR'] ?? null,
        'stubVersion' => \defined('RECOVERY_STUB_VERSION') ? RECOVERY_STUB_VERSION : null,
    ];
}

function recoveryStubWriteLogLine(string $basename, string $line): void
{
    $path = recoveryStubLogPath($basename);
    if (@\file_put_contents($path, $line . "\n", \FILE_APPEND | \LOCK_EX) === false) {
        @\error_log('[recovery-stub] log_write_failed path=' . $path . ' line=' . $line);
    }
}

/**
 * @param array<string, mixed> $context
 */
function recoveryStubLog(string $level, string $message, array $context = []): void
{
    $line = \sprintf("[%s] [%s] %s", \date('Y-m-d H:i:s'), \strtoupper($level), $message);
    if ($context !== []) {
        $json = \json_encode($context, \JSON_UNESCAPED_UNICODE | \JSON_INVALID_UTF8_SUBSTITUTE);
        if ($json !== false) {
            $line .= ' ' . $json;
        }
    }
    recoveryStubWriteLogLine('stub-' . \date('Y-m-d') . '.log', $line);
}

/**
 * @param array<string, mixed> $context
 */
function recoveryStubLogAction(string $action, array $context = []): void
{
    $payload = ['action' => $action] + recoveryStubRequestContext() + $context;
    recoveryStubLog('info', 'ACTION ' . $action, $payload);
    recoveryStubWriteLogLine(
        'stub-actions-' . \date('Y-m-d') . '.log',
        \sprintf("[%s] %s %s", \date('Y-m-d H:i:s'), $action, \json_encode($payload, \JSON_UNESCAPED_UNICODE) ?: '{}')
    );
}

/**
 * @param array<string, mixed> $context
 */
function recoveryStubLogError(string $message, array $context = []): void
{
    $payload = recoveryStubRequestContext() + $context;
    recoveryStubLog('error', $message, $payload);
    recoveryStubWriteLogLine(
        'stub-errors-' . \date('Y-m-d') . '.log',
        \sprintf("[%s] %s %s", \date('Y-m-d H:i:s'), $message, \json_encode($payload, \JSON_UNESCAPED_UNICODE) ?: '{}')
    );
}

/** @param array<string, mixed> $data */
function recoveryStubLogDebug(string $location, string $message, array $data = []): void
{
    recoveryStubLog('debug', $message, ['location' => $location] + $data);
    $payload = [
        'location' => $location,
        'message' => $message,
        'data' => $data,
        'timestamp' => (int) \round(\microtime(true) * 1000),
        'stubVersion' => \defined('RECOVERY_STUB_VERSION') ? RECOVERY_STUB_VERSION : null,
    ] + recoveryStubRequestContext();
    $line = \json_encode($payload, \JSON_UNESCAPED_UNICODE | \JSON_INVALID_UTF8_SUBSTITUTE);
    if ($line !== false) {
        recoveryStubWriteLogLine('stub-debug-' . \date('Y-m-d') . '.ndjson', $line);
    }
}

function recoveryStubLogRequestStarted(): void
{
    recoveryStubLogAction('request', [
        'logDir' => recoveryStubLogDir(),
        'wcfRoot' => recoveryStubWcfRoot(),
    ]);
}

function recoveryStubLogDisplayPath(): string
{
    $root = \rtrim(recoveryStubWcfRoot(), '/\\') . '/';
    $dir = recoveryStubLogDir();
    if (\str_starts_with($dir, $root)) {
        return \rtrim(\substr($dir, \strlen($root)), '/') . '/';
    }

    return $dir;
}

/**
 * @return list<array{file: string, label: string, description: string, exists: bool, size: int}>
 */
function recoveryStubLogFileCatalog(): array
{
    $date = \date('Y-m-d');
    $entries = [
        ['file' => 'stub-' . $date . '.log', 'label' => 'Gesamtprotokoll', 'description' => 'Alle Meldungen des Stub'],
        ['file' => 'stub-actions-' . $date . '.log', 'label' => 'Aktionen', 'description' => 'Requests, Auth-Schritte, Installation'],
        ['file' => 'stub-errors-' . $date . '.log', 'label' => 'Fehlerprotokoll', 'description' => 'Nur bei echten Fehlern (Integrität, Auth, Download)'],
        ['file' => 'stub-debug-' . $date . '.ndjson', 'label' => 'Debug (NDJSON)', 'description' => 'Strukturierte Diagnose'],
    ];
    foreach ($entries as &$entry) {
        $path = recoveryStubLogPath($entry['file']);
        $entry['exists'] = \is_file($path) && \is_readable($path);
        $entry['size'] = $entry['exists'] ? (int) \filesize($path) : 0;
    }
    unset($entry);

    return $entries;
}

function recoveryStubReadLogTail(string $basename, int $maxLines = 15): string
{
    if (!\preg_match('~^stub-(?:actions-|errors-|debug-)?\d{4}-\d{2}-\d{2}\.(?:log|ndjson)$~', $basename)) {
        return '';
    }
    $path = recoveryStubLogPath($basename);
    if (!\is_file($path) || !\is_readable($path)) {
        return '';
    }
    $lines = @\file($path, \FILE_IGNORE_NEW_LINES);
    if (!\is_array($lines) || $lines === []) {
        return '';
    }

    return \implode("\n", \array_slice($lines, -\max(1, $maxLines)));
}

/**
 * @return list<string>
 */
function recoveryStubRecentLogExcerpt(int $maxLines = 18): array
{
    $date = \date('Y-m-d');
    $chunks = [];
    foreach (['stub-errors-' . $date . '.log', 'stub-actions-' . $date . '.log', 'stub-' . $date . '.log'] as $file) {
        $tail = recoveryStubReadLogTail($file, 8);
        if ($tail !== '') {
            $chunks[] = '--- ' . $file . " ---\n" . $tail;
        }
    }

    if ($chunks === []) {
        return [];
    }

    $merged = \explode("\n", \implode("\n", $chunks));

    return \array_slice($merged, -\max(1, $maxLines));
}

function recoveryStubLogExposeHeaders(): void
{
    static $sent = false;
    if ($sent || \headers_sent()) {
        return;
    }
    $sent = true;
    \header('X-WFL-Recovery-Stub-Log-Dir-B64: ' . \base64_encode(recoveryStubLogDir()));
    \header('X-WFL-Recovery-Stub-Log-B64: ' . \base64_encode(recoveryStubLogPath('stub-' . \date('Y-m-d') . '.log')));
}

function recoveryStubCleanupAllRecoveryArtifacts(): void
{
    $root = recoveryStubWcfRoot();

    $logDir = \rtrim($root, '/\\') . '/' . RECOVERY_STUB_LOG_SUBDIR;
    if (\is_dir($logDir)) {
        recoveryStubRemoveDirectory($logDir);
    }

    foreach (
        [
            $root . 'log/recovery-tool-*.ndjson',
            $root . 'log/plugin-recovery-*.ndjson',
            $root . 'log/debug-*.ndjson',
        ] as $pattern
    ) {
        foreach (\glob($pattern) ?: [] as $file) {
            if (\is_file($file)) {
                @\unlink($file);
            }
        }
    }

    foreach (
        [
            $root . RECOVERY_AUTH_FILENAME,
            $root . 'uploads/.recovery-cache',
            recoveryStubPackageDir(),
        ] as $path
    ) {
        if (\is_file($path)) {
            @\unlink($path);
        } elseif (\is_dir($path)) {
            recoveryStubRemoveDirectory($path);
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
}
