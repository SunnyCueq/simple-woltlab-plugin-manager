<?php

namespace urlshort\data\url\reaction;

use wcf\data\DatabaseObjectEditor;

/**
 * Provides functions to edit guest reactions.
 *
 * @author      Sunny C. <https://benjaro.info>
 * @copyright   2022-2025 Benjaro
 * @license     License for Commercial Plugins <https://benjaro.info>
 *
 * @package    dev.tkirch.wsc.urlshort
 * @subpackage data.url.reaction
 *
 * @method static GuestUrlReaction create(array $parameters = [])
 * @method GuestUrlReaction getDecoratedObject()
 * @mixin GuestUrlReaction
 */
class GuestUrlReactionEditor extends DatabaseObjectEditor
{
    /**
     * @inheritDoc
     */
    protected static $baseClass = GuestUrlReaction::class;
}
