<?php

declare(strict_types=1);

\define('RECOVERY_AUTH_FORMAT', 2);
\define('RECOVERY_AUTH_PENDING_DIR', 'log/recovery/.auth-pending');
\define('RECOVERY_AUTH_BOUND_DIR', 'log/recovery/.auth-bound');

function recoveryStubAuthStateDir(string $subDir): string
{
    $dir = \rtrim(recoveryStubWcfRoot(), '/\\') . '/' . $subDir . '/';
    if (!\is_dir($dir)) {
        @\mkdir($dir, 0700, true);
    }
    if (\is_dir($dir)) {
        @\chmod($dir, 0700);
    }

    return $dir;
}

function recoveryStubPendingAuthPath(string $token): string
{
    return recoveryStubAuthStateDir(RECOVERY_AUTH_PENDING_DIR) . $token . '.json';
}

function recoveryStubBoundAuthPath(string $token): string
{
    return recoveryStubAuthStateDir(RECOVERY_AUTH_BOUND_DIR) . $token . '.json';
}

function recoveryStubBuildId(): string
{
    $hash = \defined('RECOVERY_STUB_INTEGRITY_HASH') ? (string) RECOVERY_STUB_INTEGRITY_HASH : '';

    return RECOVERY_STUB_VERSION . '-' . \substr($hash, 0, 16);
}

/**
 * @return array{ok: bool, message?: string, expectedPrefix?: string, actualPrefix?: string, logDir?: string}
 */
function recoveryStubVerifyIntegrityDetailed(): array
{
    $logDir = recoveryStubLogDir();
    if (!\defined('RECOVERY_STUB_INTEGRITY_HASH') || RECOVERY_STUB_INTEGRITY_HASH === '') {
        return [
            'ok' => false,
            'message' => 'Stub-Integritätsprüfung fehlt (kein offizielles Release?).',
            'logDir' => $logDir,
        ];
    }
    $content = (string) @\file_get_contents(__FILE__);
    if ($content === '') {
        return [
            'ok' => false,
            'message' => 'Stub-Datei konnte nicht gelesen werden.',
            'logDir' => $logDir,
        ];
    }
    $placeholder = \str_repeat('0', 64);
    $canonical = \preg_replace(
        "/^define\\('RECOVERY_STUB_INTEGRITY_HASH',\\s*'[^']*'\\);/m",
        "define('RECOVERY_STUB_INTEGRITY_HASH', '" . $placeholder . "');",
        $content,
        1
    );
    if ($canonical === null) {
        return [
            'ok' => false,
            'message' => 'Stub-Integritätsprüfung fehlgeschlagen.',
            'logDir' => $logDir,
        ];
    }
    $expected = (string) RECOVERY_STUB_INTEGRITY_HASH;
    $actual = \hash('sha256', $canonical);
    if (!\hash_equals($expected, $actual)) {
        return [
            'ok' => false,
            'message' => 'Die Datei plugin-recovery-tool.php wurde verändert oder ist kein offizielles Release von GitHub.',
            'expectedPrefix' => \substr($expected, 0, 16),
            'actualPrefix' => \substr($actual, 0, 16),
            'logDir' => $logDir,
        ];
    }

    return ['ok' => true, 'logDir' => $logDir];
}

function recoveryStubVerifyIntegrity(): ?string
{
    $result = recoveryStubVerifyIntegrityDetailed();

    return $result['ok'] ? null : (string) ($result['message'] ?? 'Integritätsprüfung fehlgeschlagen.');
}

function recoveryStubAssertValidToken(string $token): bool
{
    return \preg_match('~^[a-f0-9]{40}$~', $token) === 1;
}

/**
 * @return array<string, mixed>|null
 */
function recoveryStubLoadAuthState(string $path): ?array
{
    if (!\is_file($path) || !\is_readable($path)) {
        return null;
    }
    $json = \json_decode((string) \file_get_contents($path), true);

    return \is_array($json) ? $json : null;
}

function recoveryStubSaveAuthState(string $path, array $state): bool
{
    $dir = \dirname($path);
    if (!\is_dir($dir)) {
        @\mkdir($dir, 0700, true);
    }
    $payload = \json_encode($state, \JSON_UNESCAPED_UNICODE);
    if ($payload === false) {
        return false;
    }

    return @\file_put_contents($path, $payload, \LOCK_EX) !== false && @\chmod($path, 0600);
}

