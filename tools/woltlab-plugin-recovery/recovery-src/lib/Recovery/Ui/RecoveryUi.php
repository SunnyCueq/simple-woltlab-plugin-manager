<?php

declare(strict_types=1);

/**
 * Wiederverwendbare UI-Bausteine für das Recovery-Tool (ACP-Dark-Theme).
 *
 * Design-System (einheitlich auf allen Seiten) — Primitives zuerst, dann Komponenten:
 *
 * PRIMITIVES (CSS recovery-acp-extensions.css):
 * - recovery-chip / recovery-chip-bar — Navigation, Breadcrumb, Experten-Werkzeuge (ein Look)
 * - recovery-surface — Karten, Panels, Fortschrittsleisten (ein Rahmen)
 * - recovery-toolbar-row — Label + Chip-Leiste (z. B. „Werkzeuge“)
 * - --recovery-nav-* — Hover/Active auf hellem und dunklem ACP
 *
 * KOMPONENTEN:
 * - Seitenkopf: recovery-intake-hero--compact (Icon, h1, .subtitle)
 * - Backup-/Szenario-Karten: recoveryRenderBackupChoiceCards() → recovery-scenario-card (+ Eck-Badge)
 * - Startseite: globale Nav (alle Werkzeug-Modi) + Szenario-Karten (2 Situationen) + „Weitere Experten-Modi“ (3 Spezialfälle)
 * - Kein Quick-Grid auf der Startseite — Werkzeuge nur in der globalen Nav, Szenarien nur als Karten
 * - Wizard-Ablauf: acpDashboardBox--discovery (4 Kacheln, Eck-Badge 1–4)
 * - Hinweise: recoveryRenderAlert() — kein nacktes p.info mit Inline-Margin
 * - Mehrere Start-Hinweise: recoveryRenderStatusFeed() — eine Karte, getrennte Zeilen (kein Alert-Stapel)
 * - Sicherheitshinweis (Startseite): recoveryRenderSecurityNotice() — recovery-security-notice Infobox
 * - Primär-/Sekundär-Aktionen: recoveryRenderActionBar() — eine Aktion, ein Ort (kein Button + Hint-Box)
 * - Aufklappbar: recovery-panel / recoveryRenderPanelStart() (kein section.section drumherum)
 * - Wizard Run/Done: recoveryRenderWizardCompletionAlert() + recovery-summary-table
 *   (kein recovery-run-hero, recovery-done-hero oder acpDashboardBox--run-metric)
 * - Wizard Run-Blöcke: recovery-run-block + recovery-system-check-table (wie System-Check)
 * - Wizard Einordnung/Hinweise: recoveryRenderAlert() — kein verschachteltes recovery-alert in run-block
 * - Tabellen: recovery-data-table (+ --sysinfo | --check | --summary); Prüfungen: recovery-system-check-table
 * - recovery-grid-card: nur Plugin-Uninstall Auswahl — nicht für Backup/Wizard
 * - Labels durchgängig Deutsch (z. B. „Plugin entfernen“, nicht „Plugin Uninstall“)
 */

function recoveryToolScriptName(): string
{
    return 'plugin-recovery-tool.php';
}

/**
 * CSS-Klassen für einheitliche Navigations-Chips (Design-System-Primitive).
 *
 * @param list<string> $modifiers z. B. recovery-chip--accent, recovery-chip--end
 */
function recoveryUsesNativeAcpUi(): bool
{
    static $native = null;
    if ($native !== null) {
        return $native;
    }
    try {
        $native = recoveryGetSetupAssets()['usesCompiledAcpStyle'] ?? false;
    } catch (\Throwable $ignored) {
        $native = false;
    }

    return $native;
}

/**
 * @deprecated Nur Fallback ohne kompiliertes acp/style/style.css
 */
function recoveryChipClass(bool $active = false, bool $current = false, array $modifiers = []): string
{
    $parts = ['recovery-chip'];
    if ($active) {
        $parts[] = 'is-active';
    }
    if ($current) {
        $parts[] = 'is-current';
    }
    foreach ($modifiers as $modifier) {
        if ($modifier !== '') {
            $parts[] = $modifier;
        }
    }

    return \implode(' ', $parts);
}

/**
 * Sicherheitshinweis (Startseite Footer) — kompakte Infobox ohne nummerierte Schritte.
 */
function recoveryRenderSecurityNotice(): void
{
    if (recoveryUsesNativeAcpUi()) {
        recoveryRenderAlert(
            'warning',
            'Das Tool arbeitet direkt auf dem Server (Datenbank &amp; Dateien). '
            . 'Nach erfolgreicher Recovery alle Recovery-Dateien vom Webspace entfernen.',
            'Sicherheitshinweis'
        );

        return;
    }
    ?>
    <div class="recovery-security-notice" role="alert" aria-label="Sicherheitshinweis">
        <div class="recovery-security-notice__head">
            <?= recoveryFaIcon(16, 'triangle-exclamation') ?>
            <span class="recovery-security-notice__label">WICHTIG</span>
        </div>
        <div class="recovery-security-notice__body">
            <p>Das Tool arbeitet direkt auf dem Server (Datenbank &amp; Dateien).</p>
            <p>Nach erfolgreicher Recovery alle Recovery-Dateien vom Webspace entfernen.</p>
        </div>
    </div>
    <?php
}

