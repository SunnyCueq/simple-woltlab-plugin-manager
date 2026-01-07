<?php

namespace urlshort\acp\form;

use urlshort\data\url\UrlAction;
use urlshort\util\UrlUtil;
use wcf\form\AbstractForm;
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
     * @inheritDoc
     */
    public $neededPermissions = ['admin.urlshort.canManageUrls'];
    
    /**
     * @inheritDoc
     */
    public function assignVariables()
    {
        parent::assignVariables();
        
        //generate default hash if hash is empty
        if (empty($this->hash)) {
            $this->hash = UrlUtil::generateHash();
        }
        
        WCF::getTPL()->assign([
            'action' => 'add',
            'hash' => $this->hash,
            'url' => $this->url,
        ]);
    }
    
    /**
     * @inheritDoc
     */
    public function readFormParameters()
    {
        parent::readFormParameters();
        
        if (isset($_POST['hash'])) {
            $this->hash = StringUtil::trim($_POST['hash']);
        }
        if (isset($_POST['url'])) {
            $this->url = StringUtil::trim($_POST['url']);
        }
    }
    
    /**
     * @inheritDoc
     */
    public function save()
    {
        parent::save();
        
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
        
        //show success message
        WCF::getTPL()->assign([
            'success' => true,
            'shortUrl' => $returnValues->getShortedUrl(true),
        ]);
    }
    
    /**
     * @inheritDoc
     */
    public function validate()
    {
        parent::validate();
        
        //validate hash
        $this->validateHash();
        
        //validate url
        UrlUtil::isValidUrl($this->url);
    }

    /**
     * check if the hash is not unique or not valid
     */
    protected function validateHash()
    {
        UrlUtil::isValidHash($this->hash);
    }
}
