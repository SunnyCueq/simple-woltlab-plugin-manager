<?php

namespace urlshort\data\url;

use wcf\data\DatabaseObjectEditor;

/**
 * @author      Julian Pfeil, Titus Kirch <https://julian-pfeil.de>
 * @link        https://darkwood.design/store/user-file-list/1298-julian-pfeil/
 * @copyright   2022 Julian Pfeil Websites & Co.
 * @license     License for Commercial Plugins <https://julian-pfeil.de/lizenz/>
 *
 * @package    dev.tkirch.wsc.urlshort
 * @subpackage data.url
 */
class UrlEditor extends DatabaseObjectEditor
{
    /**
     * @inheritDoc
     */
    protected static $baseClass = Url::class;
}