/**
 * @param 'info'|'success'|'warning'|'error' $type
 */
function recoveryRenderAlert(
    string $type,
    string $body,
    ?string $title = null,
    bool $bodyIsHtml = false
): void {
    if (recoveryUsesNativeAcpUi()) {
        $class = match ($type) {
            'success' => 'success',
            'warning' => 'warning',
            'error' => 'error',
            default => 'info',
        };
        echo '<div class="' . $class . '" role="status">';
        if ($title !== null && $title !== '') {
            echo '<p>' . recoveryFaIcon(16, recoveryAlertIconName($type), true) . ' <strong>'
                . \htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</strong></p>';
        }
        if ($bodyIsHtml) {
            echo '<div class="recovery-wcf-alert-body">' . $body . '</div>';
        } else {
            echo '<p>' . \nl2br(\htmlspecialchars($body, ENT_QUOTES, 'UTF-8'), false) . '</p>';
        }
        echo '</div>';

        return;
    }

    $class = match ($type) {
        'success' => 'recovery-alert recovery-alert--success',
        'warning' => 'recovery-alert recovery-alert--warning',
        'error' => 'recovery-alert recovery-alert--error',
        default => 'recovery-alert recovery-alert--info',
    };
    $icon = match ($type) {
        'success' => 'circle-check',
        'warning' => 'triangle-exclamation',
        'error' => 'circle-xmark',
        default => 'circle-info',
    };
    $role = 'status';
    ?>
    <div class="<?= $class ?>" role="<?= $role ?>">
        <div class="recovery-alert__head">
            <?= recoveryFaIcon(16, $icon) ?>
            <?php if ($title !== null && $title !== ''): ?>
            <strong class="recovery-alert__title"><?= \htmlspecialchars($title) ?></strong>
            <?php endif; ?>
        </div>
        <div class="recovery-alert__body">
            <?php if ($bodyIsHtml): ?>
            <?= $body ?>
            <?php else: ?>
            <?= \nl2br(\htmlspecialchars($body), false) ?>
            <?php endif; ?>
        </div>
    </div>
    <?php
}

/**
 * Mehrere Status-/Hinweis-Zeilen in einer Karte (Startseite, Log + Flash).
 *
 * @param list<array{
 *     type: 'info'|'success'|'warning'|'error',
 *     title: string,
 *     body: string,
 *     bodyIsHtml?: bool,
 *     mono?: bool,
 *     id?: string,
 *     meta?: string
 * }> $items
 */
