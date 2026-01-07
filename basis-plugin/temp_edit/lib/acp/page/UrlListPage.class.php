<?php

namespace urlshort\acp\page;

use urlshort\data\special\SpecialList;
use urlshort\data\url\UrlList;
use urlshort\util\UrlFeaturedLinksUtil;
use wcf\page\SortablePage;
use wcf\system\reaction\ReactionHandler;
use wcf\system\WCF;
use wcf\util\StringUtil;

/**
 * @author      Julian Pfeil, Titus Kirch <https://julian-pfeil.de>
 * @link        https://darkwood.design/store/user-file-list/1298-julian-pfeil/
 * @copyright   2022 Julian Pfeil Websites & Co.
 * @license     License for Commercial Plugins <https://julian-pfeil.de/lizenz/>
 *
 * @package    dev.tkirch.wsc.urlshort
 * @subpackage acp.page
 */
class UrlListPage extends SortablePage
{
    /**
     * @inheritDoc
     */
    public $activeMenuItem = 'urlshort.acp.menu.link.url.list';
    
    /**
     * @inheritDoc
     */
    public $neededPermissions = ['admin.urlshort.canManageUrls'];
    
    /**
     * @inheritDoc
     */
    public $objectListClassName = UrlList::class;
    
    /**
     * @inheritDoc
     */
    public $validSortFields = ['urlID', 'hash', 'url', 'counter', 'urlTitle', 'featuredLinks', 'special'];

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
            
