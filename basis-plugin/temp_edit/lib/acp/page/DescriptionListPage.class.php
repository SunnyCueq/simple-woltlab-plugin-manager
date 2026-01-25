<?php

namespace shrinkr\acp\page;

use shrinkr\data\description\DescriptionList;
use shrinkr\util\ShrinkrUtil;
use wcf\page\MultipleLinkPage;
use wcf\system\WCF;

/**
 * ACP page for listing all descriptions.
 * 
 * Provides a sortable list of description texts with filtering capabilities.
 * Supports filtering by title, description text, and active status. Used for
 * managing description texts displayed on shortened link redirect pages.
 *
 * @author      Sunny C
 * @copyright   2026 Sunny C
 * @license     License for Commercial Plugins
 * @link        https://sunnyc.de
 * @package     de.sunnyc.wsc.shrinkr
 * @subpackage  acp.page
 *
 * @property   DescriptionList $objectList  The database object list instance
 */
class DescriptionListPage extends MultipleLinkPage
{
    /**
     * Class name of the database object list.
     *
     * @var    string
     */
    public $objectListClassName = DescriptionList::class;

    /**
     * Default sort field.
     *
     * @var    string
     */
    public $sortField = 'descriptionID';

    /**
     * Default sort order.
     *
     * @var    string
     */
    public $sortOrder = 'ASC';

    /**
     * Active menu item identifier for navigation highlighting.
     *
     * @var    string
     */
    public $activeMenuItem = 'shrinkr.acp.menu.link.description.list';

    /**
     * Required permissions to access this page.
     *
     * @var    string[]
     */
    public $neededPermissions = ['admin.shrinkr.canManageDescriptions'];

    /**
     * Valid sort fields for the list.
     *
     * @var    string[]
     */
    public $validSortFields = ['descriptionID', 'title', 'isActive'];

    /**
     * Filter: Title to search for.
     *
     * @var    string
     */
    public $title;

    /**
     * Filter: Description text to search for.
     *
     * @var    string
     */
    public $descriptionText;

    /**
     * Filter: Active status (1 = active, 0 = inactive).
     *
     * @var    int
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

        // Get menu badge text
        $menuBadgeText = ShrinkrUtil::getMenuBadgeText();

        // Calculate object count
        $objectCount = $this->objectList->countObjects();

        WCF::getTPL()->assign([
            'sortField' => $this->sortField,
            'sortOrder' => $this->sortOrder,
            'title' => $this->title,
            'descriptionText' => $this->descriptionText,
            'isActiveFilter' => $this->isActive,
            'acpPageSubMenuCategoryList' => 'shrinkr.acp.menu.link.description.list',
            'menuBadgeText' => $menuBadgeText,
            'objectCount' => $objectCount,
        ]);
    }
}
