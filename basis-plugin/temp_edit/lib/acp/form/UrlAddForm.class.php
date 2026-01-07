<?php

namespace urlshort\acp\form;

use urlshort\data\custombutton\CustomButtonList;
use urlshort\data\url\UrlAction;
use urlshort\util\UrlUtil;
use wcf\form\AbstractForm;
use wcf\system\application\ApplicationHandler;
use wcf\system\exception\UserInputException;
use wcf\system\WCF;
use wcf\util\StringUtil;

/**
 * @author      Julian Pfeil, Titus Kirch <https://julian-pfeil.de>
 * @link        https://darkwood.design/store/user-file-list/1298-julian-pfeil/
 * @copyright   2022 Julian Pfeil Websites & Co.
 * @license     License for Commercial Plugins <https://julian-pfeil.de/lizenz/>
 *
 * @package    dev.tkirch.wsc.urlshort
 * @subpackage acp.form
 */
class UrlAddForm extends AbstractForm
{
    /**
     * @inheritDoc
     */
    public $activeMenuItem = 'urlshort.acp.menu.link.url.add';
    
    /**
     * @var	string
     */
    public $hash = '';
    
    /**
     * @var	string
     */
    public $url = '';
    
    /**
     * URL title (from UrlAddEventListener)
     * @var	string
     */
    public $urlTitle = '';
    
    /**
     * Maximum length for URL title field (VARCHAR)
     */
    private const MAX_URL_TITLE_LENGTH = 255;
    
    /**
     * @inheritDoc
     */
    public $neededPermissions = ['admin.urlshort.canManageUrls'];
    
