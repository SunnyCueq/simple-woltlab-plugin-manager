<?php

namespace shrinkr\data\discount;

/**
 * Represents a list of viewable discounts.
 *
 * @author      Sunny C. <https://benjaro.info>
 * @copyright   2026 Sunny C
 * @license     License for Commercial Plugins <https://benjaro.info>
 *
 * @package    de.sunnyc.wsc.shrinkr
 * @subpackage data.discount
 */
class ViewableDiscountList extends DiscountList
{
    /**
     * @inheritDoc
     */
    public $decoratorClassName = ViewableDiscount::class;
}
