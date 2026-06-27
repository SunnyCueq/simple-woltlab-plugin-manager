<?php

declare(strict_types=1);

function recoveryAcpLayoutCssHref(): string
{
    $relative = 'recovery-tool/lib/Recovery/Ui/recovery-acp-layout.css';
    if (\defined('RECOVERY_PACKAGE_DIR') && \is_file(RECOVERY_PACKAGE_DIR . '/lib/Recovery/Ui/recovery-acp-layout.css')) {
        return recoveryAssetPublicHref($relative);
    }

    return '';
}

function recoveryExtensionsCssHref(): string
{
    $file = recoveryUsesNativeAcpUi() ? 'recovery-acp-native.css' : 'recovery-acp-extensions.css';
    $relative = 'recovery-tool/lib/Recovery/Ui/' . $file;
    if (\defined('RECOVERY_PACKAGE_DIR') && \is_file(RECOVERY_PACKAGE_DIR . '/lib/Recovery/Ui/' . $file)) {
        return recoveryAssetPublicHref($relative);
    }

    return '';
}

function recoveryWcfSetupDarkCssHref(): string
{
    $relative = 'recovery-tool/lib/Recovery/Ui/recovery-wcfsetup-dark.css';
    if (\defined('RECOVERY_PACKAGE_DIR') && \is_file(RECOVERY_PACKAGE_DIR . '/lib/Recovery/Ui/recovery-wcfsetup-dark.css')) {
        return recoveryAssetPublicHref($relative);
    }

    return '';
}

function recoveryAcpLayoutCssInline(): string
{
    $path = (\defined('RECOVERY_PACKAGE_DIR') ? RECOVERY_PACKAGE_DIR : __DIR__) . '/recovery-acp-layout.css';

    return \is_file($path) ? (string) \file_get_contents($path) : '';
}

function recoveryExtensionsCssInline(): string
{
    $file = recoveryUsesNativeAcpUi() ? 'recovery-acp-native.css' : 'recovery-acp-extensions.css';
    $path = (\defined('RECOVERY_PACKAGE_DIR') ? RECOVERY_PACKAGE_DIR : __DIR__) . '/' . $file;

    return \is_file($path) ? (string) \file_get_contents($path) : '';
}

function recoveryWcfSetupDarkCssInline(): string
{
    $path = (\defined('RECOVERY_PACKAGE_DIR') ? RECOVERY_PACKAGE_DIR : __DIR__) . '/recovery-wcfsetup-dark.css';

    return \is_file($path) ? (string) \file_get_contents($path) : '';
}

function recoveryRenderColorSchemeHeadScript(): void
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

function recoveryRenderAcpInlineLayoutCss(): void
{
    $usesCompiledAcp = false;
    try {
        $usesCompiledAcp = (bool) (recoveryGetSetupAssets()['usesCompiledAcpStyle'] ?? false);
    } catch (\Throwable $ignored) {
    }
    ?>
    <style>
        <?php if (!$usesCompiledAcp): ?>
        #pageHeaderContainer {
            height: 100px;
        }
        #pageHeader {
            padding: 30px 0;
        }
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
        .recovery-auth-step[hidden] {
            display: none !important;
        }
    </style>
    <?php
}

/**
 * @param array{value?: int, max?: int, label?: string}|null $wizardProgress
 * @param array<string, mixed>|null $assets
 * @param string $bodyClass extra CSS classes on body
 */
