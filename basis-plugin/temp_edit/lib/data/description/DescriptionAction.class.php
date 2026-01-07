<?php

namespace shrinkr\data\description;

use wcf\data\AbstractDatabaseObjectAction;

/**
 * Executes description-related actions.
 *
 * @author      Sunny C. <https://benjaro.info>
 * @copyright   2026 Sunny C
 * @license     License for Commercial Plugins <https://benjaro.info>
 *
 * @package    de.sunnyc.wsc.shrinkr
 * @subpackage data.description
 *
 * @method Description       create()
 * @method DescriptionEditor[]   getObjects()
 * @method DescriptionEditor getSingleObject()
 */
class DescriptionAction extends AbstractDatabaseObjectAction
{
    /**
     * @inheritDoc
     */
    protected $permissionsCreate = ['admin.shrinkr.canManageDescriptions'];

    /**
     * @inheritDoc
     */
    protected $permissionsUpdate = ['admin.shrinkr.canManageDescriptions'];

    /**
     * @inheritDoc
     */
    protected $permissionsDelete = ['admin.shrinkr.canManageDescriptions'];

    /**
     * @inheritDoc
     */
    protected $requireACP = ['create', 'update', 'delete'];
}
