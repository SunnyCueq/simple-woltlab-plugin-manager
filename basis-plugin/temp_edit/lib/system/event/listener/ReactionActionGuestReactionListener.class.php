<?php

namespace shrinkr\system\event\listener;

use shrinkr\data\guestreaction\GuestReactionEditor;
use wcf\data\option\Option;
use wcf\data\reaction\ReactionAction;
use wcf\system\event\listener\IParameterizedEventListener;
use wcf\system\exception\IllegalLinkException;
use wcf\system\exception\PermissionDeniedException;
use wcf\system\WCF;

/**
 * Event listener for ReactionAction to handle guest reactions.
 * 
 * Intercepts ReactionAction events to enable guest reactions for Shr1nkr links.
 * Uses reflection to modify allowGuestAccess before validation, allowing guests
 * to react when guest reactions are enabled. This listener is used as a fallback
 * mechanism, but the primary handling is done by GuestReactionAction class.
 *
 * @author      Sunny C
 * @copyright   2026 Sunny C
 * @license     License for Commercial Plugins
 * @link        https://sunnyc.de
 * @package     de.sunnyc.wsc.shrinkr
 * @subpackage  system.event.listener
 */
class ReactionActionGuestReactionListener implements IParameterizedEventListener
{
    /**
     * Executes the event listener.
     * 
     * Listens to ReactionAction events (initializeAction, validateAction, finalizeAction)
     * for the 'react' action. Uses reflection to enable guest reactions by modifying
     * allowGuestAccess before validation. Note: This listener is a fallback mechanism;
     * the primary handling is done by GuestReactionAction class via JavaScript interception.
     *
     * @param   object  $eventObj    The event object (ReactionAction instance)
     * @param   string  $className   The class name of the event object
     * @param   string  $eventName   The event name ('initializeAction', 'validateAction', 'finalizeAction')
     * @param   array   $parameters  Event parameters (passed by reference)
     * @return  void
     */
    public function execute($eventObj, $className, $eventName, array &$parameters)
    {
        if (!($eventObj instanceof ReactionAction)) {
            return;
        }

        if ($eventName === 'initializeAction' && $eventObj->getActionName() === 'react') {
            $this->onInitializeActionReact($eventObj, $parameters);
        }

        if ($eventName === 'validateAction' && $eventObj->getActionName() === 'react') {
            $this->onValidateActionReact($eventObj, $parameters);
        }

        if ($eventName === 'finalizeAction' && $eventObj->getActionName() === 'react') {
            $this->onFinalizeActionReact($eventObj, $parameters);
        }
    }

    /**
     * Called during initializeAction to enable guest reactions.
     * 
     * Uses reflection to modify allowGuestAccess before validateAction() checks it.
     * This allows guests to pass the initial permission check, though validateReact()
     * may still throw exceptions. The primary handling is done by GuestReactionAction.
     *
     * @param   ReactionAction  $action      The ReactionAction instance
     * @param   array           $parameters Event parameters (passed by reference)
     * @return  void
     */
    public function onInitializeActionReact(ReactionAction $action, array &$parameters)
    {
        if (WCF::getUser()->userID) {
            return;
        }

        $guestReactionsOption = Option::getOptionByName('shrinkr_enable_guest_reactions');
        $enableGuestReactions = $guestReactionsOption ? ($guestReactionsOption->optionValue == '1' || $guestReactionsOption->optionValue == 1) : false;

        if (!$enableGuestReactions) {
            return;
        }

        if (!isset($action->parameters['data']['objectType']) ||
            $action->parameters['data']['objectType'] !== 'de.sunnyc.wsc.shrinkr.likeableShrinkrLink') {
            return;
        }

        $action->parameters['_isGuestReaction'] = true;
        
        $reflection = new \ReflectionClass($action);
        $allowGuestAccessProperty = $reflection->getProperty('allowGuestAccess');
        $allowGuestAccessProperty->setAccessible(true);
        $currentAccess = $allowGuestAccessProperty->getValue($action);
        if (!in_array('react', $currentAccess)) {
            $currentAccess[] = 'react';
            $allowGuestAccessProperty->setValue($action, $currentAccess);
        }
    }

    /**
     * Called during validateAction (after validateReact()).
     * 
     * This is called AFTER validateReact(), so if validateReact() throws an exception,
     * we never get here. The actual work is done in onInitializeActionReact().
     *
     * @param   ReactionAction  $action      The ReactionAction instance
     * @param   array           $parameters  Event parameters (passed by reference)
     * @return  void
     */
    public function onValidateActionReact(ReactionAction $action, array &$parameters)
    {
    }

    /**
     * Called after react() to handle guest reactions.
     * 
     * If the action was marked as a guest reaction and guest reactions are enabled,
     * delegates to the react() method to handle guest reaction storage and return
     * values override.
     *
     * @param   ReactionAction  $action      The ReactionAction instance
     * @param   array           $parameters  Event parameters (passed by reference)
     * @return  void
     */
    public function onFinalizeActionReact(ReactionAction $action, array &$parameters)
    {
        if (!WCF::getUser()->userID && 
            isset($action->parameters['data']['objectType']) &&
            $action->parameters['data']['objectType'] === 'de.sunnyc.wsc.shrinkr.likeableShrinkrLink') {
            
            $guestReactionsOption = Option::getOptionByName('shrinkr_enable_guest_reactions');
            $enableGuestReactions = $guestReactionsOption ? ($guestReactionsOption->optionValue == '1' || $guestReactionsOption->optionValue == 1) : false;
            
            if ($enableGuestReactions) {
                $this->react($action);
            }
        }
    }

