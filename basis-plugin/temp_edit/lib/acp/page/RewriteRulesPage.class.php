<?php

namespace urlshort\acp\page;

use wcf\page\AbstractPage;
use wcf\system\application\ApplicationHandler;
use wcf\system\WCF;

/**
 * Displays rewrite rules in a dialog.
 *
 * @author      Julian Pfeil, Titus Kirch <https://julian-pfeil.de>
 * @link        https://darkwood.design/store/user-file-list/1298-julian-pfeil/
 * @copyright   2022 Julian Pfeil Websites & Co.
 * @license     License for Commercial Plugins <https://julian-pfeil.de/lizenz/>
 *
 * @package    dev.tkirch.wsc.urlshort
 * @subpackage acp.page
 */
class RewriteRulesPage extends AbstractPage
{
    /**
     * @inheritDoc
     */
    public $neededPermissions = ['admin.urlshort.canManageUrls'];
    
    /**
     * @inheritDoc
     */
    public $templateName = '__urlshortRewriteRulesOutput';
    
    /**
     * @inheritDoc
     */
    public $templateNameApplication = 'urlshort';
    
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
        // getAbsolutePackageDir() sollte bereits einen trailing slash haben, aber sicherheitshalber prüfen
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
}
