<?php

namespace shrinkr\data\featuredlink;

use wcf\data\DatabaseObjectList;

/**
 * Represents a list of featured links.
 *
 * @author      Sunny C. <https://benjaro.info>
 * @copyright   2026 Sunny C
 * @license     License for Commercial Plugins <https://benjaro.info>
 *
 * @package    de.sunnyc.wsc.shrinkr
 * @subpackage data.featuredlink
 *
 * @method FeaturedLink       current()
 * @method FeaturedLink[]     getObjects()
 * @method FeaturedLink|null  getSingleObject()
 * @method FeaturedLink|null  search($objectID)
 * @property FeaturedLink[] $objects
 */
class FeaturedLinkList extends DatabaseObjectList
{
    /**
     * @inheritDoc
     */
    public $className = FeaturedLink::class;
}