    /**
     * Handles guest reaction creation, update, or removal.
     * 
     * Stores guest reactions in the database using session ID. Supports toggle behavior.
     * Uses reflection to override the action's return values with combined reaction data.
     *
     * @param   ReactionAction  $action  The ReactionAction instance
     * @return  void
     */
    protected function react(ReactionAction $action)
    {
        if (!isset($action->parameters['_isGuestReaction']) || !$action->parameters['_isGuestReaction']) {
            return;
        }

        $sessionID = WCF::getSession()->sessionID;
        $objectType = 'de.sunnyc.wsc.shrinkr.likeableShrinkrLink';
        $objectID = (int)$action->parameters['data']['objectID'];
        $reactionTypeID = (int)$action->parameters['reactionTypeID'];

        $sql = "SELECT  guestReactionID, reactionTypeID
                FROM    shrinkr" . WCF_N . "_guest_reaction
                WHERE   sessionID = ?
                    AND objectType = ?
                    AND objectID = ?";
        $statement = WCF::getDB()->prepareStatement($sql);
        $statement->execute([$sessionID, $objectType, $objectID]);
        $existingReaction = $statement->fetchArray();

        if ($existingReaction) {
            if ($existingReaction['reactionTypeID'] == $reactionTypeID) {
                $sql = "DELETE FROM shrinkr" . WCF_N . "_guest_reaction
                        WHERE   guestReactionID = ?";
                $statement = WCF::getDB()->prepareStatement($sql);
                $statement->execute([$existingReaction['guestReactionID']]);
                $reactionTypeID = 0;
            } else {
                $sql = "UPDATE  shrinkr" . WCF_N . "_guest_reaction
                        SET     reactionTypeID = ?,
                                time = ?
                        WHERE   guestReactionID = ?";
                $statement = WCF::getDB()->prepareStatement($sql);
                $statement->execute([$reactionTypeID, TIME_NOW, $existingReaction['guestReactionID']]);
            }
        } else {
            GuestReactionEditor::create([
                'sessionID' => $sessionID,
                'objectType' => $objectType,
                'objectID' => $objectID,
                'reactionTypeID' => $reactionTypeID,
                'time' => TIME_NOW,
            ]);
        }

        $reactionData = $this->getReactionDataWithGuests($objectType, $objectID);

        $reflection = new \ReflectionClass($action);
        $returnValuesProperty = $reflection->getProperty('returnValues');
        $returnValuesProperty->setAccessible(true);
        $returnValuesProperty->setValue($action, [
            'reactions' => $reactionData['cachedReactions'],
            'objectID' => $objectID,
            'objectType' => $objectType,
            'reactionTypeID' => $reactionTypeID,
            'reputationCount' => $reactionData['cumulativeLikes'],
        ]);
    }

    /**
     * Gets reaction data including both user and guest reactions.
     * 
     * Retrieves regular user reactions from the LikeObject system and merges them
     * with guest reactions from the guest_reaction table.
     *
     * @param   string  $objectType  The object type identifier
     * @param   int     $objectID    The object ID to get reactions for
     * @return  array   Array containing cachedReactions and cumulativeLikes
     */
    protected function getReactionDataWithGuests($objectType, $objectID)
    {
        $objectTypeObj = \wcf\system\reaction\ReactionHandler::getInstance()->getObjectType($objectType);
        if ($objectTypeObj === null) {
            $cachedReactions = [];
            $cumulativeLikes = 0;
        } else {
            $likeObject = \wcf\data\like\object\LikeObject::getLikeObject($objectTypeObj->objectTypeID, $objectID);
            if ($likeObject === null) {
                $cachedReactions = [];
                $cumulativeLikes = 0;
            } else {
                $cachedReactions = $likeObject->getReactions();
                $cumulativeLikes = $likeObject->cumulativeLikes;
            }
        }

        $sql = "SELECT  reactionTypeID, COUNT(*) as count
                FROM    shrinkr" . WCF_N . "_guest_reaction
                WHERE   objectType = ?
                    AND objectID = ?
                GROUP BY reactionTypeID";
        $statement = WCF::getDB()->prepareStatement($sql);
        $statement->execute([$objectType, $objectID]);

        while ($row = $statement->fetchArray()) {
            $reactionTypeID = $row['reactionTypeID'];
            $count = $row['count'];

            $reactionType = \wcf\data\reaction\type\ReactionTypeCache::getInstance()->getReactionTypeByID($reactionTypeID);
            if ($reactionType === null) {
                continue;
            }

            if (!isset($cachedReactions[$reactionTypeID])) {
                $cachedReactions[$reactionTypeID] = [
                    'reactionCount' => 0,
                    'renderedReactionIcon' => $reactionType->renderIcon(),
                    'renderedReactionIconEncoded' => \wcf\util\JSON::encode($reactionType->renderIcon()),
                    'reactionTitle' => $reactionType->getTitle(),
                ];
            }

            $cachedReactions[$reactionTypeID]['reactionCount'] += $count;
            $cumulativeLikes += $count;
        }

        return [
            'cachedReactions' => $cachedReactions,
            'cumulativeLikes' => $cumulativeLikes,
        ];
    }
}