function recoveryStubCreatePendingSession(string $token): void
{
    if (!recoveryStubAssertValidToken($token)) {
        return;
    }
    $now = \time();
    recoveryStubSaveAuthState(recoveryStubPendingAuthPath($token), [
        'token' => $token,
        'secret' => \bin2hex(\random_bytes(32)),
        'stubBuildId' => recoveryStubBuildId(),
        'stubIntegrity' => \defined('RECOVERY_STUB_INTEGRITY_HASH') ? RECOVERY_STUB_INTEGRITY_HASH : '',
        'createdAt' => $now,
        'expiresAt' => $now + 86400,
        'authIssuedAt' => null,
    ]);
    recoveryStubLogDebug('auth', 'pending_session_created', ['tokenPrefix' => \substr($token, 0, 8)]);
}

/**
 * Pending-Sitzung an aktuelle Stub-Version anpassen (z. B. nach Tool-Update ohne neuen Link).
 */
function recoveryStubSyncPendingStubBuild(string $token): bool
{
    if (!recoveryStubAssertValidToken($token)) {
        return false;
    }

    $path = recoveryStubPendingAuthPath($token);
    $state = recoveryStubLoadAuthState($path);
    if ($state === null) {
        return false;
    }

    $currentBuild = recoveryStubBuildId();
    if (($state['stubBuildId'] ?? '') === $currentBuild) {
        return true;
    }

    $state['stubBuildId'] = $currentBuild;
    if (\defined('RECOVERY_STUB_INTEGRITY_HASH')) {
        $state['stubIntegrity'] = (string) RECOVERY_STUB_INTEGRITY_HASH;
    }
    recoveryStubSaveAuthState($path, $state);

    $boundPath = recoveryStubBoundAuthPath($token);
    $bound = recoveryStubLoadAuthState($boundPath);
    if ($bound !== null) {
        $bound['stubBuildId'] = $currentBuild;
        if (\defined('RECOVERY_STUB_INTEGRITY_HASH')) {
            $bound['stubIntegrity'] = (string) RECOVERY_STUB_INTEGRITY_HASH;
        }
        recoveryStubSaveAuthState($boundPath, $bound);
    }

    recoveryStubLog('info', 'Pending-Sitzung an Stub-Build angepasst', [
        'tokenPrefix' => \substr($token, 0, 8),
        'buildId' => $currentBuild,
    ]);

    return true;
}

function recoveryStubAuthSignature(string $secret, int $expires, string $token, string $stubBuildId): string
{
    $payload = $expires . "\n" . $token . "\n" . $stubBuildId;

    return \hash_hmac('sha256', $payload, $secret);
}

/**
 * @return array{ok: true, content: string}|array{ok: false, error: string}
 */
function recoveryStubGenerateAuthFileContent(string $token): array
{
    if (!recoveryStubAssertValidToken($token)) {
        return ['ok' => false, 'error' => 'Ungültige Sitzung.'];
    }

    $pendingPath = recoveryStubPendingAuthPath($token);
    $pending = recoveryStubLoadAuthState($pendingPath);
    if ($pending === null) {
        recoveryStubLog('warning', 'Auth-Download ohne Pending-Session', ['tokenPrefix' => \substr($token, 0, 8)]);

        return ['ok' => false, 'error' => 'Keine gültige Recovery-Sitzung. Bitte plugin-recovery-tool.php erneut aufrufen.'];
    }

    recoveryStubSyncPendingStubBuild($token);
    $pending = recoveryStubLoadAuthState($pendingPath);
    if ($pending === null) {
        return ['ok' => false, 'error' => 'Keine gültige Recovery-Sitzung. Bitte plugin-recovery-tool.php erneut aufrufen.'];
    }

    if (($pending['stubIntegrity'] ?? '') !== '' && \defined('RECOVERY_STUB_INTEGRITY_HASH')
        && !\hash_equals((string) $pending['stubIntegrity'], (string) RECOVERY_STUB_INTEGRITY_HASH)) {
        return ['ok' => false, 'error' => 'Stub wurde seit Sitzungsstart verändert. Neu starten.'];
    }

    $expires = (int) ($pending['expiresAt'] ?? 0);
    if ($expires <= \time()) {
        return ['ok' => false, 'error' => 'Sitzung abgelaufen. Tool neu aufrufen.'];
    }

    $secret = (string) ($pending['secret'] ?? '');
    if ($secret === '') {
        return ['ok' => false, 'error' => 'Sitzungsgeheimnis fehlt.'];
    }

    $stubBuildId = recoveryStubBuildId();
    $signature = recoveryStubAuthSignature($secret, $expires, $token, $stubBuildId);

    $pending['authIssuedAt'] = \time();
    recoveryStubSaveAuthState(recoveryStubPendingAuthPath($token), $pending);

    $content = "<?php exit; /* WoltLab Plugin Recovery Auth v" . RECOVERY_AUTH_FORMAT . " — NICHT BEARBEITEN */ ?>\n"
        . $expires . "\n"
        . $token . "\n"
        . $stubBuildId . "\n"
        . $signature;

    recoveryStubLog('info', 'Auth-Datei ausgestellt', ['tokenPrefix' => \substr($token, 0, 8)]);

    return ['ok' => true, 'content' => $content];
}

