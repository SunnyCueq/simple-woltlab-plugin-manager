<?php

namespace urlshort\data\guestreaction;

use wcf\data\DatabaseObjectList;

/**
 * Represents a list of guest reactions.
 *
 * @author      Sunny C. <https://benjaro.info>
 * @copyright   2022-2025 Benjaro
 * @license     License for Commercial Plugins <https://benjaro.info>
 *
 * @package    dev.tkirch.wsc.urlshort
 * @subpackage data.guestreaction
 */
class GuestReactionList extends DatabaseObjectList
{
    /**
     * @inheritDoc
     */
    public $className = GuestReaction::class;
}

