<?php

namespace shrinkr\acp\form;

use shrinkr\data\featuredlink\FeaturedLinkList;
use shrinkr\data\special\SpecialList;
use shrinkr\data\shrinkrlink\ShrinkrLink;
use shrinkr\data\shrinkrlink\ShrinkrLinkAction;
use shrinkr\util\ShrinkrUtil;
use wcf\form\AbstractForm;
use wcf\system\exception\IllegalLinkException;
use wcf\system\WCF;

/**
 * @author      Sunny C, Sunny C <https://sunnyc.de>
 * @link        https://sunnyc.de
 * @copyright   2022 Sunny C Websites & Co.
 * @license     License for Commercial Plugins <https://sunnyc.de/lizenz/>
 *
 * @package    de.sunnyc.wsc.shrinkr
 * @subpackage acp.form
 */
class ShrinkrLinkEditForm extends ShrinkrLinkAddForm
{
    /**
     * @inheritDoc
     */
    public $activeMenuItem = 'shrinkr.acp.menu.link.menu';
    
    /**
     * edited url object
     * @var	Url
     */
    public $urlObj;
    
    /**
     * id of the edited url
     * @var	integer
     */
    public $linkID = 0;
    
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
        
        if ($this->linkID > 0) {
            // Load specials for this URL
            $specialList = new SpecialList();
            $specialList->getConditionBuilder()->add('linkID = ?', [$this->linkID]);
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
            $featuredLinkList->getConditionBuilder()->add('linkID = ?', [$this->linkID]);
            $featuredLinkList->sqlOrderBy = 'sortOrder ASC, linkID ASC';
            $featuredLinkList->readObjects();
            
            $urlFeaturedLinks = $featuredLinkList->getObjects();
        }
        
        WCF::getTPL()->assign([
            'action' => 'edit',
            'url' => $this->url,
            'linkID' => $this->linkID,
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
            $this->linkID = \intval($_REQUEST['id']);
        }
        $this->urlObj = new ShrinkrLink($this->linkID);
        if (!$this->urlObj->linkID) {
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
        
        $this->objectAction = new ShrinkrLinkAction([$this->linkID], 'update', [
            'data' => \array_merge($this->additionalFields, [
                'hash' => $this->hash,
                'url' => $this->url,
            ]),
        ]);
        $this->objectAction->executeAction();
        $this->saved();

        //update object
        $this->urlObj = new ShrinkrLink($this->linkID);
        
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
        ShrinkrUtil::isValidHash($this->hash, $this->urlObj);
    }
}
