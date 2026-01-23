<?php

namespace shrinkr\data\custombutton;

use wcf\data\AbstractDatabaseObjectAction;
use wcf\data\ISortableAction;
use wcf\system\exception\PermissionDeniedException;
use wcf\system\exception\UserInputException;
use wcf\system\WCF;

/**
 * Executes custom button-related actions.
 * 
 * Action class for performing operations on CustomButton database objects.
 * Handles AJAX requests for custom button management and implements ISortableAction
 * for drag-and-drop sorting functionality.
 *
 * @author      Sunny C
 * @copyright   2026 Sunny C
 * @license     License for Commercial Plugins
 * @link        https://sunnyc.de
 * @package     de.sunnyc.wsc.shrinkr
 * @subpackage  data.custombutton
 *
 * @method CustomButton       create()
 * @method CustomButtonEditor[]   getObjects()
 * @method CustomButtonEditor getSingleObject()
 */
class CustomButtonAction extends AbstractDatabaseObjectAction implements ISortableAction
{
    /**
     * Required permissions for create action.
     *
     * @var    string[]
     */
    protected $permissionsCreate = ['admin.shrinkr.canManageCustomButtons'];

    /**
     * Required permissions for update action.
     *
     * @var    string[]
     */
    protected $permissionsUpdate = ['admin.shrinkr.canManageCustomButtons'];

    /**
     * Required permissions for delete action.
     *
     * @var    string[]
     */
    protected $permissionsDelete = ['admin.shrinkr.canManageCustomButtons'];

    /**
     * Actions that require ACP access.
     *
     * @var    string[]
     */
    protected $requireACP = ['create', 'update', 'delete', 'updatePosition'];

    /**
     * Validates the updatePosition action for drag-and-drop sorting.
     * 
     * Implements ISortableAction interface. Validates that objects can be sorted.
     *
     * @return  void
     * @throws  PermissionDeniedException  If user lacks permission
     * @throws  UserInputException          If input is invalid
     */
    public function validateUpdatePosition()
    {
        // Check ACP permission
        if (!WCF::getSession()->getPermission('admin.shrinkr.canManageCustomButtons')) {
            throw new PermissionDeniedException();
        }

        // Validate structure parameter
        if (!isset($this->parameters['data']['structure']) || !is_array($this->parameters['data']['structure'])) {
            throw new UserInputException('structure');
        }

        // Validate offset parameter
        if (!isset($this->parameters['data']['offset']) || !is_numeric($this->parameters['data']['offset'])) {
            throw new UserInputException('offset');
        }
    }

    /**
     * @inheritDoc
     */
    public function updatePosition()
    {
        $structure = $this->parameters['data']['structure'];
        $offset = intval($this->parameters['data']['offset']);

        // Update sortOrder for each custom button
        $sql = "UPDATE  shrinkr1_custom_button
                SET     sortOrder = ?
                WHERE   customButtonID = ?";
        $statement = WCF::getDB()->prepareStatement($sql);

        WCF::getDB()->beginTransaction();
        foreach ($structure[0] as $position => $customButtonID) {
            $statement->execute([
                $offset + $position + 1,
                $customButtonID
            ]);
        }
        WCF::getDB()->commitTransaction();
    }
}