function recoveryRenderStatusFeed(array $items, ?string $sectionTitle = null): void
{
    if ($items === []) {
        return;
    }

    if (recoveryUsesNativeAcpUi()) {
        foreach ($items as $item) {
            $type = $item['type'] ?? 'info';
            $class = match ($type) {
                'success' => 'success',
                'warning' => 'warning',
                'error' => 'error',
                default => 'info',
            };
            $idAttr = !empty($item['id'])
                ? ' id="' . \htmlspecialchars((string) $item['id'], ENT_QUOTES, 'UTF-8') . '"'
                : '';
            echo '<div class="' . $class . '"' . $idAttr . ' role="status">';
            if (!empty($item['title'])) {
                echo '<p><strong>' . \htmlspecialchars((string) $item['title'], ENT_QUOTES, 'UTF-8') . '</strong></p>';
            }
            if (!empty($item['bodyIsHtml'])) {
                echo '<div class="recovery-wcf-alert-body">' . $item['body'] . '</div>';
            } else {
                $body = (string) ($item['body'] ?? '');
                if (!empty($item['mono'])) {
                    echo '<p><code style="display:block;white-space:pre-wrap;word-break:break-word;">'
                        . \htmlspecialchars($body, ENT_QUOTES, 'UTF-8') . '</code></p>';
                } else {
                    echo '<p>' . \nl2br(\htmlspecialchars($body, ENT_QUOTES, 'UTF-8'), false) . '</p>';
                }
            }
            if (!empty($item['meta'])) {
                echo '<p><small>' . \htmlspecialchars((string) $item['meta'], ENT_QUOTES, 'UTF-8') . '</small></p>';
            }
            echo '</div>';
        }

        return;
    }

    $showHeading = $sectionTitle !== null && $sectionTitle !== '';
    $ariaLabel = $showHeading ? $sectionTitle : 'Status und Hinweise';
    ?>
    <section class="recovery-status-feed" aria-label="<?= \htmlspecialchars($ariaLabel, ENT_QUOTES, 'UTF-8') ?>">
        <?php if ($showHeading): ?>
        <header class="recovery-status-feed__head">
            <?= recoveryFaIcon(16, 'bell') ?>
            <h2 class="recovery-status-feed__heading"><?= \htmlspecialchars($sectionTitle, ENT_QUOTES, 'UTF-8') ?></h2>
        </header>
        <?php endif; ?>
        <ul class="recovery-status-feed__list">
            <?php foreach ($items as $item): ?>
            <?php
            $type = $item['type'] ?? 'info';
            $icon = match ($type) {
                'success' => 'circle-check',
                'warning' => 'triangle-exclamation',
                'error' => 'circle-xmark',
                default => 'circle-info',
            };
            $itemId = $item['id'] ?? '';
            ?>
            <li class="recovery-status-feed__item recovery-status-feed__item--<?= \htmlspecialchars($type, ENT_QUOTES, 'UTF-8') ?>"
                <?php if ($itemId !== ''): ?>id="<?= \htmlspecialchars($itemId, ENT_QUOTES, 'UTF-8') ?>"<?php endif; ?>>
                <span class="recovery-status-feed__icon" aria-hidden="true"><?= recoveryFaIcon(16, $icon) ?></span>
                <div class="recovery-status-feed__content">
                    <p class="recovery-status-feed__title"><?= \htmlspecialchars($item['title'], ENT_QUOTES, 'UTF-8') ?></p>
                    <?php if (!empty($item['meta'])): ?>
                    <p class="recovery-status-feed__meta"><?= \htmlspecialchars((string) $item['meta'], ENT_QUOTES, 'UTF-8') ?></p>
                    <?php endif; ?>
                    <div class="recovery-status-feed__body<?= !empty($item['mono']) ? ' recovery-status-feed__body--mono' : '' ?>">
                        <?php if (!empty($item['bodyIsHtml'])): ?>
                        <?= $item['body'] ?>
                        <?php else: ?>
                        <?= \nl2br(\htmlspecialchars($item['body'], ENT_QUOTES, 'UTF-8'), false) ?>
                        <?php endif; ?>
                    </div>
                </div>
            </li>
            <?php endforeach; ?>
        </ul>
    </section>
    <?php
}

/**
 * Warnt vor kaputten application-Zeilen (packageID 0 → Frontend/ACP down).
 */
function recoveryRenderBrokenApplicationsAlert(
    \wcf\system\database\Database $db,
    int $wcfN,
    string $authHash,
    bool $compact = false
): bool {
    $broken = recoveryFindBrokenApplicationRows($db, $wcfN);
    if ($broken === []) {
        return false;
    }

    $repairUrl = recoveryBuildModeUrl(RECOVERY_MODE_PACKAGE_LIST_REPAIR, $authHash);
    $items = '';
    foreach (\array_slice($broken, 0, 6) as $row) {
        $items .= '<li><code>' . \htmlspecialchars((string) $row['application'], ENT_QUOTES, 'UTF-8')
            . '</code> — packageID <strong>' . (int) $row['packageID'] . '</strong></li>';
    }
    if (\count($broken) > 6) {
        $items .= '<li><em>… und ' . (\count($broken) - 6) . ' weitere</em></li>';
    }

    $body = '<p><strong>Symptom:</strong> Frontend/ACP zeigt '
        . '<code>application identified by package id \'0\' is unknown</code> oder die Paketliste ist leer.</p>';
    if (!$compact) {
        $body .= '<p>Ursache: verwaiste oder ungültige Einträge in <code>wcf' . (int) $wcfN
            . '_application</code> — oft nach partieller Deinstallation.</p>';
    }
    $body .= '<ul class="recovery-step-list">' . $items . '</ul>'
        . '<p><a href="' . \htmlspecialchars($repairUrl, ENT_QUOTES, 'UTF-8') . '" class="button buttonPrimary">'
        . recoveryFaIcon(16, 'list-check') . ' Paketliste reparieren</a></p>';

    recoveryRenderAlert('error', $body, 'Kritischer DB-Fehler — Frontend blockiert', true);

    return true;
}

/**
 * Text-Link-Submit ohne WCF-Klasse buttonTextLink (vermeidet Konflikt mit .button min-height/padding).
 *
 * @param array<string, string> $attrs Zusätzliche HTML-Attribute (z. B. data-recovery-loading)
 */
function recoveryRenderTextLinkSubmit(string $label, string $formId, array $attrs = []): string
{
    $attrHtml = ' form="' . \htmlspecialchars($formId) . '"';
    foreach ($attrs as $name => $value) {
        $attrHtml .= ' ' . \htmlspecialchars((string) $name) . '="' . \htmlspecialchars((string) $value) . '"';
    }

    return '<button type="submit" class="recovery-text-link-btn"' . $attrHtml . '>'
        . \htmlspecialchars($label) . '</button>';
}

