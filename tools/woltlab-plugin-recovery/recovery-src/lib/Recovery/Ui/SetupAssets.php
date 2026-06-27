<?php

declare(strict_types=1);

/**
 * Standalone-ACP-Styles: kompiliertes ACP-CSS wenn vorhanden, sonst WCFSetup (Rescue/Setup).
 *
 * @return list<string> relative Pfade unter WCF_DIR
 */
function recoveryResolveAcpStylesheets(): array
{
    if (!\defined('WCF_DIR')) {
        try {
            \define('WCF_DIR', recoveryResolveWcfDir());
        } catch (\Throwable $ignored) {
            return [];
        }
    }

    $candidates = [
        'acp/style/style.css',
        'acp/style/setup/WCFSetup.css',
    ];

    foreach ($candidates as $relative) {
        if (\is_readable(WCF_DIR . $relative)) {
            return [$relative];
        }
    }

    return [];
}

/**
 * ACP-Setup-Assets (Styles, Logo, Font Awesome) — wie Rescue Mode / WCFSetup.
 *
 * @return array{
 *     stylesheets: list<string>,
 *     WCFSetup.css: string,
 *     woltlabSuite.png: string,
 *     fontAwesomeCss: string,
 *     fontAwesomeLocal: bool,
 *     usesCompiledAcpStyle: bool,
 *     webComponentsJs: string
 * }
 */
function recoveryGetSetupAssets(): array
{
    $assets = [
        'stylesheets' => [],
        'WCFSetup.css' => '',
        'woltlabSuite.png' => '',
        'fontAwesomeCss' => '',
        'fontAwesomeLocal' => false,
        'usesCompiledAcpStyle' => false,
        'webComponentsJs' => '',
    ];

    try {
        $assets['stylesheets'] = recoveryResolveAcpStylesheets();
    } catch (\Throwable $ignored) {
        return $assets;
    }

    if ($assets['stylesheets'] !== []) {
        $assets['WCFSetup.css'] = $assets['stylesheets'][0];
        $assets['usesCompiledAcpStyle'] = $assets['stylesheets'][0] === 'acp/style/style.css';
    }

    if (\defined('WCF_DIR')) {
        if (\is_readable(WCF_DIR . 'acp/images/woltlabSuite.png')) {
            $assets['woltlabSuite.png'] = 'acp/images/woltlabSuite.png';
        }
        if (\is_readable(WCF_DIR . 'acp/images/woltlabSuite-small.png')) {
            $assets['woltlabSuite-small.png'] = 'acp/images/woltlabSuite-small.png';
        }
        $wcPath = 'js/WoltLabSuite/WebComponent.min.js';
        if (\is_readable(WCF_DIR . $wcPath)) {
            $assets['webComponentsJs'] = $wcPath;
        }
        // Immer laden wenn vorhanden — @font-face für fa-icon (WCFSetup enthält kein fa-icon-CSS).
        foreach (['icon/font-awesome/v7/css/all.min.css'] as $faPath) {
            if (\is_readable(WCF_DIR . $faPath)) {
                $assets['fontAwesomeCss'] = $faPath;
                $assets['fontAwesomeLocal'] = true;
                break;
            }
        }
    }

    return $assets;
}

function recoveryAssetPublicHref(string $relativePath): string
{
    if ($relativePath === '') {
        return '';
    }
    if (\str_starts_with($relativePath, 'http://') || \str_starts_with($relativePath, 'https://') || \str_starts_with($relativePath, 'data:')) {
        return $relativePath;
    }

    return recoveryGetSiteBaseUrl() . \ltrim($relativePath, '/');
}
