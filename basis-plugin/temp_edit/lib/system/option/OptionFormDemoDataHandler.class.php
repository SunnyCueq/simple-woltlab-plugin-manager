<?php

namespace shrinkr\system\option;

use wcf\acp\form\OptionForm;
use wcf\system\WCF;

/**
 * Handles demo data installation and deletion when the shrinkr_install_demo_data option is changed.
 * 
 *
 * @author      Sunny C
 * @copyright   2026 Sunny C
 * @license     Commercial License
 * @package     de.sunnyc.wsc.shrinkr
 * @subpackage system.option
 */
class OptionFormDemoDataHandler
{
    /**
     * Handles the OptionForm::saved event.
     * 
     *
     * @param OptionForm $form The option form that was saved
     */
    public function handleSaved(OptionForm $form): void
    {
        // Log that the handler was called
        $this->log('OptionFormDemoDataHandler: Handler called for OptionForm::saved');
        
        // Check current value from database (option was already saved at this point)
        try {
            $option = \wcf\data\option\Option::getOptionByName('shrinkr_install_demo_data');
            if (!$option) {
                $this->log('OptionFormDemoDataHandler: Option shrinkr_install_demo_data does not exist, skipping');
                return;
            }
            
            $installDemoData = (bool) $option->optionValue;
            $this->log('OptionFormDemoDataHandler: Option value from database: ' . ($installDemoData ? '1' : '0') . ' (optionID: ' . $option->optionID . ')');
        } catch (\Exception $e) {
            $this->log('OptionFormDemoDataHandler: Error reading option: ' . $e->getMessage());
            $this->log('OptionFormDemoDataHandler: Stack trace: ' . $e->getTraceAsString());
            return;
        }
        
        // If option is disabled, delete demo data (check and delete regardless of count)
        if (!$installDemoData) {
            $this->log('OptionFormDemoDataHandler: Option is disabled, checking for demo data to delete...');
            $this->deleteDemoData();
            return;
        }
        
        // Check if demo data already exists (only if option is enabled)
        // Use isDemo flag if available, otherwise fallback to hash pattern
        try {
            // Check if isDemo column exists
            $isDemoColumnExists = false;
            try {
                $sql = "SHOW COLUMNS FROM shrinkr1_link LIKE 'isDemo'";
                $statement = WCF::getDB()->prepareStatement($sql);
                $statement->execute();
                $isDemoColumnExists = ($statement->fetchSingleColumn() !== false);
            } catch (\Exception) {
                // Column doesn't exist
            }
            
            if ($isDemoColumnExists) {
                $sql = "SELECT COUNT(*) FROM shrinkr1_link WHERE isDemo = 1";
                $statement = WCF::getDB()->prepareStatement($sql);
                $statement->execute();
                $existingCount = $statement->fetchSingleColumn();
                $this->log('OptionFormDemoDataHandler: Existing demo URLs count (by isDemo flag): ' . $existingCount);
            } else {
                // Fallback to hash pattern
                $sql = "SELECT COUNT(*) FROM shrinkr1_link WHERE hash LIKE 'DEMO-%'";
                $statement = WCF::getDB()->prepareStatement($sql);
                $statement->execute();
                $existingCount = $statement->fetchSingleColumn();
                $this->log('OptionFormDemoDataHandler: Existing demo URLs count (by hash pattern): ' . $existingCount);
            }
        } catch (\Exception $e) {
            $this->log('OptionFormDemoDataHandler: Error checking existing demo data: ' . $e->getMessage());
            return;
        }
        
        // Option is enabled - check if we need to install or complete demo data
        // Even if URLs exist, we might need to create Featured Links/Custom Buttons
        if ($existingCount > 0) {
            // URLs exist, but check if Featured Links/Custom Buttons are missing
            try {
                // Check if Featured Links exist for demo URLs
                $featuredLinksCount = 0;
                $customButtonsCount = 0;
                
                try {
                    $sql = "SELECT COUNT(*) FROM shrinkr1_featured_link fl 
                            INNER JOIN shrinkr1_link u ON fl.linkID = u.linkID 
                            WHERE u.isDemo = 1";
                    $statement = WCF::getDB()->prepareStatement($sql);
                    $statement->execute();
                    $featuredLinksCount = $statement->fetchSingleColumn();
                } catch (\Exception $e) {
                    // Table might not exist or isDemo column might not exist - try fallback
                    try {
                        $sql = "SELECT COUNT(*) FROM shrinkr1_featured_link fl 
                                INNER JOIN shrinkr1_link u ON fl.linkID = u.linkID 
                                WHERE u.hash LIKE 'DEMO-%'";
                        $statement = WCF::getDB()->prepareStatement($sql);
                        $statement->execute();
                        $featuredLinksCount = $statement->fetchSingleColumn();
                    } catch (\Exception) {
                        // Ignore
                    }
                }
                
                try {
                    $sql = "SELECT COUNT(*) FROM shrinkr1_custom_button cb 
                            INNER JOIN shrinkr1_link u ON cb.linkID = u.linkID 
                            WHERE u.isDemo = 1";
                    $statement = WCF::getDB()->prepareStatement($sql);
                    $statement->execute();
                    $customButtonsCount = $statement->fetchSingleColumn();
                } catch (\Exception $e) {
                    // Table might not exist or isDemo column might not exist - try fallback
                    try {
                        $sql = "SELECT COUNT(*) FROM shrinkr1_custom_button cb 
                                INNER JOIN shrinkr1_link u ON cb.linkID = u.linkID 
                                WHERE u.hash LIKE 'DEMO-%'";
                        $statement = WCF::getDB()->prepareStatement($sql);
                        $statement->execute();
                        $customButtonsCount = $statement->fetchSingleColumn();
                    } catch (\Exception) {
                        // Ignore
                    }
                }
                
                $this->log('OptionFormDemoDataHandler: Found ' . $featuredLinksCount . ' featured links and ' . $customButtonsCount . ' custom buttons for demo URLs');
                
                // ALWAYS call post-install script if Featured Links or Custom Buttons are missing
                // This ensures they are created even if URLs already exist
                if ($featuredLinksCount == 0 || $customButtonsCount == 0) {
                    $this->log('OptionFormDemoDataHandler: Demo URLs exist but Featured Links (' . $featuredLinksCount . ') or Custom Buttons (' . $customButtonsCount . ') are missing - calling post-install script to create them');
                    
                    $postInstallScript = WCF_DIR . 'shrinkr/acp/install_de.sunnyc.wsc.shrinkr_postInstall.php';
                    
                    if (file_exists($postInstallScript)) {
                        $GLOBALS['shrinkr_demo_data_from_event'] = true;
                        $this->log('OptionFormDemoDataHandler: Calling post-install script to create missing Featured Links/Custom Buttons...');
                        require_once($postInstallScript);
                        unset($GLOBALS['shrinkr_demo_data_from_event']);
                        $this->log('OptionFormDemoDataHandler: Post-install script completed');
                    } else {
                        $this->log('OptionFormDemoDataHandler: Post-install script not found at: ' . $postInstallScript);
                    }
                } else {
                    $this->log('OptionFormDemoDataHandler: Demo data complete (URLs, Featured Links, Custom Buttons all exist) - skipping installation');
                }
            } catch (\Exception $e) {
                $this->log('OptionFormDemoDataHandler: Error checking Featured Links/Custom Buttons: ' . $e->getMessage());
                // Fall through to normal installation
            }
        } else {
            // No demo URLs exist - call post-install script to create everything
            $this->log('OptionFormDemoDataHandler: No demo URLs found - calling post-install script to create demo data...');
        }
        
        // Call the demo data installation function from the post-install script (if URLs don't exist)
        if ($existingCount == 0) {
            $postInstallScript = WCF_DIR . 'shrinkr/acp/install_de.sunnyc.wsc.shrinkr_postInstall.php';
            
            $this->log('OptionFormDemoDataHandler: Post-install script path: ' . $postInstallScript);
            $this->log('OptionFormDemoDataHandler: Post-install script exists: ' . (file_exists($postInstallScript) ? 'yes' : 'no'));
            
            if (file_exists($postInstallScript)) {
                // Temporarily set a flag to indicate we're calling from the event handler
                $GLOBALS['shrinkr_demo_data_from_event'] = true;
                
                $this->log('OptionFormDemoDataHandler: Calling post-install script...');
                
                // Include the post-install script
                require_once($postInstallScript);
                
                $this->log('OptionFormDemoDataHandler: Post-install script completed');
                
                // Unset the flag
                unset($GLOBALS['shrinkr_demo_data_from_event']);
            } else {
                $this->log('OptionFormDemoDataHandler: Post-install script not found!');
            }
        }
    }
    
