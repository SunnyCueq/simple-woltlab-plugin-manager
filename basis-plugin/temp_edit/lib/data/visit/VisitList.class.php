<?php

namespace urlshort\data\visit;

use wcf\data\DatabaseObjectList;

/**
 * Represents a list of visits.
 *
 * @author      Sunny C. <https://benjaro.info>
 * @copyright   2022-2025 Benjaro
 * @license     License for Commercial Plugins <https://benjaro.info>
 *
 * @package    dev.tkirch.wsc.urlshort
 * @subpackage data.visit
 */
class VisitList extends DatabaseObjectList
{
    /**
     * @inheritDoc
     */
    public $className = Visit::class;
}

