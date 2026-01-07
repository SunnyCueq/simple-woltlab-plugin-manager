<?php

namespace urlshort\data\reaction;

use urlshort\data\guestreaction\GuestReactionEditor;
use wcf\data\option\Option;
use wcf\data\reaction\ReactionAction;
use wcf\system\exception\IllegalLinkException;
use wcf\system\exception\PermissionDeniedException;
use wcf\system\WCF;

/**
 * Extended ReactionAction that allows guests to react on URL shortener URLs.
 * Guest reactions are stored in the guest_reaction table instead of the standard like table.
 *
 * @author      Sunny C. <https://benjaro.info>
 * @copyright   2022-2025 Benjaro
 * @license     License for Commercial Plugins <https://benjaro.info>
 *
 * @package    dev.tkirch.wsc.urlshort
 * @subpackage data.reaction
 */
class GuestReactionAction extends ReactionAction
{
    /**
     * @inheritDoc
     */
    protected $allowGuestAccess = ['getReactionDetails', 'load', 'react'];

    /**
     * Flag to indicate if this is a guest reaction (stored in guest table)
     * @var bool
     */
    protected $isGuestReaction = false;

    /**
     * @inheritDoc
     */
    public function validateReact()
    {
        $this->readInteger('reactionTypeID', true); // allowEmpty = true because 0 is valid

        $reactionTypeID = isset($this->parameters['reactionTypeID']) ? (int)$this->parameters['reactionTypeID'] : 0;
        
        if ($reactionTypeID > 0) {
            $this->reactionType = \wcf\data\reaction\type\ReactionTypeCache::getInstance()->getReactionTypeByID($reactionTypeID);
            if (!$this->reactionType || !$this->reactionType->reactionTypeID) {
                throw new IllegalLinkException();
            }
        }

        // Check if guest reactions are enabled
        $guestReactionsOption = Option::getOptionByName('urlshort_featuredLinks_enableGuestReactions');
        $enableGuestReactions = $guestReactionsOption ? $guestReactionsOption->optionValue : 0;

        // For guests: Validate parameters manually before calling validateObjectParameters()
        // This is necessary because validateObjectParameters() may throw exceptions for guests
        if (!WCF::getUser()->userID && $enableGuestReactions) {
            // Ensure data array exists
            if (!isset($this->parameters['data'])) {
                $this->parameters['data'] = [];
            }
            
            // Try to read objectID and objectType from various possible locations
            if (!isset($this->parameters['data']['objectID'])) {
                // Try direct parameter
                if (isset($this->parameters['objectID'])) {
                    $this->parameters['data']['objectID'] = (int)$this->parameters['objectID'];
                } else {
                    // Try from objectIds array (used by dboAction)
                    if (!empty($this->objects)) {
                        $object = reset($this->objects);
                        $this->parameters['data']['objectID'] = $object->getObjectID();
                    } else {
                        throw new IllegalLinkException();
                    }
                }
            }
            
            if (!isset($this->parameters['data']['objectType'])) {
                // Try direct parameter
                if (isset($this->parameters['objectType'])) {
                    $this->parameters['data']['objectType'] = $this->parameters['objectType'];
                } else {
                    throw new IllegalLinkException();
                }
            }
            
            // Check if this is our object type
            if ($this->parameters['data']['objectType'] === 'dev.tkirch.wsc.urlshort.likeableUrl') {
                // Validate object parameters (now that we've ensured they exist)
                $this->validateObjectParameters();
                
                // Verify that the URL object exists and is valid
                $this->likeableObject = $this->objectTypeProvider->getObjectByID($this->parameters['data']['objectID']);
                if ($this->likeableObject === null || !$this->likeableObject->getObjectID()) {
                    throw new IllegalLinkException();
                }
                
                $this->likeableObject->setObjectType($this->objectType);
                
                // Mark as guest reaction
                $this->isGuestReaction = true;
                
                // Skip own content check for guests (they don't have userID)
                
                if ($this->objectTypeProvider instanceof \wcf\data\like\IRestrictedLikeObjectTypeProvider) {
                    if (!$this->objectTypeProvider->canLike($this->likeableObject)) {
                        throw new PermissionDeniedException();
                    }
                }
                
                // Skip isAssignable check for guests
                return;
            }
        }

        // For logged-in users or non-guest reactions: Validate object parameters normally
        $this->validateObjectParameters();

        // For non-guests, use standard validation
        if (!WCF::getUser()->userID || !WCF::getSession()->getPermission('user.like.canLike')) {
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

        if (!$this->reactionType->isAssignable) {
            // check, if the reaction is reverted
            $like = \wcf\data\like\Like::getLike(
                $this->likeableObject->getObjectType()->objectTypeID,
                $this->likeableObject->getObjectID(),
                WCF::getUser()->userID
            );

            if (!$like->likeID || $like->reactionTypeID !== $this->reactionType->reactionTypeID) {
                throw new IllegalLinkException();
            }
        }
    }

    /**
     * Handles guest reaction - stores in guest_reaction table instead of like table
     *
     * @return array
     */
    public function react()
    {
        // If this is a guest reaction, use custom logic
        if ($this->isGuestReaction) {
            return $this->reactAsGuest();
        }

        // For logged-in users: Use standard reaction logic, but include guest reactions in response
        $result = parent::react();
        
        // Add guest reactions to the response so the frontend shows all reactions
        $objectType = $this->parameters['data']['objectType'];
        $objectID = $this->likeableObject->getObjectID();
        $reactionDataWithGuests = $this->getReactionDataWithGuests($objectType, $objectID);
        
        // Replace reactions with combined data (normal + guest reactions)
        $result['reactions'] = $reactionDataWithGuests['cachedReactions'];
        $result['reputationCount'] = $reactionDataWithGuests['cumulativeLikes'];
        
        return $result;
    }

    /**
     * Handles guest reaction and stores it in guest_reaction table
     *
     * @return array
     */
    protected function reactAsGuest()
    {
        $sessionID = WCF::getSession()->sessionID;
        $objectType = 'dev.tkirch.wsc.urlshort.likeableUrl';
        $objectID = $this->parameters['data']['objectID'];
        $reactionTypeID = isset($this->parameters['reactionTypeID']) ? (int)$this->parameters['reactionTypeID'] : 0;

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
                // getReactions() gibt Objekte zurück, aber wir brauchen Zahlen für cachedReactions
                $reactions = $likeObject->getReactions();
                $cachedReactions = [];
                foreach ($reactions as $reactionTypeID => $reactionData) {
                    $cachedReactions[$reactionTypeID] = isset($reactionData['reactionCount']) ? $reactionData['reactionCount'] : 0;
                }
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
                $cachedReactions[$reactionTypeID] = 0;
            }

            $cachedReactions[$reactionTypeID] += $count;
            $cumulativeLikes += $count;
        }

