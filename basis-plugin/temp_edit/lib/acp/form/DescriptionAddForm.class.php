<?php

namespace shrinkr\acp\form;

use shrinkr\data\description\DescriptionAction;
use wcf\form\AbstractFormBuilderForm;
use wcf\system\form\builder\container\FormContainer;
use wcf\system\form\builder\field\BooleanFormField;
use wcf\system\form\builder\field\MultilineTextFormField;
use wcf\system\form\builder\field\TextFormField;

/**
 * Form for adding a new description.
 * 
 * ACP form for creating description texts displayed on shortened link redirect
 * pages. Provides form fields for title and description text (multilingual via I18n).
 * Uses WoltLab's FormBuilder API.
 *
 * @author      Sunny C
 * @copyright   2026 Sunny C
 * @license     License for Commercial Plugins
 * @link        https://sunnyc.de
 * @package     de.sunnyc.wsc.shrinkr
 * @subpackage  acp.form
 */
class DescriptionAddForm extends AbstractFormBuilderForm
{
    /**
     * Required permissions to access this form.
     *
     * @var    string[]
     */
    public $neededPermissions = ['admin.shrinkr.canManageDescriptions'];

    /**
     * Active menu item identifier for navigation highlighting.
     *
     * @var    string
     */
    public $activeMenuItem = 'shrinkr.acp.menu.link.description.add';

    /**
     * Action class for handling form submissions.
     *
     * @var    string
     */
    public $objectActionClass = DescriptionAction::class;

    /**
     * Creates the form structure using WoltLab's FormBuilder API.
     * 
     * Builds a form with fields for title and description text. The description
     * text supports multilingual content via I18n and Smarty template variables.
     *
     * @return  void
     */
    protected function createForm()
    {
        parent::createForm();

        $basicContainer = FormContainer::create('basic')
            ->label('wcf.shrinkr.form.section.basic')
            ->description('wcf.shrinkr.form.section.basic.description.description')
            ->appendChildren([
                TextFormField::create('title')
                    ->label('wcf.global.title')
                    ->description('wcf.shrinkr.description.title.description')
                    ->required()
                    ->autoFocus()
                    ->maximumLength(255),

                MultilineTextFormField::create('descriptionText')
                    ->label('wcf.shrinkr.description.descriptionText')
                    ->description('wcf.shrinkr.description.descriptionText.description')
                    ->required()
                    ->rows(10),
            ]);

        $this->form->appendChild($basicContainer);
    }

    /**
     * Saves the form and sets default values.
     * 
     * Sets isActive to true by default for new descriptions since the field
     * was removed from the form UI.
     *
     * @return  void
     */
    #[\Override]
    public function save()
    {
        if ($this->formAction === 'create') {
            $this->additionalFields['isActive'] = 1;
        }

        parent::save();
    }

    /**
     * @inheritDoc
     */
    #[\Override]
    public function assignVariables()
    {
        parent::assignVariables();

        \wcf\system\WCF::getTPL()->assign([
            'acpPageSubMenuCategoryList' => 'shrinkr.acp.menu.link.description.list'
        ]);
    }
}
