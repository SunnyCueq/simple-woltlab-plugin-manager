<?php

namespace shrinkr\acp\page;

use shrinkr\data\special\SpecialList;
use shrinkr\data\shrinkrlink\ShrinkrLinkList;
use shrinkr\util\ShrinkrFeaturedLinksUtil;
use shrinkr\util\ShrinkrUtil;
use wcf\data\option\Option;
use wcf\page\SortablePage;
use wcf\system\WCF;
use wcf\util\StringUtil;

/**
 * ACP page for listing and managing shortened links.
 * 
 * Provides a sortable list of all shortened links with search functionality.
 * Supports filtering by URL, title, and custom sorting for featured links and
 * special events. Integrates with reaction system to display reaction counts.
 *
 * @author      Sunny C
 * @copyright   2026 Sunny C
 * @license     License for Commercial Plugins
 * @link        https://sunnyc.de
 * @package     de.sunnyc.wsc.shrinkr
 * @subpackage  acp.page
 */
class ShrinkrLinkListPage extends SortablePage
{
    /**
     * Active menu item identifier for navigation highlighting.
     *
     * @var    string
     */
    public $activeMenuItem = 'shrinkr.acp.menu.link.link.list';
    
    /**
     * Required permissions to access this page.
     *
     * @var    string[]
     */
    public $neededPermissions = ['admin.shrinkr.canManageLinks'];
    
    /**
     * Class name of the database object list.
     *
     * @var    string
     */
    public $objectListClassName = ShrinkrLinkList::class;
    
    /**
     * Valid sort fields for the list.
     * 
     * Note: 'featuredLinks' and 'special' require PHP-based sorting as they
     * are not direct database fields.
     *
     * @var    string[]
     */
    public $validSortFields = ['linkID', 'hash', 'url', 'counter', 'linkTitle', 'featuredLinks', 'special'];

    /**
     * Search query string for URL filtering.
     *
     * @var    string
     */
    public $q;

    /**
     * Search query for title filtering.
     *
     * @var    string
     */
    public $qTitle;

    /**
     * Custom sort field for PHP-based sorting (featuredLinks, special).
     * 
     * Used when sorting by fields that are not direct database columns.
     *
     * @var    string
     */
    public $sortFieldCustom;

    /**
     * Custom sort order for PHP-based sorting (ASC, DESC).
     *
     * @var    string
     */
    public $sortOrderCustom;

    /**
     * Filter for password protected links.
     *
     * @var    bool
     */
    public $passwordProtected = false;

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

        // Read password protected filter
        if (isset($_REQUEST['passwordProtected']) && $_REQUEST['passwordProtected'] == '1') {
            $this->passwordProtected = true;
            // Set active menu item to password filter menu item
            $this->activeMenuItem = 'shrinkr.acp.menu.link.link.password';
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

        // Add password protected filter
        if ($this->passwordProtected) {
            $this->objectList->getConditionBuilder()->add('passwordHash IS NOT NULL');
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
        }

        // Step 7: Sort objects if needed (featuredLinks or special)
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
        
        // Get menu badge text
        $menuBadgeText = ShrinkrUtil::getMenuBadgeText();

        // Assign template variables
        WCF::getTPL()->assign([
            'q' => $this->q,
            'qTitle' => $this->qTitle,
            'passwordProtected' => $this->passwordProtected,
            'linksArray' => $linksArray,
            'acpPageSubMenuCategoryList' => 'shrinkr.acp.menu.link.link.list',
            'menuBadgeText' => $menuBadgeText,
        ]);
        
        // Override objects in template if sorted
        if ($sortedObjects !== null) {
            WCF::getTPL()->assign([
                'objects' => $sortedObjects,
            ]);
        }
    }
}
