<?php

namespace shrinkr\acp\page;

use shrinkr\data\theme\ThemeList;
use wcf\page\MultipleLinkPage;
use wcf\system\WCF;

/**
 * ACP page for listing all themes.
 * 
 * Provides a sortable list of themes with filtering capabilities. Supports
 * filtering by title, identifier, and active status. Used for managing
 * theme configurations for special events.
 *
 * @author      Sunny C
 * @copyright   2026 Sunny C
 * @license     License for Commercial Plugins
 * @link        https://sunnyc.de
 * @package     de.sunnyc.wsc.shrinkr
 * @subpackage  acp.page
 */

class ThemeListPage extends MultipleLinkPage
{
    /**
     * Class name of the database object list.
     *
     * @var    string
     */
    public $objectListClassName = ThemeList::class;

    /**
     * Default sort field.
     *
     * @var    string
     */
    public $sortField = 'sortOrder';

    /**
     * Default sort order.
     *
     * @var    string
     */
    public $sortOrder = 'ASC';

    /**
     * Valid sort fields for the list.
     *
     * @var    string[]
     */
    public $validSortFields = ['themeID', 'identifier', 'title', 'sortOrder', 'isActive'];

    /**
     * Active menu item identifier for navigation highlighting.
     *
     * @var    string
     */
    public $activeMenuItem = 'shrinkr.acp.menu.link.theme.list';

    /**
     * Required permissions to access this page.
     *
     * @var    string[]
     */
    public $neededPermissions = ['admin.shrinkr.canManageThemes'];

    /**
     * Filter: Title to search for.
     *
     * @var    string
     */
    public $title;

    /**
     * Filter: Theme identifier to filter by.
     *
     * @var    string
     */
    public $identifier;

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
        if (isset($_REQUEST['identifier'])) {
            $this->identifier = $_REQUEST['identifier'];
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

        // Add identifier filter
        if ($this->identifier) {
            $conditions[] = 'identifier LIKE ?';
            $parameters[] = '%' . $this->identifier . '%';
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
            'title' => $this->title,
            'identifier' => $this->identifier,
            'isActiveFilter' => $this->isActive,
            'sortField' => $this->sortField,
            'sortOrder' => $this->sortOrder,
            'acpPageSubMenuCategoryList' => 'shrinkr.acp.menu.link.theme.list',
        ]);
    }
}

