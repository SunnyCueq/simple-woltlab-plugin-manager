<?php

namespace shrinkr\data\buttonclick;

use wcf\data\DatabaseObjectList;

/**
 * Represents a list of button clicks.
 * 
 * Database object list for querying and retrieving multiple button click entries.
 * Extends DatabaseObjectList to provide filtering, sorting, and pagination capabilities
 * for the ACP button click list page.
 *
 * @author      Sunny C
 * @copyright   2026 Sunny C
 * @license     License for Commercial Plugins
 * @link        https://sunnyc.de
 * @package     de.sunnyc.wsc.shrinkr
 * @subpackage  data.buttonclick
 */
class ButtonClickList extends DatabaseObjectList
{
    /**
     * Class name of the objects in this list.
     *
     * @var    string
     */
    public $className = ButtonClick::class;
}

