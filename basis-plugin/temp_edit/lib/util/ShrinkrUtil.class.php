<?php

namespace shrinkr\util;

use shrinkr\data\shrinkrlink\ShrinkrLink;
use shrinkr\data\shrinkrlink\ShrinkrLinkEditor;
use shrinkr\system\exception\HashException;
use shrinkr\system\exception\UrlException;
use wcf\data\page\Page;
use wcf\system\exception\UserInputException;
use wcf\system\request\LinkHandler;
use wcf\util\ArrayUtil;
use wcf\util\StringUtil;

/**
 * @author      Sunny C, Sunny C <https://sunnyc.de>
 * @link        https://sunnyc.de
 * @copyright   2022 Sunny C Websites & Co.
 * @license     License for Commercial Plugins <https://sunnyc.de/lizenz/>
 *
 * @package    de.sunnyc.wsc.shrinkr
 * @subpackage util
 */
final class ShrinkrUtil
{
    /**
     * Returns true if the given URL is a valid URL
     *
     * @param	string      $url
     *
     * @return	bool
     */
    public static function isValidUrl(string $url): bool
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
        if (SHRINKR_ONLY_HTTPS && $parsedUrl['scheme'] != 'https') {
            throw new UserInputException('url', 'httpsRequired');
        }

        $urlBacklist = ArrayUtil::trim(\explode("\n", \mb_strtolower(StringUtil::unifyNewlines(SHRINKR_URL_BLACKLIST))));
        if (!empty($urlBacklist) && \in_array($parsedUrl['host'], $urlBacklist)) {
            throw new UserInputException('url', 'isOnBlacklist');
        }

        if (SHRINKR_DEACTIVATE_FORWARDING_CHAINS) {
            // Check if external URL is set
            if (SHRINKR_EXPERTMODE_ACTIVE && !empty(SHRINKR_EXTERNAL_URL)) {
                if (StringUtil::startsWith($url, SHRINKR_EXTERNAL_URL)) {
                    throw new UserInputException('url', 'forwardingChain');
                }
            } else {
                // Get redirect page and redirect page application by redirect page identifier
                $redirectPage = Page::getPageByIdentifier('de.sunnyc.wsc.shrinkr.redirect');
                $redirectPageApplication = $redirectPage->getApplication();

                // Get base URL for redirect page (format: /r/{hash}/)
                // LinkHandler automatically uses CMS Page Individual URL
                $redirectPageUrl = LinkHandler::getInstance()->getLink('Redirect', [
                    'application' => $redirectPageApplication->getAbbreviation(),
                    'hash' => 'PLACEHOLDER',
                    'forceFrontend' => true,
                ]);
                
                // Extract base URL (remove hash parameter) for forwarding chain check
                $redirectPageUrl = \preg_replace('/PLACEHOLDER\/?$/', '', $redirectPageUrl);
                $redirectPageUrl = \rtrim($redirectPageUrl, '/') . '/';
              
                if (StringUtil::startsWith($url, $redirectPageUrl)) {
                    throw new UserInputException('url', 'forwardingChain');
                }
            }
        }

        return true;
    }

    /**
     * Returns true if the given hash is a valid hash
     *
     * @param	string      $hash
     * @param   Url|null    $url
     *
     * @return	bool
     */
    public static function(ShrinkrLink $url = null): bool
    {
        if (empty($hash)) {
            throw new UserInputException('hash');
        }

        if (\mb_strlen($hash) > 64) {
            throw new UserInputException('hash', 'tooLong');
        }

        if (!preg_match('~^' . SHRINKR_PATTERN . '$~', $hash)) {
            throw new UserInputException('hash', 'notMatchesPattern');
        }

        $linkID = ShrinkrLink::getLinkByHash($hash)->linkID;
        if ($linkID && ($url == null || $linkID != $url->linkID)) {
            throw new UserInputException('hash', 'alreadyExists');
        }

        return true;
    }

    /**
     * Adds a URL (as interface for third party developments)
     *
     * @param   string      $url        URL you want to add
     * @param   string      $hash       Hash you want to use
     * @param   string      $prefix     Hash prefix you want to use
     *
     * @return  Url
     */
    public static function add(string $url, string $hash = '', string $prefix = ''): Url
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
            if (ShrinkrLink::getLinkByHash($hash)->linkID) {
                throw new HashException('Hash is not unique.');
            }
        }

        //save url and give it back
        return ShrinkrLinkEditor::create([
            'url' => $url,
            'hash' => $hash,
        ]);
    }

    /**
     * Generate a unique random hash
     *
     * @param   string  $prefix         Prefix of the hash
     * @param   int     $length         Length of the hash
     * @param   int     $maxAttempts    Number of maximum attempts to generate unique hashes
     *
     * @return  string
     */
    public static function generateHash(string $prefix = '', int $length = 0, int $maxAttempts = 10): string
    {
        //setup
        $unique = false;
        $numberOfAttempts = 0;

        //check if the parameters are valid and if the default values must be used
        if (empty($prefix)) {
            $prefix = SHRINKR_HASH_PREFIX;
        }
        if ($length == 0 || ($length + \mb_strlen($prefix)) > 64) {
            $length = SHRINKR_HASH_LENGTH;
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
            if (!ShrinkrLink::getLinkByHash($hash)->linkID) {
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
     * forbid creation of ShrinkrUtil objects.
     * copied from https://github.com/WoltLab/WCF/blob/d24be33172c0479f35e2f1bd4e82180f2054d7c1/wcfsetup/install/files/lib/util/UserUtil.class.php#L275
     */
    private function __construct()
    {
        //does nothing
    }
}
