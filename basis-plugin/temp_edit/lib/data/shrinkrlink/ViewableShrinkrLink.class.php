<?php

/**
 * Viewable ShrinkrLink wrapper used for moderation queue integration.
 *
 * @author      Sunny C
 * @copyright   2026 Sunny C
 * @license     License for Commercial Plugins
 * @link        https://sunnyc.de
 *
 * @package     de.sunnyc.wsc.shrinkr
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
     * @inheritDoc
     */
    protected static $baseClass = ShrinkrLink::class;

    /**
     * User profile object
     * @var UserProfile|null
     */
    protected ?UserProfile $userProfile = null;

    /**
     * @inheritDoc
     */
    public function getTime(): int
    {
        return (int) ($this->time ?? 0);
    }

    /**
     * @inheritDoc
     */
    public function getUserID(): int
    {
        return (int) ($this->userID ?? 0);
    }

    /**
     * @inheritDoc
     */
    public function getUsername(): string
    {
        return (string) ($this->username ?? '');
    }

    /**
     * Returns the user profile object.
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
     * @inheritDoc
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
     * @inheritDoc
     */
    public function getLink(): string
    {
        return $this->getDecoratedObject()->getShortedUrl();
    }
}
