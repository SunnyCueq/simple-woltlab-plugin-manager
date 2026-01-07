<?php

namespace urlshort\acp\form;

use urlshort\data\custombutton\CustomButtonAction;
use urlshort\data\custombutton\CustomButtonList;
use wcf\form\AbstractFormBuilderForm;
use wcf\system\exception\IllegalLinkException;
use wcf\system\form\builder\container\FormContainer;
use wcf\system\form\builder\field\HiddenFormField;
use wcf\system\form\builder\field\IntegerFormField;
use wcf\system\form\builder\field\TextFormField;
use wcf\system\form\builder\field\UrlFormField;
use wcf\system\request\LinkHandler;
use wcf\system\WCF;
use wcf\util\HeaderUtil;

/**
 * Form for adding a new custom button.
 *
 * @author      Sunny C. <https://benjaro.info>
 * @copyright   2022-2025 Benjaro
 * @license     License for Commercial Plugins <https://benjaro.info>
 *
 * @package    dev.tkirch.wsc.urlshort
 * @subpackage acp.form
 */
class CustomButtonAddForm extends AbstractFormBuilderForm
{
    /**
     * @inheritDoc
     */
    public $neededPermissions = ['admin.urlshort.canManageCustomButtons'];

    /**
     * @inheritDoc
     */
    public $activeMenuItem = 'urlshort.acp.menu.link.menu';

    /**
     * @inheritDoc
     */
    public $objectActionClass = CustomButtonAction::class;

    /**
     * URL ID (required parameter from URL query)
     */
    public int $urlID = 0;

    /**
     * URL hash for display
     */
    public string $urlHash = '';

    /**
     * @inheritDoc
     */
    #[\Override]
    public function readParameters()
    {
        parent::readParameters();

        if (isset($_REQUEST['urlID'])) {
            $this->urlID = (int) $_REQUEST['urlID'];
        }

        // URL ID is required
        if ($this->urlID === 0) {
            throw new IllegalLinkException();
        }

        // Load URL data (hash)
        $sql = "SELECT hash FROM urlshort1_url WHERE urlID = ?";
        $statement = WCF::getDB()->prepareStatement($sql);
        $statement->execute([$this->urlID]);
        $this->urlHash = $statement->fetchSingleColumn();

        if (empty($this->urlHash)) {
            throw new IllegalLinkException();
        }
    }

    /**
     * @inheritDoc
     */
    #[\Override]
    protected function createForm()
    {
        parent::createForm();

        $dataContainer = FormContainer::create('data')
            ->label('wcf.global.form.data');

        $dataContainer->appendChildren([
            // Hidden field for urlID (submitted with form)
            HiddenFormField::create('urlID')
                ->value($this->urlID),

            UrlFormField::create('targetUrl')
                ->label('wcf.urlshort.customButton.targetUrl')
                ->description('wcf.urlshort.customButton.targetUrl.description')
                ->required()
                ->autoFocus()
                ->maximumLength(255),

            TextFormField::create('title')
                ->label('wcf.global.title')
                ->description('wcf.urlshort.customButton.title.description')
                ->required()
                ->maximumLength(255),

            IntegerFormField::create('sortOrder')
                ->label('wcf.urlshort.customButton.sortOrder')
                ->description('wcf.urlshort.customButton.sortOrder.description')
                ->value(1)
                ->minimum(1),
        ]);

        $this->form->appendChild($dataContainer);
    }

    /**
     * @inheritDoc
     */
    #[\Override]
    public function saved()
    {
        parent::saved();

        // Redirect back to add form with same urlID to allow adding another custom button
        $url = LinkHandler::getInstance()->getControllerLink(CustomButtonAddForm::class, [
            'application' => 'urlshort',
        ]);
        $url .= '&urlID=' . $this->urlID;
        
        HeaderUtil::redirect($url);
        exit;
    }

    /**
     * @inheritDoc
     */
    #[\Override]
    public function assignVariables()
    {
        parent::assignVariables();

        // Check if there are existing custom buttons for this URL
        $customButtonList = new CustomButtonList();
        $customButtonList->getConditionBuilder()->add('urlID = ?', [$this->urlID]);
        $customButtonList->readObjects();
        $hasExistingCustomButtons = $customButtonList->count() > 0;

        WCF::getTPL()->assign([
            'urlID' => $this->urlID,
            'urlHash' => $this->urlHash,
            'hasExistingCustomButtons' => $hasExistingCustomButtons,
        ]);
    }
}

