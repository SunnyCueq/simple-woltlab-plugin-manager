<?php

namespace shrinkr\data\guestreaction;

use wcf\data\DatabaseObjectEditor;

/**
 * Provides functions to edit guest reactions.
 *
 * @author      Sunny C
 * @copyright   2026 Sunny C
 * @license     License for Commercial Plugins
 *
 * @package    de.sunnyc.wsc.shrinkr
 * @subpackage data.guestreaction
 */
class GuestReactionEditor extends DatabaseObjectEditor
{
    /**
     * @inheritDoc
     */
    protected static $baseClass = GuestReaction::class;
}

