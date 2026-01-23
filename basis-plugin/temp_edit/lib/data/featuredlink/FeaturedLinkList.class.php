<?php

namespace shrinkr\data\featuredlink;

use wcf\data\DatabaseObjectList;

/**
 * Represents a list of featured links.
 * 
 * Database object list for querying and retrieving multiple featured link entries.
 * Extends DatabaseObjectList to provide filtering, sorting, and pagination capabilities
 * for the ACP featured link list page.
 *
 * @author      Sunny C
 * @copyright   2026 Sunny C
 * @license     License for Commercial Plugins
 * @link        https://sunnyc.de
 * @package     de.sunnyc.wsc.shrinkr
 * @subpackage  data.featuredlink
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
     * Class name of the objects in this list.
     *
     * @var    string
     */
    public $className = FeaturedLink::class;
}
