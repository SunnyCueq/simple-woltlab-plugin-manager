<?php

namespace shrinkr\data\discount;

/**
 * Represents a list of viewable discounts.
 *
 * @author      Sunny C
 * @copyright   2026 Sunny C
 * @license     License for Commercial Plugins
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
