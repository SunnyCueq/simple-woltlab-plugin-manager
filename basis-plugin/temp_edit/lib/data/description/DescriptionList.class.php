<?php

namespace urlshort\data\description;

use wcf\data\DatabaseObjectList;

/**
 * Represents a list of descriptions.
 *
 * @author      Sunny C. <https://benjaro.info>
 * @copyright   2022-2025 Benjaro
 * @license     License for Commercial Plugins <https://benjaro.info>
 *
 * @package    dev.tkirch.wsc.urlshort
 * @subpackage data.description
 *
 * @method Description       current()
 * @method Description[]     getObjects()
 * @method Description|null  getSingleObject()
 * @method Description|null  search($objectID)
 * @property Description[] $objects
 */
class DescriptionList extends DatabaseObjectList
{
    /**
     * @inheritDoc
     */
    public $className = Description::class;
}
