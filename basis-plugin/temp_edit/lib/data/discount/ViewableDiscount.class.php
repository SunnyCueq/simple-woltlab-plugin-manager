<?php

namespace shrinkr\data\discount;

use wcf\data\DatabaseObjectDecorator;

/**
 * Decorator for discounts that can be viewed on the frontend.
 *
 * @author      Sunny C. <https://benjaro.info>
 * @copyright   2026 Sunny C
 * @license     License for Commercial Plugins <https://benjaro.info>
 *
 * @package    de.sunnyc.wsc.shrinkr
 * @subpackage data.discount
 */
class ViewableDiscount extends DatabaseObjectDecorator
{
    /**
     * @inheritdoc
     */
    protected static $baseClass = Discount::class;

    /**
     * Gets a specific entry decorated as viewable discount.
     *
     * @param int $discountID The discount ID
     * @return self The viewable discount instance
     */
    public static function getDiscount(int $discountID): self
    {
        return new self(new Discount($discountID));
    }
}
