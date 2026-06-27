<?php

declare(strict_types=1);

/**
 * WoltLab-ACP-Markup (nur wenn acp/style/style.css aktiv).
 *
 * Keine recovery-*-Primitives — nur Klassen aus dem echten ACP:
 * acpPageMenu, acpPageSubMenu, section, sectionHeader, table.tableList,
 * tabMenuContainer, breadcrumb, contentHeader, div.info|warning|error|success,
 * dl/dt/dd, acpDashboard / acpDashboardBox.
 */

function recoveryAcpTableClass(): string
{
    return recoveryUsesNativeAcpUi() ? 'table tableList' : 'tableList recovery-table-list recovery-data-table';
}

function recoveryRenderAcpSectionStart(string $title, string $description = '', bool $descriptionIsHtml = false): void
{
    ?>
    <section class="section">
        <header class="sectionHeader">
            <h2 class="sectionTitle"><?= \htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></h2>
            <?php if ($description !== ''): ?>
            <p class="sectionDescription">
                <?php if ($descriptionIsHtml): ?>
                <?= $description ?>
                <?php else: ?>
                <?= \htmlspecialchars($description, ENT_QUOTES, 'UTF-8') ?>
                <?php endif; ?>
            </p>
            <?php endif; ?>
        </header>
    <?php
}

function recoveryRenderAcpSectionEnd(): void
{
    echo '</section>';
}

/**
 * @return list<array{
 *     id: string,
 *     label: string,
 *     icon: string,
 *     items: list<array{mode: int|string, label: string, href?: string}>
 * }>
 */
function recoveryAcpMenuSections(string $authHash): array
{
    $acpUrl = recoveryGetSiteBaseUrl() . 'acp/';

    return [
        [
            'id' => 'recovery-overview',
            'label' => 'Plugin Recovery',
            'icon' => 'life-ring',
            'items' => [
                ['mode' => RECOVERY_MODE_SELECTION, 'label' => 'Start'],
            ],
        ],
        [
            'id' => 'recovery-diagnose',
            'label' => 'Diagnose',
            'icon' => 'stethoscope',
            'items' => [
                ['mode' => RECOVERY_MODE_SYSTEM_CHECK, 'label' => 'System-Check'],
                ['mode' => RECOVERY_MODE_DIRECTORY_STRUCTURE, 'label' => 'Verzeichnisstruktur'],
                ['mode' => RECOVERY_MODE_RECOVERY_WIZARD, 'label' => 'Recovery-Wizard'],
            ],
        ],
        [
            'id' => 'recovery-werkzeuge',
            'label' => 'Werkzeuge',
            'icon' => 'screwdriver-wrench',
            'items' => [
                ['mode' => RECOVERY_MODE_BACKUP_GUIDE, 'label' => 'Datensicherung'],
                ['mode' => RECOVERY_MODE_PLUGIN_UNINSTALL, 'label' => 'Plugin entfernen'],
                ['mode' => RECOVERY_MODE_CACHE_CLEAR, 'label' => 'Cache leeren'],
                ['mode' => RECOVERY_MODE_ACP_REPAIR, 'label' => 'ACP Repair'],
                ['mode' => RECOVERY_MODE_PACKAGE_LIST_REPAIR, 'label' => 'Paketliste reparieren'],
                ['mode' => RECOVERY_MODE_PACKAGE_FILE_REPAIR, 'label' => 'Datei-Reparatur'],
            ],
        ],
        [
            'id' => 'recovery-benutzer',
            'label' => 'Benutzer',
            'icon' => 'user-shield',
            'items' => [
                ['mode' => RECOVERY_MODE_USER_MANAGEMENT, 'label' => 'Admin-Konto'],
            ],
        ],
        [
            'id' => 'recovery-woltlab',
            'label' => 'WoltLab',
            'icon' => 'house',
            'items' => [
                ['mode' => 'acp', 'label' => 'Zum ACP', 'href' => $acpUrl],
            ],
        ],
    ];
}

function recoveryResolveAcpMenuSectionId(int $activeMode): string
{
    return match ($activeMode) {
        RECOVERY_MODE_SELECTION => 'recovery-overview',
        RECOVERY_MODE_SYSTEM_CHECK,
        RECOVERY_MODE_DIRECTORY_STRUCTURE,
        RECOVERY_MODE_RECOVERY_WIZARD => 'recovery-diagnose',
        RECOVERY_MODE_USER_MANAGEMENT => 'recovery-benutzer',
        default => 'recovery-werkzeuge',
    };
}

/**
 * @param array{mode: int|string, label: string, href?: string} $item
 */
function recoveryAcpMenuItemHref(array $item, string $authHash): string
{
    if (isset($item['href'])) {
        return (string) $item['href'];
    }

    $mode = $item['mode'];

    return $mode === RECOVERY_MODE_SELECTION
        ? recoveryHomeUrl($authHash)
        : recoveryBuildModeUrl((int) $mode, $authHash);
}

/**
 * Linke ACP-Navigation (pageMenu.tpl) — Hauptpunkte + Untermenü je Bereich.
 */
