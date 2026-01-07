<?php

namespace urlshort\data\url;

use wcf\data\DatabaseObjectDecorator;
use wcf\data\IUserContent;
use wcf\data\user\User;
use wcf\data\user\UserProfile;
use wcf\system\cache\runtime\UserProfileRuntimeCache;

/**
 * Viewable URL wrapper used for moderation queue integration.
 *
 * @author      Sunny C. <https://benjaro.info>
 * @copyright   2022-2025 Benjaro
 * @license     License for Commercial Plugins <https://benjaro.info>
 *
 * @package    dev.tkirch.wsc.urlshort
 * @subpackage data.url
 */
class ViewableUrl extends DatabaseObjectDecorator implements IUserContent
{
    /**
     * @inheritDoc
     */
    protected static $baseClass = Url::class;

    /**
     * user profile object
     * @var UserProfile
     */
    protected $userProfile;

    /**
     * @inheritDoc
     */
    public function getTime()
    {
        return (int) ($this->time ?? 0);
    }

    /**
     * @inheritDoc
     */
    public function getUserID()
    {
        return (int) ($this->userID ?? 0);
    }

    /**
     * @inheritDoc
     */
    public function getUsername()
    {
        return (string) ($this->username ?? '');
    }

    /**
     * Returns the user profile object.
     *
     * @return  UserProfile
     */
    public function getUserProfile()
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
        if (!empty($this->urlTitle)) {
            return $this->urlTitle;
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

