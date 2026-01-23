<?php

namespace shrinkr\data\discount;

/**
 * Represents a list of viewable discounts.
 * 
 * Database object list for querying and retrieving multiple discount entries
 * decorated as ViewableDiscount objects. Extends DiscountList and automatically
 * decorates all returned objects with ViewableDiscount.
 *
 * @author      Sunny C
 * @copyright   2026 Sunny C
 * @license     License for Commercial Plugins
 * @link        https://sunnyc.de
 * @package     de.sunnyc.wsc.shrinkr
 * @subpackage  data.discount
 */
class ViewableDiscountList extends DiscountList
{
    /**
     * Decorator class name for objects in this list.
     *
     * @var    string
     */
    public $decoratorClassName = ViewableDiscount::class;
}
