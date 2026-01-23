<?php

namespace shrinkr\data\guestreaction;

use wcf\data\DatabaseObject;

/**
 * Represents a guest reaction entry.
 * 
 * Database object for storing reactions from guests (non-authenticated users).
 * Uses session ID instead of user ID to track guest reactions. Allows guests
 * to react to Shr1nkr links when guest reactions are enabled.
 *
 * @author      Sunny C
 * @copyright   2026 Sunny C
 * @license     License for Commercial Plugins
 * @link        https://sunnyc.de
 * @package     de.sunnyc.wsc.shrinkr
 * @subpackage  data.guestreaction
 *
 * @property-read int    $guestReactionID Unique ID of the guest reaction
 * @property-read string $sessionID       Session ID of the guest (identifies anonymous user)
 * @property-read string $objectType      Object type identifier (e.g., 'de.sunnyc.wsc.shrinkr.likeableShrinkrLink')
 * @property-read int    $objectID        ID of the reacted object (linkID for Shr1nkr links)
 * @property-read int    $reactionTypeID  ID of the reaction type (like, haha, sad, etc.)
 * @property-read int    $time            Timestamp of the reaction (UNIX timestamp)
 */
class GuestReaction extends DatabaseObject
{
    /**
     * Database table name for guest reactions.
     *
     * @var    string
     */
    protected static $databaseTableName = 'guest_reaction';

    /**
     * Primary key column name.
     *
     * @var    string
     */
    protected static $databaseTableIndexName = 'guestReactionID';

    /**
     * Returns the session ID of the guest who reacted.
     *
     * @return  string  The session ID
     */
    public function getSessionID(): string
    {
        return $this->sessionID;
    }

    /**
     * Returns the object type identifier.
     *
     * @return  string  The object type (e.g., 'de.sunnyc.wsc.shrinkr.likeableShrinkrLink')
     */
    public function getObjectType(): string
    {
        return $this->objectType;
    }

    /**
     * Returns the object ID that was reacted to.
     *
     * @return  int     The object ID (linkID for Shr1nkr links)
     */
    public function getObjectID(): int
    {
        return $this->objectID;
    }

    /**
     * Returns the reaction type ID.
     *
     * @return  int     The reaction type ID (like, haha, sad, etc.)
     */
    public function getReactionTypeID(): int
    {
        return $this->reactionTypeID;
    }

    /**
     * Returns the reaction timestamp.
     *
     * @return  int     The UNIX timestamp when the reaction was created
     */
    public function getTime(): int
    {
        return $this->time;
    }
}
