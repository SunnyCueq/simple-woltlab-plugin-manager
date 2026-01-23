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
 * @author      Sunny C
 * @copyright   2026 Sunny C
 * @license     License for Commercial Plugins
 *
 * @package    de.sunnyc.wsc.shrinkr
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

        // Use the action-specific method name pattern
        // IMPORTANT: We need to set allowGuestAccess BEFORE validateAction() checks it
        // So we hook into initializeAction which is called before validateAction
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
     * Called during initializeAction - we can modify allowGuestAccess here BEFORE validateAction() checks it
     */
    public function onInitializeActionReact(ReactionAction $action, array &$parameters)
    {
        // Only handle guests
        if (WCF::getUser()->userID) {
            return;
        }

        // Check if guest reactions are enabled
        $guestReactionsOption = Option::getOptionByName('shrinkr_enable_guest_reactions');
        $enableGuestReactions = $guestReactionsOption ? ($guestReactionsOption->optionValue == '1' || $guestReactionsOption->optionValue == 1) : false;

        if (!$enableGuestReactions) {
            return; // Let standard validation handle it (will fail for guests)
        }

        // Check if this is our object type
        if (!isset($action->parameters['data']['objectType']) ||
            $action->parameters['data']['objectType'] !== 'de.sunnyc.wsc.shrinkr.likeableShrinkrLink') {
            return; // Not our object type, let standard validation handle it
        }

        // Mark that we should handle this as guest reaction
        $action->parameters['_isGuestReaction'] = true;
        
        // Use reflection to set allowGuestAccess so validateAction() allows it
        $reflection = new \ReflectionClass($action);
        $allowGuestAccessProperty = $reflection->getProperty('allowGuestAccess');
        $allowGuestAccessProperty->setAccessible(true);
        $currentAccess = $allowGuestAccessProperty->getValue($action);
        if (!in_array('react', $currentAccess)) {
            $currentAccess[] = 'react';
            $allowGuestAccessProperty->setValue($action, $currentAccess);
        }
        
        // IMPORTANT: validateReact() still checks "if (!WCF::getUser()->userID || !WCF::getSession()->getPermission('user.like.canLike'))"
        // Even though allowGuestAccess is set, validateReact() will still throw an exception for guests.
        // We need to use reflection to create a wrapper method that intercepts validateReact() calls.
        // However, PHP doesn't allow runtime method replacement easily.
        // The solution: We need to catch the PermissionDeniedException and handle it in finalizeAction.
        // But if validateReact() throws, we never reach finalizeAction.
        // Actually, wait - if allowGuestAccess includes 'react', validateAction() passes the first check.
        // But validateReact() still does its own check. So we need to prevent that check.
        // The only way: Use a custom ReactionAction class that overrides validateReact().
        // But we can't do that via event listener. So we need a different approach.
        // Let me check if we can use a closure or runkit to modify the method...
        // Actually, the best approach: Create a custom ReactionAction class via object type provider.
        // But that's complex. Let's try a simpler approach: Use reflection to modify the method at runtime.
        // Actually, we can't modify methods at runtime in PHP easily.
        // The solution: We need to catch the exception in a try-catch in validateAction, but we can't do that.
        // Wait - maybe the old plugin uses a different approach. Let me check the JavaScript side...
        // Actually, the old plugin might intercept the AJAX call on the client side!
    }

    /**
     * Called during validateAction - this is called AFTER validateReact(), so we can't prevent the exception here
     * The actual work is done in onInitializeActionReact() which is called BEFORE validateAction()
     * But we still need to handle the case where validateReact() throws an exception for guests
     */
    public function onValidateActionReact(ReactionAction $action, array &$parameters)
    {
        // This is called AFTER validateReact(), so if validateReact() throws an exception,
        // we never get here. The actual work is done in onInitializeActionReact().
        // However, if validateReact() doesn't throw an exception (because allowGuestAccess was set),
        // we can still mark it as guest reaction here.
    }

    /**
     * Called after react() - we can override the return values
     */
    public function onFinalizeActionReact(ReactionAction $action, array &$parameters)
    {
        // Check if this should be handled as guest reaction
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
     * Handles guest reaction
     */
    protected function react(ReactionAction $action)
    {
        // Only handle if marked as guest reaction
        if (!isset($action->parameters['_isGuestReaction']) || !$action->parameters['_isGuestReaction']) {
            return;
        }

        $sessionID = WCF::getSession()->sessionID;
        $objectType = 'de.sunnyc.wsc.shrinkr.likeableShrinkrLink';
        $objectID = (int)$action->parameters['data']['objectID'];
        $reactionTypeID = (int)$action->parameters['reactionTypeID'];

        // Check if guest already reacted on this object
        $sql = "SELECT  guestReactionID, reactionTypeID
                FROM    shrinkr" . WCF_N . "_guest_reaction
                WHERE   sessionID = ?
                    AND objectType = ?
                    AND objectID = ?";
        $statement = WCF::getDB()->prepareStatement($sql);
        $statement->execute([$sessionID, $objectType, $objectID]);
        $existingReaction = $statement->fetchArray();

        if ($existingReaction) {
            // Check if same reaction type - if so, remove it (toggle)
            if ($existingReaction['reactionTypeID'] == $reactionTypeID) {
                // Remove reaction (toggle off)
                $sql = "DELETE FROM shrinkr" . WCF_N . "_guest_reaction
                        WHERE   guestReactionID = ?";
                $statement = WCF::getDB()->prepareStatement($sql);
                $statement->execute([$existingReaction['guestReactionID']]);
                $reactionTypeID = 0; // No reaction
            } else {
                // Update to new reaction type
                $sql = "UPDATE  shrinkr" . WCF_N . "_guest_reaction
                        SET     reactionTypeID = ?,
                                time = ?
                        WHERE   guestReactionID = ?";
                $statement = WCF::getDB()->prepareStatement($sql);
                $statement->execute([$reactionTypeID, TIME_NOW, $existingReaction['guestReactionID']]);
            }
        } else {
            // Create new reaction
            GuestReactionEditor::create([
                'sessionID' => $sessionID,
                'objectType' => $objectType,
                'objectID' => $objectID,
                'reactionTypeID' => $reactionTypeID,
                'time' => TIME_NOW,
            ]);
        }

        // Get reaction data including guest reactions
        $reactionData = $this->getReactionDataWithGuests($objectType, $objectID);

        // Override return values
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
     * Gets reaction data including guest reactions
     *
     * @param string $objectType
     * @param int $objectID
     * @return array
     */
    protected function getReactionDataWithGuests($objectType, $objectID)
    {
        // Get regular reactions
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

        // Get guest reactions
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
