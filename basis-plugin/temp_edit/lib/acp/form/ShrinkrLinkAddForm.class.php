<?php

namespace shrinkr\acp\form;

use shrinkr\data\custombutton\CustomButtonList;
use shrinkr\data\featuredlink\FeaturedLinkList;
use shrinkr\data\special\SpecialList;
use shrinkr\data\shrinkrlink\ShrinkrLinkAction;
use shrinkr\util\ShrinkrUtil;
use wcf\form\AbstractFormBuilderForm;
use wcf\system\application\ApplicationHandler;
use wcf\system\form\builder\container\FormContainer;
use wcf\system\form\builder\container\TabFormContainer;
use wcf\system\form\builder\container\TabMenuFormContainer;
use wcf\system\form\builder\field\TextFormField;
use wcf\system\form\builder\field\UploadFormField;
use wcf\system\form\builder\field\UrlFormField;
use wcf\system\option\OptionHandler;
use wcf\system\exception\UserInputException;
use wcf\system\user\authentication\password\PasswordAlgorithmManager;
use wcf\system\WCF;

/**
 * Form for adding a new short link.
 * 
 * ACP form for creating new shortened links. Provides form fields for URL,
 * custom title, hash, and Open Graph image. Uses WoltLab's FormBuilder API
 * for dynamic form generation with tabbed interface.
 *
 * @author      Sunny C
 * @copyright   2026 Sunny C
 * @license     License for Commercial Plugins
 * @link        https://sunnyc.de
 * @package     de.sunnyc.wsc.shrinkr
 * @subpackage  acp.form
 */
class ShrinkrLinkAddForm extends AbstractFormBuilderForm
{
    /**
     * Required permissions to access this form.
     *
     * @var    string[]
     */
    public $neededPermissions = ['admin.shrinkr.canManageLinks'];

    /**
     * Active menu item identifier for navigation highlighting.
     *
     * @var    string
     */
    public $activeMenuItem = 'shrinkr.acp.menu.link.link.add';

    /**
     * Action class for handling form submissions.
     *
     * @var    string
     */
    public $objectActionClass = ShrinkrLinkAction::class;

    /**
     * Form action name (create, update, delete).
     *
     * @var    string
     */
    public $formAction = 'create';

    /**
     * Maximum length for URL title field (matches database VARCHAR length).
     *
     * @var    int
     */
    private const MAX_URL_TITLE_LENGTH = 255;

    /**
     * Creates the form structure using WoltLab's FormBuilder API.
     * 
     * Builds a tabbed form with basic fields (URL, title, hash, Open Graph image).
     * The design tab is only available in the edit form (ShrinkrLinkEditForm).
     *
     * @return  void
     */
    protected function createForm()
    {
        parent::createForm();

        $tabMenu = TabMenuFormContainer::create('linkTabMenu');

        $basicTab = TabFormContainer::create('basicTab')
            ->label('wcf.global.form.data');

        $basicContainer = FormContainer::create('basicFields')
            ->appendChildren([
                UrlFormField::create('url')
                    ->label('wcf.shrinkr.urlGoal')
                    ->description('wcf.shrinkr.urlGoal.description')
                    ->required()
                    ->autoFocus()
                    ->maximumLength(65535),

                TextFormField::create('linkTitle')
                    ->label('wcf.shrinkr.url.urlTitle')
                    ->description('wcf.shrinkr.url.urlTitle.description')
                    ->placeholder('wcf.shrinkr.url.linkTitle.placeholder')
                    ->maximumLength(self::MAX_URL_TITLE_LENGTH),

                TextFormField::create('hash')
                    ->label('wcf.shrinkr.url.hash')
                    ->description('wcf.shrinkr.url.hash.description')
                    ->maximumLength(64)
                    ->value($this->getDefaultHash()),

                UploadFormField::create('ogImage')
                    ->label('wcf.shrinkr.url.ogImage')
                    ->description('wcf.shrinkr.url.ogImage.description')
                    ->imageOnly(true),

                TextFormField::create('password')
                    ->label('wcf.shrinkr.link.password')
                    ->description('wcf.shrinkr.link.password.description')
                    ->attribute('type', 'password'),
            ]);

        $basicTab->appendChild($basicContainer);
        $tabMenu->appendChild($basicTab);

        $this->form->appendChild($tabMenu);
    }

    /**
     * Generates a default hash for new links.
     * 
     * Creates a unique hash identifier using ShrinkrUtil::generateHash().
     * This hash is used as the short URL identifier (e.g., /r/{hash}/).
     *
     * @return  string  A generated hash string
     */
    protected function getDefaultHash(): string
    {
        return ShrinkrUtil::generateHash();
    }

    /**
     * Returns the minimum password length.
     *
     * @return  int     Minimum password length (hardcoded: 8)
     */
    protected function getPasswordMinLength(): int
    {
        return 8; // Hardcoded minimum password length
    }

