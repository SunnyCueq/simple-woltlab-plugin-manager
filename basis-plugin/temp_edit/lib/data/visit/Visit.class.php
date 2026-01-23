<?php

namespace shrinkr\data\visit;

use wcf\data\DatabaseObject;

/**
 * Represents a visit tracking entry.
 * 
 * Database object for tracking visits to shortened links. Stores analytics
 * data including referrer, geolocation, device information, and anonymized
 * IP addresses for DSGVO compliance.
 *
 * @author      Sunny C
 * @copyright   2026 Sunny C
 * @license     License for Commercial Plugins
 * @link        https://sunnyc.de
 * @package     de.sunnyc.wsc.shrinkr
 * @subpackage  data.visit
 *
 * @property-read int       $visitID        Unique ID of the visit
 * @property-read int       $linkID         ID of the associated shortened URL
 * @property-read int       $visitTime      Timestamp of the visit (UNIX timestamp)
 * @property-read string|null $referrer     Referrer URL (if available)
 * @property-read string|null $country     ISO 3166-1 alpha-2 country code
 * @property-read string|null $city         City name
 * @property-read string|null $deviceType   Device type (desktop, mobile, tablet)
 * @property-read string|null $browser      Browser name
 * @property-read string|null $browserVersion Browser version
 * @property-read string|null $os           Operating system name
 * @property-read string|null $ipAddress    Anonymized IP address (DSGVO compliant)
 * @property-read int|null   $userID       ID of the user (if logged in, null for guests)
 * @property-read string|null $sessionID    Session ID (for guests, null for logged-in users)
 */
class Visit extends DatabaseObject
{
    /**
     * Returns the associated shortened URL ID.
     *
     * @return  int     The link ID
     */
    public function getUrlID(): int
    {
        return $this->linkID;
    }

    /**
     * Returns the visit timestamp.
     *
     * @return  int     The UNIX timestamp when the visit occurred
     */
    public function getVisitTime(): int
    {
        return $this->visitTime;
    }

    /**
     * Returns the referrer URL.
     * 
     * The URL from which the visitor came to the shortened link.
     *
     * @return  string|null  The referrer URL, or null if not available
     */
    public function getReferrer(): ?string
    {
        return $this->referrer;
    }
}

