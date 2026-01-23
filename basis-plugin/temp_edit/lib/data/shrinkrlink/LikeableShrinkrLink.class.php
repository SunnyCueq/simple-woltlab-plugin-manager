<?php

namespace shrinkr\data\shrinkrlink;

use wcf\data\DatabaseObjectDecorator;
use wcf\data\like\object\ILikeObject;
use wcf\data\object\type\ObjectType;

/**
 * Likeable object implementation for ShrinkrLink.
 * 
 * Decorator class that makes ShrinkrLink objects compatible with WoltLab's reaction system.
 * Implements ILikeObject interface to enable reactions (likes) on shortened links.
 * Since ShrinkrLink objects don't have owners, notifications are not sent.
 *
 * @author      Sunny C
 * @copyright   2026 Sunny C
 * @license     License for Commercial Plugins
 * @link        https://sunnyc.de
 * @package     de.sunnyc.wsc.shrinkr
 * @subpackage  data.shrinkrlink
 *
 * @method  ShrinkrLink getDecoratedObject()
 * @mixin   ShrinkrLink
 */
class LikeableShrinkrLink extends DatabaseObjectDecorator implements ILikeObject
{
    /**
     * Base class name for this decorator.
     *
     * @var    string
     */
    protected static $baseClass = ShrinkrLink::class;

    /**
     * Object type instance for this likeable object.
     *
     * @var    ObjectType
     */
    protected $objectType;

    /**
     * Returns the title of the likeable object.
     * 
     * Delegates to the decorated ShrinkrLink object's getTitle() method.
     *
     * @return  string  The link hash (used as title)
     */
    public function getTitle(): string
    {
        return $this->getDecoratedObject()->getTitle();
    }

    /**
     * Returns the URL of the likeable object.
     * 
     * Returns the shortened URL of the link.
     *
     * @return  string  The shortened URL
     */
    public function getURL()
    {
        return $this->getDecoratedObject()->getShortedUrl();
    }

    /**
     * Returns the user ID of the object owner.
     * 
     * ShrinkrLink objects don't have owners, so this always returns null.
     *
     * @return  null    Always null (no owner)
     */
    public function getUserID()
    {
        return null;
    }

    /**
     * Returns the object ID.
     * 
     * Returns the linkID of the decorated ShrinkrLink object.
     *
     * @return  int     The link ID
     */
    public function getObjectID()
    {
        return $this->linkID;
    }

    /**
     * Updates the like counter.
     * 
     * ShrinkrLink doesn't store cumulative likes in the database, so this
     * method does nothing. Likes are calculated dynamically from the reaction system.
     *
     * @param   int     $cumulativeLikes  The cumulative likes count (ignored)
     * @return  void
     */
    public function updateLikeCounter($cumulativeLikes)
    {
    }

    /**
     * Returns the object type instance.
     *
     * @return  ObjectType  The object type instance
     */
    public function getObjectType()
    {
        return $this->objectType;
    }

    /**
     * Sets the object type instance.
     *
     * @param   ObjectType  $objectType  The object type instance to set
     * @return  void
     */
    public function setObjectType(ObjectType $objectType)
    {
        $this->objectType = $objectType;
    }

    /**
     * Sends a notification when the object is liked.
     * 
     * ShrinkrLink objects don't have owners, so no notifications are sent.
     *
     * @param   \wcf\data\like\Like  $like  The like object (ignored)
     * @return  void
     */
    public function sendNotification(\wcf\data\like\Like $like)
    {
    }

    /**
     * Returns the language ID of the object.
     * 
     * ShrinkrLink objects are language-independent, so this returns null.
     *
     * @return  null    Always null (no language)
     */
    public function getLanguageID()
    {
        return null;
    }
}
