<?php

namespace urlshort\system\event\listener;

use urlshort\system\user\action\UserActionGuestReactionSyncHandler;
use wcf\data\user\UserAction;
use wcf\system\event\listener\IParameterizedEventListener;

/**
 * Event listener for UserAction::finalizeAction event.
 * Delegates to UserActionGuestReactionSyncHandler.
 * 
 *
 * @author      Sunny C. <https://benjaro.info>
 * @copyright   2022-2025 Benjaro
 * @license     License for Commercial Plugins <https://benjaro.info>
 *
 * @package    dev.tkirch.wsc.urlshort
 * @subpackage system.event.listener
 */
class UserActionGuestReactionSyncListener implements IParameterizedEventListener
{
    /**
     * @inheritDoc
     */
    public function execute($eventObj, $className, $eventName, array &$parameters)
    {
        if ($eventName === 'finalizeAction' && $eventObj instanceof UserAction) {
            $handler = new UserActionGuestReactionSyncHandler();
            $handler->handleFinalizeAction($eventObj);
        }
    }
}