/**
 * Sekundärer Submit in der Action-Bar (WCF button buttonSmall, sichtbarer Button-Rahmen).
 *
 * @param array<string, string> $attrs Zusätzliche HTML-Attribute (z. B. data-recovery-loading)
 */
function recoveryRenderSecondarySubmit(string $label, string $formId, array $attrs = []): string
{
    $attrHtml = ' form="' . \htmlspecialchars($formId) . '"';
    foreach ($attrs as $name => $value) {
        $attrHtml .= ' ' . \htmlspecialchars((string) $name) . '="' . \htmlspecialchars((string) $value) . '"';
    }

    return '<button type="submit" class="button buttonSmall"' . $attrHtml . '>'
        . \htmlspecialchars($label) . '</button>';
}

/**
 * Sekundäre Navigation in der Action-Bar (outline-ähnlicher ACP-Button, nicht Text-Link).
 */
function recoveryRenderSecondaryActionLink(string $href, string $label): string
{
    return '<a href="' . \htmlspecialchars($href) . '" class="button buttonSmall">'
        . \htmlspecialchars($label) . '</a>';
}

/**
 * @param list<string> $actions Raw HTML (buttons/links/forms)
 */
function recoveryRenderActionBar(array $actions, string $extraClass = ''): void
{
    if ($actions === []) {
        return;
    }
    $class = recoveryUsesNativeAcpUi()
        ? 'formSubmit'
        : 'formSubmit recovery-formSubmit--center recovery-action-bar';
    if ($extraClass !== '' && !recoveryUsesNativeAcpUi()) {
        $class .= ' ' . $extraClass;
    }
    echo '<div class="' . \htmlspecialchars($class) . '">';
    foreach ($actions as $action) {
        $trimmed = trim($action);
        if ($trimmed === '') {
            continue;
        }
        if (preg_match('/<button\b[^>]*>\s*<\/button>/i', $trimmed)) {
            continue;
        }
        echo $action;
    }
    echo '</div>';
}

/**
 * @param array{open?: bool, compact?: bool, status?: string, statusClass?: string} $opts
 */
function recoveryRenderPanelStart(string $summaryHtml, array $opts = []): void
{
    if (recoveryUsesNativeAcpUi()) {
        $title = (string) ($opts['title'] ?? \trim(\strip_tags($summaryHtml)));
        $desc = (string) ($opts['description'] ?? '');
        recoveryRenderAcpSectionStart($title, $desc, !empty($opts['descriptionHtml']));

        return;
    }

    $open = !empty($opts['open']) ? ' open' : '';
    $compact = !empty($opts['compact']) ? ' recovery-panel--compact' : '';
    $status = (string) ($opts['status'] ?? '');
    $statusClass = (string) ($opts['statusClass'] ?? 'recovery-panel__status');
    ?>
    <details class="recovery-panel<?= $compact ?>"<?= $open ?>>
        <summary>
            <?= $summaryHtml ?>
            <?php if ($status !== ''): ?>
            <span class="<?= \htmlspecialchars($statusClass) ?>"><?= \htmlspecialchars($status) ?></span>
            <?php endif; ?>
        </summary>
        <div class="recovery-panel__body">
    <?php
}

function recoveryRenderPanelEnd(): void
{
    if (recoveryUsesNativeAcpUi()) {
        recoveryRenderAcpSectionEnd();

        return;
    }
    ?>
        </div>
    </details>
    <?php
}

/**
 * @param list<array{title: string, body?: string, bodyHtml?: string}> $steps
 */
function recoveryRenderStepList(array $steps, bool $ordered = true): void
{
    $tag = $ordered ? 'ol' : 'ul';
    $class = 'recovery-step-list' . ($ordered ? ' recovery-step-list--ordered' : '');
    echo '<' . $tag . ' class="' . $class . '">';
    foreach ($steps as $step) {
        echo '<li class="recovery-step-list__item">';
        echo '<strong class="recovery-step-list__title">' . \htmlspecialchars((string) $step['title']) . '</strong>';
        if (!empty($step['bodyHtml'])) {
            echo '<span class="recovery-step-list__body">' . $step['bodyHtml'] . '</span>';
        } elseif (!empty($step['body'])) {
            echo '<span class="recovery-step-list__body">' . \htmlspecialchars((string) $step['body']) . '</span>';
        }
        echo '</li>';
    }
    echo '</' . $tag . '>';
}

/**
 * Kompakter Workflow-Status: einzeilige OK-Schritte + Aktionsleiste (ohne aufklappbare Panel-Header).
 *
 * @param list<array{label: string, status: 'ok'|'warn'|'pending', statusText?: string}> $checkItems
 * @param list<string> $actions Raw HTML (Buttons/Forms)
 */
