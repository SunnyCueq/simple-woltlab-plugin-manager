<?php

namespace urlshort\data\featuredlink;

use wcf\data\DatabaseObject;
use wcf\data\ITitledObject;
use wcf\system\WCF;

/**
 * Represents a featured link for a shortened URL.
 *
 * @author      Sunny C. <https://benjaro.info>
 * @copyright   2022-2025 Benjaro
 * @license     License for Commercial Plugins <https://benjaro.info>
 *
 * @package    dev.tkirch.wsc.urlshort
 * @subpackage data.featuredlink
 *
 * @property-read int    $linkID         Unique ID of the featured link
 * @property-read int    $urlID          ID of the associated URL
 * @property-read string $url            The featured link URL
 * @property-read string $title          Display title for the link
 * @property-read int    $sortOrder      Sort order (lower = higher priority)
 */
class FeaturedLink extends DatabaseObject implements ITitledObject
{
    /**
     * @inheritDoc
     */
    protected static $databaseTableName = 'featured_link';

    /**
     * @inheritDoc
     */
    protected static $databaseTableIndexName = 'linkID';

    /**
     * Returns the title of the featured link.
     *
     * @return string The title
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * Returns the URL of the featured link.
     *
     * @return string The URL
     */
    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * Returns the sort order of the featured link.
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
        return $this->urlID;
    }

    /**
     * Extracts the host from the URL for badge display.
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
        return WCF::getSession()->getPermission('admin.urlshort.canManageFeaturedLinks');
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
