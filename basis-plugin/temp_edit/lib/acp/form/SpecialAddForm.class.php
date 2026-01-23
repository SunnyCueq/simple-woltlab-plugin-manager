<?php

namespace shrinkr\acp\form;

use shrinkr\data\special\SpecialAction;
use shrinkr\system\special\SpecialThemeHelper;
use wcf\form\AbstractFormBuilderForm;
use wcf\system\exception\IllegalLinkException;
use wcf\system\form\builder\container\FormContainer;
use wcf\system\form\builder\container\TabFormContainer;
use wcf\system\form\builder\container\TabMenuFormContainer;
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
 * ACP form for creating special events/promotions. Requires linkID parameter
 * from URL query. Provides form fields for theme, colors, discount codes,
 * time range, and additional text. Uses WoltLab's FormBuilder API with
 * tabbed interface.
 *
 * @author      Sunny C
 * @copyright   2026 Sunny C
 * @license     License for Commercial Plugins
 * @link        https://sunnyc.de
 * @package     de.sunnyc.wsc.shrinkr
 * @subpackage  acp.form
 */
class SpecialAddForm extends AbstractFormBuilderForm
{
    /**
     * Required permissions to access this form.
     *
     * @var    string[]
     */
    public $neededPermissions = ['admin.shrinkr.canManageSpecials'];

    /**
     * Active menu item identifier for navigation highlighting.
     *
     * @var    string
     */
    public $activeMenuItem = 'shrinkr.acp.menu.link.special.list';

    /**
     * Action class for handling form submissions.
     *
     * @var    string
     */
    public $objectActionClass = SpecialAction::class;

    /**
     * Additional fields to save with the form object.
     *
     * @var    array
     */
    public $additionalFields = ['discountID' => 0];

    /**
     * URL ID (required parameter from URL query).
     *
     * @var    int
     */
    public int $linkID = 0;

    /**
     * URL hash for display purposes.
     *
     * @var    string
     */
    public string $urlHash = '';

    /**
     * Reads request parameters and validates linkID.
     * 
     * Extracts linkID from request parameters. Throws IllegalLinkException
     * if linkID is missing.
     *
     * @return  void
     * @throws  IllegalLinkException  If linkID is invalid
     */
    #[\Override]
    public function readParameters()
    {
        parent::readParameters();

        if (isset($_REQUEST['linkID'])) {
            $this->linkID = (int) $_REQUEST['linkID'];
        }

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

        // Create tab menu container
        $tabMenu = TabMenuFormContainer::create('specialTabs');

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

        // === TAB 1: Allgemein ===
        $generalTab = TabFormContainer::create('generalTab')
            ->label('wcf.global.form.data');
        
        $generalContainer = FormContainer::create('generalData');
        $generalContainer->appendChildren([
            // Hidden field for linkID
            HiddenFormField::create('linkID')
                ->value($this->linkID),

            SelectFormField::create('theme')
                ->label('wcf.shrinkr.special.theme')
                ->description('wcf.shrinkr.special.theme.description')
                ->options($themeOptions)
                ->value(''),

            TextFormField::create('title')
                ->label('wcf.global.title')
                ->description('wcf.shrinkr.special.title.description')
                ->required()
                ->autoFocus()
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
                ->value('SHRINKR'),
        ]);
        $generalTab->appendChild($generalContainer);
        
        // === TAB 2: Design ===
        $designTab = TabFormContainer::create('designTab')
            ->label('wcf.shrinkr.form.section.colors')
            ->description('wcf.shrinkr.form.section.colors.description');
        
        $designContainer = FormContainer::create('designData');
        $designContainer->appendChildren([
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
        $designTab->appendChild($designContainer);
        
        // === TAB 3: Zeitraum ===
        $periodTab = TabFormContainer::create('periodTab')
            ->label('wcf.shrinkr.form.tab.period')
            ->description('Zeitraum für das Special (optional)');
        
        $periodContainer = FormContainer::create('periodData');
        $periodContainer->appendChildren([
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
        $periodTab->appendChild($periodContainer);

        // === TAB 4: Zusatztext ===
        $additionalTextTab = TabFormContainer::create('additionalTextTab')
            ->label('wcf.shrinkr.form.tab.additionalText');
        
        // WYSIWYG Container for additionalText
        $wysiwygContainer = WysiwygFormContainer::create('additionalText')
            ->description('wcf.shrinkr.special.additionalText.description')
            ->messageObjectType('de.sunnyc.wsc.shrinkr.special.additionalText');
        
        $additionalTextTab->appendChild($wysiwygContainer);

        // Append tabs to tab menu
        $tabMenu->appendChild($generalTab);
        $tabMenu->appendChild($designTab);
        $tabMenu->appendChild($periodTab);
        $tabMenu->appendChild($additionalTextTab);
        
        // Append tab menu to form
        $this->form->appendChild($tabMenu);
    }

    /**
     * @inheritDoc
     */
    #[\Override]
    public function save()
    {
        // Set isActive to true by default if not set (since field was removed from form)
        if ($this->formAction === 'create') {
            $this->additionalFields['isActive'] = 1;
        }

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
            'acpPageSubMenuCategoryList' => 'shrinkr.acp.menu.link.special.list',
        ]);
    }
}

