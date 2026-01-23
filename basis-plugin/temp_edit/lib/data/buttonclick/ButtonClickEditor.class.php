<?php

namespace shrinkr\data\buttonclick;

use wcf\data\DatabaseObjectEditor;

/**
 * Provides functions to edit button clicks.
 * 
 * Editor class for ButtonClick database objects. Extends DatabaseObjectEditor
 * to provide create, update, and delete functionality for button click entries.
 *
 * @author      Sunny C
 * @copyright   2026 Sunny C
 * @license     License for Commercial Plugins
 * @link        https://sunnyc.de
 * @package     de.sunnyc.wsc.shrinkr
 * @subpackage  data.buttonclick
 *
 * @method static ButtonClick create(array $parameters = [])
 * @method      ButtonClick getDecoratedObject()
 * @mixin       ButtonClick
 */
class ButtonClickEditor extends DatabaseObjectEditor
{
    /**
     * Base class name for this editor.
     *
     * @var    string
     */
    protected static $baseClass = ButtonClick::class;
}

