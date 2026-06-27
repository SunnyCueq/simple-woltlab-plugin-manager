<?php
/** Recovery mode: acp_repair — included by lib/Recovery/router.php */
declare(strict_types=1);

if ($mode === RECOVERY_MODE_ACP_REPAIR) {
?>
    <h1>ACP Repair</h1>
    <p class="subtitle">Repariert defekte ACP-Menüeinträge eines Plugins</p>

<?php
    if (recoveryWasPostTruncated()) {
        recoveryRenderPostTruncatedWarning();
    }

    $acpModeUrl = recoveryBuildModeUrl(RECOVERY_MODE_ACP_REPAIR, $authHash);

    if (recoveryAcpShouldShowInputForm()) {
?>
    <form method="POST" enctype="multipart/form-data" action="<?= \htmlspecialchars($acpModeUrl) ?>" data-recovery-loading="Paket wird analysiert …">
        <?php recoveryRenderFormModeHiddenFields(RECOVERY_MODE_ACP_REPAIR, $authHash); ?>
        <section class="section">
            <header class="sectionHeader"><h2 class="sectionTitle">Option 1: Package-Identifier</h2></header>
            <div class="form-group">
                <label for="acp_package_identifier">Package-Identifier</label>
                <input type="text" id="acp_package_identifier" name="package_identifier" placeholder="z.B. de.example.my-plugin" autocomplete="off">
                <small class="recovery-form-hint-inline">
                    Der eindeutige Bezeichner des Plugins, dessen ACP-Menüeinträge repariert werden sollen.
                </small>
            </div>
            <?php
            recoveryRenderActionBar([
                '<button type="submit" class="button buttonPrimary">' . recoveryFaIcon(16, 'wrench') . ' Mit Identifier reparieren</button>',
            ]);
            ?>
        </section>
    </form>

    <section class="section">
        <header class="sectionHeader"><h2 class="sectionTitle">Option 2: Package-Datei hochladen</h2></header>
        <form method="POST" enctype="multipart/form-data" action="<?= \htmlspecialchars($acpModeUrl) ?>" data-recovery-loading="Paket wird hochgeladen und analysiert …">
            <?php recoveryRenderFormModeHiddenFields(RECOVERY_MODE_ACP_REPAIR, $authHash); ?>
            <div class="form-group">
                <label for="acp_package_file">Package-Datei (.tar, .tar.gz, .tgz – max. 100 MiB)</label>
                <input type="file" id="acp_package_file" name="package_file" accept=".tar,.tar.gz,.tgz" required>
            </div>
            <?php
            recoveryRenderActionBar([
                '<button type="submit" class="button buttonPrimary">' . recoveryFaIcon(16, 'wrench') . ' Mit Datei reparieren</button>',
            ]);
            ?>
        </form>
    </section>
<?php
    } else {
        echo '<div id="recovery-loading-overlay" class="recovery-loading recovery-loading--visible">Paket wird analysiert …</div>';
        try {
            $packageInput = recoveryResolvePackageInputFromRequest($authHash);
            if (isset($packageInput['error'])) {
                recoveryRenderAlert('error', (string) $packageInput['error']);
            }

            $packageIdentifier = $packageInput['packageIdentifier'] ?? null;
            $extractDir = $packageInput['extractDir'] ?? recoveryResolveTrustedExtractDir();

            if ($packageIdentifier) {
                $resources = null;
                if ($extractDir && \is_dir($extractDir)) {
                    $resources = analyzePackageResources($extractDir, $packageIdentifier, $db);
                    if ($resources && !empty($resources['acpMenu']['prefix'])) {
                        displayResourcePreview($resources, $resources['wcfN'], $packageIdentifier);
                    }
                }

                $sql = "SELECT packageID, package, packageName, packageDir, isApplication
                        FROM wcf" . WCF_N . "_package
                        WHERE package = ?";
                $statement = $db->prepareStatement($sql);
                $statement->execute([$packageIdentifier]);
                $packageData = $statement->fetchArray();

                $wcfN = $resources ? (int) $resources['wcfN'] : WCF_N;

                if (!$packageData && !isset($_POST['force_cleanup'])) {
                    $menuItems = recoveryFetchAcpMenuItemsForPackage($db, $wcfN, $packageIdentifier, null, $resources);
                    $foundPatterns = recoveryInferAcpMenuSearchPatterns($packageIdentifier, $resources);
                    $menuCount = \count($menuItems);

                    if ($menuCount > 0) {
                        $warnBody = 'Plugin nicht in Datenbank gefunden — Installation evtl. fehlgeschlagen.<br><br>';
                        if ($foundPatterns !== []) {
                            $warnBody .= '<small>Suchmuster: ' . \htmlspecialchars(\implode(', ', $foundPatterns)) . '</small><br><br>';
                        }
                        $warnBody .= '<strong>Gefundene ACP-Menüeinträge (' . $menuCount . '):</strong>';
                        recoveryRenderAlert('warning', $warnBody, 'Warnung', true);

                        echo '<table class="tableList recovery-table-list"><thead><tr><th>Menu Item</th><th>Controller</th></tr></thead><tbody>';
                        foreach ($menuItems as $item) {
                            echo '<tr><td>' . \htmlspecialchars($item['menuItem']) . '</td>';
                            echo '<td>' . \htmlspecialchars($item['menuItemController'] ?: '-') . '</td></tr>';
                        }
                        echo '</tbody></table>';

                        echo '<form method="POST" enctype="multipart/form-data" action="' . \htmlspecialchars($acpModeUrl) . '">';
                        recoveryRenderFormModeHiddenFields(RECOVERY_MODE_ACP_REPAIR, $authHash);
                        echo '<input type="hidden" name="package_identifier" value="' . \htmlspecialchars($packageIdentifier) . '">';
                        if ($extractDir) {
                            echo '<input type="hidden" name="extract_dir" value="' . \htmlspecialchars($extractDir) . '">';
                        }
                        echo '<input type="hidden" name="force_cleanup" value="1">';
                        recoveryRenderActionBar([
                            '<button type="submit" class="button buttonPrimary">' . recoveryFaIcon(16, 'trash-can') . ' Diese ' . $menuCount . ' Menüeinträge löschen</button>',
                        ]);
                        echo '</form>';
                    } else {
                        recoveryRenderAlert(
                            'info',
                            'Keine ACP-Menüeinträge mit den ermittelten Mustern gefunden — es gibt nichts zu bereinigen.'
                        );
                    }
                } else {
                    $menuItems = recoveryFetchAcpMenuItemsForPackage($db, $wcfN, $packageIdentifier, $packageData ?: null, $resources);

                    if (empty($menuItems)) {
                        recoveryRenderAlert(
                            'info',
                            'Für dieses Plugin existieren keine ACP-Menüeinträge in der Datenbank.',
                            'Keine ACP-Menüeinträge gefunden'
                        );
                    } elseif (!isset($_POST['confirm_delete'])) {
                        recoveryRenderAlert(
                            'info',
                            '<strong>' . \count($menuItems) . ' Einträge</strong> werden beim Bestätigen gelöscht.',
                            'Gefundene ACP-Menüeinträge',
                            true
                        );
                        echo '<table class="tableList recovery-table-list"><thead><tr><th>Menu Item</th><th>Controller</th></tr></thead><tbody>';
                        foreach ($menuItems as $item) {
                            echo '<tr><td>' . \htmlspecialchars($item['menuItem']) . '</td>';
                            echo '<td>' . \htmlspecialchars($item['menuItemController'] ?: '-') . '</td></tr>';
                        }
                        echo '</tbody></table>';

                        echo '<form method="POST" enctype="multipart/form-data" action="' . \htmlspecialchars($acpModeUrl) . '">';
                        recoveryRenderFormModeHiddenFields(RECOVERY_MODE_ACP_REPAIR, $authHash);
                        echo '<input type="hidden" name="package_identifier" value="' . \htmlspecialchars($packageIdentifier) . '">';
                        if ($extractDir) {
                            echo '<input type="hidden" name="extract_dir" value="' . \htmlspecialchars($extractDir) . '">';
                        }
                        if (!$packageData) {
                            echo '<input type="hidden" name="force_cleanup" value="1">';
                        }
                        echo '<input type="hidden" name="confirm_delete" value="1">';
                        recoveryRenderActionBar([
                            '<button type="submit" class="button buttonPrimary">' . recoveryFaIcon(16, 'trash-can') . ' Alle löschen</button>',
                        ]);
                        echo '</form>';
                    } else {
                        $extractDir = recoveryResolveTrustedExtractDir();
                        if ($extractDir && \is_dir($extractDir)) {
                            $resources = analyzePackageResources($extractDir, $packageIdentifier, $db);
                        }
                        $wcfN = $resources ? (int) $resources['wcfN'] : WCF_N;

                        $db->beginTransaction();
                        try {
                            $deletedCount = recoveryDeleteAcpMenuItemsForPackage(
                                $db,
                                $wcfN,
                                $packageIdentifier,
                                $packageData ?: null,
                                $resources
                            );
                            clearCompiledTemplates();
                            $optionFbLog = [];
                            recoveryEnsureOptionConstantFallbacks($db, $wcfN, $optionFbLog);
                            $db->commitTransaction();
                            recoveryCleanupUploadWorkspace();

                            $successBody = 'Gelöschte Menüeinträge: <strong>' . $deletedCount . '</strong><br>Cache wurde geleert.';
                            foreach ($optionFbLog as $fbEntry) {
                                $successBody .= '<br>' . \htmlspecialchars($fbEntry);
                            }
                            recoveryRenderAlert('success', $successBody, 'ACP-Repair erfolgreich', true);
                        } catch (\Throwable $e) {
                            recoverySafeRollBackTransaction($db);
                            recoveryRenderAlert('error', recoveryFormatUserError($e), 'Fehler');
                            recoveryRenderExceptionDetails($e);
                        }
                    }
                }
            } else {
                recoveryRenderAlert('error', 'Kein Package-Identifier konnte ermittelt werden. Bitte versuchen Sie es erneut.');
            }
        } catch (\Throwable $e) {
            recoveryRenderProcessingError($e);
        }
        echo '<script>var o=document.getElementById("recovery-loading-overlay");if(o){o.classList.remove("recovery-loading--visible");o.hidden=true;}</script>';
        recoveryRenderActionBar([
            '<a href="' . \htmlspecialchars($acpModeUrl) . '" class="button">' . recoveryFaIcon(16, 'arrow-left') . ' Neue Analyse starten</a>',
        ]);
    }
}
