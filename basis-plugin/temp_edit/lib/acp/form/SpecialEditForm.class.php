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
 * ACP form for editing special events/promotions. Extends SpecialAddForm
 * and loads the special object from the database. Handles linkID and URL hash
 * loading for form initialization.
 *
 * @author      Sunny C
 * @copyright   2026 Sunny C
 * @license     License for Commercial Plugins
 * @link        https://sunnyc.de
 * @package     de.sunnyc.wsc.shrinkr
 * @subpackage  acp.form
 */
class SpecialEditForm extends SpecialAddForm
{
    /**
     * Active menu item identifier for navigation highlighting.
     *
     * @var    string
     */
    public $activeMenuItem = 'shrinkr.acp.menu.link.special.list';

    /**
     * Form action name (edit, update, delete).
     *
     * @var    string
     */
    public $formAction = 'edit';

    /**
     * Reads request parameters and loads the special to edit.
     * 
     * Loads the Special object first to get linkID, then loads the URL hash.
     * Calls parent readParameters without the linkID check since we already
     * have the linkID from the special object.
     *
     * @return  void
     * @throws  IllegalLinkException  If special ID is invalid or link doesn't exist
     */
    #[\Override]
    public function readParameters()
    {
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

            $this->linkID = $this->formObject->linkID;
        } catch (MappingError) {
            throw new IllegalLinkException();
        }

        $sql = "SELECT hash FROM shrinkr1_link WHERE linkID = ?";
        $statement = WCF::getDB()->prepareStatement($sql);
        $statement->execute([$this->linkID]);
        $this->urlHash = $statement->fetchSingleColumn();

        if (empty($this->urlHash)) {
            throw new IllegalLinkException();
        }

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

