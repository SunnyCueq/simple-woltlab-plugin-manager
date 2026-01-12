<?php

namespace shrinkr\data\theme;

use wcf\data\AbstractDatabaseObjectAction;
use wcf\system\exception\UserInputException;

/**
 * Executes theme-related actions.
 *
 * @author      Sunny C
 * @copyright   2026 Sunny C
 * @license     License for Commercial Plugins
 *
 * @package    de.sunnyc.wsc.shrinkr
 * @subpackage data.theme
 */
class ThemeAction extends AbstractDatabaseObjectAction
{
    private const ALLOWED_EFFECTS = ['none', 'autumnLeaves', 'snow', 'ghosts'];

    /**
     * @inheritDoc
     */
    protected $permissionsCreate = ['admin.shrinkr.canManageThemes'];

    /**
     * @inheritDoc
     */
    protected $permissionsUpdate = ['admin.shrinkr.canManageThemes'];

    /**
     * @inheritDoc
     */
    protected $permissionsDelete = ['admin.shrinkr.canManageThemes'];

    /**
     * @inheritDoc
     */
    public function create()
    {
        // Validate color values before creating
        $this->validateColors();

        // Validate identifier uniqueness
        $this->validateIdentifier();

        $this->validateEffectIdentifier();

        return parent::create();
    }

    /**
     * @inheritDoc
     */
    public function update()
    {
        // Validate color values before updating
        $this->validateColors();

        // Validate identifier uniqueness
        $this->validateIdentifier();

        $this->validateEffectIdentifier();

        parent::update();
    }

    /**
     * Validates color values in the parameters.
     *
     * @throws UserInputException If any color value is invalid
     */
    protected function validateColors(): void
    {
        if (!isset($this->parameters['data'])) {
            return;
        }

        $colorFields = [
            'primaryColor',
            'secondaryColor',
            'primaryTextColor',
            'secondaryTextColor'
        ];

        foreach ($colorFields as $field) {
            if (isset($this->parameters['data'][$field])) {
                $color = $this->parameters['data'][$field];

                if (!empty($color) && !Theme::isValidColor($color)) {
                    throw new UserInputException($field, 'invalid');
                }
            }
        }
    }

    /**
     * Validates that the identifier is unique.
     *
     * @throws UserInputException If identifier is not unique
     */
    protected function validateIdentifier(): void
    {
        if (!isset($this->parameters['data']['identifier'])) {
            return;
        }

        $identifier = $this->parameters['data']['identifier'];
        
        // Check if identifier already exists (excluding current theme in update)
        $sql = "SELECT  COUNT(*) as count
                FROM    shrinkr1_theme
                WHERE   identifier = ?";
        $params = [$identifier];

        // If updating, exclude current theme
        if (!empty($this->objects)) {
            $sql .= " AND themeID != ?";
            $params[] = $this->objects[0]->themeID;
        }

        $statement = \wcf\system\WCF::getDB()->prepareStatement($sql);
        $statement->execute($params);
        $row = $statement->fetchArray();

        if ($row['count'] > 0) {
            throw new UserInputException('identifier', 'notUnique');
        }
    }

    /**
     * Ensures the selected effect is supported.
     */
    private function validateEffectIdentifier(): void
    {
        if (!isset($this->parameters['data']['effectIdentifier'])) {
            return;
        }

        $effectIdentifier = $this->parameters['data']['effectIdentifier'];
        if (!\in_array($effectIdentifier, self::ALLOWED_EFFECTS, true)) {
            throw new UserInputException('effectIdentifier', 'invalid');
        }
    }
}