function recoveryRenderPageStart(
    string $documentTitle,
    string $contentTitle = '',
    ?array $assets = null,
    ?array $wizardProgress = null,
    string $contentDescription = '',
    string $bodyClass = ''
): void {
    try {
        $assets ??= recoveryGetSetupAssets();
    } catch (\Throwable $ignored) {
        $assets = [
            'stylesheets' => [],
            'WCFSetup.css' => '',
            'woltlabSuite.png' => '',
            'fontAwesomeCss' => '',
            'fontAwesomeLocal' => false,
            'usesCompiledAcpStyle' => false,
            'webComponentsJs' => '',
        ];
    }

    $stylesheets = $assets['stylesheets'] ?? [];
    if ($stylesheets === [] && ($assets['WCFSetup.css'] ?? '') !== '') {
        $stylesheets = [(string) $assets['WCFSetup.css']];
    }

    $usesCompiledAcp = (bool) ($assets['usesCompiledAcpStyle'] ?? false);
    $logoHref = ($assets['woltlabSuite.png'] ?? '') !== '' ? recoveryAssetPublicHref((string) $assets['woltlabSuite.png']) : '';
    $faHref = ($assets['fontAwesomeCss'] ?? '') !== ''
        ? recoveryAssetPublicHref((string) $assets['fontAwesomeCss'])
        : '';
    $faExtra = ($assets['fontAwesomeLocal'] ?? false) ? '' : ' crossorigin="anonymous" referrerpolicy="no-referrer"';

    $usesNativeAcp = recoveryUsesNativeAcpUi();
    $layoutCss = '';
    $layoutInline = '';
    if (!$usesNativeAcp) {
        $layoutCss = recoveryAcpLayoutCssHref();
        $layoutInline = $layoutCss === '' ? recoveryAcpLayoutCssInline() : '';
    }
    $extCss = recoveryExtensionsCssHref();
    $extInline = $extCss === '' ? recoveryExtensionsCssInline() : '';
    $darkCss = !$usesCompiledAcp ? recoveryWcfSetupDarkCssHref() : '';
    $darkInline = ($darkCss === '' && !$usesCompiledAcp) ? recoveryWcfSetupDarkCssInline() : '';

    $displayTitle = $contentTitle !== '' ? $contentTitle : $documentTitle;
    $showContentHeader = $contentTitle !== '' || $contentDescription !== '' || $wizardProgress !== null;
    $bodyClassAttr = \trim($bodyClass) !== '' ? ' ' . \htmlspecialchars(\trim($bodyClass), ENT_QUOTES, 'UTF-8') : '';
    $pageContainerClass = $usesNativeAcp ? 'pageContainer acpPageSubMenuActive' : 'pageContainer acpPageHiddenMenu';
    $logoSmallHref = '';
    if (($assets['woltlabSuite-small.png'] ?? '') !== '') {
        $logoSmallHref = recoveryAssetPublicHref((string) $assets['woltlabSuite-small.png']);
    }

    \header('Content-Type: text/html; charset=utf-8');
    ?>
<!DOCTYPE html>
<html lang="de" data-color-scheme="system">
<head>
    <meta charset="utf-8">
    <meta name="robots" content="noindex">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= \htmlspecialchars($documentTitle) ?></title>
    <?php foreach ($stylesheets as $stylesheet): ?>
    <link rel="stylesheet" type="text/css" media="screen" href="<?= \htmlspecialchars(recoveryAssetPublicHref((string) $stylesheet)) ?>">
    <?php endforeach; ?>
    <?php if ($faHref !== ''): ?>
    <link rel="stylesheet" href="<?= \htmlspecialchars($faHref) ?>"<?= $faExtra ?>>
    <?php endif; ?>
    <?php recoveryRenderColorSchemeHeadScript(); ?>
    <?php recoveryRenderWoltLabHeadGlobals(); ?>
    <?php recoveryRenderFaIconStyles(); ?>
    <?php recoveryRenderWoltLabWebComponentsScript(); ?>
    <?php recoveryRenderAcpInlineLayoutCss(); ?>
    <?php if ($layoutCss !== ''): ?>
    <link rel="stylesheet" href="<?= \htmlspecialchars($layoutCss) ?>">
    <?php elseif ($layoutInline !== ''): ?>
    <style><?= $layoutInline ?></style>
    <?php endif; ?>
    <?php if ($extCss !== ''): ?>
    <link rel="stylesheet" href="<?= \htmlspecialchars($extCss) ?>">
    <?php elseif ($extInline !== ''): ?>
    <style><?= $extInline ?></style>
    <?php endif; ?>
    <?php if ($darkCss !== ''): ?>
    <link rel="stylesheet" href="<?= \htmlspecialchars($darkCss) ?>">
    <?php elseif ($darkInline !== ''): ?>
    <style><?= $darkInline ?></style>
    <?php endif; ?>
</head>
<body id="tplRecoveryTool" data-template="recoveryTool" class="wcfAcp<?= $bodyClassAttr ?>">
<a id="top"></a>
<div id="pageContainer" class="<?= \htmlspecialchars($pageContainerClass, ENT_QUOTES, 'UTF-8') ?>">
    <div id="pageHeaderContainer" class="pageHeaderContainer">
        <header id="pageHeader" class="pageHeader">
            <div id="pageHeaderPanel" class="pageHeaderPanel">
                <div class="layoutBoundary">
                    <div id="pageHeaderLogo" class="pageHeaderLogo">
                        <?php if ($logoHref !== ''): ?>
                        <a href="<?= \htmlspecialchars(recoveryGetSiteBaseUrl()) ?>acp/">
                            <img src="<?= \htmlspecialchars($logoHref) ?>" alt="" width="562" height="80" loading="eager" class="pageHeaderLogoLarge">
                            <?php if ($logoSmallHref !== ''): ?>
                            <img src="<?= \htmlspecialchars($logoSmallHref) ?>" alt="" width="55" height="30" loading="eager" class="pageHeaderLogoSmall">
                            <?php endif; ?>
                        </a>
                        <?php endif; ?>
                    </div>
                    <?php if ($usesNativeAcp): ?>
                    <?php recoveryRenderAcpPageHeaderMenu(); ?>
                    <?php endif; ?>
                </div>
            </div>
        </header>
    </div>
    <div id="acpPageContentContainer" class="acpPageContentContainer">
    <?php
    if (!$usesNativeAcp) {
        recoveryRenderPageMainOpen(
            $displayTitle,
            $showContentHeader,
            $wizardProgress,
            $contentDescription
        );
    } else {
        RecoveryPageLayoutState::$displayTitle = $displayTitle;
        RecoveryPageLayoutState::$showContentHeader = $showContentHeader;
        RecoveryPageLayoutState::$wizardProgress = $wizardProgress;
        RecoveryPageLayoutState::$contentDescription = $contentDescription;
    }
}

