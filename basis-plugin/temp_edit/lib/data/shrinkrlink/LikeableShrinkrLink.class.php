<?php

namespace shrinkr\data\shrinkrlink;

use wcf\data\DatabaseObjectDecorator;
use wcf\data\like\object\ILikeObject;
use wcf\data\object\type\ObjectType;

/**
 * Likeable object implementation for ShrinkrLink.
 *
 * @author      Sunny C
 * @copyright   2026 Sunny C
 * @license     License for Commercial Plugins
 *
 * @package    de.sunnyc.wsc.shrinkr
 * @subpackage data.shrinkrlink
 *
 * @method  ShrinkrLink getDecoratedObject()
 * @mixin   ShrinkrLink
 */
class LikeableShrinkrLink extends DatabaseObjectDecorator implements ILikeObject
{
    /**
     * @inheritDoc
     */
    protected static $baseClass = ShrinkrLink::class;

    /**
     * object type
     * @var ObjectType
     */
    protected $objectType;

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
        // ShrinkrLink objects don't have a userID
        return null;
    }

    /**
     * @inheritDoc
     */
    public function getObjectID()
    {
        return $this->linkID;
    }

    /**
     * @inheritDoc
     */
    public function updateLikeCounter($cumulativeLikes)
    {
        // ShrinkrLink doesn't store cumulative likes, so we don't need to update anything
    }

    /**
     * @inheritDoc
     */
    public function getObjectType()
    {
        return $this->objectType;
    }

    /**
     * @inheritDoc
     */
    public function setObjectType(ObjectType $objectType)
    {
        $this->objectType = $objectType;
    }

    /**
     * @inheritDoc
     */
    public function sendNotification(\wcf\data\like\Like $like)
    {
        // ShrinkrLink objects don't have owners, so no notifications needed
    }

    /**
     * @inheritDoc
     */
    public function getLanguageID()
    {
        return null;
    }
}
