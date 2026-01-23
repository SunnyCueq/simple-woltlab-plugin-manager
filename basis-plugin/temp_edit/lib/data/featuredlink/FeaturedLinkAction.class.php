<?php

namespace shrinkr\data\featuredlink;

use wcf\data\AbstractDatabaseObjectAction;
use wcf\data\ISortableAction;
use wcf\system\exception\PermissionDeniedException;
use wcf\system\exception\UserInputException;
use wcf\system\WCF;

/**
 * Executes featured link-related actions.
 * 
 * Action class for performing operations on FeaturedLink database objects.
 * Handles AJAX requests for featured link management and implements ISortableAction
 * for drag-and-drop sorting functionality.
 *
 * @author      Sunny C
 * @copyright   2026 Sunny C
 * @license     License for Commercial Plugins
 * @link        https://sunnyc.de
 * @package     de.sunnyc.wsc.shrinkr
 * @subpackage  data.featuredlink
 *
 * @method FeaturedLink       create()
 * @method FeaturedLinkEditor[]   getObjects()
 * @method FeaturedLinkEditor getSingleObject()
 */
class FeaturedLinkAction extends AbstractDatabaseObjectAction implements ISortableAction
{
    /**
     * Required permissions for create action.
     *
     * @var    string[]
     */
    protected $permissionsCreate = ['admin.shrinkr.canManageFeaturedLinks'];

    /**
     * Required permissions for update action.
     *
     * @var    string[]
     */
    protected $permissionsUpdate = ['admin.shrinkr.canManageFeaturedLinks'];

    /**
     * Required permissions for delete action.
     *
     * @var    string[]
     */
    protected $permissionsDelete = ['admin.shrinkr.canManageFeaturedLinks'];

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
        if (!WCF::getSession()->getPermission('admin.shrinkr.canManageFeaturedLinks')) {
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

        // Update sortOrder for each featured link
        $sql = "UPDATE  shrinkr1_featured_link
                SET     sortOrder = ?
                WHERE   linkID = ?";
        $statement = WCF::getDB()->prepareStatement($sql);

        WCF::getDB()->beginTransaction();
        foreach ($structure[0] as $position => $linkID) {
            $statement->execute([
                $offset + $position + 1,
                $linkID
            ]);
        }
        WCF::getDB()->commitTransaction();
    }
}
