<?php

namespace shrinkr\acp\form;

use CuyZ\Valinor\Mapper\MappingError;
use shrinkr\data\special\Special;
use shrinkr\system\special\SpecialThemeHelper;
use wcf\http\Helper;
use wcf\system\exception\IllegalLinkException;
use wcf\system\WCF;

/**
 * Form for editing an existing special.
 *
 * @author      Sunny C
 * @copyright   2026 Sunny C
 * @license     License for Commercial Plugins
 *
 * @package    de.sunnyc.wsc.shrinkr
 * @subpackage acp.form
 */
class SpecialEditForm extends SpecialAddForm
{
    /**
     * @inheritDoc
     */
    public $activeMenuItem = 'shrinkr.acp.menu.link.special.list';

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
        // Load Special first (before parent, to get linkID)
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

            // Set linkID from loaded object (needed for parent::readParameters)
            $this->linkID = $this->formObject->linkID;
        } catch (MappingError) {
            throw new IllegalLinkException();
        }

        // Now load URL hash (parent expects linkID to be set)
        $sql = "SELECT hash FROM shrinkr1_link WHERE linkID = ?";
        $statement = WCF::getDB()->prepareStatement($sql);
        $statement->execute([$this->linkID]);
        $this->urlHash = $statement->fetchSingleColumn();

        if (empty($this->urlHash)) {
            throw new IllegalLinkException();
        }

        // Call parent WITHOUT readParameters (to avoid linkID check from query)
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
        // Already handled in parent::assignVariables() (SpecialAddForm), but also here for safety
        if (isset($this->formObject) && $this->formObject->specialID) {
            $upcastProcessor = new \wcf\system\html\upcast\HtmlUpcastProcessor();
            $upcastProcessor->process(
                $this->formObject->additionalText ?? '',
                'de.sunnyc.wsc.shrinkr.special.additionalText',
                $this->formObject->specialID
            );
            WCF::getTPL()->assign('additionalText', $upcastProcessor->getHtml());
        }

        // Load URL hash
        $sql = "SELECT hash FROM shrinkr1_link WHERE linkID = ?";
        $statement = WCF::getDB()->prepareStatement($sql);
        $statement->execute([$this->linkID]);
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
            'acpPageSubMenuCategoryList' => 'shrinkr.acp.menu.link.special.list'
        ]);
    }
}

