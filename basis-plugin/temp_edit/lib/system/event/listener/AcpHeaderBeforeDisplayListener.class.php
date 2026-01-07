<?php

namespace urlshort\system\event\listener;

use wcf\system\event\listener\IParameterizedEventListener;
use wcf\system\reaction\ReactionHandler;
use wcf\system\request\RequestHandler;
use wcf\system\WCF;

/**
 * Event listener to add REACTION_TYPES to template variables for ACP header.
 *
 * @author      Sunny C. <https://benjaro.info>
 * @copyright   2022-2025 Benjaro
 * @license     License for Commercial Plugins <https://benjaro.info>
 *
 * @package    dev.tkirch.wsc.urlshort
 * @subpackage system.event.listener
 */
class AcpHeaderBeforeDisplayListener implements IParameterizedEventListener
{
    /**
     * @inheritDoc
     */
    public function execute($eventObj, $className, $eventName, array &$parameters)
    {
        if (RequestHandler::getInstance()->isACPRequest() && MODULE_LIKE) {
            $reactionHandler = ReactionHandler::getInstance();
            $reactionTypesJS = $reactionHandler->getReactionsJSVariable();
            
            WCF::getTPL()->assign([
                'reactionTypesJS' => $reactionTypesJS,
            ]);
        }
    }
}
