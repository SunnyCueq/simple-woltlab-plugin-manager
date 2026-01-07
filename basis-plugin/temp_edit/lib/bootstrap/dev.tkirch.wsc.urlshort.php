<?php

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
        // Hauptmenü-Item
        $event->register(new AcpMenuItem(
            "urlshort.acp.menu.link.menu",
            'urlshort.acp.menu.link.menu',
            'wcf.acp.menu.link.application'
        ));
        
        // Untermenü-Items nur wenn Plugin aktiviert
        if (defined('URLSHORT_ACTIVE') && URLSHORT_ACTIVE) {
            $event->register(new AcpMenuItem(
                "urlshort.acp.menu.link.url.list",
                'urlshort.acp.menu.link.url.list',
                "urlshort.acp.menu.link.menu",
                LinkHandler::getInstance()->getControllerLink(\urlshort\acp\page\UrlListPage::class)
            ));
            
            $event->register(new AcpMenuItem(
                "urlshort.acp.menu.link.url.add",
                'urlshort.acp.menu.link.url.add',
                "urlshort.acp.menu.link.url.list",
                LinkHandler::getInstance()->getControllerLink(\urlshort\acp\form\UrlAddForm::class),
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