/**
 * @return array{ok: bool, reason?: string}
 */
function recoveryStubValidateAuthFile(string $urlToken): array
{
    if (!recoveryStubAssertValidToken($urlToken)) {
        return ['ok' => false, 'reason' => 'invalid_token'];
    }

    $authPath = recoveryStubWcfRoot() . RECOVERY_AUTH_FILENAME;
    if (!\is_file($authPath) || !\is_readable($authPath)) {
        return ['ok' => false, 'reason' => 'missing_file'];
    }

    $lines = \preg_split("/\r\n|\n|\r/", (string) \file_get_contents($authPath)) ?: [];
    if (\count($lines) < 4) {
        return ['ok' => false, 'reason' => 'legacy_or_invalid_format'];
    }

    if (!\str_contains((string) ($lines[0] ?? ''), 'Auth v' . RECOVERY_AUTH_FORMAT)) {
        return ['ok' => false, 'reason' => 'wrong_format_version'];
    }

    $expires = (int) ($lines[1] ?? 0);
    $fileToken = \trim((string) ($lines[2] ?? ''));
    $fileStubBuildId = \trim((string) ($lines[3] ?? ''));
    $fileSignature = \trim((string) ($lines[4] ?? ''));

    if ($expires <= \time()) {
        return ['ok' => false, 'reason' => 'expired'];
    }

    if (!\hash_equals($urlToken, $fileToken)) {
        recoveryStubLog('warning', 'Auth-Token-Mismatch URL vs. Datei', []);

        return ['ok' => false, 'reason' => 'token_mismatch'];
    }

    if (!\hash_equals(recoveryStubBuildId(), $fileStubBuildId)) {
        recoveryStubLog('warning', 'Auth-Stub-Build-Mismatch', [
            'expected' => recoveryStubBuildId(),
            'got' => $fileStubBuildId,
        ]);

        return ['ok' => false, 'reason' => 'stub_mismatch'];
    }

    $state = recoveryStubLoadAuthState(recoveryStubPendingAuthPath($urlToken))
        ?? recoveryStubLoadAuthState(recoveryStubBoundAuthPath($urlToken));
    if ($state === null) {
        return ['ok' => false, 'reason' => 'no_server_session'];
    }

    if (($state['stubBuildId'] ?? '') !== recoveryStubBuildId()) {
        recoveryStubSyncPendingStubBuild($urlToken);
        $state = recoveryStubLoadAuthState(recoveryStubPendingAuthPath($urlToken))
            ?? recoveryStubLoadAuthState(recoveryStubBoundAuthPath($urlToken));
        if ($state === null || ($state['stubBuildId'] ?? '') !== recoveryStubBuildId()) {
            return ['ok' => false, 'reason' => 'server_stub_mismatch'];
        }
    }

    $secret = (string) ($state['secret'] ?? '');
    if ($secret === '') {
        return ['ok' => false, 'reason' => 'no_secret'];
    }

    $expected = recoveryStubAuthSignature($secret, $expires, $fileToken, $fileStubBuildId);
    if ($fileSignature === '' || !\hash_equals($expected, $fileSignature)) {
        recoveryStubLog('warning', 'Auth-Signatur ungültig', []);

        return ['ok' => false, 'reason' => 'bad_signature'];
    }

    recoveryStubSaveAuthState(recoveryStubBoundAuthPath($urlToken), $state + [
        'boundAt' => \time(),
        'lastValidatedAt' => \time(),
    ]);
    @\unlink(recoveryStubPendingAuthPath($urlToken));

    recoveryStubLog('info', 'Auth erfolgreich validiert', ['tokenPrefix' => \substr($urlToken, 0, 8)]);

    return ['ok' => true];
}

function recoveryStubInvalidateBoundSession(string $urlToken): void
{
    if (!recoveryStubAssertValidToken($urlToken)) {
        return;
    }
    @\unlink(recoveryStubBoundAuthPath($urlToken));
}

