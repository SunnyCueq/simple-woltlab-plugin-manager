<?php

namespace urlshort\acp\page;

use urlshort\data\special\SpecialList;
use urlshort\system\special\SpecialThemeHelper;
use wcf\page\MultipleLinkPage;
use wcf\system\WCF;

/**
 * ACP page for listing all specials.
 *
 * @author      Sunny C. <https://benjaro.info>
 * @copyright   2022-2025 Benjaro
 * @license     License for Commercial Plugins <https://benjaro.info>
 *
 * @package    dev.tkirch.wsc.urlshort
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
    public $activeMenuItem = 'urlshort.acp.menu.link.special.list';

    /**
     * @inheritDoc
     */
    public $neededPermissions = ['admin.urlshort.canManageSpecials'];

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
                $conditions[] = 'urlID = ?';
                $parameters[] = (int) $this->shortUrlQuery;
            } else {
                $conditions[] = 'urlID IN (
                    SELECT urlID
                    FROM urlshort1_url
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
            $urlIDs = [];
            foreach ($objects as $special) {
                if (isset($special->urlID) && $special->urlID > 0) {
                    $urlIDs[] = $special->urlID;
                }
            }
            
            if (!empty($urlIDs)) {
                $placeholders = str_repeat('?,', count($urlIDs) - 1) . '?';
                $sql = "SELECT urlID, hash FROM urlshort1_url WHERE urlID IN ({$placeholders})";
                $statement = WCF::getDB()->prepareStatement($sql);
                $statement->execute($urlIDs);
                while ($row = $statement->fetchArray()) {
                    $urlHashes[$row['urlID']] = $row['hash'];
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
        ]);
    }
}

