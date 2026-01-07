<?php

namespace urlshort\data\url\reaction;

use urlshort\data\guestreaction\GuestReactionEditor;
use wcf\data\AbstractDatabaseObjectAction;
use wcf\data\option\Option;
use wcf\system\exception\IllegalLinkException;
use wcf\system\exception\PermissionDeniedException;
use wcf\system\WCF;

/**
 * Handles guest reactions for URL shortener URLs.
 *
 * @author      Sunny C. <https://benjaro.info>
 * @copyright   2022-2025 Benjaro
 * @license     License for Commercial Plugins <https://benjaro.info>
 *
 * @package    dev.tkirch.wsc.urlshort
 * @subpackage data.url.reaction
 */
class GuestUrlReactionAction extends AbstractDatabaseObjectAction
{
    /**
     * @inheritDoc
     */
    protected $className = GuestReactionEditor::class;

    /**
     * @inheritDoc
     */
    protected $allowGuestAccess = ['react'];

    /**
     * Validates the react action for guests
     */
    public function validateReact()
    {
        // Check if guest reactions are enabled
        $guestReactionsOption = Option::getOptionByName('urlshort_featuredLinks_enableGuestReactions');
        $enableGuestReactions = $guestReactionsOption ? $guestReactionsOption->optionValue : 0;

        if (!$enableGuestReactions) {
            throw new PermissionDeniedException();
        }

        // Only allow guests
        if (WCF::getUser()->userID) {
            throw new PermissionDeniedException();
        }

        // Read reactionTypeID - allowEmpty = true because 0 is valid (remove reaction)
        // Check if it exists, if not try to read it (might be set to default 0)
        if (!isset($this->parameters['reactionTypeID'])) {
            // Try to read it anyway - readInteger with allowEmpty=true will set it to 0 if missing
            $this->readInteger('reactionTypeID', true);
        } else {
            $this->readInteger('reactionTypeID', true); // true = allowEmpty, because 0 is valid
        }
        
        // Read objectID - support both direct parameter and data.objectID (for consistency with standard ReactionAction)
        $objectID = null;
        if (isset($this->parameters['data']['objectID'])) {
            $this->readInteger('objectID', false, 'data');
            $objectID = $this->parameters['data']['objectID'];
        } elseif (isset($this->parameters['objectID'])) {
            $this->readInteger('objectID', false);
            $objectID = $this->parameters['objectID'];
        }

        if ($objectID === null) {
            throw new IllegalLinkException();
        }

        // Validate reaction type (0 = remove reaction is allowed)
        $reactionTypeID = isset($this->parameters['reactionTypeID']) ? (int)$this->parameters['reactionTypeID'] : 0;
        if ($reactionTypeID > 0) {
            $reactionType = \wcf\data\reaction\type\ReactionTypeCache::getInstance()->getReactionTypeByID($reactionTypeID);
            if (!$reactionType || !$reactionType->reactionTypeID) {
                throw new IllegalLinkException();
            }
        }

        // Validate URL object exists
        $url = new \urlshort\data\url\Url($objectID);
        if (!$url->urlID) {
            throw new IllegalLinkException();
        }
    }

    /**
     * Handles guest reaction
     */
    public function react()
    {
        $sessionID = WCF::getSession()->sessionID;
        $objectType = 'dev.tkirch.wsc.urlshort.likeableUrl';
        
        // Get objectID from either location
        $objectID = isset($this->parameters['data']['objectID']) 
            ? $this->parameters['data']['objectID'] 
            : $this->parameters['objectID'];
        $reactionTypeID = $this->parameters['reactionTypeID'];

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

        return [
            'reactions' => $reactionData['cachedReactions'],
            'objectID' => $objectID,
            'objectType' => $objectType,
            'reactionTypeID' => $reactionTypeID,
            'reputationCount' => $reactionData['cumulativeLikes'],
        ];
    }

    /**
     * Gets reaction data including guest reactions
     */
    private function getReactionDataWithGuests($objectType, $objectID)
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
