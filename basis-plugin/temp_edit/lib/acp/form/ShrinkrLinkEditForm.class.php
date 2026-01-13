<?php

namespace shrinkr\acp\form;

use CuyZ\Valinor\Mapper\MappingError;
use shrinkr\data\custombutton\CustomButtonList;
use shrinkr\data\featuredlink\FeaturedLinkList;
use shrinkr\data\special\SpecialList;
use shrinkr\data\shrinkrlink\ShrinkrLink;
use wcf\http\Helper;
use wcf\system\exception\IllegalLinkException;
use wcf\system\form\builder\container\FormContainer;
use wcf\system\form\builder\container\TabFormContainer;
use wcf\system\form\builder\TemplateFormNode;
use wcf\system\WCF;

/**
 * Form for editing an existing short link.
 *
 * @author      Sunny C
 * @copyright   2026 Sunny C
 * @license     License for Commercial Plugins
 *
 * @package    de.sunnyc.wsc.shrinkr
 * @subpackage acp.form
 */
class ShrinkrLinkEditForm extends ShrinkrLinkAddForm
{
    /**
     * @inheritDoc
     */
    public $activeMenuItem = 'shrinkr.acp.menu.link.menu';

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
            $this->formObject = new ShrinkrLink($queryParameters['id']);

            if (!$this->formObject->linkID) {
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

        // Get the tab menu container
        $tabMenu = $this->form->getNodeById('linkTabMenu');

        if ($tabMenu) {
            // === FEATURED LINKS TAB ===
            $featuredLinksTab = TabFormContainer::create('featuredLinksTab')
                ->label('wcf.shrinkr.featuredLink.section');

            $featuredLinksContainer = FormContainer::create('featuredLinksContainer');
            
            // Use TemplateFormNode for Featured Links content
            $featuredLinksTemplate = TemplateFormNode::create('featuredLinksTemplate')
                ->application('shrinkr')
                ->templateName('__shrinkrLinkEditFeaturedLinksSection')
                ->variables([
                    'action' => 'edit',
                    'linkID' => $this->formObject->linkID ?? 0,
                    'urlFeaturedLinks' => $this->getFeaturedLinks(),
                ]);
            
            $featuredLinksContainer->appendChild($featuredLinksTemplate);
            $featuredLinksTab->appendChild($featuredLinksContainer);

            // === CUSTOM BUTTONS TAB ===
            $customButtonsTab = TabFormContainer::create('customButtonsTab')
                ->label('wcf.shrinkr.customButton.section');

            $customButtonsContainer = FormContainer::create('customButtonsContainer');
            
            // Use TemplateFormNode for Custom Buttons content
            $customButtonsTemplate = TemplateFormNode::create('customButtonsTemplate')
                ->application('shrinkr')
                ->templateName('__shrinkrLinkEditCustomButtonsSection')
                ->variables([
                    'action' => 'edit',
                    'linkID' => $this->formObject->linkID ?? 0,
                    'urlCustomButtons' => $this->getCustomButtons(),
                ]);
            
            $customButtonsContainer->appendChild($customButtonsTemplate);
            $customButtonsTab->appendChild($customButtonsContainer);

            $tabMenu->appendChild($featuredLinksTab);
            $tabMenu->appendChild($customButtonsTab);
        }
    }

    /**
     * Returns featured links for the current URL.
     *
     * @return array
     */
    protected function getFeaturedLinks(): array
    {
        if (!$this->formObject || !$this->formObject->linkID) {
            return [];
        }

        $featuredLinkList = new FeaturedLinkList();
        $featuredLinkList->getConditionBuilder()->add('linkID = ?', [$this->formObject->linkID]);
        $featuredLinkList->sqlOrderBy = 'sortOrder ASC, linkID ASC';
        $featuredLinkList->readObjects();

        return $featuredLinkList->getObjects();
    }

    /**
     * Returns custom buttons for the current URL.
     *
     * @return array
     */
    protected function getCustomButtons(): array
    {
        if (!$this->formObject || !$this->formObject->linkID) {
            return [];
        }

        $customButtonList = new CustomButtonList();
        $customButtonList->getConditionBuilder()->add('linkID = ?', [$this->formObject->linkID]);
        $customButtonList->sqlOrderBy = 'sortOrder ASC';
        $customButtonList->readObjects();

        return $customButtonList->getObjects();
    }

    /**
     * @inheritDoc
     */
    public function assignVariables()
    {
        parent::assignVariables();

        // Load specials for this URL
        $urlSpecials = [];
        $urlActiveSpecials = [];
        $hasActiveSpecials = false;
        $firstActiveSpecialID = null;

        if ($this->formObject && $this->formObject->linkID) {
            $specialList = new SpecialList();
            $specialList->getConditionBuilder()->add('linkID = ?', [$this->formObject->linkID]);
            $specialList->readObjects();

            $specials = $specialList->getObjects();
            foreach ($specials as $special) {
                if ($special->isCurrentlyActive()) {
                    $urlActiveSpecials[] = $special;
                    if ($firstActiveSpecialID === null) {
                        $firstActiveSpecialID = $special->specialID;
                    }
                }
            }

            $urlSpecials = $specials;
            $hasActiveSpecials = !empty($urlActiveSpecials);
        }

        // Get data for tabs
        $urlFeaturedLinks = $this->getFeaturedLinks();
        $urlCustomButtons = $this->getCustomButtons();

        // Update tab labels with badges
        $featuredLinksTab = $this->form->getNodeById('featuredLinksTab');
        if ($featuredLinksTab) {
            $featuredLinksCount = count($urlFeaturedLinks);
            $featuredLinksLabel = WCF::getLanguage()->get('wcf.shrinkr.featuredLink.section');
            if ($featuredLinksCount > 0) {
                $featuredLinksLabel .= ' <span class="badge">' . $featuredLinksCount . '</span>';
            }
            $featuredLinksTab->label($featuredLinksLabel);
        }

        $customButtonsTab = $this->form->getNodeById('customButtonsTab');
        if ($customButtonsTab) {
            $customButtonsCount = count($urlCustomButtons);
            $customButtonsLabel = WCF::getLanguage()->get('wcf.shrinkr.customButton.section');
            if ($customButtonsCount > 0) {
                $customButtonsLabel .= ' <span class="badge">' . $customButtonsCount . '</span>';
            }
            $customButtonsTab->label($customButtonsLabel);
        }

        WCF::getTPL()->assign([
            'linkID' => $this->formObject->linkID ?? 0,
            'url' => $this->formObject->url ?? '',
            'urlSpecials' => $urlSpecials,
            'urlActiveSpecials' => $urlActiveSpecials,
            'hasActiveSpecials' => $hasActiveSpecials,
            'firstActiveSpecialID' => $firstActiveSpecialID,
            'urlFeaturedLinks' => $urlFeaturedLinks,
            'urlCustomButtons' => $urlCustomButtons,
        ]);
    }
}
