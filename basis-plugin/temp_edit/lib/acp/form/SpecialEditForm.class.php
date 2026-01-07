<?php

namespace urlshort\acp\form;

use CuyZ\Valinor\Mapper\MappingError;
use urlshort\data\special\Special;
use urlshort\system\special\SpecialThemeHelper;
use wcf\http\Helper;
use wcf\system\exception\IllegalLinkException;
use wcf\system\WCF;

/**
 * Form for editing an existing special.
 *
 * @author      Sunny C. <https://benjaro.info>
 * @copyright   2022-2025 Benjaro
 * @license     License for Commercial Plugins <https://benjaro.info>
 *
 * @package    dev.tkirch.wsc.urlshort
 * @subpackage acp.form
 */
class SpecialEditForm extends SpecialAddForm
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
        // Load Special first (before parent, to get urlID)
        try {
            $queryParameters = Helper::mapQueryParameters(
                $_GET,
                <<<'EOT'
                    array {
                        id: positive-int
                    }
                    EOT
            );
            $this->formObject = new Special($queryParameters['id']);

            if (!$this->formObject->specialID) {
                throw new IllegalLinkException();
            }

            // Set urlID from loaded object (needed for parent::readParameters)
            $this->urlID = $this->formObject->urlID;
        } catch (MappingError) {
            throw new IllegalLinkException();
        }

        // Now load URL hash (parent expects urlID to be set)
        $sql = "SELECT hash FROM urlshort1_url WHERE urlID = ?";
        $statement = WCF::getDB()->prepareStatement($sql);
        $statement->execute([$this->urlID]);
        $this->urlHash = $statement->fetchSingleColumn();

        if (empty($this->urlHash)) {
            throw new IllegalLinkException();
        }

        // Call parent WITHOUT readParameters (to avoid urlID check from query)
        \wcf\form\AbstractFormBuilderForm::readParameters();
    }

    /**
     * @inheritDoc
     */
    #[\Override]
    protected function createForm()
    {
        parent::createForm();
    }

    /**
     * @inheritDoc
     */
    #[\Override]
    public function readData()
    {
        parent::readData();

        // ItemListFormField expects a string (comma-separated) when setting value, not an array
        // The field will convert it to array internally
        if ($this->formObject && $this->formObject->codes) {
            $codesField = $this->form->getNodeById('codes');
            if ($codesField) {
                // Use the raw codes string, not the array from getCodes()
                $codesField->value($this->formObject->codes);
            }
        }

        // Empty theme means no theme (standard WoltLab styling)
        // No mapping needed - empty string is valid
    }

    /**
     * @inheritDoc
     */
    #[\Override]
    public function assignVariables()
    {
        parent::assignVariables();

        // HtmlUpcastProcessor für WYSIWYG-Feld (WoltLab 6.1 Best Practice)
        // Wird bereits in parent::assignVariables() (SpecialAddForm) behandelt, aber sicherheitshalber hier auch
        if (isset($this->formObject) && $this->formObject->specialID) {
            $upcastProcessor = new \wcf\system\html\upcast\HtmlUpcastProcessor();
            $upcastProcessor->process(
                $this->formObject->additionalText ?? '',
                'dev.tkirch.wsc.urlshort.special.additionalText',
                $this->formObject->specialID
            );
            WCF::getTPL()->assign('additionalText', $upcastProcessor->getHtml());
        }

        // Load URL hash
        $sql = "SELECT hash FROM urlshort1_url WHERE urlID = ?";
        $statement = WCF::getDB()->prepareStatement($sql);
        $statement->execute([$this->urlID]);
        $urlHash = $statement->fetchSingleColumn();

        // Get themes (with fallback to empty array)
        try {
            $themes = SpecialThemeHelper::getThemes();
        } catch (\Exception) {
            $themes = [];
        }

        WCF::getTPL()->assign([
            'urlHash' => $urlHash ?? '',
            'themes' => $themes,
        ]);
    }
}

