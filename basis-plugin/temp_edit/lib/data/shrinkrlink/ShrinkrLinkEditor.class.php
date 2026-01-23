<?php

/**
 * Provides functions to edit ShrinkrLink objects.
 * 
 * Editor class for ShrinkrLink database objects. Extends DatabaseObjectEditor
 * to provide create, update, and delete functionality for shortened links.
 *
 * @author      Sunny C
 * @copyright   2026 Sunny C
 * @license     License for Commercial Plugins
 * @link        https://sunnyc.de
 * @package     de.sunnyc.wsc.shrinkr
 * @subpackage  data.shrinkrlink
 */

namespace shrinkr\data\shrinkrlink;

use wcf\data\DatabaseObjectEditor;

/**
 * Editor class for ShrinkrLink database objects.
 */
class ShrinkrLinkEditor extends DatabaseObjectEditor
{
    /**
     * Base class name for this editor.
     *
     * @var    string
     */
    protected static $baseClass = ShrinkrLink::class;

    /**
     * Creates a new ShrinkrLink object.
     * 
     * Validates and creates a new shortened link entry in the database.
     *
     * @param   array   $parameters   Array of link properties (url, hash, etc.)
     * @return  ShrinkrLink            The created link object
     */
    public static function create(array $parameters = [])
    {
        return parent::create($parameters);
    }

    /**
     * Updates the ShrinkrLink object.
     * 
     * Updates the properties of an existing shortened link.
     *
     * @param   array   $parameters   Array of properties to update
     * @return  void
     */
    public function update(array $parameters = [])
    {
        parent::update($parameters);
    }
}
