<?php
/** Recovery mode: recovery_wizard — included by lib/Recovery/router.php */
declare(strict_types=1);

if ($mode === RECOVERY_MODE_RECOVERY_WIZARD) {
    $wizardUrl = recoveryBuildModeUrl(RECOVERY_MODE_RECOVERY_WIZARD, $authHash);
    $wcfDir = \rtrim(WCF_DIR, '/\\') . \DIRECTORY_SEPARATOR;

    $phase = (string) ($_POST['wizard_phase'] ?? $_GET['wizard_phase'] ?? 'package');
    $stepLabels = ['Paket', 'Backup', 'Diagnose', 'Plan', 'Ausführung'];
    if ($phase === 'done') {
        $stepLabels[] = 'Zusammenfassung';
        $phaseIndex = 5;
    } else {
        $phaseIndex = match ($phase) {
            'backup' => 1,
            'diagnose' => 2,
            'plan' => 3,
            'run' => 4,
            default => 0,
        };
    }

    recoveryRenderWizardPhaseSteps($phaseIndex, $stepLabels);

    $wizardHomeLink = ' <a href="' . \htmlspecialchars(recoveryHomeUrl($authHash)) . '">← Andere Situation wählen</a>';
    $wizardEmergencyFixed = recoverySessionGetEmergencyFixed($authHash);
    if ($wizardEmergencyFixed !== null) {
        $suggestedPkg = '';
        foreach (recoveryExtractMissingClassesFromLog($wcfDir) as $cn) {
            if (\preg_match('/^([a-z0-9]+)\\\\/', (string) $cn, $m)) {
                $suggestedPkg = 'de.sunnyc.wsc.' . $m[1];
                break;
            }
        }
        $removeUrl = recoveryBuildModeUrl(RECOVERY_MODE_PLUGIN_UNINSTALL, $authHash, $suggestedPkg !== '' ? ['package_identifier' => $suggestedPkg] : []);
        ?>
    <?php
    recoveryRenderAlert(
        'warning',
        '<strong>ACP-Notfall wurde bereits ausgeführt.</strong> Dateien nicht wiederherstellen — Plugin vollständig entfernen. '
        . '<a href="' . \htmlspecialchars($removeUrl) . '" class="button recovery-emergency-banner__btn">'
        . recoveryFaIcon(16, 'trash-can') . ' Plugin entfernen</a>',
        null,
        true
    );
    ?>
        <?php
    }

    if ($phase === 'run' && isset($_POST['wizard_execute'])) {
        if (recoveryHasUploadedPackageFile()) {
            $upload = recoveryHandlePackageUpload($_FILES['package_file']);
            if ($upload['ok'] && !empty($upload['extractDir'])) {
                recoveryStorePackageContext($authHash, (string) $upload['packageIdentifier'], $upload['extractDir']);
            }
        }
        $wizardState = recoveryWizardLoadState($authHash);
        $scopeForRun = isset($wizardState['scopeApplication']) ? (string) $wizardState['scopeApplication'] : '';
        $plan = [
            'orphans' => !empty($_POST['do_orphans']),
            'files' => !empty($_POST['do_files']) && $wizardEmergencyFixed === null,
            'neutralizeBootstrap' => !empty($_POST['do_neutralize_bootstrap']),
            'dbEventListeners' => !empty($_POST['do_db_event_listeners']),
            'cache' => !empty($_POST['do_cache']),
            'restoreOptionsInc' => !empty($_POST['do_restore_options_inc']),
            'optionConstantFallbacks' => !empty($_POST['do_option_constants']),
            'disableApplication' => !empty($_POST['do_disable_application']),
            'extractDir' => recoveryResolveWizardExtractDir($authHash),
            'scopeApplication' => $scopeForRun !== '' ? $scopeForRun : null,
            'dryRun' => !empty($_POST['wizard_dry_run']),
            'classes' => isset($_POST['repair_classes']) && \is_array($_POST['repair_classes'])
                ? \array_values(\array_filter(\array_map('strval', $_POST['repair_classes'])))
                : [],
        ];
        $execLog = [];
        $result = recoveryWizardExecutePlan($wcfDir, $db, WCF_N, $plan, $execLog);
        recoveryWizardSaveState($authHash, ['lastRun' => $result, 'lastPlan' => $plan]);
        $runInterp = recoveryBuildWizardRunInterpretation($result, $plan);
        recoveryRenderWizardRunSummary(
            $authHash,
            $wizardUrl,
            $recoveryBaseUrl,
            $wcfDir,
            $result,
            $plan,
            $execLog,
            $wizardState,
            $runInterp
        );
    } elseif ($phase === 'done') {
        $state = recoveryWizardLoadState($authHash);
        $lastRun = $state['lastRun'] ?? [];
        $lastPlan = $state['lastPlan'] ?? [];
?>
    <?php
        recoveryRenderBackupStepHero(
            'Zusammenfassung',
            '<strong>Ziel:</strong> ACP testen, Plugin entfernen und Recovery Tool aufräumen.' . $wizardHomeLink,
            'flag-checkered'
        );
        $postStillDone = \is_array($lastRun['postCheck'] ?? null) ? ($lastRun['postCheck']['stillPresent'] ?? []) : [];
        $wizardSuccess = $lastRun !== [] && $postStillDone === [] && empty($lastRun['dryRun']);
        $doneScope = isset($state['scopeApplication']) ? (string) $state['scopeApplication'] : null;
        $rebuildFlash = isset($_GET['rebuilt']) ? recoverySessionPullFlash($authHash, 'display_rebuild') : null;
    ?>
    <?php if (!recoveryUsesNativeAcpUi()): ?><div class="recovery-wizard-stack"><?php endif; ?>
    <?php if ($rebuildFlash !== null): ?>
    <?php
        $rebuildBody = '<strong>Anzeige aktualisiert.</strong><br>';
        foreach ($rebuildFlash['log'] ?? [] as $line) {
            $rebuildBody .= \htmlspecialchars((string) $line) . '<br>';
        }
        recoveryRenderAlert(
            !empty($rebuildFlash['ok']) ? 'success' : 'warning',
            $rebuildBody,
            null,
            true
        );
    ?>
    <?php endif; ?>
    <?php
        recoveryRenderWizardCompletionAlert(
            'done',
            !empty($lastRun['dryRun']),
            $postStillDone !== [],
            $wizardSuccess,
            '',
            '',
            0,
            $postStillDone !== [] ? (string) ($postStillDone[0] ?? '') : null
        );
        if ($postStillDone !== []) {
            recoveryRenderWizardPostCheckActions($authHash, $wizardUrl, $doneScope !== '' ? $doneScope : null);
        }
    ?>
    <?php recoveryRenderWizardLastRunSummary($lastRun); ?>
    <?php
        $donePkgId = (string) ($state['packageLabel'] ?? '');
        $pkgCtxDone = recoveryLoadPackageContext($authHash);
        if ($donePkgId === '' && !empty($pkgCtxDone['packageIdentifier'])) {
            $donePkgId = (string) $pkgCtxDone['packageIdentifier'];
        }
        recoveryRenderWizardCleanupSection($authHash, $wizardUrl, $recoveryBaseUrl, $donePkgId, $wizardSuccess);
    ?>
    <?php if (!recoveryUsesNativeAcpUi()): ?></div><?php endif; ?>
    <div class="formSubmit">
        <a href="<?= \htmlspecialchars($recoveryBaseUrl . 'acp/') ?>" class="button buttonPrimary" target="_blank" rel="noopener"><fa-icon size="16" name="gauge-high" solid></fa-icon> Zum ACP</a>
        <a href="<?= \htmlspecialchars($wizardUrl . '&wizard_phase=package') ?>" class="button">Wizard von vorn</a>
    </div>
<?php
    } elseif ($phase === 'plan' || isset($_POST['wizard_to_plan'])) {
        if (recoveryHasUploadedPackageFile()) {
            $upload = recoveryHandlePackageUpload($_FILES['package_file']);
            if ($upload['ok'] && !empty($upload['extractDir'])) {
                recoveryStorePackageContext($authHash, (string) $upload['packageIdentifier'], $upload['extractDir']);
            }
        }
        $state = recoveryWizardLoadState($authHash);
        $scopeApp = isset($state['scopeApplication']) ? (string) $state['scopeApplication'] : null;
        $scopeApp = $scopeApp !== '' ? $scopeApp : null;
        $diag = recoveryBuildSystemDiagnosis($wcfDir, $db, WCF_N, $scopeApp);
        recoveryWizardSaveState($authHash, \array_merge($state, ['diagnosis' => $diag]));
        $suggest = \array_merge([
            'orphans' => false,
            'files' => false,
            'neutralizeBootstrap' => false,
            'dbEventListeners' => false,
            'cache' => true,
            'restoreOptionsInc' => false,
            'optionConstantFallbacks' => false,
            'disableApplication' => false,
        ], $diag['suggestedActions'] ?? []);
        $missing = $diag['missingBootstrapClasses'] ?? recoveryFindMissingBootstrapClasses($wcfDir);
        if ($scopeApp !== null) {
            $missing = recoveryFilterFqcnByApplicationPrefix($missing, $scopeApp);
        }
        $pkgCtx = recoveryLoadPackageContext($authHash);
        $extractDir = recoveryResolveWizardExtractDir($authHash);
        $sessionPackageId = (string) ($pkgCtx['packageIdentifier'] ?? $state['packageLabel'] ?? '');
        if ($extractDir !== null) {
            recoveryWizardSaveState($authHash, ['extractDir' => $extractDir]);
        }
        $wizardRec = recoveryBuildWizardRecommendations($diag, $sessionPackageId !== '' ? $sessionPackageId : null);
        $acpAlreadyFixed = recoverySessionGetEmergencyFixed($authHash) !== null;
        $recByKey = [];
        foreach ($wizardRec['steps'] as $rs) {
            if (isset($rs['key'])) {
                $recByKey[(string) $rs['key']] = $rs;
            }
        }
?>
    <?php
        recoveryRenderBackupStepHero(
            'Schritt 4 — Plan',
            'Wählen Sie die Reparatur-Schritte für Ihre Installation.' . $wizardHomeLink,
            'list-check'
        );
    ?>
    <?php if ($acpAlreadyFixed): ?>
    <?php
        recoveryRenderAlert(
            'warning',
            '<strong>ACP läuft bereits?</strong> Dann <em>keine</em> fehlenden Plugin-Dateien wiederherstellen (Schritt 2 abwählen). '
            . 'Stattdessen <a href="' . \htmlspecialchars(recoveryBuildModeUrl(RECOVERY_MODE_PLUGIN_UNINSTALL, $authHash), ENT_QUOTES, 'UTF-8')
            . '">Plugin entfernen</a>.',
            null,
            true
        );
    ?>
    <?php endif; ?>

    <?php recoveryRenderPanelStart('', ['title' => 'Empfehlungen aus der Diagnose']); ?>
        <?php recoveryRenderWizardRecommendationsPanel($wizardRec); ?>
    <?php recoveryRenderPanelEnd(); ?>

    <?php if ($extractDir || $sessionPackageId !== ''): ?>
    <?php
        $pkgBanner = '<strong>Paket aus Schritt 1:</strong> ';
        if ($sessionPackageId !== '') {
            $pkgBanner .= '<code>' . \htmlspecialchars($sessionPackageId, ENT_QUOTES, 'UTF-8') . '</code> ';
        }
        $pkgBanner .= '— kein erneuter Upload nötig. '
            . '<button type="button" class="button small recovery-plan-change-pkg" id="recoveryPlanChangePackage">Anderes Paket</button>';
        recoveryRenderAlert('success', $pkgBanner, null, true);
    ?>
    <div id="recoveryPlanPackageAlt" class="recovery-plan-package-alt<?= recoveryUsesNativeAcpUi() ? '' : ' recovery-panel' ?>" hidden>
        <?php recoveryRenderFileInput('wizard_package_file_plan', 'package_file', 'Anderes Paket-Archiv (.tar.gz)', ['accept' => '.tar,.tar.gz,.tgz']); ?>
    </div>
    <?php endif; ?>

    <form id="recoveryPlanForm" method="POST" enctype="multipart/form-data" action="<?= \htmlspecialchars($wizardUrl) ?>"
        data-recovery-loading="Recovery-Schritte werden ausgeführt …"
        data-recovery-loading-steps="Reihenfolge: Paketliste → Dateien → Bootstrap → DB-Listener → Cache. Bitte nicht abbrechen.">
        <?php recoveryRenderFormModeHiddenFields(RECOVERY_MODE_RECOVERY_WIZARD, $authHash); ?>
        <input type="hidden" name="wizard_phase" value="run">
        <input type="hidden" name="wizard_execute" value="1">
        <?php if ($extractDir): ?>
        <input type="hidden" name="extract_dir" value="<?= \htmlspecialchars($extractDir) ?>">
        <?php endif; ?>

        <section class="section">
            <header class="sectionHeader">
                <h2 class="sectionTitle">Schritte auswählen</h2>
                <p class="sectionDescription">Reihenfolge: Paketliste → Dateien → Bootstrap → DB-Listener → Cache.</p>
            </header>
            <div class="recovery-plan-toolbar">
                <button type="button" class="button small" id="recoveryPlanSelectAll">Alle auswählen</button>
                <button type="button" class="button small" id="recoveryPlanSelectNone">Alle abwählen</button>
            </div>
            <ul class="listView recovery-plan-list">
                <li class="listViewItem">
                    <label class="listViewItem__head">
                        <input type="checkbox" class="recovery-plan-chk" name="do_orphans" value="1" <?= !empty($suggest['orphans']) ? 'checked' : '' ?>>
                        <span><strong>1. Paketliste reparieren</strong></span>
                        <span class="badge badgeYellow listViewItem__badge"><?= (int) ($diag['orphanApplicationCount'] ?? 0) ?></span>
                    </label>
                    <?php if (isset($recByKey['orphans'])): ?>
                    <div class="listViewItem__details"><?= $recByKey['orphans']['why'] ?? '' ?></div>
                    <?php endif; ?>
                </li>
                <li class="listViewItem">
                    <label class="listViewItem__head">
                        <input type="checkbox" class="recovery-plan-chk" name="do_files" value="1" id="recoveryPlanDoFiles" <?= !empty($suggest['files']) && !$acpAlreadyFixed ? 'checked' : '' ?> <?= $acpAlreadyFixed ? 'disabled' : '' ?>>
                        <span><strong>2. Plugin-Dateien wiederherstellen</strong><?= $acpAlreadyFixed ? ' (deaktiviert)' : '' ?></span>
                        <span class="badge <?= \count($missing) > 0 ? 'badgeRed' : 'badgeGreen' ?> listViewItem__badge"><?= \count($missing) ?></span>
                    </label>
                    <?php if (isset($recByKey['files'])): ?>
                    <div class="listViewItem__details"><?= $recByKey['files']['why'] ?? '' ?></div>
                    <?php endif; ?>
                </li>
                <?php $neutralCand = (int) ($diag['bootstrapNeutralizeCandidates'] ?? recoveryCountNeutralizableBootstrapRegisters($wcfDir)); ?>
                <li class="listViewItem">
                    <label class="listViewItem__head">
                        <input type="checkbox" class="recovery-plan-chk" name="do_neutralize_bootstrap" value="1" <?= !empty($suggest['neutralizeBootstrap']) ? 'checked' : '' ?>>
                        <span><strong>3. Bootstrap neutralisieren</strong></span>
                        <span class="badge <?= $neutralCand > 0 ? 'badgeYellow' : 'badgeGreen' ?> listViewItem__badge"><?= $neutralCand ?></span>
                    </label>
                    <?php if (isset($recByKey['neutralizeBootstrap'])): ?>
                    <div class="listViewItem__details"><?= $recByKey['neutralizeBootstrap']['why'] ?? '' ?></div>
                    <?php endif; ?>
                </li>
                <?php $orphDb = $diag['orphanedDbEventListeners'] ?? recoveryFindOrphanedDbEventListeners($wcfDir, $db, WCF_N, $scopeApp); ?>
                <li class="listViewItem">
                    <label class="listViewItem__head">
                        <input type="checkbox" class="recovery-plan-chk" name="do_db_event_listeners" value="1" <?= !empty($suggest['dbEventListeners']) ? 'checked' : '' ?>>
                        <span><strong>4. DB Event-Listener bereinigen</strong></span>
                        <span class="badge <?= \count($orphDb) > 0 ? 'badgeYellow' : 'badgeGreen' ?> listViewItem__badge"><?= \count($orphDb) ?></span>
                    </label>
                    <?php if (isset($recByKey['dbEventListeners'])): ?>
                    <div class="listViewItem__details"><?= $recByKey['dbEventListeners']['why'] ?? '' ?></div>
                    <?php endif; ?>
                </li>
                <?php $undefConsts = $diag['undefinedConstants'] ?? []; ?>
                <?php if ($undefConsts !== []): ?>
                <li class="listViewItem">
                    <label class="listViewItem__head">
                        <input type="checkbox" class="recovery-plan-chk" name="do_restore_options_inc" value="1" <?= !empty($suggest['restoreOptionsInc']) ? 'checked' : '' ?>>
                        <span><strong>5a. options.inc.php aus Paket mergen</strong></span>
                        <span class="badge badgeRed listViewItem__badge"><?= \count($undefConsts) ?></span>
                    </label>
                </li>
                <li class="listViewItem">
                    <label class="listViewItem__head">
                        <input type="checkbox" class="recovery-plan-chk" name="do_option_constants" value="1" <?= !empty($suggest['optionConstantFallbacks']) ? 'checked' : '' ?>>
                        <span><strong>5b. Option-Konstanten-Fallback</strong></span>
                    </label>
                </li>
                <?php if ($scopeApp !== null): ?>
                <li class="listViewItem">
                    <label class="listViewItem__head">
                        <input type="checkbox" class="recovery-plan-chk" name="do_disable_application" value="1" <?= !empty($suggest['disableApplication']) ? 'checked' : '' ?>>
                        <span><strong>Experte: Application <code><?= \htmlspecialchars($scopeApp) ?></code> Bootstrap überspringen</strong></span>
                    </label>
                </li>
                <?php endif; ?>
                <?php endif; ?>
                <li class="listViewItem">
                    <label class="listViewItem__head">
                        <input type="checkbox" class="recovery-plan-chk" name="do_cache" value="1" checked>
                        <span><strong><?= $undefConsts !== [] ? '6' : '5' ?>. Cache leeren</strong> + options.inc.php-Fallback (empfohlen)</span>
                    </label>
                    <?php if (isset($recByKey['cache'])): ?>
                    <div class="listViewItem__details"><?= $recByKey['cache']['why'] ?? '' ?></div>
                    <?php endif; ?>
                </li>
            </ul>

            <?php if ($missing !== []): ?>
            <?php
                recoveryRenderPanelStart('', [
                    'title' => 'Klassen für Schritt 2 (' . \count($missing) . ')',
                    'status' => \count($missing) . ' von ' . \count($missing) . ' ausgewählt',
                    'statusClass' => 'recovery-panel__status',
                    'open' => true,
                ]);
            ?>
                    <p id="recoveryPlanClassesSummary" class="silent"><?= \count($missing) ?> von <?= \count($missing) ?> ausgewählt</p>
                    <div class="recovery-plan-class-toolbar">
                        <input type="search" id="recoveryPlanClassFilter" class="recovery-plan-class-filter"
                            placeholder="Klasse filtern …" aria-label="Klassen filtern">
                        <div class="recovery-plan-class-toolbar__actions">
                            <button type="button" class="button small" id="recoveryPlanClassAll">Alle auswählen</button>
                            <button type="button" class="button small" id="recoveryPlanClassNone">Alle abwählen</button>
                        </div>
                    </div>
                    <div class="recovery-plan-class-grid-wrap">
                    <ul class="recovery-plan-class-grid">
                    <?php foreach ($missing as $cn): ?>
                        <li class="recovery-plan-class-grid__item">
                            <label class="recovery-plan-class-item">
                                <input type="checkbox" name="repair_classes[]" value="<?= \htmlspecialchars($cn) ?>" checked class="recovery-plan-class-chk">
                                <code class="recovery-plan-class-name"><?= \htmlspecialchars($cn) ?></code>
                            </label>
                        </li>
                    <?php endforeach; ?>
                    </ul>
                    </div>
            <?php recoveryRenderPanelEnd(); ?>
            <?php endif; ?>

            <?php if (!$extractDir && $sessionPackageId === ''): ?>
            <?php
                recoveryRenderAlert(
                    'warning',
                    'Für Schritt 2: <strong>Paket-Archiv hochladen</strong> — Standard-WoltLab-<code>.tar.gz</code> '
                    . 'mit <code>package.xml</code> und <code>files.tar</code>.',
                    null,
                    true
                );
            ?>
            <?php recoveryRenderFileInput('wizard_package_file_plan', 'package_file', 'Paket (.tar.gz)', ['accept' => '.tar,.tar.gz,.tgz']); ?>
            <?php elseif (!$extractDir && $sessionPackageId !== ''): ?>
            <?php
                recoveryRenderAlert(
                    'info',
                    'Paket-ID gespeichert: <code>' . \htmlspecialchars($sessionPackageId, ENT_QUOTES, 'UTF-8') . '</code>. '
                    . 'Für <strong>Schritt 2 (Dateien)</strong> bitte das Archiv nachreichen.',
                    null,
                    true
                );
            ?>
            <?php recoveryRenderFileInput('wizard_package_file_plan', 'package_file', 'Paket-Archiv (.tar.gz)', ['accept' => '.tar,.tar.gz,.tgz']); ?>
            <?php endif; ?>
        </section>

        <?php if (recoveryUsesNativeAcpUi()): ?>
        <dl>
            <dt></dt>
            <dd>
                <label>
                    <input type="checkbox" name="wizard_dry_run" value="1" id="wizardDryRunChk">
                    <strong>Dry-Run:</strong> Zeigt im Protokoll, was passieren würde — ohne Änderungen am Server.
                </label>
            </dd>
        </dl>
        <?php else: ?>
        <div class="recovery-dryrun-panel recovery-dryrun-panel--compact">
            <label class="recovery-checkbox-label">
                <input type="checkbox" name="wizard_dry_run" value="1" id="wizardDryRunChk">
                <strong>Dry-Run:</strong> Zeigt im Protokoll, was passieren würde — ohne Änderungen am Server.
            </label>
        </div>
        <?php endif; ?>

        <div id="recoveryPlanFilesWarn" hidden>
        <?php
            recoveryRenderAlert(
                'warning',
                '<strong>Schritt 2 ausgewählt, aber kein Paket-Archiv in Session.</strong> Bitte Paket hochladen oder Schritt 2 abwählen.',
                null,
                true
            );
        ?>
        </div>
    </form>
    <?php
        $planUninstallUrl = recoveryBuildModeUrl(
            RECOVERY_MODE_PLUGIN_UNINSTALL,
            $authHash,
            $sessionPackageId !== '' ? ['package_identifier' => $sessionPackageId] : []
        );
    ?>
    <div class="formSubmit recovery-formSubmit--center">
        <button type="submit" form="recoveryPlanForm" class="button buttonPrimary"><fa-icon size="16" name="play" solid></fa-icon> Ausgewählte Schritte jetzt ausführen</button>
        <a href="<?= \htmlspecialchars($wizardUrl . '&wizard_phase=diagnose') ?>" class="button">Zurück zur Diagnose</a>
        <a href="<?= \htmlspecialchars($wizardUrl . '&wizard_phase=package') ?>" class="button">Paket ändern</a>
        <?php if ($sessionPackageId !== ''): ?>
        <a href="<?= \htmlspecialchars($planUninstallUrl) ?>" class="button">Plugin entfernen</a>
        <?php endif; ?>
    </div>
    <script>
    (function () {
        var allBtn = document.getElementById('recoveryPlanSelectAll');
        var noneBtn = document.getElementById('recoveryPlanSelectNone');
        var changePkg = document.getElementById('recoveryPlanChangePackage');
        var pkgAlt = document.getElementById('recoveryPlanPackageAlt');
        var filesChk = document.getElementById('recoveryPlanDoFiles');
        var filesWarn = document.getElementById('recoveryPlanFilesWarn');
        var hasPackage = <?= \json_encode($extractDir !== null || $sessionPackageId !== '') ?>;
        function setPlanChecks(checked) {
            document.querySelectorAll('.recovery-plan-chk:not(:disabled)').forEach(function (c) {
                c.checked = checked;
            });
            syncFilesWarn();
        }
        function syncFilesWarn() {
            if (!filesWarn || !filesChk) { return; }
            filesWarn.hidden = !(filesChk.checked && !hasPackage && !pkgAlt || (pkgAlt && pkgAlt.hidden));
            if (filesChk.checked && !hasPackage) {
                filesWarn.hidden = pkgAlt && !pkgAlt.hidden;
            }
        }
        if (allBtn) { allBtn.addEventListener('click', function () { setPlanChecks(true); }); }
        if (noneBtn) { noneBtn.addEventListener('click', function () { setPlanChecks(false); }); }
        if (changePkg && pkgAlt) {
            changePkg.addEventListener('click', function () {
                pkgAlt.hidden = !pkgAlt.hidden;
                syncFilesWarn();
            });
        }
        if (filesChk) {
            filesChk.addEventListener('change', syncFilesWarn);
            syncFilesWarn();
        }
        var classFilter = document.getElementById('recoveryPlanClassFilter');
        var classSummary = document.getElementById('recoveryPlanClassesSummary');
        var classAllBtn = document.getElementById('recoveryPlanClassAll');
        var classNoneBtn = document.getElementById('recoveryPlanClassNone');
        function syncClassSummary() {
            if (!classSummary) { return; }
            var checks = document.querySelectorAll('.recovery-plan-class-chk');
            var total = checks.length;
            var selected = 0;
            checks.forEach(function (c) { if (c.checked) { selected++; } });
            classSummary.textContent = selected + ' von ' + total + ' ausgewählt';
        }
        function setClassChecks(checked) {
            document.querySelectorAll('.recovery-plan-class-chk:not(:disabled)').forEach(function (c) {
                c.checked = checked;
            });
            syncClassSummary();
        }
        if (classFilter) {
            classFilter.addEventListener('input', function () {
                var q = classFilter.value.trim().toLowerCase();
                document.querySelectorAll('.recovery-plan-class-grid__item').forEach(function (item) {
                    var name = item.querySelector('.recovery-plan-class-name');
                    var text = name ? name.textContent.toLowerCase() : '';
                    item.hidden = q !== '' && !text.includes(q);
                });
            });
        }
        if (classAllBtn) { classAllBtn.addEventListener('click', function () { setClassChecks(true); }); }
        if (classNoneBtn) { classNoneBtn.addEventListener('click', function () { setClassChecks(false); }); }
        document.querySelectorAll('.recovery-plan-class-chk').forEach(function (c) {
            c.addEventListener('change', syncClassSummary);
        });
        syncClassSummary();
    })();
    </script>
<?php
    }

    $wizardUploadError = null;
    if (isset($_POST['wizard_to_diagnose'])) {
        $phase = 'diagnose';
        $scopeApp = null;
        $packageLabel = '';
        $fullServerScan = !empty($_POST['wizard_full_scan']);

        if (!empty($_POST['wizard_backup_continue'])) {
            $state = recoveryWizardLoadState($authHash);
            $scopeApp = (string) ($state['scopeApplication'] ?? '');
            $packageLabel = (string) ($state['packageLabel'] ?? '');
            $fullServerScan = !empty($state['fullServerScan']);
        } elseif (!$fullServerScan) {
            if (recoveryHasUploadedPackageFile()) {
                $upload = recoveryHandlePackageUpload($_FILES['package_file']);
                if (!$upload['ok']) {
                    $wizardUploadError = (string) ($upload['error'] ?? 'Upload fehlgeschlagen.');
                    $phase = 'package';
                } elseif (!empty($upload['extractDir'])) {
                    recoveryStorePackageContext($authHash, (string) $upload['packageIdentifier'], $upload['extractDir']);
                    $meta = recoveryParsePackageMetaFromExtractDir((string) $upload['extractDir']);
                    $packageLabel = (string) ($meta['package'] ?? $upload['packageIdentifier'] ?? '');
                    $scopeApp = (string) ($meta['applicationDirectory'] ?? '');
                    recoveryWizardSaveState($authHash, [
                        'extractDir' => (string) $upload['extractDir'],
                        'packageLabel' => $packageLabel,
                    ]);
                }
            } elseif (!empty($_POST['package_identifier'])) {
                $packageLabel = \trim((string) $_POST['package_identifier']);
                if (\preg_match(RECOVERY_PACKAGE_ID_PATTERN, $packageLabel)) {
                    recoveryStorePackageContext($authHash, $packageLabel, null);
                    $scopeApp = recoveryGuessApplicationFromPackageIdentifier($packageLabel);
                } else {
                    $wizardUploadError = 'Ungültige Paket-ID.';
                    $phase = 'package';
                }
            } else {
                $wizardUploadError = 'Bitte Paket-Archiv hochladen, Paket-ID eingeben oder „gesamten Server prüfen“ wählen.';
                $phase = 'package';
            }
        }

        $runDiagnoseNow = $fullServerScan || !empty($_POST['wizard_backup_continue']);
        if ($phase === 'diagnose' && !$runDiagnoseNow) {
            $phase = 'backup';
            recoveryWizardSaveState($authHash, [
                'scopeApplication' => $scopeApp !== '' ? $scopeApp : null,
                'packageLabel' => $packageLabel,
                'fullServerScan' => false,
            ]);
        } elseif ($phase === 'diagnose' && $runDiagnoseNow) {
            $diag = recoveryBuildSystemDiagnosis(
                $wcfDir,
                $db,
                WCF_N,
                $fullServerScan ? null : ($scopeApp !== '' && $scopeApp !== null ? $scopeApp : null)
            );
            recoveryWizardSaveState($authHash, [
                'diagnosis' => $diag,
                'scopeApplication' => $fullServerScan ? null : ($scopeApp !== '' ? $scopeApp : null),
                'packageLabel' => $packageLabel,
                'fullServerScan' => $fullServerScan,
            ]);
        }
    }

    if ($phase === 'backup') {
        $backupGuideUrl = recoveryBuildModeUrl(RECOVERY_MODE_BACKUP_GUIDE, $authHash);
        $skipFormHtml = '';
        if (recoveryUsesNativeAcpUi()) {
            \ob_start();
            ?>
        <form method="POST" action="<?= \htmlspecialchars($wizardUrl) ?>" data-recovery-loading="Diagnose wird vorbereitet …">
            <?php recoveryRenderFormModeHiddenFields(RECOVERY_MODE_RECOVERY_WIZARD, $authHash); ?>
            <input type="hidden" name="wizard_phase" value="diagnose">
            <input type="hidden" name="wizard_to_diagnose" value="1">
            <input type="hidden" name="wizard_backup_continue" value="1">
            <div class="formSubmit">
                <button type="submit" class="button">Backup überspringen → zur Diagnose</button>
                <a href="<?= \htmlspecialchars($wizardUrl . '&wizard_phase=package') ?>" class="button">Paket ändern</a>
            </div>
        </form>
            <?php
            $skipFormHtml = (string) \ob_get_clean();
        } else {
            \ob_start();
            ?>
        <form id="recoveryWizardBackupSkipForm" method="POST" action="<?= \htmlspecialchars($wizardUrl) ?>" data-recovery-loading="Diagnose wird vorbereitet …">
            <?php recoveryRenderFormModeHiddenFields(RECOVERY_MODE_RECOVERY_WIZARD, $authHash); ?>
            <input type="hidden" name="wizard_phase" value="diagnose">
            <input type="hidden" name="wizard_to_diagnose" value="1">
            <input type="hidden" name="wizard_backup_continue" value="1">
        </form>
            <?php
            $skipFormHtml = (string) \ob_get_clean();
        }
        recoveryRenderBackupStepSection($authHash, $backupGuideUrl, [
            'heroTitle' => 'Schritt 2 — Backup',
            'heroSubtitle' => 'Optional, aber empfohlen — Backups öffnen sich in einem neuen Tab. '
                . 'Danach folgt die Diagnose für Ihr Paket.',
            'heroIcon' => 'database',
            'includeBoth' => true,
            'formTarget' => '_blank',
            'dryRunFormId' => '',
            'skipHint' => recoveryUsesNativeAcpUi() ? null : 'Nur überspringen, wenn Sie bereits ein aktuelles Backup haben.',
            'beforeActionsHtml' => recoveryUsesNativeAcpUi() ? $skipFormHtml : $skipFormHtml,
            'actions' => recoveryUsesNativeAcpUi() ? [] : [
                recoveryRenderSecondarySubmit(
                    'Backup überspringen → zur Diagnose',
                    'recoveryWizardBackupSkipForm'
                ),
                recoveryRenderSecondaryActionLink(
                    $wizardUrl . '&wizard_phase=package',
                    'Paket ändern'
                ),
            ],
        ]);
    } elseif ($phase === 'package') {
?>
    <?php if (!recoveryUsesNativeAcpUi()): ?>
    <?php
        recoveryRenderBackupStepHero(
            'Schritt 1 — Paket festlegen',
            'Damit Diagnose und Dateiwiederherstellung gezielt für Ihr Plugin laufen. '
            . '<strong>.tar.gz</strong> mit <code>package.xml</code> + <code>files.tar</code> ist ideal; '
            . 'nur die Paket-ID filtert die Diagnose, reicht aber nicht zum Kopieren von Dateien.',
            'box-archive'
        );
    ?>
    <section class="section">
        <header class="sectionHeader">
            <h2 class="sectionTitle">Ablauf</h2>
            <p class="sectionDescription">Backup (optional) → Diagnose → Plan → Ausführung → Zusammenfassung.</p>
        </header>
        <div class="acpDashboardBoxContainer acpDashboardBoxContainer--discovery">
            <div class="acpDashboardBox acpDashboardBox--discovery">
                <span class="recovery-scenario-card__badge recovery-scenario-card__badge--step" aria-label="Schritt 1">1</span>
                <div class="acpDashboardBox__head">
                    <span class="acpDashboardBox__icon"><?= recoveryFaIcon(22, 'database') ?></span>
                    <p class="acpDashboardBox__label">Backup</p>
                </div>
                <p class="acpDashboardBox__desc">Optional DB- und Datei-Backup vor Änderungen.</p>
            </div>
            <div class="acpDashboardBox acpDashboardBox--discovery">
                <span class="recovery-scenario-card__badge recovery-scenario-card__badge--step" aria-label="Schritt 2">2</span>
                <div class="acpDashboardBox__head">
                    <span class="acpDashboardBox__icon"><?= recoveryFaIcon(22, 'stethoscope') ?></span>
                    <p class="acpDashboardBox__label">Diagnose</p>
                </div>
                <p class="acpDashboardBox__desc">Bootstrap, DB-Listener und Log-Fehler prüfen.</p>
            </div>
            <div class="acpDashboardBox acpDashboardBox--discovery">
                <span class="recovery-scenario-card__badge recovery-scenario-card__badge--step" aria-label="Schritt 3">3</span>
                <div class="acpDashboardBox__head">
                    <span class="acpDashboardBox__icon"><?= recoveryFaIcon(22, 'list-check') ?></span>
                    <p class="acpDashboardBox__label">Plan</p>
                </div>
                <p class="acpDashboardBox__desc">Einzelne Reparatur-Schritte auswählen.</p>
            </div>
            <div class="acpDashboardBox acpDashboardBox--discovery">
                <span class="recovery-scenario-card__badge recovery-scenario-card__badge--step" aria-label="Schritt 4">4</span>
                <div class="acpDashboardBox__head">
                    <span class="acpDashboardBox__icon"><?= recoveryFaIcon(22, 'play') ?></span>
                    <p class="acpDashboardBox__label">Ausführung</p>
                </div>
                <p class="acpDashboardBox__desc">Nur die gewählten Schritte auf dem Server ausführen.</p>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <?php if ($wizardUploadError !== null): ?>
    <?php recoveryRenderAlert('error', $wizardUploadError); ?>
    <?php endif; ?>

    <section class="section">
        <header class="sectionHeader">
            <h2 class="sectionTitle">Paket festlegen</h2>
            <p class="sectionDescription">
                Schritt 1 — Backup (optional) → Diagnose → Plan → Ausführung.
                <strong>.tar.gz</strong> mit <code>package.xml</code> und <code>files.tar</code> ist ideal;
                nur die Paket-ID filtert die Diagnose, reicht aber nicht zum Kopieren von Dateien.
            </p>
        </header>
        <?php if (recoveryUsesNativeAcpUi()): ?>
        <div class="acpDashboard recovery-acp-dashboard--flow">
            <?php
            $flowSteps = [
                ['1', 'Backup', 'Optional DB- und Datei-Backup vor Änderungen.'],
                ['2', 'Diagnose', 'Bootstrap, DB-Listener und Log-Fehler prüfen.'],
                ['3', 'Plan', 'Einzelne Reparatur-Schritte auswählen.'],
                ['4', 'Ausführung', 'Nur die gewählten Schritte auf dem Server ausführen.'],
            ];
            foreach ($flowSteps as [$num, $title, $desc]): ?>
            <div class="acpDashboardBox">
                <h2 class="acpDashboardBox__title"><span class="badge small"><?= $num ?></span> <?= \htmlspecialchars($title) ?></h2>
                <div class="acpDashboardBox__content"><p><?= \htmlspecialchars($desc) ?></p></div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        <form id="recoveryPackagePrimaryForm" method="POST" enctype="multipart/form-data" action="<?= \htmlspecialchars($wizardUrl) ?>" data-recovery-loading="Paket wird hochgeladen — als Nächstes Backup (optional) …">
            <?php recoveryRenderFormModeHiddenFields(RECOVERY_MODE_RECOVERY_WIZARD, $authHash); ?>
            <input type="hidden" name="wizard_phase" value="backup">
            <input type="hidden" name="wizard_to_diagnose" value="1">
            <input type="hidden" name="wizard_full_scan" value="0" id="recoveryWizardFullScanFlag">
            <?php recoveryRenderFileInput('wizard_package_file', 'package_file', 'Paket-Archiv (.tar, .tar.gz, .tgz)', ['accept' => '.tar,.tar.gz,.tgz']); ?>
            <?php if (recoveryUsesNativeAcpUi()): ?>
            <dl>
                <dt><label for="wizard_package_identifier">oder nur Paket-ID</label></dt>
                <dd><input type="text" name="package_identifier" id="wizard_package_identifier" placeholder="de.vendor.meinplugin" class="long"></dd>
            </dl>
            <?php else: ?>
            <p class="recovery-form-field-label"><label for="wizard_package_identifier">oder nur Paket-ID</label></p>
            <input type="text" name="package_identifier" id="wizard_package_identifier" placeholder="de.vendor.meinplugin" class="recovery-input--package-id">
            <?php endif; ?>
        </form>
        <?php
            recoveryRenderAlert(
                'info',
                '<strong>Weiter — Backup:</strong> Paket speichern, optional DB/Datei-Backup, dann Diagnose. '
                . '<strong>Ohne Paket:</strong> sofort Diagnose für alle Plugins (kein gezieltes Datei-Kopieren).',
                null,
                true
            );
        ?>
        <div class="formSubmit">
            <button type="submit" form="recoveryPackagePrimaryForm" class="button buttonPrimary">Weiter — Backup</button>
            <button type="submit" form="recoveryPackagePrimaryForm" class="button" id="recoveryWizardFullScanBtn"
                onclick="document.getElementById('recoveryWizardFullScanFlag').value='1';document.getElementById('recoveryPackagePrimaryForm').querySelector('[name=wizard_phase]').value='diagnose';"
                title="Überspringt Paket/Backup — scannt alle Bootstrap-Registrierungen">
                Ohne Paket — gesamten Server prüfen
            </button>
        </div>
    </section>
<?php
    } elseif ($phase === 'diagnose') {
        $state = recoveryWizardLoadState($authHash);
        if (empty($state['diagnosis'])):
?>
    <?php recoveryRenderAlert('warning', 'Noch keine Diagnose. Bitte zuerst Schritt 1 (Paket) ausführen.'); ?>
    <p><a href="<?= \htmlspecialchars($wizardUrl . '&wizard_phase=package') ?>" class="button">Zu Schritt 1 — Paket</a></p>
<?php
        else:
        $scopeForDiag = (string) ($state['scopeApplication'] ?? '');
        $diag = recoveryBuildSystemDiagnosis(
            $wcfDir,
            $db,
            WCF_N,
            $scopeForDiag !== '' ? $scopeForDiag : null
        );
        $fullServerScan = !empty($state['fullServerScan']);
        $packageLabel = (string) ($state['packageLabel'] ?? '');
        $scopeApp = (string) ($state['scopeApplication'] ?? '');
        $diagSubtitle = '';
        if ($fullServerScan) {
            $diagSubtitle = 'Es wird der <strong>gesamte Server</strong> geprüft (alle Bootstrap-Registrierungen).';
        } elseif ($packageLabel !== '') {
            $diagSubtitle = 'Gefiltert für Paket <code>' . \htmlspecialchars($packageLabel) . '</code>';
            if ($scopeApp !== '') {
                $diagSubtitle .= ' — App <code>' . \htmlspecialchars($scopeApp) . '</code>';
            }
            $diagSubtitle .= '.';
        } else {
            $diagSubtitle = 'Live-Scan: Bootstrap-Einträge vs. vorhandene <code>.class.php</code>.';
        }
        recoveryRenderBackupStepHero(
            'Schritt 3 — Diagnose',
            $diagSubtitle . $wizardHomeLink,
            'stethoscope'
        );
?>

    <section class="section">
        <header class="sectionHeader"><h2 class="sectionTitle">Ergebnis (aktueller Server-Stand)</h2></header>
    <?php
        $missingCnt = \count($diag['missingBootstrapClasses']);
        $orphanCnt = (int) ($diag['orphanApplicationCount'] ?? 0);
        $neutralCnt = (int) ($diag['bootstrapNeutralizeCandidates'] ?? recoveryCountNeutralizableBootstrapRegisters($wcfDir));
        $orphDbCnt = \count($diag['orphanedDbEventListeners'] ?? recoveryFindOrphanedDbEventListeners($wcfDir, $db, WCF_N, $scopeApp !== '' ? $scopeApp : null));
        $undefCnt = \count($diag['undefinedConstants'] ?? []);
        recoveryRenderDiagnosisMetricGrid([
            ['label' => 'Fehlende Bootstrap-Klassen', 'value' => $missingCnt, 'status' => $missingCnt > 0 ? 'error' : 'ok'],
            ['label' => 'Verwaiste Applications (DB)', 'value' => $orphanCnt, 'status' => $orphanCnt > 0 ? 'warn' : 'ok'],
            ['label' => 'PSR-14 Bootstrap-Register', 'value' => $neutralCnt, 'status' => $neutralCnt > 0 ? 'warn' : 'ok'],
            ['label' => 'DB Event-Listener (fehlende Klasse)', 'value' => $orphDbCnt, 'status' => $orphDbCnt > 0 ? 'warn' : 'ok'],
            ['label' => 'Fehlende Konstanten (Log)', 'value' => $undefCnt, 'status' => $undefCnt > 0 ? 'error' : 'ok'],
        ]);
    ?>
    </section>

    <?php recoveryRenderUndefinedConstantsList($diag['undefinedConstants'] ?? []); ?>

    <?php
        $diagRec = recoveryBuildWizardRecommendations($diag, $packageLabel !== '' ? $packageLabel : null);
    ?>
    <?php recoveryRenderPanelStart('', ['title' => 'Empfehlungen & Hinweise']); ?>
        <?php recoveryRenderWizardRecommendationsPanel($diagRec); ?>
    <?php recoveryRenderPanelEnd(); ?>
    <?php recoveryRenderLogExcerptsPanel($diag['logExcerpts'] ?? [], 'wizard-diag-log'); ?>

    <?php if ($diag['missingBootstrapClasses'] === []): ?>
    <?php
        recoveryRenderAlert(
            'success',
            'Keine fehlenden Bootstrap-Klassen (im gewählten Umfang) gefunden. Sie können trotzdem fortfahren '
            . '(z.&nbsp;B. Cache leeren oder Paketliste bereinigen).',
            null,
            true
        );
    ?>
    <?php else: ?>
    <?php
        recoveryRenderAlert(
            'error',
            '<strong>' . \count($diag['missingBootstrapClasses']) . ' fehlende Klassen</strong> im gewählten Umfang '
            . '(Bootstrap-Registrierung, aber keine <code>.class.php</code> auf dem Server).',
            null,
            true
        );
    ?>
    <?php recoveryRenderWizardMissingClassesDetails($diag['missingBootstrapClasses']); ?>
    <?php endif; ?>

    <?php
        $diagExtractDir = recoveryResolveWizardExtractDir($authHash);
    ?>
    <?php
        recoveryRenderAlert(
            'info',
            '<strong>Nächster Schritt:</strong> Auf dem Plan-Schritt wählen Sie die Reparatur-Maßnahmen '
            . '(Bootstrap, DB-Listener, Dateien, Cache) und führen sie kontrolliert aus.',
            null,
            true
        );
    ?>
    <form id="recoveryDiagToPlanForm" method="POST" action="<?= \htmlspecialchars($wizardUrl) ?>" data-recovery-loading="Plan &amp; Auswahl wird geladen …">
        <?php recoveryRenderFormModeHiddenFields(RECOVERY_MODE_RECOVERY_WIZARD, $authHash); ?>
        <input type="hidden" name="wizard_phase" value="plan">
        <input type="hidden" name="wizard_to_plan" value="1">
        <?php if ($diagExtractDir): ?>
        <input type="hidden" name="extract_dir" value="<?= \htmlspecialchars($diagExtractDir) ?>">
        <?php endif; ?>
    </form>
    <div class="formSubmit">
        <button type="submit" form="recoveryDiagToPlanForm" class="button buttonPrimary">Plan &amp; Auswahl anzeigen</button>
        <a href="<?= \htmlspecialchars($wizardUrl . '&wizard_phase=backup') ?>" class="button">← Backup</a>
    </div>
<?php
        endif;
    }
}

// ============================================================================
// MODUS 8: SYSTEM-CHECK
// ============================================================================

