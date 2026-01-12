<?php

namespace shrinkr\data\featuredlink;

use wcf\data\DatabaseObjectEditor;

/**
 * Provides functions to edit featured links.
 *
 * @author      Sunny C
 * @copyright   2026 Sunny C
 * @license     License for Commercial Plugins
 *
 * @package    de.sunnyc.wsc.shrinkr
 * @subpackage data.featuredlink
 *
 * @method static FeaturedLink  create(array $parameters = [])
 * @method        FeaturedLink  getDecoratedObject()
 * @mixin         FeaturedLink
 */
class FeaturedLinkEditor extends DatabaseObjectEditor
{
    /**
     * @inheritDoc
     */
    protected static $baseClass = FeaturedLink::class;
}
