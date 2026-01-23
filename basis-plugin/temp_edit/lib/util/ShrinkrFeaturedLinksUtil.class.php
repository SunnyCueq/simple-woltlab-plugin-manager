<?php

namespace shrinkr\util;

use shrinkr\data\shrinkrlink\ShrinkrLink;
use wcf\util\StringUtil;
use wcf\util\ArrayUtil;

/**
 * Utility class for featured links management and parsing.
 * 
 * Provides static methods for sorting, parsing, and counting featured links.
 * Featured links are stored in a specific format: "POSITION:URL==Title" where
 * POSITION is optional and used for sorting.
 *
 * @author      Sunny C
 * @copyright   2026 Sunny C
 * @license     License for Commercial Plugins
 * @link        https://sunnyc.de
 * @package     de.sunnyc.wsc.shrinkr
 * @subpackage  util
 */
final class ShrinkrFeaturedLinksUtil
{
    /**
     * Divider used to separate URL and title in featured links format.
     * 
     * Format: "URL==Title" (e.g., "https://google.de==Google Search")
     *
     * @var    string
     */
    public const DIVIDER = '==';

    /**
     * Sorts featured link lines in ascending order by position.
     * 
     * Links with a leading number (position) are sorted numerically.
     * Links without a position are appended to the end of the array.
     *
     * @param   array   $featuredLinkLines  Array of featured link strings
     * @return  array                        Sorted array of featured link strings
     */
    public static function sortArrayAscending(array $featuredLinkLines): array
    {
        usort($featuredLinkLines, function ($a, $b) {
            $numberA = self::extractPosition($a);
            $numberB = self::extractPosition($b);

            if ($numberA !== null && $numberB !== null) {
                return $numberA - $numberB;
            } elseif ($numberA !== null) {
                return -1;
            } elseif ($numberB !== null) {
                return 1;
            } else {
                return 0;
            }
        });

        return $featuredLinkLines;
    }

    /**
     * Extracts the position number from a featured link string.
     * 
     * Parses the leading number from strings in format "POSITION:URL==Title".
     * Returns null if no position is found.
     *
     * @param   string  $string  The featured link string to parse
     * @return  int|null          The position number, or null if not found
     */
    public static function extractPosition(string $string) : ?int
    {
        preg_match('/^(\d+):/', $string, $matches);
        return isset($matches[1]) ? intval($matches[1]) : null;
    }

    /**
     * Counts the number of featured links for a ShrinkrLink object.
     * 
     * Parses the featuredLinks field (newline-separated) and returns the count.
     * Used to display the number of featured links in the ACP list.
     *
     * @param   ShrinkrLink  $featuredLinks  The ShrinkrLink object
     * @return  int                          The number of featured links (0 if empty)
     */
    public static function countFeaturedLinks(ShrinkrLink $featuredLinks): int
    {
        if (empty($featuredLinks->featuredLinks))
            return 0;

        return count(explode("\n", StringUtil::unifyNewlines($featuredLinks->featuredLinks)));
    }

    /**
     * Extracts URL and title from a featured link string.
     * 
     * Parses strings in format "POSITION:URL==Title" or "URL==Title".
     * Removes the position prefix if present, then splits by DIVIDER.
     *
     * @param   string       $featuredLinkLine  The featured link string to parse
     * @return  array|false                     Array with [url, title] or false if invalid format
     */
    public static function extractPositionExplodeLink(string $featuredLinkLine) : array|false
    {
        if (\preg_match('/^(\d+):/', $featuredLinkLine)) {
            $featuredLinkLine = \preg_replace('/^(\d+):/', '', $featuredLinkLine);
        }

        if (\str_contains($featuredLinkLine, self::DIVIDER)) {
            $explodedItem = \explode(self::DIVIDER, $featuredLinkLine, 2);
            $explodedItem = ArrayUtil::trim($explodedItem);

            return $explodedItem;
        }

        return false;
    }

}
