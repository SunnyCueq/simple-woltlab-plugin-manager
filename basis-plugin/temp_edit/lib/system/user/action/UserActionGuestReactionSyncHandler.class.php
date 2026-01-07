<?php

namespace urlshort\system\user\action;

use urlshort\data\guestreaction\GuestReactionEditor;
use urlshort\data\guestreaction\GuestReactionList;
use wcf\data\like\Like;
use wcf\data\reaction\ReactionAction;
use wcf\data\user\User;
use wcf\data\user\UserAction;
use wcf\system\reaction\ReactionHandler;
use wcf\system\WCF;

/**
 * Handles synchronization of guest reactions with user account upon registration.
 * 
 *
 * @author      Sunny C. <https://benjaro.info>
 * @copyright   2022-2025 Benjaro
 * @license     License for Commercial Plugins <https://benjaro.info>
 *
 * @package    dev.tkirch.wsc.urlshort
 * @subpackage system.user.action
 */
class UserActionGuestReactionSyncHandler
{
    /**
     * Handles the UserAction::finalizeAction event when a user is created.
     * 
     *
     * @param UserAction $userAction The user action that was finalized
     */
    public function handleFinalizeAction(UserAction $userAction): void
    {
        if ($userAction->getActionName() === 'create') {
            // Get the created user
            $returnValues = $userAction->getReturnValues();
            if (isset($returnValues['returnValues']) && $returnValues['returnValues'] instanceof User) {
                $user = $returnValues['returnValues'];
                $sessionID = WCF::getSession()->sessionID;
                
                // Get all guest reactions for this session
                $guestReactionList = new GuestReactionList();
                $guestReactionList->getConditionBuilder()->add('sessionID = ?', [$sessionID]);
                $guestReactionList->readObjects();
                
                // Convert guest reactions to regular reactions
                foreach ($guestReactionList as $guestReaction) {
                    // Check if user already reacted on this object
                    $objectType = ReactionHandler::getInstance()->getObjectType($guestReaction->objectType);
                    if ($objectType === null) {
                        continue;
                    }
                    
                    $like = Like::getLike(
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
                    $guestReactionEditor = new GuestReactionEditor($guestReaction);
                    $guestReactionEditor->delete();
                }
            }
        }
    }
}
