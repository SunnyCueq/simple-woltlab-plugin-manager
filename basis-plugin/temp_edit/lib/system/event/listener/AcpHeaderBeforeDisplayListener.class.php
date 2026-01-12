<?php

namespace shrinkr\system\event\listener;

use wcf\system\event\listener\IParameterizedEventListener;
use wcf\system\reaction\ReactionHandler;
use wcf\system\request\RequestHandler;
use wcf\system\WCF;

/**
 * Event listener to add REACTION_TYPES to template variables for ACP header.
 *
 * @author      Sunny C
 * @copyright   2026 Sunny C
 * @license     License for Commercial Plugins
 *
 * @package    de.sunnyc.wsc.shrinkr
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