    /**
     * Deletes all demo data (URLs, featured links, custom buttons, specials, discounts)
     */
    private function deleteDemoData(): void
    {
        try {
            $this->log('OptionFormDemoDataHandler: Starting demo data deletion...');
            
            // Step 1: Get all demo URL IDs (with security verification)
            // First try by isDemo flag (most reliable)
            $demoUrlIDs = [];
            $demoHashes = [];
            
            // Check if isDemo column exists
            $isDemoColumnExists = false;
            try {
                $sql = "SHOW COLUMNS FROM shrinkr1_link LIKE 'isDemo'";
                $statement = WCF::getDB()->prepareStatement($sql);
                $statement->execute();
                $isDemoColumnExists = ($statement->fetchSingleColumn() !== false);
            } catch (\Exception) {
                // Column doesn't exist
            }
            
            if ($isDemoColumnExists) {
                // Use isDemo flag for identification (most reliable)
                // SECURITY: Also check hash starts with DEMO- to ensure we only get demo URLs
                $sql = "SELECT linkID, hash FROM shrinkr1_link WHERE isDemo = 1 AND hash LIKE 'DEMO-%'";
                $statement = WCF::getDB()->prepareStatement($sql);
                $statement->execute();
                while ($row = $statement->fetchArray()) {
                    if (!empty($row['linkID'])) {
                        $demoUrlIDs[] = $row['linkID'];
                    }
                    if (!empty($row['hash'])) {
                        $demoHashes[] = $row['hash'];
                    }
                }
                $this->log('OptionFormDemoDataHandler: Found ' . count($demoUrlIDs) . ' demo URLs by isDemo flag (with DEMO- hash check)');
            }
            
            // Fallback: Also check by hash pattern (for backwards compatibility)
            if (empty($demoUrlIDs)) {
                $sql = "SELECT linkID, hash FROM shrinkr1_link WHERE hash LIKE 'DEMO-%'";
                $statement = WCF::getDB()->prepareStatement($sql);
                $statement->execute();
                while ($row = $statement->fetchArray()) {
                    // Double-check: hash must start with DEMO-
                    if (!empty($row['hash']) && strpos($row['hash'], 'DEMO-') === 0) {
                        if (!empty($row['linkID']) && !in_array($row['linkID'], $demoUrlIDs)) {
                            $demoUrlIDs[] = $row['linkID'];
                        }
                        if (!in_array($row['hash'], $demoHashes)) {
                            $demoHashes[] = $row['hash'];
                        }
                    }
                }
                $this->log('OptionFormDemoDataHandler: Found ' . count($demoUrlIDs) . ' demo URLs by hash pattern (fallback)');
            }
            
            $demoUrlCount = count($demoUrlIDs);
            $hashList = !empty($demoHashes) ? implode(', ', $demoHashes) : '(none)';
            $this->log('OptionFormDemoDataHandler: Total demo URLs to delete: ' . $demoUrlCount . ' (hashes: ' . $hashList . ')');
            
            if (empty($demoUrlIDs)) {
                $this->log('OptionFormDemoDataHandler: No demo URLs found, skipping cleanup');
                return;
            }
            
            // SECURITY CHECK: Verify all URLs have DEMO- hash pattern before deletion
            // This ensures we never delete non-demo URLs, even if isDemo flag is incorrectly set
            $verifiedDemoUrlIDs = [];
            foreach ($demoUrlIDs as $linkID) {
                try {
                    $sql = "SELECT hash FROM shrinkr1_link WHERE linkID = ?";
                    $statement = WCF::getDB()->prepareStatement($sql);
                    $statement->execute([$linkID]);
                    $hash = $statement->fetchSingleColumn();
                    
                    // Only include if hash starts with "DEMO-" (double-check for safety)
                    if ($hash && strpos($hash, 'DEMO-') === 0) {
                        $verifiedDemoUrlIDs[] = $linkID;
                    } else {
                        $this->log('OptionFormDemoDataHandler: SECURITY WARNING - URL ID ' . $linkID . ' has hash "' . ($hash ?? 'NULL') . '" which does not start with "DEMO-", skipping deletion');
                    }
                } catch (\Exception $e) {
                    $this->log('OptionFormDemoDataHandler: Error verifying URL ID ' . $linkID . ': ' . $e->getMessage());
                }
            }
            
            if (empty($verifiedDemoUrlIDs)) {
                $this->log('OptionFormDemoDataHandler: No verified demo URLs found after security check, skipping cleanup');
                return;
            }
            
            if (count($verifiedDemoUrlIDs) < count($demoUrlIDs)) {
                $this->log('OptionFormDemoDataHandler: SECURITY WARNING - Only ' . count($verifiedDemoUrlIDs) . ' of ' . count($demoUrlIDs) . ' URLs passed security verification');
            }
            
            // Use only verified demo URL IDs for all deletion operations
            $demoUrlIDs = $verifiedDemoUrlIDs;
            
            // Step 2: Delete associated Featured Links (with error handling and debugging)
            try {
                $featuredLinkTableExists = false;
                try {
                    $sql = "SHOW TABLES LIKE 'shrinkr1_featured_link'";
                    $statement = WCF::getDB()->prepareStatement($sql);
                    $statement->execute();
                    $featuredLinkTableExists = ($statement->fetchSingleColumn() !== false);
                } catch (\Exception) {
                    // Table doesn't exist
                }
                
                if ($featuredLinkTableExists && !empty($demoUrlIDs)) {
                    // Debug: Check how many featured links exist for these URLs
                    $placeholders = str_repeat('?,', count($demoUrlIDs) - 1) . '?';
                    $sql = "SELECT COUNT(*) FROM shrinkr1_featured_link WHERE linkID IN ({$placeholders})";
                    $statement = WCF::getDB()->prepareStatement($sql);
                    $statement->execute($demoUrlIDs);
                    $existingLinksCount = $statement->fetchSingleColumn();
                    $this->log('OptionFormDemoDataHandler: Found ' . $existingLinksCount . ' featured links for demo URLs');
                    
                    // Now delete them
                    $sql = "DELETE FROM shrinkr1_featured_link WHERE linkID IN ({$placeholders})";
                    $statement = WCF::getDB()->prepareStatement($sql);
                    $statement->execute($demoUrlIDs);
                    $deletedLinks = $statement->getAffectedRows();
                    $this->log('OptionFormDemoDataHandler: Deleted ' . $deletedLinks . ' featured links');
                } else {
                    $this->log('OptionFormDemoDataHandler: Featured link table does not exist or no demo URL IDs');
                }
            } catch (\Exception $e) {
                $this->log('OptionFormDemoDataHandler: Error deleting featured links: ' . $e->getMessage());
            }
            
            // Step 3: Delete associated Custom Buttons (with error handling and debugging)
            try {
                $customButtonTableExists = false;
                try {
                    $sql = "SHOW TABLES LIKE 'shrinkr1_custom_button'";
                    $statement = WCF::getDB()->prepareStatement($sql);
                    $statement->execute();
                    $customButtonTableExists = ($statement->fetchSingleColumn() !== false);
                } catch (\Exception) {
                    // Table doesn't exist
                }
                
                if ($customButtonTableExists && !empty($demoUrlIDs)) {
                    // Debug: Check how many custom buttons exist for these URLs
                    $placeholders = str_repeat('?,', count($demoUrlIDs) - 1) . '?';
                    $sql = "SELECT COUNT(*) FROM shrinkr1_custom_button WHERE linkID IN ({$placeholders})";
                    $statement = WCF::getDB()->prepareStatement($sql);
                    $statement->execute($demoUrlIDs);
                    $existingButtonsCount = $statement->fetchSingleColumn();
                    $this->log('OptionFormDemoDataHandler: Found ' . $existingButtonsCount . ' custom buttons for demo URLs');
                    
                    // Now delete them
                    $sql = "DELETE FROM shrinkr1_custom_button WHERE linkID IN ({$placeholders})";
                    $statement = WCF::getDB()->prepareStatement($sql);
                    $statement->execute($demoUrlIDs);
                    $deletedButtons = $statement->getAffectedRows();
                    $this->log('OptionFormDemoDataHandler: Deleted ' . $deletedButtons . ' custom buttons');
                } else {
                    $this->log('OptionFormDemoDataHandler: Custom button table does not exist or no demo URL IDs');
                }
            } catch (\Exception $e) {
                $this->log('OptionFormDemoDataHandler: Error deleting custom buttons: ' . $e->getMessage());
            }
            
            // Step 4: Delete associated Specials (with error handling)
            try {
                $specialTableExists = false;
                try {
                    $sql = "SHOW TABLES LIKE 'shrinkr1_special'";
                    $statement = WCF::getDB()->prepareStatement($sql);
                    $statement->execute();
                    $specialTableExists = ($statement->fetchSingleColumn() !== false);
                } catch (\Exception) {
                    // Table doesn't exist
                }
                
                if ($specialTableExists && !empty($demoUrlIDs)) {
                    $placeholders = str_repeat('?,', count($demoUrlIDs) - 1) . '?';
                    $sql = "DELETE FROM shrinkr1_special WHERE linkID IN ({$placeholders})";
                    $statement = WCF::getDB()->prepareStatement($sql);
                    $statement->execute($demoUrlIDs);
                    $deletedSpecials = $statement->getAffectedRows();
                    $this->log('OptionFormDemoDataHandler: Deleted ' . $deletedSpecials . ' specials');
                }
            } catch (\Exception $e) {
                $this->log('OptionFormDemoDataHandler: Error deleting specials: ' . $e->getMessage());
            }
            
            // Step 5: Delete associated Discounts (by unique demo discount codes)
            // NOTE: Discount table has NO linkID column, so we identify demo discounts by their unique codes
            // This is SAFE because we only delete discounts with specific demo codes, not all discounts for those hostnames
            try {
                $discountTableExists = false;
                try {
                    $sql = "SHOW TABLES LIKE 'shrinkr1_discount'";
                    $statement = WCF::getDB()->prepareStatement($sql);
                    $statement->execute();
                    $discountTableExists = ($statement->fetchSingleColumn() !== false);
                } catch (\Exception) {
                    // Table doesn't exist
                }
                
                if ($discountTableExists) {
                    // Demo discounts are identified by codes starting with "DEMO-"
                    // This is SAFE because no real discount codes should start with "DEMO-"
                    // Delete all discounts that have any code starting with "DEMO-" in their codes field
                    try {
                        // Match any code starting with "DEMO-" in codes field (comma-separated list)
                        // This will match: "DEMO-XXX", "DEMO-XXX,OTHER", "OTHER,DEMO-XXX", etc.
                        $sql = "DELETE FROM shrinkr1_discount WHERE codes LIKE 'DEMO-%' OR codes LIKE '%,DEMO-%' OR codes LIKE 'DEMO-%,%'";
                        $statement = WCF::getDB()->prepareStatement($sql);
                        $statement->execute();
                        $deletedDiscounts = $statement->getAffectedRows();
                        $this->log('OptionFormDemoDataHandler: Deleted ' . $deletedDiscounts . ' discounts (by DEMO- prefix in codes - SAFE)');
                        
                        // Also delete old example discount with BEISPIELCODE2025 (backwards compatibility)
                        try {
                            $sql = "DELETE FROM shrinkr1_discount WHERE codes = 'BEISPIELCODE2025' OR codes LIKE 'BEISPIELCODE2025,%' OR codes LIKE '%,BEISPIELCODE2025' OR codes LIKE '%,BEISPIELCODE2025,%'";
                            $statement = WCF::getDB()->prepareStatement($sql);
                            $statement->execute();
                            $deletedOldExample = $statement->getAffectedRows();
                            if ($deletedOldExample > 0) {
                                $this->log('OptionFormDemoDataHandler: Deleted ' . $deletedOldExample . ' old example discount(s) with BEISPIELCODE2025 (backwards compatibility)');
                            }
                        } catch (\Exception $e) {
                            // Ignore - old example discount might not exist
                        }
                    } catch (\Exception $e) {
                        $this->log('OptionFormDemoDataHandler: Error deleting demo discounts: ' . $e->getMessage());
                    }
                } else {
                    $this->log('OptionFormDemoDataHandler: Discount table does not exist');
                }
            } catch (\Exception $e) {
                $this->log('OptionFormDemoDataHandler: Error deleting discounts: ' . $e->getMessage());
            }
            
            // Step 6: Delete demo URLs themselves (ONLY verified demo URLs)
            try {
                if (!empty($demoUrlIDs)) {
                    // SECURITY: Only delete by verified linkID list (already verified to have DEMO- hash)
                    $placeholders = str_repeat('?,', count($demoUrlIDs) - 1) . '?';
                    $sql = "DELETE FROM shrinkr1_link WHERE linkID IN ({$placeholders}) AND hash LIKE 'DEMO-%'";
                    $statement = WCF::getDB()->prepareStatement($sql);
                    $statement->execute($demoUrlIDs);
                    $deletedUrls = $statement->getAffectedRows();
                    $this->log('OptionFormDemoDataHandler: Deleted ' . $deletedUrls . ' demo URLs (by verified linkID with DEMO- hash check)');
                    
                    if ($deletedUrls < count($demoUrlIDs)) {
                        $this->log('OptionFormDemoDataHandler: WARNING - Expected to delete ' . count($demoUrlIDs) . ' URLs but only deleted ' . $deletedUrls);
                    }
                } else {
                    $this->log('OptionFormDemoDataHandler: No verified demo URL IDs to delete');
                }
            } catch (\Exception $e) {
                $this->log('OptionFormDemoDataHandler: CRITICAL ERROR deleting demo URLs: ' . $e->getMessage());
                throw $e; // Re-throw for outer catch block
            }
            
            $this->log('OptionFormDemoDataHandler: Demo data cleanup completed successfully');
        } catch (\Exception $e) {
            $this->log('OptionFormDemoDataHandler: Demo data cleanup failed: ' . $e->getMessage());
            $this->log('OptionFormDemoDataHandler: Stack trace: ' . $e->getTraceAsString());
        }
    }
    
