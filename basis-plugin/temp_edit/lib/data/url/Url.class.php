<?php

namespace urlshort\data\url;

use wcf\data\DatabaseObject;
use wcf\data\page\Page;
use wcf\system\request\IRouteController;
use wcf\system\request\LinkHandler;
use wcf\system\request\RouteHandler;
use wcf\system\WCF;
use wcf\util\FileUtil;

/**
 * @author      Julian Pfeil, Titus Kirch <https://julian-pfeil.de>
 * @link        https://darkwood.design/store/user-file-list/1298-julian-pfeil/
 * @copyright   2022 Julian Pfeil Websites & Co.
 * @license     License for Commercial Plugins <https://julian-pfeil.de/lizenz/>
 *
 * @package    dev.tkirch.wsc.urlshort
 * @subpackage data.url
 */
class Url extends DatabaseObject implements IRouteController
{

    /**
     * Returns the title.
     *
     * @return	string
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
     * Return shortened URL (format: /r/{hash}/)
     *
     * @param	bool		$isAcp
     * @return	string
     */
    public function getShortedUrl(bool $isAcp = false): string
    {
        // Get redirect page by redirect page identifier
        $redirectPage = Page::getPageByIdentifier('dev.tkirch.wsc.urlshort.redirect');
        if (!$redirectPage || !$redirectPage->pageID) {
            return '';
        }

        // Check if external URL is set
        if (URLSHORT_EXPERTMODE_ACTIVE && !empty(URLSHORT_EXTERNAL_URL)) {
            $url = FileUtil::addTrailingSlash(URLSHORT_EXTERNAL_URL) . $this->hash;
            if (\wcf\util\Url::is($url)) {
                return $url;
            }
        }

        // Get individual URL (should be "r")
        $individualUrl = $redirectPage->controllerCustomURL ?: 'r';

        // Check if URL rewriting is enabled
        $urlRewriteEnabled = \defined('URL_OMIT_INDEX_PHP') && URL_OMIT_INDEX_PHP;

        // Prüfe Option: Soll /urls/ Prefix entfernt werden?
        $removeUrlsPrefix = \defined('URLSHORT_REMOVE_URLS_PREFIX') && URLSHORT_REMOVE_URLS_PREFIX;

        // Wenn Prefix entfernt werden soll UND URL-Rewriting aktiv ist, baue URL direkt auf
        if ($removeUrlsPrefix && $urlRewriteEnabled) {
            // Baue URL direkt auf: /r/{hash}/
            $url = '/' . $individualUrl . '/' . $this->hash . '/';
            // Domain hinzufügen
            if (!preg_match('#^https?://#', $url)) {
                $url = RouteHandler::getHost() . $url;
            }
            return $url;
        } else {
            // Normale URL-Generierung mit LinkHandler
            $url = LinkHandler::getInstance()->getLink('Redirect', [
                'application' => 'urlshort',  // Controller liegt in urlshort
                'hash' => $this->hash,
                'forceFrontend' => true,  // Wichtig für Link-Umschreibung
            ]);
            
            // Prüfe, ob getLink() bereits eine vollständige URL zurückgibt
            $isFullUrl = preg_match('#^https?://#', $url);
            $host = null;
            if ($isFullUrl) {
                // Extrahiere Domain aus der vollständigen URL
                $parsedUrl = parse_url($url);
                if (isset($parsedUrl['scheme']) && isset($parsedUrl['host'])) {
                    $host = $parsedUrl['scheme'] . '://' . $parsedUrl['host'];
                    // Entferne Domain für Regex-Ersetzungen
                    $url = substr($url, strlen($host));
                }
            }
            
            // Schritt 1: Ersetze "redirect" durch individuelle URL "r"
            $url = preg_replace('#redirect/#', $individualUrl . '/', $url);
            $url = preg_replace('#redirect&hash=#', $individualUrl . '/', $url);
            $url = preg_replace('#redirect#', $individualUrl, $url);
            
            // Schritt 2: Entferne &hash= Parameter und füge Hash direkt in die URL ein
            $url = preg_replace('#[?&]hash=' . preg_quote($this->hash, '#') . '#', '/' . $this->hash . '/', $url);
            $url = preg_replace('#&hash=' . preg_quote($this->hash, '#') . '#', '/' . $this->hash . '/', $url);
            
            // Normalisiere doppelte Slashes
            $url = preg_replace('#//+#', '/', $url);
            
            // Schritt 3: Stelle sicher, dass Format korrekt ist
            if ($urlRewriteEnabled) {
                // Link-Umschreibung AN
                $url = preg_replace('#/index\.php\?#', '/', $url);
                $url = preg_replace('#^index\.php\?#', '', $url);
                
                // Ensure format is /r/{hash}/ oder /urls/r/{hash}/
                $url = preg_replace(
                    '#/' . preg_quote($individualUrl, '#') . '/+' . preg_quote($this->hash, '#') . '/?#',
                    '/' . $individualUrl . '/' . $this->hash . '/',
                    $url
                );
            } else {
                // Link-Umschreibung AUS
                if (!str_contains($url, 'index.php?')) {
                    $url = preg_replace('#/' . preg_quote($individualUrl, '#') . '/#', '/index.php?' . $individualUrl . '/', $url);
                }
                
                // Ensure hash is in correct format
                $url = preg_replace(
                    '#/' . preg_quote($individualUrl, '#') . '/+' . preg_quote($this->hash, '#') . '/?#',
                    '/' . $individualUrl . '/' . $this->hash . '/',
                    $url
                );
            }
            
            // Wenn Prefix entfernt werden soll, entferne /urls/ am allerletzten Schritt
            if ($removeUrlsPrefix) {
                $url = preg_replace('#^/urls/#', '/', $url);
            }
            
            // Finale Normalisierung - entferne doppelte Slashes
            $url = preg_replace('#//+#', '/', $url);
            
            // Am Ende: Domain wieder hinzufügen, wenn nötig
            if ($isFullUrl && $host) {
                // Domain wurde vorher entfernt, jetzt wieder hinzufügen
                $url = $host . $url;
            } elseif (!preg_match('#^https?://#', $url)) {
                // Relative URL: Domain hinzufügen
                $url = RouteHandler::getHost() . $url;
            }
        }

        return $url;
    }

    /**
     * Returns the URL with the given hash
     * copied from https://github.com/WoltLab/WCF/blob/d24be33172c0479f35e2f1bd4e82180f2054d7c1/wcfsetup/install/files/lib/data/user/User.class.php#L319
     *
     * @param	string		$hash
     * @return	Url
     */
    public static function getUrlByHash(string $hash): Url
    {
        $sql = "SELECT * FROM urlshort1_url WHERE hash = ?";
        $statement = WCF::getDB()->prepareStatement($sql);
        $statement->execute([$hash]);
        $row = $statement->fetchArray();
        if (!$row) {
            $row = [];
        }
        
        return new self(null, $row);
    }
}
