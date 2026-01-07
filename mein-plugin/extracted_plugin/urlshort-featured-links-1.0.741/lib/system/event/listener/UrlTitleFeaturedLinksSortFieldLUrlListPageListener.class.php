<?php
namespace urlshort\system\event\listener;

use urlshort\util\UrlFeaturedLinksUtil;
use urlshort\acp\page\UrlListPage;
use wcf\system\event\listener\AbstractEventListener;
use wcf\system\WCF;
use wcf\util\StringUtil;

/**
 * Event listener to add urlTitle, featuredLinks and special as valid sort fields.
 *
 * @author      Sunny C. <https://benjaro.info>
 * @copyright   2022-2025 Benjaro
 * @license     License for Commercial Plugins <https://benjaro.info>
 *
 * @package    info.benjaro.urlshort.affiliate
 * @subpackage system.event.listener
 */

final class UrlTitleFeaturedLinksSortFieldLUrlListPageListener extends AbstractEventListener
{
    /**
     * Search query for title filtering
     */
    private ?string $qTitle = null;

    /**
     * Adds urlTitle, featuredLinks and special as valid sort fields.
     */
    protected function onValidateSortField(UrlListPage $page): void
    {
        $page->validSortFields[] = 'urlTitle';
        $page->validSortFields[] = 'featuredLinks';
        $page->validSortFields[] = 'special';
    }

    /**
     * Reads request parameters for title search.
     * Intercepts sorting for featuredLinks and special before SQL is generated.
     */
    protected function onReadParameters(UrlListPage $page): void
    {
        if (isset($_REQUEST['qTitle']) && is_string($_REQUEST['qTitle'])) {
            $this->qTitle = StringUtil::trim($_REQUEST['qTitle']);
        }
        
        // Handle sorting for featuredLinks and special (these are not database fields)
        // Must intercept AFTER readParameters() but BEFORE SQL is generated
        if (isset($page->sortField) && in_array($page->sortField, ['featuredLinks', 'special'])) {
            // Store original sort field/order for later PHP sorting
            $page->sortFieldCustom = $page->sortField;
            $page->sortOrderCustom = $page->sortOrder ?? 'ASC';
            
            // Override sortField to prevent SQL error (use urlID as fallback)
            $page->sortField = 'urlID';
            if (empty($page->sortOrder)) {
                $page->sortOrder = 'ASC';
            }
        }
    }

    /**
     * Intercepts sorting AFTER initObjectList but BEFORE readData() sets sqlOrderBy.
     * This is called via 'afterInitObjectList' event.
     */
    protected function onAfterInitObjectList(UrlListPage $page): void
    {
        // Handle sorting for featuredLinks and special (these are not database fields)
        // Must intercept here, BEFORE readData() sets sqlOrderBy in readData()
        if (isset($page->sortField) && in_array($page->sortField, ['featuredLinks', 'special'])) {
            // Store original sort field/order for later PHP sorting
            $page->sortFieldCustom = $page->sortField;
            $page->sortOrderCustom = $page->sortOrder ?? 'ASC';
            
            // Override sortField to prevent SQL error (use urlID as fallback)
            $page->sortField = 'urlID';
            if (empty($page->sortOrder)) {
                $page->sortOrder = 'ASC';
            }
        }
    }

    /**
     * Adds search condition for title filtering before reading objects.
     * Also ensures sqlOrderBy doesn't contain invalid fields (safety net).
     */
    protected function onBeforeReadObjects(UrlListPage $page): void
    {
        if (!empty($this->qTitle)) {
            $page->objectList->getConditionBuilder()->add('urlTitle LIKE ? OR featuredLinks LIKE ?', [
                '%' . $this->qTitle . '%',
                '%' . $this->qTitle . '%'
            ]);
        }
        
        // Safety net: Ensure sqlOrderBy doesn't contain invalid fields
        // This should not be needed if onAfterInitObjectList worked, but just in case
        if (isset($page->sqlOrderBy) && (strpos($page->sqlOrderBy, 'featuredLinks') !== false || strpos($page->sqlOrderBy, 'special') !== false)) {
            // Replace with urlID as fallback
            $page->sqlOrderBy = preg_replace('/\b(featuredLinks|special)\b/', 'urlID', $page->sqlOrderBy);
        }
        
        // Also check objectList's sqlOrderBy
        if (isset($page->objectList->sqlOrderBy) && (strpos($page->objectList->sqlOrderBy, 'featuredLinks') !== false || strpos($page->objectList->sqlOrderBy, 'special') !== false)) {
            $page->objectList->sqlOrderBy = preg_replace('/\b(featuredLinks|special)\b/', 'urlID', $page->objectList->sqlOrderBy);
        }
    }

    /**
     * Assigns template variables for featured links display.
     */
    protected function onAssignVariables(UrlListPage $page): void
    {
        // Get existing linksArray from template (set by UrlListFeaturedLinksListener)
        $linksArray = WCF::getTPL()->get('linksArray', []);
        if (!is_array($linksArray)) {
            $linksArray = [];
        }

        foreach ($page->objectList as $object) {
            if (!is_a($object, 'urlshort\data\url\Url')) {
                $object = $object->getDecoratedObject();
            }

            // Initialize if not exists (preserve existing data from UrlListFeaturedLinksListener)
            if (!isset($linksArray[$object->urlID])) {
                $linksArray[$object->urlID] = [];
            }

            // Only set countFeaturedLinks if not already set (UrlListFeaturedLinksListener has priority)
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
        
        // Sort objects if needed (featuredLinks or special)
        $sortedObjects = null;
        if (isset($page->sortFieldCustom) && in_array($page->sortFieldCustom, ['featuredLinks', 'special'])) {
            $sortField = $page->sortFieldCustom;
            $sortOrder = $page->sortOrderCustom ?? 'ASC';
            
            // Get all objects as array from objectList
            $objects = $page->objectList->getObjects();
            
            // Sort the array
            usort($objects, function($a, $b) use ($sortField, $sortOrder, $linksArray) {
                // Get decorated objects if needed
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
            
            // Store sorted objects for template
            $sortedObjects = $objects;
        }

        WCF::getTPL()->assign([
            'qTitle' => $this->qTitle,
            'linksArray' => $linksArray,
        ]);
        
        // Override objects in template if sorted
        if ($sortedObjects !== null) {
            WCF::getTPL()->assign([
                'objects' => $sortedObjects,
            ]);
        }
    }
}
