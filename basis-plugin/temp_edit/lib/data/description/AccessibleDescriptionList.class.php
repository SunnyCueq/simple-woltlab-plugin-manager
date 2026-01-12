<?php

namespace shrinkr\data\description;

/**
 * Returns only active descriptions for frontend display (random selection).
 *
 * @author      Sunny C
 * @copyright   2026 Sunny C
 * @license     License for Commercial Plugins
 *
 * @package    de.sunnyc.wsc.shrinkr
 * @subpackage data.description
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
     */
    public function __construct()
    {
        parent::__construct();

        // Only active descriptions
        $this->getConditionBuilder()->add('description.isActive = ?', [1]);
    }
}
