<?php

namespace shrinkr\data\buttonclick;

use wcf\data\DatabaseObject;

/**
 * Represents a button click tracking entry.
 *
 * @author      Sunny C
 * @copyright   2026 Sunny C
 * @license     License for Commercial Plugins
 *
 * @package    de.sunnyc.wsc.shrinkr
 * @subpackage data.buttonclick
 *
 * @property-read int    $clickID      Unique ID of the click
 * @property-read int    $linkID        ID of the associated URL
 * @property-read string $buttonType   Type of button ('forward', 'featured_link', 'custom')
 * @property-read int|null $linkID     ID of the featured link (if buttonType is 'featured_link')
 * @property-read int    $clickTime    Timestamp of the click
 * @property-read int    $userID       ID of the user (if logged in)
 * @property-read string $sessionID    Session ID (for guests)
 */
class ButtonClick extends DatabaseObject
{
    /**
     * @inheritDoc
     */
    protected static $databaseTableName = 'button_click';

    /**
     * @inheritDoc
     */
    protected static $databaseTableIndexName = 'clickID';

    /**
     * Returns the button type.
     *
     * @return string The button type
     */
    public function getButtonType(): string
    {
        return $this->buttonType;
    }

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
     * Returns the click timestamp.
     *
     * @return int The timestamp
     */
    public function getClickTime(): int
    {
        return $this->clickTime;
    }
}

