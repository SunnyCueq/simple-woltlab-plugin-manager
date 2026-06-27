<?php
/** Recovery mode: selection — included by lib/Recovery/router.php */
declare(strict_types=1);

if ($mode === RECOVERY_MODE_SELECTION) {
    recoveryRenderFlashSnackbarFromQuery();
    $wizardStartUrl = recoveryBuildModeUrl(RECOVERY_MODE_RECOVERY_WIZARD, $authHash);
    $adminUrl = recoveryBuildModeUrl(RECOVERY_MODE_USER_MANAGEMENT, $authHash);
    $uninstallUrl = recoveryBuildModeUrl(RECOVERY_MODE_PLUGIN_UNINSTALL, $authHash);
    $expertOpen = !empty($_GET['expert']);
    $sysinfoOpen = !empty($_GET['sysinfo']);
    $acpUrl = $recoveryBaseUrl . 'acp/';
    $criticalIssues = recoveryDetectCriticalDbIssues($wcfDirMain, $db, WCF_N);
    $showEmergencyFix = $criticalIssues['offerFix'] && $emergencyFixedSession === null && $acpFixOutcome === null;
    $acpDoneActive = $acpFixOutcome !== null;
?>
    <?php recoveryRenderBrokenApplicationsAlert($db, WCF_N, $authHash, true); ?>

    <?php if (!recoveryUsesNativeAcpUi()): ?>
    <div class="recovery-content-stack">
    <?php endif; ?>

    <?php
    $startStatusItems = recoveryCollectStartPageStatusItems();
    if ($startStatusItems !== []) {
        $statusHeading = \count($startStatusItems) > 1 ? 'Aktueller Status' : null;
        recoveryRenderStatusFeed($startStatusItems, $statusHeading);
    }
    ?>

    <?php if (recoveryUsesNativeAcpUi()): ?>
    <?php
        recoveryRenderAcpSectionStart(
            'System-Info & Kurzstatus',
            'Werte für Support kopieren. Recovery-URL absichtlich ohne geheimes Token (?t=…).'
        );
        recoveryRenderRuntimeInfoPanel($authHash, $recoveryBaseUrl, true, true);
        recoveryRenderAcpSectionEnd();
    ?>
    <?php else: ?>
    <details class="recovery-panel recovery-panel--sysinfo recovery-license-panel"<?= $sysinfoOpen ? ' open' : '' ?>>
        <summary>
            <span class="recovery-license-panel__summary-title"><?= recoveryFaIcon(16, 'server') ?> System-Info &amp; Kurzstatus</span>
            <span class="badge badgeGreen recovery-license-panel__summary-badge">Bereit</span>
        </summary>
        <div class="recovery-panel__body recovery-panel__body--sysinfo">
            <?php recoveryRenderRuntimeInfoPanel($authHash, $recoveryBaseUrl, true, true); ?>
        </div>
    </details>
    <?php endif; ?>

    <?php if ($emergencyAcpResult !== null && !empty($emergencyAcpResult['error'])): ?>
    <?php recoveryRenderAlert(
        'error',
        (string) $emergencyAcpResult['error'],
        'Notfall-Reparatur fehlgeschlagen'
    ); ?>
    <?php elseif ($acpFixOutcome !== null): ?>
    <?php
        recoveryRenderAcpRecoveredGuidance(
            $acpFixOutcome['result'],
            $acpUrl,
            $uninstallUrl,
            $authHash,
            $acpFixOutcome['log']
        );
    ?>
    <?php endif; ?>

    <?php if ($showEmergencyFix): ?>
    <?php
        $emergencyList = '';
        if ($criticalIssues['brokenApplications'] !== []) {
            $emergencyList .= '<li><code>application identified by package id \'0\' is unknown</code> ('
                . \count($criticalIssues['brokenApplications']) . ' kaputte Application-Zeile(n))</li>';
        }
        if ($criticalIssues['orphanedListeners'] !== []) {
            $emergencyList .= '<li><code>ClassNotFoundException</code> / fehlende Event-Listener-Klasse ('
                . \count($criticalIssues['orphanedListeners']) . ' DB-Eintrag/Einträge)</li>';
        }
        if ($criticalIssues['logClasses'] !== [] && $criticalIssues['orphanedListeners'] === []) {
            $emergencyList .= '<li>Fehlende Klasse im Log: <code>'
                . \htmlspecialchars($criticalIssues['logClasses'][0], ENT_QUOTES, 'UTF-8') . '</code></li>';
        }
        $listClass = recoveryUsesNativeAcpUi() ? '' : ' class="recovery-step-list"';
        $emergencyBody = '<p><strong>Automatisch erkannt</strong> — typische Ursachen nach Plugin-Deinstallation:</p>'
            . '<ul' . $listClass . '>' . $emergencyList . '</ul>'
            . '<p>Ein Klick repariert <strong>Applications (packageID 0)</strong>, entfernt tote '
            . '<code>event_listener</code>-Zeilen, deaktiviert Bootstrap-<code>register()</code> (mit Backup) und leert den Cache.</p>';
        ?>
    <section class="section" id="recovery-core-repair">
        <header class="sectionHeader">
            <h2 class="sectionTitle">Notfall-Reparatur</h2>
            <p class="sectionDescription">ACP oder Frontend blockiert — automatisch erkannte Ursachen beheben.</p>
        </header>
        <?php recoveryRenderAlert('warning', $emergencyBody, 'Sofort: ACP/Frontend blockiert', true); ?>
        <form method="POST" action="<?= \htmlspecialchars(recoveryBuildHomeUrl($authHash)) ?>"
            data-recovery-loading="Notfall-Reparatur läuft (Bootstrap, DB, Cache) …"
            data-recovery-confirm="Bootstrap-Register werden auskommentiert (mit Backup), DB-Listener gelöscht, Cache geleert. Fortfahren?"
            data-recovery-confirm-title="ACP ClassNotFound beheben"
            data-recovery-confirm-ok="Jetzt beheben">
            <?php recoveryRenderFormModeHiddenFields(RECOVERY_MODE_SELECTION, $authHash); ?>
            <input type="hidden" name="emergency_acp_fix" value="1">
            <div class="formSubmit">
                <button type="submit" class="button buttonPrimary"><?= recoveryFaIcon(16, 'bolt') ?> ACP ClassNotFound jetzt beheben</button>
            </div>
        </form>
    </section>
    <?php endif; ?>

    <?php if (!$acpDoneActive): ?>
    <?php if (recoveryUsesNativeAcpUi()): ?>
    <section class="section">
        <header class="sectionHeader">
            <h2 class="sectionTitle">Szenarien</h2>
            <p class="sectionDescription">Situation wählen — empfohlener Weg oder Admin-Zugang.</p>
        </header>
    <?php else: ?>
    <h2 class="recovery-scenario-heading">Szenarien</h2>
    <?php endif; ?>
    <?php if (recoveryUsesNativeAcpUi()): ?>
    <div class="acpDashboard">
        <div class="acpDashboardBox">
            <h2 class="acpDashboardBox__title">Plugin defekt / ACP nicht erreichbar</h2>
            <div class="acpDashboardBox__content">
                <p><span class="badge green small">Empfohlen</span></p>
                <p>
                    Wenn <code>/acp/</code> nicht lädt: Schritt für Schritt Diagnose, Reparatur, optional Dateien aus dem
                    Paket-Archiv. <strong>ACP läuft bereits?</strong> Dann <strong>Plugin entfernen</strong> in der linken Navigation.
                </p>
                <div class="formSubmit">
                    <a href="<?= \htmlspecialchars($wizardStartUrl) ?>" class="button buttonPrimary">Recovery-Wizard starten</a>
                </div>
            </div>
        </div>
        <div class="acpDashboardBox">
            <h2 class="acpDashboardBox__title">Admin-Zugang wiederherstellen</h2>
            <div class="acpDashboardBox__content">
                <p><span class="badge small">Admin</span></p>
                <p>
                    Passwort vergessen, kein Zugang zum ACP — Benutzerkonto und Berechtigungen direkt in der Datenbank anpassen.
                </p>
                <div class="formSubmit">
                    <a href="<?= \htmlspecialchars($adminUrl) ?>" class="button buttonPrimary">Admin-Konto</a>
                </div>
            </div>
        </div>
    </div>
    <?php else: ?>
    <div class="recovery-scenario-grid recovery-scenario-grid--2">
        <a href="<?= \htmlspecialchars($wizardStartUrl) ?>" class="recovery-scenario-card recovery-scenario-card--primary">
            <span class="recovery-scenario-card__badge recovery-scenario-card__badge--primary">Empfohlen</span>
            <span class="recovery-scenario-icon" aria-hidden="true"><?= recoveryFaIcon(32, 'route') ?></span>
            <h2>Plugin defekt / ACP nicht erreichbar</h2>
            <p>
                Wenn <code>/acp/</code> nicht lädt: Schritt für Schritt Diagnose, Reparatur, optional Dateien aus dem
                Paket-Archiv. <strong>ACP läuft bereits?</strong> Dann nicht den Wizard für „Dateien wiederherstellen“ —
                stattdessen <strong>Plugin entfernen</strong> unter „Weitere Experten-Modi“.
            </p>
            <span class="recovery-scenario-cta">Recovery-Wizard starten →</span>
        </a>

        <a href="<?= \htmlspecialchars($adminUrl) ?>" class="recovery-scenario-card">
            <span class="recovery-scenario-card__badge recovery-scenario-card__badge--admin">Admin</span>
            <span class="recovery-scenario-icon" aria-hidden="true"><?= recoveryFaIcon(32, 'user-shield') ?></span>
            <h2>Admin-Zugang wiederherstellen</h2>
            <p>
                Passwort vergessen, kein Zugang zum ACP, Administrator-Rechte nötig — Benutzerkonto und Berechtigungen
                direkt in der Datenbank anpassen.
            </p>
            <span class="recovery-scenario-cta">User Management →</span>
        </a>
    </div>
    <?php endif; ?>
    <?php if (recoveryUsesNativeAcpUi()): ?>
    </section>
    <?php endif; ?>

    <?php else: ?>
    <p class="recovery-acp-done-continue">
        <a href="<?= \htmlspecialchars(recoveryBuildHomeUrl($authHash)) ?>" class="button"><?= recoveryFaIcon(16, 'house') ?> Zur Startübersicht</a>
    </p>
    <?php endif; ?>

    <?php if (!$acpDoneActive): ?>
    <?php if (!recoveryUsesNativeAcpUi()): ?>
    <details class="recovery-panel recovery-expert-panel" id="recovery-expert-panel"<?= $expertOpen ? ' open' : '' ?>>
        <summary>
            <?= recoveryFaIcon(16, 'screwdriver-wrench') ?>
            Weitere Experten-Modi
        </summary>
        <div class="recovery-expert-body">
            <p class="recovery-expert-intro">
                Datensicherung, Plugin entfernen, Cache leeren sowie ACP Repair, Paketliste und Datei-Reparatur —
                Werkzeuge für gezielte Eingriffe, die nicht über die Szenario-Karten abgedeckt sind.
            </p>
            <div class="recovery-expert-modes">
                <?php recoveryRenderExpertModesGrid($authHash); ?>
            </div>
        </div>
    </details>
    <?php endif; ?>

    <?php recoveryRenderSecurityNotice(); ?>
    <?php endif; ?>

    <?php if (recoveryUsesNativeAcpUi()): ?>
    <section class="section">
        <header class="sectionHeader">
            <h2 class="sectionTitle">Fertig mit Recovery?</h2>
            <p class="sectionDescription">Wenn alles wieder funktioniert, Recovery Tool und Auth-Datei löschen.</p>
        </header>
        <div class="formSubmit">
            <a href="?action=cleanup&amp;t=<?= \htmlspecialchars($authHash) ?>" class="button"
                onclick="return confirm('ACHTUNG: Das Recovery Tool wird vollständig entfernt (Auth-Datei, recovery-tool/, log/recovery/, Cache, plugin-recovery-tool.php). Fortfahren?')">
                <?= recoveryFaIcon(16, 'xmark') ?> Recovery Tool vollständig entfernen
            </a>
        </div>
    </section>
    <?php else: ?>
    <section class="section recovery-cleanup-section">
        <header class="sectionHeader">
            <h2 class="sectionTitle"><?= recoveryFaIcon(24, 'triangle-exclamation') ?> Fertig mit Recovery?</h2>
            <p class="sectionDescription">Wenn alles wieder funktioniert, Recovery Tool und Auth-Datei löschen.</p>
        </header>
    </section>
    <?php
    recoveryRenderActionBar([
        '<a href="?action=cleanup&amp;t=' . \htmlspecialchars($authHash) . '" class="button" onclick="return confirm(\'ACHTUNG: Das Recovery Tool wird vollständig entfernt (Auth-Datei, recovery-tool/, log/recovery/, Cache, plugin-recovery-tool.php). Fortfahren?\')">'
        . recoveryFaIcon(16, 'xmark') . ' Recovery Tool vollständig entfernen</a>',
    ]);
    ?>
    <?php endif; ?>

    <?php if (!recoveryUsesNativeAcpUi()): ?>
    </div>
    <?php endif; ?>

<?php
}
