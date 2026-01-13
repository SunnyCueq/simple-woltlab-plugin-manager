<?php

namespace shrinkr\acp\page;

use shrinkr\data\special\SpecialList;
use shrinkr\data\shrinkrlink\ShrinkrLinkList;
use shrinkr\util\ShrinkrFeaturedLinksUtil;
use wcf\page\SortablePage;
use wcf\system\reaction\ReactionHandler;
use wcf\system\WCF;
use wcf\util\StringUtil;

/**
 * @author      Sunny C, Sunny C <https://sunnyc.de>
 * @link        https://sunnyc.de
 * @copyright   2026 Sunny C Websites & Co.
 * @license     License for Commercial Plugins <https://sunnyc.de/lizenz/>
 *
 * @package    de.sunnyc.wsc.shrinkr
 * @subpackage acp.page
 */
class ShrinkrLinkListPage extends SortablePage
{
    /**
     * @inheritDoc
     */
    public $activeMenuItem = 'shrinkr.acp.menu.link.link.list';
    
    /**
     * @inheritDoc
     */
    public $neededPermissions = ['admin.shrinkr.canManageLinks'];
    
    /**
     * @inheritDoc
     */
    public $objectListClassName = ShrinkrLinkList::class;
    
    /**
     * @inheritDoc
     */
    public $validSortFields = ['linkID', 'hash', 'url', 'counter', 'linkTitle', 'featuredLinks', 'special'];

    /**
     * query string
     */
    public $q;

    /**
     * Search query for title filtering
     */
    public $qTitle;

    /**
     * Custom sort field for PHP-based sorting (featuredLinks, special)
     */
    public $sortFieldCustom;

    /**
     * Custom sort order for PHP-based sorting
     */
    public $sortOrderCustom;

    /**
     * @inheritDoc
     */
    public function readParameters()
    {
        parent::readParameters();
        
        //read query parameter
        if (isset($_REQUEST['q'])) {
            $this->q = $_REQUEST['q'];
        }

        // Read title search parameter
        if (isset($_REQUEST['qTitle']) && is_string($_REQUEST['qTitle'])) {
            $this->qTitle = StringUtil::trim($_REQUEST['qTitle']);
        }

        // Handle sorting for featuredLinks and special (these are not database fields)
        if (isset($this->sortField) && in_array($this->sortField, ['featuredLinks', 'special'])) {
            // Store original sort field/order for later PHP sorting
            $this->sortFieldCustom = $this->sortField;
            $this->sortOrderCustom = $this->sortOrder ?? 'ASC';
            
            // Override sortField to prevent SQL error (use linkID as fallback)
            $this->sortField = 'linkID';
            if (empty($this->sortOrder)) {
                $this->sortOrder = 'ASC';
            }
        }
    }

    /**
     * @inheritDoc
     */
    protected function initObjectList()
    {
        parent::initObjectList();

        //add query parameter
        if ($this->q) {
            $this->objectList->getConditionBuilder()->add('hash LIKE ? OR url LIKE ?', [
                '%' . $this->q . '%',
                '%' . $this->q . '%',
            ]);
        }

        // Add title search condition
        if (!empty($this->qTitle)) {
            $this->objectList->getConditionBuilder()->add('linkTitle LIKE ? OR featuredLinks LIKE ?', [
                '%' . $this->qTitle . '%',
                '%' . $this->qTitle . '%'
            ]);
        }
    }

    /**
     * @inheritDoc
     */
    public function readData()
    {
        parent::readData();

        // Safety net: Ensure sqlOrderBy doesn't contain invalid fields
        if (isset($this->sqlOrderBy) && (strpos($this->sqlOrderBy, 'featuredLinks') !== false || strpos($this->sqlOrderBy, 'special') !== false)) {
            // Replace with linkID as fallback
            $this->sqlOrderBy = preg_replace('/\b(featuredLinks|special)\b/', 'linkID', $this->sqlOrderBy);
        }
        
        // Also check objectList's sqlOrderBy
        if (isset($this->objectList->sqlOrderBy) && (strpos($this->objectList->sqlOrderBy, 'featuredLinks') !== false || strpos($this->objectList->sqlOrderBy, 'special') !== false)) {
            $this->objectList->sqlOrderBy = preg_replace('/\b(featuredLinks|special)\b/', 'linkID', $this->objectList->sqlOrderBy);
        }
    }
    
