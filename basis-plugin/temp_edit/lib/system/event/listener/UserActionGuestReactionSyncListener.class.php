<?php

namespace shrinkr\system\event\listener;

use shrinkr\data\guestreaction\GuestReactionEditor;
use shrinkr\data\guestreaction\GuestReactionList;
use wcf\data\reaction\ReactionAction;
use wcf\data\user\UserAction;
use wcf\system\event\listener\IParameterizedEventListener;
use wcf\system\WCF;

/**
 * Event listener to synchronize guest reactions with user account upon registration.
 * 
 * When a guest registers a new user account, this listener converts all guest
 * reactions (stored with session ID) to regular user reactions (stored with user ID).
 * This ensures that reactions made as a guest are preserved after registration.
 *
 * @author      Sunny C
 * @copyright   2026 Sunny C
 * @license     License for Commercial Plugins
 * @link        https://sunnyc.de
 * @package     de.sunnyc.wsc.shrinkr
 * @subpackage  system.event.listener
 */
class UserActionGuestReactionSyncListener implements IParameterizedEventListener
{
    /**
     * Executes the event listener.
     * 
     * Listens to UserAction finalizeAction event for 'create' action. When a new
     * user is created (registration), converts all guest reactions from the current
     * session to regular user reactions and deletes the guest reaction entries.
     *
     * @param   object  $eventObj    The event object (UserAction instance)
     * @param   string  $className   The class name of the event object
     * @param   string  $eventName   The event name ('finalizeAction')
     * @param   array   $parameters  Event parameters (passed by reference)
     * @return  void
     */
    public function execute($eventObj, $className, $eventName, array &$parameters)
    {
        if ($eventName === 'finalizeAction' && $eventObj instanceof UserAction) {
            if ($eventObj->getActionName() === 'create') {
                $returnValues = $eventObj->getReturnValues();
                if (isset($returnValues['returnValues']) && $returnValues['returnValues'] instanceof \wcf\data\user\User) {
                    $user = $returnValues['returnValues'];
                    $sessionID = WCF::getSession()->sessionID;
                    
                    $guestReactionList = new GuestReactionList();
                    $guestReactionList->getConditionBuilder()->add('sessionID = ?', [$sessionID]);
                    $guestReactionList->readObjects();
                    
                    foreach ($guestReactionList as $guestReaction) {
                        $objectType = \wcf\system\reaction\ReactionHandler::getInstance()->getObjectType($guestReaction->objectType);
                        if ($objectType === null) {
                            continue;
                        }
                        
                        $like = \wcf\data\like\Like::getLike(
                            $objectType->objectTypeID,
                            $guestReaction->objectID,
                            $user->userID
                        );
                        
                        if (!$like->likeID) {
                            $reactionAction = new ReactionAction([], 'create', [
                                'data' => [
                                    'objectID' => $guestReaction->objectID,
                                    'objectTypeID' => $objectType->objectTypeID,
                                    'objectUserID' => null,
                                    'userID' => $user->userID,
                                    'time' => $guestReaction->time,
                                    'likeValue' => 1,
                                    'reactionTypeID' => $guestReaction->reactionTypeID,
                                ],
                            ]);
                            $reactionAction->executeAction();
                        }
                        
                        $guestReactionEditor = new GuestReactionEditor($guestReaction);
                        $guestReactionEditor->delete();
                    }
                }
            }
        }
    }
}