    /**
     * @inheritDoc
     */
    public function save()
    {
        // Note: ogImage upload is handled automatically by UploadFormField
        // Password hashing is handled before save

        // Get form data
        $formData = $this->form->getData();
        
        // Handle password hashing and validation
        if (isset($formData['data']['password'])) {
            $password = $formData['data']['password'];
            
            if (!empty($password)) {
                // Validate password length (serverseitig, wie WoltLab es macht)
                $minLength = $this->getPasswordMinLength();
                if (\strlen($password) < $minLength) {
                    throw new UserInputException('password', 'wcf.shrinkr.password.minLength', ['minLength' => $minLength]);
                }
                
                // Hash password using WoltLab PasswordAlgorithmManager (WoltLab 6.1 API)
                $passwordHash = PasswordAlgorithmManager::getInstance()->getDefaultAlgorithm()->hash($password);
                $formData['data']['passwordHash'] = $passwordHash;
            } else {
                // Password field was cleared, remove password
                $formData['data']['passwordHash'] = null;
            }
            
            // Remove plain password from data
            unset($formData['data']['password']);
            
            // Update form data
            $this->form->data($formData);
        }

        parent::save();
    }

    /**
     * @inheritDoc
     */
    public function assignVariables()
    {
        parent::assignVariables();

        // Make option available in template
        $removeUrlsPrefix = \defined('SHRINKR_REMOVE_SHRINKR_PREFIX') && SHRINKR_REMOVE_SHRINKR_PREFIX;

        // Generate rewrite rules if needed
        $rewriteRules = [];
        $htaccessRuleExists = false;
        $detectedWebserver = null;
        if ($removeUrlsPrefix) {
            $detectedWebserver = $this->detectWebserver();
            $rewriteRules = $this->fetchRewriteRules();
            $htaccessRuleExists = $this->checkHtaccessRule();
        }

        // Load custom buttons for URL edit page
        $urlCustomButtons = [];
        if (isset($this->formObject) && isset($this->formObject->linkID) && $this->formObject->linkID) {
            $customButtonList = new CustomButtonList();
            $customButtonList->getConditionBuilder()->add('linkID = ?', [$this->formObject->linkID]);
            $customButtonList->sqlOrderBy = 'sortOrder ASC';
            $customButtonList->readObjects();

            $urlCustomButtons = $customButtonList->getObjects();
        }

        // Set shortUrl for success message (after create/update)
        $shortUrl = '';
        if (isset($this->formObject) && $this->formObject->linkID) {
            $shortUrl = $this->formObject->getShortedUrl();
        }

        WCF::getTPL()->assign([
            'urlCustomButtons' => $urlCustomButtons,
            'removeUrlsPrefix' => $removeUrlsPrefix,
            'rewriteRules' => $rewriteRules,
            'htaccessRuleExists' => $htaccessRuleExists,
            'detectedWebserver' => $detectedWebserver,
            'acpPageSubMenuCategoryList' => 'shrinkr.acp.menu.link.link.list',
            'shortUrl' => $shortUrl,
        ]);
    }

    /**
     * Detects the webserver type (Apache or nginx).
     *
     * @return string|null 'apache', 'nginx', or null if unknown
     */
    protected function detectWebserver(): ?string
    {
        // Check SERVER_SOFTWARE
        if (isset($_SERVER['SERVER_SOFTWARE'])) {
            $serverSoftware = \strtolower($_SERVER['SERVER_SOFTWARE']);
            if (\strpos($serverSoftware, 'nginx') !== false) {
                return 'nginx';
            }
            if (\strpos($serverSoftware, 'apache') !== false) {
                return 'apache';
            }
        }

        // Fallback: Check if .htaccess exists (Apache)
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

        return null;
    }

    /**
     * Returns the rewrite rules for Apache and nginx.
     *
     * @return array Array with 'apache' and 'nginx' keys
     */
    protected function fetchRewriteRules(): array
    {
        $apacheRules = <<<'SNIPPET'
# Shr1nkr: /r/ auf /shrinkr/r/ umschreiben
RewriteCond %{SCRIPT_FILENAME} !-d
RewriteCond %{SCRIPT_FILENAME} !-f
RewriteRule ^r/(.*)$ shrinkr/r/$1 [L,QSA]
SNIPPET;

        $nginxInstructions = WCF::getLanguage()->get('shrinkr.acp.url.rewrite.nginx.instructions');
        $nginxImportant = WCF::getLanguage()->get('shrinkr.acp.url.rewrite.nginx.important');
        $nginxNote = WCF::getLanguage()->get('shrinkr.acp.url.rewrite.nginx.note');

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

    /**
     * Checks if the .htaccess rule for Shr1nkr is already set.
     *
     * @return bool
     */
    protected function checkHtaccessRule(): bool
    {
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

        $rootDir = \rtrim($rootDir, '/') . '/';
        $htaccessPath = $rootDir . '.htaccess';

        if (!\is_readable($htaccessPath)) {
            return false;
        }

        $content = \file_get_contents($htaccessPath);
        if ($content === false) {
            return false;
        }

        $cleanContent = \preg_replace('/^\s*#.*$/m', '', $content);

        // Check for Shr1nkr rewrite rule
        $pattern = '/RewriteRule\s+\^r\/([^$]*)\$\s+shrinkr\/r\/\$1/is';

        if (\preg_match($pattern, $cleanContent) === 1) {
            return true;
        }

        return \stripos($cleanContent, 'shrinkr/r/$1') !== false;
    }
}
