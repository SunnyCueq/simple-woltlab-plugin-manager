<?php

/**
 * Executes actions for ShrinkrLink objects.
 *
 * @author      Sunny C
 * @copyright   2026 Sunny C
 * @license     License for Commercial Plugins
 * @link        https://sunnyc.de
 *
 * @package     de.sunnyc.wsc.shrinkr
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
     * @inheritDoc
     */
    protected $className = ShrinkrLinkEditor::class;
    
    /**
     * @inheritDoc
     */
    protected $permissionsDelete = ['admin.shrinkr.canManageLinks'];
    
    /**
     * Increases the click counter for the given link.
     *
     * @param ShrinkrLink $link The link whose counter should be increased
     * @return void
     */
    public static function increaseCounter(ShrinkrLink $link): void
    {
        if (SHRINKR_COUNTER_ACTIVE) {
            $editor = new ShrinkrLinkEditor($link);
            $editor->update(['counter' => ($link->counter + 1)]);
        }
    }
}
