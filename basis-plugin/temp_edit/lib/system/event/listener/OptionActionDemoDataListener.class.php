<?php

namespace shrinkr\system\event\listener;

use wcf\data\option\OptionAction;
use wcf\system\event\listener\AbstractEventListener;
use wcf\system\WCF;

/**
 * Listens to option changes and installs demo data when shrinkr_install_demo_data is enabled.
 *
 * @author      Sunny C
 * @copyright   2026 Sunny C
 * @license     Commercial License
 * @package     de.sunnyc.wsc.shrinkr
 */
class OptionActionDemoDataListener extends AbstractEventListener
{
    /**
     * @inheritDoc
     */
    public function onFinalizeAction(OptionAction $action): void
    {
        // Log that the event listener was called
        $this->log('Event listener called for OptionAction');
        $this->log('Action name: ' . $action->getActionName());
        
        // Check current value from database (option was already saved at this point)
        // We check the database because:
        // 1. For 'updateAll', options are updated by ID, not by name
        // 2. The option is already saved when finalizeAction is called
        // 3. This is the most reliable way to get the current value
        try {
            // Small delay to ensure database transaction is committed
            // (finalizeAction is called after the action, but transaction might not be committed yet)
            usleep(100000); // 100ms delay
            
            $option = \wcf\data\option\Option::getOptionByName('shrinkr_install_demo_data');
            if (!$option) {
                $this->log('Option shrinkr_install_demo_data does not exist, skipping');
                return;
            }
            
            $installDemoData = (bool) $option->optionValue;
            $this->log('Option value from database: ' . ($installDemoData ? '1' : '0') . ' (optionID: ' . $option->optionID . ')');
        } catch (\Exception $e) {
            $this->log('Error reading option: ' . $e->getMessage());
            $this->log('Stack trace: ' . $e->getTraceAsString());
            return;
        }
        
        // Only install if option is enabled
        if (!$installDemoData) {
            $this->log('Option is disabled, skipping demo data installation');
            return;
        }
        
        // Check if demo data already exists
        try {
            $sql = "SELECT COUNT(*) FROM shrinkr1_link WHERE hash LIKE 'DEMO-%'";
            $statement = WCF::getDB()->prepareStatement($sql);
            $statement->execute();
            $existingCount = $statement->fetchSingleColumn();
            
            $this->log('Existing demo URLs count: ' . $existingCount);
            
            if ($existingCount > 0) {
                // Demo data already exists, skip installation
                $this->log('Demo data already exists, skipping installation');
                return;
            }
        } catch (\Exception $e) {
            $this->log('Error checking existing demo data: ' . $e->getMessage());
            return;
        }
        
        // Call the demo data installation function from the post-install script
        // We'll include the post-install script which contains the installation logic
        // The script checks if demo data already exists, so it's safe to call multiple times
        $postInstallScript = WCF_DIR . 'shrinkr/acp/install_de.sunnyc.wsc.shrinkr_postInstall.php';
        
        $this->log('Post-install script path: ' . $postInstallScript);
        $this->log('Post-install script exists: ' . (file_exists($postInstallScript) ? 'yes' : 'no'));
        
        if (file_exists($postInstallScript)) {
            // Temporarily set a flag to indicate we're calling from the event listener
            // This allows the post-install script to know it's being called from here
            // and not during initial installation
            $GLOBALS['shrinkr_demo_data_from_event'] = true;
            
            $this->log('Calling post-install script...');
            
            // Include the post-install script
            // The script will check the option value and install demo data if needed
            // (The option is already saved to the database at this point)
            require_once($postInstallScript);
            
            $this->log('Post-install script completed');
            
            // Unset the flag
            unset($GLOBALS['shrinkr_demo_data_from_event']);
        } else {
            $this->log('Post-install script not found!');
        }
    }
    
    /**
     * Helper function for logging
     */
    private function log(string $message): void
    {
        try {
            if (class_exists('wcf\system\exception\SystemException') && class_exists('wcf\system\WCF')) {
                $exception = new \wcf\system\exception\SystemException('[DemoDataListener] ' . $message, 0, '', null);
                if (method_exists('wcf\system\WCF', 'getExceptionLogger')) {
                    \wcf\system\WCF::getExceptionLogger()->logException($exception);
                }
            } else {
                error_log('[DemoDataListener] ' . $message);
            }
        } catch (\Exception $e) {
            error_log('[DemoDataListener] ' . $message);
        }
    }
}
