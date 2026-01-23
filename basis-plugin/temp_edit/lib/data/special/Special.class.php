<?php

namespace shrinkr\data\special;

use wcf\data\DatabaseObject;
use wcf\data\ITitledObject;
use wcf\system\WCF;
use shrinkr\system\favicon\FaviconHandler;

/**
 * Represents a special promotion for a shortened URL.
 * 
 * Database object for special events/promotions (e.g., Halloween, Black Week, Christmas).
 * Associates themes, colors, discount codes, and time-based activation with shortened links.
 * Implements ITitledObject for moderation queue compatibility.
 *
 * @author      Sunny C
 * @copyright   2026 Sunny C
 * @license     License for Commercial Plugins
 * @link        https://sunnyc.de
 * @package     de.sunnyc.wsc.shrinkr
 * @subpackage  data.special
 *
 * @property-read int    $specialID         Unique ID of the special
 * @property-read int    $linkID            ID of the associated shortened URL
 * @property-read string $theme             Theme identifier (halloween, blackweek, christmas, etc.)
 * @property-read string $title             Display title for the overview (not shown on redirect page)
 * @property-read string $discount          Discount text (e.g. "30%") - shown on redirect page
 * @property-read int    $discountID        ID of the associated discount (optional, reserved for future use)
 * @property-read string $codes             Discount codes (comma-separated)
 * @property-read string $primaryColor      Primary background color (RGBA format)
 * @property-read string $secondaryColor    Secondary background color (RGBA format)
 * @property-read string $primaryTextColor  Primary text color (RGBA format)
 * @property-read string $secondaryTextColor Secondary text color (RGBA format)
 * @property-read string $additionalText    Additional HTML text for display
 * @property-read int    $startTime         Start timestamp (UNIX timestamp)
 * @property-read int    $endTime           End timestamp (UNIX timestamp)
 * @property-read bool   $isActive          Whether the special is currently active
 */
class Special extends DatabaseObject implements ITitledObject
{
    /**
     * Returns the title of the special.
     *
     * @return string The title
     */
    public function getTitle(): string
    {
        return $this->title;
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
     * Returns the theme identifier.
     *
     * @return string The theme identifier
     */
    public function getTheme(): string
    {
        return $this->theme;
    }

    /**
     * Returns the discount ID.
     *
     * @return int The discount ID
     */
    public function getDiscountID(): int
    {
        return $this->discountID;
    }

    /**
     * Returns the discount codes as an array.
     *
     * @return array<int, string> Array of discount codes
     */
    public function getCodes(): array
    {
        if (empty($this->codes)) {
            return [];
        }

        return array_map('trim', explode(',', $this->codes));
    }

    /**
     * Checks if the special is currently active (within time range and isActive = true).
     *
     * @return bool True if the special is currently active
     */
    public function isCurrentlyActive(): bool
    {
        if (!$this->isActive) {
            return false;
        }

        $now = TIME_NOW;

        // Check if we're within the time range
        if ($this->startTime > 0 && $now < $this->startTime) {
            return false; // Not started yet
        }

        if ($this->endTime > 0 && $now > $this->endTime) {
            return false; // Already ended
        }

        return true;
    }

    /**
     * Returns remaining seconds until end, or null if not active or no end time.
     *
     * @return int|null Remaining seconds or null
     */
    public function getRemainingSeconds(): ?int
    {
        if (!$this->isCurrentlyActive() || $this->endTime <= 0) {
            return null;
        }

        $remaining = $this->endTime - TIME_NOW;
        return $remaining > 0 ? (int) $remaining : null;
    }

    /**
     * Returns true if the current user can add specials.
     *
     * @return bool True if user can add specials
     */
    public function canAdd(): bool
    {
        return WCF::getSession()->getPermission('admin.shrinkr.canManageSpecials');
    }

    /**
     * Returns true if the current user can edit the special.
     *
     * @return bool True if user can edit the special
     */
    public function canEdit(): bool
    {
        return $this->canAdd();
    }

    /**
     * Returns true if the current user can delete the special.
     *
     * @return bool True if user can delete the special
     */
    public function canDelete(): bool
    {
        return $this->canAdd();
    }

    /**
     * Returns the favicon image tag for this special.
     * Fetches favicon automatically from the redirectUrl.
     *
     * @param int $size Image size in pixels
     * @param string|null $redirectUrl Optional redirect URL to fetch favicon from
     * @return string HTML img tag or empty string
     */
    public function getImageTag(int $size = 32, ?string $redirectUrl = null): string
    {
        if ($redirectUrl === null || empty($redirectUrl)) {
            return '';
        }

        $faviconPath = FaviconHandler::getInstance()->getFaviconPath($redirectUrl);
        if ($faviconPath === null) {
            return '';
        }

        $imagePath = WCF::getPath() . $faviconPath;

        return sprintf(
            '<img class="redirectFavicon" src="%s" alt="" style="width: %dpx; height: %dpx">',
            htmlspecialchars($imagePath, ENT_QUOTES, 'UTF-8'),
            $size,
            $size
        );
    }

    /**
     * Returns HTML with favicon for display in title (used in templates).
     *
     * @param string|null $redirectUrl The URL to fetch favicon from
     * @return string HTML string with favicon + title
     */
    public function getFaviconHtml(?string $redirectUrl = null): string
    {
        $favicon = $this->getImageTag(16, $redirectUrl);
        if (empty($favicon)) {
            return $this->getTitle();
        }

        return $favicon . ' ' . htmlspecialchars($this->getTitle(), ENT_QUOTES, 'UTF-8');
    }
}

