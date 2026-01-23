<?php

namespace shrinkr\system\option;

use wcf\data\option\Option;
use wcf\system\exception\UserInputException;
use wcf\system\option\AbstractOptionType;
use wcf\system\WCF;

/**
 * Option type implementation for custom forward button style with preview.
 * 
 * Custom option type for ACP options that provides a checkbox to enable/disable
 * the custom 3D button style. Renders a custom template with a static preview
 * of how the button will look.
 *
 * @author      Sunny C
 * @copyright   2026 Sunny C
 * @license     License for Commercial Plugins
 * @link        https://sunnyc.de
 * @package     de.sunnyc.wsc.shrinkr
 * @subpackage  system.option
 */
class CustomForwardButtonOptionType extends AbstractOptionType
{
    /**
     * Returns the form element for this option type.
     * 
     * Renders a custom template with checkbox and preview for the custom
     * forward button style option.
     *
     * @param   Option  $option  The option object
     * @param   mixed   $value    The current option value
     * @return  string            Rendered HTML form element
     */
    public function getFormElement(Option $option, $value)
    {
        WCF::getTPL()->assign([
            'option' => $option,
            'value' => $value ? 1 : 0,
        ]);
        
        return WCF::getTPL()->fetch('customForwardButtonOptionType', 'shrinkr');
    }

    /**
     * Validates the option value.
     * 
     * Ensures the value is a valid boolean (0, 1, empty string, or null).
     *
     * @param   Option  $option    The option object
     * @param   mixed   $newValue  The new value to validate
     * @return  void
     * @throws  UserInputException If the value is invalid
     */
    public function validate(Option $option, $newValue)
    {
        if ($newValue !== '0' && $newValue !== '1' && $newValue !== '' && $newValue !== null) {
            throw new UserInputException($option->optionName, 'invalidValue');
        }
    }

    /**
     * Processes the option value for storage.
     * 
     * Converts various boolean representations to 1 or 0 for database storage.
     *
     * @param   Option  $option    The option object
     * @param   mixed   $newValue  The new value to process
     * @return  int                 1 if enabled, 0 if disabled
     */
    public function getData(Option $option, $newValue)
    {
        return ($newValue == '1' || $newValue === true || $newValue === 'true') ? 1 : 0;
    }
}

