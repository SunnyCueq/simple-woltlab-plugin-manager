<?php

namespace shrinkr\acp\form;

use shrinkr\data\discount\DiscountAction;
use wcf\form\AbstractFormBuilderForm;
use wcf\system\form\builder\container\FormContainer;
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
 * @author      Sunny C. <https://benjaro.info>
 * @copyright   2026 Sunny C
 * @license     License for Commercial Plugins <https://benjaro.info>
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

        /* dataContainer */
        $dataContainer = FormContainer::create('data')
            ->label('wcf.global.form.data');

        /* append to dataContainer */
        $dataContainer->appendChildren([
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

            ItemListFormField::create('codes')
                ->label('wcf.shrinkr.codes')
                ->description('wcf.shrinkr.codes.description')
                ->value('BENJARO')
                ->required(),
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
            ->label('Farben für Promo Badge')
            ->description('Primäre Farben (linke Seite) und Sekundäre Farben (rechte Seite) des Promo Badges')
            ->appendChildren([
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
        
        // === COUNTDOWN SECTION ===
        $countdownContainer = FormContainer::create('countdown')
            ->label('Countdown (Optional)')
            ->description('Zeitraum für den Rabatt (optional, für zeitlich begrenzte Aktionen)')
            ->appendChildren([
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

        // === WYSIWYG Container for additionalText ===
        $wysiwygContainer = WysiwygFormContainer::create('additionalText')
            ->label('Zusätzlicher Text')
            ->description('Zusätzlicher HTML-Text für den Rabatt (wird unter dem Promo Badge angezeigt)')
            ->messageObjectType('de.sunnyc.wsc.shrinkr.discount.additionalText');

        // Append all containers to form
        $this->form->appendChild($dataContainer);
        $this->form->appendChild($colorContainer);
        $this->form->appendChild($countdownContainer);
        $this->form->appendChild($wysiwygContainer);

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
