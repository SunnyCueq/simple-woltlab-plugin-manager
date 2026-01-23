<?php

namespace shrinkr\data\description;

use wcf\data\DatabaseObjectEditor;

/**
 * Provides functions to edit descriptions.
 * 
 * Editor class for Description database objects. Extends DatabaseObjectEditor
 * to provide create, update, and delete functionality for description entries.
 *
 * @author      Sunny C
 * @copyright   2026 Sunny C
 * @license     License for Commercial Plugins
 * @link        https://sunnyc.de
 * @package     de.sunnyc.wsc.shrinkr
 * @subpackage  data.description
 *
 * @method static Description  create(array $parameters = [])
 * @method        Description  getDecoratedObject()
 * @mixin         Description
 */
class DescriptionEditor extends DatabaseObjectEditor
{
    /**
     * Base class name for this editor.
     *
     * @var    string
     */
    protected static $baseClass = Description::class;
}
