<?php

use wcf\system\database\table\column\MediumtextDatabaseTableColumn;
use wcf\system\database\table\PartialDatabaseTable;
use wcf\system\database\table\column\NotNullVarchar255DatabaseTableColumn;
use wcf\system\database\table\column\VarcharDatabaseTableColumn;
use wcf\system\database\table\column\DefaultFalseBooleanDatabaseTableColumn;
use wcf\system\database\table\column\IntDatabaseTableColumn;
use wcf\system\database\table\column\ObjectIdDatabaseTableColumn;
use wcf\system\database\table\DatabaseTable;
use wcf\system\database\table\index\DatabaseTablePrimaryIndex;
use wcf\system\database\table\index\DatabaseTableIndex;

/**
 * @author      Julian Pfeil <https://julian-pfeil.de>
 * @link        https://julian-pfeil.de/r/plugins
 * @copyright   2022 Benjaro
 * @license     License for Commercial Plugins <https://julian-pfeil.de/lizenz/>
 *
 * @package    dev.tkirch.wsc.urlshort
 */

return [
    PartialDatabaseTable::create('urlshort1_url')
        ->columns([
            MediumtextDatabaseTableColumn::create('featuredLinks'),
            VarcharDatabaseTableColumn::create('urlTitle')
                ->length(255),
            VarcharDatabaseTableColumn::create('autoExtractedTitle')
                ->length(255)
                ->defaultValue(''),
            VarcharDatabaseTableColumn::create('faviconUrl')
                ->length(255)
                ->defaultValue(''),
            DefaultFalseBooleanDatabaseTableColumn::create('isDemo')
                ->defaultValue(0),
        ]),

    DatabaseTable::create('urlshort1_description')
        ->columns([
            ObjectIdDatabaseTableColumn::create('descriptionID'),
            NotNullVarchar255DatabaseTableColumn::create('title'),
            MediumtextDatabaseTableColumn::create('descriptionText'),
            DefaultFalseBooleanDatabaseTableColumn::create('isActive')
                ->defaultValue(1),
        ])
        ->indices([
            DatabaseTablePrimaryIndex::create()
                ->columns(['descriptionID']),
        ]),

    DatabaseTable::create('urlshort1_discount')
        ->columns([
            ObjectIdDatabaseTableColumn::create('discountID'),
            NotNullVarchar255DatabaseTableColumn::create('discountValue'),
            VarcharDatabaseTableColumn::create('favicon')
                ->length(255)
                ->defaultValue(''),
            MediumtextDatabaseTableColumn::create('hosts'),
            DefaultFalseBooleanDatabaseTableColumn::create('special'),
            VarcharDatabaseTableColumn::create('specialIdentifier')
                ->length(255),
            MediumtextDatabaseTableColumn::create('additionalText'),
            MediumtextDatabaseTableColumn::create('codes'),
            NotNullVarchar255DatabaseTableColumn::create('primaryColor'),
            NotNullVarchar255DatabaseTableColumn::create('secondaryColor'),
            NotNullVarchar255DatabaseTableColumn::create('primaryTextColor'),
            NotNullVarchar255DatabaseTableColumn::create('secondaryTextColor'),
            IntDatabaseTableColumn::create('countdownStart')
                ->length(10)
                ->defaultValue(0),
            IntDatabaseTableColumn::create('countdownEnd')
                ->length(10)
                ->defaultValue(0),
        ])
        ->indices([
            DatabaseTablePrimaryIndex::create()
                ->columns(['discountID']),
        ]),

    DatabaseTable::create('urlshort1_featured_link')
        ->columns([
            ObjectIdDatabaseTableColumn::create('linkID'),
            IntDatabaseTableColumn::create('urlID')
                ->length(10)
                ->notNull(),
            NotNullVarchar255DatabaseTableColumn::create('url'),
            NotNullVarchar255DatabaseTableColumn::create('title'),
            IntDatabaseTableColumn::create('sortOrder')
                ->length(10)
                ->defaultValue(0),
        ])
        ->indices([
            DatabaseTablePrimaryIndex::create()
                ->columns(['linkID']),
        ]),

    DatabaseTable::create('urlshort1_custom_button')
        ->columns([
            ObjectIdDatabaseTableColumn::create('customButtonID'),
            IntDatabaseTableColumn::create('urlID')
                ->length(10)
                ->notNull(),
            NotNullVarchar255DatabaseTableColumn::create('targetUrl'),
            NotNullVarchar255DatabaseTableColumn::create('title'),
            IntDatabaseTableColumn::create('sortOrder')
                ->length(10)
                ->defaultValue(0),
        ])
        ->indices([
            DatabaseTablePrimaryIndex::create()
                ->columns(['customButtonID']),
            DatabaseTableIndex::create('urlID')
                ->columns(['urlID']),
        ]),

    DatabaseTable::create('urlshort1_special')
        ->columns([
            ObjectIdDatabaseTableColumn::create('specialID'),
            IntDatabaseTableColumn::create('urlID')
                ->length(10)
                ->notNull(),
            NotNullVarchar255DatabaseTableColumn::create('theme')
                ->defaultValue(''),
            NotNullVarchar255DatabaseTableColumn::create('title'),
            NotNullVarchar255DatabaseTableColumn::create('discount')
                ->defaultValue(''),
            IntDatabaseTableColumn::create('discountID')
                ->length(10)
                ->defaultValue(0),
            MediumtextDatabaseTableColumn::create('codes'),
            NotNullVarchar255DatabaseTableColumn::create('primaryColor')
                ->defaultValue('var(--wcfHeaderBackground)'),
            NotNullVarchar255DatabaseTableColumn::create('secondaryColor')
                ->defaultValue('var(--wcfHeaderMenuBackground)'),
            NotNullVarchar255DatabaseTableColumn::create('primaryTextColor')
                ->defaultValue('var(--wcfHeaderMenuLink)'),
            NotNullVarchar255DatabaseTableColumn::create('secondaryTextColor')
                ->defaultValue('var(--wcfHeaderMenuLink)'),
            MediumtextDatabaseTableColumn::create('additionalText'),
            IntDatabaseTableColumn::create('startTime')
                ->length(10)
                ->defaultValue(0),
            IntDatabaseTableColumn::create('endTime')
                ->length(10)
                ->defaultValue(0),
            DefaultFalseBooleanDatabaseTableColumn::create('isActive')
                ->defaultValue(1),
        ])
        ->indices([
            DatabaseTablePrimaryIndex::create()
                ->columns(['specialID']),
        ]),

    DatabaseTable::create('urlshort1_guest_reaction')
        ->columns([
            ObjectIdDatabaseTableColumn::create('guestReactionID'),
            NotNullVarchar255DatabaseTableColumn::create('sessionID'),
            NotNullVarchar255DatabaseTableColumn::create('objectType'),
            IntDatabaseTableColumn::create('objectID')
                ->length(10)
                ->notNull(),
            IntDatabaseTableColumn::create('reactionTypeID')
                ->length(10)
                ->notNull(),
            IntDatabaseTableColumn::create('time')
                ->length(10)
                ->notNull(),
        ])
        ->indices([
            DatabaseTablePrimaryIndex::create()
                ->columns(['guestReactionID']),
            \wcf\system\database\table\index\DatabaseTableIndex::create('sessionID')
                ->columns(['sessionID']),
            \wcf\system\database\table\index\DatabaseTableIndex::create('objectTypeObjectID')
                ->columns(['objectType', 'objectID']),
        ]),

    DatabaseTable::create('urlshort1_theme')
        ->columns([
            ObjectIdDatabaseTableColumn::create('themeID'),
            NotNullVarchar255DatabaseTableColumn::create('identifier'),
            NotNullVarchar255DatabaseTableColumn::create('title'),
            VarcharDatabaseTableColumn::create('effectIdentifier')
                ->length(64)
                ->defaultValue('none'),
            NotNullVarchar255DatabaseTableColumn::create('primaryColor')
                ->defaultValue('rgba(255, 255, 255, 1)'),
            NotNullVarchar255DatabaseTableColumn::create('secondaryColor')
                ->defaultValue('rgba(255, 255, 255, 1)'),
            NotNullVarchar255DatabaseTableColumn::create('primaryTextColor')
                ->defaultValue('rgba(0, 0, 0, 1)'),
            NotNullVarchar255DatabaseTableColumn::create('secondaryTextColor')
                ->defaultValue('rgba(0, 0, 0, 1)'),
            DefaultFalseBooleanDatabaseTableColumn::create('isActive')
                ->defaultValue(1),
            IntDatabaseTableColumn::create('sortOrder')
                ->length(10)
                ->defaultValue(0),
            MediumtextDatabaseTableColumn::create('cssContent'),
        ])
        ->indices([
            DatabaseTablePrimaryIndex::create()
                ->columns(['themeID']),
            \wcf\system\database\table\index\DatabaseTableIndex::create('identifier')
                ->columns(['identifier'])
                ->type(\wcf\system\database\table\index\DatabaseTableIndex::UNIQUE_TYPE),
        ]),

    DatabaseTable::create('urlshort1_button_click')
        ->columns([
            ObjectIdDatabaseTableColumn::create('clickID'),
            IntDatabaseTableColumn::create('urlID')
                ->length(10)
                ->notNull(),
            VarcharDatabaseTableColumn::create('buttonType')
                ->length(50)
                ->notNull(),
            IntDatabaseTableColumn::create('linkID')
                ->length(10),
            IntDatabaseTableColumn::create('clickTime')
                ->length(10)
                ->notNull(),
            IntDatabaseTableColumn::create('userID')
                ->length(10),
            VarcharDatabaseTableColumn::create('sessionID')
                ->length(255),
        ])
        ->indices([
            DatabaseTablePrimaryIndex::create()
                ->columns(['clickID']),
            DatabaseTableIndex::create('urlID')
                ->columns(['urlID']),
            DatabaseTableIndex::create('buttonType')
                ->columns(['buttonType']),
            DatabaseTableIndex::create('clickTime')
                ->columns(['clickTime']),
        ]),

    DatabaseTable::create('urlshort1_visit')
        ->columns([
            ObjectIdDatabaseTableColumn::create('visitID'),
            IntDatabaseTableColumn::create('urlID')
                ->length(10)
                ->notNull(),
            IntDatabaseTableColumn::create('visitTime')
                ->length(10)
                ->notNull(),
            VarcharDatabaseTableColumn::create('referrer')
                ->length(512),
            IntDatabaseTableColumn::create('userID')
                ->length(10),
            VarcharDatabaseTableColumn::create('sessionID')
                ->length(255),
        ])
        ->indices([
            DatabaseTablePrimaryIndex::create()
                ->columns(['visitID']),
            DatabaseTableIndex::create('urlID')
                ->columns(['urlID']),
            DatabaseTableIndex::create('visitTime')
                ->columns(['visitTime']),
        ]),
];
