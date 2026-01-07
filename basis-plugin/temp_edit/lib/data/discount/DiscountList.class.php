<?php

namespace shrinkr\data\discount;

use wcf\data\DatabaseObjectList;

/**
 * Represents a list of discounts.
 *
 * @author      Sunny C. <https://benjaro.info>
 * @copyright   2026 Sunny C
 * @license     License for Commercial Plugins <https://benjaro.info>
 *
 * @package    de.sunnyc.wsc.shrinkr
 * @subpackage data.discount
 */
class DiscountList extends DatabaseObjectList
{
    /**
     * @inheritDoc
     */
    public $className = Discount::class;
}
