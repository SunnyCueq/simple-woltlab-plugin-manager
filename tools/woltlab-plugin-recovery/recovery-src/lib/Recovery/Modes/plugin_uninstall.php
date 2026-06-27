<?php
/** Recovery mode: plugin_uninstall — included by lib/Recovery/router.php */
declare(strict_types=1);

if ($mode === RECOVERY_MODE_PLUGIN_UNINSTALL) {
?>
<?php
    if (recoveryWasPostTruncated()) {
        recoveryRenderPostTruncatedWarning();
    }

    $uninstallModeUrl = recoveryBuildModeUrl(RECOVERY_MODE_PLUGIN_UNINSTALL, $authHash);
    $uninstallStep = recoveryResolveUninstallStep();
    $showEntryForms = recoveryUninstallShouldShowInputForm($authHash);

    if ($showEntryForms) {
        $pkgCtxPrefill = recoveryLoadPackageContext($authHash);
        $prefillPackageId = \trim((string) ($_GET['package_identifier'] ?? ($pkgCtxPrefill['packageIdentifier'] ?? '')));
        $dbPackages = recoveryFindUninstallDbPackages($db);
?>
    <?php recoveryRenderWizardPhaseSteps(0, ['Analyse & Auswahl', 'Backup', 'Ausführen']); ?>
    <?php recoveryRenderBrokenApplicationsAlert($db, WCF_N, $authHash, true); ?>
    <?php
        recoveryRenderBackupStepHero(
            'Plugin entfernen',
            'Vollständige Deinstallation: DB-Einträge, Plugin-Tabellen und Dateien — mit Backup und Dry-Run.',
            'trash-can'
        );
    ?>

    <?php if ($dbPackages !== []): ?>
    <?php if (recoveryUsesNativeAcpUi()): ?>
    <section class="section">
        <header class="sectionHeader">
            <h2 class="sectionTitle">In der Datenbank gefundene Pakete (<?= \count($dbPackages) ?>)</h2>
        </header>
        <table class="table tableList">
            <thead>
                <tr>
                    <th>Paket</th>
                    <th>Name</th>
                    <th class="columnActions">Aktion</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($dbPackages as $dbPkg): ?>
                <tr>
                    <td><code><?= \htmlspecialchars($dbPkg['package']) ?></code></td>
                    <td><?= \htmlspecialchars($dbPkg['packageName'] !== '' ? $dbPkg['packageName'] : '—') ?></td>
                    <td class="columnActions">
                        <a href="<?= \htmlspecialchars(recoveryBuildModeUrl(RECOVERY_MODE_PLUGIN_UNINSTALL, $authHash, ['package_identifier' => $dbPkg['package']])) ?>" class="button buttonSmall buttonPrimary">Deinstallation starten</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </section>
    <?php else: ?>
    <details class="recovery-panel recovery-panel--mb" open>
        <summary><?= recoveryFaIcon(16, 'database') ?> In der Datenbank gefundene Pakete (<?= \count($dbPackages) ?>)</summary>
        <div class="recovery-panel__body">
            <ul class="recovery-uninstall-pkg-list">
            <?php foreach ($dbPackages as $dbPkg): ?>
                <li class="recovery-uninstall-pkg-list__item">
                    <div>
                        <strong><code><?= \htmlspecialchars($dbPkg['package']) ?></code></strong>
                        <?php if ($dbPkg['packageName'] !== ''): ?>
                        — <?= \htmlspecialchars($dbPkg['packageName']) ?>
                        <?php endif; ?>
                        <span class="recovery-muted-inline">(ID <?= (int) $dbPkg['packageID'] ?>)</span>
                    </div>
                    <a href="<?= \htmlspecialchars(recoveryBuildModeUrl(RECOVERY_MODE_PLUGIN_UNINSTALL, $authHash, ['package_identifier' => $dbPkg['package']])) ?>" class="button buttonSmall buttonPrimary">
                        <?= recoveryFaIcon(16, 'play') ?> Deinstallation starten
                    </a>
                </li>
            <?php endforeach; ?>
            </ul>
        </div>
    </details>
    <?php endif; ?>
    <?php endif; ?>

    <?php if ($prefillPackageId !== ''): ?>
    <?php
        recoveryRenderAlert(
            'info',
            'Paket <code>' . \htmlspecialchars($prefillPackageId, ENT_QUOTES, 'UTF-8') . '</code>'
            . (!empty($pkgCtxPrefill['extractDir']) ? ' — Archiv in Session, kein erneuter Upload nötig.' : '.'),
            'Aus Recovery-Wizard',
            true
        );
    ?>
    <div class="formSubmit recovery-formSubmit--center recovery-formSubmit--tight">
        <a href="<?= \htmlspecialchars(recoveryBuildModeUrl(RECOVERY_MODE_PLUGIN_UNINSTALL, $authHash, ['package_identifier' => $prefillPackageId])) ?>" class="button buttonPrimary">
            <?= recoveryFaIcon(16, 'magnifying-glass') ?> <?= \htmlspecialchars($prefillPackageId) ?> analysieren
        </a>
    </div>
    <?php endif; ?>

    <?php if (recoveryUsesNativeAcpUi()): ?>
    <section class="section">
        <header class="sectionHeader">
            <h2 class="sectionTitle">Package-Identifier</h2>
            <p class="sectionDescription">Der eindeutige Package-Identifier (Reverse-Domain-Notation).</p>
        </header>
        <form method="POST" enctype="multipart/form-data" action="<?= \htmlspecialchars($uninstallModeUrl) ?>" data-recovery-loading="Paket wird analysiert …">
            <?php recoveryRenderFormModeHiddenFields(RECOVERY_MODE_PLUGIN_UNINSTALL, $authHash); ?>
            <dl>
                <dt><label for="uninstall_package_identifier">Package-Identifier</label></dt>
                <dd>
                    <input type="text" class="long" id="uninstall_package_identifier" name="package_identifier" value="<?= \htmlspecialchars($prefillPackageId) ?>" placeholder="z.B. de.example.my-plugin" autocomplete="off">
                </dd>
            </dl>
            <div class="formSubmit">
                <button type="submit" class="button buttonPrimary">Analysieren</button>
            </div>
        </form>
    </section>
    <section class="section">
        <header class="sectionHeader">
            <h2 class="sectionTitle">Package-Datei hochladen</h2>
            <p class="sectionDescription"><code>.tar</code>, <code>.tar.gz</code>, <code>.tgz</code> — max. 100 MiB. <code>package.xml</code> wird automatisch ausgelesen.</p>
        </header>
        <form method="POST" enctype="multipart/form-data" action="<?= \htmlspecialchars($uninstallModeUrl) ?>" data-recovery-loading="Paket wird hochgeladen und analysiert …" id="recoveryUninstallUploadForm">
            <?php recoveryRenderFormModeHiddenFields(RECOVERY_MODE_PLUGIN_UNINSTALL, $authHash); ?>
            <?php recoveryRenderFileInput('uninstall_package_file', 'package_file', 'Package-Datei (.tar, .tar.gz, .tgz)', ['accept' => '.tar,.tar.gz,.tgz', 'required' => '1']); ?>
            <div class="formSubmit">
                <button type="submit" class="button buttonPrimary" id="recoveryUninstallUploadBtn">Analysieren</button>
            </div>
        </form>
    </section>
    <?php else: ?>
    <div class="recovery-grid recovery-grid--2 recovery-uninstall-entry-grid">
        <div class="recovery-grid-card">
            <h3><?= recoveryFaIcon(16, 'keyboard') ?> Package-Identifier</h3>
            <p>Der eindeutige Package-Identifier (Reverse-Domain-Notation).</p>
            <form method="POST" enctype="multipart/form-data" action="<?= \htmlspecialchars($uninstallModeUrl) ?>" data-recovery-loading="Paket wird analysiert …">
                <?php recoveryRenderFormModeHiddenFields(RECOVERY_MODE_PLUGIN_UNINSTALL, $authHash); ?>
                <div class="form-group">
                    <label for="uninstall_package_identifier">Package-Identifier</label>
                    <input type="text" id="uninstall_package_identifier" name="package_identifier" value="<?= \htmlspecialchars($prefillPackageId) ?>" placeholder="z.B. de.example.my-plugin" autocomplete="off">
                </div>
                <div class="formSubmit recovery-formSubmit--center">
                    <button type="submit" class="button buttonPrimary"><fa-icon size="16" name="magnifying-glass" solid></fa-icon> Analysieren</button>
                </div>
            </form>
        </div>
        <div class="recovery-grid-card">
            <h3><?= recoveryFaIcon(16, 'file-archive') ?> Package-Datei hochladen</h3>
            <p><code>.tar</code>, <code>.tar.gz</code>, <code>.tgz</code> — max. 100 MiB. <code>package.xml</code> wird automatisch ausgelesen.</p>
            <form method="POST" enctype="multipart/form-data" action="<?= \htmlspecialchars($uninstallModeUrl) ?>" data-recovery-loading="Paket wird hochgeladen und analysiert …" id="recoveryUninstallUploadForm">
                <?php recoveryRenderFormModeHiddenFields(RECOVERY_MODE_PLUGIN_UNINSTALL, $authHash); ?>
                <div class="form-group">
                    <?php recoveryRenderFileInput('uninstall_package_file', 'package_file', 'Package-Datei (.tar, .tar.gz, .tgz)', ['accept' => '.tar,.tar.gz,.tgz', 'required' => '1']); ?>
                </div>
                <div class="formSubmit recovery-formSubmit--center">
                    <button type="submit" class="button buttonPrimary" id="recoveryUninstallUploadBtn"><fa-icon size="16" name="magnifying-glass" solid></fa-icon> Analysieren</button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>
    <script>
    (function () {
        var input = document.querySelector('#recoveryUninstallUploadForm input[type="file"]');
        var btn = document.getElementById('recoveryUninstallUploadBtn');
        if (input && btn) {
            input.addEventListener('change', function () {
                btn.classList.toggle('buttonPrimary', !!(input.files && input.files.length));
            });
        }
    })();
    </script>
<?php
    } else {
        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && $uninstallStep === '') {
            echo '<div id="recovery-loading-overlay" class="recovery-loading recovery-loading--visible">Paket wird analysiert …</div>';
        }
        try {
        $packageInput = recoveryResolvePackageInputFromRequest($authHash);
        if (isset($packageInput['error'])) {
            echo '<p class="error"><strong>Fehler:</strong> '
                . \htmlspecialchars($packageInput['error']) . '</p>';
        } else {
            $packageIdentifier = $packageInput['packageIdentifier'] ?? null;
            $extractDir        = $packageInput['extractDir'] ?? recoveryResolveTrustedExtractDir();

            if (!$packageIdentifier) {
                echo '<p class="error"><strong>Fehler:</strong> Kein Package-Identifier ermittelt. Bitte erneut versuchen.</p>';
            } else {
                $packageData = recoveryLookupPackageInDatabase($db, $packageIdentifier);
                $packageID   = $packageData ? (int) $packageData['packageID'] : null;

                // Ressourcen aus Archiv (falls vorhanden)
                $resources = null;
                if ($extractDir && \is_dir($extractDir)) {
                    $resources = analyzePackageResources($extractDir, $packageIdentifier, $db);
                }
                $wcfN = $resources ? (int)$resources['wcfN'] : WCF_N;

                // ── SCHRITT 1: ANALYSE + AUSWAHL ──────────────────────────────
                if ($uninstallStep === '') {
?>
    <?php recoveryRenderWizardPhaseSteps(0, ['Analyse & Auswahl', 'Backup', 'Ausführen']); ?>
    <?php recoveryRenderBrokenApplicationsAlert($db, $wcfN, $authHash); ?>
    <?php
                    recoveryRenderBackupStepHero(
                        'Analyse & Auswahl',
                        'Prüfen Sie DB-Einträge und Dateien — <strong>Application</strong> ist Pflicht und wird immer mit entfernt.',
                        'list-check'
                    );
                    echo '<div class="recovery-uninstall-stack">';
                    echo '<div class="recovery-uninstall-pkg-banner recovery-alert recovery-alert--info">';
                    echo '<p class="recovery-alert__title"><strong>Paket</strong> <code>' . \htmlspecialchars($packageIdentifier) . '</code></p>';
                    echo '<dl>';
                    if ($packageData) {
                        echo '<dt>Status</dt><dd>In Datenbank (ID <strong>' . (int) $packageID . '</strong>)</dd>';
                        echo '<dt>Name</dt><dd>' . \htmlspecialchars($packageData['packageName']) . '</dd>';
                        echo '<dt>WCF_N</dt><dd>' . (int) $wcfN . '</dd>';
                    } else {
                        echo '<dt>Status</dt><dd><em>Nicht in Datenbank</em> — nur Tabellen-Drops und Datei-Löschung möglich</dd>';
                    }
                    echo '</dl></div>';

                    // PIP-Counts aus DB (+ weitere Tabellen mit packageID-Spalte)
                    $pipCtx = recoveryBuildUninstallPipContext($db, $wcfN, $packageID);
                    $pipMap = $pipCtx['map'];
                    $pipCounts = $pipCtx['counts'];

                    // Plugin-eigene Tabellen ermitteln
                    $customTables = [];
                    if ($resources && !empty($resources['tables'])) {
                        $customTables = $resources['tables'];
                    } else {
                        $customTables = findPackageTables($db, $packageIdentifier, $wcfN);
                    }

                    // Dateisystem prüfen
                    $fsEval = recoveryEvaluatePluginDirectoryDeletion(
                        $packageData, $packageIdentifier, $db, $wcfN, $extractDir
                    );

                    echo '<form method="POST" enctype="multipart/form-data" class="recovery-uninstall-form" action="' . \htmlspecialchars(recoveryBuildModeUrl(RECOVERY_MODE_PLUGIN_UNINSTALL, $authHash)) . '">';
                    echo '<input type="hidden" name="mode" value="' . RECOVERY_MODE_PLUGIN_UNINSTALL . '">';
                    echo '<input type="hidden" name="t" value="' . \htmlspecialchars($authHash, ENT_QUOTES, 'UTF-8') . '">';
                    echo '<input type="hidden" name="package_identifier" value="' . \htmlspecialchars($packageIdentifier) . '">';
                    if ($extractDir) {
                        echo '<input type="hidden" name="extract_dir" value="' . \htmlspecialchars($extractDir) . '">';
                    }
                    echo '<input type="hidden" name="uninstall_step" value="1">';

                    recoveryRenderAlert(
                        'warning',
                        '<strong>' . recoveryFaIcon(16, 'database') . ' Vor dem Entfernen:</strong> '
                        . '<a href="' . \htmlspecialchars(recoveryBuildModeUrl(RECOVERY_MODE_BACKUP_GUIDE, $authHash)) . '">Datensicherung</a> '
                        . 'anlegen (DB + Dateien) — siehe <a href="https://manual.woltlab.com/de/backup/" target="_blank" rel="noopener">WoltLab-Handbuch</a>.',
                        null,
                        true
                    );

                    echo '<details class="recovery-panel recovery-panel--mb">';
                    echo '<summary><strong>Dry-Run (optional)</strong> — Vorschau ohne Änderungen</summary>';
                    echo '<div class="recovery-panel__body recovery-dryrun-panel">';
                    echo '<p class="recovery-mb-sm"><label class="recovery-checkbox-label"><input type="checkbox" name="dry_run" id="recoveryDryRunToggle" value="1">';
                    echo '<strong>Dry-Run-Modus:</strong> Zeigt was gelöscht WÜRDE, ohne tatsächliche Änderungen vorzunehmen</label></p>';
                    echo '<div id="recoveryDryRunQuick" class="recovery-dryrun-quick" hidden>';
                    echo '<button type="submit" name="dry_run" value="1" class="button" data-recovery-dry-run-info>' . recoveryFaIcon(16, 'play') . ' Dry-Run jetzt starten</button>';
                    echo '<p class="recovery-muted-inline">Startet direkt mit Dry-Run — ohne nach unten zu scrollen.</p>';
                    echo '</div></div></details>';

                    // ── DB-Einträge nach packageID ────────────────────────────
                    if ($packageID) {
                        $hasSafeRows = false;
                        foreach ($pipCounts as $cnt) {
                            if ($cnt > 0) { $hasSafeRows = true; break; }
                        }

                        echo '<details class="recovery-panel recovery-panel--mb"' . ($hasSafeRows ? ' open' : '') . '>';
                        echo '<summary><strong>DB-Einträge nach packageID</strong> (' . (int) $packageID . ')</summary>';
                        echo '<div class="recovery-panel__body">';
                        echo '<p class="recovery-panel__intro">Nur Einträge mit <code>packageID = ' . (int) $packageID . '</code> werden gelöscht. '
                            . 'Klicken Sie auf die <strong>Zahl</strong> in „Einträge“ für eine Vorschau. '
                            . '<strong>Application</strong> ist vorausgewählt (Pflicht).</p>';
                        echo '<table class="tableList recovery-table-list recovery-data-table recovery-pip-table"><thead><tr>';
                        echo '<th class="recovery-col-check"><input type="checkbox" id="chkAllPip" title="Alle aus/abwählen"></th>';
                        echo '<th>Kategorie (PIP)</th><th>Tabelle</th><th class="recovery-col-num">Einträge</th>';
                        echo '</tr></thead><tbody>';

                        foreach ($pipMap as $pip => $info) {
                            if (!$info['safe'] || $info['col'] !== 'packageID' || $info['table'] === '') {
                                continue;
                            }
                            $count = $pipCounts[$pip] ?? 0;
                            if ($count < 0) {
                                // Tabelle existiert nicht
                                echo '<tr class="recovery-row--muted">';
                                echo '<td class="recovery-col-check"><input type="checkbox" name="pip_select[]" value="' . \htmlspecialchars($pip) . '" disabled></td>';
                                echo '<td>' . \htmlspecialchars($info['label']) . '</td>';
                                echo '<td><code>wcf' . $wcfN . '_' . \htmlspecialchars($info['table']) . '</code></td>';
                                echo '<td class="recovery-col-num"><small>–</small></td>';
                                echo '</tr>';
                            } else {
                                $forceCheck = $pip === 'application';
                                $checked = ($forceCheck || $count > 0) ? ' checked' : '';
                                $requiredAttr = $forceCheck ? ' data-recovery-pip-required="1"' : '';
                                $rowClass = $count === 0 && !$forceCheck ? ' class="recovery-row--empty"' : '';
                                echo '<tr' . $rowClass . '>';
                                echo '<td class="recovery-col-check"><input type="checkbox" name="pip_select[]" value="' . \htmlspecialchars($pip) . '"' . $checked . $requiredAttr . '></td>';
                                echo '<td>' . \htmlspecialchars($info['label']) . '</td>';
                                echo '<td><code>wcf' . $wcfN . '_' . \htmlspecialchars($info['table']) . '</code></td>';
                                echo '<td class="recovery-col-num">'
                                    . recoveryRenderPipCountCell($count, $info['table'], $packageID) . '</td>';
                                echo '</tr>';
                            }
                        }
                        echo '</tbody></table></div></details>';
                        echo '<div id="recoveryPipPreviewModal" hidden>';
                        echo '<div class="recovery-pip-preview-dialog" role="dialog" aria-modal="true">';
                        echo '<h3 id="recoveryPipPreviewTitle">Einträge</h3>';
                        echo '<div id="recoveryPipPreviewBody"></div>';
                        echo '<p class="recovery-mt-md"><button type="button" class="button" id="recoveryPipPreviewClose">Schließen</button></p>';
                        echo '</div></div>';
                        echo '<script>
                            (function () {
                                var authToken = ' . \json_encode($authHash) . ';
                                var dryToggle = document.getElementById("recoveryDryRunToggle");
                                var dryQuick = document.getElementById("recoveryDryRunQuick");
                                if (dryToggle && dryQuick) {
                                    var syncDryQuick = function () {
                                        dryQuick.hidden = !dryToggle.checked;
                                    };
                                    syncDryQuick();
                                    dryToggle.addEventListener("change", syncDryQuick);
                                }
                                var counts = ' . \json_encode($pipCounts) . ';
                                var allChecked = Object.values(counts).some(function (v) { return v > 0; });
                                var chkAllPip = document.getElementById("chkAllPip");
                                if (chkAllPip) {
                                    chkAllPip.checked = allChecked;
                                    chkAllPip.addEventListener("change", function () {
                                        document.querySelectorAll("input[name=\\"pip_select[]\\"]:not(:disabled):not([data-recovery-pip-required])").forEach(function (c) {
                                            c.checked = chkAllPip.checked;
                                        });
                                    });
                                }
                                document.querySelectorAll("input[data-recovery-pip-required]").forEach(function (c) {
                                    c.addEventListener("click", function (e) {
                                        e.preventDefault();
                                        c.checked = true;
                                    });
                                });
                                var modal = document.getElementById("recoveryPipPreviewModal");
                                var modalBody = document.getElementById("recoveryPipPreviewBody");
                                var modalTitle = document.getElementById("recoveryPipPreviewTitle");
                                var modalClose = document.getElementById("recoveryPipPreviewClose");
                                function escapeHtml(s) {
                                    var d = document.createElement("div");
                                    d.textContent = s;
                                    return d.innerHTML;
                                }
                                function closeModal() { if (modal) { modal.hidden = true; } }
                                if (modalClose) { modalClose.addEventListener("click", closeModal); }
                                if (modal) {
                                    modal.addEventListener("click", function (e) {
                                        if (e.target === modal) { closeModal(); }
                                    });
                                }
                                document.querySelectorAll(".recovery-pip-count-btn").forEach(function (btn) {
                                    btn.addEventListener("click", function () {
                                        var table = btn.getAttribute("data-table");
                                        var packageId = btn.getAttribute("data-package-id");
                                        if (!table || !packageId) { return; }
                                        modalTitle.textContent = "Lade …";
                                        modalBody.innerHTML = "<p>Bitte warten …</p>";
                                        modal.hidden = false;
                                        var previewUrl = new URL(window.location.href);
                                        previewUrl.search = "";
                                        previewUrl.searchParams.set("action", "pip-preview");
                                        previewUrl.searchParams.set("t", authToken);
                                        previewUrl.searchParams.set("table", table);
                                        previewUrl.searchParams.set("package_id", packageId);
                                        fetch(previewUrl.toString(), { credentials: "same-origin" })
                                            .then(function (r) {
                                                return r.text().then(function (text) {
                                                    if (!text) {
                                                        throw new Error("Leere Server-Antwort (HTTP " + r.status + ")");
                                                    }
                                                    try {
                                                        return JSON.parse(text);
                                                    } catch (parseErr) {
                                                        throw new Error("Keine gültige JSON-Antwort: " + text.substring(0, 200));
                                                    }
                                                });
                                            })
                                            .then(function (data) {
                                                if (!data.ok) {
                                                    modalBody.innerHTML = "<p class=\\"error\\">"
                                                        + escapeHtml(data.error || "Fehler") + "</p>";
                                                    return;
                                                }
                                                modalTitle.textContent = data.table + " (" + data.total + " Einträge)";
                                                if (!data.rows || data.rows.length === 0) {
                                                    modalBody.innerHTML = "<p><em>Keine Zeilen gefunden.</em></p>";
                                                    return;
                                                }
                                                var html = "<p><small>Vorschau (max. " + data.rows.length
                                                    + " von " + data.total + "):</small></p>";
                                                html += "<table class=\\"table\\"><thead><tr>";
                                                (data.columns || []).forEach(function (c) {
                                                    html += "<th>" + escapeHtml(c) + "</th>";
                                                });
                                                html += "</tr></thead><tbody>";
                                                data.rows.forEach(function (row) {
                                                    html += "<tr>";
                                                    (data.columns || []).forEach(function (c) {
                                                        var val = row[c];
                                                        if (val === null || val === undefined) { val = "—"; }
                                                        else if (String(val).length > 120) {
                                                            val = String(val).substring(0, 117) + "…";
                                                        }
                                                        html += "<td><code>" + escapeHtml(String(val)) + "</code></td>";
                                                    });
                                                    html += "</tr>";
                                                });
                                                html += "</tbody></table>";
                                                modalBody.innerHTML = html;
                                            })
                                            .catch(function (err) {
                                                modalBody.innerHTML = "<p class=\\"error\\">"
                                                    + escapeHtml(String(err)) + "</p>";
                                            });
                                    });
                                });
                            })();
                        </script>';
                    } else {
                        recoveryRenderAlert('warning', 'Keine packageID in der Datenbank — DB-Einträge per packageID nicht analysierbar.');
                        if (!empty($customTables)) {
                            recoveryRenderAlert('info', 'Plugin-eigene Tabellen wurden per Namensmuster gefunden — DROP TABLE unten möglich.');
                        }
                    }

                    echo '<details class="recovery-panel recovery-panel--mb"' . (!empty($customTables) ? ' open' : '') . '>';
                    echo '<summary><strong>Plugin-eigene Tabellen (DROP TABLE)</strong></summary>';
                    echo '<div class="recovery-panel__body">';
                    if (!empty($customTables)) {
                        echo '<table class="tableList recovery-table-list recovery-data-table recovery-pip-table">';
                        echo '<thead><tr><th class="recovery-col-check">&#x2713;</th><th>Tabellenname</th><th class="recovery-col-num">Einträge</th></tr></thead>';
                        echo '<tbody>';
                        foreach ($customTables as $table) {
                            $safeTable = \str_replace('`', '', (string)$table);
                            if (!recoveryValidateSqlTableName($safeTable)) {
                                continue;
                            }
                            $cnt = '?';
                            try {
                                $st = $db->prepareStatement('SELECT COUNT(*) AS c FROM `' . $safeTable . '`');
                                $st->execute();
                                $cnt = (int)($st->fetchArray()['c'] ?? 0);
                            } catch (\Throwable $ignored) {}
                            echo '<tr>';
                            echo '<td class="recovery-col-check"><input type="checkbox" name="drop_tables[]" value="' . \htmlspecialchars($safeTable) . '" checked></td>';
                            echo '<td><code>' . \htmlspecialchars($safeTable) . '</code></td>';
                            echo '<td class="recovery-col-num">' . $cnt . '</td>';
                            echo '</tr>';
                        }
                        echo '</tbody></table>';
                    } else {
                        echo '<p class="recovery-muted-empty"><em>Keine plugin-eigenen Tabellen gefunden.</em></p>';
                    }
                    echo '</div></details>';

                    $fileLogCount = $packageID ? \count(recoveryLoadPackageFileLogPaths($db, $wcfN, $packageID)) : 0;
                    $sqlPreviewStep = $packageID ? recoveryPreviewSqlRollback($db, $wcfN, $packageID) : ['actions' => []];

                    if ($packageID) {
                        echo '<details class="recovery-panel recovery-panel--mb">';
                        echo '<summary><strong>' . recoveryFaIcon(16, 'gears') . ' Erweiterte Schritte</strong></summary>';
                        echo '<div class="recovery-panel__body recovery-uninstall-advanced">';
                        echo '<label class="recovery-checkbox-label--block"><input type="checkbox" name="rebuild_bootstrap" value="1" checked> ';
                        echo '<strong>lib/bootstrap.php neu erzeugen</strong> (empfohlen)</label>';
                        if ($fileLogCount > 0) {
                            echo '<label class="recovery-checkbox-label--block"><input type="checkbox" name="delete_files_log" value="1" checked> ';
                            echo '<strong>Dateien aus file_log löschen</strong> (' . $fileLogCount . ')</label>';
                        }
                        if ($sqlPreviewStep['actions'] !== []) {
                            echo '<label class="recovery-checkbox-label--block"><input type="checkbox" name="sql_rollback" value="1"> ';
                            echo '<strong>SQL-Schema zurücksetzen</strong> (' . \count($sqlPreviewStep['actions']) . ' Aktionen — optional)</label>';
                            echo '<small>Destruktiv — nur mit DB-Backup.</small>';
                        }
                        echo '</div></details>';
                    }

                    echo '<details class="recovery-panel recovery-panel--mb"' . ($fsEval['deletable'] ? ' open' : '') . '>';
                    echo '<summary><strong>' . recoveryFaIcon(16, 'folder-open') . ' Dateisystem</strong></summary>';
                    echo '<div class="recovery-panel__body">';
                    if ($fsEval['deletable']) {
                        echo '<label class="recovery-checkbox-label"><input type="checkbox" name="delete_files_dir" value="1"';
                        if ($fileLogCount === 0) { echo ' checked'; }
                        echo '> Plugin-Verzeichnis <code>' . \htmlspecialchars((string)$fsEval['relativePath']) . '/</code> auf dem Server löschen</label>';
                        echo '<p class="recovery-muted-inline">Zusätzlich zu file_log oder als Fallback.</p>';
                    } else {
                        recoveryRenderAlert('info', '<strong>Dateisystem:</strong> ' . \htmlspecialchars($fsEval['reason']), null, true);
                    }
                    echo '</div></details>';
                    echo '</div>';
                    recoveryRenderActionBar([
                        '<button type="submit" class="button buttonPrimary">' . recoveryFaIcon(16, 'play') . ' Weiter: Backup &amp; Ausführen</button>',
                    ]);
                    echo '</form>';

                // ── SCHRITT 2: BACKUP ─────────────────────────────────────────
                } elseif ($uninstallStep === '1') {
                    $isDryRun      = isset($_POST['dry_run']) && $_POST['dry_run'] === '1';
                    $selectedPips  = \is_array($_POST['pip_select'] ?? null)  ? (array)$_POST['pip_select']  : [];
                    $dropTables    = \is_array($_POST['drop_tables'] ?? null)  ? (array)$_POST['drop_tables']  : [];
                    $deleteFilesLog = !empty($_POST['delete_files_log']) && $_POST['delete_files_log'] === '1';
                    $deleteFilesDir = !empty($_POST['delete_files_dir']) && $_POST['delete_files_dir'] === '1';
                    $deleteFilesLegacy = !empty($_POST['delete_files']) && $_POST['delete_files'] === '1';
                    $deleteFiles   = $deleteFilesLog || $deleteFilesDir || $deleteFilesLegacy;
                    $sqlRollback   = !empty($_POST['sql_rollback']) && $_POST['sql_rollback'] === '1';
                    $rebuildBootstrap = !isset($_POST['rebuild_bootstrap']) || $_POST['rebuild_bootstrap'] === '1';

                    // Eingaben validieren
                    $pipCtx = recoveryBuildUninstallPipContext($db, $wcfN, $packageID);
                    $pipMap = $pipCtx['map'];
                    $validPips = \array_values(\array_filter($selectedPips, static fn($p) => isset($pipMap[$p]) && $pipMap[$p]['safe'] && $pipMap[$p]['table'] !== ''));
                    $validDropTables = [];
                    foreach ($dropTables as $t) {
                        $s = \str_replace('`', '', (string)$t);
                        if (recoveryValidateSqlTableName($s)) {
                            $validDropTables[] = $s;
                        }
                    }
?>
    <?php recoveryRenderWizardPhaseSteps(1, ['Analyse & Auswahl', 'Backup', 'Ausführen']); ?>
<?php
                    // SQL-Backup generieren
                    $backupSql = '';
                    if ($packageID && !empty($validPips)) {
                        $backupSql = recoveryGenerateSqlBackup($db, $wcfN, $packageID, $validPips);
                    }

                    if ($backupSql !== '') {
                        $backupB64 = \base64_encode($backupSql);
                        $backupFilename = 'recovery-backup-' . \date('Y-m-d-His') . '.sql';
                        $backupSizeLabel = \number_format(\strlen($backupSql));
                        echo '<section class="section recovery-uninstall-backup">';
                        echo '<header class="sectionHeader">';
                        echo '<h2 class="sectionTitle">' . recoveryFaIcon(16, 'database') . ' SQL-Backup der betroffenen Zeilen</h2>';
                        echo '<p class="sectionDescription">Sichern Sie die betroffenen Datenbankzeilen, bevor die Deinstallation ausgeführt wird.</p>';
                        echo '</header>';
                        echo '<div class="alert alertInfo recovery-uninstall-backup-intro">';
                        echo '<p class="recovery-uninstall-backup-intro__text">';
                        echo '<strong>Backup für packageID = ' . (int) $packageID . '</strong><br>';
                        echo '<span class="recovery-panel__hint">Enthält alle Zeilen aus den ausgewählten Tabellen – bitte vor dem Ausführen herunterladen.</span>';
                        echo '</p></div>';
                        echo '<div class="recovery-action-bar">';
                        echo '<form method="POST" action="?action=download-sql&amp;t=' . \htmlspecialchars($authHash) . '" id="recoverySqlDownloadForm" class="recovery-download-form">';
                        echo '<input type="hidden" name="sql_b64" value="' . \htmlspecialchars($backupB64) . '">';
                        echo '<button type="submit" class="button buttonPrimary recovery-download-form__submit">';
                        echo recoveryFaIcon(16, 'download') . ' SQL-Backup herunterladen (.sql)</button>';
                        echo '</form></div>';
                        echo '<p class="recovery-panel__hint recovery-uninstall-backup-fallback-hint">Der Download erfolgt lokal im Browser. Falls das fehlschlägt, wird automatisch die Server-Variante verwendet.</p>';
                        echo '<script>(function(){var form=document.getElementById("recoverySqlDownloadForm");';
                        echo 'if(!form||!window.Blob||!window.URL||!URL.createObjectURL){return;}';
                        echo 'form.addEventListener("submit",function(e){e.preventDefault();';
                        echo 'try{var s=atob(' . \json_encode($backupB64) . ');';
                        echo 'var b=new Blob([s],{type:"text/plain;charset=utf-8"});';
                        echo 'var a=document.createElement("a");a.href=URL.createObjectURL(b);';
                        echo 'a.download=' . \json_encode($backupFilename) . ';';
                        echo 'document.body.appendChild(a);a.click();document.body.removeChild(a);';
                        echo 'URL.revokeObjectURL(a.href);}catch(err){form.submit();}});})();</script>';
                        echo '<details class="recovery-panel recovery-uninstall-sql-preview">';
                        echo '<summary>SQL-Inhalt anzeigen (' . $backupSizeLabel . ' Bytes)</summary>';
                        echo '<div class="recovery-panel__body">';
                        echo '<textarea class="recovery-sql-preview" readonly spellcheck="false">';
                        echo \htmlspecialchars(\substr($backupSql, 0, 50000)) . (\strlen($backupSql) > 50000 ? "\n-- [gekürzt …]" : '');
                        echo '</textarea></div></details>';
                        echo '</section>';
                    } else {
                        echo '<div class="alert alertInfo recovery-uninstall-backup-empty">';
                        echo '<p class="recovery-uninstall-backup-intro__text">';
                        echo '<strong>Kein SQL-Backup erforderlich</strong><br>';
                        if (!$packageID) {
                            echo '<span class="recovery-panel__hint">Ohne packageID können keine Zeilen gesichert werden.</span>';
                        } else {
                            echo '<span class="recovery-panel__hint">Keine Zeilen in den ausgewählten Tabellen gefunden.</span>';
                        }
                        echo '</p></div>';
                    }

                    // Zusammenfassung der geplanten Aktionen
                    $plannedLines = [];
                    if ($isDryRun) {
                        $plannedLines[] = 'Dry-Run – keine Änderungen werden vorgenommen';
                    }
                    if (!empty($validPips) && $packageID) {
                        $plannedLines[] = 'DB-Löschungen (WHERE packageID = ' . $packageID . '):';
                        foreach ($validPips as $pip) {
                            $plannedLines[] = '  • wcf' . $wcfN . '_' . $pipMap[$pip]['table'] . ' – ' . $pipMap[$pip]['label'];
                        }
                        $plannedLines[] = '  • wcf' . $wcfN . '_package – Package-Eintrag (ID ' . $packageID . ')';
                        $plannedLines[] = '  • Package-Queue, Requirements, SQL-Log, File-Log';
                    } elseif (empty($validPips)) {
                        $plannedLines[] = 'Keine DB-Kategorien ausgewählt.';
                    }
                    if (!empty($validDropTables)) {
                        $plannedLines[] = 'DROP TABLE:';
                        foreach ($validDropTables as $t) {
                            $plannedLines[] = '  • ' . $t;
                        }
                    }
                    if ($packageID) {
                        $plannedLines[] = 'Uninstall-Script: acp/uninstall/' . $packageIdentifier . '.php '
                            . (\is_file(\rtrim(WCF_DIR, '/\\') . '/acp/uninstall/' . $packageIdentifier . '.php') ? '(vorhanden)' : '(nicht vorhanden)');
                        if ($sqlRollback) {
                            $sp = recoveryPreviewSqlRollback($db, $wcfN, $packageID);
                            $plannedLines[] = 'SQL-Rollback: ' . \count($sp['actions']) . ' Aktion(en)';
                        }
                        if ($rebuildBootstrap) {
                            $plannedLines[] = 'Bootstrap: lib/bootstrap.php wird neu erzeugt';
                        }
                    }
                    if ($deleteFilesLog) {
                        $plannedLines[] = 'File-Log: ' . \count(recoveryLoadPackageFileLogPaths($db, $wcfN, (int)$packageID)) . ' Datei(en)';
                    }
                    if ($deleteFilesDir) {
                        $fsEval2 = recoveryEvaluatePluginDirectoryDeletion($packageData, $packageIdentifier, $db, $wcfN, $extractDir);
                        if ($fsEval2['deletable']) {
                            $plannedLines[] = 'Verzeichnis: ' . (string) $fsEval2['relativePath'] . '/';
                        }
                    }
                    echo '<details class="recovery-panel recovery-planned-actions" open>';
                    echo '<summary>' . recoveryFaIcon(16, 'list-check') . ' Geplante Aktionen';
                    if ($isDryRun) {
                        echo ' <span class="badge badgeYellow">Dry-Run</span>';
                    }
                    echo '</summary>';
                    echo '<div class="recovery-panel__body">';
                    if ($isDryRun) {
                        echo '<div class="recovery-log-hint recovery-planned-actions__dryrun">';
                        echo '<p class="recovery-log-hint__title">' . recoveryFaIcon(16, 'eye') . ' Dry-Run – keine Änderungen</p>';
                        echo '<p class="recovery-log-hint__message">Es werden nur Vorschau-Einträge protokolliert, nichts wird gelöscht.</p>';
                        echo '</div>';
                    }
                    echo '<pre class="recovery-cmd-block recovery-planned-summary">' . \htmlspecialchars(\implode("\n", $plannedLines)) . '</pre>';
                    echo '</div></details>';

                    // Formular mit allen Selektionen als Hidden-Inputs → Step 3 (Execute)
                    echo '<form method="POST" enctype="multipart/form-data" class="recovery-uninstall-execute-form" action="' . \htmlspecialchars(recoveryBuildModeUrl(RECOVERY_MODE_PLUGIN_UNINSTALL, $authHash)) . '">';
                    echo '<input type="hidden" name="mode" value="' . RECOVERY_MODE_PLUGIN_UNINSTALL . '">';
                    echo '<input type="hidden" name="t" value="' . \htmlspecialchars($authHash, ENT_QUOTES, 'UTF-8') . '">';
                    echo '<input type="hidden" name="package_identifier" value="' . \htmlspecialchars($packageIdentifier) . '">';
                    if ($extractDir) {
                        echo '<input type="hidden" name="extract_dir" value="' . \htmlspecialchars($extractDir) . '">';
                    }
                    echo '<input type="hidden" name="uninstall_step" value="2">';
                    if ($isDryRun) {
                        echo '<input type="hidden" name="dry_run" value="1">';
                    }
                    if ($deleteFilesLog) {
                        echo '<input type="hidden" name="delete_files_log" value="1">';
                    }
                    if ($deleteFilesDir) {
                        echo '<input type="hidden" name="delete_files_dir" value="1">';
                    }
                    if ($sqlRollback) {
                        echo '<input type="hidden" name="sql_rollback" value="1">';
                    }
                    if ($rebuildBootstrap) {
                        echo '<input type="hidden" name="rebuild_bootstrap" value="1">';
                    }
                    foreach ($validPips as $pip) {
                        echo '<input type="hidden" name="pip_select[]" value="' . \htmlspecialchars($pip) . '">';
                    }
                    foreach ($validDropTables as $t) {
                        echo '<input type="hidden" name="drop_tables[]" value="' . \htmlspecialchars($t) . '">';
                    }
                    if (!$isDryRun) {
                        echo '<div class="alert alertWarning recovery-uninstall-destructive-hint">';
                        echo '<strong>Destruktive Aktion</strong> – Diese Schritte können nicht rückgängig gemacht werden. Stellen Sie sicher, dass Sie ein Backup haben.';
                        echo '</div>';
                    }
                    $btnLabel = $isDryRun
                        ? recoveryFaIcon(16, 'play') . ' Dry-Run starten'
                        : recoveryFaIcon(16, 'trash-can') . ' Jetzt ausführen (nicht rückgängig!)';
                    $btnClass = $isDryRun ? 'button' : 'button btn-danger';
                    echo '<div class="formSubmit recovery-formSubmit--center recovery-uninstall-execute-actions">';
                    echo '<button type="submit" class="' . $btnClass . '">' . $btnLabel . '</button>';
                    echo '</div></form>';

                // ── SCHRITT 3: AUSFÜHREN ──────────────────────────────────────
                } elseif ($uninstallStep === '2') {
                    $isDryRun      = !empty($_POST['dry_run']) && $_POST['dry_run'] === '1';
                    $selectedPips  = \is_array($_POST['pip_select'] ?? null)  ? (array)$_POST['pip_select']  : [];
                    $dropTables    = \is_array($_POST['drop_tables'] ?? null)  ? (array)$_POST['drop_tables']  : [];
                    $deleteFilesLog = !empty($_POST['delete_files_log']) && $_POST['delete_files_log'] === '1';
                    $deleteFilesDir = !empty($_POST['delete_files_dir']) && $_POST['delete_files_dir'] === '1';
                    $deleteFilesLegacy = !empty($_POST['delete_files']) && $_POST['delete_files'] === '1';
                    $deleteFiles   = $deleteFilesLog || $deleteFilesDir || $deleteFilesLegacy;
                    $sqlRollback   = !empty($_POST['sql_rollback']) && $_POST['sql_rollback'] === '1';
                    $rebuildBootstrap = !isset($_POST['rebuild_bootstrap']) || $_POST['rebuild_bootstrap'] === '1';

                    $pipCtx = recoveryBuildUninstallPipContext($db, $wcfN, $packageID ?: null);
                    $pipMap = $pipCtx['map'];
                    $validPips = \array_values(\array_filter($selectedPips, static fn($p) => isset($pipMap[$p]) && $pipMap[$p]['safe'] && $pipMap[$p]['table'] !== ''));
                    $validDropTables = [];
                    foreach ($dropTables as $t) {
                        $s = \str_replace('`', '', (string)$t);
                        if (recoveryValidateSqlTableName($s)) {
                            $validDropTables[] = $s;
                        }
                    }
?>
    <?php recoveryRenderWizardPhaseSteps(2, ['Analyse & Auswahl', 'Backup', 'Ausführen']); ?>
<?php
                    $log = [];
                    $removalOpts = [
                        'dryRun' => $isDryRun,
                        'sqlRollback' => $sqlRollback,
                        'deleteFilesLog' => $deleteFilesLog || ($deleteFilesLegacy && !$deleteFilesDir),
                        'deleteFilesDir' => $deleteFilesDir || $deleteFilesLegacy,
                        'rebuildBootstrap' => $rebuildBootstrap,
                        'runUninstallScript' => true,
                    ];

                    try {
                        recoveryRunPreDbRemovalSteps(
                            $db,
                            $wcfN,
                            $packageIdentifier,
                            $packageID ?: null,
                            $removalOpts,
                            $log
                        );

                        // ── DB-Bereinigung nach packageID ─────────────────────
                        if ($packageID && !empty($validPips)) {
                            foreach ($validPips as $pip) {
                                $info = $pipMap[$pip];
                                if ($isDryRun) {
                                    try {
                                        $st = $db->prepareStatement("SELECT COUNT(*) AS cnt FROM wcf{$wcfN}_{$info['table']} WHERE packageID = ?");
                                        $st->execute([$packageID]);
                                        $r = $st->fetchArray();
                                        $log[] = '[DRY-RUN] WÜRDE LÖSCHEN: wcf' . $wcfN . '_' . $info['table'] . ' – ' . (int)($r['cnt'] ?? 0) . ' Einträge';
                                    } catch (\Throwable $e) {
                                        $log[] = '[DRY-RUN] ' . $info['label'] . ': Tabelle nicht vorhanden';
                                    }
                                } else {
                                    recoveryTryDeleteByPackageId($db, $wcfN, $info['table'], $packageID, $info['label'], $log);
                                }
                            }
                        }

                        // ── Package-Infrastruktur ─────────────────────────────
                        if ($packageID) {
                            if ($isDryRun) {
                                $log[] = '[DRY-RUN] WÜRDE LÖSCHEN: Package-Queue, Nodes, Forms, Requirements, SQL-Log, Package-Eintrag';
                            } else {
                                recoveryCleanupPackageInstallationArtifacts($db, $wcfN, $packageID, $packageIdentifier, $log);
                                recoveryCleanupPackageUpdateEntries($db, $wcfN, $packageIdentifier, $log);
                                recoveryTryDeletePackageRequirements($db, $wcfN, $packageID, $log);
                                if (!$sqlRollback) {
                                    recoveryTryExecuteDelete(
                                        $db,
                                        "DELETE FROM wcf{$wcfN}_package_installation_sql_log WHERE packageID = ?",
                                        [$packageID],
                                        'Package SQL-Log',
                                        $log
                                    );
                                }
                                recoveryTryExecuteDelete(
                                    $db,
                                    "DELETE FROM wcf{$wcfN}_package WHERE packageID = ?",
                                    [$packageID],
                                    'Package-Eintrag',
                                    $log
                                );
                                recoveryTryDeleteByPackageId($db, $wcfN, 'application', $packageID, 'Application (Pflicht)', $log);
                                try {
                                    $orphanFix = recoveryRepairOrphanedPackageReferences($db, $wcfN);
                                    foreach ($orphanFix['log'] as $orphanLine) {
                                        $log[] = 'Nachbereinigung: ' . $orphanLine;
                                    }
                                } catch (\Throwable $orphanErr) {
                                    $log[] = 'Nachbereinigung übersprungen: ' . $orphanErr->getMessage();
                                }
                            }
                        }

                        // ── Plugin-eigene Tabellen droppen ────────────────────
                        foreach ($validDropTables as $table) {
                            if ($isDryRun) {
                                $log[] = '[DRY-RUN] WÜRDE DROP TABLE: ' . $table;
                            } else {
                                try {
                                    $stmt = $db->prepareStatement('DROP TABLE IF EXISTS `' . $table . '`');
                                    $stmt->execute();
                                    $log[] = 'Tabelle gelöscht: ' . $table;
                                } catch (\Throwable $e) {
                                    $log[] = 'DROP TABLE fehlgeschlagen (' . $table . '): ' . $e->getMessage();
                                }
                            }
                        }

                        recoveryRunPostDbRemovalSteps(
                            $db,
                            $wcfN,
                            $packageIdentifier,
                            $packageData,
                            $packageID ?: null,
                            $removalOpts,
                            $log,
                            $extractDir
                        );

                        // ── options.inc.php + Cache ───────────────────────────
                        if (!$isDryRun) {
                            $optionConstants = recoveryCollectOptionConstantNames($db, $wcfN, $packageID);
                            if (recoveryRebuildOptionsIncPhp()) {
                                $log[] = 'options.inc.php neu erzeugt';
                            } elseif (!empty($optionConstants)) {
                                recoveryStripConstantsFromOptionsIncPhp($optionConstants);
                                $log[] = 'options.inc.php bereinigt (' . \count($optionConstants) . ' Konstanten entfernt)';
                            }
                            recoveryEnsureOptionConstantFallbacks($db, $wcfN, $log);
                            $deletedCacheFiles = clearCompiledTemplates();
                            $log[] = 'Cache gelöscht: ' . $deletedCacheFiles . ' Dateien';
                            recoveryCleanupUploadWorkspace();
                        }

                        // ── Ergebnis anzeigen ─────────────────────────────────
                        $resultClass = $isDryRun ? 'alert-warning' : 'alert-success';
                        echo '<p class="' . $resultClass . '" recovery-grid--mb">';
                        echo '<strong>' . ($isDryRun ? '&#128065; Dry-Run abgeschlossen – keine Änderungen vorgenommen' : '&#10003; Plugin-Bereinigung abgeschlossen!') . '</strong><br><br>';
                        echo '</p><details class="recovery-panel" open><summary><strong>Protokoll</strong></summary>';
                        echo '<div class="recovery-panel__body"><pre class="recovery-cmd-block recovery-log-pre--medium">';
                        foreach ($log as $entry) {
                            echo \htmlspecialchars($entry) . "\n";
                        }
                        echo '</pre></div></details>';

                        if (!$isDryRun) {
                            recoveryRenderAcpCacheClearHint($authHash);
                        }

                    } catch (\Throwable $e) {
                        echo '<p class="error">';
                        echo '<strong>Fehler bei Deinstallation:</strong><br>';
                        echo \nl2br(\htmlspecialchars(recoveryFormatUserError($e)));
                        recoveryRenderExceptionDetails($e);
                        echo '</p>';
                    }
                } else {
                    echo '<p class="error"><strong>Fehler:</strong> Unbekannter Wizard-Schritt (uninstall_step='
                        . \htmlspecialchars($uninstallStep) . ').</p>';
                }
            }
        }
        } catch (\Throwable $e) {
            recoveryRenderProcessingError($e);
        }
        echo '<script>var o=document.getElementById("recovery-loading-overlay");if(o){o.style.display="none";}</script>';
        recoveryRenderActionBar([
            '<a href="' . \htmlspecialchars($uninstallModeUrl) . '" class="button">' . recoveryFaIcon(16, 'arrow-left') . ' Neue Analyse starten</a>',
        ]);
    }
}

// ============================================================================
// MODUS 3: USER MANAGEMENT
// ============================================================================

