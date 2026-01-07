<?php

namespace urlshort\data\featuredlink;

use wcf\data\DatabaseObjectList;

/**
 * Represents a list of featured links.
 *
 * @author      Sunny C. <https://benjaro.info>
 * @copyright   2022-2025 Benjaro
 * @license     License for Commercial Plugins <https://benjaro.info>
 *
 * @package    dev.tkirch.wsc.urlshort
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
