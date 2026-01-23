<?php

namespace shrinkr\system\event\listener;

use wcf\data\option\OptionAction;
use wcf\system\event\listener\AbstractEventListener;
use wcf\system\WCF;

/**
 * Listens to option changes and installs demo data when shrinkr_install_demo_data is enabled.
 * 
 * Event listener for OptionAction that triggers demo data installation when the
 * shrinkr_install_demo_data option is enabled. Delegates to the post-install script
 * for actual installation logic.
 *
 * @author      Sunny C
 * @copyright   2026 Sunny C
 * @license     License for Commercial Plugins
 * @link        https://sunnyc.de
 * @package     de.sunnyc.wsc.shrinkr
 * @subpackage  system.event.listener
 */
class OptionActionDemoDataListener extends AbstractEventListener
{
    /**
     * Executes the event listener when OptionAction is finalized.
     * 
     * Checks if shrinkr_install_demo_data is enabled and installs demo data
     * if it doesn't already exist. Includes a small delay to ensure database
     * transaction is committed before reading the option value.
     *
     * @param   OptionAction  $action  The OptionAction instance
     * @return  void
     */
    public function onFinalizeAction(OptionAction $action): void
    {
        $this->log('Event listener called for OptionAction');
        $this->log('Action name: ' . $action->getActionName());
        
        try {
            usleep(100000);
            
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
        
        if (!$installDemoData) {
            $this->log('Option is disabled, skipping demo data installation');
            return;
        }
        
        try {
            $sql = "SELECT COUNT(*) FROM shrinkr1_link WHERE hash LIKE 'DEMO-%'";
            $statement = WCF::getDB()->prepareStatement($sql);
            $statement->execute();
            $existingCount = $statement->fetchSingleColumn();
            
            $this->log('Existing demo URLs count: ' . $existingCount);
            
            if ($existingCount > 0) {
                $this->log('Demo data already exists, skipping installation');
                return;
            }
        } catch (\Exception $e) {
            $this->log('Error checking existing demo data: ' . $e->getMessage());
            return;
        }
        
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
