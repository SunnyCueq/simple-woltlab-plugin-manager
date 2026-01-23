<?php

namespace shrinkr\data\theme;

use wcf\data\DatabaseObject;
use wcf\data\ITitledObject;
use wcf\system\WCF;

/**
 * Represents a theme object with color scheme and visual effects.
 * 
 * Database object for themes that can be applied to shortened link redirect pages.
 * Themes define color schemes, visual effects (e.g., snow, autumn leaves), and
 * custom CSS. Implements ITitledObject for moderation queue compatibility.
 *
 * @author      Sunny C
 * @copyright   2026 Sunny C
 * @license     License for Commercial Plugins
 * @link        https://sunnyc.de
 * @package     de.sunnyc.wsc.shrinkr
 * @subpackage  data.theme
 *
 * @property-read int    $themeID            Unique ID of the theme
 * @property-read string $identifier         Theme identifier (e.g., 'halloween', 'blackweek')
 * @property-read string $title              Display title of the theme
 * @property-read string $effectIdentifier   Visual effect identifier (e.g., 'snow', 'autumnLeaves')
 * @property-read string $primaryColor       Primary background color (RGBA format)
 * @property-read string $secondaryColor     Secondary background color (RGBA format)
 * @property-read string $primaryTextColor   Primary text color (RGBA format)
 * @property-read string $secondaryTextColor Secondary text color (RGBA format)
 * @property-read int    $isActive           Active status (1 = active, 0 = inactive)
 * @property-read int    $sortOrder          Sort order for display (lower = higher priority)
 * @property-read string $cssContent         Custom CSS content for the theme
 */
class Theme extends DatabaseObject implements ITitledObject
{
    /**
     * Returns the title of the theme.
     * 
     * Implements ITitledObject interface.
     *
     * @return  string  The theme title
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * Returns the theme title as string representation.
     *
     * @return  string  The theme title
     */
    public function __toString(): string
    {
        return $this->getTitle();
    }

    /**
     * Checks if the current user can add themes.
     *
     * @return  bool    True if user has admin.shrinkr.canManageThemes permission
     */
    public function canAdd(): bool
    {
        return WCF::getSession()->getPermission('admin.shrinkr.canManageThemes');
    }

    /**
     * Checks if the current user can edit this theme.
     *
     * @return  bool    True if user has admin.shrinkr.canManageThemes permission
     */
    public function canEdit(): bool
    {
        return $this->canAdd();
    }

    /**
     * Checks if the current user can delete this theme.
     *
     * @return  bool    True if user has admin.shrinkr.canManageThemes permission
     */
    public function canDelete(): bool
    {
        return $this->canAdd();
    }

