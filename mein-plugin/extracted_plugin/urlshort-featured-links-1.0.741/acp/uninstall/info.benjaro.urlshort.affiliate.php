<?php

/**
 * Uninstall script for URL Shortener: Affiliate System
 * 
 * This script runs during uninstallation and removes all demo data:
 * - Demo URLs (DEMO-1 to DEMO-7)
 * - Associated Featured Links
 * - Associated Custom Buttons
 * - Associated Specials
 * - Associated Discounts (if they were created for demo URLs)
 *
 * NOTE: This script is automatically executed by WoltLab's PackageUninstallationDispatcher
 * when a package is uninstalled. It must be located at:
 * acp/uninstall/PACKAGE_NAME.php
 *
 * @author      Benjaro <https://benjaro.info>
 * @copyright   2025 Benjaro
 * @license     Commercial License
 * @package     info.benjaro.urlshort.affiliate
 */

use wcf\system\WCF;

// Helper function for logging
// Uses WoltLab's SystemException for error logging (appears in ACP → Management → Log → Errors)
// See: https://docs.woltlab.com/6.0/view/languages-naming-conventions/#error-texts
function logUninstall($message) {
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[{$timestamp}] [Uninstall] {$message}";
    
    // ALWAYS log to PHP error_log first (this works even if WCF is not loaded)
    error_log($logMessage);
    
    // Try to write to demodata.log (same as Post-Install and Event Listener)
    // Use multiple fallback paths to ensure logging works
    $logFile = null;
    if (defined('WCF_DIR') && WCF_DIR) {
        $logFile = WCF_DIR . 'log/demodata.log';
    } else {
        // Fallback: Try to find WCF_DIR from common locations
        $possiblePaths = [
            __DIR__ . '/../../../../log/demodata.log',
            __DIR__ . '/../../../log/demodata.log',
            dirname(__DIR__, 4) . '/log/demodata.log',
        ];
        foreach ($possiblePaths as $path) {
            if (file_exists(dirname($path)) || is_writable(dirname($path))) {
                $logFile = $path;
                break;
            }
        }
    }
    
    if ($logFile) {
        try {
            $logDir = dirname($logFile);
            if (!is_dir($logDir)) {
                @mkdir($logDir, 0755, true);
            }
            if (is_writable($logDir) || (file_exists($logFile) && is_writable($logFile))) {
                @file_put_contents($logFile, $logMessage . PHP_EOL, FILE_APPEND | LOCK_EX);
            }
        } catch (\Exception $e) {
            // Ignore file write errors - already logged to error_log
        }
    }
    
    // Also try to log to WoltLab's error log system (if available)
    try {
        if (class_exists('wcf\system\exception\SystemException') && class_exists('wcf\system\WCF')) {
            $exception = new \wcf\system\exception\SystemException('[Uninstall] ' . $message, 0, '', null);
            if (method_exists('wcf\system\WCF', 'getExceptionLogger')) {
                \wcf\system\WCF::getExceptionLogger()->logException($exception);
            }
        }
    } catch (\Exception $e) {
        // Ignore - already logged to error_log and demodata.log
    }
}

// CRITICAL: Log immediately to ensure we can see if script is called
logUninstall('========================================');
logUninstall('UNINSTALL SCRIPT CALLED - Starting execution');
logUninstall('========================================');
logUninstall('Uninstall: Script file: ' . __FILE__);
logUninstall('Uninstall: PHP version: ' . PHP_VERSION);
logUninstall('Uninstall: WCF_DIR: ' . (defined('WCF_DIR') ? WCF_DIR : 'NOT DEFINED'));
logUninstall('Uninstall: WCF class exists: ' . (class_exists('wcf\system\WCF') ? 'YES' : 'NO'));

// Check if WCF is available
$wcfAvailable = false;
try {
    if (class_exists('wcf\system\WCF')) {
        $wcfAvailable = true;
        logUninstall('Uninstall: WCF class available');
        logUninstall('Uninstall: WCF::getDB() available: ' . (method_exists('wcf\system\WCF', 'getDB') ? 'YES' : 'NO'));
    } else {
        logUninstall('Uninstall: WARNING - WCF class not available!');
    }
} catch (\Exception $e) {
    logUninstall('Uninstall: Exception checking WCF: ' . $e->getMessage());
}

if (!$wcfAvailable) {
    logUninstall('Uninstall: WCF not available - cannot proceed with database operations');
    logUninstall('Uninstall: This might be normal if plugin is being uninstalled before WCF is fully loaded');
    logUninstall('========================================');
    logUninstall('Uninstall script completed (WCF not available)');
    logUninstall('========================================');
    return;
}

