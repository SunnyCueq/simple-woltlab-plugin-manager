<?php

/**
 * Shr1nkr Bootstrap File
 * 
 * Registers ACP menu items for the Shr1nkr plugin using WoltLab 6.1 API.
 * This file overrides the URLs generated from acpMenu.xml to ensure they
 * use the correct ACP URL format without application prefix for AJAX loading.
 *
 * @author      Sunny C
 * @copyright   2026 Sunny C
 * @license     License for Commercial Plugins
 * @link        https://sunnyc.de
 * @package     de.sunnyc.wsc.shrinkr
 */

use wcf\event\acp\menu\item\ItemCollecting;
use wcf\system\event\EventHandler;
use wcf\system\menu\acp\AcpMenuItem;
use wcf\system\request\LinkHandler;
use wcf\system\style\FontAwesomeIcon;

return static function (): void {
    EventHandler::getInstance()->register(ItemCollecting::class, static function (ItemCollecting $event) {
        // Override menu items from acpMenu.xml to fix URLs
        // ACP URLs must not include application prefix for AJAX loading to work
        // We use getControllerLink() but override the application parameter to 'wcf'
        // so URLs are generated as /acp/index.php?controller/ instead of /shrinkr/acp/index.php?controller/
        
        $linkHandler = LinkHandler::getInstance();
        
        // ShrinkrLinkListPage
        $event->register(new AcpMenuItem(
            'shrinkr.acp.menu.link.link.list',
            'shrinkr.acp.menu.link.link.list',
            'shrinkr.acp.menu.link.menu',
            $linkHandler->getControllerLink(\shrinkr\acp\page\ShrinkrLinkListPage::class, ['application' => 'wcf'])
        ));
        
        // DiscountListPage
        $event->register(new AcpMenuItem(
            'shrinkr.acp.menu.link.discount.list',
            'shrinkr.acp.menu.link.discount.list',
            'shrinkr.acp.menu.link.menu',
            $linkHandler->getControllerLink(\shrinkr\acp\page\DiscountListPage::class, ['application' => 'wcf'])
        ));
        
        // DescriptionListPage
        $event->register(new AcpMenuItem(
            'shrinkr.acp.menu.link.description.list',
            'shrinkr.acp.menu.link.description.list',
            'shrinkr.acp.menu.link.menu',
            $linkHandler->getControllerLink(\shrinkr\acp\page\DescriptionListPage::class, ['application' => 'wcf'])
        ));
        
        // ThemeListPage
        $event->register(new AcpMenuItem(
            'shrinkr.acp.menu.link.theme.list',
            'shrinkr.acp.menu.link.theme.list',
            'shrinkr.acp.menu.link.menu',
            $linkHandler->getControllerLink(\shrinkr\acp\page\ThemeListPage::class, ['application' => 'wcf'])
        ));
        
        // SpecialListPage
        $event->register(new AcpMenuItem(
            'shrinkr.acp.menu.link.special.list',
            'shrinkr.acp.menu.link.special.list',
            'shrinkr.acp.menu.link.link.list',
            $linkHandler->getControllerLink(\shrinkr\acp\page\SpecialListPage::class, ['application' => 'wcf']),
            FontAwesomeIcon::fromString('fire;false')
        ));
        
        // ShrinkrLinkAddForm
        $event->register(new AcpMenuItem(
            'shrinkr.acp.menu.link.link.add',
            'shrinkr.acp.menu.link.link.add',
            'shrinkr.acp.menu.link.link.list',
            $linkHandler->getControllerLink(\shrinkr\acp\form\ShrinkrLinkAddForm::class, ['application' => 'wcf']),
            FontAwesomeIcon::fromString('plus;false')
        ));
        
        // ShrinkrLinkListPage (password protected filter)
        $event->register(new AcpMenuItem(
            'shrinkr.acp.menu.link.link.password',
            'shrinkr.acp.menu.link.link.password',
            'shrinkr.acp.menu.link.link.list',
            $linkHandler->getControllerLink(\shrinkr\acp\page\ShrinkrLinkListPage::class, ['application' => 'wcf'], 'passwordProtected=1'),
            FontAwesomeIcon::fromString('key;false')
        ));
        
        // DiscountAddForm
        $event->register(new AcpMenuItem(
            'shrinkr.acp.menu.link.discount.add',
            'shrinkr.acp.menu.link.discount.add',
            'shrinkr.acp.menu.link.discount.list',
            $linkHandler->getControllerLink(\shrinkr\acp\form\DiscountAddForm::class, ['application' => 'wcf']),
            FontAwesomeIcon::fromString('plus;false')
        ));
        
        // DescriptionAddForm
        $event->register(new AcpMenuItem(
            'shrinkr.acp.menu.link.description.add',
            'shrinkr.acp.menu.link.description.add',
            'shrinkr.acp.menu.link.description.list',
            $linkHandler->getControllerLink(\shrinkr\acp\form\DescriptionAddForm::class, ['application' => 'wcf']),
            FontAwesomeIcon::fromString('plus;false')
        ));
        
        // ThemeAddForm
        $event->register(new AcpMenuItem(
            'shrinkr.acp.menu.link.theme.add',
            'shrinkr.acp.menu.link.theme.add',
            'shrinkr.acp.menu.link.theme.list',
            $linkHandler->getControllerLink(\shrinkr\acp\form\ThemeAddForm::class, ['application' => 'wcf']),
            FontAwesomeIcon::fromString('plus;false')
        ));
        
        // ShrinkrSettingsForm
        $event->register(new AcpMenuItem(
            'shrinkr.acp.menu.link.settings',
            'shrinkr.acp.menu.link.settings',
            'shrinkr.acp.menu.link.menu',
            $linkHandler->getControllerLink(\shrinkr\acp\form\ShrinkrSettingsForm::class, ['application' => 'wcf']),
            FontAwesomeIcon::fromString('gear;false')
        ));
    });
};
