<?php

namespace urlshort\data\url;

use wcf\data\AbstractDatabaseObjectAction;

/**
 * @author      Julian Pfeil, Titus Kirch <https://julian-pfeil.de>
 * @link        https://darkwood.design/store/user-file-list/1298-julian-pfeil/
 * @copyright   2022 Julian Pfeil Websites & Co.
 * @license     License for Commercial Plugins <https://julian-pfeil.de/lizenz/>
 *
 * @package    dev.tkirch.wsc.urlshort
 * @subpackage data.url
 */
class UrlAction extends AbstractDatabaseObjectAction
{
    /**
     * @inheritDoc
     */
    protected $permissionsDelete = ['admin.urlshort.canManageUrls'];
    
    /**
     * Increase the URL counter of the given URL
     *
     * @param   Url     $url
     */
    public static function increaseUrlCounter(Url $url): void
    {
        // Check if URL counter is active
        if (URLSHORT_COUNTER_ACTIVE) {
            $urlEditor = new UrlEditor($url);
            $urlEditor->update(['counter' => ($url->counter + 1)]);
        }
    }
}
