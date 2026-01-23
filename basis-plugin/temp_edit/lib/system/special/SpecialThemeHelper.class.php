<?php

namespace shrinkr\system\special;

use shrinkr\data\theme\ThemeList;

/**
 * Helper class for managing special themes.
 * 
 * Provides static methods to retrieve theme data from the database or fallback
 * to default themes. Used by special events to get color schemes and theme
 * configurations.
 *
 * @author      Sunny C
 * @copyright   2026 Sunny C
 * @license     License for Commercial Plugins
 * @link        https://sunnyc.de
 * @package     de.sunnyc.wsc.shrinkr
 * @subpackage  system.special
 */
class SpecialThemeHelper
{
    /**
     * Returns all available themes with their colors from database.
     * 
     * Loads active themes from the database and returns them in a structured format.
     * Falls back to default themes if no themes are found in the database.
     *
     * @return  array   Array with structure: ['themeKey' => ['name' => 'Display Name', 'primaryColor' => '...', ...]]
     */
    public static function getThemes(): array
    {
        $themeList = new ThemeList();
        $themeList->getConditionBuilder()->add('isActive = ?', [1]);
        $themeList->sqlOrderBy = 'sortOrder ASC';
        $themeList->readObjects();

        $themes = [];
        foreach ($themeList as $theme) {
            $themes[$theme->identifier] = [
                'name' => $theme->title,
                'primaryColor' => $theme->primaryColor,
                'secondaryColor' => $theme->secondaryColor,
                'primaryTextColor' => $theme->primaryTextColor,
                'secondaryTextColor' => $theme->secondaryTextColor,
            ];
        }

        return !empty($themes) ? $themes : self::getDefaultThemes();
    }

    /**
     * Returns default theme configurations.
     * 
     * Provides fallback themes (Halloween, Black Week, Christmas) with predefined
     * color schemes when no themes are configured in the database.
     *
     * @return  array   Array of default theme configurations
     */
    private static function getDefaultThemes(): array
    {
        return [
            'halloween' => [
                'name' => 'Halloween',
                'primaryColor' => 'rgba(139, 0, 0, 1)',
                'secondaryColor' => 'rgba(255, 140, 0, 1)',
                'primaryTextColor' => 'rgba(255, 255, 255, 1)',
                'secondaryTextColor' => 'rgba(255, 255, 255, 1)',
            ],
            'blackweek' => [
                'name' => 'Black Week',
                'primaryColor' => 'rgba(0, 0, 0, 1)',
                'secondaryColor' => 'rgba(255, 215, 0, 1)',
                'primaryTextColor' => 'rgba(255, 255, 255, 1)',
                'secondaryTextColor' => 'rgba(0, 0, 0, 1)',
            ],
            'christmas' => [
                'name' => 'Weihnachten',
                'primaryColor' => 'rgba(220, 20, 60, 1)',
                'secondaryColor' => 'rgba(34, 139, 34, 1)',
                'primaryTextColor' => 'rgba(255, 255, 255, 1)',
                'secondaryTextColor' => 'rgba(255, 255, 255, 1)',
            ],
        ];
    }

    /**
     * Returns theme data for a specific theme key.
     *
     * @param string $themeKey
     * @return array|null Theme data or null if not found
     */
    public static function getTheme(string $themeKey): ?array
    {
        $themes = self::getThemes();
        return $themes[$themeKey] ?? null;
    }

    /**
     * Returns theme options for select field.
     * Sorted alphabetically (A-Z, German collation).
     * Empty option allows selecting "no theme".
     *
     * @return array Array with structure: ['themeKey' => 'Display Name']
     */
    public static function getThemeOptions(): array
    {
        // Always include empty option for "no theme"
        $options = [
            '' => 'Kein Theme (Standard Styling)'
        ];
        
        // Load themes from database
        $themeList = new ThemeList();
        $themeList->getConditionBuilder()->add('isActive = ?', [1]);
        $themeList->sqlOrderBy = 'title ASC';
        $themeList->readObjects();
        
        // Add themes sorted alphabetically
        foreach ($themeList as $theme) {
            $options[$theme->identifier] = $theme->title;
        }

        // Fallback to default themes if database is empty (but keep empty option)
        if (count($options) === 1) {
            $defaultThemes = self::getDefaultThemes();
            // Sort default themes alphabetically by name
            uasort($defaultThemes, function($a, $b) {
                return strcoll($a['name'], $b['name']);
            });
            foreach ($defaultThemes as $key => $theme) {
                $options[$key] = $theme['name'];
            }
        }

        return $options;
    }
}

