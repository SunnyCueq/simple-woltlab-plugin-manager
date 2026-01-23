<?php

namespace shrinkr\data\visit;

use wcf\data\DatabaseObjectList;

/**
 * Represents a list of visits.
 * 
 * Database object list for querying and retrieving multiple visit tracking entries.
 * Extends DatabaseObjectList to provide filtering, sorting, and pagination capabilities
 * for analytics and visit tracking features.
 *
 * @author      Sunny C
 * @copyright   2026 Sunny C
 * @license     License for Commercial Plugins
 * @link        https://sunnyc.de
 * @package     de.sunnyc.wsc.shrinkr
 * @subpackage  data.visit
 */
class VisitList extends DatabaseObjectList
{
    /**
     * Class name of the objects in this list.
     *
     * @var    string
     */
    public $className = Visit::class;
}

