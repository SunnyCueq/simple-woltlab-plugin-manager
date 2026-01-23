<?php

namespace shrinkr\data\special;

use wcf\data\DatabaseObjectList;

/**
 * Represents a list of specials.
 * 
 * Database object list for querying and retrieving multiple special event entries.
 * Extends DatabaseObjectList to provide filtering, sorting, and pagination capabilities
 * for the ACP special list page.
 *
 * @author      Sunny C
 * @copyright   2026 Sunny C
 * @license     License for Commercial Plugins
 * @link        https://sunnyc.de
 * @package     de.sunnyc.wsc.shrinkr
 * @subpackage  data.special
 */
class SpecialList extends DatabaseObjectList
{
    /**
     * Class name of the objects in this list.
     *
     * @var    string
     */
    public $className = Special::class;
}

