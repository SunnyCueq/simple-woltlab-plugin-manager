<?php

namespace shrinkr\acp\page;

use shrinkr\data\featuredlink\FeaturedLinkList;
use wcf\page\MultipleLinkPage;
use wcf\system\exception\IllegalLinkException;
use wcf\system\WCF;
use wcf\util\StringUtil;

/**
 * ACP page for listing featured links for a specific URL.
 *
 * @author      Sunny C. <https://benjaro.info>
 * @copyright   2026 Sunny C
 * @license     License for Commercial Plugins <https://benjaro.info>
 *
 * @package    de.sunnyc.wsc.shrinkr
 * @subpackage acp.page
 *
 * @property FeaturedLinkList $objectList
 */
class FeaturedLinkListPage extends MultipleLinkPage
{
    /**
     * @inheritDoc
     */
    public $objectListClassName = FeaturedLinkList::class;

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
    public $activeMenuItem = 'shrinkr.acp.menu.link.menu';

    /**
     * @inheritDoc
     */
    public $neededPermissions = ['admin.shrinkr.canManageFeaturedLinks'];

    /**
     * @inheritDoc
     */
    public $validSortFields = ['linkID', 'url', 'title', 'sortOrder'];

    /**
     * URL ID (required parameter from URL query)
     */
    public int $linkID = 0;

    /**
     * Hash of the short URL.
     */
    public string $urlHash = '';

    /**
     * Destination URL of the short URL.
     */
    public string $urlTarget = '';

    /**
     * Query string for filtering
     */
    public $q;

    /**
     * @inheritDoc
     */
    public function readParameters()
    {
        parent::readParameters();

        // Read sort parameters with StringUtil::trim() for sanitization
        $sortField = StringUtil::trim($_REQUEST['sortField'] ?? '');
        if (\in_array($sortField, $this->validSortFields, true)) {
            $this->sortField = $sortField;
        }

        $sortOrder = StringUtil::trim($_REQUEST['sortOrder'] ?? '');
        if (\in_array($sortOrder, ['ASC', 'DESC'], true)) {
            $this->sortOrder = $sortOrder;
        }

        // Read linkID parameter
        $this->linkID = \intval($_REQUEST['linkID'] ?? 0);

        // URL ID is required
        if ($this->linkID === 0) {
            throw new IllegalLinkException();
        }

        // Load URL metadata
        $sql = "SELECT hash, url FROM shrinkr1_link WHERE linkID = ?";
        $statement = WCF::getDB()->prepareStatement($sql);
        $statement->execute([$this->linkID]);
        $row = $statement->fetchArray();
        if (!$row) {
            throw new IllegalLinkException();
        }

        $this->urlHash = $row['hash'] ?? '';
        $this->urlTarget = $row['url'] ?? '';

        // Read query parameter with StringUtil::trim() for sanitization
        $this->q = StringUtil::trim($_REQUEST['q'] ?? '');
    }

    /**
     * @inheritDoc
     */
    protected function initObjectList()
    {
        parent::initObjectList();

        // Filter by linkID (required)
        $this->objectList->getConditionBuilder()->add('linkID = ?', [$this->linkID]);

        // Apply sorting
        if (in_array($this->sortField, $this->validSortFields)) {
            $this->objectList->sqlOrderBy = $this->sortField . ' ' . $this->sortOrder;
        }

        // Add query parameter filter
        if ($this->q) {
            $this->objectList->getConditionBuilder()->add(
                'url LIKE ? OR title LIKE ?',
                [
                    '%' . $this->q . '%',
                    '%' . $this->q . '%',
                ]
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
            'linkID' => $this->linkID,
            'q' => $this->q,
            'urlHash' => $this->urlHash,
            'urlTarget' => $this->urlTarget,
        ]);
    }
}