// Check if URL table exists (base plugin must be installed)
$urlTableExists = false;
try {
    $sql = "SHOW TABLES LIKE 'urlshort1_url'";
    $statement = WCF::getDB()->prepareStatement($sql);
    $statement->execute();
    $urlTableExists = ($statement->fetchSingleColumn() !== false);
    logUninstall('Uninstall: Checked for urlshort1_url table - exists: ' . ($urlTableExists ? 'YES' : 'NO'));
} catch (\Exception $e) {
    logUninstall('Uninstall: Exception checking for URL table: ' . $e->getMessage());
    logUninstall('Uninstall: Stack trace: ' . $e->getTraceAsString());
    // Table doesn't exist - base plugin not installed
}

if (!$urlTableExists) {
    logUninstall('URL table does not exist, skipping demo data cleanup');
    logUninstall('========================================');
    logUninstall('Uninstall script completed (nothing to clean)');
    logUninstall('========================================');
    return;
}

logUninstall('URL table exists, starting demo data cleanup...');

try {
    // CRITICAL: Use the SAME logic as OptionFormDemoDataListener - delete by ID (not hash pattern)
    // This is the proven working method that successfully deletes demo URLs
    logUninstall('Step 1: Get demo URL IDs (same logic as OptionFormDemoDataListener)...');
    
    // Step 1: Get all demo URL IDs (with security verification)
    $demoUrlIDs = [];
    $demoHashes = [];
    
    // Check if isDemo column exists
    $isDemoColumnExists = false;
    try {
        $sql = "SHOW COLUMNS FROM urlshort1_url LIKE 'isDemo'";
        $statement = WCF::getDB()->prepareStatement($sql);
        $statement->execute();
        $isDemoColumnExists = ($statement->fetchSingleColumn() !== false);
    } catch (\Exception) {
        // Column doesn't exist
    }
    
    if ($isDemoColumnExists) {
        // Use isDemo flag for identification (most reliable)
        // SECURITY: Also check hash starts with DEMO- to ensure we only get demo URLs
        $sql = "SELECT urlID, hash FROM urlshort1_url WHERE isDemo = 1 AND hash LIKE 'DEMO-%'";
        $statement = WCF::getDB()->prepareStatement($sql);
        $statement->execute();
        while ($row = $statement->fetchArray()) {
            if (!empty($row['urlID'])) {
                $demoUrlIDs[] = $row['urlID'];
            }
            if (!empty($row['hash'])) {
                $demoHashes[] = $row['hash'];
            }
        }
        logUninstall('Found ' . count($demoUrlIDs) . ' demo URLs by isDemo flag (with DEMO- hash check)');
    }
    
    // Fallback: Also check by hash pattern (for backwards compatibility)
    if (empty($demoUrlIDs)) {
        $sql = "SELECT urlID, hash FROM urlshort1_url WHERE hash LIKE 'DEMO-%'";
        $statement = WCF::getDB()->prepareStatement($sql);
        $statement->execute();
        while ($row = $statement->fetchArray()) {
            // Double-check: hash must start with DEMO-
            if (!empty($row['hash']) && strpos($row['hash'], 'DEMO-') === 0) {
                if (!empty($row['urlID']) && !in_array($row['urlID'], $demoUrlIDs)) {
                    $demoUrlIDs[] = $row['urlID'];
                }
                if (!in_array($row['hash'], $demoHashes)) {
                    $demoHashes[] = $row['hash'];
                }
            }
        }
        logUninstall('Found ' . count($demoUrlIDs) . ' demo URLs by hash pattern (fallback)');
    }
    
    $demoUrlCount = count($demoUrlIDs);
    $hashList = !empty($demoHashes) ? implode(', ', $demoHashes) : '(none)';
    logUninstall('Total demo URLs to delete: ' . $demoUrlCount . ' (hashes: ' . $hashList . ')');
    
    if (empty($demoUrlIDs)) {
        logUninstall('No demo URLs found, skipping cleanup');
        logUninstall('Demo data cleanup completed (nothing to delete)');
        return;
    }
    
    // SECURITY CHECK: Verify all URLs have DEMO- hash pattern before deletion
    // This ensures we never delete non-demo URLs, even if isDemo flag is incorrectly set
    $verifiedDemoUrlIDs = [];
    foreach ($demoUrlIDs as $urlID) {
        try {
            $sql = "SELECT hash FROM urlshort1_url WHERE urlID = ?";
            $statement = WCF::getDB()->prepareStatement($sql);
            $statement->execute([$urlID]);
            $hash = $statement->fetchSingleColumn();
            
            // Only include if hash starts with "DEMO-" (double-check for safety)
            if ($hash && strpos($hash, 'DEMO-') === 0) {
                $verifiedDemoUrlIDs[] = $urlID;
            } else {
                logUninstall('SECURITY WARNING - URL ID ' . $urlID . ' has hash "' . ($hash ?? 'NULL') . '" which does not start with "DEMO-", skipping deletion');
            }
        } catch (\Exception $e) {
            logUninstall('Error verifying URL ID ' . $urlID . ': ' . $e->getMessage());
        }
    }
    
    if (empty($verifiedDemoUrlIDs)) {
        logUninstall('No verified demo URLs found after security check, skipping cleanup');
        logUninstall('Demo data cleanup completed (no verified URLs)');
        return;
    }
    
    if (count($verifiedDemoUrlIDs) < count($demoUrlIDs)) {
        logUninstall('SECURITY WARNING - Only ' . count($verifiedDemoUrlIDs) . ' of ' . count($demoUrlIDs) . ' URLs passed security verification');
    }
    
    // Use only verified demo URL IDs for all deletion operations
    $demoUrlIDs = $verifiedDemoUrlIDs;
    logUninstall('Using ' . count($demoUrlIDs) . ' verified demo URL IDs for deletion');
    
    // Step 2: Delete associated Featured Links (ONLY for verified demo URLs)
    try {
        $featuredLinkTableExists = false;
        try {
            $sql = "SHOW TABLES LIKE 'urlshort1_featured_link'";
            $statement = WCF::getDB()->prepareStatement($sql);
            $statement->execute();
            $featuredLinkTableExists = ($statement->fetchSingleColumn() !== false);
        } catch (\Exception) {
            // Table doesn't exist
        }
        
        if ($featuredLinkTableExists && !empty($demoUrlIDs)) {
            // SECURITY: Only delete featured links for verified demo URLs
            // Additional safety: Join with url table to ensure hash starts with DEMO-
            $placeholders = str_repeat('?,', count($demoUrlIDs) - 1) . '?';
            $sql = "DELETE fl FROM urlshort1_featured_link fl 
                    INNER JOIN urlshort1_url u ON fl.urlID = u.urlID 
                    WHERE fl.urlID IN ({$placeholders}) AND u.hash LIKE 'DEMO-%'";
            $statement = WCF::getDB()->prepareStatement($sql);
            $statement->execute($demoUrlIDs);
            $deletedLinks = $statement->getAffectedRows();
            logUninstall('Deleted ' . $deletedLinks . ' featured links (with DEMO- hash verification)');
        }
    } catch (\Exception $e) {
        logUninstall('Error deleting featured links: ' . $e->getMessage());
    }
    
    // Step 3: Delete associated Custom Buttons (ONLY for verified demo URLs)
    try {
        $customButtonTableExists = false;
        try {
            $sql = "SHOW TABLES LIKE 'urlshort1_custom_button'";
            $statement = WCF::getDB()->prepareStatement($sql);
            $statement->execute();
            $customButtonTableExists = ($statement->fetchSingleColumn() !== false);
        } catch (\Exception) {
            // Table doesn't exist
        }
        
        if ($customButtonTableExists && !empty($demoUrlIDs)) {
            // SECURITY: Only delete custom buttons for verified demo URLs
            // Additional safety: Join with url table to ensure hash starts with DEMO-
            $placeholders = str_repeat('?,', count($demoUrlIDs) - 1) . '?';
            $sql = "DELETE cb FROM urlshort1_custom_button cb 
                    INNER JOIN urlshort1_url u ON cb.urlID = u.urlID 
                    WHERE cb.urlID IN ({$placeholders}) AND u.hash LIKE 'DEMO-%'";
            $statement = WCF::getDB()->prepareStatement($sql);
            $statement->execute($demoUrlIDs);
            $deletedButtons = $statement->getAffectedRows();
            logUninstall('Deleted ' . $deletedButtons . ' custom buttons (with DEMO- hash verification)');
        }
    } catch (\Exception $e) {
        logUninstall('Error deleting custom buttons: ' . $e->getMessage());
    }
    
    // Step 4: Delete associated Specials (ONLY for verified demo URLs)
    try {
        $specialTableExists = false;
        try {
            $sql = "SHOW TABLES LIKE 'urlshort1_special'";
            $statement = WCF::getDB()->prepareStatement($sql);
            $statement->execute();
            $specialTableExists = ($statement->fetchSingleColumn() !== false);
        } catch (\Exception) {
            // Table doesn't exist
        }
        
        if ($specialTableExists && !empty($demoUrlIDs)) {
            // SECURITY: Only delete specials for verified demo URLs
            // Additional safety: Join with url table to ensure hash starts with DEMO-
            $placeholders = str_repeat('?,', count($demoUrlIDs) - 1) . '?';
            $sql = "DELETE s FROM urlshort1_special s 
                    INNER JOIN urlshort1_url u ON s.urlID = u.urlID 
                    WHERE s.urlID IN ({$placeholders}) AND u.hash LIKE 'DEMO-%'";
            $statement = WCF::getDB()->prepareStatement($sql);
            $statement->execute($demoUrlIDs);
            $deletedSpecials = $statement->getAffectedRows();
            logUninstall('Deleted ' . $deletedSpecials . ' specials (with DEMO- hash verification)');
        }
    } catch (\Exception $e) {
        logUninstall('Error deleting specials: ' . $e->getMessage());
    }
    
    // Step 5: Delete associated Discounts (by unique demo discount codes)
    try {
        $discountTableExists = false;
        try {
            $sql = "SHOW TABLES LIKE 'urlshort1_discount'";
            $statement = WCF::getDB()->prepareStatement($sql);
            $statement->execute();
            $discountTableExists = ($statement->fetchSingleColumn() !== false);
        } catch (\Exception) {
            // Table doesn't exist
        }
        
        if ($discountTableExists) {
            // Demo discounts are identified by codes starting with "DEMO-"
            $sql = "DELETE FROM urlshort1_discount WHERE codes LIKE 'DEMO-%' OR codes LIKE '%,DEMO-%' OR codes LIKE 'DEMO-%,%'";
            $statement = WCF::getDB()->prepareStatement($sql);
            $statement->execute();
            $deletedDiscounts = $statement->getAffectedRows();
            logUninstall('Deleted ' . $deletedDiscounts . ' discounts (by DEMO- prefix in codes - SAFE)');
        }
    } catch (\Exception $e) {
        logUninstall('Error deleting discounts: ' . $e->getMessage());
    }
    
    // Step 6: Delete demo URLs themselves (ONLY verified demo URLs by ID - same as OptionFormDemoDataListener)
    try {
        if (!empty($demoUrlIDs)) {
            // SECURITY: Only delete by verified urlID list (already verified to have DEMO- hash)
            // This is the SAME method that works in OptionFormDemoDataListener
            $placeholders = str_repeat('?,', count($demoUrlIDs) - 1) . '?';
            $sql = "DELETE FROM urlshort1_url WHERE urlID IN ({$placeholders}) AND hash LIKE 'DEMO-%'";
            $statement = WCF::getDB()->prepareStatement($sql);
            $statement->execute($demoUrlIDs);
            $deletedUrls = $statement->getAffectedRows();
            logUninstall('Deleted ' . $deletedUrls . ' demo URLs (by verified urlID with DEMO- hash check - SAME METHOD AS OPTION LISTENER)');
            
            if ($deletedUrls < count($demoUrlIDs)) {
                logUninstall('WARNING - Expected to delete ' . count($demoUrlIDs) . ' URLs but only deleted ' . $deletedUrls);
            }
            
            // Final cleanup: Also delete by hash pattern as fallback (in case something was missed)
            try {
                $sql = "DELETE FROM urlshort1_url WHERE hash LIKE 'DEMO-%'";
                $statement = WCF::getDB()->prepareStatement($sql);
                $statement->execute();
                $deletedByHash = $statement->getAffectedRows();
                if ($deletedByHash > 0) {
                    logUninstall('FINAL CLEANUP: Deleted ' . $deletedByHash . ' additional demo URLs by hash pattern');
                }
            } catch (\Exception $e) {
                logUninstall('FINAL CLEANUP: Error deleting by hash pattern: ' . $e->getMessage());
            }
        } else {
            logUninstall('No verified demo URL IDs to delete');
        }
    } catch (\Exception $e) {
        logUninstall('CRITICAL ERROR deleting demo URLs: ' . $e->getMessage());
        logUninstall('Stack trace: ' . $e->getTraceAsString());
        
        // Emergency fallback: Try by hash pattern
        try {
            $sql = "DELETE FROM urlshort1_url WHERE hash LIKE 'DEMO-%'";
            $statement = WCF::getDB()->prepareStatement($sql);
            $statement->execute();
            $deletedByHash = $statement->getAffectedRows();
            if ($deletedByHash > 0) {
                logUninstall('EMERGENCY FALLBACK: Deleted ' . $deletedByHash . ' demo URLs by hash pattern');
            }
        } catch (\Exception $e2) {
            logUninstall('EMERGENCY FALLBACK also failed: ' . $e2->getMessage());
        }
    }
    
    logUninstall('Demo data cleanup completed successfully');
} catch (\Exception $e) {
    logUninstall('Demo data cleanup failed: ' . $e->getMessage());
    logUninstall('Stack trace: ' . $e->getTraceAsString());
}

logUninstall('========================================');
logUninstall('Uninstall script completed');
logUninstall('========================================');

