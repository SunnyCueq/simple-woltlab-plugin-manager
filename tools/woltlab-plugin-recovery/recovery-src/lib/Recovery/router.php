<?php

declare(strict_types=1);

/**
 * Modus-Routing (eine Datei pro Modus).
 *
 * @var int $mode
 * @var string $authHash
 * @var \wcf\system\database\Database $db
 * @var string $recoveryBaseUrl
 * @var string $wcfDirMain
 * @var array|null $emergencyAcpResult
 * @var array|null $emergencyFixedSession
 * @var array{result: array<string, mixed>, log: list<string>}|null $acpFixOutcome
 */

$modesDir = __DIR__ . '/Modes';

if ($mode === RECOVERY_MODE_SELECTION) {
    require $modesDir . '/selection.php';
} elseif ($mode === RECOVERY_MODE_ACP_REPAIR) {
    require $modesDir . '/acp_repair.php';
} elseif ($mode === RECOVERY_MODE_PLUGIN_UNINSTALL) {
    require $modesDir . '/plugin_uninstall.php';
} elseif ($mode === RECOVERY_MODE_USER_MANAGEMENT) {
    require $modesDir . '/user_management.php';
} elseif ($mode === RECOVERY_MODE_CACHE_CLEAR) {
    require $modesDir . '/cache_clear.php';
} elseif ($mode === RECOVERY_MODE_PACKAGE_LIST_REPAIR) {
    require $modesDir . '/package_list_repair.php';
} elseif ($mode === RECOVERY_MODE_PACKAGE_FILE_REPAIR) {
    require $modesDir . '/package_file_repair.php';
} elseif ($mode === RECOVERY_MODE_RECOVERY_WIZARD) {
    require $modesDir . '/recovery_wizard.php';
} elseif ($mode === RECOVERY_MODE_SYSTEM_CHECK) {
    require $modesDir . '/system_check.php';
} elseif ($mode === RECOVERY_MODE_BACKUP_GUIDE) {
    require $modesDir . '/backup_guide.php';
} elseif ($mode === RECOVERY_MODE_DIRECTORY_STRUCTURE) {
    require $modesDir . '/directory_structure.php';
}
