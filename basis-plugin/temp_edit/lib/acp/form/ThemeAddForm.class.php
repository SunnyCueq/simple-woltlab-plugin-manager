<?php

namespace shrinkr\acp\form;

use shrinkr\data\theme\ThemeAction;
use wcf\form\AbstractFormBuilderForm;
use wcf\system\form\builder\container\FormContainer;
use wcf\system\form\builder\container\TabFormContainer;
use wcf\system\form\builder\container\TabMenuFormContainer;
use wcf\system\form\builder\field\BooleanFormField;
use wcf\system\form\builder\field\ColorFormField;
use wcf\system\form\builder\field\IntegerFormField;
use wcf\system\form\builder\field\SingleSelectionFormField;
use wcf\system\form\builder\field\TextFormField;
use wcf\system\WCF;

/**
 * Form for adding a new theme.
 *
 * @author      Sunny C
 * @copyright   2026 Sunny C
 * @license     License for Commercial Plugins
 *
 * @package    de.sunnyc.wsc.shrinkr
 * @subpackage acp.form
 */

class ThemeAddForm extends AbstractFormBuilderForm
{
    /**
     * @inheritDoc
     */
    public $neededPermissions = ['admin.shrinkr.canManageThemes'];

    /**
     * @inheritDoc
     */
    public $activeMenuItem = 'shrinkr.acp.menu.link.theme.add';

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

        // Create tab menu container
        $tabMenu = TabMenuFormContainer::create('themeTabs');
        
        // === TAB 1: Basic Settings ===
        $basicTab = TabFormContainer::create('basicTab')
            ->label('wcf.shrinkr.form.section.basic')
            ->description('wcf.shrinkr.form.section.basic.description');
        
        $basicContainer = FormContainer::create('basicData');
        $basicContainer->appendChildren([
            TextFormField::create('identifier')
                ->label('wcf.shrinkr.theme.identifier')
                ->description('wcf.shrinkr.theme.identifier.description')
                ->required()
                ->autoFocus()
                ->maximumLength(255),

            TextFormField::create('title')
                ->label('wcf.shrinkr.theme.title')
                ->description('wcf.shrinkr.theme.title.description')
                ->required()
                ->maximumLength(255),

            SingleSelectionFormField::create('effectIdentifier')
                ->label('wcf.shrinkr.theme.effect')
                ->description('wcf.shrinkr.theme.effect.description')
                ->options([
                    'none' => 'wcf.shrinkr.theme.effect.none',
                    'autumnLeaves' => 'wcf.shrinkr.theme.effect.autumnLeaves',
                    'snow' => 'wcf.shrinkr.theme.effect.snow',
                    'ghosts' => 'wcf.shrinkr.theme.effect.ghosts',
                ])
                ->value('none'),

            BooleanFormField::create('isActive')
                ->label('wcf.shrinkr.theme.isActive')
                ->description('wcf.shrinkr.theme.isActive.description')
                ->value(true),

            IntegerFormField::create('sortOrder')
                ->label('wcf.shrinkr.theme.sortOrder')
                ->description('wcf.shrinkr.theme.sortOrder.description')
                ->value(0)
                ->minimum(0),
        ]);
        $basicTab->appendChild($basicContainer);
        
        // === TAB 2: Colors ===
        // Load default colors from style_variable database table
        $sql = "SELECT variableName, defaultValue FROM wcf" . WCF_N . "_style_variable WHERE variableName IN (?, ?, ?)";
        $statement = WCF::getDB()->prepareStatement($sql);
        $statement->execute(['wcfHeaderBackground', 'wcfHeaderMenuBackground', 'wcfHeaderText']);
        $styleVariables = $statement->fetchMap('variableName', 'defaultValue');
        
        $defaultPrimaryColor = $styleVariables['wcfHeaderBackground'] ?? 'rgba(58, 109, 156, 1)';
        $defaultSecondaryColor = $styleVariables['wcfHeaderMenuBackground'] ?? 'rgba(44, 62, 80, 1)';
        $defaultTextColor = $styleVariables['wcfHeaderText'] ?? 'rgba(255, 255, 255, 1)';
        
        $colorTab = TabFormContainer::create('colorTab')
            ->label('wcf.shrinkr.form.section.colors')
            ->description('wcf.shrinkr.form.section.colors.description');
        
        $colorContainer = FormContainer::create('colorData');
        $colorContainer->appendChildren([
            ColorFormField::create('primaryColor')
                ->label('wcf.shrinkr.primaryColor')
                ->description('wcf.shrinkr.theme.primaryColor.description')
                ->value($defaultPrimaryColor)
                ->required(),

            ColorFormField::create('primaryTextColor')
                ->label('wcf.shrinkr.primaryTextColor')
                ->description('wcf.shrinkr.theme.primaryTextColor.description')
                ->value($defaultTextColor)
                ->required(),

            ColorFormField::create('secondaryColor')
                ->label('wcf.shrinkr.secondaryColor')
                ->description('wcf.shrinkr.theme.secondaryColor.description')
                ->value($defaultSecondaryColor)
                ->required(),

            ColorFormField::create('secondaryTextColor')
                ->label('wcf.shrinkr.secondaryTextColor')
                ->description('wcf.shrinkr.theme.secondaryTextColor.description')
                ->value($defaultTextColor)
                ->required(),
        ]);
        $colorTab->appendChild($colorContainer);

        // Append tabs to tab menu
        $tabMenu->appendChild($basicTab);
        $tabMenu->appendChild($colorTab);
        
        // Append tab menu to form
        $this->form->appendChild($tabMenu);
    }
}

