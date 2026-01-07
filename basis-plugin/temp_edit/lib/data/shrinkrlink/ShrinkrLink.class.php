<?php

/**
 * Represents a shortened link in Shr1nkr.
 *
 * @author      Sunny C
 * @copyright   2026 Sunny C
 * @license     License for Commercial Plugins
 * @link        https://sunnyc.de
 *
 * @package     de.sunnyc.wsc.shrinkr
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
 */
class ShrinkrLink extends DatabaseObject implements IRouteController
{
    /**
     * @var string Database table name
     */
    protected static $databaseTableName = 'shrinkr1_link';

    /**
     * @var string Database table index name
     */
    protected static $databaseTableIndexName = 'linkID';

    /**
     * Returns the title (hash) as string representation.
     */
    public function __toString(): string
    {
        return $this->getTitle();
    }
    
    /**
     * @inheritDoc
     */
    public function getTitle(): string
    {
        return $this->hash;
    }

    /**
     * Returns the shortened URL (format: /r/{hash}/).
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
     * Returns the link with the given hash.
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
     * Alias for getLinkByHash for backwards compatibility.
     * @deprecated Use getLinkByHash() instead
     */
    public static function getLinkByHash(string $hash): ShrinkrLink
    {
        return self::getLinkByHash($hash);
    }
}
