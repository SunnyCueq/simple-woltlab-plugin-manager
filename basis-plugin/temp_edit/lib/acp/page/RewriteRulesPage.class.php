<?php

namespace shrinkr\acp\page;

use wcf\page\AbstractPage;
use wcf\system\application\ApplicationHandler;
use wcf\system\WCF;

/**
 * Displays rewrite rules in a dialog.
 *
 * @author      Sunny C, Sunny C <https://sunnyc.de>
 * @link        https://sunnyc.de
 * @copyright   2026 Sunny C Websites & Co.
 * @license     License for Commercial Plugins <https://sunnyc.de/lizenz/>
 *
 * @package    de.sunnyc.wsc.shrinkr
 * @subpackage acp.page
 */
class RewriteRulesPage extends AbstractPage
{
    /**
     * @inheritDoc
     */
    public $neededPermissions = ['admin.shrinkr.canManageLinks'];
    
    /**
     * @inheritDoc
     */
    public $templateName = '__shrinkrRewriteRulesOutput';
    
    /**
     * @inheritDoc
     */
    public $templateNameApplication = 'shrinkr';
    
    /**
     * @inheritDoc
     */
    public function readData()
    {
        parent::readData();
        
        // Get .htaccess path (root directory)
        // WoltLab-Methode: Verwende ApplicationHandler wie in OptionAction::fetchRewriteRules()
        $rootDir = null;
        foreach (ApplicationHandler::getInstance()->getApplications() as $app) {
            $packageDir = $app->getPackage()->getAbsolutePackageDir();
            if ($rootDir === null || \strlen($packageDir) < \strlen($rootDir)) {
                $rootDir = $packageDir;
            }
        }
        
        // WoltLab-Methode: $htaccess = "{$dir}.htaccess" (siehe OptionAction::fetchRewriteRules Zeile 192)
        // getAbsolutePackageDir() should already have a trailing slash, but check for safety
        if ($rootDir !== null) {
            $rootDir = \rtrim($rootDir, '/') . '/';
            $htaccessPath = $rootDir . '.htaccess';
        } else {
            $htaccessPath = '.htaccess';
        }
        
        // Generate rewrite rules
        $rewriteRules = $this->fetchRewriteRules();
        
        // Assign to template
        WCF::getTPL()->assign([
            'rewriteRules' => [
                'apache' => [
                    $htaccessPath => $rewriteRules['apache'],
                ],
                'nginx' => [
                    'nginx.conf' => $rewriteRules['nginx'],
                ],
            ],
        ]);
    }
    
    /**
     * Returns the rewrite rules for Apache and nginx.
     *
     * @return array Array with 'apache' and 'nginx' keys
     */
    protected function fetchRewriteRules()
    {
        $apacheRules = <<<'SNIPPET'
# Shr1nkr: /r/ auf /shrinkr/r/ umschreiben
RewriteCond %{SCRIPT_FILENAME} !-d
RewriteCond %{SCRIPT_FILENAME} !-f
RewriteRule ^r/(.*)$ shrinkr/r/$1 [L,QSA]
SNIPPET;
        
        $nginxInstructions = \WCF::getLanguage()->get('shrinkr.acp.url.rewrite.nginx.instructions');
        $nginxImportant = \WCF::getLanguage()->get('shrinkr.acp.url.rewrite.nginx.important');
        $nginxNote = \WCF::getLanguage()->get('shrinkr.acp.url.rewrite.nginx.note');
        
        $nginxRules = <<<SNIPPET
# Shr1nkr: /r/ auf /shrinkr/r/ umschreiben
# {$nginxInstructions}
# {$nginxImportant}

location ~ ^/r/(.*)$ {
    try_files \$uri \$uri/ @short;
}

location @short {
    rewrite /r/(.*)$ /shrinkr/r/$1 last;
}

# {$nginxNote}
SNIPPET;
        
        return [
            'apache' => $apacheRules,
            'nginx' => $nginxRules,
        ];
    }
}
