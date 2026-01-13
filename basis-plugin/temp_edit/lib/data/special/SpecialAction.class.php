<?php

namespace shrinkr\data\special;

use wcf\data\AbstractDatabaseObjectAction;

/**
 * Executes special-related actions.
 *
 * @author      Sunny C
 * @copyright   2026 Sunny C
 * @license     License for Commercial Plugins
 *
 * @package    de.sunnyc.wsc.shrinkr
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
    protected $permissionsCreate = ['admin.shrinkr.canManageSpecials'];

    /**
     * @inheritDoc
     */
    protected $permissionsDelete = ['admin.shrinkr.canManageSpecials'];

    /**
     * @inheritDoc
     */
    protected $permissionsUpdate = ['admin.shrinkr.canManageSpecials'];

    /**
     * @inheritDoc
     */
    protected $requireACP = ['create', 'delete', 'update', 'toggle'];

    /**
     * Validates the "toggle" action.
     */
    public function validateToggle()
    {
        $this->validateUpdate();
    }

    /**
     * Toggles the active state of specials.
     */
    public function toggle()
    {
        foreach ($this->getObjects() as $object) {
            $object->update([
                'isActive' => $object->isActive ? 0 : 1,
            ]);
        }
    }
}