function recoveryStubGetAuthWizardStep(string $urlToken): int
{
    if (!recoveryStubAssertValidToken($urlToken)) {
        return 1;
    }

    if (!empty($_GET['wizard']) && (int) $_GET['wizard'] === 2) {
        return 2;
    }

    $pending = recoveryStubLoadAuthState(recoveryStubPendingAuthPath($urlToken));
    if ($pending !== null && ($pending['authIssuedAt'] ?? null) !== null) {
        return 2;
    }

    return 1;
}

function recoveryStubEnsurePhpSession(): void
{
    if (\session_status() === PHP_SESSION_NONE) {
        \session_start([
            'cookie_httponly' => true,
            'cookie_samesite' => 'Lax',
        ]);
    }
}

function recoveryStubBindSessionAuthToken(string $token): void
{
    if (!recoveryStubAssertValidToken($token)) {
        return;
    }
    recoveryStubEnsurePhpSession();
    $_SESSION['recovery_auth_token'] = $token;
    $_SESSION['recovery_auth_bound_at'] = \time();
}

function recoveryStubGetSessionAuthToken(): ?string
{
    recoveryStubEnsurePhpSession();
    $token = $_SESSION['recovery_auth_token'] ?? null;
    if (!\is_string($token) || !recoveryStubAssertValidToken($token)) {
        return null;
    }
    $boundAt = (int) ($_SESSION['recovery_auth_bound_at'] ?? 0);
    if ($boundAt > 0 && \time() - $boundAt > 86400) {
        unset($_SESSION['recovery_auth_token'], $_SESSION['recovery_auth_bound_at']);

        return null;
    }

    return $token;
}

function recoveryStubIsAuthenticated(string $urlToken): bool
{
    static $cache = [];

    if (isset($cache[$urlToken])) {
        return $cache[$urlToken];
    }

    $result = recoveryStubValidateAuthFile($urlToken);
    if (!$result['ok']) {
        if (\is_file(recoveryStubBoundAuthPath($urlToken))) {
            recoveryStubInvalidateBoundSession($urlToken);
        }

        return $cache[$urlToken] = false;
    }

    recoveryStubBindSessionAuthToken($urlToken);

    return $cache[$urlToken] = true;
}

/**
 * @return array{
 *     reason: string,
 *     title: string,
 *     message: string,
 *     steps: list<string>,
 *     severity: string,
 *     suggestRedownload: bool,
 *     suggestNewSession: bool
 * }
 */
