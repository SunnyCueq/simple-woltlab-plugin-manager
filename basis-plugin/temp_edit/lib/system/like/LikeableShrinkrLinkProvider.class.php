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
 * @author      Sunny C
 * @copyright   2026 Sunny C
 * @license     License for Commercial Plugins
 *
 * @package    de.sunnyc.wsc.shrinkr
 * @subpackage system.like
 *
 * @method  LikeableShrinkrLink     getObjectByID($objectID)
 * @method  LikeableShrinkrLink[]   getObjectsByIDs(array $objectIDs)
 */
class LikeableShrinkrLinkProvider extends AbstractObjectTypeProvider implements ILikeObjectTypeProvider
{
    /**
     * @inheritDoc
     */
    public $className = ShrinkrLink::class;

    /**
     * @inheritDoc
     */
    public $listClassName = ShrinkrLinkList::class;

    /**
     * @inheritDoc
     */
    public $decoratorClassName = LikeableShrinkrLink::class;

    /**
     * @inheritDoc
     */
    public function checkPermissions(ILikeObject $object)
    {
        /** @var LikeableShrinkrLink $object */
        // Everyone can react on links, as long as the link exists
        return $object->linkID > 0;
    }
}
