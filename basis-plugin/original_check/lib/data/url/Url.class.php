<?php

namespace urlshort\data\url;

use wcf\data\DatabaseObject;
use wcf\data\page\Page;
use wcf\system\request\IRouteController;
use wcf\system\request\LinkHandler;
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
     * returns the title.
     *
     * @return	string
     */
    public function __toString()
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
     * return shortened url
     *
     * @param	string		$isAcp
     * @return	string
     */
    public function getShortedUrl($isAcp = false)
    {
        //get redirect page and redirect page application by redirect page identifier
        $redirectPage = Page::getPageByIdentifier('dev.tkirch.wsc.urlshort.redirect');
        $redirectPageApplication = $redirectPage->getApplication();

        //check if external url isset
        if (URLSHORT_EXPERTMODE_ACTIVE && !empty(URLSHORT_EXTERNAL_URL)) {
            $url = FileUtil::addTrailingSlash(URLSHORT_EXTERNAL_URL) . $this->hash;
            if (\wcf\util\Url::is($url)) {
                return $url;
            }
        }

        //check if acp workaround is needed
        if ($isAcp) {
            //acp workaround
            $url = LinkHandler::getInstance()->getLink('Redirect', [
                'application' => $redirectPageApplication->getAbbreviation(),
                'hash' => $this->hash,
                'forceFrontend' => true,
            ]);

            $controllerPart = 'r';
            if ($redirectPage->controllerCustomURL) {
                $controllerPart = $redirectPage->controllerCustomURL;
            }
            
            $controllerURL = (!empty($redirectPage->controllerCustomURL) ? $redirectPage->controllerCustomURL : 'redirect');
            $url = \str_replace($controllerURL . '/', $controllerPart . '/', $url);
            $url = \preg_replace('/[?|&]hash=/', '', $url);
            $url .= '/';
            
            return $url;
        } else {
            return LinkHandler::getInstance()->getLink('Redirect', [
                'application' => $redirectPageApplication->getAbbreviation(),
                'hash' => $this->hash,
                'forceFrontend' => true,
            ]);
        }
    }

    /**
     * returns the url with the given hash
     * copied from https://github.com/WoltLab/WCF/blob/d24be33172c0479f35e2f1bd4e82180f2054d7c1/wcfsetup/install/files/lib/data/user/User.class.php#L319
     *
     * @param	string		$hash
     * @return	Url
     */
    public static function getUrlByHash($hash)
    {
        $sql = "SELECT * FROM urlshort1_url WHERE hash = ?";
        $statement = WCF::getDB()->prepare($sql);
        $statement->execute([$hash]);
        $row = $statement->fetchArray();
        if (!$row) {
            $row = [];
        }
        
        return new self(null, $row);
    }
}
