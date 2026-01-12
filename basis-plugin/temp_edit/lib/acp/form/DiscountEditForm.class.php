<?php

namespace shrinkr\acp\form;

use CuyZ\Valinor\Mapper\MappingError;
use shrinkr\data\discount\Discount;
use wcf\http\Helper;
use wcf\system\exception\IllegalLinkException;

/**
 * Form for editing an existing discount.
 *
 * @author      Sunny C
 * @copyright   2026 Sunny C
 * @license     License for Commercial Plugins
 *
 * @package    de.sunnyc.wsc.shrinkr
 * @subpackage acp.form
 */

class DiscountEditForm extends DiscountAddForm
{
    /**
     * @inheritDoc
     */
    public $activeMenuItem = 'shrinkr.acp.menu.link.discount.edit';

    /**
     * @inheritDoc
     */
    public $formAction = 'edit';

    /**
     * @inheritDoc
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