function recoveryRenderWorkflowStatusBlock(
    array $checkItems,
    array $actions = [],
    ?string $hint = null,
    ?string $successMessage = null,
    string $title = 'Workflow-Status'
): void {
    if ($checkItems === [] && $actions === [] && $hint === null && $successMessage === null) {
        return;
    }

    if (recoveryUsesNativeAcpUi() && $checkItems !== []) {
        recoveryRenderAcpSectionStart($title);
        ?>
        <table class="table tableList">
            <thead>
                <tr>
                    <th class="columnIcon" aria-label="Status"><span class="silent">Status</span></th>
                    <th>Schritt</th>
                    <th>Ergebnis</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($checkItems as $item): ?>
            <?php
            $status = (string) ($item['status'] ?? 'pending');
            $statusText = (string) ($item['statusText'] ?? '');
            $icon = match ($status) {
                'ok' => recoveryFaIcon(16, 'circle-check'),
                'warn' => recoveryFaIcon(16, 'triangle-exclamation'),
                default => recoveryFaIcon(16, 'circle', true),
            };
            ?>
                <tr>
                    <td class="columnIcon"><?= $icon ?></td>
                    <td><?= \htmlspecialchars((string) $item['label']) ?></td>
                    <td><?= \htmlspecialchars($statusText) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php
        recoveryRenderAcpSectionEnd();
        if ($successMessage !== null && $successMessage !== '') {
            recoveryRenderAlert('success', $successMessage);
        }
        if ($hint !== null && $hint !== '') {
            recoveryRenderAlert('info', $hint, null, true);
        }
        if ($actions !== []) {
            echo '<div class="formSubmit">';
            foreach ($actions as $action) {
                echo $action;
            }
            echo '</div>';
        }

        return;
    }

    ?>
    <div class="recovery-workflow-block">
        <?php if ($checkItems !== []): ?>
        <p class="recovery-workflow-block__title"><?= \htmlspecialchars($title) ?></p>
        <ul class="recovery-workflow-checklist">
            <?php foreach ($checkItems as $item): ?>
            <?php
            $status = (string) ($item['status'] ?? 'pending');
            $statusText = (string) ($item['statusText'] ?? '');
            $icon = match ($status) {
                'ok' => recoveryFaIcon(16, 'circle-check'),
                'warn' => recoveryFaIcon(16, 'triangle-exclamation'),
                default => recoveryFaIcon(16, 'circle', true),
            };
            ?>
            <li class="recovery-workflow-checklist__item recovery-workflow-checklist__item--<?= \htmlspecialchars($status) ?>">
                <span class="recovery-workflow-checklist__icon"><?= $icon ?></span>
                <span class="recovery-workflow-checklist__label"><?= \htmlspecialchars((string) $item['label']) ?></span>
                <?php if ($statusText !== ''): ?>
                <span class="recovery-workflow-checklist__status"><?= \htmlspecialchars($statusText) ?></span>
                <?php endif; ?>
            </li>
            <?php endforeach; ?>
        </ul>
        <?php endif; ?>
        <?php if ($successMessage !== null && $successMessage !== ''): ?>
        <p class="success recovery-workflow-block__success"><?= \htmlspecialchars($successMessage) ?></p>
        <?php endif; ?>
        <?php if ($hint !== null && $hint !== ''): ?>
        <p class="recovery-workflow-block__hint"><?= $hint ?></p>
        <?php endif; ?>
        <?php if ($actions !== []): ?>
        <div class="recovery-workflow-block__actions formSubmit recovery-formSubmit--center">
            <?php foreach ($actions as $action): ?>
            <?= $action ?>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
    <?php
}

/**
 * @param list<array{key?: string, title: string, why: string, recommended?: bool, required?: bool}> $steps
 */
function recoveryRenderRecommendationSteps(array $steps): void
{
    if ($steps === []) {
        return;
    }
    echo '<p class="recovery-rec-steps-intro"><strong>Empfohlene Reihenfolge im Plan:</strong></p>';
    echo '<ol class="recovery-rec-steps">';
    foreach ($steps as $step) {
        $badges = [];
        if (!empty($step['required'])) {
            $badges[] = '<span class="badge badgeRed">Wichtig</span>';
        } elseif (!empty($step['recommended'])) {
            $badges[] = '<span class="badge badgeYellow">Empfohlen</span>';
        }
        echo '<li class="recovery-rec-steps__item">';
        if ($badges !== []) {
            echo '<span class="recovery-rec-steps__badges">' . \implode(' ', $badges) . '</span> ';
        }
        echo '<strong>' . \htmlspecialchars((string) $step['title']) . '</strong>';
        echo ' <span class="recovery-rec-steps__why">— ' . (string) ($step['why'] ?? '') . '</span>';
        echo '</li>';
    }
    echo '</ol>';
}

/**
 * Kompakter Hero für Backup-Schritte (Wizard Schritt 2, Modus Datensicherung).
 */
function recoveryRenderBackupStepHero(string $title, string $subtitle, string $icon = 'database'): void
{
    if (recoveryUsesNativeAcpUi()) {
        return;
    }
    ?>
    <header class="recovery-intake-hero recovery-intake-hero--compact">
        <span class="recovery-intake-hero__icon" aria-hidden="true"><?= recoveryFaIcon(28, $icon) ?></span>
        <h1><?= \htmlspecialchars($title) ?></h1>
        <p class="subtitle"><?= $subtitle ?></p>
    </header>
    <?php
}

