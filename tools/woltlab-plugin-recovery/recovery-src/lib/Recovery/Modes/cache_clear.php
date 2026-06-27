<?php
/** Recovery mode: cache_clear — included by lib/Recovery/router.php */
declare(strict_types=1);

if ($mode === RECOVERY_MODE_CACHE_CLEAR) {
    $returnWizard = isset($_GET['return']) && $_GET['return'] === 'wizard';
    $wizardDoneUrl = recoveryBuildModeUrl(RECOVERY_MODE_RECOVERY_WIZARD, $authHash, ['wizard_phase' => 'done']);
    $nativeAvailable = recoveryIsNativeWcfCacheClearAvailable();
?>
<?php
    if (!isset($_POST['confirm_clear'])) {
?>
    <?php if (recoveryUsesNativeAcpUi()): ?>
    <?php
    recoveryRenderAlert(
        'info',
        'Leert kompilierte Templates und Cache-Verzeichnisse. Anschließend werden fehlende Option-<code>define()</code>s '
        . 'aus der Datenbank aller Plugins in <code>options.inc.php</code> nachgetragen (Schutz vor „Undefined constant“ im ACP).',
        null,
        true
    );
    ?>
    <?php else: ?>
    <p class="recovery-page-intro">
        Leert kompilierte Templates und Cache-Verzeichnisse. Anschließend werden fehlende Option-<code>define()</code>s
        aus der Datenbank aller Plugins in <code>options.inc.php</code> nachgetragen (Schutz vor „Undefined constant“ im ACP).
    </p>
    <?php endif; ?>

    <?php if ($nativeAvailable): ?>
    <?php
    recoveryRenderAlert(
        'info',
        'Wenn WoltLab-Klassen ladbar sind, nutzt das Tool dieselbe Cache-API wie das ACP '
        . '(<strong>Wartung → Cache leeren</strong>: <code>CacheHandler</code>, <code>TemplateEngine</code>). '
        . 'Zusätzlich wird <code>tmp/</code> per Dateisystem geleert.<br><br>'
        . 'Das ACP selbst wird <em>nicht</em> aufgerufen — bei defekten Plugins ist es oft nicht erreichbar. '
        . 'Der Menüpunkt „Daten aktualisieren“ im ACP bleibt für den regulären Betrieb gedacht.',
        'WoltLab-Cache-API',
        true
    );
    ?>
    <?php else: ?>
    <?php
    recoveryRenderAlert(
        'warning',
        'Das ACP ist in Notfällen oft nicht erreichbar (defektes Plugin, Fatal Error). '
        . 'Deshalb kann das Recovery Tool den ACP-Menüpunkt „Daten aktualisieren“ nicht nutzen — '
        . 'ein Link dorthin würde ohnehin ins Leere führen.<br><br>'
        . 'Stattdessen werden folgende Verzeichnisse direkt geleert: '
        . '<code>tmp/</code>, <code>cache/</code>, <code>templates/compiled/</code>, '
        . '<code>acp/templates/compiled/</code> sowie bei installierten Apps deren '
        . '<code>templates/compiled/</code>-Ordner.',
        'Manueller Fallback',
        true
    );
    ?>
    <?php endif; ?>

    <form method="POST">
        <?php recoveryRenderFormModeHiddenFields(RECOVERY_MODE_CACHE_CLEAR, $authHash); ?>
        <?php if ($returnWizard): ?>
        <input type="hidden" name="return" value="wizard">
        <?php endif; ?>
        <input type="hidden" name="confirm_clear" value="1">
        <?php
        $actions = [
            '<button type="submit" class="button buttonPrimary">' . recoveryFaIcon(16, 'broom') . ' Cache jetzt löschen</button>',
        ];
        if ($returnWizard) {
            $actions[] = '<a href="' . \htmlspecialchars($wizardDoneUrl) . '" class="button">' . recoveryFaIcon(16, 'arrow-left') . ' Zurück zur Zusammenfassung</a>';
        } else {
            $actions[] = '<a href="' . \htmlspecialchars(recoveryHomeUrl($authHash)) . '" class="button">' . recoveryFaIcon(16, 'house') . ' Zurück zum Start</a>';
        }
        recoveryRenderActionBar($actions);
        ?>
    </form>
<?php
    } else {
        $clearResult = recoveryClearAllCaches();
        $optionFbLog = [];
        recoveryEnsureOptionConstantFallbacks($db, WCF_N, $optionFbLog);
        $returnAfter = isset($_POST['return']) && $_POST['return'] === 'wizard';
        $methodLabel = ($clearResult['method'] ?? '') === 'native'
            ? ' (WoltLab-Cache-API)'
            : ' (manueller Fallback)';
        $logBody = 'Gelöschte Dateien: <strong>' . (int) $clearResult['deleted'] . '</strong>' . $methodLabel;
        foreach (\array_merge($clearResult['log'], $optionFbLog) as $fbEntry) {
            $logBody .= '<br>' . \htmlspecialchars((string) $fbEntry, ENT_QUOTES, 'UTF-8');
        }
        recoveryRenderAlert('success', $logBody, 'Cache erfolgreich geleert', true);
        $actions = [];
        if ($returnAfter) {
            $actions[] = '<a href="' . \htmlspecialchars($wizardDoneUrl) . '" class="button buttonPrimary">' . recoveryFaIcon(16, 'arrow-left') . ' Zurück zur Zusammenfassung</a>';
        } else {
            $actions[] = '<a href="' . \htmlspecialchars(recoveryHomeUrl($authHash)) . '" class="button buttonPrimary">' . recoveryFaIcon(16, 'house') . ' Zurück zum Start</a>';
        }
        recoveryRenderActionBar($actions);
    }
}
