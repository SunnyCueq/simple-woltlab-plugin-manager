<?php

namespace urlshort\data\discount;

/**
 * Represents a list of viewable discounts.
 *
 * @author      Sunny C. <https://benjaro.info>
 * @copyright   2022-2025 Benjaro
 * @license     License for Commercial Plugins <https://benjaro.info>
 *
 * @package    dev.tkirch.wsc.urlshort
 * @subpackage data.discount
 */
class ViewableDiscountList extends DiscountList
{
    /**
     * @inheritDoc
     */
    public $decoratorClassName = ViewableDiscount::class;
}
