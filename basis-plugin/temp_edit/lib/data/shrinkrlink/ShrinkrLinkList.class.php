<?php

/**
 * Represents a list of ShrinkrLink objects.
 *
 * @author      Sunny C
 * @copyright   2026 Sunny C
 * @license     License for Commercial Plugins
 * @link        https://sunnyc.de
 *
 * @package     de.sunnyc.wsc.shrinkr
 */

namespace shrinkr\data\shrinkrlink;

use wcf\data\DatabaseObjectList;

/**
 * List class for ShrinkrLink database objects.
 */
class ShrinkrLinkList extends DatabaseObjectList
{
    /**
     * @inheritDoc
     */
    public $className = ShrinkrLink::class;
}
