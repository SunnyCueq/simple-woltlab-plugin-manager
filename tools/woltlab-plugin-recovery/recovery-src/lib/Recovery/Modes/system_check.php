<?php
/** Recovery mode: system_check — included by lib/Recovery/router.php */
declare(strict_types=1);

if ($mode === RECOVERY_MODE_SYSTEM_CHECK) {
    $wcfDirCheck = \rtrim(WCF_DIR, '/\\') . \DIRECTORY_SEPARATOR;
    $assetsCheck = recoveryGetSetupAssets();
    recoveryRenderSystemCheckPage($authHash, $wcfDirCheck, $db, WCF_N, $assetsCheck);
}

// ============================================================================
// MODUS 9: DATENSICHERUNG (Handbuch)
// ============================================================================

