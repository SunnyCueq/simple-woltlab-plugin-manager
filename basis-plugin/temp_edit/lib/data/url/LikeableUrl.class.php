<?php

namespace urlshort\data\url;

use wcf\data\like\Like;
use wcf\data\like\object\AbstractLikeObject;
use wcf\data\reaction\object\IReactionObject;
use wcf\system\user\notification\object\LikeUserNotificationObject;
use wcf\system\user\notification\UserNotificationHandler;
use wcf\system\WCF;

/**
 * Likeable object implementation for URL shortener URLs.
 *
 * @author      Sunny C. <https://benjaro.info>
 * @copyright   2022-2025 Benjaro
 * @license     License for Commercial Plugins <https://benjaro.info>
 *
 * @package    dev.tkirch.wsc.urlshort
 * @subpackage data.url
 * @subpackage data.url
 *
 * @method  Url getDecoratedObject()
 * @mixin   Url
 */
class LikeableUrl extends AbstractLikeObject implements IReactionObject
{
    /**
     * @inheritDoc
     */
    protected static $baseClass = Url::class;

    /**
     * @inheritDoc
     */
    public function getTitle(): string
    {
        return $this->getDecoratedObject()->getTitle();
    }

    /**
     * @inheritDoc
     */
    public function getURL()
    {
        return $this->getDecoratedObject()->getShortedUrl();
    }

    /**
     * @inheritDoc
     */
    public function getUserID()
    {
        return $this->userID ?? null;
    }

    /**
     * @inheritDoc
     */
    public function getObjectID()
    {
        return $this->urlID;
    }

    /**
     * @inheritDoc
     */
    public function updateLikeCounter($cumulativeLikes)
    {
        // Optional: Store cumulativeLikes in database if needed
    }

    /**
     * @inheritDoc
     */
    public function getLanguageID()
    {
        return null;
    }

    /**
     * @inheritDoc
     */
    public function sendNotification(Like $like)
    {
        $userID = $this->getUserID();
        if ($userID && $userID != WCF::getUser()->userID) {
            $notificationObject = new LikeUserNotificationObject($like);
            UserNotificationHandler::getInstance()->fireEvent(
                'like',
                'dev.tkirch.wsc.urlshort.likeableUrl.notification',
                $notificationObject,
                [$userID],
                ['objectID' => $this->getObjectID()]
            );
        }
    }
}