    /**
     * @inheritDoc
     */
    public function assignVariables()
    {
        parent::assignVariables();
        
        // Load featured links, custom buttons and specials for all URLs
        $linksArray = [];
        $linkIDs = [];
        if (isset($this->objectList) && $this->objectList !== null) {
            foreach ($this->objectList as $object) {
                if ($object === null) {
                    continue;
                }
                
                // ShrinkrLink objects have linkID property directly
                $linkID = $object->linkID ?? null;
                
                if ($linkID) {
                    $linkIDs[] = $linkID;
                }
            }
        }
        
        if (!empty($linkIDs)) {
            // Step 1: Initialize all URLs with default values
            foreach ($linkIDs as $linkID) {
                $linksArray[$linkID] = [
                    'countFeaturedLinks' => 0,
                    'countCustomButtons' => 0,
                    'hasActiveSpecial' => false,
                    'firstActiveSpecialID' => null,
                    'firstSpecialID' => null,
                ];
            }
            
            // Step 2: Load featured links count for all URLs
            if (count($linkIDs) === 1) {
                $sql = "SELECT linkID, COUNT(*) as count 
                        FROM shrinkr1_featured_link 
                        WHERE linkID = ? 
                        GROUP BY linkID";
                $statement = WCF::getDB()->prepareStatement($sql);
                $statement->execute([$linkIDs[0]]);
            } else {
                $placeholders = str_repeat('?,', count($linkIDs) - 1) . '?';
                $sql = "SELECT linkID, COUNT(*) as count 
                        FROM shrinkr1_featured_link 
                        WHERE linkID IN ({$placeholders}) 
                        GROUP BY linkID";
                $statement = WCF::getDB()->prepareStatement($sql);
                $statement->execute($linkIDs);
            }
            
            while ($row = $statement->fetchArray()) {
                if (!isset($linksArray[$row['linkID']])) {
                    $linksArray[$row['linkID']] = [
                        'countFeaturedLinks' => 0,
                        'countCustomButtons' => 0,
                        'hasActiveSpecial' => false,
                        'firstActiveSpecialID' => null,
                        'firstSpecialID' => null,
                    ];
                }
                $linksArray[$row['linkID']]['countFeaturedLinks'] = (int) $row['count'];
                if (!isset($linksArray[$row['linkID']]['hasActiveSpecial'])) {
                    $linksArray[$row['linkID']]['hasActiveSpecial'] = false;
                }
            }
            
            // Step 2b: Load custom buttons count for all URLs
            if (count($linkIDs) === 1) {
                $sql = "SELECT linkID, COUNT(*) as count 
                        FROM shrinkr1_custom_button 
                        WHERE linkID = ? 
                        GROUP BY linkID";
                $statement = WCF::getDB()->prepareStatement($sql);
                $statement->execute([$linkIDs[0]]);
            } else {
                $placeholders = str_repeat('?,', count($linkIDs) - 1) . '?';
                $sql = "SELECT linkID, COUNT(*) as count 
                        FROM shrinkr1_custom_button 
                        WHERE linkID IN ({$placeholders}) 
                        GROUP BY linkID";
                $statement = WCF::getDB()->prepareStatement($sql);
                $statement->execute($linkIDs);
            }
            
            while ($row = $statement->fetchArray()) {
                if (!isset($linksArray[$row['linkID']])) {
                    $linksArray[$row['linkID']] = [
                        'countFeaturedLinks' => 0,
                        'countCustomButtons' => 0,
                        'hasActiveSpecial' => false,
                        'firstActiveSpecialID' => null,
                        'firstSpecialID' => null,
                    ];
                }
                $linksArray[$row['linkID']]['countCustomButtons'] = (int) $row['count'];
            }
            
            // Step 3: Load active specials for all URLs
            $specialList = new SpecialList();
            $specialList->getConditionBuilder()->add('linkID IN (?)', [$linkIDs]);
            $specialList->readObjects();
            
            $specialsArray = [];
            foreach ($specialList->getObjects() as $special) {
                if ($special === null || !isset($special->linkID)) {
                    continue;
                }
                if (!isset($specialsArray[$special->linkID])) {
                    $specialsArray[$special->linkID] = [];
                }
                $specialsArray[$special->linkID][] = $special;
            }
            
            // Step 4: Check for active specials and update linksArray
            foreach ($linkIDs as $linkID) {
                if (!isset($linksArray[$linkID]['hasActiveSpecial'])) {
                    $linksArray[$linkID]['hasActiveSpecial'] = false;
                }
                
                if (isset($specialsArray[$linkID])) {
                    foreach ($specialsArray[$linkID] as $special) {
                        // Set firstSpecialID (for any special, active or inactive)
                        if ($linksArray[$linkID]['firstSpecialID'] === null) {
                            $linksArray[$linkID]['firstSpecialID'] = $special->specialID;
                        }
                        
                        // Set firstActiveSpecialID (only for active specials)
                        if ($special->isCurrentlyActive()) {
                            $linksArray[$linkID]['hasActiveSpecial'] = true;
                            if ($linksArray[$linkID]['firstActiveSpecialID'] === null) {
                                $linksArray[$linkID]['firstActiveSpecialID'] = $special->specialID;
                            }
                        }
                    }
                }
            }

            // Step 5: Add plainLinks for featured links
            foreach ($this->objectList as $object) {
                // ShrinkrLink is already a DatabaseObject, not a decorator
                // No need to call getDecoratedObject()
                
                if (!isset($linksArray[$object->linkID])) {
                    $linksArray[$object->linkID] = [
                        'countFeaturedLinks' => 0,
                        'countCustomButtons' => 0,
                        'hasActiveSpecial' => false,
                        'firstActiveSpecialID' => null,
                        'firstSpecialID' => null,
                    ];
                }

                // Only set countFeaturedLinks if not already set
                if (!isset($linksArray[$object->linkID]['countFeaturedLinks'])) {
                    $linksArray[$object->linkID]['countFeaturedLinks'] = ShrinkrFeaturedLinksUtil::countFeaturedLinks($object);
                }

                if (!empty($object->featuredLinks)) {
                    $parsedLink = [];

                    $featuredLinks = \explode("\n", StringUtil::unifyNewlines($object->featuredLinks));
                    foreach ($featuredLinks as $link) {
                        $explodedItem = ShrinkrFeaturedLinksUtil::extractPositionExplodeLink($link);
                        if (is_array($explodedItem)) {
                            $parsedLink[$explodedItem[0]] = $explodedItem[1];
                        }
                    }

                    $linksArray[$object->linkID]['plainLinks'] = $parsedLink;
                }
            }

            // Step 6: Load button click counts for all URLs
            $buttonClicksArray = [];
            foreach ($linkIDs as $linkID) {
                $buttonClicksArray[$linkID] = [
                    'total' => 0,
                    'forward' => 0,
                    'featured_link' => 0,
                    'custom' => 0,
                ];
            }
            
            if (count($linkIDs) === 1) {
                $sql = "SELECT linkID, buttonType, COUNT(*) as count 
                        FROM shrinkr1_button_click 
                        WHERE linkID = ? 
                        GROUP BY linkID, buttonType";
                $statement = WCF::getDB()->prepareStatement($sql);
                $statement->execute([$linkIDs[0]]);
            } else {
                $placeholders = str_repeat('?,', count($linkIDs) - 1) . '?';
                $sql = "SELECT linkID, buttonType, COUNT(*) as count 
                        FROM shrinkr1_button_click 
                        WHERE linkID IN ({$placeholders}) 
                        GROUP BY linkID, buttonType";
                $statement = WCF::getDB()->prepareStatement($sql);
                $statement->execute($linkIDs);
            }
            
            while ($row = $statement->fetchArray()) {
                $linkID = $row['linkID'];
                $buttonType = $row['buttonType'];
                $count = (int) $row['count'];
                
                if (!isset($buttonClicksArray[$linkID])) {
                    $buttonClicksArray[$linkID] = [
                        'total' => 0,
                        'forward' => 0,
                        'featured_link' => 0,
                        'custom' => 0,
                    ];
                }
                
                if (isset($buttonClicksArray[$linkID][$buttonType])) {
                    $buttonClicksArray[$linkID][$buttonType] = $count;
                }
                
                $buttonClicksArray[$linkID]['total'] += $count;
            }
        } else {
            $buttonClicksArray = [];
        }

        // Step 7: Load reaction data for all URLs
        $reactionData = [];
        if (MODULE_LIKE && isset($this->objectList) && !empty($this->objectList)) {
            $reactionUrlIDs = [];
            foreach ($this->objectList as $object) {
                // ShrinkrLink objects have linkID property directly
                if (isset($object->linkID) && $object->linkID) {
                    $reactionUrlIDs[] = $object->linkID;
                }
            }
            
            if (!empty($reactionUrlIDs)) {
                $objectType = ReactionHandler::getInstance()->getObjectType('de.sunnyc.wsc.shrinkr.likeableUrl');
                if ($objectType !== null) {
                    ReactionHandler::getInstance()->loadLikeObjects($objectType, $reactionUrlIDs);
                    foreach ($reactionUrlIDs as $linkID) {
                        $likeObject = ReactionHandler::getInstance()->getLikeObject($objectType, $linkID);
                        
                        // Lade Reaktionen inkl. Gast-Reaktionen
                        $guestReactionAction = new \shrinkr\data\reaction\GuestReactionAction([], 'react');
                        $reactionDataWithGuests = $guestReactionAction->getReactionDataWithGuests('de.sunnyc.wsc.shrinkr.likeableUrl', $linkID);
                        
                        // Erstelle Reaktions-Array mit Objekten
                        $reactions = [];
                        foreach ($reactionDataWithGuests['cachedReactions'] as $reactionTypeID => $count) {
                            $reactionType = \wcf\data\reaction\type\ReactionTypeCache::getInstance()->getReactionTypeByID($reactionTypeID);
                            if ($reactionType !== null) {
                                $reactions[$reactionTypeID] = [
                                    'reactionCount' => $count,
                                    'renderedReactionIcon' => $reactionType->renderIcon(),
                                    'renderedReactionIconEncoded' => \wcf\util\JSON::encode($reactionType->renderIcon()),
                                    'reactionTitle' => $reactionType->getTitle(),
                                ];
                            }
                        }
                        
                        $reactionTypeID = ($likeObject !== null && isset($likeObject->reactionTypeID)) ? $likeObject->reactionTypeID : 0;
                        
                        $wrapper = new class($reactions, $reactionDataWithGuests['cumulativeLikes'], $reactionTypeID) {
                            private $reactions;
                            private $cumulativeLikes;
                            private $reactionTypeID;
                            
                            public function __construct($reactions, $cumulativeLikes, $reactionTypeID) {
                                $this->reactions = $reactions;
                                $this->cumulativeLikes = $cumulativeLikes;
                                $this->reactionTypeID = $reactionTypeID;
                            }
                            
                            public function getReactions() {
                                return $this->reactions;
                            }
                            
                            public function getReactionsJson(): string {
                                $data = [];
                                foreach ($this->reactions as $reactionTypeID => $value) {
                                    $data[] = [
                                        $reactionTypeID, $value['reactionCount'],
                                    ];
                                }
                                return \wcf\util\JSON::encode($data);
                            }
                            
                            public function __get($name) {
                                if ($name === 'reactionTypeID') {
                                    return $this->reactionTypeID;
                                }
                                if ($name === 'cumulativeLikes') {
                                    return $this->cumulativeLikes;
                                }
                                return null;
                            }
                        };
                        
                        $reactionData[$linkID] = $wrapper;
                    }
                }
            }
        }

        // Assign REACTION_TYPES JavaScript variable
        $reactionTypesJS = '';
        if (MODULE_LIKE) {
            $reactionHandler = \wcf\system\reaction\ReactionHandler::getInstance();
            $reactionTypesJS = $reactionHandler->getReactionsJSVariable();
        }

        // Step 8: Sort objects if needed (featuredLinks or special)
        $sortedObjects = null;
        if (isset($this->sortFieldCustom) && in_array($this->sortFieldCustom, ['featuredLinks', 'special'])) {
            $sortField = $this->sortFieldCustom;
            $sortOrder = $this->sortOrderCustom ?? 'ASC';
            
            $objects = $this->objectList->getObjects();
            
            usort($objects, function($a, $b) use ($sortField, $sortOrder, $linksArray) {
                // ShrinkrLink objects are used directly, no decorator
                $aObj = $a;
                $bObj = $b;
                
                $aID = $aObj->linkID;
                $bID = $bObj->linkID;
                
                $aValue = 0;
                $bValue = 0;
                
                if ($sortField === 'featuredLinks') {
                    $aValue = $linksArray[$aID]['countFeaturedLinks'] ?? 0;
                    $bValue = $linksArray[$bID]['countFeaturedLinks'] ?? 0;
                } elseif ($sortField === 'special') {
                    $aValue = (($linksArray[$aID]['hasActiveSpecial'] ?? false) == true || ($linksArray[$aID]['hasActiveSpecial'] ?? false) == 1) ? 1 : 0;
                    $bValue = (($linksArray[$bID]['hasActiveSpecial'] ?? false) == true || ($linksArray[$bID]['hasActiveSpecial'] ?? false) == 1) ? 1 : 0;
                }
                
                if ($aValue == $bValue) {
                    return 0;
                }
                
                $result = ($aValue < $bValue) ? -1 : 1;
                return ($sortOrder === 'DESC') ? -$result : $result;
            });
            
            $sortedObjects = $objects;
        }
        
        //assign template variables
        WCF::getTPL()->assign([
            'q' => $this->q,
            'qTitle' => $this->qTitle,
            'linksArray' => $linksArray,
            'buttonClicksArray' => $buttonClicksArray,
            'reactionData' => $reactionData,
            'reactionObjectType' => 'de.sunnyc.wsc.shrinkr.likeableUrl',
            'reactionTypesJS' => $reactionTypesJS,
            'acpPageSubMenuCategoryList' => 'shrinkr.acp.menu.link.link.list',
        ]);
        
        // Override objects in template if sorted
        if ($sortedObjects !== null) {
            WCF::getTPL()->assign([
                'objects' => $sortedObjects,
            ]);
        }
    }
}
