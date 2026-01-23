<?php

namespace shrinkr\data\description;

use wcf\data\AbstractDatabaseObjectAction;

/**
 * Executes description-related actions.
 * 
 * Action class for performing operations on Description database objects.
 * Handles AJAX requests for description management, including toggle functionality
 * to activate/deactivate descriptions.
 *
 * @author      Sunny C
 * @copyright   2026 Sunny C
 * @license     License for Commercial Plugins
 * @link        https://sunnyc.de
 * @package     de.sunnyc.wsc.shrinkr
 * @subpackage  data.description
 *
 * @method Description       create()
 * @method DescriptionEditor[]   getObjects()
 * @method DescriptionEditor getSingleObject()
 */
class DescriptionAction extends AbstractDatabaseObjectAction
{
    /**
     * Required permissions for create action.
     *
     * @var    string[]
     */
    protected $permissionsCreate = ['admin.shrinkr.canManageDescriptions'];

    /**
     * Required permissions for update action.
     *
     * @var    string[]
     */
    protected $permissionsUpdate = ['admin.shrinkr.canManageDescriptions'];

    /**
     * Required permissions for delete action.
     *
     * @var    string[]
     */
    protected $permissionsDelete = ['admin.shrinkr.canManageDescriptions'];

    /**
     * Actions that require ACP access.
     *
     * @var    string[]
     */
    protected $requireACP = ['create', 'update', 'delete', 'toggle'];

    /**
     * Validates the toggle action for activating/deactivating descriptions.
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
     * Toggles the active state of descriptions.
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
