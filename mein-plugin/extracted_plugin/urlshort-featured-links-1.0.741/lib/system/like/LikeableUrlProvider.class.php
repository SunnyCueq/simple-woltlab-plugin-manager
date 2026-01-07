<?php

namespace urlshort\system\like;

use urlshort\data\url\LikeableUrl;
use urlshort\data\url\Url;
use urlshort\data\url\UrlList;
use wcf\data\like\ILikeObjectTypeProvider;
use wcf\data\like\object\ILikeObject;
use wcf\data\object\type\AbstractObjectTypeProvider;

/**
 * Like Object type provider for URL shortener URLs.
 *
 * @author      Sunny C. <https://benjaro.info>
 * @copyright   2022-2025 Benjaro
 * @license     License for Commercial Plugins <https://benjaro.info>
 *
 * @package    info.benjaro.urlshort.affiliate
 * @subpackage system.like
 *
 * @method  LikeableUrl     getObjectByID($objectID)
 * @method  LikeableUrl[]   getObjectsByIDs(array $objectIDs)
 */
class LikeableUrlProvider extends AbstractObjectTypeProvider implements ILikeObjectTypeProvider
{
    /**
     * @inheritDoc
     */
    public $className = Url::class;

    /**
     * @inheritDoc
     */
    public $listClassName = UrlList::class;

    /**
     * @inheritDoc
     */
    public $decoratorClassName = LikeableUrl::class;

    /**
     * @inheritDoc
     */
    public function checkPermissions(ILikeObject $object)
    {
        /** @var LikeableUrl $object */
        // Jeder kann auf URLs reagieren, solange die URL existiert
        return $object->urlID > 0;
    }
}

