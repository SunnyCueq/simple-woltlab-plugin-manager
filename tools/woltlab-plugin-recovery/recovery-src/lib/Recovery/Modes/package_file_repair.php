<?php
/** Recovery mode: package_file_repair — included by lib/Recovery/router.php */
declare(strict_types=1);

if ($mode === RECOVERY_MODE_PACKAGE_FILE_REPAIR) {
    $fileRepairUrl = recoveryBuildModeUrl(RECOVERY_MODE_PACKAGE_FILE_REPAIR, $authHash);
    $wcfDir = \rtrim(WCF_DIR, '/\\') . \DIRECTORY_SEPARATOR;
    $liveMissing = recoveryFindMissingBootstrapClasses($wcfDir);
?>
    <h1>Plugin-Dateien reparieren</h1>
    <p class="subtitle">Fehlende PHP-Klassen (Bootstrap-Registrierung) aus hochgeladenem Paket wiederherstellen</p>

<?php
    if (recoveryWasPostTruncated()) {
        recoveryRenderPostTruncatedWarning();
    }

    if (isset($_POST['confirm_file_repair'])) {
        $repairLog = [];
        $extractDir = recoveryResolveTrustedExtractDir($authHash);
        if ($extractDir === null) {
            recoveryRenderAlert('error', 'Bitte erneut hochladen.', 'Kein gültiges Paket-Archiv in der Session');
        } else {
            $payload = recoveryExtractPackageInstructionTars($extractDir, $repairLog);
            if ($payload === null) {
                $body = '<strong>Paket konnte nicht ausgewertet werden.</strong><br>';
                foreach ($repairLog as $line) {
                    $body .= \htmlspecialchars($line) . '<br>';
                }
                recoveryRenderAlert('error', $body, null, true);
            } else {
                $toRestore = $liveMissing;
                if (isset($_POST['repair_classes']) && \is_array($_POST['repair_classes'])) {
                    $toRestore = [];
                    foreach ($_POST['repair_classes'] as $cn) {
                        $cn = \trim((string) $cn);
                        if ($cn !== '') {
                            $toRestore[] = $cn;
                        }
                    }
                }
                $copied = recoveryRepairMissingPluginFilesFromPayload($wcfDir, $payload, $toRestore, $repairLog);
                $deletedFiles = clearCompiledTemplates();
                $optionFbLog = [];
                recoveryEnsureOptionConstantFallbacks($db, WCF_N, $optionFbLog);
                recoveryCleanupUploadWorkspace();

                $logBody = 'Kopierte Dateien: <strong>' . \count($copied) . '</strong><br>';
                $logBody .= 'Cache-Dateien gelöscht: <strong>' . (int) $deletedFiles . '</strong><br><br>';
                foreach ($repairLog as $line) {
                    $logBody .= '• ' . \htmlspecialchars($line) . '<br>';
                }
                foreach ($optionFbLog as $fbEntry) {
                    $logBody .= \htmlspecialchars($fbEntry) . '<br>';
                }
                $logBody .= '<br><strong>Bitte ACP erneut testen.</strong>';
                recoveryRenderAlert('success', $logBody, 'Reparatur abgeschlossen', true);
                recoveryRenderActionBar([
                    '<a href="' . \htmlspecialchars(recoveryHomeUrl($authHash)) . '" class="button buttonPrimary">' . recoveryFaIcon(16, 'house') . ' Zurück zum Start</a>',
                ]);
            }
        }
    } elseif (isset($_POST['analyze_file_repair']) || recoveryHasUploadedPackageFile()) {
        $packageInput = recoveryResolvePackageInputFromRequest($authHash);
        if (isset($packageInput['error'])) {
            recoveryRenderAlert('error', (string) $packageInput['error']);
        } elseif (empty($packageInput['extractDir'])) {
            recoveryRenderAlert('error', 'Kein Entpack-Verzeichnis.');
        } else {
            $analyzeLog = [];
            $payload = recoveryExtractPackageInstructionTars($packageInput['extractDir'], $analyzeLog);
            if ($payload === null) {
                $body = '<strong>Paket konnte nicht ausgewertet werden.</strong><br>';
                foreach ($analyzeLog as $line) {
                    $body .= \htmlspecialchars($line) . '<br>';
                }
                recoveryRenderAlert('error', $body, null, true);
            } else {
                $missingNow = recoveryFindMissingBootstrapClasses($wcfDir);
                $analyzeBody = 'Paket <code>' . \htmlspecialchars((string) ($payload['package'] ?? $packageInput['packageIdentifier'] ?? '')) . '</code>'
                    . ' (App: <code>' . \htmlspecialchars((string) ($payload['applicationDirectory'] ?? '')) . '</code>)<br>';
                foreach ($analyzeLog as $line) {
                    $analyzeBody .= \htmlspecialchars($line) . '<br>';
                }
                recoveryRenderAlert('info', $analyzeBody, 'Analyse', true);
?>
    <form method="POST" action="<?= \htmlspecialchars($fileRepairUrl) ?>">
        <?php recoveryRenderFormModeHiddenFields(RECOVERY_MODE_PACKAGE_FILE_REPAIR, $authHash); ?>
        <input type="hidden" name="extract_dir" value="<?= \htmlspecialchars((string) $packageInput['extractDir']) ?>">
        <input type="hidden" name="confirm_file_repair" value="1">

        <section class="section">
            <header class="sectionHeader"><h2 class="sectionTitle">Fehlende Klassen auf dem Server</h2></header>
        <?php if ($missingNow === []): ?>
            <p>Keine fehlenden Bootstrap-Klassen erkannt. Sie können trotzdem Bootstrap aus dem Paket synchronisieren.</p>
        <?php else: ?>
            <ul class="recovery-step-list">
            <?php foreach ($missingNow as $cn): ?>
                <li class="recovery-step-list__item">
                    <label class="recovery-checkbox-label">
                        <input type="checkbox" name="repair_classes[]" value="<?= \htmlspecialchars($cn) ?>" checked>
                        <code><?= \htmlspecialchars($cn) ?></code>
                    </label>
                </li>
            <?php endforeach; ?>
            </ul>
        <?php endif; ?>
        </section>

        <?php
        recoveryRenderActionBar([
            '<button type="submit" class="button buttonPrimary">' . recoveryFaIcon(16, 'screwdriver-wrench') . ' Dateien jetzt wiederherstellen + Cache leeren</button>',
        ]);
        ?>
    </form>
<?php
            }
        }
    } else {
        recoveryRenderAlert(
            'warning',
            '<strong>Typisches Symptom:</strong> ACP zeigt <code>ClassNotFoundException</code> für eine Klasse, die im Bootstrap '
            . '(<code>lib/bootstrap/de.*.php</code>) registriert ist, deren <code>.class.php</code> auf dem Server fehlt '
            . '(z.&nbsp;B. nach partiellem Löschen von <code>shrinkr/lib/</code>).<br><br>'
            . 'Das Tool liest <code>lib/bootstrap/*.php</code>, findet fehlende Klassen und kopiert sie aus Ihrem '
            . '<strong>Paket-Archiv</strong> (<code>files.tar</code> / <code>files_wcf.tar</code>), leert danach den Cache und räumt Uploads auf.',
            'Wann dieser Modus hilft',
            true
        );

        if ($liveMissing !== []) {
            $missingList = '<ul class="recovery-step-list">';
            foreach ($liveMissing as $cn) {
                $missingList .= '<li><code>' . \htmlspecialchars($cn) . '</code></li>';
            }
            $missingList .= '</ul>';
            recoveryRenderAlert('error', $missingList, 'Aktuell fehlend (Live-Scan)', true);
        } else {
            recoveryRenderAlert('success', 'Live-Scan: keine fehlenden Bootstrap-Klassen gefunden (ACP-Fehler kann andere Ursache haben).');
        }
?>
    <form method="POST" enctype="multipart/form-data" action="<?= \htmlspecialchars($fileRepairUrl) ?>">
        <?php recoveryRenderFormModeHiddenFields(RECOVERY_MODE_PACKAGE_FILE_REPAIR, $authHash); ?>
        <input type="hidden" name="analyze_file_repair" value="1">
        <div class="form-group">
            <label for="package_file">Paket-Archiv (.tar.gz) — z.&nbsp;B. de.sunnyc.wsc.shrinkr_v1.0.17.tar.gz</label>
            <input type="file" name="package_file" id="package_file" accept=".tar,.tar.gz,.tgz" required>
        </div>
        <div class="form-group">
            <label for="file_repair_package_id">Optional Paket-ID</label>
            <input type="text" name="package_identifier" id="file_repair_package_id" placeholder="de.sunnyc.wsc.shrinkr" class="recovery-input--package-id">
        </div>
        <?php
        recoveryRenderActionBar([
            '<button type="submit" class="button buttonPrimary">' . recoveryFaIcon(16, 'magnifying-glass') . ' Paket analysieren & Vorschau</button>',
        ]);
        ?>
    </form>
<?php
    }
}
