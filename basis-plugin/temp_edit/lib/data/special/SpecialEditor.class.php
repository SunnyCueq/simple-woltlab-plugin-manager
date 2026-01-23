<?php

namespace shrinkr\data\special;

use wcf\data\DatabaseObjectEditor;

/**
 * Provides functions to edit specials.
 * 
 * Editor class for Special database objects. Extends DatabaseObjectEditor
 * to provide create, update, and delete functionality for special event entries.
 *
 * @author      Sunny C
 * @copyright   2026 Sunny C
 * @license     License for Commercial Plugins
 * @link        https://sunnyc.de
 * @package     de.sunnyc.wsc.shrinkr
 * @subpackage  data.special
 *
 * @method static Special create(array $parameters = [])
 * @method      Special getDecoratedObject()
 * @mixin       Special
 */
class SpecialEditor extends DatabaseObjectEditor
{
    /**
     * Base class name for this editor.
     *
     * @var    string
     */
    protected static $baseClass = Special::class;
}

