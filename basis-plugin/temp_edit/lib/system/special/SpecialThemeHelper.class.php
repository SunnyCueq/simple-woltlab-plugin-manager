<?php

namespace urlshort\system\special;

use urlshort\data\theme\ThemeList;

/**
 * Helper class for managing special themes.
 *
 * @author      Sunny C. <https://benjaro.info>
 * @copyright   2022-2025 Benjaro
 * @license     License for Commercial Plugins <https://benjaro.info>
 *
 * @package    dev.tkirch.wsc.urlshort
 * @subpackage system.special
 */
class SpecialThemeHelper
{
    /**
     * Returns all available themes with their colors from database.
     *
     * @return array Array with structure: ['themeKey' => ['name' => 'Display Name', 'primaryColor' => '...', ...]]
     */
    public static function getThemes(): array
    {
        // Load themes from database
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

        // Fallback to default themes if database is empty
        return !empty($themes) ? $themes : self::getDefaultThemes();
    }

    /**
     * Returns default themes.
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

