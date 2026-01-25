<?php

/**
 * Shr1nkr Database Installation
 * 
 * Defines database table structure for the Shr1nkr plugin. Creates all required
 * tables including links, discounts, specials, themes, descriptions, visits,
 * button clicks, guest reactions, and featured links. Uses WoltLab's database
 * table builder API for type-safe table definitions.
 *
 * @author      Sunny C
 * @copyright   2026 Sunny C
 * @license     License for Commercial Plugins
 * @link        https://sunnyc.de
 * @package     de.sunnyc.wsc.shrinkr
 */

use wcf\system\database\table\column\MediumtextDatabaseTableColumn;
use wcf\system\database\table\column\NotNullInt10DatabaseTableColumn;
use wcf\system\database\table\column\NotNullVarchar255DatabaseTableColumn;
use wcf\system\database\table\column\ObjectIdDatabaseTableColumn;
use wcf\system\database\table\column\VarcharDatabaseTableColumn;
use wcf\system\database\table\column\DefaultFalseBooleanDatabaseTableColumn;
use wcf\system\database\table\column\IntDatabaseTableColumn;
use wcf\system\database\table\column\EnumDatabaseTableColumn;
use wcf\system\database\table\PartialDatabaseTable;
use wcf\system\database\table\DatabaseTable;
use wcf\system\database\table\index\DatabaseTablePrimaryIndex;
use wcf\system\database\table\index\DatabaseTableIndex;

return [
    // Main link table
    DatabaseTable::create('shrinkr1_link')
        ->columns([
            ObjectIdDatabaseTableColumn::create('linkID'),
            NotNullVarchar255DatabaseTableColumn::create('url'),
            NotNullVarchar255DatabaseTableColumn::create('hash')
                ->length(64),
            NotNullInt10DatabaseTableColumn::create('counter')
                ->defaultValue(0),
            MediumtextDatabaseTableColumn::create('featuredLinks'),
            VarcharDatabaseTableColumn::create('linkTitle')
                ->length(255),
            VarcharDatabaseTableColumn::create('autoExtractedTitle')
                ->length(255)
                ->defaultValue(''),
            VarcharDatabaseTableColumn::create('faviconUrl')
                ->length(255)
                ->defaultValue(''),
            VarcharDatabaseTableColumn::create('ogImage')
                ->length(255)
                ->defaultValue(''),
            DefaultFalseBooleanDatabaseTableColumn::create('isDemo')
                ->defaultValue(0),
            VarcharDatabaseTableColumn::create('passwordHash')
                ->length(255)
                ->defaultValue(null),
            DefaultFalseBooleanDatabaseTableColumn::create('sessionStorageEnabled')
                ->defaultValue(0),
        ])
        ->indices([
            DatabaseTablePrimaryIndex::create()
                ->columns(['linkID']),
            DatabaseTableIndex::create('hash')
                ->type(DatabaseTableIndex::UNIQUE_TYPE)
                ->columns(['hash'])
        ]),

    // Description table
    DatabaseTable::create('shrinkr1_description')
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

    // Discount table
    DatabaseTable::create('shrinkr1_discount')
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

    // Featured link table
    DatabaseTable::create('shrinkr1_featured_link')
        ->columns([
            ObjectIdDatabaseTableColumn::create('featuredLinkID'),
            IntDatabaseTableColumn::create('linkID')
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
                ->columns(['featuredLinkID']),
            DatabaseTableIndex::create('linkID')
                ->columns(['linkID']),
        ]),

    // Custom button table
    DatabaseTable::create('shrinkr1_custom_button')
        ->columns([
            ObjectIdDatabaseTableColumn::create('customButtonID'),
            IntDatabaseTableColumn::create('linkID')
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
            DatabaseTableIndex::create('linkID')
                ->columns(['linkID']),
        ]),

    // Special table
    DatabaseTable::create('shrinkr1_special')
        ->columns([
            ObjectIdDatabaseTableColumn::create('specialID'),
            IntDatabaseTableColumn::create('linkID')
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
            DatabaseTableIndex::create('linkID')
                ->columns(['linkID']),
        ]),

    // Theme table
    DatabaseTable::create('shrinkr1_theme')
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
            DatabaseTableIndex::create('identifier')
                ->columns(['identifier'])
                ->type(DatabaseTableIndex::UNIQUE_TYPE),
        ]),

    // Guest reaction table
    DatabaseTable::create('shrinkr1_guest_reaction')
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
            DatabaseTableIndex::create('sessionID')
                ->columns(['sessionID']),
            DatabaseTableIndex::create('objectTypeObjectID')
                ->columns(['objectType', 'objectID']),
        ]),
];
