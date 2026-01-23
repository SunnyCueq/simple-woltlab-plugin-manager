<?php

namespace shrinkr\system\event\listener;

use shrinkr\acp\form\ShrinkrLinkAddForm;
use shrinkr\data\custombutton\CustomButtonList;
use wcf\system\event\listener\AbstractEventListener;
use wcf\system\WCF;

/**
 * Loads custom buttons for URL edit page.
 * 
 * Event listener that loads and assigns custom buttons for a shortened link
 * when editing. Retrieves buttons from the database and sorts them by sortOrder.
 *
 * @author      Sunny C
 * @copyright   2026 Sunny C
 * @license     License for Commercial Plugins
 * @link        https://sunnyc.de
 * @package     de.sunnyc.wsc.shrinkr
 * @subpackage  system.event.listener
 */
class ShrinkrLinkEditCustomButtonsListener extends AbstractEventListener
{
    /**
     * Assigns custom buttons to the template.
     * 
     * Loads custom buttons for the link being edited and assigns them to the
     * template for display in the edit form.
     *
     * @param   ShrinkrLinkAddForm  $page  The form page instance
     * @return  void
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
