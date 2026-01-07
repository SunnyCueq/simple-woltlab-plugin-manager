<?php

namespace urlshort\data\featuredlink;

use wcf\data\AbstractDatabaseObjectAction;
use wcf\data\ISortableAction;
use wcf\system\exception\PermissionDeniedException;
use wcf\system\exception\UserInputException;
use wcf\system\WCF;

/**
 * Executes featured link-related actions.
 *
 * @author      Sunny C. <https://benjaro.info>
 * @copyright   2022-2025 Benjaro
 * @license     License for Commercial Plugins <https://benjaro.info>
 *
 * @package    dev.tkirch.wsc.urlshort
 * @subpackage data.featuredlink
 *
 * @method FeaturedLink       create()
 * @method FeaturedLinkEditor[]   getObjects()
 * @method FeaturedLinkEditor getSingleObject()
 */
class FeaturedLinkAction extends AbstractDatabaseObjectAction implements ISortableAction
{
    /**
     * @inheritDoc
     */
    protected $permissionsCreate = ['admin.urlshort.canManageFeaturedLinks'];

    /**
     * @inheritDoc
     */
    protected $permissionsUpdate = ['admin.urlshort.canManageFeaturedLinks'];

    /**
     * @inheritDoc
     */
    protected $permissionsDelete = ['admin.urlshort.canManageFeaturedLinks'];

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
        if (!WCF::getSession()->getPermission('admin.urlshort.canManageFeaturedLinks')) {
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
        $sql = "UPDATE  urlshort1_featured_link
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
