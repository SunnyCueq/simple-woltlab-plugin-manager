<?php

namespace shrinkr\data\visit;

use wcf\data\DatabaseObjectEditor;

/**
 * Provides functions to edit visits.
 * 
 * Editor class for Visit database objects. Extends DatabaseObjectEditor
 * to provide create, update, and delete functionality for visit tracking entries.
 * Visits are typically created automatically by the system, not manually.
 *
 * @author      Sunny C
 * @copyright   2026 Sunny C
 * @license     License for Commercial Plugins
 * @link        https://sunnyc.de
 * @package     de.sunnyc.wsc.shrinkr
 * @subpackage  data.visit
 *
 * @method static Visit create(array $parameters = [])
 * @method      Visit getDecoratedObject()
 * @mixin       Visit
 */
class VisitEditor extends DatabaseObjectEditor
{
    /**
     * Base class name for this editor.
     *
     * @var    string
     */
    protected static $baseClass = Visit::class;
}

