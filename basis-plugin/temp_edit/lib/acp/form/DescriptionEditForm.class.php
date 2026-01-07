<?php

namespace shrinkr\acp\form;

use CuyZ\Valinor\Mapper\MappingError;
use shrinkr\data\description\Description;
use wcf\http\Helper;
use wcf\system\exception\IllegalLinkException;

/**
 * Form for editing an existing description.
 *
 * @author      Sunny C. <https://benjaro.info>
 * @copyright   2026 Sunny C
 * @license     License for Commercial Plugins <https://benjaro.info>
 *
 * @package    de.sunnyc.wsc.shrinkr
 * @subpackage acp.form
 */
class DescriptionEditForm extends DescriptionAddForm
{
    /**
     * @inheritDoc
     */
    public $formAction = 'edit';

    /**
     * @inheritDoc
     */
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
            $this->formObject = new Description($queryParameters['id']);

            if (!$this->formObject->descriptionID) {
                throw new IllegalLinkException();
            }
        } catch (MappingError) {
            throw new IllegalLinkException();
        }
    }
}
