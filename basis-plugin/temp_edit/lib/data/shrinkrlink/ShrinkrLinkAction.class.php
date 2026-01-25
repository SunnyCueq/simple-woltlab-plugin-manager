<?php

/**
 * Executes actions for ShrinkrLink objects.
 * 
 * Action class for performing operations on ShrinkrLink database objects.
 * Handles AJAX requests for link management and provides static methods
 * for click counter updates.
 *
 * @author      Sunny C
 * @copyright   2026 Sunny C
 * @license     License for Commercial Plugins
 * @link        https://sunnyc.de
 * @package     de.sunnyc.wsc.shrinkr
 * @subpackage  data.shrinkrlink
 */

namespace shrinkr\data\shrinkrlink;

use wcf\data\AbstractDatabaseObjectAction;
use wcf\data\option\Option;
use wcf\system\exception\IllegalLinkException;
use wcf\system\exception\PermissionDeniedException;
use wcf\system\exception\UserInputException;
use wcf\system\WCF;

/**
 * Action class for ShrinkrLink database objects.
 */
class ShrinkrLinkAction extends AbstractDatabaseObjectAction
{
    /**
     * Editor class name for ShrinkrLink objects.
     *
     * @var    string
     */
    protected $className = ShrinkrLinkEditor::class;
    
    /**
     * Required permissions for delete action.
     *
     * @var    string[]
     */
    protected $permissionsDelete = ['admin.shrinkr.canManageLinks'];
    
    /**
     * @deprecated No longer used - visit tracking is now handled via statistics system
     * Increases the click counter for the given link.
     * 
     * This method is deprecated and no longer functional. Visit tracking is now
     * handled by the RedirectPageVisitTrackerListener and stored in the
     * shrinkr1_statistic_visit table.
     *
     * @param   ShrinkrLink  $link  The link whose counter should be increased
     * @return  void
     */
    public static function increaseCounter(ShrinkrLink $link): void
    {
        // Deprecated: Counter field removed from database
        // Visit tracking is now handled by RedirectPageVisitTrackerListener
    }
}
