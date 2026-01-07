<?php

/**
 * @author      Julian Pfeil <https://julian-pfeil.de>
 * @link        https://darkwood.design/store/user-file-list/1298-julian-pfeil/
 * @copyright   2022 Julian Pfeil Websites & Co.
 * @license     License for Commercial Plugins <https://julian-pfeil.de/lizenz/>
 *
 * @package    dev.tkirch.wsc.urlshort
 */

use wcf\system\database\table\column\NotNullInt10DatabaseTableColumn;
use wcf\system\database\table\column\NotNullVarchar255DatabaseTableColumn;
use wcf\system\database\table\column\ObjectIdDatabaseTableColumn;
use wcf\system\database\table\DatabaseTable;
use wcf\system\database\table\index\DatabaseTablePrimaryIndex;
use wcf\system\database\table\index\DatabaseTableIndex;

return [
    DatabaseTable::create('urlshort1_url')
        ->columns([
            ObjectIdDatabaseTableColumn::create('urlID'),
            NotNullVarchar255DatabaseTableColumn::create('url'),
            NotNullVarchar255DatabaseTableColumn::create('hash')
                ->length(64),
            NotNullInt10DatabaseTableColumn::create('counter')
                ->defaultValue(0),
        ])
        ->indices([
            DatabaseTablePrimaryIndex::create()
                ->columns(['urlID']),
            DatabaseTableIndex::create('hash')
                ->type(DatabaseTableIndex::UNIQUE_TYPE)
                ->columns(['hash'])
        ]),
];
