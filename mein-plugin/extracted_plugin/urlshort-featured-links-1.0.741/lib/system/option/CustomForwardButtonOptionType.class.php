<?php

namespace urlshort\system\option;

use wcf\data\option\Option;
use wcf\system\exception\UserInputException;
use wcf\system\option\AbstractOptionType;
use wcf\system\WCF;

/**
 * Option type implementation for custom forward button style with preview.
 *
 * Provides a checkbox to enable/disable the custom 3D button style
 * and shows a static preview of how the button will look.
 *
 * @author      Sunny C. <https://benjaro.info>
 * @copyright   2022-2025 Benjaro
 * @license     License for Commercial Plugins <https://benjaro.info>
 *
 * @package    info.benjaro.urlshort.affiliate
 * @subpackage system.option
 */
class CustomForwardButtonOptionType extends AbstractOptionType
{
    /**
     * @inheritDoc
     */
    public function getFormElement(Option $option, $value)
    {
        // Assign variables to template
        WCF::getTPL()->assign([
            'option' => $option,
            'value' => $value ? 1 : 0,
        ]);
        
        // Render using custom template for option type
        return WCF::getTPL()->fetch('customForwardButtonOptionType', 'urlshort');
    }

    /**
     * @inheritDoc
     */
    public function validate(Option $option, $newValue)
    {
        // Boolean option - validate that it's 0 or 1
        if ($newValue !== '0' && $newValue !== '1' && $newValue !== '' && $newValue !== null) {
            throw new UserInputException($option->optionName, 'invalidValue');
        }
    }

    /**
     * @inheritDoc
     */
    public function getData(Option $option, $newValue)
    {
        // Return 1 if checked, 0 if not
        return ($newValue == '1' || $newValue === true || $newValue === 'true') ? 1 : 0;
    }
}

