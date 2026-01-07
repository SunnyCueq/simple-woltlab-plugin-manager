<?php

namespace urlshort\data\guestreaction;

use wcf\data\DatabaseObjectEditor;

/**
 * Provides functions to edit guest reactions.
 *
 * @author      Sunny C. <https://benjaro.info>
 * @copyright   2022-2025 Benjaro
 * @license     License for Commercial Plugins <https://benjaro.info>
 *
 * @package    dev.tkirch.wsc.urlshort
 * @subpackage data.guestreaction
 */
class GuestReactionEditor extends DatabaseObjectEditor
{
    /**
     * @inheritDoc
     */
    protected static $baseClass = GuestReaction::class;
}

