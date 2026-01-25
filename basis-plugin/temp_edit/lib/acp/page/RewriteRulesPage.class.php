<?php

namespace shrinkr\acp\page;

use shrinkr\util\ShrinkrUtil;
use wcf\page\AbstractPage;
use wcf\system\application\ApplicationHandler;
use wcf\system\WCF;

/**
 * Displays rewrite rules in a dialog.
 * 
 * ACP page that generates and displays Apache .htaccess and Nginx rewrite rules
 * for shortened URLs. Used by the rewrite rule generator in the ACP options.
 * Determines the correct .htaccess path based on WoltLab installation structure.
 *
 * @author      Sunny C
 * @copyright   2026 Sunny C
 * @license     License for Commercial Plugins
 * @link        https://sunnyc.de
 * @package     de.sunnyc.wsc.shrinkr
 * @subpackage  acp.page
 */
class RewriteRulesPage extends AbstractPage
{
    /**
     * Required permissions to access this page.
     *
     * @var    string[]
     */
    public $neededPermissions = ['admin.shrinkr.canManageLinks'];
    
    /**
     * Template name for rendering rewrite rules.
     *
     * @var    string
     */
    public $templateName = '__shrinkrRewriteRulesOutput';
    
    /**
     * Application name for template lookup.
     *
     * @var    string
     */
    public $templateNameApplication = 'shrinkr';
    
    /**
     * Reads data and generates rewrite rules.
     * 
     * Determines the .htaccess path based on WoltLab installation structure
     * and generates rewrite rules for both Apache and Nginx.
     *
     * @return  void
     */
    public function readData()
    {
        parent::readData();
        
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
        } else {
            $htaccessPath = '.htaccess';
        }
        
        $rewriteRules = $this->fetchRewriteRules();
        
        // Get menu badge text
        $menuBadgeText = ShrinkrUtil::getMenuBadgeText();
        
        WCF::getTPL()->assign([
            'rewriteRules' => [
                'apache' => [
                    $htaccessPath => $rewriteRules['apache'],
                ],
                'nginx' => [
                    'nginx.conf' => $rewriteRules['nginx'],
                ],
            ],
            'menuBadgeText' => $menuBadgeText,
        ]);
    }
    
    /**
     * Returns the rewrite rules for Apache and Nginx.
     * 
     * Generates Apache .htaccess rewrite rules and Nginx configuration snippets
     * for redirecting shortened URLs from /r/ to /shrinkr/r/.
     *
     * @return  array   Array with 'apache' and 'nginx' keys containing rewrite rules
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
