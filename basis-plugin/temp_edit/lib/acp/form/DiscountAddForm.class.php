<?php

namespace shrinkr\acp\form;

use shrinkr\data\discount\DiscountAction;
use wcf\form\AbstractFormBuilderForm;
use wcf\system\form\builder\container\FormContainer;
use wcf\system\form\builder\container\TabFormContainer;
use wcf\system\form\builder\container\TabMenuFormContainer;
use wcf\system\form\builder\container\wysiwyg\WysiwygFormContainer;
use wcf\system\form\builder\container\RowFormFieldContainer;
use wcf\system\form\builder\data\processor\VoidFormDataProcessor;
use wcf\system\form\builder\field\dependency\NonEmptyFormFieldDependency;
use wcf\system\form\builder\field\BooleanFormField;
use wcf\system\form\builder\field\UploadFormField;
use wcf\system\form\builder\field\ColorFormField;
use wcf\system\form\builder\field\DateFormField;
use wcf\system\form\builder\field\ItemListFormField;
use wcf\system\form\builder\field\TextFormField;
use wcf\system\html\upcast\HtmlUpcastProcessor;
use wcf\system\WCF;

/**
 * Form for adding a new discount.
 *
 * @author      Sunny C
 * @copyright   2026 Sunny C
 * @license     License for Commercial Plugins
 *
 * @package    de.sunnyc.wsc.shrinkr
 * @subpackage acp.form
 */

class DiscountAddForm extends AbstractFormBuilderForm
{
    /**
     * @inheritDoc
     */
    public $neededPermissions = ['admin.shrinkr.canManageDiscounts'];

    /**
     * @inheritDoc
     */
    public $activeMenuItem = 'shrinkr.acp.menu.link.discount.add';

    /**
    * @inheritDoc
    */
    public $objectActionClass = DiscountAction::class;

