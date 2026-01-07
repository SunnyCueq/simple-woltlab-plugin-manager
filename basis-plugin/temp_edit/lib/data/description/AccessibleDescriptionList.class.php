<?php

namespace urlshort\data\description;

/**
 * Returns only active descriptions for frontend display (random selection).
 *
 * @author      Sunny C. <https://benjaro.info>
 * @copyright   2022-2025 Benjaro
 * @license     License for Commercial Plugins <https://benjaro.info>
 *
 * @package    dev.tkirch.wsc.urlshort
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
