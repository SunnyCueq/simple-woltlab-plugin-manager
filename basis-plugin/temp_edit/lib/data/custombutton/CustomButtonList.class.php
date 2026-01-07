<?php

namespace shrinkr\data\custombutton;

use wcf\data\DatabaseObjectList;

/**
 * Represents a list of custom buttons.
 *
 * @author      Sunny C. <https://benjaro.info>
 * @copyright   2026 Sunny C
 * @license     License for Commercial Plugins <https://benjaro.info>
 *
 * @package    de.sunnyc.wsc.shrinkr
 * @subpackage data.custombutton
 *
 * @method CustomButton       current()
 * @method CustomButton[]     getObjects()
 * @method CustomButton|null  getSingleObject()
 * @method CustomButton|null  search($objectID)
 * @property CustomButton[] $objects
 */
class CustomButtonList extends DatabaseObjectList
{
    /**
     * @inheritDoc
     */
    public $className = CustomButton::class;
}

