<?php

namespace urlshort\system\reaction\action;

use urlshort\data\guestreaction\GuestReactionEditor;
use wcf\data\like\object\LikeObject;
use wcf\data\option\Option;
use wcf\data\reaction\ReactionAction;
use wcf\data\reaction\type\ReactionTypeCache;
use wcf\system\reaction\ReactionHandler;
use wcf\system\WCF;
use wcf\util\JSON;

/**
 * Handles guest reactions for ReactionAction.
 * 
 *
 * @author      Sunny C. <https://benjaro.info>
 * @copyright   2022-2025 Benjaro
 * @license     License for Commercial Plugins <https://benjaro.info>
 *
 * @package    dev.tkirch.wsc.urlshort
 * @subpackage system.reaction.action
 */
class ReactionActionGuestReactionHandler
{
    /**
     * Object type for likeable URLs
     */
    private const OBJECT_TYPE = 'dev.tkirch.wsc.urlshort.likeableUrl';

    /**
     * Handles the ReactionAction::validateAction event for react action.
     * 
     * Note: This is called AFTER validateReact(), so if validateReact() throws an exception,
     * we never get here. We use reflection to modify the action state before validation.
     *
     * @param ReactionAction $action The reaction action
     * @param array $parameters Event parameters
     */
    public function handleValidateAction(ReactionAction $action, array &$parameters): void
    {
        // This is called AFTER validateReact(), so if validateReact() throws an exception,
        // we never get here. We need to use reflection to modify the action state before validation.
        // However, since we can't hook into validateReact() directly, we handle it in finalizeAction instead.
    }

    /**
     * Handles the ReactionAction::finalizeAction event for react action.
     * 
     *
     * @param ReactionAction $action The reaction action
     * @param array $parameters Event parameters
     */
    public function handleFinalizeAction(ReactionAction $action, array &$parameters): void
    {
        // Check if this should be handled as guest reaction
        if (!WCF::getUser()->userID && 
            isset($action->parameters['data']['objectType']) &&
            $action->parameters['data']['objectType'] === self::OBJECT_TYPE) {
            
            $guestReactionsOption = Option::getOptionByName('urlshort_featuredLinks_enableGuestReactions');
            $enableGuestReactions = $guestReactionsOption ? $guestReactionsOption->optionValue : 0;
            
            if ($enableGuestReactions) {
                $this->react($action);
            }
        }
    }

    /**
     * Validates guest reaction - uses reflection to modify the action state.
     * 
     * Note: This method is not directly called by the event system, but can be used
     * if we need to modify the action before validation.
     *
     * @param ReactionAction $action The reaction action
     */
    public function validateReact(ReactionAction $action): void
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
            $action->parameters['data']['objectType'] !== self::OBJECT_TYPE) {
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
     * Handles guest reaction.
     * 
     *
     * @param ReactionAction $action The reaction action
     */
    protected function react(ReactionAction $action): void
    {
        // Only handle if marked as guest reaction
        if (!isset($action->parameters['_isGuestReaction']) || !$action->parameters['_isGuestReaction']) {
            return;
        }

        $sessionID = WCF::getSession()->sessionID;
        $objectType = self::OBJECT_TYPE;
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
     * Gets reaction data including guest reactions.
     * 
     *
     * @param string $objectType
     * @param int $objectID
     * @return array
     */
    protected function getReactionDataWithGuests($objectType, $objectID)
    {
        // Get regular reactions
        $objectTypeObj = ReactionHandler::getInstance()->getObjectType($objectType);
        if ($objectTypeObj === null) {
            $cachedReactions = [];
            $cumulativeLikes = 0;
        } else {
            $likeObject = LikeObject::getLikeObject($objectTypeObj->objectTypeID, $objectID);
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

            $reactionType = ReactionTypeCache::getInstance()->getReactionTypeByID($reactionTypeID);
            if ($reactionType === null) {
                continue;
            }

            if (!isset($cachedReactions[$reactionTypeID])) {
                $cachedReactions[$reactionTypeID] = [
                    'reactionCount' => 0,
                    'renderedReactionIcon' => $reactionType->renderIcon(),
                    'renderedReactionIconEncoded' => JSON::encode($reactionType->renderIcon()),
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
