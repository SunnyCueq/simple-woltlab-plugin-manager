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
 * Utility class for Shr1nkr link management and validation.
 * Provides methods for URL/hash validation and link generation.
 *
 * @author      Sunny C
 * @copyright   2026 Sunny C
 * @license     License for Commercial Plugins
 * @package     de.sunnyc.wsc.shrinkr
 */
final class ShrinkrUtil
{
    /**
     * Validates a given URL for use as target URL.
     * Checks length, format, HTTPS requirement, blacklist, and forwarding chains.
     *
     * @param   string  $url    The URL to validate
     * @return  bool    True if valid
     * @throws  UserInputException  If URL is invalid
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
     * Validates a given hash for use as short link identifier.
     * Checks length, pattern match, and uniqueness.
     *
     * @param   string          $hash   The hash to validate
     * @param   ShrinkrLink|null $link  Optional existing link for update validation
     * @return  bool            True if valid
     * @throws  UserInputException  If hash is invalid or already exists
     */
    public static function isValidHash(string $hash, ShrinkrLink $link = null): bool
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
        if ($linkID && ($link == null || $linkID != $link->linkID)) {
            throw new UserInputException('hash', 'alreadyExists');
        }

        return true;
    }

    /**
     * Creates a new shortened link (API for third-party integrations).
     * Validates URL and hash, generates hash if not provided.
     *
     * @param   string  $url        Target URL to shorten
     * @param   string  $hash       Custom hash (optional, auto-generated if empty)
     * @param   string  $prefix     Hash prefix (optional)
     * @return  ShrinkrLink         The created link object
     * @throws  UrlException        If URL is invalid
     * @throws  HashException       If hash is invalid or not unique
     */
    public static function add(string $url, string $hash = '', string $prefix = ''): ShrinkrLink
    {
        // Validate URL
        try {
            self::isValidUrl($url);
        } catch(UserInputException $e) {
            throw new UrlException('Url is not valid.');
        }
        
        // Get or generate hash
        if (empty($hash)) {
            $hash = self::generateHash($prefix);
        } else {
            $hash = $prefix . $hash;

            try {
                self::isValidHash($hash);
            } catch(UserInputException $e) {
                throw new HashException('Hash is not valid.');
            }

            if (ShrinkrLink::getLinkByHash($hash)->linkID) {
                throw new HashException('Hash is not unique.');
            }
        }

        return ShrinkrLinkEditor::create([
            'url' => $url,
            'hash' => $hash,
        ]);
    }

    /**
     * Generates a unique random hash.
     * Uses cryptographically secure random bytes for hash generation.
     *
     * @param   string  $prefix         Hash prefix (uses SHRINKR_HASH_PREFIX if empty)
     * @param   int     $length         Hash length (uses SHRINKR_HASH_LENGTH if 0)
     * @param   int     $maxAttempts    Maximum generation attempts before throwing exception
     * @return  string                  The generated unique hash with prefix
     * @throws  HashException           If maximum attempts reached without unique hash
     */
    public static function generateHash(string $prefix = '', int $length = 0, int $maxAttempts = 10): string
    {
        $unique = false;
        $numberOfAttempts = 0;

        // Use default values if parameters are invalid
        if (empty($prefix)) {
            $prefix = SHRINKR_HASH_PREFIX;
        }
        if ($length == 0 || ($length + \mb_strlen($prefix)) > 64) {
            $length = SHRINKR_HASH_LENGTH;
        }
        if (($length + \mb_strlen($prefix)) > 64) {
            $prefix = '';
        }

        // Generate unique hash
        while (!$unique) {
            $numberOfAttempts++;

            $hashBytes = \random_bytes(\ceil($length / 2));
            $hash = \substr(\bin2hex($hashBytes), 0, $length);

            if (!ShrinkrLink::getLinkByHash($hash)->linkID) {
                $unique = true;
            }

            if ($numberOfAttempts >= $maxAttempts) {
                throw new HashException('Hash could not be generated, because the maximum number of attempts was reached (maximum attempts: ' . $maxAttempts . ')');
            }
        }

        return $prefix . $hash;
    }

    /**
     * Prevents instantiation of utility class.
     */
    private function __construct()
    {
        // Utility class, no instances allowed
    }
}