        return [
            'cachedReactions' => $cachedReactions,
            'cumulativeLikes' => $cumulativeLikes,
        ];
    }

    /**
     * Gets guest reactions for multiple objects (batch operation)
     * [PERFORMANCE-OPTIMIERUNG] Loads all guest reactions in one query instead of N+1
     * Note: Regular reactions should be loaded separately using loadLikeObjects()
     *
     * @param string $objectType
     * @param array $objectIDs Array of object IDs
     * @return array Array indexed by objectID with guest reaction data only
     */
    public function getReactionDataWithGuestsBatch($objectType, array $objectIDs): array
    {
        if (empty($objectIDs)) {
            return [];
        }

        $result = [];
        
        // Initialize result array for all object IDs (only guest reactions)
        foreach ($objectIDs as $objectID) {
            $result[$objectID] = [
                'cachedReactions' => [],
                'cumulativeLikes' => 0,
            ];
        }

        // Get all guest reactions in one query
        $placeholders = str_repeat('?,', count($objectIDs) - 1) . '?';
        $sql = "SELECT  objectID, reactionTypeID, COUNT(*) as count
                FROM    urlshort" . WCF_N . "_guest_reaction
                WHERE   objectType = ?
                    AND objectID IN ({$placeholders})
                GROUP BY objectID, reactionTypeID";
        $statement = WCF::getDB()->prepareStatement($sql);
        $statement->execute(array_merge([$objectType], $objectIDs));

        while ($row = $statement->fetchArray()) {
            $objectID = $row['objectID'];
            $reactionTypeID = $row['reactionTypeID'];
            $count = $row['count'];

            if (!isset($result[$objectID])) {
                $result[$objectID] = [
                    'cachedReactions' => [],
                    'cumulativeLikes' => 0,
                ];
            }

            $reactionType = \wcf\data\reaction\type\ReactionTypeCache::getInstance()->getReactionTypeByID($reactionTypeID);
            if ($reactionType === null) {
                continue;
            }

            if (!isset($result[$objectID]['cachedReactions'][$reactionTypeID])) {
                $result[$objectID]['cachedReactions'][$reactionTypeID] = 0;
            }

            $result[$objectID]['cachedReactions'][$reactionTypeID] += $count;
            $result[$objectID]['cumulativeLikes'] += $count;
        }

        return $result;
    }
}