            // Override sortField to prevent SQL error (use urlID as fallback)
            $this->sortField = 'urlID';
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
            $this->objectList->getConditionBuilder()->add('urlTitle LIKE ? OR featuredLinks LIKE ?', [
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
            // Replace with urlID as fallback
            $this->sqlOrderBy = preg_replace('/\b(featuredLinks|special)\b/', 'urlID', $this->sqlOrderBy);
        }
        
        // Also check objectList's sqlOrderBy
        if (isset($this->objectList->sqlOrderBy) && (strpos($this->objectList->sqlOrderBy, 'featuredLinks') !== false || strpos($this->objectList->sqlOrderBy, 'special') !== false)) {
            $this->objectList->sqlOrderBy = preg_replace('/\b(featuredLinks|special)\b/', 'urlID', $this->objectList->sqlOrderBy);
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
        $urlIDs = [];
        if (isset($this->objectList) && $this->objectList !== null) {
            foreach ($this->objectList as $object) {
                if ($object === null) {
                    continue;
                }
                
                // Handle both Url objects and decorated objects
                $urlID = null;
                if (is_a($object, 'urlshort\data\url\Url')) {
                    $urlID = $object->urlID ?? null;
                } elseif (method_exists($object, 'getDecoratedObject')) {
                    try {
                        $decorated = $object->getDecoratedObject();
                        if ($decorated !== null && is_a($decorated, 'urlshort\data\url\Url')) {
                            $urlID = $decorated->urlID ?? null;
                        }
                    } catch (\Exception $e) {
                        // Skip this object if getDecoratedObject() fails
                        continue;
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
            if (count($urlIDs) === 1) {
                $sql = "SELECT urlID, COUNT(*) as count 
                        FROM urlshort1_featured_link 
                        WHERE urlID = ? 
                        GROUP BY urlID";
                $statement = WCF::getDB()->prepareStatement($sql);
                $statement->execute([$urlIDs[0]]);
            } else {
                $placeholders = str_repeat('?,', count($urlIDs) - 1) . '?';
                $sql = "SELECT urlID, COUNT(*) as count 
                        FROM urlshort1_featured_link 
                        WHERE urlID IN ({$placeholders}) 
                        GROUP BY urlID";
                $statement = WCF::getDB()->prepareStatement($sql);
                $statement->execute($urlIDs);
            }
            
            while ($row = $statement->fetchArray()) {
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
                if (!isset($linksArray[$row['urlID']]['hasActiveSpecial'])) {
                    $linksArray[$row['urlID']]['hasActiveSpecial'] = false;
                }
            }
            
            // Step 2b: Load custom buttons count for all URLs
            if (count($urlIDs) === 1) {
                $sql = "SELECT urlID, COUNT(*) as count 
                        FROM urlshort1_custom_button 
                        WHERE urlID = ? 
                        GROUP BY urlID";
                $statement = WCF::getDB()->prepareStatement($sql);
                $statement->execute([$urlIDs[0]]);
            } else {
                $placeholders = str_repeat('?,', count($urlIDs) - 1) . '?';
                $sql = "SELECT urlID, COUNT(*) as count 
                        FROM urlshort1_custom_button 
                        WHERE urlID IN ({$placeholders}) 
                        GROUP BY urlID";
                $statement = WCF::getDB()->prepareStatement($sql);
                $statement->execute($urlIDs);
            }
            
            while ($row = $statement->fetchArray()) {
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
            
            $specialsArray = [];
            foreach ($specialList->getObjects() as $special) {
                if ($special === null || !isset($special->urlID)) {
                    continue;
                }
                if (!isset($specialsArray[$special->urlID])) {
                    $specialsArray[$special->urlID] = [];
                }
                $specialsArray[$special->urlID][] = $special;
            }
            
            // Step 4: Check for active specials and update linksArray
            foreach ($urlIDs as $urlID) {
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

            // Step 5: Add plainLinks for featured links
            foreach ($this->objectList as $object) {
                if (!is_a($object, 'urlshort\data\url\Url')) {
                    $object = $object->getDecoratedObject();
                }

                if (!isset($linksArray[$object->urlID])) {
                    $linksArray[$object->urlID] = [
                        'countFeaturedLinks' => 0,
                        'countCustomButtons' => 0,
                        'hasActiveSpecial' => false,
                        'firstActiveSpecialID' => null,
                        'firstSpecialID' => null,
                    ];
                }

                // Only set countFeaturedLinks if not already set
                if (!isset($linksArray[$object->urlID]['countFeaturedLinks'])) {
                    $linksArray[$object->urlID]['countFeaturedLinks'] = UrlFeaturedLinksUtil::countFeaturedLinks($object);
                }

                if (!empty($object->featuredLinks)) {
                    $parsedLink = [];

                    $featuredLinks = \explode("\n", StringUtil::unifyNewlines($object->featuredLinks));
                    foreach ($featuredLinks as $link) {
                        $explodedItem = UrlFeaturedLinksUtil::extractPositionExplodeLink($link);
                        if (is_array($explodedItem)) {
                            $parsedLink[$explodedItem[0]] = $explodedItem[1];
                        }
                    }

                    $linksArray[$object->urlID]['plainLinks'] = $parsedLink;
                }
            }

            // Step 6: Load button click counts for all URLs
            $buttonClicksArray = [];
            foreach ($urlIDs as $urlID) {
                $buttonClicksArray[$urlID] = [
                    'total' => 0,
                    'forward' => 0,
                    'featured_link' => 0,
                    'custom' => 0,
                ];
            }
            
            if (count($urlIDs) === 1) {
                $sql = "SELECT urlID, buttonType, COUNT(*) as count 
                        FROM urlshort1_button_click 
                        WHERE urlID = ? 
                        GROUP BY urlID, buttonType";
                $statement = WCF::getDB()->prepareStatement($sql);
                $statement->execute([$urlIDs[0]]);
            } else {
                $placeholders = str_repeat('?,', count($urlIDs) - 1) . '?';
                $sql = "SELECT urlID, buttonType, COUNT(*) as count 
                        FROM urlshort1_button_click 
                        WHERE urlID IN ({$placeholders}) 
                        GROUP BY urlID, buttonType";
                $statement = WCF::getDB()->prepareStatement($sql);
                $statement->execute($urlIDs);
            }
            
            while ($row = $statement->fetchArray()) {
                $urlID = $row['urlID'];
                $buttonType = $row['buttonType'];
                $count = (int) $row['count'];
                
                if (!isset($buttonClicksArray[$urlID])) {
                    $buttonClicksArray[$urlID] = [
                        'total' => 0,
                        'forward' => 0,
                        'featured_link' => 0,
                        'custom' => 0,
                    ];
                }
                
                if (isset($buttonClicksArray[$urlID][$buttonType])) {
                    $buttonClicksArray[$urlID][$buttonType] = $count;
                }
                
                $buttonClicksArray[$urlID]['total'] += $count;
            }
        } else {
            $buttonClicksArray = [];
        }

        // Step 7: Load reaction data for all URLs
        $reactionData = [];
        if (MODULE_LIKE && isset($this->objectList) && !empty($this->objectList)) {
            $reactionUrlIDs = [];
            foreach ($this->objectList as $object) {
                if (!is_a($object, 'urlshort\data\url\Url')) {
                    $object = $object->getDecoratedObject();
                }
                if (isset($object->urlID) && $object->urlID) {
                    $reactionUrlIDs[] = $object->urlID;
                }
            }
            
            if (!empty($reactionUrlIDs)) {
                $objectType = ReactionHandler::getInstance()->getObjectType('dev.tkirch.wsc.urlshort.likeableUrl');
                if ($objectType !== null) {
                    ReactionHandler::getInstance()->loadLikeObjects($objectType, $reactionUrlIDs);
                    foreach ($reactionUrlIDs as $urlID) {
                        $likeObject = ReactionHandler::getInstance()->getLikeObject($objectType, $urlID);
                        
                        // Lade Reaktionen inkl. Gast-Reaktionen
                        $guestReactionAction = new \urlshort\data\reaction\GuestReactionAction([], 'react');
                        $reactionDataWithGuests = $guestReactionAction->getReactionDataWithGuests('dev.tkirch.wsc.urlshort.likeableUrl', $urlID);
                        
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
                        
                        $reactionData[$urlID] = $wrapper;
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
                $aObj = is_a($a, 'urlshort\data\url\Url') ? $a : $a->getDecoratedObject();
                $bObj = is_a($b, 'urlshort\data\url\Url') ? $b : $b->getDecoratedObject();
                
                $aID = $aObj->urlID;
                $bID = $bObj->urlID;
                
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
            'reactionObjectType' => 'dev.tkirch.wsc.urlshort.likeableUrl',
            'reactionTypesJS' => $reactionTypesJS,
            'enableQrCode' => URLSHORT_ENABLE_QR_CODE,
        ]);
        
        // Override objects in template if sorted
        if ($sortedObjects !== null) {
            WCF::getTPL()->assign([
                'objects' => $sortedObjects,
            ]);
        }
    }
}