    /**
     * Helper function for logging
     * Logs to multiple locations for debugging
     */
    private function log(string $message): void
    {
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[{$timestamp}] [DemoDataHandler] {$message}";
        
        // Always log to PHP error_log (visible in server logs)
        error_log($logMessage);
        
        // Try to write to a log file in the plugin directory
        try {
            $logFile = WCF_DIR . 'log/demodata.log';
            $logDir = dirname($logFile);
            if (!is_dir($logDir)) {
                @mkdir($logDir, 0755, true);
            }
            if (is_writable($logDir) || (file_exists($logFile) && is_writable($logFile))) {
                @file_put_contents($logFile, $logMessage . PHP_EOL, FILE_APPEND | LOCK_EX);
            }
        } catch (\Exception $e) {
            // Ignore file write errors
        }
        
        // Also try to log to WoltLab's error log system
        try {
            if (class_exists('wcf\system\exception\SystemException') && class_exists('wcf\system\WCF')) {
                $exception = new \wcf\system\exception\SystemException($logMessage, 0, '', null);
                if (method_exists('wcf\system\WCF', 'getExceptionLogger')) {
                    \wcf\system\WCF::getExceptionLogger()->logException($exception);
                }
            }
        } catch (\Exception $e) {
            // Ignore - already logged to error_log
        }
    }
}
