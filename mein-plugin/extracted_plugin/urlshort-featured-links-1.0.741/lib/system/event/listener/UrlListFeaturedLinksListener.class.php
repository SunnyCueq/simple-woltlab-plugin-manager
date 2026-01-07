<?php

namespace urlshort\system\event\listener;

use urlshort\acp\page\UrlListPage;
use urlshort\data\featuredlink\FeaturedLinkList;
use urlshort\data\special\SpecialList;
use wcf\system\event\listener\AbstractEventListener;
use wcf\system\WCF;

/**
 * Loads featured links and specials for URL list page.
 *
 * @author      Sunny C. <https://benjaro.info>
 * @copyright   2022-2025 Benjaro
 * @license     License for Commercial Plugins <https://benjaro.info>
 *
 * @package    info.benjaro.urlshort.affiliate
 * @subpackage system.event.listener
 */
class UrlListFeaturedLinksListener extends AbstractEventListener
{
    /**
     * @inheritDoc
     */
    public function onAssignVariables(UrlListPage $page): void
    {
        $linksArray = [];
        $specialsArray = [];
        
        // Get all URL IDs from the list
        $urlIDs = [];
        if (isset($page->objectList) && $page->objectList !== null) {
            foreach ($page->objectList as $object) {
                // Handle both Url objects and decorated objects
                $urlID = null;
                if (is_a($object, 'urlshort\data\url\Url')) {
                    $urlID = $object->urlID;
                } elseif (method_exists($object, 'getDecoratedObject')) {
                    $decorated = $object->getDecoratedObject();
                    if (is_a($decorated, 'urlshort\data\url\Url')) {
                        $urlID = $decorated->urlID;
                    }
                } elseif (isset($object->urlID)) {
                    $urlID = $object->urlID;
                }
                
                if ($urlID) {
                    $urlIDs[] = $urlID;
                }
            }
        }
        
        if (!empty($urlIDs)) {
            // Step 1: Initialize all URLs with default values
            foreach ($urlIDs as $urlID) {
                $linksArray[$urlID] = [
                    'countFeaturedLinks' => 0,
                    'countCustomButtons' => 0,
                    'hasActiveSpecial' => false,
                    'firstActiveSpecialID' => null,
                    'firstSpecialID' => null,
                ];
            }
            
            // Step 2: Load featured links count for all URLs
            $placeholders = str_repeat('?,', count($urlIDs) - 1) . '?';
            $sql = "SELECT urlID, COUNT(*) as count 
                    FROM urlshort1_featured_link 
                    WHERE urlID IN ({$placeholders}) 
                    GROUP BY urlID";
            $statement = WCF::getDB()->prepareStatement($sql);
            $statement->execute($urlIDs);
            
            while ($row = $statement->fetchArray()) {
                // Ensure all keys exist when updating
                if (!isset($linksArray[$row['urlID']])) {
                    $linksArray[$row['urlID']] = [
                        'countFeaturedLinks' => 0,
                        'countCustomButtons' => 0,
                        'hasActiveSpecial' => false,
                        'firstActiveSpecialID' => null,
                        'firstSpecialID' => null,
                    ];
                }
                $linksArray[$row['urlID']]['countFeaturedLinks'] = (int) $row['count'];
                // Ensure hasActiveSpecial is always set
                if (!isset($linksArray[$row['urlID']]['hasActiveSpecial'])) {
                    $linksArray[$row['urlID']]['hasActiveSpecial'] = false;
                }
            }
            
            // Step 2b: Load custom buttons count for all URLs
            $sql = "SELECT urlID, COUNT(*) as count 
                    FROM urlshort1_custom_button 
                    WHERE urlID IN ({$placeholders}) 
                    GROUP BY urlID";
            $statement = WCF::getDB()->prepareStatement($sql);
            $statement->execute($urlIDs);
            
            while ($row = $statement->fetchArray()) {
                // Ensure all keys exist when updating
                if (!isset($linksArray[$row['urlID']])) {
                    $linksArray[$row['urlID']] = [
                        'countFeaturedLinks' => 0,
                        'countCustomButtons' => 0,
                        'hasActiveSpecial' => false,
                        'firstActiveSpecialID' => null,
                        'firstSpecialID' => null,
                    ];
                }
                $linksArray[$row['urlID']]['countCustomButtons'] = (int) $row['count'];
            }
            
            // Step 3: Load active specials for all URLs
            $specialList = new SpecialList();
            $specialList->getConditionBuilder()->add('urlID IN (?)', [$urlIDs]);
            $specialList->readObjects();
            
            foreach ($specialList->getObjects() as $special) {
                if (!isset($specialsArray[$special->urlID])) {
                    $specialsArray[$special->urlID] = [];
                }
                $specialsArray[$special->urlID][] = $special;
            }
            
            // Step 4: Check for active specials and update linksArray
            foreach ($urlIDs as $urlID) {
                // Ensure hasActiveSpecial is always set, even if no specials found
                if (!isset($linksArray[$urlID]['hasActiveSpecial'])) {
                    $linksArray[$urlID]['hasActiveSpecial'] = false;
                }
                
                if (isset($specialsArray[$urlID])) {
                    foreach ($specialsArray[$urlID] as $special) {
                        // Set firstSpecialID (for any special, active or inactive)
                        if ($linksArray[$urlID]['firstSpecialID'] === null) {
                            $linksArray[$urlID]['firstSpecialID'] = $special->specialID;
                        }
                        
                        // Set firstActiveSpecialID (only for active specials)
                        if ($special->isCurrentlyActive()) {
                            $linksArray[$urlID]['hasActiveSpecial'] = true;
                            if ($linksArray[$urlID]['firstActiveSpecialID'] === null) {
                                $linksArray[$urlID]['firstActiveSpecialID'] = $special->specialID;
                            }
                        }
                    }
                }
            }
        }
        
        WCF::getTPL()->assign([
            'linksArray' => $linksArray,
        ]);
    }
}

