<?php

namespace urlshort\acp\form;

use urlshort\data\description\DescriptionAction;
use wcf\form\AbstractFormBuilderForm;
use wcf\system\form\builder\container\FormContainer;
use wcf\system\form\builder\field\BooleanFormField;
use wcf\system\form\builder\field\MultilineTextFormField;
use wcf\system\form\builder\field\TextFormField;

/**
 * Form for adding a new description.
 *
 * @author      Sunny C. <https://benjaro.info>
 * @copyright   2022-2025 Benjaro
 * @license     License for Commercial Plugins <https://benjaro.info>
 *
 * @package    dev.tkirch.wsc.urlshort
 * @subpackage acp.form
 */
class DescriptionAddForm extends AbstractFormBuilderForm
{
    /**
     * @inheritDoc
     */
    public $neededPermissions = ['admin.urlshort.canManageDescriptions'];

    /**
     * @inheritDoc
     */
    public $activeMenuItem = 'urlshort.acp.menu.link.description.add';

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
            ->label('wcf.urlshort.form.section.basic')
            ->description('wcf.urlshort.form.section.basic.description.description')
            ->appendChildren([
                TextFormField::create('title')
                    ->label('wcf.global.title')
                    ->description('wcf.urlshort.description.title.description')
                    ->required()
                    ->autoFocus()
                    ->maximumLength(255),

                MultilineTextFormField::create('descriptionText')
                    ->label('wcf.urlshort.description.descriptionText')
                    ->description('wcf.urlshort.description.descriptionText.description')
                    ->required()
                    ->rows(10),
            ]);

        // === SETTINGS ===
        $settingsContainer = FormContainer::create('settings')
            ->label('wcf.urlshort.form.section.settings')
            ->description('wcf.urlshort.form.section.settings.description')
            ->appendChildren([
                BooleanFormField::create('isActive')
                    ->label('wcf.urlshort.description.isActive')
                    ->description('wcf.urlshort.description.isActive.description')
                    ->value(true),
            ]);

        $this->form->appendChild($basicContainer);
        $this->form->appendChild($settingsContainer);
    }
}
