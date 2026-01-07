<?php

namespace shrinkr\system\event\listener;

use shrinkr\acp\form\ShrinkrLinkAddForm;
use shrinkr\data\custombutton\CustomButtonList;
use wcf\system\event\listener\AbstractEventListener;
use wcf\system\WCF;

/**
 * Loads custom buttons for URL edit page.
 *
 * @author      Sunny C. <https://benjaro.info>
 * @copyright   2026 Sunny C
 * @license     License for Commercial Plugins <https://benjaro.info>
 *
 * @package    de.sunnyc.wsc.shrinkr
 * @subpackage system.event.listener
 */
class ShrinkrLinkEditCustomButtonsListener extends AbstractEventListener
{
    /**
     * @inheritDoc
     */
    public function onAssignVariables(ShrinkrLinkAddForm $page): void
    {
        $urlCustomButtons = [];
        
        if (isset($page->formObject) && $page->formObject->linkID) {
            $customButtonList = new CustomButtonList();
            $customButtonList->getConditionBuilder()->add('linkID = ?', [$page->formObject->linkID]);
            $customButtonList->sqlOrderBy = 'sortOrder ASC';
            $customButtonList->readObjects();
            
            $urlCustomButtons = $customButtonList->getObjects();
        }
        
        WCF::getTPL()->assign([
            'urlCustomButtons' => $urlCustomButtons,
        ]);
    }
}
