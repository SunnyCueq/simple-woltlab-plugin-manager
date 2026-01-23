<?php

namespace shrinkr\data\buttonclick;

use wcf\data\AbstractDatabaseObjectAction;
use wcf\system\WCF;

/**
 * Executes button click-related actions.
 * 
 * Handles AJAX requests for tracking button clicks on shortened link pages.
 * Supports guest access for click tracking without authentication.
 *
 * @author      Sunny C
 * @copyright   2026 Sunny C
 * @license     License for Commercial Plugins
 * @link        https://sunnyc.de
 * @package     de.sunnyc.wsc.shrinkr
 * @subpackage  data.buttonclick
 *
 * @method      ButtonClickEditor[]    getObjects()
 * @method      ButtonClickEditor      getSingleObject()
 */
class ButtonClickAction extends AbstractDatabaseObjectAction
{
    /**
     * Editor class name for button clicks.
     *
     * @var    string
     */
    protected $className = ButtonClickEditor::class;

    /**
     * Required permissions for delete action.
     *
     * @var    string[]
     */
    protected $permissionsDelete = ['admin.shrinkr.canManageButtonClicks'];

    /**
     * Actions that require ACP access.
     *
     * @var    string[]
     */
    protected $requireACP = ['delete'];

    /**
     * Actions accessible for guests without authentication.
     *
     * @var    string[]
     */
    protected $allowGuestAccess = ['trackClick'];

    /**
     * Validates the trackClick action parameters.
     * 
     * Reads and validates linkID (required), buttonType (required), and optional
     * featured link ID from request parameters.
     *
     * @return  void
     */
    public function validateTrackClick()
    {
        $this->readInteger('linkID');
        $this->readString('buttonType');
        $this->readInteger('linkID', true);
    }

    /**
     * Tracks a button click via AJAX request.
     * 
     * Creates a new button click entry in the database and returns success status
     * with the created click ID.
     *
     * @return  array   Response array containing:
     *                  - success: bool Always true
     *                  - clickID: int The ID of the created click entry
     */
    public function trackClick()
    {
        $linkID = $this->parameters['linkID'];
        $buttonType = $this->parameters['buttonType'];
        $linkID = isset($this->parameters['linkID']) ? $this->parameters['linkID'] : null;

        $click = self::trackClickStatic($linkID, $buttonType, $linkID);

        return [
            'success' => true,
            'clickID' => $click->clickID,
        ];
    }

    /**
     * Tracks a button click (static method for direct calls).
     * 
     * Creates a button click entry in the database. Automatically determines user ID
     * (from current session) or session ID (for guests). Used internally by trackClick()
     * and can be called directly from other parts of the application.
     *
     * @param   int         $linkID       The shortened URL ID
     * @param   string      $buttonType   The button type ('forward', 'featured_link', 'custom')
     * @param   int|null    $linkID       The featured link ID (optional, only for 'featured_link' type)
     * @return  ButtonClick               The created button click entry
     */
    public static function trackClickStatic(int $linkID, string $buttonType, ?int $linkID = null): ButtonClick
    {
        $userID = null;
        $sessionID = null;

        if (WCF::getUser()->userID) {
            $userID = WCF::getUser()->userID;
        } else {
            $sessionID = WCF::getSession()->sessionID;
        }

        $click = ButtonClickEditor::create([
            'linkID' => $linkID,
            'buttonType' => $buttonType,
            'linkID' => $linkID,
            'clickTime' => TIME_NOW,
            'userID' => $userID,
            'sessionID' => $sessionID,
        ]);

        return $click->getDecoratedObject();
    }
}

