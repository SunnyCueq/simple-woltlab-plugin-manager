<?php

namespace shrinkr\data\theme;

use wcf\data\DatabaseObjectEditor;

/**
 * Provides functions to edit themes.
 *
 * @author      Sunny C. <https://benjaro.info>
 * @copyright   2026 Sunny C
 * @license     License for Commercial Plugins <https://benjaro.info>
 *
 * @package    de.sunnyc.wsc.shrinkr
 * @subpackage data.theme
 */
class ThemeEditor extends DatabaseObjectEditor
{
    /**
     * @inheritdoc
     */
    protected static $baseClass = Theme::class;
}

