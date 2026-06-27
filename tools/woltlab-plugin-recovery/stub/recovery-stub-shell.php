<?php

declare(strict_types=1);

function recoveryStubGetSiteBaseUrl(): string
{
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $script = $_SERVER['SCRIPT_NAME'] ?? '/plugin-recovery-tool.php';
    $base = \rtrim(\str_replace('\\', '/', \dirname($script)), '/');

    return $scheme . '://' . $host . ($base === '' || $base === '.' ? '' : $base) . '/';
}

function recoveryStubSelfScript(): string
{
    return \basename($_SERVER['SCRIPT_NAME'] ?? 'plugin-recovery-tool.php');
}

function recoveryStubContinuationUrl(string $authHash): string
{
    return recoveryStubSelfScript() . '?t=' . \rawurlencode($authHash);
}

/**
 * @return list<string>
 */
function recoveryStubResolveAcpStylesheets(): array
{
    $root = recoveryStubWcfRoot();
    foreach (['acp/style/style.css', 'acp/style/setup/WCFSetup.css'] as $relative) {
        if (\is_readable($root . $relative)) {
            return [$relative];
        }
    }

    return [];
}

function recoveryStubUsesCompiledAcpStyle(): bool
{
    return recoveryStubResolveAcpStylesheets() !== []
        && recoveryStubResolveAcpStylesheets()[0] === 'acp/style/style.css';
}

/**
 * @return array{stylesheets: list<string>, WCFSetup.css: string, woltlabSuite.png: string, fontAwesomeCss: string, fontAwesomeLocal: bool, usesCompiledAcpStyle: bool, webComponentsJs: string}
 */
function recoveryStubGetSetupAssets(): array
{
    $root = recoveryStubWcfRoot();
    $stylesheets = recoveryStubResolveAcpStylesheets();
    $assets = [
        'stylesheets' => $stylesheets,
        'WCFSetup.css' => $stylesheets[0] ?? '',
        'woltlabSuite.png' => '',
        'fontAwesomeCss' => '',
        'fontAwesomeLocal' => false,
        'usesCompiledAcpStyle' => recoveryStubUsesCompiledAcpStyle(),
        'webComponentsJs' => '',
    ];
    if (\is_readable($root . 'acp/images/woltlabSuite.png')) {
        $assets['woltlabSuite.png'] = 'acp/images/woltlabSuite.png';
    }
    if (\is_readable($root . 'acp/images/woltlabSuite-small.png')) {
        $assets['woltlabSuite-small.png'] = 'acp/images/woltlabSuite-small.png';
    }
    $wcPath = 'js/WoltLabSuite/WebComponent.min.js';
    if (\is_readable($root . $wcPath)) {
        $assets['webComponentsJs'] = $wcPath;
    }
    if (\is_readable($root . 'icon/font-awesome/v7/css/all.min.css')) {
        $assets['fontAwesomeCss'] = 'icon/font-awesome/v7/css/all.min.css';
        $assets['fontAwesomeLocal'] = true;
    }

    return $assets;
}

function recoveryStubFaIcon(int $size, string $name, bool $solid = true): string
{
    $size = \in_array($size, [16, 24, 32, 48, 64, 96, 128, 144], true) ? $size : 16;
    $name = \htmlspecialchars($name, \ENT_QUOTES, 'UTF-8');
    if ($solid) {
        return \sprintf('<fa-icon size="%d" name="%s" solid></fa-icon>', $size, $name);
    }

    return \sprintf('<fa-icon size="%d" name="%s"></fa-icon>', $size, $name);
}

function recoveryStubAuthAlertIcon(string $severity): string
{
    $name = match ($severity) {
        'error' => 'circle-xmark',
        'info' => 'circle-info',
        'success' => 'circle-check',
        default => 'triangle-exclamation',
    };

    return recoveryStubFaIcon(16, $name, true);
}

function recoveryStubLoadingIndicator(int $size = 24): string
{
    if (!\in_array($size, [24, 48, 96], true)) {
        $size = 24;
    }

    return '<woltlab-core-loading-indicator size="' . $size . '" hide-text></woltlab-core-loading-indicator>';
}

function recoveryStubRenderWoltLabHeadGlobals(): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;
    $timeNow = \time();
    ?>
    <script data-eager="true">
    window.TIME_NOW = window.TIME_NOW || <?= (int) $timeNow ?>;
    window.LAST_UPDATE_TIME = window.LAST_UPDATE_TIME || <?= (int) $timeNow ?>;
    window.COMPILER_TARGET_DEFAULT = true;
    </script>
    <?php
}

function recoveryStubRenderFaIconStyles(): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;
    $root = recoveryStubWcfRoot();
    $solidFont = '';
    $regularFont = '';
    foreach ([
        'acp/style/font/fa-solid-900.woff2',
        'icon/font-awesome/v7/webfonts/fa-solid-900.woff2',
    ] as $rel) {
        if (\is_readable($root . $rel)) {
            $solidFont = recoveryStubAssetHref($rel);
            break;
        }
    }
    foreach ([
        'acp/style/font/fa-regular-400.woff2',
        'icon/font-awesome/v7/webfonts/fa-regular-400.woff2',
    ] as $rel) {
        if (\is_readable($root . $rel)) {
            $regularFont = recoveryStubAssetHref($rel);
            break;
        }
    }
    ?>
    <style>
    <?php if ($regularFont !== ''): ?>
    @font-face {
        font-family: "Font Awesome 7 Free";
        font-style: normal;
        font-weight: 400;
        font-display: block;
        src: url("<?= \htmlspecialchars($regularFont) ?>") format("woff2");
    }
    <?php endif; ?>
    <?php if ($solidFont !== ''): ?>
    @font-face {
        font-family: "Font Awesome 7 Free";
        font-style: normal;
        font-weight: 900;
        font-display: block;
        src: url("<?= \htmlspecialchars($solidFont) ?>") format("woff2");
    }
    <?php endif; ?>
    fa-brand, fa-icon {
        align-items: center;
        display: inline-flex;
        height: var(--icon-size);
        justify-content: center;
        pointer-events: none;
        width: calc(var(--icon-size) * 1.25);
    }
    fa-icon[size="16"] { --font-size: 14px; --icon-size: 16px; }
    fa-icon[size="24"] { --font-size: 18px; --icon-size: 24px; }
    fa-icon[size="32"] { --font-size: 28px; --icon-size: 32px; }
    fa-icon {
        font-family: var(--fa-font-family, "Font Awesome 7 Free") !important;
        font-size: var(--font-size) !important;
        font-weight: var(--fa-font-weight, 400) !important;
        line-height: 1 !important;
        visibility: visible !important;
    }
    fa-icon[solid] { font-weight: 900 !important; }
    #tplRecoveryAuth fa-icon { visibility: visible !important; }
    </style>
    <?php
}

