<?php

namespace shrinkr\data\description;

use wcf\data\DatabaseObject;
use wcf\data\ITitledObject;
use wcf\system\WCF;

/**
 * Represents a redirect description.
 * 
 * Database object for description texts displayed on shortened link redirect pages.
 * Supports multilingual content via I18n and Smarty template variables for dynamic
 * content (e.g., [[FAV_URL_LINK]] placeholder). Implements ITitledObject for
 * moderation queue compatibility.
 *
 * @author      Sunny C
 * @copyright   2026 Sunny C
 * @license     License for Commercial Plugins
 * @link        https://sunnyc.de
 * @package     de.sunnyc.wsc.shrinkr
 * @subpackage  data.description
 *
 * @property-read int    $descriptionID      Unique ID of the description
 * @property-read string $title              Internal title (ACP only, not multilingual)
 * @property-read string $descriptionText    Description text (multilingual via I18n)
 * @property-read int    $isActive           Active status (1 = active, 0 = inactive)
 */
class Description extends DatabaseObject implements ITitledObject
{
    /**
     * Returns the title (for ACP list view).
     * 
     * Implements ITitledObject interface.
     *
     * @return  string  The internal title
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * Checks if the current user can add descriptions.
     *
     * @return  bool    True if user has admin.shrinkr.canManageDescriptions permission
     */
    public function canAdd(): bool
    {
        return WCF::getSession()->getPermission('admin.shrinkr.canManageDescriptions');
    }

    /**
     * Checks if the current user can edit this description.
     *
     * @return  bool    True if user has admin.shrinkr.canManageDescriptions permission
     */
    public function canEdit(): bool
    {
        return $this->canAdd();
    }

    /**
     * Checks if the current user can delete this description.
     *
     * @return  bool    True if user has admin.shrinkr.canManageDescriptions permission
     */
    public function canDelete(): bool
    {
        return $this->canAdd();
    }

    /**
     * Returns the description text with compiled Smarty variables.
     * 
     * Processes the description text and replaces placeholders like [[FAV_URL_LINK]]
     * with rendered Smarty templates. Supports dynamic content via template variables.
     *
     * @param   array   $variables  Template variables for dynamic content
     * @return  string              Rendered description text
     */
    public function getDescriptionText(array $variables = []): string
    {
        if (empty($this->descriptionText)) {
            return '';
        }

        // Convert placeholder to complete Smarty link with favicon + title
        $text = $this->descriptionText;
        // WoltLab Smarty doesn't support 'default' modifier, use {if} instead
        // {$url} is now a string (full URL), not an object
        // Check both discount and special for favicon
        $linkCode = '<a href="{$url}">{if $special|isset && $special->getImageTag(16, $url)}{unsafe:$special->getImageTag(16, $url)} {elseif $discount|isset && $discount->getImageTag(16, $url)}{unsafe:$discount->getImageTag(16, $url)} {/if}{$extractedTitle}</a>';
        $text = str_replace('[[FAV_URL_LINK]]', $linkCode, $text);

        // Compile and execute description text as Smarty template
        $tpl = WCF::getTPL();
        $compiler = $tpl->getCompiler();

        // Compile the Smarty source code to PHP
        $compiled = $compiler->compileString(
            'description_' . $this->descriptionID,
            $text,
            ['application' => 'shrinkr', 'data' => null, 'filename' => ''],
            true // isolated compilation
        );

        // Check if compilation was successful
        if (!isset($compiled['template']) || empty($compiled['template'])) {
            // Compilation failed, return original text
            return $text;
        }

        // Execute the compiled PHP code with variables
        // fetchString expects compiled PHP code, not raw Smarty code
        return $tpl->fetchString($compiled['template'], $variables);
    }

    /**
     * Returns true, if description is active.
     */
    public function isActive(): bool
    {
        return (bool) $this->isActive;
    }

    /**
     * __toString() implementation.
     */
    public function __toString(): string
    {
        return $this->getTitle();
    }
}
