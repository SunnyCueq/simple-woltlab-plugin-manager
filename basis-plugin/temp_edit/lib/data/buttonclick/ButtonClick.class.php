<?php

namespace shrinkr\data\buttonclick;

use wcf\data\DatabaseObject;

/**
 * Represents a button click tracking entry.
 * 
 * Database object for tracking clicks on buttons (forward, featured links, custom buttons)
 * on shortened link redirect pages. Stores click information including user/session ID,
 * timestamp, and button type for analytics purposes.
 *
 * @author      Sunny C
 * @copyright   2026 Sunny C
 * @license     License for Commercial Plugins
 * @link        https://sunnyc.de
 * @package     de.sunnyc.wsc.shrinkr
 * @subpackage  data.buttonclick
 *
 * @property-read int       $clickID      Unique ID of the click
 * @property-read int       $linkID       ID of the associated shortened URL
 * @property-read string    $buttonType   Type of button ('forward', 'featured_link', 'custom')
 * @property-read int|null  $linkID       ID of the featured link (if buttonType is 'featured_link')
 * @property-read int       $clickTime    Timestamp of the click (UNIX timestamp)
 * @property-read int       $userID       ID of the user (if logged in, 0 for guests)
 * @property-read string    $sessionID    Session ID (for guests, empty for logged-in users)
 */
class ButtonClick extends DatabaseObject
{
    /**
     * Database table name for button clicks.
     *
     * @var    string
     */
    protected static $databaseTableName = 'button_click';

    /**
     * Primary key column name.
     *
     * @var    string
     */
    protected static $databaseTableIndexName = 'clickID';

    /**
     * Returns the button type.
     * 
     * Possible values: 'forward', 'featured_link', 'custom'
     *
     * @return  string  The button type identifier
     */
    public function getButtonType(): string
    {
        return $this->buttonType;
    }

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
     * Returns the click timestamp.
     *
     * @return  int     The UNIX timestamp when the click occurred
     */
    public function getClickTime(): int
    {
        return $this->clickTime;
    }
}

