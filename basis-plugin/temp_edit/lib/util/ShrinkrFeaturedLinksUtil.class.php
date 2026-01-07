<?php

namespace shrinkr\util;

use shrinkr\data\shrinkrlink\ShrinkrLink;
use wcf\util\StringUtil;
use wcf\util\ArrayUtil;

final class ShrinkrFeaturedLinksUtil
{
    /**
     * Divider used to separate URL and title in featured links format.
     * Format: "URL==Title" (e.g., "https://google.de==Google Search")
     */
    public const DIVIDER = '==';

    //Ascending sorting. Links without a leading number are appended to the end of the array
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

    //returns, if available, the sorting of the link as an integer, otherwise null
    public static function extractPosition(string $string) : ?int
    {
        preg_match('/^(\d+):/', $string, $matches);
        return isset($matches[1]) ? intval($matches[1]) : null;
    }

    // counts the existing featuredLinks to display them in the list.
    public static function(ShrinkrLink $featuredLinks): int
    {
        if (empty($featuredLinks->featuredLinks))
            return 0;

        return count(explode("\n", StringUtil::unifyNewlines($featuredLinks->featuredLinks)));
    }

    // returns the link and the title as an array
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
