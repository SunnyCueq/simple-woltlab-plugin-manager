<?php

namespace urlshort\data\custombutton;

use wcf\data\DatabaseObjectEditor;

/**
 * Provides functions to edit custom buttons.
 *
 * @author      Sunny C. <https://benjaro.info>
 * @copyright   2022-2025 Benjaro
 * @license     License for Commercial Plugins <https://benjaro.info>
 *
 * @package    dev.tkirch.wsc.urlshort
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

