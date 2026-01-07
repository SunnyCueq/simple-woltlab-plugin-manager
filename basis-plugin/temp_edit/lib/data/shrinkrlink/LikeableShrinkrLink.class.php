<?php

/**
 * Likeable object implementation for Shr1nkr links.
 *
 * @author      Sunny C
 * @copyright   2026 Sunny C
 * @license     License for Commercial Plugins
 * @link        https://sunnyc.de
 *
 * @package     de.sunnyc.wsc.shrinkr
 */

namespace shrinkr\data\shrinkrlink;

use wcf\data\like\Like;
use wcf\data\like\object\AbstractLikeObject;
use wcf\data\reaction\object\IReactionObject;
use wcf\system\user\notification\object\LikeUserNotificationObject;
use wcf\system\user\notification\UserNotificationHandler;
use wcf\system\WCF;

/**
 * Likeable object implementation for ShrinkrLink.
 *
 * @method  ShrinkrLink getDecoratedObject()
 * @mixin   ShrinkrLink
 */
class LikeableShrinkrLink extends AbstractLikeObject implements IReactionObject
{
    /**
     * @inheritDoc
     */
    protected static $baseClass = ShrinkrLink::class;

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
    public function getURL(): string
    {
        return $this->getDecoratedObject()->getShortedUrl();
    }

    /**
     * @inheritDoc
     */
    public function getUserID(): ?int
    {
        return $this->userID ?? null;
    }

    /**
     * @inheritDoc
     */
    public function getObjectID(): int
    {
        return $this->linkID;
    }

    /**
     * @inheritDoc
     */
    public function updateLikeCounter($cumulativeLikes): void
    {
        // Optional: Store cumulativeLikes in database if needed
    }

    /**
     * @inheritDoc
     */
    public function getLanguageID(): ?int
    {
        return null;
    }

    /**
     * @inheritDoc
     */
    public function sendNotification(Like $like): void
    {
        $userID = $this->getUserID();
        if ($userID && $userID != WCF::getUser()->userID) {
            $notificationObject = new LikeUserNotificationObject($like);
            UserNotificationHandler::getInstance()->fireEvent(
                'like',
                'de.sunnyc.wsc.shrinkr.likeableShrinkrLink.notification',
                $notificationObject,
                [$userID],
                ['objectID' => $this->getObjectID()]
            );
        }
    }
}
