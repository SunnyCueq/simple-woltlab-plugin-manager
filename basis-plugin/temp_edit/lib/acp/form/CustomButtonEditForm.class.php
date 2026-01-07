<?php

namespace urlshort\acp\form;

use CuyZ\Valinor\Mapper\MappingError;
use urlshort\data\custombutton\CustomButton;
use wcf\http\Helper;
use wcf\system\exception\IllegalLinkException;

/**
 * Form for editing an existing custom button.
 *
 * @author      Sunny C. <https://benjaro.info>
 * @copyright   2022-2025 Benjaro
 * @license     License for Commercial Plugins <https://benjaro.info>
 *
 * @package    dev.tkirch.wsc.urlshort
 * @subpackage acp.form
 */
class CustomButtonEditForm extends CustomButtonAddForm
{
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
        // Load CustomButton first (before parent, to get urlID)
        try {
            $queryParameters = Helper::mapQueryParameters(
                $_GET,
                <<<'EOT'
                    array {
                        id: positive-int
                    }
                    EOT
            );
            $this->formObject = new CustomButton($queryParameters['id']);

            if (!$this->formObject->customButtonID) {
                throw new IllegalLinkException();
            }

            // Set urlID from loaded object (needed for parent::readParameters)
            $this->urlID = $this->formObject->urlID;
        } catch (MappingError) {
            throw new IllegalLinkException();
        }

        // Now load URL hash (parent expects urlID to be set)
        $sql = "SELECT hash FROM urlshort1_url WHERE urlID = ?";
        $statement = \wcf\system\WCF::getDB()->prepareStatement($sql);
        $statement->execute([$this->urlID]);
        $this->urlHash = $statement->fetchSingleColumn();

        if (empty($this->urlHash)) {
            throw new IllegalLinkException();
        }

        // Call parent WITHOUT readParameters (to avoid urlID check from query)
        \wcf\form\AbstractFormBuilderForm::readParameters();
    }
}

