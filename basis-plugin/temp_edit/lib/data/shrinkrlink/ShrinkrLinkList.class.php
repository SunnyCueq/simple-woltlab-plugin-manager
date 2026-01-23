<?php

/**
 * Represents a list of ShrinkrLink objects.
 * 
 * Database object list for querying and retrieving multiple shortened link entries.
 * Extends DatabaseObjectList to provide filtering, sorting, and pagination capabilities
 * for the ACP link list page.
 *
 * @author      Sunny C
 * @copyright   2026 Sunny C
 * @license     License for Commercial Plugins
 * @link        https://sunnyc.de
 * @package     de.sunnyc.wsc.shrinkr
 * @subpackage  data.shrinkrlink
 */

namespace shrinkr\data\shrinkrlink;

use wcf\data\DatabaseObjectList;

/**
 * List class for ShrinkrLink database objects.
 */
class ShrinkrLinkList extends DatabaseObjectList
{
    /**
     * Class name of the objects in this list.
     *
     * @var    string
     */
    public $className = ShrinkrLink::class;
}
