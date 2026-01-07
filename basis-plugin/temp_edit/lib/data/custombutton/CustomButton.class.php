<?php

namespace shrinkr\data\custombutton;

use wcf\data\DatabaseObject;
use wcf\data\ITitledObject;
use wcf\system\WCF;

/**
 * Represents a custom button for a shortened URL.
 *
 * @author      Sunny C. <https://benjaro.info>
 * @copyright   2026 Sunny C
 * @license     License for Commercial Plugins <https://benjaro.info>
 *
 * @package    de.sunnyc.wsc.shrinkr
 * @subpackage data.custombutton
 *
 * @property-read int    $customButtonID  Unique ID of the custom button
 * @property-read int    $linkID          ID of the associated URL
 * @property-read string $targetUrl      The target URL for the button
 * @property-read string $title          Display title for the button
 * @property-read int    $sortOrder      Sort order (lower = higher priority)
 */
class CustomButton extends DatabaseObject implements ITitledObject
{
    /**
     * @inheritDoc
     */
    protected static $databaseTableName = 'custom_button';

    /**
     * @inheritDoc
     */
    protected static $databaseTableIndexName = 'customButtonID';

    /**
     * Returns the title of the custom button.
     *
     * @return string The title
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * Returns the target URL of the custom button.
     *
     * @return string The target URL
     */
    public function getTargetUrl(): string
    {
        return $this->targetUrl;
    }

    /**
     * Returns the sort order of the custom button.
     *
     * @return int The sort order
     */
    public function getSortOrder(): int
    {
        return $this->sortOrder;
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
     * Returns true, if current user can add custom buttons.
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

