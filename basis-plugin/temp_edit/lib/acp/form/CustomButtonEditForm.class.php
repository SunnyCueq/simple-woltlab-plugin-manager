<?php

namespace shrinkr\acp\form;

use CuyZ\Valinor\Mapper\MappingError;
use shrinkr\data\custombutton\CustomButton;
use wcf\http\Helper;
use wcf\system\exception\IllegalLinkException;

/**
 * Form for editing an existing custom button.
 * 
 * ACP form for editing custom buttons. Extends CustomButtonAddForm and loads
 * the custom button object from the database. Handles linkID and URL hash
 * loading for form initialization.
 *
 * @author      Sunny C
 * @copyright   2026 Sunny C
 * @license     License for Commercial Plugins
 * @link        https://sunnyc.de
 * @package     de.sunnyc.wsc.shrinkr
 * @subpackage  acp.form
 */
class CustomButtonEditForm extends CustomButtonAddForm
{
    /**
     * Active menu item identifier for navigation highlighting.
     *
     * @var    string
     */
    public $activeMenuItem = 'shrinkr.acp.menu.link.customButton.edit';

    /**
     * Form action name (edit, update, delete).
     *
     * @var    string
     */
    public $formAction = 'edit';

    /**
     * Reads request parameters and loads the custom button to edit.
     * 
     * Loads the CustomButton object first to get linkID, then loads the URL hash.
     * Calls parent readParameters without the linkID check since we already
     * have the linkID from the custom button object.
     *
     * @return  void
     * @throws  IllegalLinkException  If custom button ID is invalid or link doesn't exist
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
            $this->formObject = new CustomButton($queryParameters['id']);

            if (!$this->formObject->customButtonID) {
                throw new IllegalLinkException();
            }

            $this->linkID = $this->formObject->linkID;
        } catch (MappingError) {
            throw new IllegalLinkException();
        }

        $sql = "SELECT hash FROM shrinkr1_link WHERE linkID = ?";
        $statement = \wcf\system\WCF::getDB()->prepareStatement($sql);
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
    public function assignVariables()
    {
        parent::assignVariables();

        \wcf\system\WCF::getTPL()->assign([
            'acpPageSubMenuCategoryList' => 'shrinkr.acp.menu.link.customButton.list'
        ]);
    }
}

