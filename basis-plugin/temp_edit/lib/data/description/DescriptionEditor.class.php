<?php

namespace shrinkr\data\description;

use wcf\data\DatabaseObjectEditor;

/**
 * Provides functions to edit descriptions.
 *
 * @author      Sunny C
 * @copyright   2026 Sunny C
 * @license     License for Commercial Plugins
 *
 * @package    de.sunnyc.wsc.shrinkr
 * @subpackage data.description
 *
 * @method static Description  create(array $parameters = [])
 * @method        Description  getDecoratedObject()
 * @mixin         Description
 */
class DescriptionEditor extends DatabaseObjectEditor
{
    /**
     * @inheritDoc
     */
    protected static $baseClass = Description::class;
}
