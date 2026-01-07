<?php

namespace urlshort\data\custombutton;

use wcf\data\DatabaseObjectList;

/**
 * Represents a list of custom buttons.
 *
 * @author      Sunny C. <https://benjaro.info>
 * @copyright   2022-2025 Benjaro
 * @license     License for Commercial Plugins <https://benjaro.info>
 *
 * @package    dev.tkirch.wsc.urlshort
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