/**
 * Backup-Schritt: Hero, Karten, optional Hinweis + Action-Bar (Wizard + Datensicherung).
 *
 * @param array{
 *     heroTitle?: string,
 *     heroSubtitle?: string,
 *     heroIcon?: string,
 *     showHero?: bool,
 *     includeBoth?: bool,
 *     formTarget?: string,
 *     ajaxBackup?: bool,
 *     dryRunFormId?: string,
 *     actions?: list<string>,
 *     skipHint?: string|null,
 *     beforeActionsHtml?: string,
 * } $options
 */
function recoveryRenderBackupStepSection(string $authHash, string $backupUrl, array $options = []): void
{
    $showHero = $options['showHero'] ?? true;
    if ($showHero) {
        recoveryRenderBackupStepHero(
            (string) ($options['heroTitle'] ?? 'Datensicherung'),
            (string) ($options['heroSubtitle'] ?? 'Datenbank und Dateisystem sichern, bevor Sie Reparaturen ausführen.'),
            (string) ($options['heroIcon'] ?? 'database')
        );
    }

    recoveryRenderBackupChoiceCards($authHash, $backupUrl, [
        'includeBoth' => $options['includeBoth'] ?? true,
        'formTarget' => (string) ($options['formTarget'] ?? ''),
        'ajaxBackup' => !empty($options['ajaxBackup']),
        'dryRunFormId' => (string) ($options['dryRunFormId'] ?? 'recovery-backup-dryrun'),
    ]);

    $skipHint = $options['skipHint'] ?? null;
    if ($skipHint !== null && $skipHint !== '') {
        echo '<p class="recovery-package-step-hint">' . \htmlspecialchars((string) $skipHint) . '</p>';
    }

    if (!empty($options['beforeActionsHtml'])) {
        echo (string) $options['beforeActionsHtml'];
    }

    $actions = $options['actions'] ?? [];
    if ($actions !== []) {
        recoveryRenderActionBar($actions);
    }
}

/**
 * Backup-Auswahl als Szenario-Karten (gleiches System wie Startseite).
 *
 * @param array{
 *     includeBoth?: bool,
 *     formTarget?: string,
 *     ajaxBackup?: bool,
 *     dryRunFormId?: string,
 * } $options
 */
