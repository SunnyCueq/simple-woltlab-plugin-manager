<?php

namespace shrinkr\data\special;

use wcf\data\DatabaseObjectList;

/**
 * Represents a list of specials.
 *
 * @author      Sunny C
 * @copyright   2026 Sunny C
 * @license     License for Commercial Plugins
 *
 * @package    de.sunnyc.wsc.shrinkr
 * @subpackage data.special
 */
class SpecialList extends DatabaseObjectList
{
    /**
     * @inheritDoc
     */
    public $className = Special::class;
}

