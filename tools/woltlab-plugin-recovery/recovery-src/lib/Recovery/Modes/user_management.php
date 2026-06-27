<?php
/** Recovery mode: user_management — included by lib/Recovery/router.php */
declare(strict_types=1);

if ($mode === RECOVERY_MODE_USER_MANAGEMENT) {
    $umBaseUrl = recoveryBuildModeUrl(RECOVERY_MODE_USER_MANAGEMENT, $authHash);
    $umUid     = isset($_GET['um_uid']) ? (int)$_GET['um_uid'] : 0;
    $umMessages = [];
    $umErrors   = [];

    if ($umUid > 0 && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $umAction = $_POST['um_action'] ?? '';
        try {
            switch ($umAction) {
                case 'reset_password':
                    $newPwd = recoveryUserGenerateRandomPassword();
                    recoveryUserResetPassword($db, $umUid, $newPwd);
                    $umMessages[] = 'Passwort wurde auf <code>' . \htmlspecialchars($newPwd) . '</code> gesetzt. Bitte sofort notieren!';
                    break;

                case 'reset_password_custom':
                    $customPwd = \trim($_POST['custom_password'] ?? '');
                    if ($customPwd === '') {
                        $umErrors[] = 'Bitte ein Passwort eingeben.';
                    } elseif (\strlen($customPwd) < 8) {
                        $umErrors[] = 'Passwort muss mindestens 8 Zeichen lang sein.';
                    } else {
                        recoveryUserResetPassword($db, $umUid, $customPwd);
                        $umMessages[] = 'Passwort wurde erfolgreich gesetzt.';
                    }
                    break;

                case 'set_groups':
                    $groupIDs = isset($_POST['group_ids']) && \is_array($_POST['group_ids'])
                        ? \array_map('intval', $_POST['group_ids'])
                        : [];
                    recoveryUserSetGroups($db, $umUid, $groupIDs);
                    $umMessages[] = 'Gruppenmitgliedschaften wurden aktualisiert.';
                    break;

                case 'add_admin':
                    $currentGIDs = recoveryUserGetGroupIDs($db, $umUid);
                    if (!\in_array(4, $currentGIDs, true)) {
                        $currentGIDs[] = 4;
                        recoveryUserSetGroups($db, $umUid, $currentGIDs);
                        $umMessages[] = 'Benutzer wurde zur Administrator-Gruppe (ID&nbsp;4) hinzugefügt.';
                    } else {
                        $umMessages[] = 'Benutzer ist bereits in der Administrator-Gruppe.';
                    }
                    break;

                case 'change_email':
                    $newEmail = \trim($_POST['new_email'] ?? '');
                    if ($newEmail === '' || !\filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
                        $umErrors[] = 'Bitte eine gültige E-Mail-Adresse eingeben.';
                    } else {
                        recoveryUserChangeEmail($db, $umUid, $newEmail);
                        $umMessages[] = 'E-Mail-Adresse auf <code>' . \htmlspecialchars($newEmail) . '</code> geändert.';
                    }
                    break;

                case 'activate':
                    recoveryUserActivate($db, $umUid);
                    $umMessages[] = 'Benutzer wurde aktiviert und Sperre aufgehoben.';
                    break;

                case 'disable_2fa':
                    recoveryUserDisable2FA($db, $umUid);
                    $umMessages[] = 'Zwei-Faktor-Authentifizierung wurde deaktiviert und alle 2FA-Setups gelöscht.';
                    break;
            }
        } catch (\Throwable $e) {
            $umErrors[] = 'Fehler: ' . \htmlspecialchars(recoveryFormatUserError($e));
            recoveryRenderExceptionDetails($e);
        }
    }
?>
    <h1>User Management</h1>
    <p class="subtitle">Benutzersuche, Passwort-Reset, Gruppen, E-Mail &amp; Kontoverwaltung</p>

<?php if ($umUid > 0):
    $umUser = recoveryUserGetByID($db, $umUid);
    if ($umUser === null):
        recoveryRenderAlert('error', 'Benutzer mit ID <code>' . (int) $umUid . '</code> nicht gefunden.');
        recoveryRenderActionBar([
            '<a href="' . \htmlspecialchars($umBaseUrl) . '" class="button">' . recoveryFaIcon(16, 'arrow-left') . ' Zurück zur Suche</a>',
        ]);
    else:
        $currentGroupIDs = recoveryUserGetGroupIDs($db, (int)$umUser['userID']);
?>
    <?php
    recoveryRenderActionBar([
        '<a href="' . \htmlspecialchars($umBaseUrl) . '" class="button">' . recoveryFaIcon(16, 'arrow-left') . ' Anderen Benutzer suchen</a>',
    ]);
    ?>

    <h2>Benutzer: <code><?= \htmlspecialchars($umUser['username']) ?></code>
        <span class="recovery-um-meta">(ID&nbsp;<?= (int)$umUser['userID'] ?>)</span>
    </h2>

    <table class="tableList recovery-table-list recovery-um-detail-table">
        <tbody>
            <tr><th>Benutzername</th><td><?= \htmlspecialchars($umUser['username']) ?></td></tr>
            <tr><th>E-Mail</th><td><?= \htmlspecialchars($umUser['email']) ?></td></tr>
            <tr><th>Status</th><td>
                <?php if ($umUser['banned']): ?>
                    <span class="recovery-um-status--banned">&#9632; Gesperrt</span>
                <?php elseif ($umUser['activationCode'] != 0): ?>
                    <span class="recovery-um-status--pending">&#9632; Aktivierung ausstehend</span>
                <?php else: ?>
                    <span class="recovery-um-status--active">&#9632; Aktiv</span>
                <?php endif; ?>
            </td></tr>
            <tr><th>2FA</th><td><?= $umUser['multifactorActive'] ? '<span class="recovery-um-status--pending">Aktiv</span>' : '<span class="recovery-um-status--muted">Inaktiv</span>' ?></td></tr>
            <tr><th>Gruppen</th><td><?= \implode(', ', $currentGroupIDs) ?></td></tr>
        </tbody>
    </table>

    <?php foreach ($umErrors as $err): ?>
    <?php recoveryRenderAlert('error', $err); ?>
    <?php endforeach; ?>
    <?php foreach ($umMessages as $msg): ?>
    <?php recoveryRenderAlert('success', $msg, null, true); ?>
    <?php endforeach; ?>

    <section class="section">
        <header class="sectionHeader"><h2 class="sectionTitle"><?= recoveryFaIcon(16, 'key') ?> Passwort zurücksetzen</h2></header>
        <p class="recovery-um-hint">Wie im <a href="https://manual.woltlab.com/de/recovery-tool/" target="_blank" rel="noopener">offiziellen WoltLab Recovery Tool</a>: zufälliges Passwort bestätigen oder ein eigenes setzen.</p>
        <div class="recovery-option-cards">
            <div class="recovery-option-card recovery-card">
                <h3><?= recoveryFaIcon(16, 'dice') ?> Zufälliges Passwort</h3>
                <p class="recovery-um-hint">Wird nach dem Setzen <strong>einmalig</strong> angezeigt – bitte sofort notieren.</p>
                <form method="POST" action="<?= \htmlspecialchars($umBaseUrl) ?>&amp;um_uid=<?= (int)$umUser['userID'] ?>">
                    <input type="hidden" name="um_action" value="reset_password">
                    <button type="submit" class="button buttonPrimary"><?= recoveryFaIcon(16, 'key') ?> Zufälliges Passwort setzen</button>
                </form>
            </div>
            <div class="recovery-option-card recovery-card">
                <h3><?= recoveryFaIcon(16, 'pen') ?> Eigenes Passwort</h3>
                <form method="POST" action="<?= \htmlspecialchars($umBaseUrl) ?>&amp;um_uid=<?= (int)$umUser['userID'] ?>">
                    <input type="hidden" name="um_action" value="reset_password_custom">
                    <div class="form-group">
                        <label for="um_custom_pwd">Neues Passwort (min. 8 Zeichen)</label>
                        <input type="password" id="um_custom_pwd" name="custom_password" autocomplete="new-password" placeholder="Passwort eingeben">
                    </div>
                    <button type="submit" class="button buttonPrimary"><?= recoveryFaIcon(16, 'key') ?> Passwort setzen</button>
                </form>
            </div>
        </div>
    </section>

    <section class="section">
        <header class="sectionHeader"><h2 class="sectionTitle"><?= recoveryFaIcon(16, 'envelope') ?> E-Mail-Adresse ändern</h2></header>
        <form method="POST" action="<?= \htmlspecialchars($umBaseUrl) ?>&amp;um_uid=<?= (int)$umUser['userID'] ?>">
            <input type="hidden" name="um_action" value="change_email">
            <div class="form-group">
                <label for="um_email">Neue E-Mail-Adresse</label>
                <input type="text" id="um_email" name="new_email" value="<?= \htmlspecialchars($umUser['email']) ?>" placeholder="neue@email.de">
            </div>
            <?php recoveryRenderActionBar(['<button type="submit" class="button buttonPrimary">' . recoveryFaIcon(16, 'envelope') . ' E-Mail ändern</button>']); ?>
        </form>
    </section>

    <?php if ($umUser['banned'] || $umUser['activationCode'] != 0): ?>
    <section class="section">
        <header class="sectionHeader"><h2 class="sectionTitle"><?= recoveryFaIcon(16, 'user') ?> Konto aktivieren &amp; Sperre aufheben</h2></header>
        <p class="recovery-um-hint recovery-um-hint--tight">
            Setzt <code>activationCode&nbsp;=&nbsp;0</code>, <code>banned&nbsp;=&nbsp;0</code> und löscht den Sperr-Grund.
        </p>
        <form method="POST" action="<?= \htmlspecialchars($umBaseUrl) ?>&amp;um_uid=<?= (int)$umUser['userID'] ?>">
            <input type="hidden" name="um_action" value="activate">
            <?php recoveryRenderActionBar(['<button type="submit" class="button buttonPrimary">' . recoveryFaIcon(16, 'circle-check') . ' Benutzer aktivieren &amp; entsperren</button>']); ?>
        </form>
    </section>
    <?php endif; ?>

    <?php if ($umUser['multifactorActive']): ?>
    <section class="section">
        <header class="sectionHeader"><h2 class="sectionTitle"><?= recoveryFaIcon(16, 'shield-halved') ?> Zwei-Faktor-Authentifizierung deaktivieren</h2></header>
        <p class="recovery-um-hint recovery-um-hint--tight">
            Löscht alle 2FA-Setups (inkl. Backup-Codes) und setzt <code>multifactorActive&nbsp;=&nbsp;0</code>.
        </p>
        <form method="POST" action="<?= \htmlspecialchars($umBaseUrl) ?>&amp;um_uid=<?= (int)$umUser['userID'] ?>">
            <input type="hidden" name="um_action" value="disable_2fa">
            <?php recoveryRenderActionBar(['<button type="submit" class="button">' . recoveryFaIcon(16, 'shield-halved') . ' 2FA deaktivieren</button>']); ?>
        </form>
    </section>
    <?php endif; ?>

    <section class="section">
        <header class="sectionHeader"><h2 class="sectionTitle"><?= recoveryFaIcon(16, 'users-gear') ?> Administrator-Gruppe (ID&nbsp;4)</h2></header>
        <?php if (\in_array(4, $currentGroupIDs, true)): ?>
        <?php recoveryRenderAlert('info', 'Benutzer ist bereits in der Administrator-Gruppe (ID&nbsp;4).'); ?>
        <?php else: ?>
        <p class="recovery-um-hint recovery-um-hint--tight">
            Fügt den Benutzer direkt zur WoltLab-Standard-Administrator-Gruppe (groupID&nbsp;4) hinzu.
        </p>
        <form method="POST" action="<?= \htmlspecialchars($umBaseUrl) ?>&amp;um_uid=<?= (int)$umUser['userID'] ?>">
            <input type="hidden" name="um_action" value="add_admin">
            <?php recoveryRenderActionBar(['<button type="submit" class="button buttonPrimary">' . recoveryFaIcon(16, 'users-gear') . ' Zur Administrator-Gruppe hinzufügen</button>']); ?>
        </form>
        <?php endif; ?>
    </section>

    <section class="section">
        <header class="sectionHeader"><h2 class="sectionTitle"><?= recoveryFaIcon(16, 'sliders') ?> Alle Gruppen verwalten</h2></header>
        <?php $allGroups = recoveryUserGetAllGroups($db); ?>
        <form method="POST" action="<?= \htmlspecialchars($umBaseUrl) ?>&amp;um_uid=<?= (int)$umUser['userID'] ?>">
            <input type="hidden" name="um_action" value="set_groups">
            <table class="tableList recovery-table-list recovery-um-groups-table">
                <thead>
                    <tr>
                        <th class="recovery-col-check"></th>
                        <th class="recovery-col-id">ID</th>
                        <th>Gruppe</th>
                        <th class="recovery-col-type">Typ</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($allGroups as $grp):
                    $gid      = (int)$grp['groupID'];
                    $isSystem = \in_array($gid, [1, 2], true);
                    $isMember = \in_array($gid, $currentGroupIDs, true);
                    $groupType = (int) $grp['groupType'];
                    $gType = match ($groupType) {
                        1 => 'System',
                        4 => 'Admin',
                        default => 'Normal',
                    };
                ?>
                    <tr>
                        <td class="recovery-col-check">
                            <input type="checkbox" name="group_ids[]" id="grp_<?= $gid ?>"
                                value="<?= $gid ?>"
                                <?= $isMember ? 'checked' : '' ?>
                                <?= $isSystem ? 'disabled' : '' ?>>
                            <?php if ($isSystem): ?>
                            <input type="hidden" name="group_ids[]" value="<?= $gid ?>">
                            <?php endif; ?>
                        </td>
                        <td><?= $gid ?></td>
                        <td><label for="grp_<?= $gid ?>"><?= recoveryFormatUserGroupLabel($gid, (string) $grp['groupName']) ?></label></td>
                        <td><small><?= $gType ?></small></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php recoveryRenderActionBar(['<button type="submit" class="button buttonPrimary">' . recoveryFaIcon(16, 'sliders') . ' Gruppen speichern</button>']); ?>
        </form>
    </section>

<?php endif;

else:

    foreach ($umErrors as $err) {
        recoveryRenderAlert('error', $err);
    }
    foreach ($umMessages as $msg) {
        recoveryRenderAlert('success', $msg, null, true);
    }
?>
    <section class="section">
        <header class="sectionHeader"><h2 class="sectionTitle"><?= recoveryFaIcon(16, 'magnifying-glass') ?> Benutzer suchen</h2></header>
        <form method="POST" action="<?= \htmlspecialchars($umBaseUrl) ?>">
            <div class="form-group">
                <label for="um_search">Benutzername oder E-Mail (Präfix-Suche, max. 50 Treffer)</label>
                <input type="text" id="um_search" name="um_search"
                    value="<?= \htmlspecialchars($_POST['um_search'] ?? '') ?>"
                    placeholder="z.&thinsp;B. Admin oder admin@example.com" autofocus>
            </div>
            <?php recoveryRenderActionBar(['<button type="submit" class="button buttonPrimary">' . recoveryFaIcon(16, 'magnifying-glass') . ' Suchen</button>']); ?>
        </form>
    </section>

    <?php
    $umSearchQuery = \trim($_POST['um_search'] ?? '');
    if ($umSearchQuery !== ''):
        try {
            $umResults = recoveryUserSearch($db, $umSearchQuery);
        } catch (\Throwable $e) {
            $umResults = [];
            recoveryRenderAlert('error', 'Suchfehler: ' . $e->getMessage());
        }

        if (empty($umResults)) {
            recoveryRenderAlert('info', 'Keine Benutzer für <code>' . \htmlspecialchars($umSearchQuery) . '</code> gefunden.');
        } else {
    ?>
    <table class="tableList recovery-table-list recovery-um-search-table">
        <thead>
            <tr>
                <th class="recovery-col-id">ID</th>
                <th>Benutzername</th>
                <th>E-Mail</th>
                <th class="recovery-col-status">Status</th>
                <th class="recovery-col-2fa">2FA</th>
                <th class="recovery-col-action"></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($umResults as $umRow): ?>
            <tr>
                <td><?= (int)$umRow['userID'] ?></td>
                <td><?= \htmlspecialchars($umRow['username']) ?></td>
                <td><?= \htmlspecialchars($umRow['email']) ?></td>
                <td>
                    <?php if ($umRow['banned']): ?>
                        <span class="recovery-um-status--banned">Gesperrt</span>
                    <?php elseif ($umRow['activationCode'] != 0): ?>
                        <span class="recovery-um-status--pending">Inaktiv</span>
                    <?php else: ?>
                        <span class="recovery-um-status--active">Aktiv</span>
                    <?php endif; ?>
                </td>
                <td><?= $umRow['multifactorActive'] ? '<span class="recovery-um-status--pending">Ja</span>' : 'Nein' ?></td>
                <td class="recovery-um-search-actions">
                    <a href="<?= \htmlspecialchars($umBaseUrl) ?>&amp;um_uid=<?= (int)$umRow['userID'] ?>" class="button">Bearbeiten</a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php
        }
    endif;
endif;
}
?>