function recoveryRenderBackupChoiceCards(string $authHash, string $backupUrl, array $options = []): void
{
    $includeBoth = $options['includeBoth'] ?? true;
    $formTarget = (string) ($options['formTarget'] ?? '');
    $ajaxBackup = !empty($options['ajaxBackup']);
    $dryRunFormId = (string) ($options['dryRunFormId'] ?? 'recovery-backup-dryrun');
    $targetAttr = $formTarget !== '' ? ' target="' . \htmlspecialchars($formTarget) . '"' : '';
    $ajaxAttr = $ajaxBackup ? ' data-recovery-ajax-backup="1"' : '';

    $cards = [
        [
            'action' => 'db',
            'icon' => 'database',
            'title' => 'Datenbank',
            'desc' => 'SQL-Dump der WoltLab-Datenbank (PDO oder mysqldump).',
            'btn' => 'DB-Backup erstellen',
            'loading' => 'Datenbank-Backup läuft …',
            'badge' => null,
        ],
        [
            'action' => 'files',
            'icon' => 'folder',
            'title' => 'Dateisystem',
            'desc' => 'Tarball des WoltLab-Hauptverzeichnisses — kann einige Minuten dauern.',
            'btn' => 'Datei-Backup erstellen',
            'loading' => 'Dateisystem-Backup läuft …',
            'confirm' => 'Großes tar.gz — Fortfahren?',
            'confirmTitle' => 'Dateisystem-Backup',
            'badge' => null,
        ],
    ];
    if ($includeBoth) {
        $cards[] = [
            'action' => 'both',
            'icon' => 'layer-group',
            'title' => 'Beides auf einmal',
            'desc' => 'Datenbank und Dateisystem nacheinander — ein Klick, kombiniertes Ergebnis.',
            'btn' => 'Beides erstellen',
            'loading' => 'Vollständiges Backup läuft (DB, dann Dateien) …',
            'confirm' => 'Datenbank- und Dateisystem-Backup nacheinander starten?',
            'confirmTitle' => 'Vollständiges Backup',
            'badge' => 'Empfohlen',
        ];
    }

    if (recoveryUsesNativeAcpUi()) {
        echo '<div class="acpDashboard recovery-acp-dashboard--actions">';
        foreach ($cards as $card) {
            ?>
        <div class="acpDashboardBox">
            <h2 class="acpDashboardBox__title">
                <?= \htmlspecialchars((string) $card['title']) ?>
                <?php if (!empty($card['badge'])): ?>
                <span class="badge green small"><?= \htmlspecialchars((string) $card['badge']) ?></span>
                <?php endif; ?>
            </h2>
            <div class="acpDashboardBox__content">
                <p><?= $card['desc'] ?></p>
                <form method="POST" action="<?= \htmlspecialchars($backupUrl) ?>"<?= $targetAttr ?>
                    data-recovery-loading="<?= \htmlspecialchars((string) $card['loading']) ?>"<?= $ajaxAttr ?>
                    <?php if (!empty($card['confirm'])): ?>
                    data-recovery-confirm="<?= \htmlspecialchars((string) $card['confirm']) ?>"
                    data-recovery-confirm-title="<?= \htmlspecialchars((string) $card['confirmTitle']) ?>"
                    <?php endif; ?>>
                    <?php recoveryRenderFormModeHiddenFields(RECOVERY_MODE_BACKUP_GUIDE, $authHash); ?>
                    <input type="hidden" name="recovery_backup_action" value="<?= \htmlspecialchars((string) $card['action']) ?>">
                    <div class="formSubmit">
                        <button type="submit" class="button buttonPrimary"><?= \htmlspecialchars((string) $card['btn']) ?></button>
                    </div>
                </form>
            </div>
        </div>
            <?php
        }
        echo '</div>';
    } else {
        $gridClass = 'recovery-scenario-grid recovery-scenario-grid--backup'
            . ($includeBoth ? ' recovery-scenario-grid--3' : ' recovery-scenario-grid--2');
        ?>
    <div class="<?= $gridClass ?>">
    <?php foreach ($cards as $card): ?>
        <div class="recovery-scenario-card recovery-backup-card">
            <?php if (!empty($card['badge'])): ?>
            <span class="recovery-scenario-card__badge recovery-scenario-card__badge--primary"><?= \htmlspecialchars((string) $card['badge']) ?></span>
            <?php endif; ?>
            <span class="recovery-scenario-icon" aria-hidden="true"><?= recoveryFaIcon(32, (string) $card['icon']) ?></span>
            <h2><?= \htmlspecialchars((string) $card['title']) ?></h2>
            <p><?= $card['desc'] ?></p>
            <div class="recovery-backup-card__actions">
            <form method="POST" action="<?= \htmlspecialchars($backupUrl) ?>"<?= $targetAttr ?>
                data-recovery-loading="<?= \htmlspecialchars((string) $card['loading']) ?>"<?= $ajaxAttr ?>
                <?php if (!empty($card['confirm'])): ?>
                data-recovery-confirm="<?= \htmlspecialchars((string) $card['confirm']) ?>"
                data-recovery-confirm-title="<?= \htmlspecialchars((string) $card['confirmTitle']) ?>"
                <?php endif; ?>>
                <?php recoveryRenderFormModeHiddenFields(RECOVERY_MODE_BACKUP_GUIDE, $authHash); ?>
                <input type="hidden" name="recovery_backup_action" value="<?= \htmlspecialchars((string) $card['action']) ?>">
                <button type="submit" class="button buttonPrimary"><?= recoveryFaIcon(16, (string) $card['icon']) ?> <?= \htmlspecialchars((string) $card['btn']) ?></button>
            </form>
            </div>
        </div>
    <?php endforeach; ?>
    </div>
        <?php
    }
    if ($dryRunFormId !== '') {
        if (recoveryUsesNativeAcpUi()) {
            ?>
    <div class="info">
        <label for="recoveryBackupDryRun">
            <input type="checkbox" name="backup_dry_run" value="1" id="recoveryBackupDryRun" form="<?= \htmlspecialchars($dryRunFormId) ?>">
            <strong>Dry-Run:</strong> Nur Vorschau, kein Schreiben auf den Server.
        </label>
    </div>
    <form id="<?= \htmlspecialchars($dryRunFormId) ?>" style="display:none" aria-hidden="true"></form>
            <?php
        } else {
            ?>
    <div class="recovery-dryrun-panel recovery-dryrun-panel--compact">
        <label class="recovery-checkbox-label" for="recoveryBackupDryRun">
            <input type="checkbox" name="backup_dry_run" value="1" id="recoveryBackupDryRun" form="<?= \htmlspecialchars($dryRunFormId) ?>">
            <strong>Dry-Run:</strong> Nur Vorschau, kein Schreiben auf den Server.
        </label>
    </div>
    <form id="<?= \htmlspecialchars($dryRunFormId) ?>" style="display:none" aria-hidden="true"></form>
            <?php
        }
    }
}

/**
 * Wizard Run/Done: Status als recovery-alert (statt recovery-run-hero / recovery-done-hero).
 *
 * @param 'run'|'done' $context
 */
