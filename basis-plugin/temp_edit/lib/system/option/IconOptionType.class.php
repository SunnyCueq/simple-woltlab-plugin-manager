<?php

namespace shrinkr\system\option;

use wcf\data\option\Option;
use wcf\system\exception\UserInputException;
use wcf\system\option\AbstractOptionType;
use wcf\system\style\FontAwesomeIcon;
use wcf\system\WCF;

/**
 * Option type implementation for Font Awesome icon selection.
 * 
 * Custom option type for ACP options that provides a user-friendly icon picker
 * with preview. Uses WoltLab's IconFormField and FontAwesomeIcon classes for
 * icon selection and validation.
 *
 * @author      Sunny C
 * @copyright   2026 Sunny C
 * @license     License for Commercial Plugins
 * @link        https://sunnyc.de
 * @package     de.sunnyc.wsc.shrinkr
 * @subpackage  system.option
 */
class IconOptionType extends AbstractOptionType
{
    /**
     * Returns the form element for this option type.
     * 
     * Renders a custom template with icon picker using WoltLab's IconFormField.
     * Ensures JavaScript is only included once across multiple icon options.
     *
     * @param   Option  $option  The option object
     * @param   mixed   $value    The current option value (icon string)
     * @return  string            Rendered HTML form element
     */
    public function getFormElement(Option $option, $value)
    {
        $icon = null;
        if ($value && FontAwesomeIcon::isValidString($value)) {
            $icon = FontAwesomeIcon::fromString($value);
        }
        
        static $includeJavaScript = true;
        $includeJavaScriptValue = $includeJavaScript;
        if ($includeJavaScript) {
            $includeJavaScript = false;
        }
        
        WCF::getTPL()->assign([
            'option' => $option,
            'value' => $value ?: '',
            'icon' => $icon,
            '__iconFormFieldIncludeJavaScript' => $includeJavaScriptValue,
        ]);
        
        return WCF::getTPL()->fetch('iconOptionType', 'shrinkr');
    }

    /**
     * Validates the option value.
     * 
     * Ensures the value is a valid Font Awesome icon string if provided.
     *
     * @param   Option  $option    The option object
     * @param   mixed   $newValue  The new value to validate
     * @return  void
     * @throws  UserInputException If the value is not a valid icon string
     */
    public function validate(Option $option, $newValue)
    {
        if ($newValue && !FontAwesomeIcon::isValidString($newValue)) {
            throw new UserInputException($option->optionName, 'invalidValue');
        }
    }

    /**
     * Processes the option value for storage.
     * 
     * Validates and returns the icon string, or empty string if invalid.
     *
     * @param   Option  $option    The option object
     * @param   mixed   $newValue  The new value to process
     * @return  string             Valid icon string or empty string
     */
    public function getData(Option $option, $newValue)
    {
        if (empty($newValue)) {
            return '';
        }
        
        if (FontAwesomeIcon::isValidString($newValue)) {
            return $newValue;
        }
        
        return '';
    }
}

