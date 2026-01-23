<?php

namespace shrinkr\data\description;

/**
 * Returns only active descriptions for frontend display (random selection).
 * 
 * Database object list for querying and retrieving active description entries
 * for frontend display. Automatically filters to only include descriptions
 * where isActive = 1. Extends DescriptionList.
 *
 * @author      Sunny C
 * @copyright   2026 Sunny C
 * @license     License for Commercial Plugins
 * @link        https://sunnyc.de
 * @package     de.sunnyc.wsc.shrinkr
 * @subpackage  data.description
 *
 * @method Description       current()
 * @method Description[]     getObjects()
 * @method Description|null  getSingleObject()
 * @method Description|null  search($objectID)
 * @property Description[] $objects
 */
class AccessibleDescriptionList extends DescriptionList
{
    /**
     * Creates a new AccessibleDescriptionList object.
     * 
     * Initializes the list and automatically applies a filter to only include
     * active descriptions (isActive = 1).
     *
     * @return  void
     */
    public function __construct()
    {
        parent::__construct();

        $this->getConditionBuilder()->add('description.isActive = ?', [1]);
    }
}
