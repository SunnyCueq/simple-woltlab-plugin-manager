<?php

declare(strict_types=1);

/**
 * WoltLab-Hauptverzeichnis (Parent von recovery-tool/).
 */
function recoveryWcfRoot(): string
{
    if (\defined('RECOVERY_WCF_ROOT')) {
        return \rtrim((string) \constant('RECOVERY_WCF_ROOT'), '/\\') . '/';
    }

    return \rtrim(\dirname(\defined('RECOVERY_PACKAGE_DIR') ? RECOVERY_PACKAGE_DIR : __DIR__), '/\\') . '/';
}

function recoveryWcfPath(string $relative = ''): string
{
    $root = recoveryWcfRoot();
    $relative = \ltrim(\str_replace('\\', '/', $relative), '/');

    return $relative === '' ? $root : $root . $relative;
}

function recoveryGetSiteBaseUrl(): string
{
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $script = $_SERVER['SCRIPT_NAME'] ?? '/plugin-recovery-tool.php';
    $base = \rtrim(\str_replace('\\', '/', \dirname($script)), '/');

    return $scheme . '://' . $host . ($base === '' || $base === '.' ? '' : $base) . '/';
}
