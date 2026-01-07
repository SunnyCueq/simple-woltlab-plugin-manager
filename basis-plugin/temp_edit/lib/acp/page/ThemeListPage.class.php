<?php

namespace shrinkr\acp\page;

use shrinkr\data\theme\ThemeList;
use wcf\page\MultipleLinkPage;
use wcf\system\WCF;

/**
 * ACP page for listing all themes.
 *
 * @author      Sunny C. <https://benjaro.info>
 * @copyright   2026 Sunny C
 * @license     License for Commercial Plugins <https://benjaro.info>
 *
 * @package    de.sunnyc.wsc.shrinkr
 * @subpackage acp.page
 */

class ThemeListPage extends MultipleLinkPage
{
    /**
     * @inheritDoc
     */
    public $objectListClassName = ThemeList::class;

    /**
     * @inheritDoc
     */
    public $sortField = 'sortOrder';

    /**
     * @inheritDoc
     */
    public $sortOrder = 'ASC';

    /**
     * @inheritDoc
     */
    public $validSortFields = ['themeID', 'identifier', 'title', 'sortOrder', 'isActive'];

    /**
     * @inheritDoc
     */
    public $activeMenuItem = 'shrinkr.acp.menu.link.theme.list';

    /**
     * @inheritDoc
     */
    public $neededPermissions = ['admin.shrinkr.canManageThemes'];

    /**
     * Filter: Title
     */
    public $title;

    /**
     * Filter: Identifier
     */
    public $identifier;

    /**
     * Filter: Active state
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
        ]);
    }
}

