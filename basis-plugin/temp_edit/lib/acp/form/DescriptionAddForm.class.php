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
 * @author      Sunny C
 * @copyright   2026 Sunny C
 * @license     License for Commercial Plugins
 *
 * @package    de.sunnyc.wsc.shrinkr
 * @subpackage acp.form
 */
class DescriptionAddForm extends AbstractFormBuilderForm
{
    /**
     * @inheritDoc
     */
    public $neededPermissions = ['admin.shrinkr.canManageDescriptions'];

    /**
     * @inheritDoc
     */
    public $activeMenuItem = 'shrinkr.acp.menu.link.description.add';

    /**
     * @inheritDoc
     */
    public $objectActionClass = DescriptionAction::class;

    /**
     * @inheritDoc
     */
    protected function createForm()
    {
        parent::createForm();

        // === BASIC SETTINGS ===
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

        // === SETTINGS ===
        $settingsContainer = FormContainer::create('settings')
            ->label('wcf.shrinkr.form.section.settings')
            ->description('wcf.shrinkr.form.section.settings.description')
            ->appendChildren([
                BooleanFormField::create('isActive')
                    ->label('wcf.shrinkr.description.isActive')
                    ->description('wcf.shrinkr.description.isActive.description')
                    ->value(true),
            ]);

        $this->form->appendChild($basicContainer);
        $this->form->appendChild($settingsContainer);
    }
}
