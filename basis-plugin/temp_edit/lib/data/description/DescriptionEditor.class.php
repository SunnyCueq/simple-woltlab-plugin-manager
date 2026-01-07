<?php

namespace urlshort\data\description;

use wcf\data\DatabaseObjectEditor;

/**
 * Provides functions to edit descriptions.
 *
 * @author      Sunny C. <https://benjaro.info>
 * @copyright   2022-2025 Benjaro
 * @license     License for Commercial Plugins <https://benjaro.info>
 *
 * @package    dev.tkirch.wsc.urlshort
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