function recoveryStubAuthFailureDetails(string $reason): array
{
    $authFile = RECOVERY_AUTH_FILENAME;

    return match ($reason) {
        'token_mismatch' => [
            'reason' => $reason,
            'title' => 'Falsche Auth-Datei für diese Sitzung',
            'message' => 'Die Datei auf dem Server stammt von einem anderen Tool-Aufruf (anderer Link / anderes Browser-Tab).',
            'steps' => [
                'Alte Datei auf dem Server löschen oder überschreiben: <code>' . $authFile . '</code> im WoltLab-Hauptverzeichnis.',
                'Unten „Auth-Datei neu herunterladen“ — nur die Datei von dieser Seite ist gültig.',
                'Neue Datei per FTP/SFTP hochladen (Binärmodus).',
                '„Auth-Datei prüfen“ klicken — die Prüfung läuft automatisch.',
            ],
            'severity' => 'error',
            'suggestRedownload' => true,
            'suggestNewSession' => false,
        ],
        'stub_mismatch', 'server_stub_mismatch' => [
            'reason' => $reason,
            'title' => 'Auth-Datei passt nicht zur Tool-Version',
            'message' => 'Die hochgeladene Datei wurde mit einer anderen plugin-recovery-tool.php erzeugt.',
            'steps' => [
                'Aktuelle <code>plugin-recovery-tool.php</code> vom GitHub-Release verwenden.',
                'Auth-Datei neu herunterladen (Button unten) und alte Datei überschreiben.',
                'Erneut „Auth-Datei prüfen“.',
            ],
            'severity' => 'error',
            'suggestRedownload' => true,
            'suggestNewSession' => false,
        ],
        'bad_signature' => [
            'reason' => $reason,
            'title' => 'Auth-Datei ungültig oder verändert',
            'message' => 'Die Signatur stimmt nicht — Datei wurde bearbeitet oder stammt von woanders.',
            'steps' => [
                'Datei nicht in einem Editor öffnen oder anpassen.',
                'Neu herunterladen und die alte Datei vollständig ersetzen.',
                '„Auth-Datei prüfen“ erneut ausführen.',
            ],
            'severity' => 'error',
            'suggestRedownload' => true,
            'suggestNewSession' => false,
        ],
        'expired' => [
            'reason' => $reason,
            'title' => 'Auth-Datei abgelaufen',
            'message' => 'Aus Sicherheitsgründen sind Auth-Dateien nur kurz gültig.',
            'steps' => [
                'Neue Auth-Datei herunterladen (Button unten).',
                'Alte Datei auf dem Server überschreiben.',
                '„Auth-Datei prüfen“.',
            ],
            'severity' => 'warning',
            'suggestRedownload' => true,
            'suggestNewSession' => false,
        ],
        'no_server_session' => [
            'reason' => $reason,
            'title' => 'Recovery-Sitzung abgelaufen',
            'message' => 'Auf dem Server gibt es keine passende Sitzung mehr zu diesem Link.',
            'steps' => [
                'Diese Seite komplett neu laden — Sie erhalten einen neuen Sicherheits-Link.',
                'Auth-Datei neu herunterladen und hochladen.',
                'Lesezeichen mit altem <code>?t=…</code>-Link nicht weiterverwenden.',
            ],
            'severity' => 'warning',
            'suggestRedownload' => true,
            'suggestNewSession' => true,
        ],
        'legacy_or_invalid_format', 'wrong_format_version' => [
            'reason' => $reason,
            'title' => 'Veraltetes Auth-Datei-Format',
            'message' => 'Die Datei ist zu alt oder keine gültige Recovery-Auth-Datei.',
            'steps' => [
                'Nur die Datei aus diesem Tool verwenden (Download-Button).',
                'Alte <code>' . $authFile . '</code> löschen und die neue hochladen.',
                '„Auth-Datei prüfen“.',
            ],
            'severity' => 'error',
            'suggestRedownload' => true,
            'suggestNewSession' => false,
        ],
        'missing_file' => [
            'reason' => $reason,
            'title' => 'Auth-Datei fehlt noch',
            'message' => 'Auf dem Server wurde <code>' . $authFile . '</code> noch nicht gefunden.',
            'steps' => [
                'Datei ins WoltLab-Hauptverzeichnis legen — neben <code>plugin-recovery-tool.php</code>.',
                'Vorhandene Datei überschreiben (FTP/SFTP: Binärmodus).',
                'Die Prüfung läuft automatisch, sobald die Datei erkannt wird.',
            ],
            'severity' => 'warning',
            'suggestRedownload' => false,
            'suggestNewSession' => false,
        ],
        default => [
            'reason' => $reason,
            'title' => 'Authentifizierung fehlgeschlagen',
            'message' => recoveryStubAuthFailureMessageShort($reason),
            'steps' => [
                'Auth-Datei neu herunterladen und hochladen.',
                '„Auth-Datei prüfen“ erneut ausführen.',
            ],
            'severity' => 'error',
            'suggestRedownload' => true,
            'suggestNewSession' => false,
        ],
    };
}

function recoveryStubAuthFailureMessageShort(string $reason): string
{
    return match ($reason) {
        'token_mismatch' => 'Die Auth-Datei gehört nicht zu dieser Sitzung (URL-Token stimmt nicht überein).',
        'stub_mismatch', 'server_stub_mismatch' => 'Die Auth-Datei passt nicht zu dieser plugin-recovery-tool.php-Version.',
        'bad_signature' => 'Die Auth-Datei wurde manipuliert oder stammt von einer anderen Sitzung.',
        'expired' => 'Die Auth-Datei ist abgelaufen.',
        'no_server_session' => 'Keine gültige Recovery-Sitzung auf dem Server.',
        'legacy_or_invalid_format', 'wrong_format_version' => 'Ungültige oder veraltete Auth-Datei.',
        'missing_file' => 'Auth-Datei wurde noch nicht hochgeladen.',
        default => 'Authentifizierung fehlgeschlagen.',
    };
}

function recoveryStubAuthFailureMessage(string $reason): string
{
    return recoveryStubAuthFailureMessageShort($reason);
}

function recoveryStubCleanupAuthState(): void
{
    foreach ([RECOVERY_AUTH_PENDING_DIR, RECOVERY_AUTH_BOUND_DIR] as $sub) {
        $dir = \rtrim(recoveryStubWcfRoot(), '/\\') . '/' . $sub;
        if (\is_dir($dir)) {
            recoveryStubRemoveDirectory($dir);
        }
    }
}
