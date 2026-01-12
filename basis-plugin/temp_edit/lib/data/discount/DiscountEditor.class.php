<?php

namespace shrinkr\data\discount;

use wcf\data\DatabaseObjectEditor;

/**
 * Provides functions to edit discounts.
 *
 * @author      Sunny C
 * @copyright   2026 Sunny C
 * @license     License for Commercial Plugins
 *
 * @package    de.sunnyc.wsc.shrinkr
 * @subpackage data.discount
 */
class DiscountEditor extends DatabaseObjectEditor
{
    /**
     * @inheritdoc
     */
    protected static $baseClass = Discount::class;
}
