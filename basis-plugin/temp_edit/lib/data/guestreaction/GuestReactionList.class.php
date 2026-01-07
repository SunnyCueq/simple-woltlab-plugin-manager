<?php

namespace shrinkr\data\guestreaction;

use wcf\data\DatabaseObjectList;

/**
 * Represents a list of guest reactions.
 *
 * @author      Sunny C. <https://benjaro.info>
 * @copyright   2026 Sunny C
 * @license     License for Commercial Plugins <https://benjaro.info>
 *
 * @package    de.sunnyc.wsc.shrinkr
 * @subpackage data.guestreaction
 */
class GuestReactionList extends DatabaseObjectList
{
    /**
     * @inheritDoc
     */
    public $className = GuestReaction::class;
}

