<?php

namespace shrinkr\data\discount;

use shrinkr\system\favicon\FaviconHandler;
use wcf\data\DatabaseObject;
use wcf\data\ITitledObject;
use wcf\system\WCF;

/**
 * Represents a discount object with associated data.
 *
 * @author      Sunny C
 * @copyright   2026 Sunny C
 * @license     License for Commercial Plugins
 *
 * @package    de.sunnyc.wsc.shrinkr
 * @subpackage data.discount
 *
 * @property int    $discountID
 * @property string $discountValue
 * @property string $hosts
 * @property int    $special
 * @property string $specialIdentifier
 * @property string $additionalText
 * @property string $codes
 * @property string $favicon
 * @property string $primaryColor
 * @property string $secondaryColor
 * @property string $primaryTextColor
 * @property string $secondaryTextColor
 * @property int    $countdownStart
 * @property int    $countdownEnd
 */
class Discount extends DatabaseObject implements ITitledObject
{
    /**
     * @inheritdoc
     */
    public function getTitle(): string
    {
        return $this->discountValue;
    }

    /**
     * @inheritdoc
     */
    public function __toString(): string
    {
        return $this->getTitle();
    }

    /**
     * Returns true, if current user can add discounts
     */
    public function canAdd(): bool
    {
        return WCF::getSession()->getPermission('admin.shrinkr.canManageDiscounts');
    }

    /**
     * Returns true, if current user can edit the discount.
     */
    public function canEdit(): bool
    {
        return $this->canAdd();
    }

    /**
     * Returns true, if current user can delete the discount.
     */
    public function canDelete(): bool
    {
        return $this->canAdd();
    }

    /**
     * Returns the url to the image file for this discount.
     *
     * @param string|null $redirectUrl Optional redirect URL to fetch favicon from
     */
    public function getImagePath(?string $redirectUrl = null): string
    {
        // If favicon is uploaded, use that (has priority)
        if (!empty($this->favicon)) {
            return WCF::getPath() . 'images/discount/' . $this->favicon;
        }

        // If redirectUrl provided, try to fetch favicon automatically (fallback)
        if ($redirectUrl !== null && !empty($redirectUrl)) {
            $faviconPath = FaviconHandler::getInstance()->getFaviconPath($redirectUrl);
            if ($faviconPath !== null) {
                return WCF::getPath() . $faviconPath;
            }
        }

        return '';
    }

    /**
     * Returns a URL from the first host for automatic favicon fetching.
     * Used as fallback when no favicon is uploaded.
     *
     * @return string|null URL or null if no hosts available
     */
    public function getFirstHostUrl(): ?string
    {
        if (empty($this->hosts)) {
            return null;
        }

        // Get first host from comma-separated list
        $hosts = explode(',', $this->hosts);
        $firstHost = trim($hosts[0]);

        if (empty($firstHost)) {
            return null;
        }

        // Construct URL (assume https)
        return 'https://' . $firstHost;
    }

    /**
     * Returns the location of the image for this discount.
     */
    public function getFaviconUploadFileLocations(): array
    {
        if (!$this->favicon) {
            return [];
        }

        return [
            WCF_DIR . 'images/discount/' . $this->favicon,
        ];
    }

    /**
     * Returns the image with the given size.
     *
     * @param int $size Image size in pixels
     * @param string|null $redirectUrl Optional redirect URL to fetch favicon from
     */
    public function getImageTag(int $size = 32, ?string $redirectUrl = null): string
    {
        $imagePath = $this->getImagePath($redirectUrl);
        if (empty($imagePath)) {
            return '';
        }

        return sprintf(
            '<img class="redirectFavicon" src="%s" alt="" style="width: %dpx; height: %dpx">',
            htmlspecialchars($imagePath, ENT_QUOTES, 'UTF-8'),
            $size,
            $size
        );
    }

    /**
     * Validates if the given color string is a safe rgba/rgb value.
     *
     * @param string $color The color value to validate
     * @return bool True if valid, false otherwise
     */
    public static function isValidColor(string $color): bool
    {
        // Allow rgba(r,g,b,a) or rgb(r,g,b) format with optional spaces
        return (bool)preg_match('/^rgba?\(\s*\d+\s*,\s*\d+\s*,\s*\d+\s*(,\s*[\d.]+)?\s*\)$/', $color);
    }

    /**
     * Returns a sanitized color value safe for CSS output.
     * Returns empty string if color is invalid.
     *
     * @param string|null $color The color to sanitize
     * @return string Safe color value or empty string
     */
    public static function sanitizeColor(?string $color): string
    {
        if ($color === null || $color === '') {
            return '';
        }

        return self::isValidColor($color) ? $color : '';
    }

