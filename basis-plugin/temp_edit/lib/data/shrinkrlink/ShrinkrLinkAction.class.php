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

/**
 * Action class for ShrinkrLink database objects.
 */
class ShrinkrLinkAction extends AbstractDatabaseObjectAction
{
    /**
     * @inheritDoc
     */
    protected $permissionsDelete = ['admin.shrinkr.canManageLinks'];
    
    /**
     * Increases the click counter for the given link.
     */
    public static function increaseCounter(ShrinkrLink $link): void
    {
        if (SHRINKR_COUNTER_ACTIVE) {
            $editor = new ShrinkrLinkEditor($link);
            $editor->update(['counter' => ($link->counter + 1)]);
        }
    }

    /**
     * Alias for increaseCounter for backwards compatibility.
     * @deprecated Use increaseCounter() instead
     */
    public static function increaseCounter($link): void
    {
        if ($link instanceof ShrinkrLink) {
            self::increaseCounter($link);
        }
    }
}