function recoveryRenderAcpPageMenu(int $activeMode, string $authHash): void
{
    if (!recoveryUsesNativeAcpUi()) {
        return;
    }

    $activeSectionId = recoveryResolveAcpMenuSectionId($activeMode);
    $sections = recoveryAcpMenuSections($authHash);

    ?>
    <nav id="acpPageMenu" class="acpPageMenu">
        <ol class="acpPageMenuList">
            <?php foreach ($sections as $section):
                $isSectionActive = $section['id'] === $activeSectionId;
                ?>
            <li>
                <button type="button" class="acpPageMenuLink<?= $isSectionActive ? ' active' : '' ?>"
                    data-menu-item="<?= \htmlspecialchars($section['id'], ENT_QUOTES, 'UTF-8') ?>">
                    <?= recoveryFaIcon(32, (string) $section['icon'], true) ?>
                    <span class="acpPageMenuItemLabel"><?= \htmlspecialchars((string) $section['label'], ENT_QUOTES, 'UTF-8') ?></span>
                </button>
            </li>
            <?php endforeach; ?>
        </ol>
    </nav>
    <nav id="acpPageSubMenu" class="acpPageSubMenu">
        <?php foreach ($sections as $section):
            $isSectionActive = $section['id'] === $activeSectionId;
            ?>
        <ol class="acpPageSubMenuCategoryList<?= $isSectionActive ? ' active' : '' ?>"
            data-menu-item="<?= \htmlspecialchars($section['id'], ENT_QUOTES, 'UTF-8') ?>">
            <li class="acpPageSubMenuCategory">
                <span><?= \htmlspecialchars((string) $section['label'], ENT_QUOTES, 'UTF-8') ?></span>
                <ol class="acpPageSubMenuItemList">
                    <?php foreach ($section['items'] as $item):
                        $href = recoveryAcpMenuItemHref($item, $authHash);
                        $itemMode = $item['mode'];
                        $isActive = \is_int($itemMode) && $itemMode === $activeMode;
                        ?>
                    <li<?= $isActive ? ' class="active"' : '' ?>>
                        <a href="<?= \htmlspecialchars($href, ENT_QUOTES, 'UTF-8') ?>" class="acpPageSubMenuLink"><?= \htmlspecialchars((string) $item['label'], ENT_QUOTES, 'UTF-8') ?></a>
                    </li>
                    <?php endforeach; ?>
                </ol>
            </li>
        </ol>
        <?php endforeach; ?>
    </nav>
    <?php
}

/**
 * Mobil-Button im Kopfbereich (pageHeaderMenu.tpl) — Inhalt aus acpPageMenu via recovery-acp-menu.js.
 */
function recoveryRenderAcpPageHeaderMenu(): void
{
    if (!recoveryUsesNativeAcpUi()) {
        return;
    }
    ?>
    <div id="mainMenu" class="mainMenu" aria-hidden="true"></div>
    <button type="button" class="pageHeaderMenuMobile" aria-expanded="false" aria-label="Menü">
        <span class="pageHeaderMenuMobileInactive" aria-hidden="true"><?= recoveryFaIcon(32, 'bars', true) ?></span>
        <span class="pageHeaderMenuMobileActive" aria-hidden="true"><?= recoveryFaIcon(32, 'xmark', true) ?></span>
    </button>
    <?php
}

function recoveryAcpMenuScriptHref(): string
{
    $relative = 'recovery-tool/lib/Recovery/Ui/recovery-acp-menu.js';

    if (\defined('RECOVERY_PACKAGE_DIR') && \is_file(RECOVERY_PACKAGE_DIR . '/lib/Recovery/Ui/recovery-acp-menu.js')) {
        return recoveryAssetPublicHref($relative);
    }

    return '';
}

/**
 * WCF-Breadcrumbs (flex, nicht gestapelte .breadcrumb > ul > li).
 *
 * @param list<array{href: ?string, label: string, current: bool}> $items
 */
function recoveryRenderAcpBreadcrumb(array $items): void
{
    if ($items === []) {
        return;
    }
    ?>
    <nav class="breadcrumbs" aria-label="Brotkrumen">
        <ol class="breadcrumbs__list">
            <?php foreach ($items as $item): ?>
            <li class="breadcrumbs__item" title="<?= \htmlspecialchars((string) $item['label'], ENT_QUOTES, 'UTF-8') ?>">
                <?php if (!empty($item['current'])): ?>
                <span class="breadcrumbs__title" aria-current="page"><?= \htmlspecialchars((string) $item['label'], ENT_QUOTES, 'UTF-8') ?></span>
                <?php else: ?>
                <a class="breadcrumbs__link" href="<?= \htmlspecialchars((string) $item['href'], ENT_QUOTES, 'UTF-8') ?>">
                    <span class="breadcrumbs__title"><?= \htmlspecialchars((string) $item['label'], ENT_QUOTES, 'UTF-8') ?></span>
                </a>
                <?php endif; ?>
            </li>
            <?php endforeach; ?>
        </ol>
    </nav>
    <?php
}

function recoveryRenderAcpMenuScript(): void
{
    if (!recoveryUsesNativeAcpUi()) {
        return;
    }

    $href = recoveryAcpMenuScriptHref();
    if ($href === '') {
        $path = (\defined('RECOVERY_PACKAGE_DIR') ? RECOVERY_PACKAGE_DIR : __DIR__) . '/recovery-acp-menu.js';
        if (!\is_file($path)) {
            return;
        }
        echo '<script>' . (string) \file_get_contents($path) . '</script>';

        return;
    }

    echo '<script src="' . \htmlspecialchars($href, ENT_QUOTES, 'UTF-8') . '" defer></script>';
}
