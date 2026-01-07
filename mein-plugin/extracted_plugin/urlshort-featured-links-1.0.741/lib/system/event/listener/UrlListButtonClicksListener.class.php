<?php

namespace urlshort\system\event\listener;

use urlshort\acp\page\UrlListPage;
use wcf\system\event\listener\AbstractEventListener;
use wcf\system\WCF;

/**
 * Loads button click counts for URL list page.
 *
 * @author      Sunny C. <https://benjaro.info>
 * @copyright   2022-2025 Benjaro
 * @license     License for Commercial Plugins <https://benjaro.info>
 *
 * @package    info.benjaro.urlshort.affiliate
 * @subpackage system.event.listener
 */
class UrlListButtonClicksListener extends AbstractEventListener
{
    /**
     * @inheritDoc
     */
    public function onAssignVariables(UrlListPage $page): void
    {
        $buttonClicksArray = [];
        
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
            // Initialize all URLs with default values
            foreach ($urlIDs as $urlID) {
                $buttonClicksArray[$urlID] = [
                    'total' => 0,
                    'forward' => 0,
                    'featured_link' => 0,
                    'custom' => 0,
                ];
            }
            
            // Load button click counts for all URLs
            $placeholders = str_repeat('?,', count($urlIDs) - 1) . '?';
            $sql = "SELECT urlID, buttonType, COUNT(*) as count 
                    FROM urlshort1_button_click 
                    WHERE urlID IN ({$placeholders}) 
                    GROUP BY urlID, buttonType";
            $statement = WCF::getDB()->prepareStatement($sql);
            $statement->execute($urlIDs);
            
            while ($row = $statement->fetchArray()) {
                $urlID = $row['urlID'];
                $buttonType = $row['buttonType'];
                $count = (int) $row['count'];
                
                // Ensure all keys exist when updating
                if (!isset($buttonClicksArray[$urlID])) {
                    $buttonClicksArray[$urlID] = [
                        'total' => 0,
                        'forward' => 0,
                        'featured_link' => 0,
                        'custom' => 0,
                    ];
                }
                
                // Set count for specific button type
                if (isset($buttonClicksArray[$urlID][$buttonType])) {
                    $buttonClicksArray[$urlID][$buttonType] = $count;
                }
                
                // Add to total
                $buttonClicksArray[$urlID]['total'] += $count;
            }
        }
        
        WCF::getTPL()->assign([
            'buttonClicksArray' => $buttonClicksArray,
        ]);
    }
}

