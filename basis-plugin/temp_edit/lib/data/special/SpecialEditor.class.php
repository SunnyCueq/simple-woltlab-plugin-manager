<?php

namespace urlshort\data\special;

use wcf\data\DatabaseObjectEditor;

/**
 * Provides functions to edit specials.
 *
 * @author      Sunny C. <https://benjaro.info>
 * @copyright   2022-2025 Benjaro
 * @license     License for Commercial Plugins <https://benjaro.info>
 *
 * @package    dev.tkirch.wsc.urlshort
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

