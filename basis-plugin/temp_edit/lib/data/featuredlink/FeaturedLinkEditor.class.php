<?php

namespace shrinkr\data\featuredlink;

use wcf\data\DatabaseObjectEditor;

/**
 * Provides functions to edit featured links.
 * 
 * Editor class for FeaturedLink database objects. Extends DatabaseObjectEditor
 * to provide create, update, and delete functionality for featured link entries.
 *
 * @author      Sunny C
 * @copyright   2026 Sunny C
 * @license     License for Commercial Plugins
 * @link        https://sunnyc.de
 * @package     de.sunnyc.wsc.shrinkr
 * @subpackage  data.featuredlink
 *
 * @method static FeaturedLink  create(array $parameters = [])
 * @method        FeaturedLink  getDecoratedObject()
 * @mixin         FeaturedLink
 */
class FeaturedLinkEditor extends DatabaseObjectEditor
{
    /**
     * Base class name for this editor.
     *
     * @var    string
     */
    protected static $baseClass = FeaturedLink::class;
}
