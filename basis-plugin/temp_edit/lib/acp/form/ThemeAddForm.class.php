<?php

namespace urlshort\acp\form;

use urlshort\data\theme\ThemeAction;
use wcf\form\AbstractFormBuilderForm;
use wcf\system\form\builder\container\FormContainer;
use wcf\system\form\builder\field\BooleanFormField;
use wcf\system\form\builder\field\ColorFormField;
use wcf\system\form\builder\field\IntegerFormField;
use wcf\system\form\builder\field\SingleSelectionFormField;
use wcf\system\form\builder\field\TextFormField;
use wcf\system\WCF;

/**
 * Form for adding a new theme.
 *
 * @author      Sunny C. <https://benjaro.info>
 * @copyright   2022-2025 Benjaro
 * @license     License for Commercial Plugins <https://benjaro.info>
 *
 * @package    dev.tkirch.wsc.urlshort
 * @subpackage acp.form
 */

class ThemeAddForm extends AbstractFormBuilderForm
{
    /**
     * @inheritDoc
     */
    public $neededPermissions = ['admin.urlshort.canManageThemes'];

    /**
     * @inheritDoc
     */
    public $activeMenuItem = 'urlshort.acp.menu.link.theme.add';

    /**
    * @inheritDoc
    */
    public $objectActionClass = ThemeAction::class;

    /**
     * @inheritDoc
     */
    #[\Override]
    protected function createForm()
    {
        parent::createForm();

        // === BASIC SETTINGS ===
        $basicContainer = FormContainer::create('basic')
            ->label('wcf.urlshort.form.section.basic')
            ->description('wcf.urlshort.form.section.basic.description')
            ->appendChildren([
                TextFormField::create('identifier')
                    ->label('wcf.urlshort.theme.identifier')
                    ->description('wcf.urlshort.theme.identifier.description')
                    ->required()
                    ->autoFocus()
                    ->maximumLength(255),

                TextFormField::create('title')
                    ->label('wcf.urlshort.theme.title')
                    ->description('wcf.urlshort.theme.title.description')
                    ->required()
                    ->maximumLength(255),

                SingleSelectionFormField::create('effectIdentifier')
                    ->label('wcf.urlshort.theme.effect')
                    ->description('wcf.urlshort.theme.effect.description')
                    ->options([
                        'none' => 'wcf.urlshort.theme.effect.none',
                        'autumnLeaves' => 'wcf.urlshort.theme.effect.autumnLeaves',
                        'snow' => 'wcf.urlshort.theme.effect.snow',
                        'ghosts' => 'wcf.urlshort.theme.effect.ghosts',
                    ])
                    ->value('none'),

                BooleanFormField::create('isActive')
                    ->label('wcf.urlshort.theme.isActive')
                    ->description('wcf.urlshort.theme.isActive.description')
                    ->value(true),

                IntegerFormField::create('sortOrder')
                    ->label('wcf.urlshort.theme.sortOrder')
                    ->description('wcf.urlshort.theme.sortOrder.description')
                    ->value(0)
                    ->minimum(0),
            ]);
        
        // Load default colors from style_variable database table (like WoltLab's StyleAddForm does)
        $sql = "SELECT variableName, defaultValue FROM wcf" . WCF_N . "_style_variable WHERE variableName IN (?, ?, ?)";
        $statement = WCF::getDB()->prepareStatement($sql);
        $statement->execute(['wcfHeaderBackground', 'wcfHeaderMenuBackground', 'wcfHeaderText']);
        $styleVariables = $statement->fetchMap('variableName', 'defaultValue');
        
        // Fallback to WoltLab Corporate Identity if variables not found
        $defaultPrimaryColor = $styleVariables['wcfHeaderBackground'] ?? 'rgba(58, 109, 156, 1)';
        $defaultSecondaryColor = $styleVariables['wcfHeaderMenuBackground'] ?? 'rgba(44, 62, 80, 1)';
        $defaultTextColor = $styleVariables['wcfHeaderText'] ?? 'rgba(255, 255, 255, 1)';
        
        // === COLOR SECTION for Promo Badge ===
        $colorContainer = FormContainer::create('colors')
            ->label('wcf.urlshort.form.section.colors')
            ->description('wcf.urlshort.form.section.colors.description')
            ->appendChildren([
                ColorFormField::create('primaryColor')
                    ->label('wcf.urlshort.primaryColor')
                    ->description('wcf.urlshort.theme.primaryColor.description')
                    ->value($defaultPrimaryColor)
                    ->required(),

                ColorFormField::create('primaryTextColor')
                    ->label('wcf.urlshort.primaryTextColor')
                    ->description('wcf.urlshort.theme.primaryTextColor.description')
                    ->value($defaultTextColor)
                    ->required(),

                ColorFormField::create('secondaryColor')
                    ->label('wcf.urlshort.secondaryColor')
                    ->description('wcf.urlshort.theme.secondaryColor.description')
                    ->value($defaultSecondaryColor)
                    ->required(),

                ColorFormField::create('secondaryTextColor')
                    ->label('wcf.urlshort.secondaryTextColor')
                    ->description('wcf.urlshort.theme.secondaryTextColor.description')
                    ->value($defaultTextColor)
                    ->required(),
            ]);

        // Append all containers to form
        $this->form->appendChild($basicContainer);
        $this->form->appendChild($colorContainer);
    }
}

