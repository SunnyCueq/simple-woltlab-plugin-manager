<?php

namespace urlshort\system\favicon;

use wcf\system\io\HttpFactory;
use wcf\system\SingletonFactory;
use wcf\util\FileUtil;
use wcf\util\StringUtil;

/**
 * Handles favicon fetching from URLs.
 *
 * Fetches favicons from websites without using external services.
 * Policy-compliant implementation for WoltLab Plugin Store.
 *
 * @author      Sunny C. <https://benjaro.info>
 * @copyright   2022-2025 Benjaro
 * @license     License for Commercial Plugins <https://benjaro.info>
 *
 * @package    dev.tkirch.wsc.urlshort
 * @subpackage system.favicon
 */
class FaviconHandler extends SingletonFactory
{
    /**
     * Cache directory for favicons (relative to WCF_DIR).
     */
    private const CACHE_DIR = 'images/favicons/';

    /**
     * Target size for favicon (16x16 is standard).
     */
    private const TARGET_SIZE = 16;

    /**
     * Cache lifetime in seconds (7 days).
     */
    private const CACHE_LIFETIME = 604800;

    /**
     * Returns the favicon URL for a given URL.
     *
     * @param string $url The URL to fetch favicon from
     * @return string|null Relative path to cached favicon, or null if not found
     */
    public function getFaviconPath(string $url): ?string
    {
        // Normalize URL
        $url = StringUtil::trim($url);

        if (empty($url) || !$this->isValidUrl($url)) {
            return null;
        }

        // Parse URL
        $urlParts = \parse_url($url);
        if (empty($urlParts['host'])) {
            return null;
        }

        // Generate cache filename based on host
        $cacheFilename = $this->getCacheFilename($urlParts['host']);
        $cachePath = WCF_DIR . self::CACHE_DIR . $cacheFilename;

        // Return cached favicon if exists and not expired
        if (\file_exists($cachePath)) {
            $fileAge = TIME_NOW - \filemtime($cachePath);
            if ($fileAge < self::CACHE_LIFETIME) {
                return self::CACHE_DIR . $cacheFilename;
            }
        }

        // Try to fetch favicon
        $faviconUrl = $this->findFaviconUrl($url, $urlParts);

        if ($faviconUrl && $this->downloadFavicon($faviconUrl, $cachePath)) {
            return self::CACHE_DIR . $cacheFilename;
        }

        return null;
    }

    /**
     * Extracts the page title from a given URL.
     *
     * @param string $url The URL to fetch title from
     * @return string|null Page title, or null if not found
     */
    public function extractPageTitle(string $url): ?string
    {
        // Normalize URL
        $url = StringUtil::trim($url);

        if (empty($url) || !$this->isValidUrl($url)) {
            return null;
        }

        try {
            // Fetch the page HTML using WoltLab's HttpFactory
            $client = HttpFactory::makeClient();
            $response = $client->get($url);

            if ($response->getStatusCode() !== 200) {
                return null;
            }

            $html = (string)$response->getBody();

            // Extract <title> tag
            if (\preg_match('/<title[^>]*>([^<]+)<\/title>/i', $html, $matches)) {
                $title = \html_entity_decode($matches[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');
                return StringUtil::trim($title);
            }

            return null;
        } catch (\Exception) {
            return null;
        }
    }

    /**
     * Validates if a URL is valid.
     *
     * @param string $url
     * @return bool
     */
    private function isValidUrl(string $url): bool
    {
        return \filter_var($url, FILTER_VALIDATE_URL) !== false
            && \preg_match('/^https?:\/\//i', $url);
    }

    /**
     * Generates cache filename for a host.
     *
     * @param string $host
     * @return string
     */
    private function getCacheFilename(string $host): string
    {
        return \sha1($host) . '-' . self::TARGET_SIZE . 'x' . self::TARGET_SIZE . '.png';
    }

    /**
     * Finds the favicon URL for a given page.
     *
     * @param string $url Full URL
     * @param array $urlParts Parsed URL parts
     * @return string|null Favicon URL or null
     */
    private function findFaviconUrl(string $url, array $urlParts): ?string
    {
        try {
            // Try to fetch the page HTML
            $client = HttpFactory::makeClient();
            $response = $client->get($url);

            if ($response->getStatusCode() !== 200) {
                return $this->tryDefaultFavicon($urlParts);
            }

            $html = (string)$response->getBody();

            // Parse HTML for <link rel="icon"> tags
            if (\preg_match_all(
                '/<link[^>]+rel=["\'](?:icon|shortcut icon|alternate icon)["\'][^>]*>/i',
                $html,
                $matches
            )) {
                foreach ($matches[0] as $linkTag) {
                    // Extract href attribute
                    if (\preg_match('/href=["\']([^"\']+)["\']/i', $linkTag, $hrefMatch)) {
                        $faviconUrl = $hrefMatch[1];

                        // Skip data: URIs (too complex for now)
                        if (\str_starts_with($faviconUrl, 'data:')) {
                            continue;
                        }

                        // Convert relative URLs to absolute
                        $faviconUrl = $this->makeAbsoluteUrl($faviconUrl, $url, $urlParts);

                        if ($this->isValidUrl($faviconUrl)) {
                            return $faviconUrl;
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            // Ignore errors, try default favicon
        }

        // Fallback: try /favicon.ico
        return $this->tryDefaultFavicon($urlParts);
    }

    /**
     * Tries the default /favicon.ico location.
     *
     * @param array $urlParts
     * @return string|null
     */
    private function tryDefaultFavicon(array $urlParts): ?string
    {
        return ($urlParts['scheme'] ?? 'https') . '://' . $urlParts['host'] . '/favicon.ico';
    }

    /**
     * Converts a relative URL to absolute.
     *
     * @param string $relativeUrl
     * @param string $baseUrl
     * @param array $urlParts
     * @return string
     */
    private function makeAbsoluteUrl(string $relativeUrl, string $baseUrl, array $urlParts): string
    {
        // Already absolute
        if (\str_starts_with($relativeUrl, 'http://') || \str_starts_with($relativeUrl, 'https://')) {
            return $relativeUrl;
        }

        // Protocol-relative URL
        if (\str_starts_with($relativeUrl, '//')) {
            return ($urlParts['scheme'] ?? 'https') . ':' . $relativeUrl;
        }

        // Root-relative URL
        if (\str_starts_with($relativeUrl, '/')) {
            return ($urlParts['scheme'] ?? 'https') . '://' . $urlParts['host'] . $relativeUrl;
        }

        // Relative to current path
        $basePath = \dirname($baseUrl);
        return \rtrim($basePath, '/') . '/' . $relativeUrl;
    }

    /**
     * Downloads a favicon and saves it to cache.
     *
     * @param string $faviconUrl
     * @param string $cachePath
     * @return bool Success
     */
    private function downloadFavicon(string $faviconUrl, string $cachePath): bool
    {
        try {
            // Ensure cache directory exists
            $cacheDir = \dirname($cachePath);
            if (!\is_dir($cacheDir)) {
                FileUtil::makePath($cacheDir);
            }

            // Download favicon
            $client = HttpFactory::makeClient();
            $response = $client->get($faviconUrl);

            if ($response->getStatusCode() !== 200) {
                return false;
            }

            $imageData = (string)$response->getBody();

            // Basic validation: check if it's an image
            $contentType = $response->getHeaderLine('Content-Type');
            if (!\str_starts_with($contentType, 'image/')) {
                return false;
            }

            // Save to cache (simple approach: save as-is, no resizing for now)
            // TODO: Add image resizing with GD if needed
            \file_put_contents($cachePath, $imageData);

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
