<?php

namespace shrinkr\data\visit;

use wcf\data\DatabaseObjectList;

/**
 * Represents a list of visits.
 *
 * @author      Sunny C. <https://benjaro.info>
 * @copyright   2026 Sunny C
 * @license     License for Commercial Plugins <https://benjaro.info>
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

