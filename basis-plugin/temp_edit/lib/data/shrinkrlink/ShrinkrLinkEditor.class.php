<?php

/**
 * Provides functions to edit ShrinkrLink objects.
 *
 * @author      Sunny C
 * @copyright   2026 Sunny C
 * @license     License for Commercial Plugins
 * @link        https://sunnyc.de
 *
 * @package     de.sunnyc.wsc.shrinkr
 */

namespace shrinkr\data\shrinkrlink;

use wcf\data\DatabaseObjectEditor;

/**
 * Editor class for ShrinkrLink database objects.
 */
class ShrinkrLinkEditor extends DatabaseObjectEditor
{
    /**
     * @inheritDoc
     */
    protected static $baseClass = ShrinkrLink::class;

    /**
     * @inheritDoc
     */
    public static function create(array $parameters = [])
    {
        return parent::create($parameters);
    }

    /**
     * @inheritDoc
     */
    public function update(array $parameters = [])
    {
        parent::update($parameters);
    }
}
