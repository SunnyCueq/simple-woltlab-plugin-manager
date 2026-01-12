<?php

namespace shrinkr\data\guestreaction;

use wcf\data\DatabaseObject;

/**
 * Represents a guest reaction.
 *
 * @author      Sunny C
 * @copyright   2026 Sunny C
 * @license     License for Commercial Plugins
 *
 * @package    de.sunnyc.wsc.shrinkr
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

