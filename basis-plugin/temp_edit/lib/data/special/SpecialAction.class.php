<?php

namespace shrinkr\data\special;

use wcf\data\AbstractDatabaseObjectAction;

/**
 * Executes special-related actions.
 * 
 * Action class for performing operations on Special database objects.
 * Handles AJAX requests for special event management, including toggle
 * functionality to activate/deactivate specials.
 *
 * @author      Sunny C
 * @copyright   2026 Sunny C
 * @license     License for Commercial Plugins
 * @link        https://sunnyc.de
 * @package     de.sunnyc.wsc.shrinkr
 * @subpackage  data.special
 *
 * @method      SpecialEditor[]    getObjects()
 * @method      SpecialEditor      getSingleObject()
 */
class SpecialAction extends AbstractDatabaseObjectAction
{
    /**
     * Editor class name for specials.
     *
     * @var    string
     */
    protected $className = SpecialEditor::class;

    /**
     * Required permissions for create action.
     *
     * @var    string[]
     */
    protected $permissionsCreate = ['admin.shrinkr.canManageSpecials'];

    /**
     * Required permissions for delete action.
     *
     * @var    string[]
     */
    protected $permissionsDelete = ['admin.shrinkr.canManageSpecials'];

    /**
     * Required permissions for update action.
     *
     * @var    string[]
     */
    protected $permissionsUpdate = ['admin.shrinkr.canManageSpecials'];

    /**
     * Actions that require ACP access.
     *
     * @var    string[]
     */
    protected $requireACP = ['create', 'delete', 'update', 'toggle'];

    /**
     * Validates the toggle action for activating/deactivating specials.
     * 
     * Uses the same validation as update action.
     *
     * @return  void
     * @throws  PermissionDeniedException  If user lacks permission
     * @throws  UserInputException          If input is invalid
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

