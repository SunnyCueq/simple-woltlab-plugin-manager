<?php

namespace urlshort\system\event\listener;

use urlshort\data\guestreaction\GuestReactionList;
use wcf\data\reaction\ReactionAction;
use wcf\data\user\UserAction;
use wcf\system\event\listener\IParameterizedEventListener;
use wcf\system\WCF;

/**
 * Event listener to synchronize guest reactions with user account upon registration.
 *
 * @author      Sunny C. <https://benjaro.info>
 * @copyright   2022-2025 Benjaro
 * @license     License for Commercial Plugins <https://benjaro.info>
 *
 * @package    info.benjaro.urlshort.affiliate
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
            if ($eventObj->getActionName() === 'create') {
                // Get the created user
                $returnValues = $eventObj->getReturnValues();
                if (isset($returnValues['returnValues']) && $returnValues['returnValues'] instanceof \wcf\data\user\User) {
                    $user = $returnValues['returnValues'];
                    $sessionID = WCF::getSession()->sessionID;
                    
                    // Get all guest reactions for this session
                    $guestReactionList = new GuestReactionList();
                    $guestReactionList->getConditionBuilder()->add('sessionID = ?', [$sessionID]);
                    $guestReactionList->readObjects();
                    
                    // Convert guest reactions to regular reactions
                    foreach ($guestReactionList as $guestReaction) {
                        // Check if user already reacted on this object
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
                            // Create regular reaction from guest reaction
                            $reactionAction = new ReactionAction([], 'create', [
                                'data' => [
                                    'objectID' => $guestReaction->objectID,
                                    'objectTypeID' => $objectType->objectTypeID,
                                    'objectUserID' => null, // URL objects don't have a userID
                                    'userID' => $user->userID,
                                    'time' => $guestReaction->time,
                                    'likeValue' => 1,
                                    'reactionTypeID' => $guestReaction->reactionTypeID,
                                ],
                            ]);
                            $reactionAction->executeAction();
                        }
                        
                        // Delete guest reaction
                        $guestReactionEditor = new \urlshort\data\guestreaction\GuestReactionEditor($guestReaction);
                        $guestReactionEditor->delete();
                    }
                }
            }
        }
    }
}

