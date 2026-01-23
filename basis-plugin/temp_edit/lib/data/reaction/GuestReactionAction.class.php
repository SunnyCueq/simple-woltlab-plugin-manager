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
 * 
 * This class extends the core ReactionAction to support guest reactions for Shr1nkr links.
 * It overrides validateReact() to allow guests to react when guest reactions are enabled,
 * and react() to handle both guest and logged-in user reactions, including guest reactions
 * in the response for logged-in users.
 *
 * @author      Sunny C
 * @copyright   2026 Sunny C
 * @license     License for Commercial Plugins
 * @link        https://sunnyc.de
 * @package     de.sunnyc.wsc.shrinkr
 * @subpackage  data.reaction
 */
class GuestReactionAction extends ReactionAction
{
    /**
     * Methods that are accessible for guests without authentication.
     *
     * @var    string[]
     */
    protected $allowGuestAccess = ['react', 'getReactionDetails', 'load'];

    /**
     * Validates the 'react' method for guests and logged-in users.
     * 
     * Overrides parent to allow guest reactions when enabled. For guests, skips permission
     * checks and own content validation. For logged-in users, uses standard validation.
     *
     * @throws  IllegalLinkException         If reaction type is invalid
     * @throws  PermissionDeniedException     If guest reactions are disabled for guests, or
     *                                        if user lacks permission, or if trying to react
     *                                        to own content, or if object type is not supported
     * @return  void
     */
    public function validateReact()
    {
        $this->validateObjectParameters();

        $this->readInteger('reactionTypeID', false);

        $this->reactionType = \wcf\data\reaction\type\ReactionTypeCache::getInstance()->getReactionTypeByID($this->parameters['reactionTypeID']);

        if (!$this->reactionType->reactionTypeID) {
            throw new IllegalLinkException();
        }

        $guestReactionsOption = Option::getOptionByName('shrinkr_enable_guest_reactions');
        $enableGuestReactions = $guestReactionsOption ? ($guestReactionsOption->optionValue == '1' || $guestReactionsOption->optionValue == 1) : false;

        if (!WCF::getUser()->userID) {
            if (!$enableGuestReactions) {
                throw new PermissionDeniedException();
            }

            if (!isset($this->parameters['data']['objectType']) ||
                $this->parameters['data']['objectType'] !== 'de.sunnyc.wsc.shrinkr.likeableShrinkrLink') {
                throw new PermissionDeniedException();
            }

            $this->likeableObject = $this->objectTypeProvider->getObjectByID($this->parameters['data']['objectID']);
            $this->likeableObject->setObjectType($this->objectType);
            return;
        }
        if (!WCF::getSession()->getPermission('user.like.canLike')) {
            throw new PermissionDeniedException();
        }

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
     * Handles the 'react' action for guests and logged-in users.
     * 
     * For guests, delegates to reactAsGuest(). For logged-in users, calls parent::react()
     * and then merges guest reaction data into the response for Shr1nkr link objects.
     * This ensures that both user and guest reactions are displayed together.
     *
     * @return  array   Response array containing:
     *                  - reactions: array<int, int> Map of reactionTypeID => count
     *                  - objectID: int The object ID
     *                  - objectType: string The object type
     *                  - reactionTypeID: int The selected reaction type ID
     *                  - reputationCount: int Total cumulative likes (user + guest)
     */
    public function react()
    {
        if (!WCF::getUser()->userID) {
            return $this->reactAsGuest();
        }

        $reactionData = parent::react();
        
        if (isset($this->parameters['data']['objectType']) && 
            $this->parameters['data']['objectType'] === 'de.sunnyc.wsc.shrinkr.likeableShrinkrLink') {
            $objectType = $this->parameters['data']['objectType'];
            $objectID = (int)$this->parameters['data']['objectID'];
            
            $combinedReactionData = $this->getReactionDataWithGuests($objectType, $objectID);
            
            $reactionsNumbers = [];
            foreach ($combinedReactionData['cachedReactions'] as $reactionTypeID => $reactionDataItem) {
                if (is_array($reactionDataItem) && isset($reactionDataItem['reactionCount'])) {
                    $reactionsNumbers[(int)$reactionTypeID] = (int)$reactionDataItem['reactionCount'];
                } else {
                    $reactionsNumbers[(int)$reactionTypeID] = is_numeric($reactionDataItem) ? (int)$reactionDataItem : 0;
                }
            }
            
            return [
                'reactions' => $reactionsNumbers,
                'objectID' => (int)$reactionData['objectID'],
                'objectType' => $reactionData['objectType'],
                'reactionTypeID' => (int)$reactionData['reactionTypeID'],
                'reputationCount' => (int)$combinedReactionData['cumulativeLikes'],
            ];
        }
        
        return $reactionData;
    }

    /**
     * Handles guest reaction creation, update, or removal.
     * 
     * Stores guest reactions in the database using session ID instead of user ID.
     * Supports toggle behavior: if the same reaction type is selected again, it is removed.
     * If a different reaction type is selected, the existing reaction is updated.
     *
     * @return  array   Response array containing:
     *                  - reactions: array<int, int> Map of reactionTypeID => count (combined user + guest)
     *                  - objectID: int The object ID
     *                  - objectType: string The object type
     *                  - reactionTypeID: int The selected reaction type ID (0 if removed)
     *                  - reputationCount: int Total cumulative likes (user + guest)
     */
    protected function reactAsGuest()
    {
        $sessionID = WCF::getSession()->sessionID;
        $objectType = 'de.sunnyc.wsc.shrinkr.likeableShrinkrLink';
        $objectID = (int)$this->parameters['data']['objectID'];
        $reactionTypeID = (int)$this->parameters['reactionTypeID'];

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

        // ReactionAction.react() returns $reactionData['cachedReactions'] from ReactionHandler.react(),
        // which is [reactionTypeID => count] (numbers only). The JavaScript/Frontend expects numbers
        // and converts them to the full structure internally (via REACTION_TYPES or similar).
        // We need to convert our full structure back to numbers to match the expected format.
        $reactionsNumbers = [];
        foreach ($reactionData['cachedReactions'] as $reactionTypeID => $reactionDataItem) {
            if (is_array($reactionDataItem) && isset($reactionDataItem['reactionCount'])) {
                $reactionsNumbers[(int)$reactionTypeID] = (int)$reactionDataItem['reactionCount'];
            } else {
                $reactionsNumbers[(int)$reactionTypeID] = is_numeric($reactionDataItem) ? (int)$reactionDataItem : 0;
            }
        }

        $returnData = [
            'reactions' => $reactionsNumbers,
            'objectID' => (int)$objectID,
            'objectType' => $objectType,
            'reactionTypeID' => (int)$reactionTypeID,
            'reputationCount' => (int)$reactionData['cumulativeLikes'],
        ];

        return $returnData;
    }

    /**
     * Gets reaction data including both user and guest reactions.
     * 
     * Retrieves regular user reactions from the LikeObject system and merges them
     * with guest reactions from the guest_reaction table. Returns a combined structure
     * with reaction counts, icons, and titles for all reaction types.
     *
     * @param   string  $objectType  The object type identifier (e.g., 'de.sunnyc.wsc.shrinkr.likeableShrinkrLink')
     * @param   int     $objectID    The object ID to get reactions for
     * @return  array   Array containing:
     *                  - cachedReactions: array<int, array> Map of reactionTypeID => [
     *                      'reactionCount' => int,
     *                      'renderedReactionIcon' => string,
     *                      'renderedReactionIconEncoded' => string,
     *                      'reactionTitle' => string
     *                    ]
     *                  - cumulativeLikes: int Total count of all reactions (user + guest)
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
