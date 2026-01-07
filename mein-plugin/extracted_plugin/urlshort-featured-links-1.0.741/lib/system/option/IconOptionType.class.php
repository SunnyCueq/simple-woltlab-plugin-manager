<?php

namespace urlshort\system\option;

use wcf\data\option\Option;
use wcf\system\exception\UserInputException;
use wcf\system\option\AbstractOptionType;
use wcf\system\style\FontAwesomeIcon;
use wcf\system\WCF;

/**
 * Option type implementation for Font Awesome icon selection.
 *
 * Uses IconFormField to provide a user-friendly icon picker with preview.
 *
 * @author      Sunny C. <https://benjaro.info>
 * @copyright   2022-2025 Benjaro
 * @license     License for Commercial Plugins <https://benjaro.info>
 *
 * @package    info.benjaro.urlshort.affiliate
 * @subpackage system.option
 */
class IconOptionType extends AbstractOptionType
{
    /**
     * @inheritDoc
     */
    public function getFormElement(Option $option, $value)
    {
        // Parse icon value
        $icon = null;
        if ($value && FontAwesomeIcon::isValidString($value)) {
            $icon = FontAwesomeIcon::fromString($value);
        }
        
        // Get HTML variables for JavaScript inclusion (from IconFormField)
        static $includeJavaScript = true;
        $includeJavaScriptValue = $includeJavaScript;
        if ($includeJavaScript) {
            $includeJavaScript = false;
        }
        
        // Assign variables to template
        WCF::getTPL()->assign([
            'option' => $option,
            'value' => $value ?: '',
            'icon' => $icon,
            '__iconFormFieldIncludeJavaScript' => $includeJavaScriptValue,
        ]);
        
        // Render using custom template for option type
        return WCF::getTPL()->fetch('iconOptionType', 'urlshort');
    }

    /**
     * @inheritDoc
     */
    public function validate(Option $option, $newValue)
    {
        if ($newValue && !FontAwesomeIcon::isValidString($newValue)) {
            throw new UserInputException($option->optionName, 'invalidValue');
        }
    }

    /**
     * @inheritDoc
     */
    public function getData(Option $option, $newValue)
    {
        // Return empty string if no value or invalid
        if (empty($newValue)) {
            return '';
        }
        
        // Validate and return the icon string
        if (FontAwesomeIcon::isValidString($newValue)) {
            return $newValue;
        }
        
        return '';
    }
}

