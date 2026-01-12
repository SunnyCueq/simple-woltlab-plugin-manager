<?php

namespace shrinkr\data\visit;

use wcf\data\DatabaseObjectList;

/**
 * Represents a list of visits.
 *
 * @author      Sunny C
 * @copyright   2026 Sunny C
 * @license     License for Commercial Plugins
 *
 * @package    de.sunnyc.wsc.shrinkr
 * @subpackage data.visit
 */
class VisitList extends DatabaseObjectList
{
    /**
     * @inheritDoc
     */
    public $className = Visit::class;
}

