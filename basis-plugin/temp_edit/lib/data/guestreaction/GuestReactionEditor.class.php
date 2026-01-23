<?php

namespace shrinkr\data\guestreaction;

use wcf\data\DatabaseObjectEditor;

/**
 * Provides functions to edit guest reactions.
 * 
 * Editor class for GuestReaction database objects. Extends DatabaseObjectEditor
 * to provide create, update, and delete functionality for guest reaction entries.
 *
 * @author      Sunny C
 * @copyright   2026 Sunny C
 * @license     License for Commercial Plugins
 * @link        https://sunnyc.de
 * @package     de.sunnyc.wsc.shrinkr
 * @subpackage  data.guestreaction
 *
 * @method static GuestReaction create(array $parameters = [])
 * @method      GuestReaction getDecoratedObject()
 * @mixin       GuestReaction
 */
class GuestReactionEditor extends DatabaseObjectEditor
{
    /**
     * Base class name for this editor.
     *
     * @var    string
     */
    protected static $baseClass = GuestReaction::class;
}
