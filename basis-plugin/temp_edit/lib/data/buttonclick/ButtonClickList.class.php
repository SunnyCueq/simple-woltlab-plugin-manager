<?php

namespace shrinkr\data\buttonclick;

use wcf\data\DatabaseObjectList;

/**
 * Represents a list of button clicks.
 *
 * @author      Sunny C
 * @copyright   2026 Sunny C
 * @license     License for Commercial Plugins
 *
 * @package    de.sunnyc.wsc.shrinkr
 * @subpackage data.buttonclick
 */
class ButtonClickList extends DatabaseObjectList
{
    /**
     * @inheritDoc
     */
    public $className = ButtonClick::class;
}

