<?php

namespace shrinkr\data\special;

use wcf\data\DatabaseObjectEditor;

/**
 * Provides functions to edit specials.
 *
 * @author      Sunny C. <https://benjaro.info>
 * @copyright   2026 Sunny C
 * @license     License for Commercial Plugins <https://benjaro.info>
 *
 * @package    de.sunnyc.wsc.shrinkr
 * @subpackage data.special
 *
 * @method static Special create(array $parameters = [])
 * @method      Special getDecoratedObject()
 * @mixin       Special
 */
class SpecialEditor extends DatabaseObjectEditor
{
    /**
     * @inheritDoc
     */
    protected static $baseClass = Special::class;
}

