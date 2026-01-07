<?php

namespace urlshort\data\featuredlink;

use wcf\data\DatabaseObjectEditor;

/**
 * Provides functions to edit featured links.
 *
 * @author      Sunny C. <https://benjaro.info>
 * @copyright   2022-2025 Benjaro
 * @license     License for Commercial Plugins <https://benjaro.info>
 *
 * @package    dev.tkirch.wsc.urlshort
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
