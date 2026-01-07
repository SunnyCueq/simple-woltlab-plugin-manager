<?php

namespace urlshort\data\buttonclick;

use wcf\data\AbstractDatabaseObjectAction;
use wcf\system\WCF;

/**
 * Executes button click-related actions.
 *
 * @author      Sunny C. <https://benjaro.info>
 * @copyright   2022-2025 Benjaro
 * @license     License for Commercial Plugins <https://benjaro.info>
 *
 * @package    dev.tkirch.wsc.urlshort
 * @subpackage data.buttonclick
 *
 * @method      ButtonClickEditor[]    getObjects()
 * @method      ButtonClickEditor      getSingleObject()
 */
class ButtonClickAction extends AbstractDatabaseObjectAction
{
    /**
     * @inheritDoc
     */
    protected $className = ButtonClickEditor::class;

    /**
     * @inheritDoc
     */
    protected $permissionsDelete = ['admin.urlshort.canManageButtonClicks'];

    /**
     * @inheritDoc
     */
    protected $requireACP = ['delete'];

    /**
     * @inheritDoc
     */
    protected $allowGuestAccess = ['trackClick'];

    /**
     * Validates the trackClick action.
     */
    public function validateTrackClick()
    {
        $this->readInteger('urlID');
        $this->readString('buttonType');
        $this->readInteger('linkID', true); // optional
    }

    /**
     * Tracks a button click via AJAX.
     *
     * @return  array
     */
    public function trackClick()
    {
        $urlID = $this->parameters['urlID'];
        $buttonType = $this->parameters['buttonType'];
        $linkID = isset($this->parameters['linkID']) ? $this->parameters['linkID'] : null;

        $click = self::trackClickStatic($urlID, $buttonType, $linkID);

        return [
            'success' => true,
            'clickID' => $click->clickID,
        ];
    }

    /**
     * Tracks a button click (static method for direct calls).
     *
     * @param   int     $urlID      The URL ID
     * @param   string  $buttonType The button type ('forward', 'featured_link', 'custom')
     * @param   int|null $linkID    The link ID (for featured links)
     * @return  ButtonClick The created button click entry
     */
    public static function trackClickStatic(int $urlID, string $buttonType, ?int $linkID = null): ButtonClick
    {
        $userID = null;
        $sessionID = null;

        if (WCF::getUser()->userID) {
            $userID = WCF::getUser()->userID;
        } else {
            $sessionID = WCF::getSession()->sessionID;
        }

        $click = ButtonClickEditor::create([
            'urlID' => $urlID,
            'buttonType' => $buttonType,
            'linkID' => $linkID,
            'clickTime' => TIME_NOW,
            'userID' => $userID,
            'sessionID' => $sessionID,
        ]);

        return $click->getDecoratedObject();
    }
}

