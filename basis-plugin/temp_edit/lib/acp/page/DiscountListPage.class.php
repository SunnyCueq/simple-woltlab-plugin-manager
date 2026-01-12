<?php

namespace shrinkr\acp\page;

use shrinkr\data\discount\DiscountList;
use wcf\page\MultipleLinkPage;
use wcf\system\WCF;

/**
 * ACP page for listing all discounts.
 *
 * @author      Sunny C
 * @copyright   2026 Sunny C
 * @license     License for Commercial Plugins
 *
 * @package    de.sunnyc.wsc.shrinkr
 * @subpackage acp.page
 */

class DiscountListPage extends MultipleLinkPage
{
    /**
     * @inheritDoc
     */
    public $objectListClassName = DiscountList::class;

    /**
     * @inheritDoc
     */
    public $sortField = 'discountID';

    /**
     * @inheritDoc
     */
    public $sortOrder = 'ASC';

    /**
     * @inheritDoc
     */
    public $validSortFields = ['discountID', 'discountValue'];

    /**
     * @inheritDoc
     */
    public $activeMenuItem = 'shrinkr.acp.menu.link.discount.list';

    /**
     * Filter: Discount value
     */
    public $discountValue;

    /**
     * Filter: Codes
     */
    public $codes;

    /**
     * Filter: Hosts
     */
    public $hosts;

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
        if (isset($_REQUEST['discountValue'])) {
            $this->discountValue = $_REQUEST['discountValue'];
        }
        if (isset($_REQUEST['codes'])) {
            $this->codes = $_REQUEST['codes'];
        }
        if (isset($_REQUEST['hosts'])) {
            $this->hosts = $_REQUEST['hosts'];
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

        // Add discountValue filter
        if ($this->discountValue) {
            $conditions[] = 'discountValue LIKE ?';
            $parameters[] = '%' . $this->discountValue . '%';
        }

        // Add codes filter
        if ($this->codes) {
            $conditions[] = 'codes LIKE ?';
            $parameters[] = '%' . $this->codes . '%';
        }

        // Add hosts filter
        if ($this->hosts) {
            $conditions[] = 'hosts LIKE ?';
            $parameters[] = '%' . $this->hosts . '%';
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
            'discountValue' => $this->discountValue,
            'codes' => $this->codes,
            'hosts' => $this->hosts,
            'sortField' => $this->sortField,
            'sortOrder' => $this->sortOrder,
        ]);
    }
}
