<?php

namespace urlshort\data\buttonclick;

use wcf\data\DatabaseObjectList;

/**
 * Represents a list of button clicks.
 *
 * @author      Sunny C. <https://benjaro.info>
 * @copyright   2022-2025 Benjaro
 * @license     License for Commercial Plugins <https://benjaro.info>
 *
 * @package    dev.tkirch.wsc.urlshort
 * @subpackage data.buttonclick
 */
class ButtonClickList extends DatabaseObjectList
{
    /**
     * @inheritDoc
     */
    public $className = ButtonClick::class;
}

