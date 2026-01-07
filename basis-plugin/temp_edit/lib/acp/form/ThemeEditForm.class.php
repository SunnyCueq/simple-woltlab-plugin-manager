<?php

namespace urlshort\acp\form;

use CuyZ\Valinor\Mapper\MappingError;
use urlshort\data\theme\Theme;
use wcf\http\Helper;
use wcf\system\exception\IllegalLinkException;
use wcf\system\form\builder\container\FormContainer;
use wcf\system\form\builder\field\MultilineTextFormField;
use wcf\system\form\builder\field\SourceCodeFormField;
use wcf\system\WCF;

/**
 * Form for editing an existing theme.
 *
 * @author      Sunny C. <https://benjaro.info>
 * @copyright   2022-2025 Benjaro
 * @license     License for Commercial Plugins <https://benjaro.info>
 *
 * @package    dev.tkirch.wsc.urlshort
 * @subpackage acp.form
 */

class ThemeEditForm extends ThemeAddForm
{
    /**
     * @inheritDoc
     */
    public $formAction = 'edit';

    /**
     * @inheritDoc
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
    protected function createForm()
    {
        parent::createForm();

        // Add CSS editor container if theme has CSS file
        // formObject is already loaded in readParameters()
        if ($this->formObject && $this->formObject->hasCssFile()) {
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
            
            $cssContainer = FormContainer::create('css')
                ->label('wcf.urlshort.theme.css.edit')
                ->description(
                    WCF::getLanguage()->get('wcf.urlshort.theme.css.edit.description') . 
                    '<p class="warning">' . 
                    WCF::getLanguage()->get('wcf.urlshort.theme.css.edit.warning') . 
                    '</p>'
                );

            // Use SourceCodeFormField for CSS editing (like WoltLab's style editor)
            $cssContainer->appendChildren([
                SourceCodeFormField::create('cssContent')
                    ->label('wcf.urlshort.theme.css.content')
                    ->description('wcf.urlshort.theme.css.content.description')
                    ->language('css')
                    ->value($cssContent),
            ]);

            $this->form->appendChild($cssContainer);
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

