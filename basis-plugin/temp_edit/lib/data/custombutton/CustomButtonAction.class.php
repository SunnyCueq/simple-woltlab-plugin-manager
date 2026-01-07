<?php

namespace urlshort\data\custombutton;

use wcf\data\AbstractDatabaseObjectAction;
use wcf\data\ISortableAction;
use wcf\system\exception\PermissionDeniedException;
use wcf\system\exception\UserInputException;
use wcf\system\WCF;

/**
 * Executes custom button-related actions.
 *
 * @author      Sunny C. <https://benjaro.info>
 * @copyright   2022-2025 Benjaro
 * @license     License for Commercial Plugins <https://benjaro.info>
 *
 * @package    dev.tkirch.wsc.urlshort
 * @subpackage data.custombutton
 *
 * @method CustomButton       create()
 * @method CustomButtonEditor[]   getObjects()
 * @method CustomButtonEditor getSingleObject()
 */
class CustomButtonAction extends AbstractDatabaseObjectAction implements ISortableAction
{
    /**
     * @inheritDoc
     */
    protected $permissionsCreate = ['admin.urlshort.canManageCustomButtons'];

    /**
     * @inheritDoc
     */
    protected $permissionsUpdate = ['admin.urlshort.canManageCustomButtons'];

    /**
     * @inheritDoc
     */
    protected $permissionsDelete = ['admin.urlshort.canManageCustomButtons'];

    /**
     * @inheritDoc
     */
    protected $requireACP = ['create', 'update', 'delete', 'updatePosition'];

    /**
     * @inheritDoc
     */
    public function validateUpdatePosition()
    {
        // Check ACP permission
        if (!WCF::getSession()->getPermission('admin.urlshort.canManageCustomButtons')) {
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
        $sql = "UPDATE  urlshort1_custom_button
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