    /**
     * @inheritDoc
     */
    #[\Override]
    public function assignVariables()
    {
        parent::assignVariables();
        
        //generate default hash if hash is empty
        if (empty($this->hash)) {
            $this->hash = UrlUtil::generateHash();
        }
        
        // Make option available in template
        // Option wird automatisch als Konstante definiert, wenn aktiviert
        $removeUrlsPrefix = \defined('URLSHORT_REMOVE_URLS_PREFIX') && URLSHORT_REMOVE_URLS_PREFIX;
        
        // Generate rewrite rules if needed (like WoltLab does)
        $rewriteRules = [];
        $htaccessRuleExists = false;
        $detectedWebserver = null;
        if ($removeUrlsPrefix) {
            $detectedWebserver = $this->detectWebserver();
            $rewriteRules = $this->fetchRewriteRules();
            $htaccessRuleExists = $this->checkHtaccessRule();
        }

        // Assign URL title to template
        $urlTitle = $this->urlTitle;

        // Load custom buttons for URL edit page
        $urlCustomButtons = [];
        if (isset($this->formObject) && isset($this->formObject->urlID) && $this->formObject->urlID) {
            $customButtonList = new CustomButtonList();
            $customButtonList->getConditionBuilder()->add('urlID = ?', [$this->formObject->urlID]);
            $customButtonList->sqlOrderBy = 'sortOrder ASC';
            $customButtonList->readObjects();
            
            $urlCustomButtons = $customButtonList->getObjects();
        } elseif (isset($this->urlObj) && isset($this->urlObj->urlID) && $this->urlObj->urlID) {
            // Fallback: Check urlObj (used in UrlEditForm)
            $customButtonList = new CustomButtonList();
            $customButtonList->getConditionBuilder()->add('urlID = ?', [$this->urlObj->urlID]);
            $customButtonList->sqlOrderBy = 'sortOrder ASC';
            $customButtonList->readObjects();
            
            $urlCustomButtons = $customButtonList->getObjects();
        }
        
        WCF::getTPL()->assign([
            'action' => 'add',
            'hash' => $this->hash,
            'url' => $this->url,
            'urlTitle' => $urlTitle,
            'urlCustomButtons' => $urlCustomButtons,
            'removeUrlsPrefix' => $removeUrlsPrefix,
            'rewriteRules' => $rewriteRules,
            'htaccessRuleExists' => $htaccessRuleExists,
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
     * Checks if the .htaccess rule for URL-Shortener is already set.
     * 
     * @return bool
     */
    protected function checkHtaccessRule(): bool
    {
        // .htaccess liegt immer im Root-Verzeichnis
        // WoltLab-Methode: Verwende ApplicationHandler wie in OptionAction::fetchRewriteRules()
        $rootDir = null;
        foreach (ApplicationHandler::getInstance()->getApplications() as $app) {
            $packageDir = $app->getPackage()->getAbsolutePackageDir();
            if ($rootDir === null || \strlen($packageDir) < \strlen($rootDir)) {
                $rootDir = $packageDir;
            }
        }
        
        if ($rootDir === null) {
            return false;
        }
        
        // WoltLab-Methode: $htaccess = "{$dir}.htaccess" (siehe OptionAction::fetchRewriteRules Zeile 192)
        // getAbsolutePackageDir() sollte bereits einen trailing slash haben, aber sicherheitshalber prüfen
        $rootDir = \rtrim($rootDir, '/') . '/';
        $htaccessPath = $rootDir . '.htaccess';
        
        if (!\is_readable($htaccessPath)) {
            return false;
        }
        
        $content = \file_get_contents($htaccessPath);
        if ($content === false) {
            return false;
        }
        
        // Entferne Kommentare
        $cleanContent = \preg_replace('/^\s*#.*$/m', '', $content);
        
        // Suche nach der eindeutigen RewriteRule: "RewriteRule ^r/" gefolgt von "urls/r/$1"
        // Das ist die spezifische Regel für den URL-Shortener
        // Pattern: RewriteRule ^r/(.*)$ urls/r/$1 [L,QSA]
        // Verwende DOTALL (s) damit . auch Newlines matcht, falls die Regel über mehrere Zeilen geht
        $pattern = '/RewriteRule\s+\^r\/([^$]*)\$\s+urls\/r\/\$1/is';
        
        if (\preg_match($pattern, $cleanContent) === 1) {
            return true;
        }
        
        // Fallback: Einfache String-Suche nach "urls/r/$1" - das ist eindeutig für unsere Regel
        return \stripos($cleanContent, 'urls/r/$1') !== false;
    }
    
    /**
     * @inheritDoc
     */
    #[\Override]
    public function readFormParameters()
    {
        parent::readFormParameters();
        
        if (isset($_POST['hash'])) {
            $this->hash = StringUtil::trim($_POST['hash']);
        }
        if (isset($_POST['url'])) {
            $this->url = StringUtil::trim($_POST['url']);
        }

        // Read URL title from POST
        if (isset($_POST['urlTitle'])) {
            $this->urlTitle = StringUtil::trim($_POST['urlTitle']);
        }
    }
    
    /**
     * @inheritDoc
     */
    #[\Override]
    public function readData()
    {
        parent::readData();

        // Read URL title from formObject (for edit mode)
        if (isset($this->formObject) && isset($this->formObject->urlTitle)) {
            $this->urlTitle = $this->formObject->urlTitle;
        } elseif (isset($this->urlObj) && isset($this->urlObj->urlTitle)) {
            // Fallback: Check urlObj (used in UrlEditForm)
            $this->urlTitle = $this->urlObj->urlTitle;
        }
    }

    /**
     * @inheritDoc
     */
    #[\Override]
    public function save()
    {
        parent::save();
        
        // Set URL title in additionalFields
        if (!isset($this->additionalFields)) {
            $this->additionalFields = [];
        }
        $this->additionalFields['urlTitle'] = $this->urlTitle;
        
        $this->objectAction = new UrlAction([], 'create', [
            'data' => \array_merge($this->additionalFields, [
                'hash' => $this->hash,
                'url' => $this->url,
            ]),
        ]);
        $returnValues = $this->objectAction->executeAction()['returnValues'];
        
        $this->saved();
        
        //reset values
        $this->hash = '';
        $this->url = '';
        $this->urlTitle = '';
        
        //show success message
        WCF::getTPL()->assign([
            'success' => true,
            'shortUrl' => $returnValues->getShortedUrl(true),
        ]);
    }
    
    /**
     * @inheritDoc
     */
    #[\Override]
    public function validate()
    {
        parent::validate();
        
        //validate hash
        $this->validateHash();
        
        //validate url
        UrlUtil::isValidUrl($this->url);

        // Validate URL title length
        if (!empty($this->urlTitle) && mb_strlen($this->urlTitle) > self::MAX_URL_TITLE_LENGTH) {
            throw new UserInputException('urlTitle', 'tooLong');
        }
    }

    /**
     * check if the hash is not unique or not valid
     */
    protected function validateHash()
    {
        UrlUtil::isValidHash($this->hash);
    }
}
