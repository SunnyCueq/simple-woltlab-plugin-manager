<?php

namespace shrinkr\data\description;

use wcf\data\DatabaseObjectList;

/**
 * Represents a list of descriptions.
 *
 * @author      Sunny C. <https://benjaro.info>
 * @copyright   2026 Sunny C
 * @license     License for Commercial Plugins <https://benjaro.info>
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
class DescriptionList extends DatabaseObjectList
{
    /**
     * @inheritDoc
     */
    public $className = Description::class;
}
