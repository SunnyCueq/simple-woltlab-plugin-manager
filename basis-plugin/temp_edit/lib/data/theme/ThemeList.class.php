<?php

namespace shrinkr\data\theme;

use wcf\data\DatabaseObjectList;

/**
 * Represents a list of themes.
 * 
 * Database object list for querying and retrieving multiple theme entries.
 * Extends DatabaseObjectList to provide filtering, sorting, and pagination capabilities
 * for the ACP theme list page.
 *
 * @author      Sunny C
 * @copyright   2026 Sunny C
 * @license     License for Commercial Plugins
 * @link        https://sunnyc.de
 * @package     de.sunnyc.wsc.shrinkr
 * @subpackage  data.theme
 */
class ThemeList extends DatabaseObjectList
{
    /**
     * Class name of the objects in this list.
     *
     * @var    string
     */
    public $className = Theme::class;
}