/**
 * @param array{value?: int, max?: int, label?: string}|null $wizardProgress
 */
function recoveryRenderPageMainOpen(
    string $displayTitle,
    bool $showContentHeader,
    ?array $wizardProgress,
    string $contentDescription
): void {
    ?>
        <section id="main" class="main" role="main">
            <div class="layoutBoundary">
                <div id="content" class="content">
                    <?php if ($showContentHeader): ?>
                    <header class="contentHeader">
                        <div class="contentHeaderTitle">
                            <h1 class="contentTitle"><?= \htmlspecialchars($displayTitle) ?></h1>
                            <?php if ($wizardProgress !== null): ?>
                            <p class="contentHeaderDescription">
                                <progress id="recoveryWizardProgress" value="<?= (int) ($wizardProgress['value'] ?? 0) ?>" max="<?= (int) ($wizardProgress['max'] ?? 100) ?>" style="width:300px" title="<?= (int) ($wizardProgress['value'] ?? 0) ?>%"><?= (int) ($wizardProgress['value'] ?? 0) ?>%</progress>
                                <?php if (($wizardProgress['label'] ?? '') !== ''): ?>
                                <?= \htmlspecialchars((string) $wizardProgress['label']) ?>
                                <?php endif; ?>
                            </p>
                            <?php elseif ($contentDescription !== ''): ?>
                            <p class="contentHeaderDescription"><?= $contentDescription ?></p>
                            <?php endif; ?>
                        </div>
                    </header>
                    <?php endif; ?>
    <?php
}

final class RecoveryPageLayoutState
{
    public static string $displayTitle = '';
    public static bool $showContentHeader = false;
    /** @var array{value?: int, max?: int, label?: string}|null */
    public static ?array $wizardProgress = null;
    public static string $contentDescription = '';
}

function recoveryRenderDeferredPageMainOpen(): void
{
    recoveryRenderPageMainOpen(
        RecoveryPageLayoutState::$displayTitle,
        RecoveryPageLayoutState::$showContentHeader,
        RecoveryPageLayoutState::$wizardProgress,
        RecoveryPageLayoutState::$contentDescription
    );
}

function recoveryRenderPageEnd(?array $assets = null): void
{
    $baseUrl = '';
    try {
        $baseUrl = recoveryGetSiteBaseUrl();
    } catch (\Throwable $ignored) {
    }
    ?>
                </div>
            </div>
        </section>
    </div>
</div>
<p class="recovery-footer-meta">
    <a href="https://github.com/benjarogit/sc-woltlab-plugin-recovery" target="_blank" rel="noopener">Plugin Recovery Tool</a>
    v<?= \htmlspecialchars(RECOVERY_VERSION) ?>
    · Einstieg: <code class="recovery-tool-script"><?= \htmlspecialchars(recoveryToolScriptName()) ?></code>
    &copy; <?= \date('Y') ?> Sunny C.
    <?php if ($baseUrl !== ''): ?>
    | <a href="<?= \htmlspecialchars($baseUrl) ?>">Installation</a>
    <?php endif; ?>
    | <a href="https://manual.woltlab.com/de/recovery-tool/" target="_blank" rel="noopener">WoltLab Recovery</a>
</p>
<?php
    recoveryRenderWoltLabUiShell();
    recoveryFormLoadingScript();
    recoveryRenderAcpMenuScript();
?>
</body>
</html>
    <?php
}
