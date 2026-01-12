<?php

namespace shrinkr\system\event\listener;

use shrinkr\data\featuredlink\FeaturedLinkList;
use shrinkr\data\special\SpecialList;
use wcf\system\event\listener\IParameterizedEventListener;
use wcf\system\WCF;

/**
 * Shows hint about existing specials in URL edit form.
 *
 * @author      Sunny C
 * @copyright   2026 Sunny C
 * @license     License for Commercial Plugins
 *
 * @package    de.sunnyc.wsc.shrinkr
 * @subpackage system.event.listener
 */
class ShrinkrLinkEditFormSpecialHintListener implements IParameterizedEventListener
{
    /**
     * @inheritDoc
     */
    public function execute($eventObj, $className, $eventName, array &$parameters)
    {
        if ($eventName === 'assignVariables') {
            // Get linkID from the form
            $linkID = $eventObj->linkID ?? 0;
            
            if ($linkID > 0) {
                // Load specials for this URL
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
                
                // Load featured links for this URL
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
