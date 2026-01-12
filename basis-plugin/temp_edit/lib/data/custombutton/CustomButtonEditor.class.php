<?php

namespace shrinkr\data\custombutton;

use wcf\data\DatabaseObjectEditor;

/**
 * Provides functions to edit custom buttons.
 *
 * @author      Sunny C
 * @copyright   2026 Sunny C
 * @license     License for Commercial Plugins
 *
 * @package    de.sunnyc.wsc.shrinkr
 * @subpackage data.custombutton
 *
 * @method static CustomButton  create(array $parameters = [])
 * @method        CustomButton  getDecoratedObject()
 * @mixin         CustomButton
 */
class CustomButtonEditor extends DatabaseObjectEditor
{
    /**
     * @inheritDoc
     */
    protected static $baseClass = CustomButton::class;
}

