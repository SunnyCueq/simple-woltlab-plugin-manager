<?php

namespace shrinkr\data\reaction;

use shrinkr\data\guestreaction\GuestReactionEditor;
use shrinkr\system\event\listener\ReactionActionGuestReactionListener;
use wcf\data\option\Option;
use wcf\data\reaction\ReactionAction;
use wcf\system\exception\IllegalLinkException;
use wcf\system\exception\PermissionDeniedException;
use wcf\system\WCF;

/**
 * Custom ReactionAction for handling guest reactions.
 * This class overrides validateReact() to allow guests to react.
 *
 * @author      Sunny C
 * @copyright   2026 Sunny C
 * @license     License for Commercial Plugins
 *
 * @package    de.sunnyc.wsc.shrinkr
 * @subpackage data.reaction
 */
class GuestReactionAction extends ReactionAction
{
    /**
     * @inheritDoc
     */
    protected $allowGuestAccess = ['react', 'getReactionDetails', 'load'];

    /**
     * Validates the 'react' method for guests.
     * Overrides parent to allow guest reactions.
     */
    public function validateReact()
    {
        $this->validateObjectParameters();

        $this->readInteger('reactionTypeID', false);

        $this->reactionType = \wcf\data\reaction\type\ReactionTypeCache::getInstance()->getReactionTypeByID($this->parameters['reactionTypeID']);

        if (!$this->reactionType->reactionTypeID) {
            throw new IllegalLinkException();
        }

        // Check if guest reactions are enabled
        $guestReactionsOption = Option::getOptionByName('shrinkr_enable_guest_reactions');
        $enableGuestReactions = $guestReactionsOption ? ($guestReactionsOption->optionValue == '1' || $guestReactionsOption->optionValue == 1) : false;

        // For guests: only allow if guest reactions are enabled
        if (!WCF::getUser()->userID) {
            if (!$enableGuestReactions) {
                throw new PermissionDeniedException();
            }

            // Check if this is our object type
            if (!isset($this->parameters['data']['objectType']) ||
                $this->parameters['data']['objectType'] !== 'de.sunnyc.wsc.shrinkr.likeableShrinkrLink') {
                throw new PermissionDeniedException();
            }

            // For guests, we skip the permission check and own content check
            $this->likeableObject = $this->objectTypeProvider->getObjectByID($this->parameters['data']['objectID']);
            $this->likeableObject->setObjectType($this->objectType);
            return; // Skip further validation for guests
        }

        // For logged-in users: use standard validation
        if (!WCF::getSession()->getPermission('user.like.canLike')) {
            throw new PermissionDeniedException();
        }

        // check if liking own content but forbidden by configuration
        $this->likeableObject = $this->objectTypeProvider->getObjectByID($this->parameters['data']['objectID']);
        $this->likeableObject->setObjectType($this->objectType);
        if ($this->likeableObject->getUserID() == WCF::getUser()->userID) {
            throw new PermissionDeniedException();
        }

        if ($this->objectTypeProvider instanceof \wcf\data\like\IRestrictedLikeObjectTypeProvider) {
            if (!$this->objectTypeProvider->canLike($this->likeableObject)) {
                throw new PermissionDeniedException();
            }
        }
    }

    /**
     * Handles the 'react' action for guests and users.
     * Overrides parent to handle guest reactions in database and include them for users too.
     */
    public function react()
    {
        // For guests: handle guest reactions
        if (!WCF::getUser()->userID) {
            return $this->reactAsGuest();
        }

        // For logged-in users: use standard reaction handling BUT include guest reactions in response
        $reactionData = parent::react();
        
        // Get reaction data including guest reactions for our object type
        if (isset($this->parameters['data']['objectType']) && 
            $this->parameters['data']['objectType'] === 'de.sunnyc.wsc.shrinkr.likeableShrinkrLink') {
            $objectType = $this->parameters['data']['objectType'];
            $objectID = (int)$this->parameters['data']['objectID'];
            
            // Get combined reaction data (user + guest reactions)
            $combinedReactionData = $this->getReactionDataWithGuests($objectType, $objectID);
            
            // Convert full structure to numbers array (like parent::react() does)
            $reactionsNumbers = [];
            foreach ($combinedReactionData['cachedReactions'] as $reactionTypeID => $reactionDataItem) {
                if (is_array($reactionDataItem) && isset($reactionDataItem['reactionCount'])) {
                    $reactionsNumbers[$reactionTypeID] = $reactionDataItem['reactionCount'];
                } else {
                    $reactionsNumbers[$reactionTypeID] = is_numeric($reactionDataItem) ? $reactionDataItem : 0;
                }
            }
            
            // Return with combined reactions
            return [
                'reactions' => $reactionsNumbers,
                'objectID' => $reactionData['objectID'],
                'objectType' => $reactionData['objectType'],
                'reactionTypeID' => $reactionData['reactionTypeID'],
                'reputationCount' => $combinedReactionData['cumulativeLikes'],
            ];
        }
        
        // For other object types, return standard response
        return $reactionData;
    }

    /**
     * Handles guest reaction.
     *
     * @return array
     */
    protected function reactAsGuest()
    {
        $sessionID = WCF::getSession()->sessionID;
        $objectType = 'de.sunnyc.wsc.shrinkr.likeableShrinkrLink';
        $objectID = (int)$this->parameters['data']['objectID'];
        $reactionTypeID = (int)$this->parameters['reactionTypeID'];

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


        // ReactionAction.react() returns $reactionData['cachedReactions'] from ReactionHandler.react(),
        // which is [reactionTypeID => count] (numbers only). The JavaScript/Frontend expects numbers
        // and converts them to the full structure internally (via REACTION_TYPES or similar).
        // We need to convert our full structure back to numbers to match the expected format.
        $reactionsNumbers = [];
        foreach ($reactionData['cachedReactions'] as $reactionTypeID => $reactionDataItem) {
            if (is_array($reactionDataItem) && isset($reactionDataItem['reactionCount'])) {
                $reactionsNumbers[$reactionTypeID] = $reactionDataItem['reactionCount'];
            } else {
                // Fallback: if it's already a number, use it directly
                $reactionsNumbers[$reactionTypeID] = is_numeric($reactionDataItem) ? $reactionDataItem : 0;
            }
        }

        $returnData = [
            'reactions' => $reactionsNumbers,
            'objectID' => $objectID,
            'objectType' => $objectType,
            'reactionTypeID' => $reactionTypeID,
            'reputationCount' => $reactionData['cumulativeLikes'],
        ];

        return $returnData;
    }

    /**
     * Gets reaction data including guest reactions
     *
     * @param string $objectType
     * @param int $objectID
     * @return array
     */
    public function getReactionDataWithGuests($objectType, $objectID)
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
