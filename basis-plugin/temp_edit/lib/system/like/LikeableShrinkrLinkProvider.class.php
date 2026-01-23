<?php

namespace shrinkr\system\like;

use shrinkr\data\shrinkrlink\LikeableShrinkrLink;
use shrinkr\data\shrinkrlink\ShrinkrLink;
use shrinkr\data\shrinkrlink\ShrinkrLinkList;
use wcf\data\like\ILikeObjectTypeProvider;
use wcf\data\like\object\ILikeObject;
use wcf\data\object\type\AbstractObjectTypeProvider;

/**
 * Like Object type provider for ShrinkrLink objects.
 * 
 * Provides ShrinkrLink objects to the WoltLab reaction system. Implements
 * ILikeObjectTypeProvider to enable reactions on shortened links. Checks
 * permissions to ensure only existing links can be reacted to.
 *
 * @author      Sunny C
 * @copyright   2026 Sunny C
 * @license     License for Commercial Plugins
 * @link        https://sunnyc.de
 * @package     de.sunnyc.wsc.shrinkr
 * @subpackage  system.like
 *
 * @method  LikeableShrinkrLink     getObjectByID($objectID)
 * @method  LikeableShrinkrLink[]   getObjectsByIDs(array $objectIDs)
 */
class LikeableShrinkrLinkProvider extends AbstractObjectTypeProvider implements ILikeObjectTypeProvider
{
    /**
     * Class name of the database objects.
     *
     * @var    string
     */
    public $className = ShrinkrLink::class;

    /**
     * Class name of the database object list.
     *
     * @var    string
     */
    public $listClassName = ShrinkrLinkList::class;

    /**
     * Class name of the decorator for likeable objects.
     *
     * @var    string
     */
    public $decoratorClassName = LikeableShrinkrLink::class;

    /**
     * Checks if the given object can be reacted to.
     * 
     * Everyone can react on links as long as the link exists (linkID > 0).
     * No additional permission checks are performed.
     *
     * @param   ILikeObject  $object  The likeable object to check
     * @return  bool                   True if the object can be reacted to
     */
    public function checkPermissions(ILikeObject $object)
    {
        /** @var LikeableShrinkrLink $object */
        return $object->linkID > 0;
    }
}