function recoveryRenderWizardCompletionAlert(
    string $context,
    bool $dryRun,
    bool $hasPostStill,
    bool $wizardSuccess = false,
    string $packageLabel = '',
    string $scopeApp = '',
    int $executedStepCount = 0,
    ?string $firstStillConstant = null
): void {
    if ($context === 'run') {
        if ($dryRun) {
            $type = 'warning';
            $title = 'Dry-Run abgeschlossen';
            $body = 'Es wurden keine Änderungen am Server vorgenommen.';
        } elseif ($hasPostStill) {
            $type = 'warning';
            $title = 'Ausführung beendet';
            $body = 'Die gewählten Schritte wurden ausgeführt; im WoltLab-Log erscheinen weiterhin fehlende Konstanten.';
        } else {
            $type = 'success';
            $title = 'Ausführung abgeschlossen';
            $body = 'Die gewählten Reparatur-Schritte wurden auf dem Server ausgeführt.';
        }
    } elseif ($wizardSuccess) {
        $type = 'success';
        $title = 'Wizard abgeschlossen';
        $body = 'Post-Check: keine gemeldeten Undefined-constant-Fehler im aktuellen Log. Bitte ACP testen.';
    } elseif ($hasPostStill) {
        $type = 'warning';
        $title = 'Nicht vollständig behoben';
        $const = $firstStillConstant !== null && $firstStillConstant !== ''
            ? ' <code>' . \htmlspecialchars($firstStillConstant, ENT_QUOTES, 'UTF-8') . '</code>'
            : '';
        $body = 'Im WoltLab-Log erscheinen weiterhin fehlende Konstanten (z.&nbsp;B.' . $const . ').';
    } else {
        $type = 'info';
        $title = 'Wizard abgeschlossen';
        $body = 'Prüfen Sie den ACP. Bei Erfolg Plugin deinstallieren und Recovery Tool vom Server entfernen.';
    }

    $meta = [];
    if ($context === 'run') {
        if ($packageLabel !== '') {
            $meta[] = 'Paket <code>' . \htmlspecialchars($packageLabel, ENT_QUOTES, 'UTF-8') . '</code>';
            if ($scopeApp !== '') {
                $meta[] = 'App <code>' . \htmlspecialchars($scopeApp, ENT_QUOTES, 'UTF-8') . '</code>';
            }
        }
        if ($executedStepCount > 0) {
            $meta[] = $executedStepCount . ' Schritte ausgeführt';
        }
        if ($dryRun) {
            $meta[] = '<span class="badge badgeYellow">Dry-Run</span>';
        }
    }

    if ($meta !== []) {
        $body .= '<p class="recovery-alert__meta">' . \implode(' · ', $meta) . '</p>';
    }

    recoveryRenderAlert($type, $body, $title, true);
}

/**
 * Wizard Run/Done: Kennzahlen-Tabelle (recovery-summary-table, wie „Letzte Ausführung“).
 *
 * @param list<array{label: string, hint: string, value: string, badge?: string, boolean?: bool}> $rows
 */
function recoveryRenderWizardSummaryTable(
    array $rows,
    string $title,
    string $description
): void {
    if ($rows === []) {
        return;
    }

    if (recoveryUsesNativeAcpUi()) {
        recoveryRenderAcpSectionStart($title, $description);
    } else {
        ?>
    <div class="recovery-run-block recovery-wizard-summary">
        <h2 class="recovery-run-block__title"><?= recoveryFaIcon(16, 'chart-simple') ?> <?= \htmlspecialchars($title) ?></h2>
        <p class="recovery-run-block__desc"><?= \htmlspecialchars($description) ?></p>
        <?php
    }
    ?>
        <table class="<?= recoveryUsesNativeAcpUi() ? 'table tableList' : 'tableList recovery-table-list recovery-summary-table' ?>">
            <colgroup>
                <col class="recovery-summary-col-label">
                <col class="recovery-summary-col-value">
            </colgroup>
            <thead>
                <tr>
                    <th class="recovery-summary-th-label">Maßnahme</th>
                    <th class="recovery-summary-th-value">Ergebnis</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <td class="recovery-summary-label">
                        <strong><?= \htmlspecialchars((string) $row['label']) ?></strong>
                        <span class="recovery-summary-hint"><?= \htmlspecialchars((string) $row['hint']) ?></span>
                    </td>
                    <td class="columnText recovery-summary-value">
                        <?php if (!empty($row['boolean'])): ?>
                            <?php $yes = ($row['value'] ?? '') === 'ja'; ?>
                            <span class="badge <?= $yes ? 'badgeGreen' : 'badgeYellow' ?>"><?= \htmlspecialchars((string) $row['value']) ?></span>
                        <?php elseif (!empty($row['badge'])): ?>
                            <span class="badge <?= \htmlspecialchars((string) $row['badge']) ?>"><?= \htmlspecialchars((string) $row['value']) ?></span>
                        <?php else: ?>
                            <span class="recovery-summary-count"><?= \htmlspecialchars((string) $row['value']) ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php
    if (recoveryUsesNativeAcpUi()) {
        recoveryRenderAcpSectionEnd();
    } else {
        echo '</div>';
    }
}
