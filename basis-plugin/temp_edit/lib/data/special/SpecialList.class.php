<?php

namespace urlshort\data\special;

use wcf\data\DatabaseObjectList;

/**
 * Represents a list of specials.
 *
 * @author      Sunny C. <https://benjaro.info>
 * @copyright   2022-2025 Benjaro
 * @license     License for Commercial Plugins <https://benjaro.info>
 *
 * @package    dev.tkirch.wsc.urlshort
 * @subpackage data.special
 */
class SpecialList extends DatabaseObjectList
{
    /**
     * @inheritDoc
     */
    public $className = Special::class;
}

