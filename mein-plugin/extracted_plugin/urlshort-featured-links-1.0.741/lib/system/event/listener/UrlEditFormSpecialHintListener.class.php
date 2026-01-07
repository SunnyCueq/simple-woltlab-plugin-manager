<?php

namespace urlshort\system\event\listener;

use urlshort\data\featuredlink\FeaturedLinkList;
use urlshort\data\special\SpecialList;
use wcf\system\event\listener\IParameterizedEventListener;
use wcf\system\WCF;

/**
 * Shows hint about existing specials in URL edit form.
 *
 * @author      Sunny C. <https://benjaro.info>
 * @copyright   2022-2025 Benjaro
 * @license     License for Commercial Plugins <https://benjaro.info>
 *
 * @package    info.benjaro.urlshort.affiliate
 * @subpackage system.event.listener
 */
class UrlEditFormSpecialHintListener implements IParameterizedEventListener
{
    /**
     * @inheritDoc
     */
    public function execute($eventObj, $className, $eventName, array &$parameters)
    {
        if ($eventName === 'assignVariables') {
            // Get urlID from the form
            $urlID = $eventObj->urlID ?? 0;
            
            if ($urlID > 0) {
                // Load specials for this URL
                $specialList = new SpecialList();
                $specialList->getConditionBuilder()->add('urlID = ?', [$urlID]);
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
                $featuredLinkList->getConditionBuilder()->add('urlID = ?', [$urlID]);
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

