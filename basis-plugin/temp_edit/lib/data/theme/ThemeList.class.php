<?php

namespace urlshort\data\theme;

use wcf\data\DatabaseObjectList;

/**
 * Represents a list of themes.
 *
 * @author      Sunny C. <https://benjaro.info>
 * @copyright   2022-2025 Benjaro
 * @license     License for Commercial Plugins <https://benjaro.info>
 *
 * @package    dev.tkirch.wsc.urlshort
 * @subpackage data.theme
 */
class ThemeList extends DatabaseObjectList
{
    /**
     * @inheritDoc
     */
    public $className = Theme::class;
}

