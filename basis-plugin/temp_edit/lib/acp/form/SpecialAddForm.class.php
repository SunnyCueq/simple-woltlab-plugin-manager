<?php

namespace shrinkr\acp\form;

use shrinkr\data\special\SpecialAction;
use shrinkr\system\special\SpecialThemeHelper;
use wcf\form\AbstractFormBuilderForm;
use wcf\system\exception\IllegalLinkException;
use wcf\system\form\builder\container\FormContainer;
use wcf\system\form\builder\container\wysiwyg\WysiwygFormContainer;
use wcf\system\form\builder\field\BooleanFormField;
use wcf\system\form\builder\field\ColorFormField;
use wcf\system\form\builder\field\DateFormField;
use wcf\system\form\builder\field\HiddenFormField;
use wcf\system\form\builder\field\ItemListFormField;
use wcf\system\form\builder\field\SelectFormField;
use wcf\system\form\builder\field\TextFormField;
use wcf\system\html\upcast\HtmlUpcastProcessor;
use wcf\system\WCF;

/**
 * Form for adding a new special.
 *
 * @author      Sunny C. <https://benjaro.info>
 * @copyright   2026 Sunny C
 * @license     License for Commercial Plugins <https://benjaro.info>
 *
 * @package    de.sunnyc.wsc.shrinkr
 * @subpackage acp.form
 */
class SpecialAddForm extends AbstractFormBuilderForm
{
    /**
     * @inheritDoc
     */
    public $neededPermissions = ['admin.shrinkr.canManageSpecials'];

    /**
     * @inheritDoc
     */
    public $activeMenuItem = 'shrinkr.acp.menu.link.special.list';

    /**
     * @inheritDoc
     */
    public $objectActionClass = SpecialAction::class;

    /**
     * @inheritDoc
     */
    public $additionalFields = ['discountID' => 0];

    /**
     * URL ID (required parameter from URL query)
     */
    public int $linkID = 0;

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

        if (isset($_REQUEST['linkID'])) {
            $this->linkID = (int) $_REQUEST['linkID'];
        }

        // URL ID is required
        if ($this->linkID === 0) {
            throw new IllegalLinkException();
        }

        // Load URL data (hash)
        $sql = "SELECT hash FROM shrinkr1_link WHERE linkID = ?";
        $statement = WCF::getDB()->prepareStatement($sql);
        $statement->execute([$this->linkID]);
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

        // Get theme options
        $themeOptions = SpecialThemeHelper::getThemeOptions();
        
        // Load default colors from style_variable database table (like WoltLab's StyleAddForm does)
        $sql = "SELECT variableName, defaultValue FROM wcf" . WCF_N . "_style_variable WHERE variableName IN (?, ?, ?)";
        $statement = WCF::getDB()->prepareStatement($sql);
        $statement->execute(['wcfHeaderBackground', 'wcfHeaderMenuBackground', 'wcfHeaderText']);
        $styleVariables = $statement->fetchMap('variableName', 'defaultValue');
        
        // Fallback to WoltLab Corporate Identity if variables not found
        $defaultPrimaryColor = $styleVariables['wcfHeaderBackground'] ?? 'rgba(58, 109, 156, 1)';
        $defaultSecondaryColor = $styleVariables['wcfHeaderMenuBackground'] ?? 'rgba(44, 62, 80, 1)';
        $defaultTextColor = $styleVariables['wcfHeaderText'] ?? 'rgba(255, 255, 255, 1)';

        $dataContainer->appendChildren([
            // Hidden field for linkID
            HiddenFormField::create('linkID')
                ->value($this->linkID),

            // === BASIC SETTINGS ===
            BooleanFormField::create('isActive')
                ->label('wcf.shrinkr.special.isActive')
                ->description('wcf.shrinkr.special.isActive.description')
                ->value(true),

            SelectFormField::create('theme')
                ->label('wcf.shrinkr.special.theme')
                ->description('wcf.shrinkr.special.theme.description')
                ->options($themeOptions)
                ->value(''),

            TextFormField::create('title')
                ->label('wcf.global.title')
                ->description('wcf.shrinkr.special.title.description')
                ->required()
                ->maximumLength(255),

            TextFormField::create('discount')
                ->label('wcf.shrinkr.special.discount')
                ->description('wcf.shrinkr.special.discount.description')
                ->required()
                ->maximumLength(255)
                ->placeholder('z.B. 30%'),

            ItemListFormField::create('codes')
                ->label('wcf.shrinkr.codes')
                ->description('wcf.shrinkr.special.codes.description')
                ->value('BENJARO'),
        ]);
        
