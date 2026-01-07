<?php

namespace urlshort\data\description;

use wcf\data\AbstractDatabaseObjectAction;

/**
 * Executes description-related actions.
 *
 * @author      Sunny C. <https://benjaro.info>
 * @copyright   2022-2025 Benjaro
 * @license     License for Commercial Plugins <https://benjaro.info>
 *
 * @package    dev.tkirch.wsc.urlshort
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
    protected $permissionsCreate = ['admin.urlshort.canManageDescriptions'];

    /**
     * @inheritDoc
     */
    protected $permissionsUpdate = ['admin.urlshort.canManageDescriptions'];

    /**
     * @inheritDoc
     */
    protected $permissionsDelete = ['admin.urlshort.canManageDescriptions'];

    /**
     * @inheritDoc
     */
    protected $requireACP = ['create', 'update', 'delete'];
}
