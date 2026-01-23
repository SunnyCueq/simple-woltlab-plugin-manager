<?php

namespace shrinkr\acp\form;

use CuyZ\Valinor\Mapper\MappingError;
use shrinkr\data\theme\Theme;
use wcf\http\Helper;
use wcf\system\exception\IllegalLinkException;
use wcf\system\form\builder\container\FormContainer;
use wcf\system\form\builder\container\TabFormContainer;
use wcf\system\form\builder\field\MultilineTextFormField;
use wcf\system\form\builder\field\SourceCodeFormField;
use wcf\system\form\builder\NoticeFormNode;
use wcf\system\form\builder\NoticeFormNodeType;
use wcf\system\WCF;

/**
 * Form for editing an existing theme.
 * 
 * ACP form for editing themes. Extends ThemeAddForm and adds a CSS tab for
 * editing custom CSS content. Loads the theme object from the database.
 *
 * @author      Sunny C
 * @copyright   2026 Sunny C
 * @license     License for Commercial Plugins
 * @link        https://sunnyc.de
 * @package     de.sunnyc.wsc.shrinkr
 * @subpackage  acp.form
 */

class ThemeEditForm extends ThemeAddForm
{
    /**
     * Active menu item identifier for navigation highlighting.
     *
     * @var    string
     */
    public $activeMenuItem = 'shrinkr.acp.menu.link.theme.list';

    /**
     * Form action name (edit, update, delete).
     *
     * @var    string
     */
    public $formAction = 'edit';

    /**
     * Reads request parameters and loads the theme to edit.
     * 
     * Validates the theme ID from query parameters and loads the Theme object.
     * Throws IllegalLinkException if the theme doesn't exist.
     *
     * @return  void
     * @throws  IllegalLinkException  If theme ID is invalid or theme doesn't exist
     */
    public function readParameters()
    {
        parent::readParameters();

        try {
            $queryParameters = Helper::mapQueryParameters(
                $_GET,
                <<<'EOT'
                    array {
                        id: positive-int
                    }
                    EOT
            );
            $this->formObject = new Theme($queryParameters['id']);

            if (!$this->formObject->themeID) {
                throw new IllegalLinkException();
            }
        } catch (MappingError) {
            throw new IllegalLinkException();
        }
    }

    /**
     * @inheritDoc
     */
    #[\Override]
    public function assignVariables()
    {
        parent::assignVariables();

        \wcf\system\WCF::getTPL()->assign([
            'acpPageSubMenuCategoryList' => 'shrinkr.acp.menu.link.theme.list'
        ]);
    }

    /**
     * @inheritDoc
     */
    protected function createForm()
    {
        parent::createForm();

        // Add CSS editor as tab if theme has CSS file
        // formObject is already loaded in readParameters()
        if ($this->formObject && $this->formObject->hasCssFile()) {
            // Get the tab menu container from parent (ThemeAddForm)
            $tabMenu = $this->form->getNodeById('themeTabs');
            
            if ($tabMenu) {
                // Load CSS content directly from file (not from database)
                $cssPath = $this->formObject->getCssFilePath();
                
                $cssContent = '';
                if ($cssPath !== null && file_exists($cssPath)) {
                    $fileContent = file_get_contents($cssPath);
                    if ($fileContent !== false) {
                        $cssContent = $fileContent;
                    }
                }
                
                // Fallback: Try loadCssContent() if direct file read failed
                if (empty($cssContent)) {
                    $cssContent = $this->formObject->loadCssContent() ?? '';
                }
                
                // === TAB 3: CSS bearbeiten ===
                $cssTab = TabFormContainer::create('cssTab')
                    ->label('wcf.shrinkr.form.tab.css')
                    ->description('wcf.shrinkr.theme.css.edit.description');
                
                $cssContainer = FormContainer::create('cssData');
                
                // Add warning notice using NoticeFormNode
                $cssWarning = NoticeFormNode::create('cssWarning')
                    ->type(NoticeFormNodeType::Warning)
                    ->languageItem('wcf.shrinkr.theme.css.edit.warning');
                
                $cssContainer->appendChildren([
                    $cssWarning,
                    SourceCodeFormField::create('cssContent')
                        ->label('wcf.shrinkr.theme.css.content')
                        ->description('wcf.shrinkr.theme.css.content.description')
                        ->language('css')
                        ->value($cssContent),
                ]);
                
                $cssTab->appendChild($cssContainer);
                $tabMenu->appendChild($cssTab);
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function readData()
    {
        parent::readData();

        // Sync colors from CSS file if theme has CSS file (ensures DB colors match CSS)
        // This is important because CSS files override database colors
        if ($this->formObject && $this->formObject->hasCssFile()) {
            // Automatically sync colors from CSS file to database
            $this->formObject->syncColorsFromCssFile();
            
            // Reload theme object to get updated colors from database
            $this->formObject = new Theme($this->formObject->themeID);
            
            // Update form fields with synced colors from CSS
            $primaryColorField = $this->form->getNodeById('primaryColor');
            $secondaryColorField = $this->form->getNodeById('secondaryColor');
            $primaryTextColorField = $this->form->getNodeById('primaryTextColor');
            $secondaryTextColorField = $this->form->getNodeById('secondaryTextColor');
            
            if ($primaryColorField && !empty($this->formObject->primaryColor)) {
                $primaryColorField->value($this->formObject->primaryColor);
            }
            if ($secondaryColorField && !empty($this->formObject->secondaryColor)) {
                $secondaryColorField->value($this->formObject->secondaryColor);
            }
            if ($primaryTextColorField && !empty($this->formObject->primaryTextColor)) {
                $primaryTextColorField->value($this->formObject->primaryTextColor);
            }
            if ($secondaryTextColorField && !empty($this->formObject->secondaryTextColor)) {
                $secondaryTextColorField->value($this->formObject->secondaryTextColor);
            }
            
            // Ensure CSS content is loaded in the form field
            // Load directly from file to ensure we have the latest content
            $cssPath = $this->formObject->getCssFilePath();
            $cssContent = '';
            if ($cssPath !== null && file_exists($cssPath)) {
                $fileContent = file_get_contents($cssPath);
                if ($fileContent !== false) {
                    $cssContent = $fileContent;
                }
            }
            
            // Fallback: Try loadCssContent() if direct file read failed
            if (empty($cssContent)) {
                $cssContent = $this->formObject->loadCssContent() ?? '';
            }
            
            // Set CSS content in form field - MUST be set here, not just in createForm()
            // SourceCodeFormField needs the value to be set before rendering
            $cssField = $this->form->getNodeById('cssContent');
            if ($cssField && !empty($cssContent)) {
                $cssField->value($cssContent);
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function save()
    {
        parent::save();

        // Save CSS content if theme has CSS file
        if ($this->formObject && $this->formObject->hasCssFile()) {
            $cssField = $this->form->getNodeById('cssContent');
            if ($cssField) {
                $cssContent = $cssField->getValue();
                if ($cssContent !== null) {
                    $this->formObject->saveCssContent($cssContent);
                }
            }
        }
    }
}

