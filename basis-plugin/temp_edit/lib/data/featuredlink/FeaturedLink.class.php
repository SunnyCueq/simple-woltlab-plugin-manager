<?php

namespace shrinkr\data\featuredlink;

use wcf\data\DatabaseObject;
use wcf\data\ITitledObject;
use wcf\system\WCF;

/**
 * Represents a featured link for a shortened URL.
 * 
 * Database object for featured links displayed on shortened link redirect pages.
 * Featured links are additional recommended links shown alongside the main redirect.
 * Implements ITitledObject for moderation queue compatibility.
 *
 * @author      Sunny C
 * @copyright   2026 Sunny C
 * @license     License for Commercial Plugins
 * @link        https://sunnyc.de
 * @package     de.sunnyc.wsc.shrinkr
 * @subpackage  data.featuredlink
 *
 * @property-read int    $linkID         Unique ID of the featured link
 * @property-read int    $linkID         ID of the associated shortened URL
 * @property-read string $url            The featured link URL
 * @property-read string $title          Display title for the link
 * @property-read int    $sortOrder      Sort order (lower = higher priority)
 */
class FeaturedLink extends DatabaseObject implements ITitledObject
{
    /**
     * Database table name for featured links.
     *
     * @var    string
     */
    protected static $databaseTableName = 'featured_link';

    /**
     * Primary key column name.
     *
     * @var    string
     */
    protected static $databaseTableIndexName = 'linkID';

    /**
     * Returns the title of the featured link.
     * 
     * Implements ITitledObject interface.
     *
     * @return  string  The link title
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * Returns the URL of the featured link.
     *
     * @return  string  The featured link URL
     */
    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * Returns the sort order of the featured link.
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
     * Extracts the host from the URL for badge display.
     * 
     * Parses the URL and returns the hostname (domain) for display
     * in featured link badges.
     *
     * @return  string  The hostname extracted from the URL
     */
    public function getHost(): string
    {
        $parsed = parse_url($this->url);
        if (!isset($parsed['host'])) {
            return '';
        }

        // Extract domain (e.g., "google" from "www.google.de")
        $parts = explode('.', $parsed['host']);
        if (count($parts) > 2) {
            // Has subdomain (e.g., www.google.de)
            return strtoupper($parts[count($parts) - 2]);
        } elseif (count($parts) > 1) {
            // No subdomain (e.g., google.de)
            return strtoupper($parts[0]);
        }

        return strtoupper($parsed['host']);
    }

    /**
     * Returns true, if current user can add featured links.
     */
    public function canAdd(): bool
    {
        return WCF::getSession()->getPermission('admin.shrinkr.canManageFeaturedLinks');
    }

    /**
     * Returns true, if current user can edit the featured link.
     */
    public function canEdit(): bool
    {
        return $this->canAdd();
    }

    /**
     * Returns true, if current user can delete the featured link.
     */
    public function canDelete(): bool
    {
        return $this->canAdd();
    }
}
