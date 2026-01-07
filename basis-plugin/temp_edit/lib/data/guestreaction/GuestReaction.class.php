<?php

namespace urlshort\data\guestreaction;

use wcf\data\DatabaseObject;

/**
 * Represents a guest reaction.
 *
 * @author      Sunny C. <https://benjaro.info>
 * @copyright   2022-2025 Benjaro
 * @license     License for Commercial Plugins <https://benjaro.info>
 *
 * @package    dev.tkirch.wsc.urlshort
 * @subpackage data.guestreaction
 */
class GuestReaction extends DatabaseObject
{
    /**
     * @inheritDoc
     */
    protected static $databaseTableName = 'guest_reaction';

    /**
     * @inheritDoc
     */
    protected static $databaseTableIndexName = 'guestReactionID';
}