    /**
     * @inheritDoc
     */
    #[\Override]
    protected function createForm()
    {
        parent::createForm();

        // Create tab menu container
        $tabMenu = TabMenuFormContainer::create('discountTabs');
        
        // === TAB 1: General (Allgemein) ===
        $generalTab = TabFormContainer::create('generalTab')
            ->label('wcf.global.form.data');
        
        $generalContainer = FormContainer::create('generalData');
        $generalContainer->appendChildren([
            TextFormField::create('discountValue')
                ->label('wcf.shrinkr.discount')
                ->description('wcf.shrinkr.discount.description')
                ->placeholder('wcf.shrinkr.discount.placeholder')
                ->required()
                ->autoFocus()
                ->maximumLength(255),
                
            UploadFormField::create('favicon')
                ->label('wcf.acp.style.general.favicon')
                ->description('wcf.shrinkr.discount.favicon.description')
                ->maximum(1)
                ->imageOnly(true),

            ItemListFormField::create('hosts')
                ->required()
                ->label('wcf.shrinkr.hosts')
                ->description('wcf.shrinkr.hosts.description'),

            ItemListFormField::create('codes')
                ->label('wcf.shrinkr.codes')
                ->description('wcf.shrinkr.codes.description')
                ->value('SHRINKR')
                ->required(),
        ]);
        $generalTab->appendChild($generalContainer);
        
        // === TAB 2: Special ===
        $specialTab = TabFormContainer::create('specialTab')
            ->label('wcf.shrinkr.special');
        
        $specialContainer = FormContainer::create('specialData');
        $specialContainer->appendChildren([
            BooleanFormField::create('special')
                ->label('wcf.shrinkr.special')
                ->value(false),

            TextFormField::create('specialIdentifier')
                ->label('wcf.shrinkr.special.identifier')
                ->description('wcf.shrinkr.special.identifier.description')
                ->required()
                ->maximumLength(255)
                ->addDependency(
                    NonEmptyFormFieldDependency::create('special')
                        ->fieldId('special')
                ),
        ]);
        $specialTab->appendChild($specialContainer);
        
        // === TAB 3: Design (Colors) ===
        // Load default colors from style_variable database table
        $sql = "SELECT variableName, defaultValue FROM wcf" . WCF_N . "_style_variable WHERE variableName IN (?, ?, ?)";
        $statement = WCF::getDB()->prepareStatement($sql);
        $statement->execute(['wcfHeaderBackground', 'wcfHeaderMenuBackground', 'wcfHeaderText']);
        $styleVariables = $statement->fetchMap('variableName', 'defaultValue');
        
        $defaultPrimaryColor = $styleVariables['wcfHeaderBackground'] ?? 'rgba(58, 109, 156, 1)';
        $defaultSecondaryColor = $styleVariables['wcfHeaderMenuBackground'] ?? 'rgba(44, 62, 80, 1)';
        $defaultTextColor = $styleVariables['wcfHeaderText'] ?? 'rgba(255, 255, 255, 1)';
        
        $designTab = TabFormContainer::create('designTab')
            ->label('wcf.shrinkr.design')
            ->description('Primäre Farben (linke Seite) und Sekundäre Farben (rechte Seite) des Promo Badges');
        
        $designContainer = FormContainer::create('designData');
        $designContainer->appendChildren([
            ColorFormField::create('primaryColor')
                ->label('wcf.shrinkr.primaryColor')
                ->description('wcf.shrinkr.primaryColor.description')
                ->value($defaultPrimaryColor)
                ->required(),

            ColorFormField::create('primaryTextColor')
                ->label('wcf.shrinkr.primaryTextColor')
                ->description('wcf.shrinkr.primaryTextColor.description')
                ->value($defaultTextColor)
                ->required(),

            ColorFormField::create('secondaryColor')
                ->label('wcf.shrinkr.secondaryColor')
                ->description('wcf.shrinkr.secondaryColor.description')
                ->value($defaultSecondaryColor)
                ->required(),

            ColorFormField::create('secondaryTextColor')
                ->label('wcf.shrinkr.secondaryTextColor')
                ->description('wcf.shrinkr.secondaryTextColor.description')
                ->value($defaultTextColor)
                ->required(),
        ]);
        $designTab->appendChild($designContainer);
        
        // === TAB 4: Zeitraum (Countdown) ===
        $timeTab = TabFormContainer::create('timeTab')
            ->label('wcf.shrinkr.countdown')
            ->description('Zeitraum für den Rabatt (optional, für zeitlich begrenzte Aktionen)');
        
        $timeContainer = FormContainer::create('timeData');
        $timeContainer->appendChildren([
            DateFormField::create('countdownStart')
                ->label('wcf.shrinkr.countdownStart')
                ->description('wcf.shrinkr.countdownStart.description')
                ->saveValueFormat('U')
                ->supportTime(true)
                ->value(strtotime('today')),

            DateFormField::create('countdownEnd')
                ->label('wcf.shrinkr.countdownEnd')
                ->description('wcf.shrinkr.countdownEnd.description')
                ->saveValueFormat('U')
                ->supportTime(true)
                ->value(strtotime('today 23:59:59')),
        ]);
        $timeTab->appendChild($timeContainer);
        
        // === TAB 5: Zusatztext (WYSIWYG) ===
        $textTab = TabFormContainer::create('textTab')
            ->label('wcf.shrinkr.additionalText')
            ->description('Zusätzlicher HTML-Text für den Rabatt (wird unter dem Promo Badge angezeigt)');
        
        $wysiwygContainer = WysiwygFormContainer::create('additionalText')
            ->messageObjectType('de.sunnyc.wsc.shrinkr.discount.additionalText');
        
        $textTab->appendChild($wysiwygContainer);
        
        // Append all tabs to tab menu
        $tabMenu->appendChild($generalTab);
        $tabMenu->appendChild($specialTab);
        $tabMenu->appendChild($designTab);
        $tabMenu->appendChild($timeTab);
        $tabMenu->appendChild($textTab);
        
        // Append tab menu to form
        $this->form->appendChild($tabMenu);

        // Remove obsolete special and countdown fields from discount forms
        $fieldsToRemove = ['special', 'specialIdentifier', 'countdownStart', 'countdownEnd'];
        
        foreach ($fieldsToRemove as $fieldId) {
            $field = $this->form->getNodeById($fieldId);
            if ($field !== null) {
                $field->available(false);
                $this->form->getDataHandler()->addProcessor(new VoidFormDataProcessor($fieldId));
            }
        }
    }

    /**
     * @inheritDoc
     */
    #[\Override]
    public function assignVariables()
    {
        parent::assignVariables();

        // HtmlUpcastProcessor für WYSIWYG-Feld (WoltLab 6.1 Best Practice)
        if (isset($this->formObject) && $this->formObject->discountID) {
            $upcastProcessor = new HtmlUpcastProcessor();
            $upcastProcessor->process(
                $this->formObject->additionalText ?? '',
                'de.sunnyc.wsc.shrinkr.discount.additionalText',
                $this->formObject->discountID
            );
            WCF::getTPL()->assign('additionalText', $upcastProcessor->getHtml());
        }
    }
}
