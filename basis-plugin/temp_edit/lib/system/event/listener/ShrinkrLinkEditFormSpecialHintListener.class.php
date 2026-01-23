<?php

namespace shrinkr\system\event\listener;

use shrinkr\data\featuredlink\FeaturedLinkList;
use shrinkr\data\special\SpecialList;
use wcf\system\event\listener\IParameterizedEventListener;
use wcf\system\WCF;

/**
 * Shows hint about existing specials in URL edit form.
 * 
 * Event listener that loads specials and featured links for a shortened link
 * when editing. Assigns active specials and featured links to the template
 * for display in the edit form.
 *
 * @author      Sunny C
 * @copyright   2026 Sunny C
 * @license     License for Commercial Plugins
 * @link        https://sunnyc.de
 * @package     de.sunnyc.wsc.shrinkr
 * @subpackage  system.event.listener
 */
class ShrinkrLinkEditFormSpecialHintListener implements IParameterizedEventListener
{
    /**
     * Executes the event listener.
     * 
     * Loads specials and featured links for the link being edited and assigns
     * them to the template. Identifies active specials for display hints.
     *
     * @param   object  $eventObj    The event object (form instance)
     * @param   string  $className   The class name of the event object
     * @param   string  $eventName   The event name ('assignVariables')
     * @param   array   $parameters  Event parameters (passed by reference)
     * @return  void
     */
    public function execute($eventObj, $className, $eventName, array &$parameters)
    {
        if ($eventName === 'assignVariables') {
            $linkID = $eventObj->linkID ?? 0;
            
            if ($linkID > 0) {
                $specialList = new SpecialList();
                $specialList->getConditionBuilder()->add('linkID = ?', [$linkID]);
                $specialList->readObjects();
                
                $specials = $specialList->getObjects();
                $activeSpecials = [];
                $firstActiveSpecialID = null;
                
                foreach ($specials as $special) {
                    if ($special->isCurrentlyActive()) {
                        $activeSpecials[] = $special;
                        if ($firstActiveSpecialID === null) {
                            $firstActiveSpecialID = $special->specialID;
                        }
                    }
                }
                
                $featuredLinkList = new FeaturedLinkList();
                $featuredLinkList->getConditionBuilder()->add('linkID = ?', [$linkID]);
                $featuredLinkList->sqlOrderBy = 'sortOrder ASC, linkID ASC';
                $featuredLinkList->readObjects();
                
                WCF::getTPL()->assign([
                    'urlSpecials' => $specials,
                    'urlActiveSpecials' => $activeSpecials,
                    'hasActiveSpecials' => !empty($activeSpecials),
                    'firstActiveSpecialID' => $firstActiveSpecialID,
                    'urlFeaturedLinks' => $featuredLinkList->getObjects(),
                ]);
            }
        }
    }
}
