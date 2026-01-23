<?php

/**
 * Represents a shortened link in Shr1nkr.
 * 
 * This class represents a database object for shortened URLs. It provides methods
 * to generate shortened URLs, retrieve links by hash, and manage link metadata
 * such as titles, favicons, and Open Graph images.
 *
 * @author      Sunny C
 * @copyright   2026 Sunny C
 * @license     License for Commercial Plugins
 * @link        https://sunnyc.de
 * @package     de.sunnyc.wsc.shrinkr
 * @subpackage  data.shrinkrlink
 */

namespace shrinkr\data\shrinkrlink;

use wcf\data\DatabaseObject;
use wcf\data\page\Page;
use wcf\system\request\IRouteController;
use wcf\system\request\LinkHandler;
use wcf\system\request\RouteHandler;
use wcf\system\WCF;
use wcf\util\FileUtil;

/**
 * Represents a shortened link database object.
 *
 * @property-read   int         $linkID             Unique link identifier
 * @property-read   string      $url                Target URL
 * @property-read   string      $hash               Short link hash/identifier
 * @property-read   int         $counter            Click counter
 * @property-read   string      $featuredLinks      Serialized featured links data
 * @property-read   string      $linkTitle          Custom link title
 * @property-read   string      $autoExtractedTitle Auto-extracted page title
 * @property-read   string      $faviconUrl         Cached favicon URL
 * @property-read   string      $ogImage            Open Graph image path
 * @property-read   int         $isDemo             Demo data flag (0 or 1)
 */
class ShrinkrLink extends DatabaseObject implements IRouteController
{
    /**
     * Database table name for links.
     *
     * @var    string
     */
    protected static $databaseTableName = 'link';

    /**
     * Primary key column name.
     *
     * @var    string
     */
    protected static $databaseTableIndexName = 'linkID';

    /**
     * Returns the hash as string representation.
     *
     * @return  string  The link hash
     */
    public function __toString(): string
    {
        return $this->getTitle();
    }
    
    /**
     * Returns the link title (hash).
     * 
     * Implements IRouteController interface requirement.
     *
     * @return  string  The link hash
     */
    public function getTitle(): string
    {
        return $this->hash;
    }

    /**
     * Generates the full shortened URL for this link.
     * Respects expert mode settings, URL rewriting, and prefix removal options.
     *
     * @param   bool    $isAcp  Reserved for future use
     * @return  string          The complete shortened URL (e.g., https://example.com/r/abc123/)
     */
    public function getShortedUrl(bool $isAcp = false): string
    {
        // Get redirect page by identifier
        $redirectPage = Page::getPageByIdentifier('de.sunnyc.wsc.shrinkr.redirect');
        if (!$redirectPage || !$redirectPage->pageID) {
            return '';
        }

        // Check if external URL is set
        if (SHRINKR_EXPERTMODE_ACTIVE && !empty(SHRINKR_EXTERNAL_URL)) {
            $url = FileUtil::addTrailingSlash(SHRINKR_EXTERNAL_URL) . $this->hash;
            if (\wcf\util\Url::is($url)) {
                return $url;
            }
        }

        // Get individual URL (should be "r")
        $individualUrl = $redirectPage->controllerCustomURL ?: 'r';

        // Check if URL rewriting is enabled
        $urlRewriteEnabled = \defined('URL_OMIT_INDEX_PHP') && URL_OMIT_INDEX_PHP;

        // Check option: Should /shrinkr/ prefix be removed?
        $removeShrinkrPrefix = \defined('SHRINKR_REMOVE_SHRINKR_PREFIX') && SHRINKR_REMOVE_SHRINKR_PREFIX;

        // If prefix should be removed AND URL rewriting is active, build URL directly
        if ($removeShrinkrPrefix && $urlRewriteEnabled) {
            $url = '/' . $individualUrl . '/' . $this->hash . '/';
            if (!preg_match('#^https?://#', $url)) {
                $url = RouteHandler::getHost() . $url;
            }
            return $url;
        } else {
            // Normal URL generation with LinkHandler
            $url = LinkHandler::getInstance()->getLink('Redirect', [
                'application' => 'shrinkr',
                'hash' => $this->hash,
                'forceFrontend' => true,
            ]);
            
            $isFullUrl = preg_match('#^https?://#', $url);
            $host = null;
            if ($isFullUrl) {
                $parsedUrl = parse_url($url);
                if (isset($parsedUrl['scheme']) && isset($parsedUrl['host'])) {
                    $host = $parsedUrl['scheme'] . '://' . $parsedUrl['host'];
                    $url = substr($url, strlen($host));
                }
            }
            
            // Replace "redirect" with individual URL "r"
            $url = preg_replace('#redirect/#', $individualUrl . '/', $url);
            $url = preg_replace('#redirect&hash=#', $individualUrl . '/', $url);
            $url = preg_replace('#redirect#', $individualUrl, $url);
            
            // Remove &hash= parameter and add hash directly to URL
            $url = preg_replace('#[?&]hash=' . preg_quote($this->hash, '#') . '#', '/' . $this->hash . '/', $url);
            $url = preg_replace('#&hash=' . preg_quote($this->hash, '#') . '#', '/' . $this->hash . '/', $url);
            
            // Normalize double slashes
            $url = preg_replace('#//+#', '/', $url);
            
            if ($urlRewriteEnabled) {
                $url = preg_replace('#/index\.php\?#', '/', $url);
                $url = preg_replace('#^index\.php\?#', '', $url);
                
                $url = preg_replace(
                    '#/' . preg_quote($individualUrl, '#') . '/+' . preg_quote($this->hash, '#') . '/?#',
                    '/' . $individualUrl . '/' . $this->hash . '/',
                    $url
                );
            } else {
                if (!str_contains($url, 'index.php?')) {
                    $url = preg_replace('#/' . preg_quote($individualUrl, '#') . '/#', '/index.php?' . $individualUrl . '/', $url);
                }
                
                $url = preg_replace(
                    '#/' . preg_quote($individualUrl, '#') . '/+' . preg_quote($this->hash, '#') . '/?#',
                    '/' . $individualUrl . '/' . $this->hash . '/',
                    $url
                );
            }
            
            if ($removeShrinkrPrefix) {
                $url = preg_replace('#^/shrinkr/#', '/', $url);
            }
            
            $url = preg_replace('#//+#', '/', $url);
            
            if ($isFullUrl && $host) {
                $url = $host . $url;
            } elseif (!preg_match('#^https?://#', $url)) {
                $url = RouteHandler::getHost() . $url;
            }
        }

        return $url;
    }

    /**
     * Retrieves a link by its hash identifier.
     * Returns an empty object if hash does not exist.
     *
     * @param   string      $hash   The hash to search for
     * @return  ShrinkrLink         Link object (may be empty if not found)
     */
    public static function getLinkByHash(string $hash): ShrinkrLink
    {
        $sql = "SELECT * FROM shrinkr1_link WHERE hash = ?";
        $statement = WCF::getDB()->prepareStatement($sql);
        $statement->execute([$hash]);
        $row = $statement->fetchArray();
        if (!$row) {
            $row = [];
        }
        
        return new self(null, $row);
    }

    /**
     * Returns the upload file locations for the ogImage field.
     * Required by UploadFormField to load existing files.
     *
     * @return  string[]
     */
    public function getOgImageUploadFileLocations(): array
    {
        if (empty($this->ogImage)) {
            return [];
        }
        
        return [$this->ogImage];
    }
}
