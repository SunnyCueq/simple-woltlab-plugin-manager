<?php

/**
 * Viewable ShrinkrLink wrapper used for moderation queue integration.
 * 
 * Decorator class that makes ShrinkrLink objects compatible with WoltLab's
 * moderation queue system. Implements IUserContent interface to provide user
 * information, timestamps, and titles for moderation purposes.
 *
 * @author      Sunny C
 * @copyright   2026 Sunny C
 * @license     License for Commercial Plugins
 * @link        https://sunnyc.de
 * @package     de.sunnyc.wsc.shrinkr
 * @subpackage  data.shrinkrlink
 */

namespace shrinkr\data\shrinkrlink;

use wcf\data\DatabaseObjectDecorator;
use wcf\data\IUserContent;
use wcf\data\user\User;
use wcf\data\user\UserProfile;
use wcf\system\cache\runtime\UserProfileRuntimeCache;

/**
 * Viewable ShrinkrLink wrapper for moderation queue.
 */
class ViewableShrinkrLink extends DatabaseObjectDecorator implements IUserContent
{
    /**
     * Base class name for this decorator.
     *
     * @var    string
     */
    protected static $baseClass = ShrinkrLink::class;

    /**
     * Cached user profile object.
     *
     * @var    UserProfile|null
     */
    protected ?UserProfile $userProfile = null;

    /**
     * Returns the creation timestamp of the link.
     * 
     * Implements IUserContent interface. Returns 0 if time is not set.
     *
     * @return  int     The UNIX timestamp (0 if not set)
     */
    public function getTime(): int
    {
        return (int) ($this->time ?? 0);
    }

    /**
     * Returns the user ID of the link creator.
     * 
     * Implements IUserContent interface. Returns 0 if userID is not set.
     *
     * @return  int     The user ID (0 if not set)
     */
    public function getUserID(): int
    {
        return (int) ($this->userID ?? 0);
    }

    /**
     * Returns the username of the link creator.
     * 
     * Implements IUserContent interface. Returns empty string if username is not set.
     *
     * @return  string  The username (empty string if not set)
     */
    public function getUsername(): string
    {
        return (string) ($this->username ?? '');
    }

    /**
     * Returns the user profile object.
     * 
     * Loads the user profile from cache if userID is set, or creates a minimal
     * profile with username if only username is available.
     *
     * @return  UserProfile  The user profile object
     */
    public function getUserProfile(): UserProfile
    {
        if ($this->userProfile === null) {
            if ($this->userID) {
                $this->userProfile = UserProfileRuntimeCache::getInstance()->getObject($this->userID);
            } else {
                $this->userProfile = new UserProfile(new User(null, [
                    'username' => $this->username ?? '',
                ]));
            }
        }

        return $this->userProfile;
    }

    /**
     * Returns the title of the link.
     * 
     * Implements IUserContent interface. Returns linkTitle if set, otherwise
     * autoExtractedTitle, otherwise the hash as fallback.
     *
     * @return  string  The link title
     */
    public function getTitle(): string
    {
        if (!empty($this->linkTitle)) {
            return $this->linkTitle;
        }

        if (!empty($this->autoExtractedTitle)) {
            return $this->autoExtractedTitle;
        }

        return $this->hash;
    }

    /**
     * Returns the URL of the link.
     * 
     * Implements IUserContent interface. Returns the shortened URL.
     *
     * @return  string  The shortened URL
     */
    public function getLink(): string
    {
        return $this->getDecoratedObject()->getShortedUrl();
    }
}
