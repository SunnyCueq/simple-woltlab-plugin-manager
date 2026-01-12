<?php

namespace shrinkr\data\visit;

use wcf\data\DatabaseObjectEditor;

/**
 * Provides functions to edit visits.
 *
 * @author      Sunny C
 * @copyright   2026 Sunny C
 * @license     License for Commercial Plugins
 *
 * @package    de.sunnyc.wsc.shrinkr
 * @subpackage data.visit
 *
 * @method static Visit create(array $parameters = [])
 * @method      Visit getDecoratedObject()
 * @mixin       Visit
 */
class VisitEditor extends DatabaseObjectEditor
{
    /**
     * @inheritDoc
     */
    protected static $baseClass = Visit::class;
}

