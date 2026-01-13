<?php

namespace shrinkr\acp\page;

use shrinkr\data\description\DescriptionList;
use wcf\page\MultipleLinkPage;
use wcf\system\WCF;

/**
 * ACP page for listing all descriptions.
 *
 * @author      Sunny C
 * @copyright   2026 Sunny C
 * @license     License for Commercial Plugins
 *
 * @package    de.sunnyc.wsc.shrinkr
 * @subpackage acp.page
 *
 * @property DescriptionList $objectList
 */
class DescriptionListPage extends MultipleLinkPage
{
    /**
     * @inheritDoc
     */
    public $objectListClassName = DescriptionList::class;

    /**
     * @inheritDoc
     */
    public $sortField = 'descriptionID';

    /**
     * @inheritDoc
     */
    public $sortOrder = 'ASC';

    /**
     * @inheritDoc
     */
    public $activeMenuItem = 'shrinkr.acp.menu.link.description.list';

    /**
     * @inheritDoc
     */
    public $neededPermissions = ['admin.shrinkr.canManageDescriptions'];

    /**
     * @inheritDoc
     */
    public $validSortFields = ['descriptionID', 'title', 'isActive'];

    /**
     * Filter: Title
     */
    public $title;

    /**
     * Filter: Description Text
     */
    public $descriptionText;

    /**
     * Filter: Active flag
     */
    public $isActive;

    /**
     * @inheritDoc
     */
    public function readParameters()
    {
        parent::readParameters();

        // Read sort parameters
        if (isset($_REQUEST['sortField']) && in_array($_REQUEST['sortField'], $this->validSortFields)) {
            $this->sortField = $_REQUEST['sortField'];
        }
        if (isset($_REQUEST['sortOrder']) && in_array($_REQUEST['sortOrder'], ['ASC', 'DESC'])) {
            $this->sortOrder = $_REQUEST['sortOrder'];
        }

        // Read filter parameters
        if (isset($_REQUEST['title'])) {
            $this->title = $_REQUEST['title'];
        }
        if (isset($_REQUEST['descriptionText'])) {
            $this->descriptionText = $_REQUEST['descriptionText'];
        }
        if (isset($_REQUEST['isActive']) && $_REQUEST['isActive'] !== '') {
            $this->isActive = (int) $_REQUEST['isActive'];
        }
    }

    /**
     * @inheritDoc
     */
    protected function initObjectList()
    {
        parent::initObjectList();

        // Apply sorting
        if (in_array($this->sortField, $this->validSortFields)) {
            $this->objectList->sqlOrderBy = $this->sortField . ' ' . $this->sortOrder;
        }

        $conditions = [];
        $parameters = [];

        // Add title filter
        if ($this->title) {
            $conditions[] = 'title LIKE ?';
            $parameters[] = '%' . $this->title . '%';
        }

        // Add descriptionText filter
        if ($this->descriptionText) {
            $conditions[] = 'descriptionText LIKE ?';
            $parameters[] = '%' . $this->descriptionText . '%';
        }

        if ($this->isActive !== null) {
            $conditions[] = 'isActive = ?';
            $parameters[] = $this->isActive;
        }

        // Apply filters
        if (!empty($conditions)) {
            $this->objectList->getConditionBuilder()->add(
                implode(' AND ', $conditions),
                $parameters
            );
        }
    }

    /**
     * @inheritDoc
     */
    public function assignVariables()
    {
        parent::assignVariables();

        WCF::getTPL()->assign([
            'sortField' => $this->sortField,
            'sortOrder' => $this->sortOrder,
            'title' => $this->title,
            'descriptionText' => $this->descriptionText,
            'isActiveFilter' => $this->isActive,
            'acpPageSubMenuCategoryList' => 'shrinkr.acp.menu.link.description.list',
        ]);
    }
}
