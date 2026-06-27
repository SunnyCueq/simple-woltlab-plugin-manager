<?php
/** Recovery mode: directory_structure — included by lib/Recovery/router.php */
declare(strict_types=1);

if ($mode === RECOVERY_MODE_DIRECTORY_STRUCTURE) {
    $wcfDirDs = \rtrim(WCF_DIR, '/\\') . \DIRECTORY_SEPARATOR;
    $applyDbResult = null;
    $applyConfigResult = null;

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['recovery_apply_directory_db'])) {
        $apps = recoveryFetchApplicationDirectoryReport($db, WCF_N, $wcfDirDs);
        $updates = recoveryCollectPackageDirDbUpdates($apps, $wcfDirDs);
        if ($updates !== []) {
            $applyDbResult = recoveryApplyPackageDirDbUpdates($db, WCF_N, $updates);
            recoveryLog('info', 'Directory structure DB apply', $applyDbResult);
            recoverySessionSetFlash($authHash, 'dir_db', $applyDbResult);
        }
        \header('Location: ' . recoveryBuildModeUrl(RECOVERY_MODE_DIRECTORY_STRUCTURE, $authHash, ['db_applied' => '1']));
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['recovery_apply_directory_config'])) {
        $apps = recoveryFetchApplicationDirectoryReport($db, WCF_N, $wcfDirDs);
        $appConfigs = recoveryScanAppConfigRelativeWcfDir($wcfDirDs, $apps);
        $patches = recoveryCollectAppConfigPatches($appConfigs, $apps);
        $singlePath = \trim((string) ($_POST['recovery_config_patch_path'] ?? ''));
        if ($singlePath !== '') {
            $patches = \array_values(\array_filter(
                $patches,
                static fn (array $p): bool => (string) ($p['path'] ?? '') === $singlePath
            ));
        }
        if ($patches !== []) {
            $applyConfigResult = recoveryApplyAppConfigPatches($patches);
            recoveryLog('info', 'Directory structure config apply', $applyConfigResult);
            recoverySessionSetFlash($authHash, 'dir_config', $applyConfigResult);
        } else {
            recoverySessionSetFlash($authHash, 'dir_config', ['ok' => true, 'applied' => 0, 'log' => ['Keine RELATIVE_WCF_DIR-Anpassungen nötig.']]);
        }
        \header('Location: ' . recoveryBuildModeUrl(RECOVERY_MODE_DIRECTORY_STRUCTURE, $authHash, ['config_applied' => '1']));
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['recovery_clear_cache_after_paths'])) {
        $deleted = clearCompiledTemplates();
        $optionLog = [];
        recoveryEnsureOptionConstantFallbacks($db, WCF_N, $optionLog);
        recoveryLog('info', 'Directory structure cache clear', ['deleted' => $deleted]);
        \header('Location: ' . recoveryBuildModeUrl(RECOVERY_MODE_DIRECTORY_STRUCTURE, $authHash, ['cache_cleared' => '1']));
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['recovery_apply_domain_db'])) {
        $domainUrl = \trim((string) ($_POST['recovery_domain_url'] ?? ''));
        $cookieDomain = \trim((string) ($_POST['recovery_cookie_domain'] ?? ''));
        $domainResult = recoveryApplyDomainToDatabase($db, WCF_N, $domainUrl, $cookieDomain);
        if (!empty($domainResult['ok'])) {
            recoveryRebuildDisplayData($wcfDirDs, $db, WCF_N);
        }
        recoverySessionSetFlash($authHash, 'dir_domain', $domainResult);
        recoveryLog('info', 'Directory structure domain apply', $domainResult);
        \header('Location: ' . recoveryBuildModeUrl(RECOVERY_MODE_DIRECTORY_STRUCTURE, $authHash, ['domain_applied' => '1']));
        exit;
    }

    recoveryRenderDirectoryStructurePage($authHash, $wcfDirDs, $db, WCF_N, $applyDbResult, $applyConfigResult);
}
