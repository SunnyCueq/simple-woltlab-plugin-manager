<?php

namespace shrinkr\data\discount;

use wcf\data\DatabaseObjectEditor;

/**
 * Provides functions to edit discounts.
 * 
 * Editor class for Discount database objects. Extends DatabaseObjectEditor
 * to provide create, update, and delete functionality for discount entries.
 *
 * @author      Sunny C
 * @copyright   2026 Sunny C
 * @license     License for Commercial Plugins
 * @link        https://sunnyc.de
 * @package     de.sunnyc.wsc.shrinkr
 * @subpackage  data.discount
 */
class DiscountEditor extends DatabaseObjectEditor
{
    /**
     * Base class name for this editor.
     *
     * @var    string
     */
    protected static $baseClass = Discount::class;
}
