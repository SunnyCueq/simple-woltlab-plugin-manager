<?php

/**
 * CLI-Harness für Entwickler (nicht für Endnutzer).
 * Simuliert GET mit gültigem ?t= für lokales Debugging.
 *
 * Ausführung: php dev/debug_invoke_recovery.php (aus dem Repo-Root)
 */
if (!\getenv('RECOVERY_AGENT_LOG_PATH')) {
    $workspaceCursor = \dirname(__DIR__, 3) . '/.cursor/debug-54d5f7.log';
    if (\is_dir(\dirname($workspaceCursor))) {
        \putenv('RECOVERY_AGENT_LOG_PATH=' . $workspaceCursor);
    }
}

$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['HTTP_HOST'] = '127.0.0.1';
$_SERVER['HTTPS'] = 'on';
$_SERVER['REQUEST_URI'] = '/plugin-recovery-tool.php';
$_SERVER['RECOVERY_DEBUG_RUN'] = 'cli-harness';

$t = \str_repeat('a', 40);
$_GET['t'] = $t;
$_REQUEST['t'] = $t;

require __DIR__ . '/../plugin-recovery-tool.php';
