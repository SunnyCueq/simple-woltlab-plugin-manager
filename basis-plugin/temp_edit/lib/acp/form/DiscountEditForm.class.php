<?php

namespace shrinkr\acp\form;

use CuyZ\Valinor\Mapper\MappingError;
use shrinkr\data\discount\Discount;
use wcf\http\Helper;
use wcf\system\exception\IllegalLinkException;

/**
 * Form for editing an existing discount.
 * 
 * ACP form for editing discount codes and promotions. Extends DiscountAddForm
 * and loads the discount object from the database.
 *
 * @author      Sunny C
 * @copyright   2026 Sunny C
 * @license     License for Commercial Plugins
 * @link        https://sunnyc.de
 * @package     de.sunnyc.wsc.shrinkr
 * @subpackage  acp.form
 */

class DiscountEditForm extends DiscountAddForm
{
    /**
     * Active menu item identifier for navigation highlighting.
     *
     * @var    string
     */
    public $activeMenuItem = 'shrinkr.acp.menu.link.discount.list';

    /**
     * Form action name (edit, update, delete).
     *
     * @var    string
     */
    public $formAction = 'edit';

    /**
     * Reads request parameters and loads the discount to edit.
     * 
     * Validates the discount ID from query parameters and loads the Discount object.
     * Throws IllegalLinkException if the discount doesn't exist.
     *
     * @return  void
     * @throws  IllegalLinkException  If discount ID is invalid or discount doesn't exist
     */
    #[\Override]
    public function readParameters()
    {
        parent::readParameters();

        try {
            $queryParameters = Helper::mapQueryParameters(
                $_GET,
                <<<'EOT'
                    array {
                        id: positive-int
                    }
                    EOT
            );
            $this->formObject = new Discount($queryParameters['id']);

            if (!$this->formObject->discountID) {
                throw new IllegalLinkException();
            }
        } catch (MappingError) {
            throw new IllegalLinkException();
        }
    }

    /**
     * @inheritDoc
     */
    #[\Override]
    public function assignVariables()
    {
        parent::assignVariables();

        \wcf\system\WCF::getTPL()->assign([
            'acpPageSubMenuCategoryList' => 'shrinkr.acp.menu.link.discount.list'
        ]);
    }
}
