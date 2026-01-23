<?php

namespace shrinkr\data\theme;

use wcf\data\DatabaseObjectEditor;

/**
 * Provides functions to edit themes.
 * 
 * Editor class for Theme database objects. Extends DatabaseObjectEditor
 * to provide create, update, and delete functionality for theme entries.
 *
 * @author      Sunny C
 * @copyright   2026 Sunny C
 * @license     License for Commercial Plugins
 * @link        https://sunnyc.de
 * @package     de.sunnyc.wsc.shrinkr
 * @subpackage  data.theme
 */
class ThemeEditor extends DatabaseObjectEditor
{
    /**
     * Base class name for this editor.
     *
     * @var    string
     */
    protected static $baseClass = Theme::class;
}

