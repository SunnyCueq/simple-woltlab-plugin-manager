<?php

namespace urlshort\data\discount;

use wcf\data\DatabaseObjectEditor;

/**
 * Provides functions to edit discounts.
 *
 * @author      Sunny C. <https://benjaro.info>
 * @copyright   2022-2025 Benjaro
 * @license     License for Commercial Plugins <https://benjaro.info>
 *
 * @package    dev.tkirch.wsc.urlshort
 * @subpackage data.discount
 */
class DiscountEditor extends DatabaseObjectEditor
{
    /**
     * @inheritdoc
     */
    protected static $baseClass = Discount::class;
}
