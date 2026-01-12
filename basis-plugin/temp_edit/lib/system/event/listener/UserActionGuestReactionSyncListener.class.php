<?php

namespace shrinkr\system\event\listener;

use shrinkr\system\user\action\UserActionGuestReactionSyncHandler;
use wcf\data\user\UserAction;
use wcf\system\event\listener\IParameterizedEventListener;

/**
 * Event listener for UserAction::finalizeAction event.
 * Delegates to UserActionGuestReactionSyncHandler.
 * 
 *
 * @author      Sunny C
 * @copyright   2026 Sunny C
 * @license     License for Commercial Plugins
 *
 * @package    de.sunnyc.wsc.shrinkr
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
