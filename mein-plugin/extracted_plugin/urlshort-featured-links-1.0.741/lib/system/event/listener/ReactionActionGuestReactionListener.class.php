<?php

namespace urlshort\system\event\listener;

use urlshort\data\guestreaction\GuestReactionEditor;
use wcf\data\option\Option;
use wcf\data\reaction\ReactionAction;
use wcf\system\event\listener\IParameterizedEventListener;
use wcf\system\exception\IllegalLinkException;
use wcf\system\exception\PermissionDeniedException;
use wcf\system\WCF;

/**
 * Event listener for ReactionAction to handle guest reactions.
 *
 * @author      Sunny C. <https://benjaro.info>
 * @copyright   2022-2025 Benjaro
 * @license     License for Commercial Plugins <https://benjaro.info>
 *
 * @package    info.benjaro.urlshort.affiliate
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
        if ($eventName === 'validateAction' && $eventObj->getActionName() === 'react') {
            $this->onValidateActionReact($eventObj, $parameters);
        }

        if ($eventName === 'finalizeAction' && $eventObj->getActionName() === 'react') {
            $this->onFinalizeActionReact($eventObj, $parameters);
        }
    }

    /**
     * Called after validateReact() - we can't prevent the exception, but we can catch it
     * Actually, this is called AFTER validateReact() throws an exception, so we can't help here
     * We need to use the extended class instead
     */
    public function onValidateActionReact(ReactionAction $action, array &$parameters)
    {
        // This is called AFTER validateReact(), so if validateReact() throws an exception,
        // we never get here. We need the extended class to override validateReact().
    }

    /**
     * Called after react() - we can override the return values
     */
    public function onFinalizeActionReact(ReactionAction $action, array &$parameters)
    {
        // Check if this should be handled as guest reaction
        if (!WCF::getUser()->userID && 
            isset($action->parameters['data']['objectType']) &&
            $action->parameters['data']['objectType'] === 'info.benjaro.urlshort.affiliate.likeableUrl') {
            
            $guestReactionsOption = Option::getOptionByName('urlshort_featuredLinks_enableGuestReactions');
            $enableGuestReactions = $guestReactionsOption ? $guestReactionsOption->optionValue : 0;
            
            if ($enableGuestReactions) {
                $this->react($action);
            }
        }
    }

    /**
     * Validates guest reaction - called AFTER validateReact() but we can prevent the exception
     * by using reflection to modify the action state
     */
    protected function validateReact(ReactionAction $action)
    {
        // Only handle guests
        if (WCF::getUser()->userID) {
            return;
        }

        // Check if guest reactions are enabled
        $guestReactionsOption = Option::getOptionByName('urlshort_featuredLinks_enableGuestReactions');
        $enableGuestReactions = $guestReactionsOption ? $guestReactionsOption->optionValue : 0;

        if (!$enableGuestReactions) {
            return; // Let standard validation handle it (will fail for guests)
        }

        // Check if this is our object type
        if (!isset($action->parameters['data']['objectType']) ||
            $action->parameters['data']['objectType'] !== 'info.benjaro.urlshort.affiliate.likeableUrl') {
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
        $objectType = 'info.benjaro.urlshort.affiliate.likeableUrl';
        $objectID = (int)$action->parameters['data']['objectID'];
        $reactionTypeID = (int)$action->parameters['reactionTypeID'];

        // Check if guest already reacted on this object
        $sql = "SELECT  guestReactionID, reactionTypeID
                FROM    urlshort" . WCF_N . "_guest_reaction
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
                $sql = "DELETE FROM urlshort" . WCF_N . "_guest_reaction
                        WHERE   guestReactionID = ?";
                $statement = WCF::getDB()->prepareStatement($sql);
                $statement->execute([$existingReaction['guestReactionID']]);
                $reactionTypeID = 0; // No reaction
            } else {
                // Update to new reaction type
                $sql = "UPDATE  urlshort" . WCF_N . "_guest_reaction
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
                FROM    urlshort" . WCF_N . "_guest_reaction
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

