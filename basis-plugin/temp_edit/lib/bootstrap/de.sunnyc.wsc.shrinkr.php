<?php

/**
 * Shr1nkr Bootstrap File
 *
 * @author      Sunny C
 * @copyright   2026 Sunny C
 * @license     License for Commercial Plugins
 * @link        https://sunnyc.de
 *
 * @package     de.sunnyc.wsc.shrinkr
 */

use wcf\event\acp\menu\item\ItemCollecting;
use wcf\event\template\TemplateEngineBeforeDisplay;
use wcf\system\event\EventHandler;
use wcf\system\menu\acp\AcpMenuItem;
use wcf\system\reaction\ReactionHandler;
use wcf\system\request\RequestHandler;
use wcf\system\request\LinkHandler;
use wcf\system\style\FontAwesomeIcon;
use wcf\system\WCF;

return static function (): void {
    EventHandler::getInstance()->register(ItemCollecting::class, static function (ItemCollecting $event) {
        // Main menu item
        $event->register(new AcpMenuItem(
            'shrinkr.acp.menu.link.menu',
            'shrinkr.acp.menu.link.menu',
            'wcf.acp.menu.link.application'
        ));
        
        // Sub menu items only when plugin is active
        if (defined('SHRINKR_ACTIVE') && SHRINKR_ACTIVE) {
            $event->register(new AcpMenuItem(
                'shrinkr.acp.menu.link.link.list',
                'shrinkr.acp.menu.link.link.list',
                'shrinkr.acp.menu.link.menu',
                LinkHandler::getInstance()->getControllerLink(\shrinkr\acp\page\ShrinkrLinkListPage::class)
            ));
            
            $event->register(new AcpMenuItem(
                'shrinkr.acp.menu.link.link.add',
                'shrinkr.acp.menu.link.link.add',
                'shrinkr.acp.menu.link.link.list',
                LinkHandler::getInstance()->getControllerLink(\shrinkr\acp\form\ShrinkrLinkAddForm::class),
                FontAwesomeIcon::fromString('plus;false')
            ));
        }
    });

    // Add REACTION_TYPES to template variables for ACP header
    EventHandler::getInstance()->register(TemplateEngineBeforeDisplay::class, static function (TemplateEngineBeforeDisplay $event) {
        if (RequestHandler::getInstance()->isACPRequest() && defined('MODULE_LIKE') && MODULE_LIKE) {
            $reactionHandler = ReactionHandler::getInstance();
            $reactionTypesJS = $reactionHandler->getReactionsJSVariable();
            
            WCF::getTPL()->assign([
                'reactionTypesJS' => $reactionTypesJS,
            ]);
        }
    });
};
