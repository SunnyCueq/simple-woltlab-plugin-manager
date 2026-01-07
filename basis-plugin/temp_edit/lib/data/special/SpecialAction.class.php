<?php

namespace urlshort\data\special;

use wcf\data\AbstractDatabaseObjectAction;

/**
 * Executes special-related actions.
 *
 * @author      Sunny C. <https://benjaro.info>
 * @copyright   2022-2025 Benjaro
 * @license     License for Commercial Plugins <https://benjaro.info>
 *
 * @package    dev.tkirch.wsc.urlshort
 * @subpackage data.special
 *
 * @method      SpecialEditor[]    getObjects()
 * @method      SpecialEditor      getSingleObject()
 */
class SpecialAction extends AbstractDatabaseObjectAction
{
    /**
     * @inheritDoc
     */
    protected $className = SpecialEditor::class;

    /**
     * @inheritDoc
     */
    protected $permissionsCreate = ['admin.urlshort.canManageSpecials'];

    /**
     * @inheritDoc
     */
    protected $permissionsDelete = ['admin.urlshort.canManageSpecials'];

    /**
     * @inheritDoc
     */
    protected $permissionsUpdate = ['admin.urlshort.canManageSpecials'];

    /**
     * @inheritDoc
     */
    protected $requireACP = ['create', 'delete', 'update'];
}

