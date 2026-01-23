<?php

namespace shrinkr\data\guestreaction;

use wcf\data\DatabaseObjectList;

/**
 * Represents a list of guest reactions.
 * 
 * Database object list for querying and retrieving multiple guest reaction entries.
 * Extends DatabaseObjectList to provide filtering, sorting, and pagination capabilities.
 *
 * @author      Sunny C
 * @copyright   2026 Sunny C
 * @license     License for Commercial Plugins
 * @link        https://sunnyc.de
 * @package     de.sunnyc.wsc.shrinkr
 * @subpackage  data.guestreaction
 */
class GuestReactionList extends DatabaseObjectList
{
    /**
     * Class name of the objects in this list.
     *
     * @var    string
     */
    public $className = GuestReaction::class;
}