function recoveryStubRenderWebComponentsScript(): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $assets = recoveryStubGetSetupAssets();
    if (($assets['webComponentsJs'] ?? '') === '') {
        return;
    }
    $done = true;
    $href = recoveryStubAssetHref($assets['webComponentsJs']);
    $mtime = @\filemtime(recoveryStubWcfRoot() . $assets['webComponentsJs']);
    if ($mtime !== false) {
        $href .= (\str_contains($href, '?') ? '&' : '?') . 'v=' . $mtime;
    }
    recoveryStubRenderWoltLabHeadGlobals();
    ?>
    <script data-eager="true" src="<?= \htmlspecialchars($href) ?>"></script>
    <script data-eager="true">
    (function () {
        function bootIcon(el) {
            if (!el || typeof el.setIcon !== "function") { return; }
            var name = el.getAttribute("name");
            if (!name) { return; }
            try { el.setIcon(name, el.hasAttribute("solid")); } catch (e) {}
        }
        function bootAll() {
            document.querySelectorAll("fa-icon[name]").forEach(bootIcon);
        }
        if (customElements.get("fa-icon")) { bootAll(); }
        else { customElements.whenDefined("fa-icon").then(bootAll); }
        document.addEventListener("DOMContentLoaded", bootAll);
    }());
    </script>
    <?php
}

function recoveryStubAssetHref(string $relative): string
{
    if ($relative === '') {
        return '';
    }

    return recoveryStubGetSiteBaseUrl() . \ltrim($relative, '/');
}

function recoveryStubWizardCss(): string
{
    if (recoveryStubUsesCompiledAcpStyle()) {
        return '';
    }
    $path = __DIR__ . '/recovery-stub-wizard.css';
    if (\is_readable($path)) {
        return (string) \file_get_contents($path);
    }

    return '';
}

function recoveryStubRenderColorSchemeHeadScript(): void
{
    ?>
    <script data-eager="true">
    (function () {
        var root = document.documentElement;
        var mq = window.matchMedia("(prefers-color-scheme: dark)");
        function apply() {
            root.dataset.colorScheme = mq.matches ? "dark" : "light";
        }
        apply();
        if (typeof mq.addEventListener === "function") {
            mq.addEventListener("change", apply);
        } else if (typeof mq.addListener === "function") {
            mq.addListener(apply);
        }
    }());
    </script>
    <?php
}

function recoveryStubAcpLayoutCss(): string
{
    $path = \dirname(__DIR__) . '/recovery-tool/lib/Recovery/Ui/recovery-acp-layout.css';
    if (\is_readable($path)) {
        return (string) \file_get_contents($path);
    }

    return '';
}

/**
 * @param array{value?: int, max?: int, label?: string}|null $wizardProgress
 * @param 'auth'|'acp' $layout auth = Login-Schmalspur, acp = volle Inhaltsbreite wie ACP-Seiten
 */
