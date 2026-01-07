<?php

namespace urlshort\data\visit;

use wcf\data\DatabaseObjectEditor;

/**
 * Provides functions to edit visits.
 *
 * @author      Sunny C. <https://benjaro.info>
 * @copyright   2022-2025 Benjaro
 * @license     License for Commercial Plugins <https://benjaro.info>
 *
 * @package    dev.tkirch.wsc.urlshort
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

