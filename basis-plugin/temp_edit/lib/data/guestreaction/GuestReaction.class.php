<?php

namespace shrinkr\data\guestreaction;

use wcf\data\DatabaseObject;

/**
 * Represents a guest reaction entry.
 *
 * @author      Sunny C
 * @copyright   2026 Sunny C
 * @license     License for Commercial Plugins
 *
 * @package    de.sunnyc.wsc.shrinkr
 * @subpackage data.guestreaction
 *
 * @property-read int    $guestReactionID Unique ID of the guest reaction
 * @property-read string $sessionID       Session ID of the guest
 * @property-read string $objectType      Object type identifier
 * @property-read int    $objectID        ID of the reacted object
 * @property-read int    $reactionTypeID  ID of the reaction type
 * @property-read int    $time            Timestamp of the reaction
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

    /**
     * Returns the session ID.
     *
     * @return string The session ID
     */
    public function getSessionID(): string
    {
        return $this->sessionID;
    }

    /**
     * Returns the object type.
     *
     * @return string The object type
     */
    public function getObjectType(): string
    {
        return $this->objectType;
    }

    /**
     * Returns the object ID.
     *
     * @return int The object ID
     */
    public function getObjectID(): int
    {
        return $this->objectID;
    }

    /**
     * Returns the reaction type ID.
     *
     * @return int The reaction type ID
     */
    public function getReactionTypeID(): int
    {
        return $this->reactionTypeID;
    }

    /**
     * Returns the reaction timestamp.
     *
     * @return int The timestamp
     */
    public function getTime(): int
    {
        return $this->time;
    }
}
