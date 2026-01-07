<?php

namespace urlshort\system\event\listener;

use urlshort\acp\page\UrlListPage;
use wcf\system\event\listener\AbstractEventListener;
use wcf\system\reaction\ReactionHandler;
use wcf\system\WCF;

/**
 * Event listener to add reaction data to UrlListPage.
 *
 * @author      Sunny C. <https://benjaro.info>
 * @copyright   2022-2025 Benjaro
 * @license     License for Commercial Plugins <https://benjaro.info>
 *
 * @package    info.benjaro.urlshort.affiliate
 * @subpackage system.event.listener
 */
class UrlListPageReactionListener extends AbstractEventListener
{
    /**
     * @inheritDoc
     */
    public function onAssignVariables(UrlListPage $page): void
    {
        // Load reaction data for all URLs
        $reactionData = [];
        if (MODULE_LIKE && isset($page->objectList) && !empty($page->objectList)) {
            $urlIDs = [];
            foreach ($page->objectList as $object) {
                if (!is_a($object, 'urlshort\data\url\Url')) {
                    $object = $object->getDecoratedObject();
                }
                if (isset($object->urlID) && $object->urlID) {
                    $urlIDs[] = $object->urlID;
                }
            }
            
            if (!empty($urlIDs)) {
                $objectType = ReactionHandler::getInstance()->getObjectType('info.benjaro.urlshort.affiliate.likeableUrl');
                if ($objectType !== null) {
                    ReactionHandler::getInstance()->loadLikeObjects($objectType, $urlIDs);
                    foreach ($urlIDs as $urlID) {
                        $likeObject = ReactionHandler::getInstance()->getLikeObject($objectType, $urlID);
                        
                        // Lade Reaktionen inkl. Gast-Reaktionen (getReactionDataWithGuests kombiniert bereits alles)
                        $guestReactionAction = new \urlshort\data\reaction\GuestReactionAction([], 'react');
                        $reactionDataWithGuests = $guestReactionAction->getReactionDataWithGuests('info.benjaro.urlshort.affiliate.likeableUrl', $urlID);
                        
                        // Erstelle Reaktions-Array mit Objekten (wie LikeObject->getReactions())
                        $reactions = [];
                        foreach ($reactionDataWithGuests['cachedReactions'] as $reactionTypeID => $count) {
                            $reactionType = \wcf\data\reaction\type\ReactionTypeCache::getInstance()->getReactionTypeByID($reactionTypeID);
                            if ($reactionType !== null) {
                                $reactions[$reactionTypeID] = [
                                    'reactionCount' => $count,
                                    'renderedReactionIcon' => $reactionType->renderIcon(),
                                    'renderedReactionIconEncoded' => \wcf\util\JSON::encode($reactionType->renderIcon()),
                                    'reactionTitle' => $reactionType->getTitle(),
                                ];
                            }
                        }
                        
                        // ReaktionTypeID vom LikeObject (falls vorhanden)
                        $reactionTypeID = ($likeObject !== null && isset($likeObject->reactionTypeID)) ? $likeObject->reactionTypeID : 0;
                        
                        // Erstelle Wrapper-Objekt
                        $wrapper = new class($reactions, $reactionDataWithGuests['cumulativeLikes'], $reactionTypeID) {
                            private $reactions;
                            private $cumulativeLikes;
                            private $reactionTypeID;
                            
                            public function __construct($reactions, $cumulativeLikes, $reactionTypeID) {
                                $this->reactions = $reactions;
                                $this->cumulativeLikes = $cumulativeLikes;
                                $this->reactionTypeID = $reactionTypeID;
                        }
                            
                            public function getReactions() {
                                return $this->reactions;
                            }
                            
                            public function getReactionsJson(): string {
                                $data = [];
                                foreach ($this->reactions as $reactionTypeID => $value) {
                                    $data[] = [
                                        $reactionTypeID, $value['reactionCount'],
                                    ];
                                }
                                return \wcf\util\JSON::encode($data);
                            }
                            
                            public function __get($name) {
                                if ($name === 'reactionTypeID') {
                                    return $this->reactionTypeID;
                                }
                                if ($name === 'cumulativeLikes') {
                                    return $this->cumulativeLikes;
                                }
                                return null;
                            }
                        };
                        
                        $reactionData[$urlID] = $wrapper;
                    }
                }
            }
        }

        // Assign REACTION_TYPES JavaScript variable
        $reactionTypesJS = '';
        if (MODULE_LIKE) {
            $reactionHandler = \wcf\system\reaction\ReactionHandler::getInstance();
            $reactionTypesJS = $reactionHandler->getReactionsJSVariable();
        }

        WCF::getTPL()->assign([
            'reactionData' => $reactionData,
            'reactionObjectType' => 'info.benjaro.urlshort.affiliate.likeableUrl',
            'reactionTypesJS' => $reactionTypesJS,
        ]);
    }
}

