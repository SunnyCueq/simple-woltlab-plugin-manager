<?php

namespace urlshort\acp\form;

use urlshort\data\featuredlink\FeaturedLinkList;
use urlshort\data\special\SpecialList;
use urlshort\data\url\Url;
use urlshort\data\url\UrlAction;
use urlshort\util\UrlUtil;
use wcf\form\AbstractForm;
use wcf\system\exception\IllegalLinkException;
use wcf\system\WCF;

/**
 * @author      Julian Pfeil, Titus Kirch <https://julian-pfeil.de>
 * @link        https://darkwood.design/store/user-file-list/1298-julian-pfeil/
 * @copyright   2022 Julian Pfeil Websites & Co.
 * @license     License for Commercial Plugins <https://julian-pfeil.de/lizenz/>
 *
 * @package    dev.tkirch.wsc.urlshort
 * @subpackage acp.form
 */
class UrlEditForm extends UrlAddForm
{
    /**
     * @inheritDoc
     */
    public $activeMenuItem = 'urlshort.acp.menu.link.menu';
    
    /**
     * edited url object
     * @var	Url
     */
    public $urlObj;
    
    /**
     * id of the edited url
     * @var	integer
     */
    public $urlID = 0;
    
    /**
     * @var	bool
     */
    public $resetCounter = false;
    
    /**
     * @inheritDoc
     */
    #[\Override]
    public function assignVariables()
    {
        parent::assignVariables();
        
        // Load specials and featured links for this URL
        $urlSpecials = [];
        $urlActiveSpecials = [];
        $hasActiveSpecials = false;
        $firstActiveSpecialID = null;
        $urlFeaturedLinks = [];
        
        if ($this->urlID > 0) {
            // Load specials for this URL
            $specialList = new SpecialList();
            $specialList->getConditionBuilder()->add('urlID = ?', [$this->urlID]);
            $specialList->readObjects();
            
            $specials = $specialList->getObjects();
            foreach ($specials as $special) {
                if ($special->isCurrentlyActive()) {
                    $urlActiveSpecials[] = $special;
                    if ($firstActiveSpecialID === null) {
                        $firstActiveSpecialID = $special->specialID;
                    }
                }
            }
            
            $urlSpecials = $specials;
            $hasActiveSpecials = !empty($urlActiveSpecials);
            
            // Load featured links for this URL
            $featuredLinkList = new FeaturedLinkList();
            $featuredLinkList->getConditionBuilder()->add('urlID = ?', [$this->urlID]);
            $featuredLinkList->sqlOrderBy = 'sortOrder ASC, linkID ASC';
            $featuredLinkList->readObjects();
            
            $urlFeaturedLinks = $featuredLinkList->getObjects();
        }
        
        WCF::getTPL()->assign([
            'action' => 'edit',
            'url' => $this->url,
            'urlID' => $this->urlID,
            'urlSpecials' => $urlSpecials,
            'urlActiveSpecials' => $urlActiveSpecials,
            'hasActiveSpecials' => $hasActiveSpecials,
            'firstActiveSpecialID' => $firstActiveSpecialID,
            'urlFeaturedLinks' => $urlFeaturedLinks,
        ]);
    }
    
    /**
     * @inheritDoc
     */
    #[\Override]
    public function readData()
    {
        parent::readData();
        
        if (empty($_POST)) {
            $this->hash = $this->urlObj->hash;
            $this->url = $this->urlObj->url;
        }
    }
    
    /**
     * @inheritDoc
     */
    #[\Override]
    public function readFormParameters()
    {
        parent::readFormParameters();
        if (isset($_POST['resetCounter']) && $_POST['resetCounter'] == 'reset') {
            $this->resetCounter = true;
        }
    }
    
    /**
     * @inheritDoc
     */
    #[\Override]
    public function readParameters()
    {
        parent::readParameters();
        
        if (isset($_REQUEST['id'])) {
            $this->urlID = \intval($_REQUEST['id']);
        }
        $this->urlObj = new Url($this->urlID);
        if (!$this->urlObj->urlID) {
            throw new IllegalLinkException();
        }
    }
    
    /**
     * @inheritDoc
     */
    #[\Override]
    public function save()
    {
        AbstractForm::save();
        
        if ($this->resetCounter === true) {
            $this->additionalFields['counter'] = 0;
        }
        
        $this->objectAction = new UrlAction([$this->urlID], 'update', [
            'data' => \array_merge($this->additionalFields, [
                'hash' => $this->hash,
                'url' => $this->url,
            ]),
        ]);
        $this->objectAction->executeAction();
        $this->saved();

        //update object
        $this->urlObj = new Url($this->urlID);
        
        //show success message
        WCF::getTPL()->assign([
            'success' => true,
            'shortUrl' => $this->urlObj->getShortedUrl(true),
        ]);
    }

    /**
     * @inheritDoc
     */
    #[\Override]
    protected function validateHash()
    {
        UrlUtil::isValidHash($this->hash, $this->urlObj);
    }
}
