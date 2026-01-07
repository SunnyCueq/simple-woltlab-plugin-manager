<?php

namespace shrinkr\data\theme;

use wcf\data\DatabaseObjectList;

/**
 * Represents a list of themes.
 *
 * @author      Sunny C. <https://benjaro.info>
 * @copyright   2026 Sunny C
 * @license     License for Commercial Plugins <https://benjaro.info>
 *
 * @package    de.sunnyc.wsc.shrinkr
 * @subpackage data.theme
 */
class ThemeList extends DatabaseObjectList
{
    /**
     * @inheritDoc
     */
    public $className = Theme::class;
}

