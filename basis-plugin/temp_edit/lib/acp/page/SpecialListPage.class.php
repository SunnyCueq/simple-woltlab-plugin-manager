<?php

namespace shrinkr\acp\page;

use shrinkr\data\special\SpecialList;
use shrinkr\system\special\SpecialThemeHelper;
use wcf\page\MultipleLinkPage;
use wcf\system\WCF;

/**
 * ACP page for listing all specials.
 *
 * @author      Sunny C
 * @copyright   2026 Sunny C
 * @license     License for Commercial Plugins
 *
 * @package    de.sunnyc.wsc.shrinkr
 * @subpackage acp.page
 */
class SpecialListPage extends MultipleLinkPage
{
    /**
     * @inheritDoc
     */
    public $objectListClassName = SpecialList::class;

    /**
     * @inheritDoc
     */
    public $sortField = 'specialID';

    /**
     * @inheritDoc
     */
    public $sortOrder = 'DESC';

    /**
     * @inheritDoc
     */
    public $activeMenuItem = 'shrinkr.acp.menu.link.special.list';

    /**
     * @inheritDoc
     */
    public $neededPermissions = ['admin.shrinkr.canManageSpecials'];

    /**
     * @inheritDoc
     */
    public $validSortFields = ['specialID', 'title', 'theme', 'startTime', 'endTime', 'isActive'];

    /**
     * Filter: Title
     */
    public $title;

    /**
     * Filter: Theme
     */
    public $theme;

    /**
     * Filter: Active status
     */
    public $isActive;

    /**
     * Filter: Short URL hash / ID
     */
    public $shortUrlQuery;

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

        if (isset($_REQUEST['title'])) {
            $this->title = $_REQUEST['title'];
        }
        if (isset($_REQUEST['theme'])) {
            $this->theme = $_REQUEST['theme'];
        }
        if (isset($_REQUEST['isActive']) && $_REQUEST['isActive'] !== '') {
            $this->isActive = (int) $_REQUEST['isActive'];
        }
        if (isset($_REQUEST['shortUrl'])) {
            $this->shortUrlQuery = trim($_REQUEST['shortUrl']);
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

        if ($this->title) {
            $conditions[] = 'title LIKE ?';
            $parameters[] = '%' . $this->title . '%';
        }

        if ($this->theme) {
            $conditions[] = 'theme = ?';
            $parameters[] = $this->theme;
        }

        if ($this->isActive !== null) {
            $conditions[] = 'isActive = ?';
            $parameters[] = $this->isActive;
        }

        if ($this->shortUrlQuery) {
            if (ctype_digit($this->shortUrlQuery)) {
                $conditions[] = 'linkID = ?';
                $parameters[] = (int) $this->shortUrlQuery;
            } else {
                $conditions[] = 'linkID IN (
                    SELECT linkID
                    FROM shrinkr1_link
                    WHERE hash LIKE ?
                )';
                $parameters[] = '%' . $this->shortUrlQuery . '%';
            }
        }

        if (!empty($conditions)) {
            $this->objectList->getConditionBuilder()->add(
                '(' . implode(' AND ', $conditions) . ')',
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

        // Load URL hashes for all specials
        $urlHashes = [];
        
        // Get objects from objectList (works with MultipleLinkPage)
        $objects = $this->objectList->getObjects();
        if (!empty($objects)) {
            $linkIDs = [];
            foreach ($objects as $special) {
                if (isset($special->linkID) && $special->linkID > 0) {
                    $linkIDs[] = $special->linkID;
                }
            }
            
            if (!empty($linkIDs)) {
                $placeholders = str_repeat('?,', count($linkIDs) - 1) . '?';
                $sql = "SELECT linkID, hash FROM shrinkr1_link WHERE linkID IN ({$placeholders})";
                $statement = WCF::getDB()->prepareStatement($sql);
                $statement->execute($linkIDs);
                while ($row = $statement->fetchArray()) {
                    $urlHashes[$row['linkID']] = $row['hash'];
                }
            }
        }

        // Get themes (with fallback to empty array)
        try {
            $themes = SpecialThemeHelper::getThemes();
        } catch (\Exception $e) {
            $themes = [];
        }

        WCF::getTPL()->assign([
            'title' => $this->title ?? '',
            'theme' => $this->theme ?? '',
            'isActive' => $this->isActive,
            'themes' => $themes,
            'urlHashes' => $urlHashes,
            'shortUrl' => $this->shortUrlQuery ?? '',
            'sortField' => $this->sortField,
            'sortOrder' => $this->sortOrder,
            'acpPageSubMenuCategoryList' => 'shrinkr.acp.menu.link.special.list',
        ]);
    }
}

