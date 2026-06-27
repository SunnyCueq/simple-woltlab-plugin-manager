<?php
/** Recovery mode: package_list_repair — included by lib/Recovery/router.php */
declare(strict_types=1);

if ($mode === RECOVERY_MODE_PACKAGE_LIST_REPAIR) {
    $pkgListUrl = recoveryBuildModeUrl(RECOVERY_MODE_PACKAGE_LIST_REPAIR, $authHash);
    $brokenApps = recoveryFindBrokenApplicationRows($db, WCF_N);
?>
    <?php
        recoveryRenderBackupStepHero(
            'Paketliste reparieren',
            'Entfernt verwaiste DB-Einträge, die Frontend, ACP-Paketliste oder Deinstallation blockieren.',
            'list-check'
        );
    ?>

<?php
    $orphanSql = recoveryGenerateOrphanRepairSql(WCF_N);

    if (!isset($_POST['confirm_repair'])) {
        if ($brokenApps !== []) {
            recoveryRenderBrokenApplicationsAlert($db, WCF_N, $authHash);
        }
        recoveryRenderAlert(
            'warning',
            '<strong>Typische Symptome:</strong><br>'
            . '• Frontend: <code>application identified by package id \'0\' is unknown</code><br>'
            . '• ACP-Paketliste: <code>Attempt to read property "packageID" on null</code><br>'
            . '• Deinstallation: <code>assert($package !== null)</code> bei hängender Queue<br><br>'
            . '<strong>Bereinigt:</strong> ungültige/verwaiste <code>application</code>-Zeilen, '
            . 'Installationsqueue, Requirements, Exclusions und File-Logs.',
            'Was wird bereinigt?',
            true
        );
        if ($brokenApps !== []) {
            recoveryRenderPanelStart('Betroffene Application-Einträge (' . \count($brokenApps) . ')', ['compact' => true]);
            echo '<ul class="recovery-step-list">';
            foreach ($brokenApps as $row) {
                echo '<li><code>' . \htmlspecialchars((string) $row['application']) . '</code>'
                    . ' — packageID <strong>' . (int) $row['packageID'] . '</strong></li>';
            }
            echo '</ul>';
            recoveryRenderPanelEnd();
        }
        recoveryRenderPanelStart('SQL für phpMyAdmin / manuelle Ausführung (WCF_N=' . (int) WCF_N . ')', ['compact' => true]);
        echo '<pre class="recoveryLog recovery-cmd-block">' . \htmlspecialchars($orphanSql) . '</pre>';
        recoveryRenderPanelEnd();
?>
    <form method="POST">
        <?php recoveryRenderFormModeHiddenFields(RECOVERY_MODE_PACKAGE_LIST_REPAIR, $authHash); ?>
        <input type="hidden" name="confirm_repair" value="1">
        <?php
        recoveryRenderActionBar([
            '<button type="submit" class="button buttonPrimary">' . recoveryFaIcon(16, 'list-check') . ' Verwaiste Einträge jetzt bereinigen</button>',
            '<a href="' . \htmlspecialchars(recoveryHomeUrl($authHash)) . '" class="button">' . recoveryFaIcon(16, 'house') . ' Zurück zum Start</a>',
        ]);
        ?>
    </form>
<?php
    } else {
        try {
            $result = recoveryRepairOrphanedPackageReferences($db, WCF_N);
            $deletedFiles = clearCompiledTemplates();
            $optionFbLog = [];
            recoveryEnsureOptionConstantFallbacks($db, WCF_N, $optionFbLog);

            $logBody = '';
            foreach ($result['log'] as $entry) {
                $logBody .= '• ' . \htmlspecialchars($entry) . '<br>';
            }
            $logBody .= '<br>Cache-Dateien gelöscht: <strong>' . (int) $deletedFiles . '</strong>';
            foreach ($optionFbLog as $fbEntry) {
                $logBody .= '<br>' . \htmlspecialchars($fbEntry);
            }
            $remaining = recoveryFindBrokenApplicationRows($db, WCF_N);
            if ($remaining === []) {
                $logBody .= '<br><strong>Frontend/ACP sollte wieder erreichbar sein.</strong> Bitte Forum und ACP testen.';
            } else {
                $logBody .= '<br><span class="badge badgeYellow">Hinweis:</span> Es verbleiben '
                    . \count($remaining) . ' kaputte Application-Zeile(n) — Details im Protokoll prüfen.';
            }
            recoveryRenderAlert('success', $logBody, 'Paketliste-Reparatur abgeschlossen', true);
            recoveryRenderActionBar([
                '<a href="' . \htmlspecialchars($recoveryBaseUrl) . '" class="button buttonPrimary" target="_blank" rel="noopener">' . recoveryFaIcon(16, 'globe') . ' Forum testen</a>',
                '<a href="' . \htmlspecialchars($recoveryBaseUrl . 'acp/') . '" class="button" target="_blank" rel="noopener">' . recoveryFaIcon(16, 'gauge-high') . ' ACP testen</a>',
                '<a href="' . \htmlspecialchars(recoveryHomeUrl($authHash)) . '" class="button">' . recoveryFaIcon(16, 'house') . ' Zurück zum Start</a>',
            ]);
        } catch (\Throwable $e) {
            recoveryRenderAlert('error', recoveryFormatUserError($e), 'Fehler');
            recoveryRenderExceptionDetails($e);
            recoveryRenderActionBar([
                '<a href="' . \htmlspecialchars($pkgListUrl) . '" class="button">' . recoveryFaIcon(16, 'arrow-left') . ' Zurück</a>',
            ]);
        }
    }
}