        // === COLOR SECTION for Promo Badge ===
        $colorContainer = FormContainer::create('colors')
            ->label('Farben für Promo Badge')
            ->description('Primäre Farben (linke Seite) und Sekundäre Farben (rechte Seite) des Promo Badges')
            ->appendChildren([
                ColorFormField::create('primaryColor')
                    ->label('wcf.shrinkr.primaryColor')
                    ->value($defaultPrimaryColor)
                    ->required(),

                ColorFormField::create('primaryTextColor')
                    ->label('wcf.shrinkr.primaryTextColor')
                    ->value($defaultTextColor)
                    ->required(),

                ColorFormField::create('secondaryColor')
                    ->label('wcf.shrinkr.secondaryColor')
                    ->value($defaultSecondaryColor)
                    ->required(),

                ColorFormField::create('secondaryTextColor')
                    ->label('wcf.shrinkr.secondaryTextColor')
                    ->value($defaultTextColor)
                    ->required(),
            ]);
        
        // === COUNTDOWN SECTION ===
        $countdownContainer = FormContainer::create('countdown')
            ->label('Countdown')
            ->description('Zeitraum für das Special (optional)')
            ->appendChildren([
                DateFormField::create('startTime')
                    ->label('wcf.shrinkr.special.startTime')
                    ->description('wcf.shrinkr.special.startTime.description')
                    ->saveValueFormat('U')
                    ->supportTime(true)
                    ->value(TIME_NOW),

                DateFormField::create('endTime')
                    ->label('wcf.shrinkr.special.endTime')
                    ->description('wcf.shrinkr.special.endTime.description')
                    ->saveValueFormat('U')
                    ->supportTime(true)
                    ->value(TIME_NOW + 86400),
            ]);

        // WYSIWYG Container for additionalText
        $wysiwygContainer = WysiwygFormContainer::create('additionalText')
            ->label('Zusätzlicher Text')
            ->description('Zusätzlicher HTML-Text für das Special (überschreibt normalen Zusatztext)')
            ->messageObjectType('de.sunnyc.wsc.shrinkr.special.additionalText');

        $this->form->appendChild($dataContainer);
        $this->form->appendChild($colorContainer);
        $this->form->appendChild($countdownContainer);
        $this->form->appendChild($wysiwygContainer);
    }

    /**
     * @inheritDoc
     */
    #[\Override]
    public function save()
    {
        // Convert codes array to comma-separated string BEFORE parent::save()
        $codesField = $this->form->getNodeById('codes');
        if ($codesField && $codesField->getValue() && is_array($codesField->getValue())) {
            // Filter out empty codes and trim
            $filteredCodes = array_filter(array_map('trim', $codesField->getValue()), function($code) {
                return !empty($code);
            });
            $codesField->value(implode(',', $filteredCodes));
        }
        
        // Handle theme field: convert null to empty string BEFORE parent::save()
        $themeField = $this->form->getNodeById('theme');
        
        if ($themeField) {
            $themeValue = $themeField->getValue();
            
            if ($themeValue === null || $themeValue === '') {
                $themeField->value('');
            }
        }
        
        parent::save();
    }
    
    /**
     * @inheritDoc
     */
    #[\Override]
    protected function setFormObjectData()
    {
        parent::setFormObjectData();
        
        // Only process if form was submitted (not on Edit form initial load)
        // getData() throws BadMethodCallException if readValues() wasn't called yet
        if (!isset($this->parameters['data'])) {
            return;
        }
        
        $data = $this->parameters['data'];
        
        // Ensure theme is never null (database constraint)
        if (!isset($data['theme']) || $data['theme'] === null) {
            $this->parameters['data']['theme'] = '';
        }
        
        // If theme is selected, override colors with theme colors
        if (!empty($data['theme'])) {
            $theme = SpecialThemeHelper::getTheme($data['theme']);
            if ($theme) {
                $this->parameters['data']['primaryColor'] = $theme['primaryColor'];
                $this->parameters['data']['secondaryColor'] = $theme['secondaryColor'];
                $this->parameters['data']['primaryTextColor'] = $theme['primaryTextColor'];
                $this->parameters['data']['secondaryTextColor'] = $theme['secondaryTextColor'];
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
        if (isset($this->formObject) && $this->formObject->specialID) {
            $upcastProcessor = new HtmlUpcastProcessor();
            $upcastProcessor->process(
                $this->formObject->additionalText ?? '',
                'de.sunnyc.wsc.shrinkr.special.additionalText',
                $this->formObject->specialID
            );
            WCF::getTPL()->assign('additionalText', $upcastProcessor->getHtml());
        }

        // Get themes (with fallback to empty array)
        try {
            $themes = SpecialThemeHelper::getThemes();
        } catch (\Exception) {
            $themes = [];
        }

        WCF::getTPL()->assign([
            'linkID' => $this->linkID,
            'urlHash' => $this->urlHash ?? '',
            'themes' => $themes,
        ]);
    }
}