function recoveryStubRenderPageStart(
    string $title,
    string $subtitle = '',
    ?array $wizardProgress = null,
    string $layout = 'auth',
): void {
    $layout = $layout === 'acp' ? 'acp' : 'auth';
    $assets = recoveryStubGetSetupAssets();
    $logoHref = ($assets['woltlabSuite.png'] ?? '') !== '' ? recoveryStubAssetHref($assets['woltlabSuite.png']) : '';
    $logoSmallHref = ($assets['woltlabSuite-small.png'] ?? '') !== ''
        ? recoveryStubAssetHref($assets['woltlabSuite-small.png'])
        : '';
    $faHref = $assets['fontAwesomeCss'] !== ''
        ? recoveryStubAssetHref($assets['fontAwesomeCss'])
        : '';
    $faExtra = $assets['fontAwesomeLocal'] ? '' : ' crossorigin="anonymous" referrerpolicy="no-referrer"';
    $wizardCss = $layout === 'auth' ? recoveryStubWizardCss() : '';
    $usesAcpUi = $assets['usesCompiledAcpStyle'];
    $hasAcpStylesheet = $assets['stylesheets'] !== [];
    $extensionsCss = '';
    if ($usesAcpUi) {
        $recoveryToolRoot = \dirname(__DIR__) . '/recovery-tool/lib/Recovery/Ui/recovery-acp-native.css';
        if (\is_readable($recoveryToolRoot)) {
            $extensionsCss = 'recovery-tool/lib/Recovery/Ui/recovery-acp-native.css';
        }
    }
    $bodyId = $layout === 'acp' ? 'tplRecoveryTool' : 'tplRecoveryAuth';
    $bodyClass = 'wcfAcp';
    if ($layout === 'acp') {
        $bodyClass .= ' recovery-stub-acp-page';
    }
    if ($usesAcpUi) {
        $bodyClass .= ' recovery-uses-acp-ui';
    }
    $headerContainerClass = 'pageHeaderContainer';
    $pageContainerClass = $layout === 'acp' ? 'pageContainer' : 'pageContainer acpPageHiddenMenu';
    $acpLayoutCss = $layout === 'acp' ? recoveryStubAcpLayoutCss() : '';

    \header('Content-Type: text/html; charset=utf-8');
    ?>
<!DOCTYPE html>
<html lang="de" data-color-scheme="system">
<head>
    <meta charset="utf-8">
    <meta name="robots" content="noindex">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= \htmlspecialchars($title) ?></title>
    <?php foreach ($assets['stylesheets'] as $stylesheet): ?>
    <link rel="stylesheet" type="text/css" media="screen" href="<?= \htmlspecialchars(recoveryStubAssetHref($stylesheet)) ?>">
    <?php endforeach; ?>
    <?php if ($faHref !== ''): ?>
    <link rel="stylesheet" href="<?= \htmlspecialchars($faHref) ?>"<?= $faExtra ?>>
    <?php endif; ?>
    <?php recoveryStubRenderColorSchemeHeadScript(); ?>
    <?php recoveryStubRenderWoltLabHeadGlobals(); ?>
    <?php recoveryStubRenderFaIconStyles(); ?>
    <?php recoveryStubRenderWebComponentsScript(); ?>
    <?php if ($extensionsCss !== ''): ?>
    <link rel="stylesheet" href="<?= \htmlspecialchars(recoveryStubAssetHref($extensionsCss)) ?>">
    <?php endif; ?>
    <style>
        <?php if (!$usesAcpUi): ?>
        #pageHeaderContainer { height: 100px; }
        #pageHeader { padding: 30px 0; }
        #pageHeaderPanel,
        #pageHeaderPanel > .layoutBoundary {
            width: 100%;
            max-width: none;
        }
        #pageHeaderLogo img.pageHeaderLogoLarge {
            height: 40px;
            width: auto;
            max-width: 281px;
        }
        <?php endif; ?>
        .recovery-auth-step[hidden] { display: none !important; }
        #tplRecoveryAuth .recovery-auth-wizard { margin: 0; }
        #tplRecoveryAuth .recovery-auth-wizard .recovery-auth-step {
            margin: 0 0 16px;
            padding: 0;
            border: 0;
            background: transparent;
            box-shadow: none;
            border-radius: 0;
        }
        #tplRecoveryAuth .recovery-auth-wizard dl { margin-bottom: 0; }
        #tplRecoveryAuth .recovery-auth-wizard dd small {
            display: block;
            margin-top: 4px;
            color: var(--wcfContentDimmedText, #888);
            line-height: 1.5;
        }
        #tplRecoveryAuth .recovery-auth-wizard .formSubmit { margin-top: 0; }
        #tplRecoveryAuth .recovery-auth-wizard .formSubmit .button { margin-right: 8px; }
        #tplRecoveryAuth .recovery-auth-wizard:has(.recovery-auth-step:not([hidden])) { min-height: 0; }
        #tplRecoveryAuth .recovery-auth-wizard:not(:has(.recovery-auth-step:not([hidden]))) { display: none; }
        #tplRecoveryAuth #auth-step-3 .success { margin: 0 0 20px; }
        #tplRecoveryAuth #auth-step-3 .formSubmit {
            margin: 0;
            padding: 0;
            clear: both;
            position: relative;
            z-index: 1;
        }
        #tplRecoveryAuth #auth-step-3 .formSubmit .button { margin-right: 0; }
        #tplRecoveryAuth .error ol,
        #tplRecoveryAuth .warning ol,
        #tplRecoveryAuth .info ol {
            margin: 12px 0 0;
            padding-left: 1.5em;
            list-style: decimal;
        }
        #tplRecoveryAuth .error ol li,
        #tplRecoveryAuth .warning ol li,
        #tplRecoveryAuth .info ol li {
            display: list-item;
            margin: 6px 0;
        }
        #tplRecoveryAuth #authAlert[hidden] { display: none !important; }
        #tplRecoveryAuth #authAlert { margin: 0 0 16px; }
        #tplRecoveryAuth #authAlert p { margin: 0 0 8px; }
        #tplRecoveryAuth #authAlert p:last-child { margin-bottom: 0; }
        #tplRecoveryAuth .success > p,
        #tplRecoveryAuth .warning > p,
        #tplRecoveryAuth .error > p,
        #tplRecoveryAuth .info > p,
        #tplRecoveryAuth p.warning,
        #tplRecoveryAuth #authAlert > p:first-child {
            display: flex;
            align-items: flex-start;
            gap: 8px;
        }
        #tplRecoveryAuth .success fa-icon,
        #tplRecoveryAuth .warning fa-icon,
        #tplRecoveryAuth .error fa-icon,
        #tplRecoveryAuth .info fa-icon,
        #tplRecoveryAuth p.warning fa-icon,
        #tplRecoveryAuth #authAlert fa-icon {
            flex-shrink: 0;
            margin-top: 2px;
        }
        .recovery-log-panel { margin-top: 24px; }
        .recovery-log-panel > summary {
            cursor: pointer;
            list-style: none;
            font-weight: 600;
        }
        .recovery-log-panel > summary::-webkit-details-marker { display: none; }
        .recovery-log-panel > summary::before {
            content: "\f078";
            font-family: "Font Awesome 6 Free", "Font Awesome 7 Free";
            font-weight: 900;
            display: inline-block;
            margin-right: 8px;
            transition: transform 0.15s ease;
        }
        .recovery-log-panel[open] > summary::before { transform: rotate(-180deg); }
        .recovery-log-files { margin: 12px 0 0; }
        .recovery-log-files dt { font-weight: 600; margin-top: 8px; }
        .recovery-log-files dd { margin: 2px 0 0; color: var(--wcfContentDimmedText, #888); }
        .recovery-log-files code { font-size: 12px; }
        .recovery-log-pre {
            margin: 12px 0 0;
            padding: 12px 14px;
            max-height: 220px;
            overflow: auto;
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
            font-size: 12px;
            line-height: 1.45;
            border-radius: 4px;
            background: var(--wcfSidebarBackground, rgba(0,0,0,.06));
            border: 1px solid var(--wcfContainerBorder, rgba(0,0,0,.12));
            white-space: pre-wrap;
            word-break: break-word;
        }
        .recovery-log-empty { margin: 8px 0 0; color: var(--wcfContentDimmedText, #888); font-size: 13px; }
        .recovery-loading-inline { display: inline-flex; align-items: center; gap: 10px; }
    </style>
    <?php if ($acpLayoutCss !== ''): ?>
    <style><?= $acpLayoutCss ?></style>
    <?php endif; ?>
    <?php if ($wizardCss !== ''): ?>
    <style><?= $wizardCss ?></style>
    <?php endif; ?>
</head>
<body id="<?= \htmlspecialchars($bodyId, ENT_QUOTES, 'UTF-8') ?>" class="<?= \htmlspecialchars($bodyClass, ENT_QUOTES, 'UTF-8') ?>">
<a id="top"></a>
<div id="pageContainer" class="<?= \htmlspecialchars($pageContainerClass, ENT_QUOTES, 'UTF-8') ?>">
    <div id="pageHeaderContainer" class="<?= \htmlspecialchars($headerContainerClass, ENT_QUOTES, 'UTF-8') ?>">
        <header id="pageHeader" class="pageHeader">
            <div id="pageHeaderPanel" class="pageHeaderPanel">
                <div class="layoutBoundary">
                    <div id="pageHeaderLogo" class="pageHeaderLogo">
                        <?php if ($logoHref !== ''): ?>
                        <a href="<?= \htmlspecialchars(recoveryStubGetSiteBaseUrl()) ?>acp/">
                            <img src="<?= \htmlspecialchars($logoHref) ?>" alt="" width="562" height="80" loading="eager" class="pageHeaderLogoLarge">
                            <?php if ($logoSmallHref !== ''): ?>
                            <img src="<?= \htmlspecialchars($logoSmallHref) ?>" alt="" width="55" height="30" loading="eager" class="pageHeaderLogoSmall">
                            <?php endif; ?>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </header>
    </div>
    <div id="acpPageContentContainer" class="acpPageContentContainer">
        <section id="main" class="main" role="main">
            <div class="layoutBoundary">
                <div id="content" class="content">
                    <header class="contentHeader">
                        <div class="contentHeaderTitle">
                            <h1 class="contentTitle"><?= \htmlspecialchars($title) ?></h1>
                            <?php if ($wizardProgress !== null): ?>
                            <p class="contentHeaderDescription">
                                <?php if (!$usesAcpUi): ?>
                                <progress id="authWizardProgress" value="<?= (int) ($wizardProgress['value'] ?? 1) ?>" max="<?= (int) ($wizardProgress['max'] ?? 3) ?>" style="width:300px"><?= (int) ($wizardProgress['value'] ?? 1) ?>%</progress>
                                <?php else: ?>
                                <span id="authWizardProgress" hidden aria-hidden="true" data-value="<?= (int) ($wizardProgress['value'] ?? 1) ?>" data-max="<?= (int) ($wizardProgress['max'] ?? 3) ?>"></span>
                                <?php endif; ?>
                                <?php if (($wizardProgress['label'] ?? '') !== ''): ?>
                                <?= \htmlspecialchars((string) $wizardProgress['label']) ?>
                                <?php endif; ?>
                            </p>
                            <?php elseif ($subtitle !== ''): ?>
                            <p class="contentHeaderDescription"><?= \htmlspecialchars($subtitle) ?></p>
                            <?php endif; ?>
                        </div>
                    </header>
    <?php
}

function recoveryStubLogFileBadge(array $entry): string
{
    $isErrorLog = \str_contains($entry['file'], 'stub-errors-');
    $hasContent = $entry['exists'] && $entry['size'] > 0;

    if ($hasContent) {
        return '<span class="badge green small">' . \htmlspecialchars(recoveryStubFormatLogSize($entry['size'])) . '</span>';
    }
    if ($isErrorLog) {
        return '<span class="badge green small">keine Fehler</span>';
    }

    return '<span class="badge small">noch keine Einträge</span>';
}

function recoveryStubRenderLogPanel(bool $expanded = false): void
{
    $catalog = recoveryStubLogFileCatalog();
    $excerpt = recoveryStubRecentLogExcerpt();
    $open = $expanded;
    ?>
    <section class="section">
        <header class="sectionHeader">
            <h2 class="sectionTitle">Protokoll &amp; Diagnose</h2>
            <p class="sectionDescription">
                Verzeichnis: <code title="<?= \htmlspecialchars(recoveryStubLogDir()) ?>"><?= \htmlspecialchars(recoveryStubLogDisplayPath()) ?></code>
            </p>
        </header>
        <div class="info" role="status">
            <p><fa-icon size="16" name="circle-info" solid></fa-icon>
                Die Log-Dateien sind Protokolle, kein Fehlerstatus der Oberfläche.
                Ein leeres <strong>Fehlerprotokoll</strong> bedeutet: es ist nichts schiefgelaufen.</p>
        </div>
        <table class="table tableList">
            <thead>
                <tr>
                    <th>Datei</th>
                    <th>Status</th>
                    <th>Beschreibung</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($catalog as $entry): ?>
                <tr>
                    <td><code><?= \htmlspecialchars($entry['file']) ?></code></td>
                    <td><?= recoveryStubLogFileBadge($entry) ?></td>
                    <td><?= \htmlspecialchars($entry['label']) ?> — <?= \htmlspecialchars($entry['description']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php if ($excerpt !== []): ?>
        <header class="sectionHeader" style="margin-top:20px">
            <h3 class="sectionTitle">Letzte Einträge</h3>
        </header>
        <pre class="recovery-log-pre" role="log"><?= \htmlspecialchars(\implode("\n", $excerpt)) ?></pre>
        <?php else: ?>
        <p class="sectionDescription">Nach Aktionen (Download, Auth, Installation) erscheinen hier die letzten Zeilen.</p>
        <?php endif; ?>
    </section>
    <?php
}

function recoveryStubFormatLogSize(int $bytes): string
{
    if ($bytes < 1024) {
        return $bytes . ' B';
    }
    if ($bytes < 1048576) {
        return \round($bytes / 1024, 1) . ' KB';
    }

    return \round($bytes / 1048576, 1) . ' MB';
}

/**
 * @param array{showLogPanel?: bool, logPanelExpanded?: bool} $options
 */
function recoveryStubRenderPageEnd(array $options = []): void
{
    if ($options['showLogPanel'] ?? true) {
        recoveryStubRenderLogPanel((bool) ($options['logPanelExpanded'] ?? false));
    }
    ?>
                </div>
            </div>
        </section>
    </div>
</div>
<p class="recovery-footer-meta" style="text-align:right;font-size:13px;margin:16px;color:var(--wcfContentDimmedText,#888);">
    <a href="https://github.com/benjarogit/sc-woltlab-plugin-recovery" target="_blank" rel="noopener">Plugin Recovery Tool</a>
    | <a href="https://manual.woltlab.com/de/recovery-tool/" target="_blank" rel="noopener">WoltLab Recovery</a>
</p>
</body>
</html>
    <?php
}

function recoveryStubRenderIntegrityError(string $message, ?string $logDir = null): void
{
    recoveryStubLogExposeHeaders();
    recoveryStubRenderPageStart('Plugin Recovery Tool', 'Integritätsprüfung fehlgeschlagen', null, 'acp');
    ?>
    <p class="error"><strong><fa-icon size="16" name="circle-xmark" solid></fa-icon> Ungültige Recovery-Datei</strong><br>
    <?= \htmlspecialchars($message) ?></p>
    <p class="info">Laden Sie <code>plugin-recovery-tool.php</code> ausschließlich vom
        <a href="https://github.com/<?= \htmlspecialchars(RECOVERY_GITHUB_REPO) ?>/releases" rel="noopener noreferrer">offiziellen GitHub-Release</a> herunter.</p>
    <?php
    recoveryStubRenderPageEnd(['logPanelExpanded' => true]);
}

function recoveryStubRenderAuthAlertBox(?string $reason): void
{
    if ($reason === null || $reason === '') {
        return;
    }
    $d = recoveryStubAuthFailureDetails($reason);
    $severity = $d['severity'];
    $wcfClass = match ($severity) {
        'error' => 'error',
        'info' => 'info',
        default => 'warning',
    };
    echo '<div id="authAlert" class="' . $wcfClass . '" role="alert">';
    echo '<p>' . recoveryStubAuthAlertIcon($severity) . ' <strong>' . \htmlspecialchars($d['title'], ENT_QUOTES, 'UTF-8') . '</strong></p>';
    echo '<p>' . \htmlspecialchars($d['message'], ENT_QUOTES, 'UTF-8') . '</p>';
    if ($d['steps'] !== []) {
        echo '<ol>';
        foreach ($d['steps'] as $step) {
            echo '<li>' . $step . '</li>';
        }
        echo '</ol>';
    }
    echo '</div>';
}

function recoveryStubRenderAuthWizard(string $authHash, ?string $errorMessage = null, ?string $errorReason = null): void
{
    $authFile = RECOVERY_AUTH_FILENAME;
    $initialStep = recoveryStubGetAuthWizardStep($authHash);
    $stepLabels = ['Schritt 1 von 3', 'Schritt 2 von 3', 'Schritt 3 von 3'];
    $assets = recoveryStubGetSetupAssets();
    $layout = ($assets['stylesheets'] ?? []) !== [] ? 'acp' : 'auth';
    $continueUrl = recoveryStubContinuationUrl($authHash);
    recoveryStubRenderPageStart('Plugin Recovery Tool', '', [
        'value' => $initialStep,
        'max' => 3,
        'label' => $stepLabels[$initialStep - 1],
    ], $layout);
    ?>
    <?php if ($errorReason === null): ?>
    <div id="authAlert" class="warning" hidden role="alert"></div>
    <?php else: ?>
    <?php recoveryStubRenderAuthAlertBox($errorReason); ?>
    <?php endif; ?>
    <div id="recovery-auth-wizard" class="recovery-auth-wizard">
        <div class="recovery-auth-step" id="auth-step-1"<?= $initialStep !== 1 ? ' hidden' : '' ?>>
            <dl>
                <dt><label for="downloadBtn"><?= \htmlspecialchars($authFile) ?></label> <span class="formFieldRequired">*</span></dt>
                <dd>
                    <small>Nur für <strong>diese</strong> Sitzung gültig. Bei neuem Link Auth-Datei neu herunterladen und auf dem Server überschreiben.</small>
                </dd>
            </dl>
            <div class="formSubmit">
                <button type="button" class="button buttonPrimary" id="downloadBtn" data-state="<?= $initialStep >= 2 ? 'done' : 'idle' ?>"<?= $initialStep >= 2 ? ' disabled' : '' ?>><?= \htmlspecialchars($authFile) ?> herunterladen</button>
            </div>
        </div>

        <div class="recovery-auth-step" id="auth-step-2"<?= $initialStep !== 2 ? ' hidden' : '' ?>>
            <dl>
                <dt><label for="validateBtn">Auth-Datei auf dem Server</label> <span class="formFieldRequired">*</span></dt>
                <dd>
                    <small>Datei ins WoltLab-Hauptverzeichnis legen (neben <code>plugin-recovery-tool.php</code>), vorhandene Datei <strong>überschreiben</strong>. FTP/SFTP: Binärmodus.</small>
                </dd>
            </dl>
            <div class="formSubmit">
                <button type="button" class="button buttonPrimary" id="validateBtn" data-state="idle">Auth-Datei prüfen</button>
                <button type="button" class="button" id="redownloadBtn">Neu herunterladen</button>
            </div>
        </div>

    </div>

    <div class="recovery-auth-step" id="auth-step-3" hidden>
        <div class="success" role="status">
            <p><?= recoveryStubAuthAlertIcon('success') ?>
                <span><strong>Authentifizierung erfolgreich</strong> — die Auth-Datei wurde erkannt.</span></p>
        </div>
        <div class="formSubmit">
            <a href="<?= \htmlspecialchars($continueUrl) ?>" class="button buttonPrimary" id="startRecoveryBtn"><?= recoveryStubFaIcon(16, 'rocket', true) ?> Recovery Tool starten</a>
        </div>
    </div>

    <div class="warning" role="alert">
        <p><?= recoveryStubFaIcon(16, 'shield-halved', true) ?> <strong>Sicherheitshinweis</strong></p>
        <p>Löschen Sie <code>plugin-recovery-tool.php</code> und <code><?= \htmlspecialchars($authFile) ?></code> nach der Verwendung.</p>
    </div>

    <script>
    (function () {
        var authToken = <?= \json_encode($authHash) ?>;
        var continueUrl = <?= \json_encode($continueUrl) ?>;
        var authFileName = <?= \json_encode($authFile) ?>;
        var pollInterval = null;
        var pollingActive = false;
        var progress = document.getElementById('authWizardProgress');
        var steps = [
            document.getElementById('auth-step-1'),
            document.getElementById('auth-step-2'),
            document.getElementById('auth-step-3')
        ];
        var stepLabels = ['Schritt 1 von 3', 'Schritt 2 von 3', 'Schritt 3 von 3'];
        var downloadBtn = document.getElementById('downloadBtn');
        var validateBtn = document.getElementById('validateBtn');
        var redownloadBtn = document.getElementById('redownloadBtn');
        var startRecoveryBtn = document.getElementById('startRecoveryBtn');
        var authWizard = document.getElementById('recovery-auth-wizard');
        var authAlert = document.getElementById('authAlert');

        var downloadBtnLabels = {
            idle: authFileName + ' herunterladen',
            loading: 'Wird erstellt …',
            done: 'Heruntergeladen — weiter zu Schritt 2'
        };
        var validateBtnLabels = {
            idle: 'Auth-Datei prüfen',
            loading: 'Prüfe …',
            success: 'Gültig',
            error: 'Erneut prüfen'
        };

        function setButtonState(btn, state) {
            if (!btn) { return; }
            btn.setAttribute('data-state', state);
            var labels = btn === downloadBtn ? downloadBtnLabels : validateBtnLabels;
            if (labels[state]) {
                btn.textContent = labels[state];
            }
            btn.disabled = state === 'loading' || state === 'done' || state === 'success';
        }

        function updateProgressLabel(n) {
            var desc = document.querySelector('.contentHeaderDescription');
            if (!desc) { return; }
            var nodes = desc.childNodes;
            for (var i = nodes.length - 1; i >= 0; i--) {
                if (nodes[i].nodeType === Node.TEXT_NODE) {
                    nodes[i].textContent = ' ' + stepLabels[n - 1];
                    return;
                }
            }
            desc.appendChild(document.createTextNode(' ' + stepLabels[n - 1]));
        }

        function goToStep(n) {
            steps.forEach(function (el, i) {
                if (!el) { return; }
                el.hidden = i + 1 !== n;
            });
            if (authWizard) {
                authWizard.hidden = n === 3;
            }
            if (progress) {
                if (progress.tagName === 'PROGRESS') {
                    progress.value = String(n);
                    progress.title = Math.round((n / 3) * 100) + '%';
                } else if (progress.dataset) {
                    progress.dataset.value = String(n);
                }
            }
            updateProgressLabel(n);
            if (n !== 2 && pollInterval) {
                clearInterval(pollInterval);
                pollInterval = null;
                pollingActive = false;
            }
            if (n === 2) {
                startPolling();
                checkAuthStatus(true);
            }
        }

        function wcfAlertClass(severity) {
            if (severity === 'error') { return 'error'; }
            if (severity === 'info') { return 'info'; }
            if (severity === 'success') { return 'success'; }
            return 'warning';
        }

        function alertIconName(severity) {
            if (severity === 'error') { return 'circle-xmark'; }
            if (severity === 'info') { return 'circle-info'; }
            if (severity === 'success') { return 'circle-check'; }
            return 'triangle-exclamation';
        }

        function faIconHtml(name) {
            return '<fa-icon size="16" name="' + escapeHtml(name) + '" solid></fa-icon>';
        }

        function renderSimpleAlert(severity, title, message, steps) {
            if (!authAlert) { return; }
            authAlert.hidden = false;
            authAlert.className = wcfAlertClass(severity);
            var html = '<p>' + faIconHtml(alertIconName(severity)) + ' <strong>' + escapeHtml(title || 'Hinweis') + '</strong></p>';
            if (message) {
                html += '<p>' + escapeHtml(message) + '</p>';
            }
            if (steps && steps.length) {
                html += '<ol>';
                steps.forEach(function (step) {
                    html += '<li>' + step + '</li>';
                });
                html += '</ol>';
            }
            authAlert.innerHTML = html;
            bootFaIcons(authAlert);
        }

        function renderAlertFromDetails(details) {
            if (!authAlert || !details) {
                return;
            }
            renderSimpleAlert(
                details.severity || 'error',
                details.title || 'Hinweis',
                details.message || '',
                details.steps || []
            );
        }

        function escapeHtml(text) {
            var div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function bootFaIcons(root) {
            var scope = root || document;
            scope.querySelectorAll('fa-icon').forEach(function (el) {
                if (typeof el.setIcon === 'function') {
                    el.setIcon(el.getAttribute('name'), el.hasAttribute('solid'));
                }
            });
        }

        function hideAlert() {
            if (!authAlert) { return; }
            authAlert.hidden = true;
            authAlert.innerHTML = '';
        }

        function triggerBlobDownload(filename, base64Content) {
            var binary = atob(base64Content);
            var bytes = new Uint8Array(binary.length);
            for (var i = 0; i < binary.length; i++) {
                bytes[i] = binary.charCodeAt(i);
            }
            var blob = new Blob([bytes], { type: 'application/octet-stream' });
            var url = URL.createObjectURL(blob);
            var link = document.createElement('a');
            link.href = url;
            link.download = filename;
            document.body.appendChild(link);
            link.click();
            link.remove();
            URL.revokeObjectURL(url);
        }

        function downloadAuthFile(options) {
            options = options || {};
            var triggerBtn = options.triggerBtn || downloadBtn;
            if (!triggerBtn) {
                return Promise.reject(new Error('Download nicht verfügbar.'));
            }
            var isRedownload = triggerBtn === redownloadBtn;
            setButtonState(triggerBtn, 'loading');
            triggerBtn.disabled = true;
            hideAlert();
            return fetch('?action=download-auth-file&ajax=1&t=' + encodeURIComponent(authToken))
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (!data.ok) {
                        throw new Error(data.message || 'Download fehlgeschlagen.');
                    }
                    triggerBlobDownload(data.filename || authFileName, data.content);
                    if (isRedownload) {
                        setButtonState(triggerBtn, 'idle');
                        triggerBtn.disabled = false;
                        renderSimpleAlert(
                            'warning',
                            'Neue Auth-Datei',
                            'Bitte die neue Datei auf den Server hochladen und überschreiben.',
                            []
                        );
                        startPolling();
                    } else if (downloadBtn) {
                        setButtonState(downloadBtn, 'done');
                        downloadBtn.disabled = true;
                        if (options.goToStep2 !== false) {
                            goToStep(2);
                        }
                    }
                    return data;
                })
                .catch(function (err) {
                    setButtonState(triggerBtn, 'idle');
                    triggerBtn.disabled = false;
                    renderSimpleAlert('error', 'Download fehlgeschlagen', err.message || 'Unbekannter Fehler', []);
                    throw err;
                });
        }

        function handleAuthStatus(data, fromPoll) {
            if (data.ok) {
                if (pollInterval) {
                    clearInterval(pollInterval);
                    pollInterval = null;
                }
                pollingActive = false;
                hideAlert();
                setButtonState(validateBtn, 'success');
                if (validateBtn) { validateBtn.disabled = true; }
                goToStep(3);
                if (startRecoveryBtn && continueUrl) {
                    startRecoveryBtn.setAttribute('href', continueUrl);
                }
                return;
            }
            if (data.details) {
                renderAlertFromDetails(data.details);
            } else {
                renderSimpleAlert(
                    fromPoll ? 'warning' : 'error',
                    'Prüfung fehlgeschlagen',
                    data.message || 'Auth-Datei noch nicht gültig.',
                    []
                );
            }
            setButtonState(validateBtn, fromPoll ? 'idle' : 'error');
            if (validateBtn && !fromPoll) { validateBtn.disabled = false; }
        }

        function checkAuthStatus(fromPoll) {
            return fetch('?action=auth-status&t=' + encodeURIComponent(authToken))
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    handleAuthStatus(data, fromPoll);
                    return data;
                })
                .catch(function () {
                    if (fromPoll) {
                        renderSimpleAlert('warning', 'Verbindung', 'Verbindungsfehler — erneuter Versuch …', []);
                    }
                });
        }

        function startPolling() {
            if (pollingActive) { return; }
            pollingActive = true;
            if (pollInterval) { clearInterval(pollInterval); }
            pollInterval = setInterval(function () {
                checkAuthStatus(true);
            }, 3000);
        }

        goToStep(<?= (int) $initialStep ?>);
        bootFaIcons(document.getElementById('recovery-auth-wizard'));
        bootFaIcons(document.getElementById('auth-step-3'));
        bootFaIcons(document.querySelector('p.warning'));

        if (downloadBtn) {
            downloadBtn.addEventListener('click', function () {
                if (downloadBtn.getAttribute('data-state') === 'done') {
                    goToStep(2);
                    return;
                }
                downloadAuthFile({ goToStep2: true, clearStatus: true });
            });
        }

        if (redownloadBtn) {
            redownloadBtn.addEventListener('click', function () {
                if (pollInterval) {
                    clearInterval(pollInterval);
                    pollInterval = null;
                }
                pollingActive = false;
                setButtonState(validateBtn, 'idle');
                if (validateBtn) { validateBtn.disabled = false; }
                downloadAuthFile({ goToStep2: false, clearStatus: true, triggerBtn: redownloadBtn });
            });
        }

        if (startRecoveryBtn) {
            startRecoveryBtn.addEventListener('click', function (e) {
                var target = startRecoveryBtn.getAttribute('href') || continueUrl;
                if (!target) {
                    return;
                }
                e.preventDefault();
                window.location.assign(target);
            });
        }

        if (validateBtn) {
            validateBtn.addEventListener('click', function () {
                setButtonState(validateBtn, 'loading');
                validateBtn.disabled = true;
                hideAlert();
                checkAuthStatus(false).finally(function () {
                    if (validateBtn.getAttribute('data-state') !== 'success' && !pollingActive) {
                        startPolling();
                    }
                    if (validateBtn.getAttribute('data-state') !== 'loading' && validateBtn.getAttribute('data-state') !== 'success') {
                        validateBtn.disabled = false;
                    }
                });
            });
        }
    }());
    </script>
    <?php
    recoveryStubRenderPageEnd(['logPanelExpanded' => false]);
}

/**
 * @return list<string>
 */
function recoveryStubGetPackageContentsList(): array
{
    $manifestPath = \defined('RECOVERY_PACKAGE_DIR')
        ? RECOVERY_PACKAGE_DIR . '/manifest.json'
        : '';
    if ($manifestPath !== '' && \is_file($manifestPath)) {
        $json = \json_decode((string) \file_get_contents($manifestPath), true);
        if (\is_array($json['contents'] ?? null)) {
            $items = [];
            foreach ($json['contents'] as $entry) {
                if (\is_string($entry) && $entry !== '') {
                    $items[] = $entry;
                }
            }
            if ($items !== []) {
                return $items;
            }
        }
    }

    return [
        'app.php — Router, Recovery-Logik und ACP-Oberfläche',
        'bootstrap.php, paths.php, version.php — Bootstrap und Version',
        'lib/Recovery/Modes/ — PHP-Modi (Start, Wizard, Plugin entfernen, System-Check, Datensicherung, …)',
        'lib/Recovery/Ui/ — Layout, RecoveryUi, recovery-acp-extensions.css, recovery-wcfsetup-dark.css',
        'lib/Recovery/Bootstrap/ — Datenbank-Anbindung',
        'lib/Recovery/Log/ — Recovery-Logger',
    ];
}

/**
 * @return array{path: string, desc: string}
 */
function recoveryStubParsePackageContentEntry(string $line): array
{
    $line = \trim($line);
    if (\preg_match('/^(.+?)\s+[—–-]\s+(.+)$/u', $line, $m)) {
        return [
            'path' => \trim((string) $m[1]),
            'desc' => \trim((string) $m[2]),
        ];
    }

    return ['path' => $line, 'desc' => ''];
}

function recoveryStubPackagePathIcon(string $path): string
{
    $path = \trim($path);
    if ($path === '' || \str_ends_with($path, '/')) {
        return recoveryStubFaIcon(16, 'folder', true);
    }
    if (\str_contains($path, '/')) {
        return recoveryStubFaIcon(16, 'folder-open', true);
    }

    return recoveryStubFaIcon(16, 'file', true);
}

function recoveryStubRenderPackageInstallPage(string $authHash, ?string $errorMessage = null): void
{
    $version = RECOVERY_PACKAGE_VERSION;
    $ghDownloadUrl = \htmlspecialchars(recoveryStubReleaseDownloadUrl($version));
    $ghReleaseUrl = \htmlspecialchars(recoveryStubReleasePageUrl($version));
    $dirName = RECOVERY_PACKAGE_DIR_NAME;
    $packageContents = recoveryStubGetPackageContentsList();

    recoveryStubRenderPageStart('Recovery-Paket installieren', 'Paket wird für die volle Oberfläche benötigt', null, 'acp');
    ?>
    <?php if ($errorMessage !== null && $errorMessage !== ''): ?>
    <div class="error" role="alert">
        <p><?= recoveryStubAuthAlertIcon('error') ?> <strong>Fehler</strong></p>
        <p><?= \htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') ?></p>
    </div>
    <?php endif; ?>

    <div class="info" role="status">
        <p><?= recoveryStubFaIcon(16, 'circle-info', true) ?>
            Das Archiv <strong><code>recovery-<?= \htmlspecialchars($version) ?>.tar.gz</code></strong> enthält das vollständige
            Recovery-Tool (alle Modi, ACP-Oberfläche, Stylesheets).</p>
    </div>

    <section class="section">
        <header class="sectionHeader">
            <h2 class="sectionTitle">Was enthält das Paket?</h2>
            <p class="sectionDescription">Inhalt von <code>recovery-<?= \htmlspecialchars($version) ?>.tar.gz</code> nach <code><?= \htmlspecialchars($dirName) ?>/</code></p>
        </header>
        <table class="table tableList">
            <thead>
                <tr>
                    <th class="columnIcon"></th>
                    <th>Pfad</th>
                    <th>Beschreibung</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($packageContents as $line):
                    $entry = recoveryStubParsePackageContentEntry((string) $line);
                    ?>
                <tr>
                    <td class="columnIcon"><?= recoveryStubPackagePathIcon($entry['path']) ?></td>
                    <td><code><?= \htmlspecialchars($entry['path']) ?></code></td>
                    <td><?= \htmlspecialchars($entry['desc']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </section>

    <section class="section">
        <header class="sectionHeader">
            <h2 class="sectionTitle">Paket automatisch installieren</h2>
            <p class="sectionDescription">Lädt <a href="<?= $ghDownloadUrl ?>"><code>recovery-<?= \htmlspecialchars($version) ?>.tar.gz</code></a> vom <a href="<?= $ghReleaseUrl ?>" rel="noopener noreferrer">GitHub-Release v<?= \htmlspecialchars($version) ?></a> und entpackt es nach <code><?= \htmlspecialchars($dirName) ?>/</code>.</p>
        </header>
        <form method="post" action="plugin-recovery-tool.php" id="installPackageForm">
            <input type="hidden" name="action" value="install-package">
            <input type="hidden" name="t" value="<?= \htmlspecialchars($authHash) ?>">
            <p class="info recovery-loading-inline" id="installPackageStatus" hidden>
                <?= recoveryStubLoadingIndicator(24) ?>
                Paket wird heruntergeladen und entpackt — bitte warten …
            </p>
            <div class="formSubmit">
                <input type="submit" id="installPackageBtn" class="button buttonPrimary" value="Paket automatisch installieren" accesskey="s">
            </div>
        </form>
    </section>

    <section class="section">
        <header class="sectionHeader">
            <h2 class="sectionTitle">Manuelle Installation</h2>
            <p class="sectionDescription">Wenn der automatische Download auf Ihrem Server blockiert ist.</p>
        </header>
        <dl>
            <dt><label>Archiv</label></dt>
            <dd><a href="<?= $ghDownloadUrl ?>">recovery-<?= \htmlspecialchars($version) ?>.tar.gz</a> (<a href="<?= $ghReleaseUrl ?>" rel="noopener noreferrer">Release</a>)</dd>
            <dt><label>Zielverzeichnis</label></dt>
            <dd><code><?= \htmlspecialchars($dirName) ?>/</code> im WoltLab-Hauptverzeichnis (neben <code>plugin-recovery-tool.php</code>)</dd>
        </dl>
    </section>

    <script>
    (function () {
        var form = document.getElementById('installPackageForm');
        if (!form) { return; }
        form.addEventListener('submit', function () {
            var status = document.getElementById('installPackageStatus');
            var btn = document.getElementById('installPackageBtn');
            if (status) {
                status.hidden = false;
            }
            if (btn) {
                btn.disabled = true;
                btn.value = 'Installation läuft …';
            }
        });
    }());
    </script>
    <?php
    recoveryStubRenderPageEnd(['logPanelExpanded' => false]);
}
