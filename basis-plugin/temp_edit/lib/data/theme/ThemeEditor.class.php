<?php

namespace urlshort\data\theme;

use wcf\data\DatabaseObjectEditor;

/**
 * Provides functions to edit themes.
 *
 * @author      Sunny C. <https://benjaro.info>
 * @copyright   2022-2025 Benjaro
 * @license     License for Commercial Plugins <https://benjaro.info>
 *
 * @package    dev.tkirch.wsc.urlshort
 * @subpackage data.theme
 */
class ThemeEditor extends DatabaseObjectEditor
{
    /**
     * @inheritdoc
     */
    protected static $baseClass = Theme::class;
}

