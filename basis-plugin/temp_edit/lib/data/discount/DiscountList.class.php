<?php

namespace urlshort\data\discount;

use wcf\data\DatabaseObjectList;

/**
 * Represents a list of discounts.
 *
 * @author      Sunny C. <https://benjaro.info>
 * @copyright   2022-2025 Benjaro
 * @license     License for Commercial Plugins <https://benjaro.info>
 *
 * @package    dev.tkirch.wsc.urlshort
 * @subpackage data.discount
 */
class DiscountList extends DatabaseObjectList
{
    /**
     * @inheritDoc
     */
    public $className = Discount::class;
}
