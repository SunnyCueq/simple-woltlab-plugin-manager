<?php
/** Recovery mode: backup_guide — included by lib/Recovery/router.php */
declare(strict_types=1);

if ($mode === RECOVERY_MODE_BACKUP_GUIDE) {
    recoveryRenderBackupGuidePage($authHash, \rtrim(WCF_DIR, '/\\') . \DIRECTORY_SEPARATOR);
}

// ============================================================================
// MODUS 10: VERZEICHNISSTRUKTUR (Handbuch)
// ============================================================================

