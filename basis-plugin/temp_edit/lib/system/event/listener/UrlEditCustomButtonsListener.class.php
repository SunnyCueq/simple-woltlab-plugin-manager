<?php

namespace urlshort\system\event\listener;

use urlshort\acp\form\UrlAddForm;
use urlshort\data\custombutton\CustomButtonList;
use wcf\system\event\listener\AbstractEventListener;
use wcf\system\WCF;

/**
 * Loads custom buttons for URL edit page.
 *
 * @author      Sunny C. <https://benjaro.info>
 * @copyright   2022-2025 Benjaro
 * @license     License for Commercial Plugins <https://benjaro.info>
 *
 * @package    dev.tkirch.wsc.urlshort
 * @subpackage system.event.listener
 */
class UrlEditCustomButtonsListener extends AbstractEventListener
{
    /**
     * @inheritDoc
     */
    public function onAssignVariables(UrlAddForm $page): void
    {
        $urlCustomButtons = [];
        
        if (isset($page->formObject) && $page->formObject->urlID) {
            $customButtonList = new CustomButtonList();
            $customButtonList->getConditionBuilder()->add('urlID = ?', [$page->formObject->urlID]);
            $customButtonList->sqlOrderBy = 'sortOrder ASC';
            $customButtonList->readObjects();
            
            $urlCustomButtons = $customButtonList->getObjects();
        }
        
        WCF::getTPL()->assign([
            'urlCustomButtons' => $urlCustomButtons,
        ]);
    }
}
