<?php

namespace urlshort\acp\page;

use urlshort\data\url\UrlList;
use wcf\page\SortablePage;
use wcf\system\WCF;

/**
 * @author      Julian Pfeil, Titus Kirch <https://julian-pfeil.de>
 * @link        https://darkwood.design/store/user-file-list/1298-julian-pfeil/
 * @copyright   2022 Julian Pfeil Websites & Co.
 * @license     License for Commercial Plugins <https://julian-pfeil.de/lizenz/>
 *
 * @package    dev.tkirch.wsc.urlshort
 * @subpackage acp.page
 */
class UrlListPage extends SortablePage
{
    /**
     * @inheritDoc
     */
    public $activeMenuItem = 'urlshort.acp.menu.link.url.list';
    
    /**
     * @inheritDoc
     */
    public $neededPermissions = ['admin.urlshort.canManageUrls'];
    
    /**
     * @inheritDoc
     */
    public $objectListClassName = UrlList::class;
    
    /**
     * @inheritDoc
     */
    public $validSortFields = ['urlID', 'hash', 'url', 'counter'];

    /**
     * query string
     */
    public $q;

    /**
     * @inheritDoc
     */
    public function readParameters()
    {
        parent::readParameters();
        
        //read query parameter
        if (isset($_REQUEST['q'])) {
            $this->q = $_REQUEST['q'];
        }
    }

    /**
     * @inheritDoc
     */
    protected function initObjectList()
    {
        parent::initObjectList();

        //add query parameter
        if ($this->q) {
            $this->objectList->getConditionBuilder()->add('hash LIKE ? OR url LIKE ?', [
                '%' . $this->q . '%',
                '%' . $this->q . '%',
            ]);
        }
    }
    
    /**
     * @inheritDoc
     */
    public function assignVariables()
    {
        parent::assignVariables();
        
        //assign query parameters
        WCF::getTPL()->assign([
            'q' => $this->q,
        ]);
    }
}