    /**
     * Checks if a countdown is configured (has valid end time).
     *
     * Intelligent detection: If start == end (same day, 00:00),
     * this means "no countdown desired" (default form values).
     *
     * @return bool True if countdown is configured
     */
    public function hasCountdown(): bool
    {
        // No countdown if end time is empty/0
        if (empty($this->countdownEnd) || $this->countdownEnd == 0) {
            return false;
        }

        // No countdown if start == end (same day, 00:00 = default values)
        // This means: User has not actively configured countdown
        if (!empty($this->countdownStart) && $this->countdownStart == $this->countdownEnd) {
            return false;
        }

        return true;
    }

    /**
     * Checks if countdown is currently active (between start and end).
     *
     * @return bool True if countdown is active
     */
    public function hasActiveCountdown(): bool
    {
        if (!$this->hasCountdown()) {
            return false;
        }

        $now = TIME_NOW;

        // If start time is set, check if we are after start
        if (!empty($this->countdownStart) && $this->countdownStart > 0) {
            if ($now < $this->countdownStart) {
                return false; // Not started yet
            }
        }

        // Check if we are before end
        return $now <= $this->countdownEnd;
    }

    /**
     * Returns countdown end timestamp if configured, null otherwise.
     * Shows countdown even if not started yet or already expired.
     *
     * @return int|null Countdown end timestamp
     */
    public function getCountdownEndTimestamp(): ?int
    {
        if (!$this->hasCountdown()) {
            return null;
        }

        return $this->countdownEnd;
    }

    /**
     * Checks if countdown has expired.
     *
     * @return bool True if countdown has passed
     */
    public function isCountdownExpired(): bool
    {
        if (!$this->hasCountdown()) {
            return false;
        }

        return TIME_NOW > $this->countdownEnd;
    }

    /**
     * Returns the formatted discount value for display.
     * Extracts only digits and appends % sign.
     * If no digits found, returns the original value.
     *
     * @return string Formatted discount value (e.g., "30%")
     */
    public function getFormattedDiscountValue(): string
    {
        $rawValue = trim($this->discountValue);
        
        // Extract only digits
        $numberOnly = preg_replace('/[^\d]/', '', $rawValue);
        
        // If digits found, append % sign
        if (!empty($numberOnly)) {
            return $numberOnly . '%';
        }
        
        // Otherwise return original value
        return $rawValue;
    }

    /**
     * Returns the list of discount codes with their labels.
     * Hardcoded codes get special labels, others get "Action code".
     *
     * @return array<int, array{code: string, label: string}> Array of code-label pairs
     */
    public function getCodesList(): array
    {
        if (empty($this->codes) || $this->codes === 'n/a') {
            return [];
        }

        $codes = array_map('trim', explode(',', $this->codes));
        $result = [];

        foreach ($codes as $code) {
            if (empty($code)) {
                continue;
            }

            $label = 'Aktionscode';
            if ($code === 'SHRINKR') {
                $label = 'Standard';
            }

            $result[] = [
                'code' => $code,
                'label' => $label,
            ];
        }

        return $result;
    }

    /**
     * Returns the number of discount codes.
     *
     * @return int Number of codes
     */
    public function getCodesCount(): int
    {
        return count($this->getCodesList());
    }

    /**
     * Returns the label for discount codes (singular or plural).
     *
     * @return string "Rabattcode" or "Rabattcodes"
     */
    public function getCodesLabel(): string
    {
        return $this->getCodesCount() === 1 ? 'Rabattcode' : 'Rabattcodes';
    }

    /**
     * Returns the list of hosts as an array.
     * Filters empty entries and trims whitespace.
     *
     * @return array Array of host strings
     */
    public function getHostsArray(): array
    {
        if (empty($this->hosts)) {
            return [];
        }

        $hosts = array_map('trim', explode(',', $this->hosts));
        return array_filter($hosts, function($host) {
            return !empty($host);
        });
    }

    /**
     * Returns the list of discount codes as an array.
     * Returns empty array if codes are "n/a" or empty.
     *
     * @return array Array of code strings
     */
    public function getCodesArray(): array
    {
        if (empty($this->codes) || $this->codes === 'n/a') {
            return [];
        }

        $codes = array_map('trim', explode(',', $this->codes));
        return array_filter($codes, function($code) {
            return !empty($code);
        });
    }

    /**
     * Checks if the discount has valid codes.
     *
     * @return bool True if valid codes exist
     */
    public function hasValidCodes(): bool
    {
        return !empty($this->getCodesArray());
    }
}
