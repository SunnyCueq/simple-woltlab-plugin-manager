<?php

namespace shrinkr\data\description;

use wcf\data\DatabaseObjectList;

/**
 * Represents a list of descriptions.
 * 
 * Database object list for querying and retrieving multiple description entries.
 * Extends DatabaseObjectList to provide filtering, sorting, and pagination capabilities
 * for the ACP description list page.
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
class DescriptionList extends DatabaseObjectList
{
    /**
     * Class name of the objects in this list.
     *
     * @var    string
     */
    public $className = Description::class;
}
