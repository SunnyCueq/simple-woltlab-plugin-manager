<?php

namespace shrinkr\data\visit;

use wcf\data\DatabaseObject;

/**
 * Represents a visit tracking entry.
 *
 * @author      Sunny C. <https://benjaro.info>
 * @copyright   2026 Sunny C
 * @license     License for Commercial Plugins <https://benjaro.info>
 *
 * @package    de.sunnyc.wsc.shrinkr
 * @subpackage data.visit
 *
 * @property-read int    $visitID      Unique ID of the visit
 * @property-read int    $linkID        ID of the associated URL
 * @property-read int    $visitTime    Timestamp of the visit
 * @property-read string|null $referrer Referrer URL (if available)
 * @property-read int|null $userID     ID of the user (if logged in)
 * @property-read string|null $sessionID Session ID (for guests)
 */
class Visit extends DatabaseObject
{
    /**
     * Returns the associated URL ID.
     *
     * @return int The URL ID
     */
    public function getUrlID(): int
    {
        return $this->linkID;
    }

    /**
     * Returns the visit timestamp.
     *
     * @return int The timestamp
     */
    public function getVisitTime(): int
    {
        return $this->visitTime;
    }

    /**
     * Returns the referrer URL.
     *
     * @return string|null The referrer URL or null if not available
     */
    public function getReferrer(): ?string
    {
        return $this->referrer;
    }
}

