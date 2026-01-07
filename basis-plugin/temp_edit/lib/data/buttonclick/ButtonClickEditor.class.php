<?php

namespace urlshort\data\buttonclick;

use wcf\data\DatabaseObjectEditor;

/**
 * Provides functions to edit button clicks.
 *
 * @author      Sunny C. <https://benjaro.info>
 * @copyright   2022-2025 Benjaro
 * @license     License for Commercial Plugins <https://benjaro.info>
 *
 * @package    dev.tkirch.wsc.urlshort
 * @subpackage data.buttonclick
 *
 * @method static ButtonClick create(array $parameters = [])
 * @method      ButtonClick getDecoratedObject()
 * @mixin       ButtonClick
 */
class ButtonClickEditor extends DatabaseObjectEditor
{
    /**
     * @inheritDoc
     */
    protected static $baseClass = ButtonClick::class;
}

