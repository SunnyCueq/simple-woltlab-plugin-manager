<?php

namespace shrinkr\data\discount;

use wcf\data\DatabaseObjectDecorator;

/**
 * Decorator for discounts that can be viewed on the frontend.
 * 
 * Extends DatabaseObjectDecorator to provide viewable discount objects for
 * frontend display. Used when displaying discount codes on redirect pages.
 *
 * @author      Sunny C
 * @copyright   2026 Sunny C
 * @license     License for Commercial Plugins
 * @link        https://sunnyc.de
 * @package     de.sunnyc.wsc.shrinkr
 * @subpackage  data.discount
 */
class ViewableDiscount extends DatabaseObjectDecorator
{
    /**
     * Base class name for this decorator.
     *
     * @var    string
     */
    protected static $baseClass = Discount::class;

    /**
     * Gets a specific entry decorated as viewable discount.
     * 
     * Loads a discount by ID and wraps it in a ViewableDiscount decorator
     * for frontend display.
     *
     * @param   int  $discountID  The discount ID to load
     * @return  self               The viewable discount instance
     */
    public static function getDiscount(int $discountID): self
    {
        return new self(new Discount($discountID));
    }
}
