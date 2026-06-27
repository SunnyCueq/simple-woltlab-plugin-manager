<?php

declare(strict_types=1);

/**
 * Icons wie WoltLab 6+ — ausschließlich <fa-icon> + WebComponent.min.js
 *
 * @see https://docs.woltlab.com/6.2/migration/wsc55/icons/
 * @see wcf\system\style\FontAwesomeIcon
 * @see wcfsetup/install/files/acp/templates/header.tpl
 */

function recoveryAlertIconName(string $type): string
{
    return match ($type) {
        'success' => 'circle-check',
        'warning' => 'triangle-exclamation',
        'error' => 'circle-xmark',
        default => 'circle-info',
    };
}

function recoveryFaIcon(int $size, string $name, bool $solid = true): string
{
    if (\class_exists(\wcf\system\style\FontAwesomeIcon::class)) {
        try {
            return \wcf\system\style\FontAwesomeIcon::fromValues($name, $solid)->toHtml($size);
        } catch (\Throwable $ignored) {
        }
    }

    $size = \in_array($size, [16, 24, 32, 48, 64, 96, 128, 144], true) ? $size : 16;
    $name = \htmlspecialchars($name, \ENT_QUOTES, 'UTF-8');

    if ($solid) {
        return \sprintf('<fa-icon size="%d" name="%s" solid></fa-icon>', $size, $name);
    }

    return \sprintf('<fa-icon size="%d" name="%s"></fa-icon>', $size, $name);
}

function recoveryLoadingIndicator(int $size = 24, bool $hideText = true): string
{
    if (!\in_array($size, [24, 48, 96], true)) {
        $size = 24;
    }
    $attrs = \sprintf('size="%d"', $size);
    if ($hideText) {
        $attrs .= ' hide-text';
    }

    return '<woltlab-core-loading-indicator ' . $attrs . '></woltlab-core-loading-indicator>';
}

function recoveryWebComponentsScriptHref(): string
{
    if (!\defined('WCF_DIR')) {
        return '';
    }
    $relative = 'js/WoltLabSuite/WebComponent.min.js';
    if (!\is_readable(WCF_DIR . $relative)) {
        return '';
    }

    $href = recoveryAssetPublicHref($relative);
    $mtime = @\filemtime(WCF_DIR . $relative);
    if ($mtime !== false) {
        $href .= (\str_contains($href, '?') ? '&' : '?') . 'v=' . $mtime;
    }

    return $href;
}

function recoveryRenderWoltLabHeadGlobals(): void
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

function recoveryResolveFaFontUrl(string $relative): string
{
    if (!\defined('WCF_DIR') || !\is_readable(WCF_DIR . $relative)) {
        return '';
    }

    return recoveryAssetPublicHref($relative);
}

function recoveryRenderFaIconStyles(): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;

    $solidFont = recoveryResolveFaFontUrl('acp/style/font/fa-solid-900.woff2');
    if ($solidFont === '') {
        $solidFont = recoveryResolveFaFontUrl('icon/font-awesome/v7/webfonts/fa-solid-900.woff2');
    }
    $regularFont = recoveryResolveFaFontUrl('acp/style/font/fa-regular-400.woff2');
    if ($regularFont === '') {
        $regularFont = recoveryResolveFaFontUrl('icon/font-awesome/v7/webfonts/fa-regular-400.woff2');
    }
    ?>
    <style id="recoveryFaIconStyles">
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
    fa-brand,
    fa-icon {
        align-items: center;
        display: inline-flex;
        height: var(--icon-size, 16px);
        justify-content: center;
        pointer-events: none;
        width: calc(var(--icon-size, 16px) * 1.25);
    }
    fa-icon[hidden],
    fa-brand[hidden] {
        display: none;
    }
    fa-icon[size="16"] { --font-size: 14px; --icon-size: 16px; }
    fa-icon[size="24"] { --font-size: 18px; --icon-size: 24px; }
    fa-icon[size="32"] { --font-size: 28px; --icon-size: 32px; }
    fa-icon[size="48"] { --font-size: 42px; --icon-size: 48px; }
    fa-icon[size="64"] { --font-size: 56px; --icon-size: 64px; }
    fa-icon[size="96"] { --font-size: 84px; --icon-size: 96px; }
    fa-icon {
        -moz-osx-font-smoothing: grayscale;
        -webkit-font-smoothing: antialiased;
        font-family: var(--fa-font-family, "Font Awesome 7 Free") !important;
        font-size: var(--font-size, 14px) !important;
        font-style: normal !important;
        font-variant: normal !important;
        font-weight: var(--fa-font-weight, 400) !important;
        line-height: 1 !important;
        text-rendering: auto;
        visibility: visible !important;
    }
    fa-icon[solid] {
        font-weight: 900 !important;
    }
    /* ACP style.css: fa-icon:not(:upgraded){visibility:hidden} — Recovery überschreibt */
    #tplRecoveryTool fa-icon,
    #tplRecoveryAuth fa-icon {
        visibility: visible !important;
    }
    #tplRecoveryTool .mode-button fa-icon,
    #tplRecoveryTool .recovery-scenario-icon fa-icon,
    #tplRecoveryTool .contentNavigation fa-icon {
        color: var(--wcfStatusInfoText, #369);
    }
    </style>
    <?php
}

/**
 * Initialisiert statische fa-icon-Elemente nach WebComponent-Load (wie setIcon() in TS).
 */
function recoveryRenderFaIconBootScript(): void
{
    static $done = false;
    if ($done) {
        return;
    }
    if (recoveryWebComponentsScriptHref() === '') {
        return;
    }
    $done = true;
    ?>
    <script data-eager="true">
    (function () {
        function bootIcon(el) {
            if (!el || typeof el.setIcon !== "function") {
                return;
            }
            var name = el.getAttribute("name");
            if (!name) {
                return;
            }
            try {
                el.setIcon(name, el.hasAttribute("solid"));
            } catch (e) {}
        }
        function bootAll() {
            document.querySelectorAll("fa-icon[name]").forEach(bootIcon);
        }
        if (customElements.get("fa-icon")) {
            bootAll();
        } else {
            customElements.whenDefined("fa-icon").then(bootAll);
        }
        document.addEventListener("DOMContentLoaded", bootAll);
    }());
    </script>
    <?php
}

function recoveryRenderWoltLabWebComponentsScript(): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $href = recoveryWebComponentsScriptHref();
    if ($href === '') {
        return;
    }
    $done = true;
    recoveryRenderWoltLabHeadGlobals();
    ?>
    <script data-eager="true" src="<?= \htmlspecialchars($href) ?>"></script>
    <?php
    recoveryRenderFaIconBootScript();
}
