<?php

namespace shrinkr\data\custombutton;

use wcf\data\DatabaseObjectEditor;

/**
 * Provides functions to edit custom buttons.
 * 
 * Editor class for CustomButton database objects. Extends DatabaseObjectEditor
 * to provide create, update, and delete functionality for custom button entries.
 *
 * @author      Sunny C
 * @copyright   2026 Sunny C
 * @license     License for Commercial Plugins
 * @link        https://sunnyc.de
 * @package     de.sunnyc.wsc.shrinkr
 * @subpackage  data.custombutton
 *
 * @method static CustomButton  create(array $parameters = [])
 * @method        CustomButton  getDecoratedObject()
 * @mixin         CustomButton
 */
class CustomButtonEditor extends DatabaseObjectEditor
{
    /**
     * Base class name for this editor.
     *
     * @var    string
     */
    protected static $baseClass = CustomButton::class;
}

