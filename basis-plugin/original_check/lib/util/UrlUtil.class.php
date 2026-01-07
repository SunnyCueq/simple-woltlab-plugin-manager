<?php

namespace urlshort\util;

use urlshort\data\url\Url;
use urlshort\data\url\UrlEditor;
use urlshort\system\exception\HashException;
use urlshort\system\exception\UrlException;
use wcf\data\page\Page;
use wcf\system\exception\UserInputException;
use wcf\system\request\LinkHandler;
use wcf\util\ArrayUtil;
use wcf\util\StringUtil;

/**
 * @author      Julian Pfeil, Titus Kirch <https://julian-pfeil.de>
 * @link        https://darkwood.design/store/user-file-list/1298-julian-pfeil/
 * @copyright   2022 Julian Pfeil Websites & Co.
 * @license     License for Commercial Plugins <https://julian-pfeil.de/lizenz/>
 *
 * @package    dev.tkirch.wsc.urlshort
 * @subpackage util
 */
final class UrlUtil
{
    /**
     * returns true if the given url is a valid url
     *
     * @param	string      $url
     *
     * @return	boolean
     */
    public static function isValidUrl(string $url)
    {
        if (empty($url)) {
            throw new UserInputException('url');
        }

        if (\mb_strlen($url) > 255) {
            throw new UserInputException('url', 'tooLong');
        }

        if (!\wcf\util\Url::is($url)) {
            throw new UserInputException('url', 'noUrl');
        }

        $parsedUrl = \wcf\util\Url::parse($url);
        if (URLSHORT_ONLY_HTTPS && $parsedUrl['scheme'] != 'https') {
            throw new UserInputException('url', 'httpsRequired');
        }

        $urlBacklist = ArrayUtil::trim(\explode("\n", \mb_strtolower(StringUtil::unifyNewlines(URLSHORT_URL_BLACKLIST))));
        if (!empty($urlBacklist) && \in_array($parsedUrl['host'], $urlBacklist)) {
            throw new UserInputException('url', 'isOnBlacklist');
        }

        if (URLSHORT_DEACTIVATE_FORWARDING_CHAINS) {
            //check if external url isset
            if (URLSHORT_EXPERTMODE_ACTIVE && !empty(URLSHORT_EXTERNAL_URL)) {
                if (StringUtil::startsWith($url, URLSHORT_EXTERNAL_URL)) {
                    throw new UserInputException('url', 'forwardingChain');
                }
            } else {
                //get redirect page and redirect page application by redirect page identifier
                $redirectPage = Page::getPageByIdentifier('dev.tkirch.wsc.urlshort.redirect');
                $redirectPageApplication = $redirectPage->getApplication();

                $redirectPageUrl = LinkHandler::getInstance()->getLink('Redirect', [
                    'application' => $redirectPageApplication->getAbbreviation(),
                    'hash' => '',
                    'forceFrontend' => true,
                ]);
                    
                $controllerPart = 'r';
                if ($redirectPage->controllerCustomURL) {
                    $controllerPart = $redirectPage->controllerCustomURL;
                }
                
                $controllerURL = (!empty($redirectPage->controllerCustomURL) ? $redirectPage->controllerCustomURL : 'redirect');
                $redirectPageUrl = \str_replace($controllerURL . '/', $controllerPart . '/', $redirectPageUrl);
                $redirectPageUrl = \preg_replace('/[?|&]hash=/', '', $redirectPageUrl);
              
                if (StringUtil::startsWith($url, $redirectPageUrl)) {
                    throw new UserInputException('url', 'forwardingChain');
                }
            }
        }

        return true;
    }

    /**
     * returns true if the given hash is a valid hash
     *
     * @param	string      $hash
     * @param   URL         $url
     *
     * @return	boolean
     */
    public static function isValidHash(string $hash, ?URL $url = null)
    {
        if (empty($hash)) {
            throw new UserInputException('hash');
        }

        if (\mb_strlen($hash) > 64) {
            throw new UserInputException('hash', 'tooLong');
        }

        if (!preg_match('~^' . URLSHORT_PATTERN . '$~', $hash)) {
            throw new UserInputException('hash', 'notMatchesPattern');
        }

        $urlID = Url::getUrlByHash($hash)->urlID;
        if ($urlID && ($url == null || $urlID != $url->urlID)) {
            throw new UserInputException('hash', 'alreadyExists');
        }

        return true;
    }

    /**
     * adds an url (as interface for third party developments)
     *
     * @param   string      $url        Url you want to add
     * @param   string      $hash       Hash you want to use
     * @param   string      $prefix     Hashprefix you want to use
     *
     * @return  Url
     */
    public static function add(string $url, string $hash = '', string $prefix = '')
    {
        //validate url
        try {
            self::isValidUrl($url);
        } catch(UserInputException $e) {
            throw new UrlException('Url is not valid.');
        }
        
        //get hash
        if (empty($hash)) {
            //try to generate unique hash, if no hash is passed
            $hash = self::generateHash($prefix);
        } else {
            //add prefix to hash
            $hash = $prefix . $hash;

            //validate hash
            try {
                self::isValidHash($hash);
            } catch(UserInputException $e) {
                throw new HashException('Hash is not valid.');
            }

            //check if hash is not unique
            if (Url::getUrlByHash($hash)->urlID) {
                throw new HashException('Hash is not unique.');
            }
        }

        //save url and give it back
        return UrlEditor::create([
            'url' => $url,
            'hash' => $hash,
        ]);
    }

    /**
     * generate a unqiue random hash
     *
     * @param   string  $prefix         prefix of the hash
     * @param   int     $length         length of the hash
     * @param   int     $maxAttempts    number of maximum attempts to generate unique hashes
     *
     * @return  string
     */
    public static function generateHash(string $prefix = '', int $length = 0, $maxAttempts = 10)
    {
        //setup
        $unique = false;
        $numberOfAttempts = 0;

        //check if the parameters are valid and if the default values must be used
        if (empty($prefix)) {
            $prefix = URLSHORT_HASH_PREFIX;
        }
        if ($length == 0 || ($length + \mb_strlen($prefix)) > 64) {
            $length = URLSHORT_HASH_LENGTH;
        }
        if (($length + \mb_strlen($prefix)) > 64) {
            $prefix = '';
        }

        //try to generate unique hash
        while (!$unique) {
            //increasing the number of attempts
            $numberOfAttempts++;

            //generate hash
            $hashBytes = \random_bytes(\ceil($length / 2));
            $hash = \substr(\bin2hex($hashBytes), 0, $length);

            //check if hash is unique
            if (!Url::getUrlByHash($hash)->urlID) {
                $unique = true;
            }

            //check if the maximum number of attempts was reached and if so, throw an error
            if ($numberOfAttempts >= $maxAttempts) {
                throw new HashException('Hash could not be generated, because the maximum number of attempts was reached (maximum attempts: ' . $maxAttempts . ')');
            }
        }

        //return generated hash with prefix
        return $prefix . $hash;
    }

    /**
     * forbid creation of UrlUtil objects.
     * copied from https://github.com/WoltLab/WCF/blob/d24be33172c0479f35e2f1bd4e82180f2054d7c1/wcfsetup/install/files/lib/util/UserUtil.class.php#L275
     */
    private function __construct()
    {
        //does nothing
    }
}
