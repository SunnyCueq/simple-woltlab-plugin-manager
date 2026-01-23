<?php

namespace shrinkr\data\custombutton;

use wcf\data\DatabaseObject;
use wcf\data\ITitledObject;
use wcf\system\WCF;

/**
 * Represents a custom button for a shortened URL.
 * 
 * Database object for custom buttons displayed on shortened link redirect pages.
 * Custom buttons allow additional call-to-action links beyond the main redirect URL.
 * Implements ITitledObject for moderation queue compatibility.
 *
 * @author      Sunny C
 * @copyright   2026 Sunny C
 * @license     License for Commercial Plugins
 * @link        https://sunnyc.de
 * @package     de.sunnyc.wsc.shrinkr
 * @subpackage  data.custombutton
 *
 * @property-read int    $customButtonID  Unique ID of the custom button
 * @property-read int    $linkID          ID of the associated shortened URL
 * @property-read string $targetUrl       The target URL for the button
 * @property-read string $title           Display title for the button
 * @property-read int    $sortOrder       Sort order (lower = higher priority)
 */
class CustomButton extends DatabaseObject implements ITitledObject
{
    /**
     * Database table name for custom buttons.
     *
     * @var    string
     */
    protected static $databaseTableName = 'custom_button';

    /**
     * Primary key column name.
     *
     * @var    string
     */
    protected static $databaseTableIndexName = 'customButtonID';

    /**
     * Returns the title of the custom button.
     * 
     * Implements ITitledObject interface.
     *
     * @return  string  The button title
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * Returns the target URL of the custom button.
     *
     * @return  string  The target URL
     */
    public function getTargetUrl(): string
    {
        return $this->targetUrl;
    }

    /**
     * Returns the sort order of the custom button.
     *
     * @return  int     The sort order (lower = higher priority)
     */
    public function getSortOrder(): int
    {
        return $this->sortOrder;
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
     * Checks if the current user can add custom buttons.
     *
     * @return  bool    True if user has admin.shrinkr.canManageCustomButtons permission
     */
    public function canAdd(): bool
    {
        return WCF::getSession()->getPermission('admin.shrinkr.canManageCustomButtons');
    }

    /**
     * Returns true, if current user can edit the custom button.
     */
    public function canEdit(): bool
    {
        return $this->canAdd();
    }

    /**
     * Returns true, if current user can delete the custom button.
     */
    public function canDelete(): bool
    {
        return $this->canAdd();
    }
}

