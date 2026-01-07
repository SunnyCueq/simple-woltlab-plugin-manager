<?php

namespace urlshort\system\event\listener;

use urlshort\system\reaction\action\ReactionActionGuestReactionHandler;
use wcf\data\reaction\ReactionAction;
use wcf\system\event\listener\IParameterizedEventListener;

/**
 * Event listener for ReactionAction::validateAction and ReactionAction::finalizeAction events.
 * Delegates to ReactionActionGuestReactionHandler.
 * 
 *
 * @author      Sunny C. <https://benjaro.info>
 * @copyright   2022-2025 Benjaro
 * @license     License for Commercial Plugins <https://benjaro.info>
 *
 * @package    dev.tkirch.wsc.urlshort
 * @subpackage system.event.listener
 */
class ReactionActionGuestReactionListener implements IParameterizedEventListener
{
    /**
     * @inheritDoc
     */
    public function execute($eventObj, $className, $eventName, array &$parameters)
    {
        if (!($eventObj instanceof ReactionAction)) {
            return;
        }

        $handler = new ReactionActionGuestReactionHandler();

        // Use the action-specific method name pattern
        if ($eventName === 'validateAction' && $eventObj->getActionName() === 'react') {
            $handler->handleValidateAction($eventObj, $parameters);
        }

        if ($eventName === 'finalizeAction' && $eventObj->getActionName() === 'react') {
            $handler->handleFinalizeAction($eventObj, $parameters);
        }
    }
}
