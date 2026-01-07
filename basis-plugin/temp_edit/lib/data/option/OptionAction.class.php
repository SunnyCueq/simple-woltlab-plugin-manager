<?php

namespace urlshort\data\option;

use wcf\data\AbstractDatabaseObjectAction;
use wcf\system\application\ApplicationHandler;
use wcf\system\WCF;

/**
 * Handles option-related actions.
 *
 * @author      Julian Pfeil, Titus Kirch <https://julian-pfeil.de>
 * @link        https://darkwood.design/store/user-file-list/1298-julian-pfeil/
 * @copyright   2022 Julian Pfeil Websites & Co.
 * @license     License for Commercial Plugins <https://julian-pfeil.de/lizenz/>
 *
 * @package    dev.tkirch.wsc.urlshort
 * @subpackage data.option
 */
class OptionAction extends AbstractDatabaseObjectAction
{
    /**
     * @inheritDoc
     */
    protected $className = \wcf\data\option\OptionEditor::class;

    /**
     * @inheritDoc
     */
    protected $allowGuestAccess = ['generateRewriteRules'];

    /**
     * @inheritDoc
     */
    protected $permissionsDelete = ['admin.configuration.canEditOption'];

    /**
     * @inheritDoc
     */
    protected $permissionsUpdate = ['admin.configuration.canEditOption'];

    /**
     * @inheritDoc
     */
    protected $requireACP = ['generateRewriteRules'];

    /**
     * Validates the generateRewriteRules action.
     */
    public function validateGenerateRewriteRules()
    {
        WCF::getSession()->checkPermissions(['admin.urlshort.canManageUrls']);
    }

    /**
     * Generates the rewrite rules for the URL shortener.
     *
     * @return string
     */
    public function generateRewriteRules()
    {
        // Detect webserver
        $detectedWebserver = $this->detectWebserver();
        
        // Generate rewrite rules
        $rewriteRules = $this->fetchRewriteRules();
        
        // Build rewrite rules array - only include detected webserver
        $rulesArray = [];
        if ($detectedWebserver === 'apache') {
            $rulesArray['apache'] = [
                '.htaccess' => $rewriteRules['apache'],
            ];
        } elseif ($detectedWebserver === 'nginx') {
            $rulesArray['nginx'] = [
                'nginx.conf' => $rewriteRules['nginx'],
            ];
        } else {
            // Fallback: Show both if webserver could not be detected
            $rulesArray = [
                'apache' => [
                    '.htaccess' => $rewriteRules['apache'],
                ],
                'nginx' => [
                    'nginx.conf' => $rewriteRules['nginx'],
                ],
            ];
        }

        return WCF::getTPL()->fetch('__urlshortRewriteRulesOutput', 'urlshort', [
            'rewriteRules' => $rulesArray,
            'detectedWebserver' => $detectedWebserver,
        ]);
    }
    
    /**
     * Detects the webserver type (Apache or nginx).
     * 
     * @return string|null 'apache', 'nginx', or null if unknown
     */
    protected function detectWebserver(): ?string
    {
        // Prüfe SERVER_SOFTWARE
        if (isset($_SERVER['SERVER_SOFTWARE'])) {
            $serverSoftware = \strtolower($_SERVER['SERVER_SOFTWARE']);
            if (\strpos($serverSoftware, 'nginx') !== false) {
                return 'nginx';
            }
            if (\strpos($serverSoftware, 'apache') !== false) {
                return 'apache';
            }
        }
        
        // Fallback: Prüfe ob .htaccess existiert (Apache)
        $rootDir = null;
        foreach (ApplicationHandler::getInstance()->getApplications() as $app) {
            $packageDir = $app->getPackage()->getAbsolutePackageDir();
            if ($rootDir === null || \strlen($packageDir) < \strlen($rootDir)) {
                $rootDir = $packageDir;
            }
        }
        
        if ($rootDir !== null) {
            $rootDir = \rtrim($rootDir, '/') . '/';
            $htaccessPath = $rootDir . '.htaccess';
            if (\file_exists($htaccessPath)) {
                return 'apache';
            }
        }
        
        // Wenn .htaccess nicht existiert, könnte es nginx sein
        // Aber wir können nicht sicher sein, daher null zurückgeben
        return null;
    }

    /**
     * Returns the rewrite rules for Apache and nginx.
     *
     * @return array Array with 'apache' and 'nginx' keys
     */
    protected function fetchRewriteRules()
    {
        $apacheRules = <<<'SNIPPET'
# URL-Shortener: /r/ auf /urls/r/ umschreiben
RewriteCond %{SCRIPT_FILENAME} !-d
RewriteCond %{SCRIPT_FILENAME} !-f
RewriteRule ^r/(.*)$ urls/r/$1 [L,QSA]
SNIPPET;
        
        $nginxRules = <<<'SNIPPET'
# URL-Shortener: /r/ auf /urls/r/ umschreiben
# Diese Regeln müssen in den server-Block Ihrer nginx.conf eingefügt werden
# WICHTIG: Die /r/ Regel muss VOR den Standard-WoltLab-Regeln stehen!

location ~ ^/r/(.*)$ {
    try_files $uri $uri/ @short;
}

location @short {
    rewrite /r/(.*)$ /urls/r/$1 last;
}

# Hinweis: Stellen Sie sicher, dass die Standard-WoltLab-Regeln
# für /urls/ ebenfalls in Ihrer nginx.conf vorhanden sind.
SNIPPET;
        
        return [
            'apache' => $apacheRules,
            'nginx' => $nginxRules,
        ];
    }

    /**
     * @inheritDoc
     */
    public function finalizeAction()
    {
        parent::finalizeAction();

        // Listen to option changes and install demo data when urlshort_install_demo_data is enabled
        $this->log('Event listener called for OptionAction');
        $this->log('Action name: ' . $this->getActionName());
        
        // Check current value from database (option was already saved at this point)
        // We check the database because:
        // 1. For 'updateAll', options are updated by ID, not by name
        // 2. The option is already saved when finalizeAction is called
        // 3. This is the most reliable way to get the current value
        try {
            // Small delay to ensure database transaction is committed
            // (finalizeAction is called after the action, but transaction might not be committed yet)
            usleep(100000); // 100ms delay
            
            $option = \wcf\data\option\Option::getOptionByName('urlshort_install_demo_data');
            if (!$option) {
                $this->log('Option urlshort_install_demo_data does not exist, skipping');
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
            $sql = "SELECT COUNT(*) FROM urlshort1_url WHERE hash LIKE 'DEMO-%'";
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
        $postInstallScript = WCF_DIR . 'urls/acp/install_dev.tkirch.wsc.urlshort_postInstall.php';
        
        $this->log('Post-install script path: ' . $postInstallScript);
        $this->log('Post-install script exists: ' . (file_exists($postInstallScript) ? 'yes' : 'no'));
        
        if (file_exists($postInstallScript)) {
            // Temporarily set a flag to indicate we're calling from the event listener
            // This allows the post-install script to know it's being called from here
            // and not during initial installation
            $GLOBALS['urlshort_demo_data_from_event'] = true;
            
            $this->log('Calling post-install script...');
            
            // Include the post-install script
            // The script will check the option value and install demo data if needed
            // (The option is already saved to the database at this point)
            require_once($postInstallScript);
            
            $this->log('Post-install script completed');
            
            // Unset the flag
            unset($GLOBALS['urlshort_demo_data_from_event']);
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