    /**
     * Validates if the given color string is a safe rgba/rgb value.
     * 
     * Checks that the color matches a valid CSS rgba() or rgb() format
     * to prevent XSS attacks through malicious color values.
     *
     * @param   string  $color  The color value to validate
     * @return  bool            True if valid rgba/rgb format, false otherwise
     */
    public static function isValidColor(string $color): bool
    {
        // Allow rgba(r,g,b,a) or rgb(r,g,b) format with optional spaces
        // Also allow hex colors (#rgb or #rrggbb)
        if (preg_match('/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $color)) {
            return true;
        }
        return (bool)preg_match('/^rgba?\(\s*\d+\s*,\s*\d+\s*,\s*\d+\s*(,\s*[\d.]+)?\s*\)$/', $color);
    }

    /**
     * Returns a sanitized color value safe for CSS output.
     * Returns empty string if color is invalid.
     *
     * @param string|null $color The color to sanitize
     * @return string Safe color value or empty string
     */
    public static function sanitizeColor(?string $color): string
    {
        if ($color === null || $color === '') {
            return '';
        }

        return self::isValidColor($color) ? $color : '';
    }

    /**
     * Returns all theme colors as an associative array.
     *
     * @return array<string, string>
     */
    public function getColors(): array
    {
        return [
            'primaryColor' => $this->primaryColor,
            'secondaryColor' => $this->secondaryColor,
            'primaryTextColor' => $this->primaryTextColor,
            'secondaryTextColor' => $this->secondaryTextColor,
        ];
    }

    /**
     * Returns HTML for color preview with sanitized inline styles.
     * Used in ACP theme list for displaying color swatches.
     * Shows all 4 Promo-Badge colors: primaryColor, primaryTextColor, secondaryColor, secondaryTextColor
     *
     * @return string HTML with color preview swatches
     */
    public function getColorPreviewHtml(): string
    {
        $primaryColor = null;
        $primaryTextColor = null;
        $secondaryColor = null;
        $secondaryTextColor = null;
        
        if ($this->hasCssFile()) {
            $cssPath = $this->getCssFilePath();
            
            if ($cssPath !== null && file_exists($cssPath)) {
                $cssContent = file_get_contents($cssPath);
                
                if ($cssContent !== false) {
                    $colors = self::extractColorsFromCss($cssContent);
                    
                    $primaryColor = $colors['primaryColor'] ?? null;
                    $primaryTextColor = $colors['primaryTextColor'] ?? null;
                    $secondaryColor = $colors['secondaryColor'] ?? null;
                    $secondaryTextColor = $colors['secondaryTextColor'] ?? null;
                }
            }
        }
        
        if ($primaryColor === null && $secondaryColor === null) {
            $primaryColor = self::sanitizeColor($this->primaryColor);
            $primaryTextColor = self::sanitizeColor($this->primaryTextColor);
            $secondaryColor = self::sanitizeColor($this->secondaryColor);
            $secondaryTextColor = self::sanitizeColor($this->secondaryTextColor);
        } else {
            $primaryColor = $primaryColor !== null ? self::sanitizeColor($primaryColor) : self::sanitizeColor($this->primaryColor);
            $primaryTextColor = $primaryTextColor !== null ? self::sanitizeColor($primaryTextColor) : self::sanitizeColor($this->primaryTextColor);
            $secondaryColor = $secondaryColor !== null ? self::sanitizeColor($secondaryColor) : self::sanitizeColor($this->secondaryColor);
            $secondaryTextColor = $secondaryTextColor !== null ? self::sanitizeColor($secondaryTextColor) : self::sanitizeColor($this->secondaryTextColor);
        }

        if (empty($primaryColor) && empty($secondaryColor)) {
            return '';
        }

        $primaryLabel = WCF::getLanguage()->get('wcf.shrinkr.theme.color.primary');
        $primaryTextLabel = WCF::getLanguage()->get('wcf.shrinkr.theme.color.primaryText');
        $secondaryLabel = WCF::getLanguage()->get('wcf.shrinkr.theme.color.secondary');
        $secondaryTextLabel = WCF::getLanguage()->get('wcf.shrinkr.theme.color.secondaryText');

        $html = '<div class="themeColorPreview">';
        
        if (!empty($primaryColor)) {
            $html .= '<div class="colorPair">';
            $primaryTooltip = htmlspecialchars($primaryLabel, ENT_QUOTES, 'UTF-8');
            if (!empty($primaryTextColor)) {
                $primaryTooltip .= ' > ' . htmlspecialchars($primaryTextLabel, ENT_QUOTES, 'UTF-8');
            }
            $html .= '<span class="colorSwatch" style="background: ' . htmlspecialchars($primaryColor, ENT_QUOTES, 'UTF-8') . ';" title="' . $primaryTooltip . '"></span>';
            if (!empty($primaryTextColor)) {
                $html .= '<span class="colorSwatch" style="background: ' . htmlspecialchars($primaryTextColor, ENT_QUOTES, 'UTF-8') . '; border: 1px solid #ccc;" title="' . htmlspecialchars($primaryTextLabel, ENT_QUOTES, 'UTF-8') . '"></span>';
            }
            $html .= '</div>';
        }
        
        if (!empty($secondaryColor)) {
            $html .= '<div class="colorPair">';
            $secondaryTooltip = htmlspecialchars($secondaryLabel, ENT_QUOTES, 'UTF-8');
            if (!empty($secondaryTextColor)) {
                $secondaryTooltip .= ' > ' . htmlspecialchars($secondaryTextLabel, ENT_QUOTES, 'UTF-8');
            }
            $html .= '<span class="colorSwatch" style="background: ' . htmlspecialchars($secondaryColor, ENT_QUOTES, 'UTF-8') . ';" title="' . $secondaryTooltip . '"></span>';
            if (!empty($secondaryTextColor)) {
                $html .= '<span class="colorSwatch" style="background: ' . htmlspecialchars($secondaryTextColor, ENT_QUOTES, 'UTF-8') . '; border: 1px solid #ccc;" title="' . htmlspecialchars($secondaryTextLabel, ENT_QUOTES, 'UTF-8') . '"></span>';
            }
            $html .= '</div>';
        }
        
        $html .= '</div>';
        
        return $html;
    }

    /**
     * Checks if this theme has an associated CSS file.
     *
     * @return bool True if theme is in the list of themes with CSS files
     */
    public function hasCssFile(): bool
    {
        // List of predefined themes with CSS files
        $predefinedThemes = ['blackweek', 'halloween', 'christmas', 'christmas-modern', 'christmas-dark', 'autumn', 'ostern', 'valentinstag'];
        
        // Return true if theme is in the list (CSS file may be created later)
        return in_array($this->identifier, $predefinedThemes);
    }

    /**
     * Returns the file path to the theme's CSS file.
     *
     * @return string|null Path to CSS file or null if no CSS file exists
     */
    public function getCssFilePath(): ?string
    {
        // List of predefined themes with CSS files
        $predefinedThemes = ['blackweek', 'halloween', 'christmas', 'christmas-modern', 'christmas-dark', 'autumn', 'ostern', 'valentinstag'];
        
        if (!in_array($this->identifier, $predefinedThemes)) {
            return null;
        }

        if (!defined('SHRINKR_DIR')) {
            return null;
        }
        
        $cssPath = SHRINKR_DIR . 'style/themes/' . $this->identifier . '.css';
        return file_exists($cssPath) ? $cssPath : null;
    }

    /**
     * Extracts Promo-Badge colors from CSS content.
     * Searches for .badge-promo rules and extracts background, color, and border-color values.
     *
     * @param string $cssContent The CSS content to parse
     * @return array<string, string|null> Array with keys: primaryColor, primaryTextColor, secondaryColor, secondaryTextColor
     */
    public static function extractColorsFromCss(string $cssContent): array
    {
        $colors = [
            'primaryColor' => null,
            'primaryTextColor' => null,
            'secondaryColor' => null,
            'secondaryTextColor' => null,
        ];

        // Pattern to match .badge-promo rules
        if (preg_match('/\.badge-promo\s*\{([^}]+)\}/s', $cssContent, $matches)) {
            $badgePromoCss = $matches[1];
            
            // Extract background (for primaryColor/secondaryColor)
            // Handle gradients: extract first rgba color from linear-gradient and convert to full opacity
            if (preg_match('/background:\s*([^;]+);/i', $badgePromoCss, $bgMatches)) {
                $background = trim($bgMatches[1]);
                // Try to extract first color from gradient (linear-gradient(...rgba(...)...))
                // Match first rgba/rgb in the gradient
                if (preg_match('/rgba?\(([^)]+)\)/', $background, $colorMatches)) {
                    $colorValue = $colorMatches[0];
                    // Convert to full opacity for better visibility in preview
                    $colors['primaryColor'] = self::normalizeColorToFullOpacity($colorValue);
                }
            }
            
            // Extract border-color (preferred over background for primaryColor, as it's more visible)
            // Match border with any property name (border, border-color, border-top, etc.)
            if (preg_match('/border[^:]*:\s*([^;!]+)/i', $badgePromoCss, $borderMatches)) {
                $borderColor = trim($borderMatches[1]);
                // Remove !important if present
                $borderColor = preg_replace('/\s*!important\s*/i', '', $borderColor);
                
                // Handle CSS variables first
                if (preg_match('/var\(([^)]+)\)/', $borderColor, $varMatches)) {
                    $varName = trim($varMatches[1]);
                    // Look for CSS variable definition
                    if (preg_match('/' . preg_quote($varName, '/') . '\s*:\s*([^;]+);/i', $cssContent, $varDefMatches)) {
                        $varValue = trim($varDefMatches[1]);
                        // Try rgba/rgb first
                        if (preg_match('/rgba?\(([^)]+)\)/', $varValue, $rgbaMatches)) {
                            $colors['primaryColor'] = self::normalizeColorToFullOpacity($rgbaMatches[0]);
                        } 
                        // Then try hex
                        elseif (preg_match('/#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})/', $varValue, $hexMatches)) {
                            $colors['primaryColor'] = self::hexToRgba($hexMatches[0]);
                        }
                    }
                }
                // Handle direct rgba/rgb values (extract from "1px solid rgba(...)" or just "rgba(...)")
                elseif (preg_match('/rgba?\(([^)]+)\)/', $borderColor, $colorMatches)) {
                    // Border color is usually more representative of the theme color
                    $colors['primaryColor'] = self::normalizeColorToFullOpacity($colorMatches[0]);
                }
                // Handle direct hex values
                elseif (preg_match('/#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})/', $borderColor, $hexMatches)) {
                    $colors['primaryColor'] = self::hexToRgba($hexMatches[0]);
                }
            }
            
            // Extract color (for primaryTextColor)
            // For CSS variables like var(--bw-text), we need to check CSS variable definitions
            // Handle !important in color values
            if (preg_match('/color:\s*([^;!]+)/i', $badgePromoCss, $colorMatches)) {
                $color = trim($colorMatches[1]);
                // Remove !important if present
                $color = preg_replace('/\s*!important\s*/i', '', $color);
                
                if (str_starts_with($color, 'var(')) {
                    // Try to extract CSS variable value from the CSS content
                    $varName = preg_replace('/var\(([^)]+)\)/', '$1', $color);
                    $varName = trim($varName);
                    // Look for CSS variable definition (e.g., --bw-text: #e8eefc;)
                    if (preg_match('/' . preg_quote($varName, '/') . '\s*:\s*([^;]+);/i', $cssContent, $varMatches)) {
                        $varValue = trim($varMatches[1]);
                        // Try rgba/rgb first
                        if (preg_match('/rgba?\(([^)]+)\)/', $varValue, $rgbaMatches)) {
                            $colors['primaryTextColor'] = self::normalizeColorToFullOpacity($rgbaMatches[0]);
                        } 
                        // Then try hex (most common in CSS variables)
                        elseif (preg_match('/#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})/', $varValue, $hexMatches)) {
                            $colors['primaryTextColor'] = self::hexToRgba($hexMatches[0]);
                        }
                    }
                } 
                // Handle direct rgba/rgb values
                elseif (preg_match('/rgba?\(([^)]+)\)/', $color, $rgbaMatches)) {
                    $colors['primaryTextColor'] = self::normalizeColorToFullOpacity($rgbaMatches[0]);
                }
                // Handle direct hex values
                elseif (preg_match('/#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})/', $color, $hexMatches)) {
                    $colors['primaryTextColor'] = self::hexToRgba($hexMatches[0]);
                }
            }
        }

        // Also check .badge-promo-content#rabatt for secondary colors
        if (preg_match('/\.badge-promo-content#rabatt\s*\{([^}]+)\}/s', $cssContent, $matches)) {
            $rabattCss = $matches[1];
            
            // Extract border-color first (more representative than background gradient)
            if (preg_match('/border[^:]*:\s*([^;]+);/i', $rabattCss, $borderMatches)) {
                $borderColor = trim($borderMatches[1]);
                // Handle CSS variables first
                if (preg_match('/var\(([^)]+)\)/', $borderColor, $varMatches)) {
                    $varName = trim($varMatches[1]);
                    // Look for CSS variable definition
                    if (preg_match('/' . preg_quote($varName, '/') . '\s*:\s*([^;]+);/i', $cssContent, $varDefMatches)) {
                        $varValue = trim($varDefMatches[1]);
                        // Try rgba/rgb first
                        if (preg_match('/rgba?\(([^)]+)\)/', $varValue, $rgbaMatches)) {
                            $colors['secondaryColor'] = self::normalizeColorToFullOpacity($rgbaMatches[0]);
                        } 
                        // Then try hex
                        elseif (preg_match('/#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})/', $varValue, $hexMatches)) {
                            $colors['secondaryColor'] = self::hexToRgba($hexMatches[0]);
                        }
                    }
                }
                // Handle direct rgba/rgb values
                elseif (preg_match('/rgba?\(([^)]+)\)/', $borderColor, $colorMatches)) {
                    $colors['secondaryColor'] = self::normalizeColorToFullOpacity($colorMatches[0]);
                }
                // Handle direct hex values
                elseif (preg_match('/#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})/', $borderColor, $hexMatches)) {
                    $colors['secondaryColor'] = self::hexToRgba($hexMatches[0]);
                }
            }
            
            // Extract background for secondaryColor (handle gradients) as fallback
            if ($colors['secondaryColor'] === null && preg_match('/background:\s*([^;]+);/i', $rabattCss, $bgMatches)) {
                $background = trim($bgMatches[1]);
                // Extract first rgba color from gradient
                if (preg_match('/rgba?\(([^)]+)\)/', $background, $colorMatches)) {
                    $colors['secondaryColor'] = self::normalizeColorToFullOpacity($colorMatches[0]);
                }
            }
            
            // Extract color for secondaryTextColor (handle CSS variables)
            // Handle !important in color values
            if (preg_match('/color:\s*([^;!]+)/i', $rabattCss, $colorMatches)) {
                $color = trim($colorMatches[1]);
                // Remove !important if present
                $color = preg_replace('/\s*!important\s*/i', '', $color);
                
                if (str_starts_with($color, 'var(')) {
                    // Try to extract CSS variable value
                    $varName = preg_replace('/var\(([^)]+)\)/', '$1', $color);
                    $varName = trim($varName);
                    if (preg_match('/' . preg_quote($varName, '/') . '\s*:\s*([^;]+);/i', $cssContent, $varMatches)) {
                        $varValue = trim($varMatches[1]);
                        // Try rgba/rgb first
                        if (preg_match('/rgba?\(([^)]+)\)/', $varValue, $rgbaMatches)) {
                            $colors['secondaryTextColor'] = self::normalizeColorToFullOpacity($rgbaMatches[0]);
                        } 
                        // Then try hex
                        elseif (preg_match('/#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})/', $varValue, $hexMatches)) {
                            $colors['secondaryTextColor'] = self::hexToRgba($hexMatches[0]);
                        }
                    }
                } 
                // Handle direct rgba/rgb values
                elseif (preg_match('/rgba?\(([^)]+)\)/', $color, $rgbaMatches)) {
                    $colors['secondaryTextColor'] = self::normalizeColorToFullOpacity($rgbaMatches[0]);
                }
                // Handle direct hex values
                elseif (preg_match('/#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})/', $color, $hexMatches)) {
                    $colors['secondaryTextColor'] = self::hexToRgba($hexMatches[0]);
                }
            }
        }

        // Check .badge-promo-right.badge#discount-countdown for additional colors
        if (preg_match('/\.badge-promo-right\.badge#discount-countdown\s*\{([^}]+)\}/s', $cssContent, $matches)) {
            $countdownCss = $matches[1];
            
            // Extract border-color first (more representative)
            if ($colors['secondaryColor'] === null && preg_match('/border[^:]*:\s*([^;]+);/i', $countdownCss, $borderMatches)) {
                $borderColor = trim($borderMatches[1]);
                if (preg_match('/rgba?\(([^)]+)\)/', $borderColor, $colorMatches)) {
                    $colors['secondaryColor'] = self::normalizeColorToFullOpacity($colorMatches[0]);
                }
            }
            
            // Extract background for secondaryColor if not already set (handle gradients) as fallback
            if ($colors['secondaryColor'] === null && preg_match('/background:\s*([^;]+);/i', $countdownCss, $bgMatches)) {
                $background = trim($bgMatches[1]);
                // Extract first rgba color from gradient
                if (preg_match('/rgba?\(([^)]+)\)/', $background, $colorMatches)) {
                    $colors['secondaryColor'] = self::normalizeColorToFullOpacity($colorMatches[0]);
                }
            }
            
            // Extract color for secondaryTextColor if not already set (handle CSS variables)
            if ($colors['secondaryTextColor'] === null && preg_match('/color:\s*([^;]+);/i', $countdownCss, $colorMatches)) {
                $color = trim($colorMatches[1]);
                if (!str_starts_with($color, 'var(') && preg_match('/rgba?\(([^)]+)\)/', $color, $rgbaMatches)) {
                    $colors['secondaryTextColor'] = self::normalizeColorToFullOpacity($rgbaMatches[0]);
                } elseif (str_starts_with($color, 'var(')) {
                    // Try to extract CSS variable value
                    $varName = preg_replace('/var\(([^)]+)\)/', '$1', $color);
                    $varName = trim($varName);
                    if (preg_match('/' . preg_quote($varName, '/') . '\s*:\s*([^;]+);/i', $cssContent, $varMatches)) {
                        $varValue = trim($varMatches[1]);
                        if (preg_match('/rgba?\(([^)]+)\)/', $varValue, $rgbaMatches)) {
                            $colors['secondaryTextColor'] = self::normalizeColorToFullOpacity($rgbaMatches[0]);
                        } elseif (preg_match('/#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})/', $varValue, $hexMatches)) {
                            $colors['secondaryTextColor'] = self::hexToRgba($hexMatches[0]);
                        }
                    }
                }
            }
        }
        
        return $colors;
    }

    /**
     * Normalizes a color value to rgba format.
     * Converts rgb, hex, and other formats to rgba.
     *
     * @param string $color The color value to normalize
     * @return string Normalized rgba color or original if conversion fails
     */
    private static function normalizeColor(string $color): string
    {
        $color = trim($color);
        
        // Already rgba format
        if (preg_match('/^rgba?\([^)]+\)$/', $color)) {
            return $color;
        }
        
        // Hex color
        if (preg_match('/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $color, $matches)) {
            return self::hexToRgba($color);
        }
        
        // Return original if we can't convert
        return $color;
    }

    /**
     * Normalizes a color value to rgba format with full opacity (alpha = 1).
     * Useful for color previews where transparency is not desired.
     *
     * @param string $color The color value to normalize
     * @return string Normalized rgba color with full opacity
     */
    private static function normalizeColorToFullOpacity(string $color): string
    {
        $color = trim($color);
        
        // rgba format - extract RGB values and set alpha to 1
        if (preg_match('/rgba?\(([^)]+)\)/', $color, $matches)) {
            $values = explode(',', $matches[1]);
            $values = array_map('trim', $values);
            
            if (count($values) >= 3) {
                $r = intval($values[0]);
                $g = intval($values[1]);
                $b = intval($values[2]);
                return "rgba({$r}, {$g}, {$b}, 1)";
            }
        }
        
        // Hex color
        if (preg_match('/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $color)) {
            return self::hexToRgba($color);
        }
        
        // Fallback to regular normalization
        return self::normalizeColor($color);
    }

    /**
     * Converts a hex color to rgba format.
     *
     * @param string $hex The hex color (e.g., #7c5cff or #fff)
     * @return string rgba color string
     */
    private static function hexToRgba(string $hex): string
    {
        $hex = trim($hex);
        if (preg_match('/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $hex, $matches)) {
            $hexValue = $matches[1];
            if (strlen($hexValue) === 3) {
                $hexValue = $hexValue[0] . $hexValue[0] . $hexValue[1] . $hexValue[1] . $hexValue[2] . $hexValue[2];
            }
            $r = hexdec(substr($hexValue, 0, 2));
            $g = hexdec(substr($hexValue, 2, 2));
            $b = hexdec(substr($hexValue, 4, 2));
            return "rgba({$r}, {$g}, {$b}, 1)";
        }
        
        // Fallback
        return "rgba(0, 0, 0, 1)";
    }

    /**
     * Loads CSS content from file or database.
     *
     * @return string|null CSS content or null if not available
     */
    public function loadCssContent(): ?string
    {
        // First try to load from file
        $cssPath = $this->getCssFilePath();
        if ($cssPath !== null && file_exists($cssPath)) {
            $content = file_get_contents($cssPath);
            if ($content !== false) {
                return $content;
            }
        }

        // Fallback to database if cssContent column exists
        if (isset($this->cssContent)) {
            return $this->cssContent;
        }

        return null;
    }

    /**
     * Saves CSS content to file and database.
     *
     * @param string $content The CSS content to save
     * @return bool True on success, false on failure
     */
    public function saveCssContent(string $content): bool
    {
        $cssPath = $this->getCssFilePath();
        
        // Save to file if path exists
        if ($cssPath !== null) {
            $dir = dirname($cssPath);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            file_put_contents($cssPath, $content);
        }

        // Save to database if cssContent column exists
        if (property_exists($this, 'cssContent')) {
            $editor = new ThemeEditor($this);
            $editor->update(['cssContent' => $content]);
        }

        return true;
    }

    /**
     * Synchronizes colors from CSS file to database.
     * Extracts colors from CSS file and updates the theme's color properties.
     *
     * @return bool True on success, false on failure
     */
    public function syncColorsFromCssFile(): bool
    {
        $cssContent = $this->loadCssContent();
        if ($cssContent === null) {
            return false;
        }

        $colors = self::extractColorsFromCss($cssContent);
        
        // Only update colors that were successfully extracted
        $updateData = [];
        if ($colors['primaryColor'] !== null) {
            $updateData['primaryColor'] = $colors['primaryColor'];
        }
        if ($colors['primaryTextColor'] !== null) {
            $updateData['primaryTextColor'] = $colors['primaryTextColor'];
        }
        if ($colors['secondaryColor'] !== null) {
            $updateData['secondaryColor'] = $colors['secondaryColor'];
        }
        if ($colors['secondaryTextColor'] !== null) {
            $updateData['secondaryTextColor'] = $colors['secondaryTextColor'];
        }

        if (!empty($updateData)) {
            $editor = new ThemeEditor($this);
            $editor->update($updateData);
            return true;
        }

        return false;
    }
}

