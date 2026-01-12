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
            // === DESIGN TAB ===
            $designTab = TabFormContainer::create('designTabContainer')
                ->label('wcf.shrinkr.design');

            // Featured Links Section (read-only display)
            $featuredLinksContainer = FormContainer::create('featuredLinksDisplay')
                ->label('wcf.shrinkr.featuredLinks')
                ->description('wcf.shrinkr.featuredLinks.manage.description');

            $designTab->appendChild($featuredLinksContainer);

            // Custom Buttons Section (read-only display)
            $customButtonsContainer = FormContainer::create('customButtonsDisplay')
                ->label('wcf.shrinkr.customButtons')
                ->description('wcf.shrinkr.customButtons.manage.description');

            $designTab->appendChild($customButtonsContainer);

            $tabMenu->appendChild($designTab);
        }
    }

    /**
     * @inheritDoc
     */
    public function assignVariables()
    {
        parent::assignVariables();

        // Load specials and featured links for this URL
        $urlSpecials = [];
        $urlActiveSpecials = [];
        $hasActiveSpecials = false;
        $firstActiveSpecialID = null;
        $urlFeaturedLinks = [];

        if ($this->formObject && $this->formObject->linkID) {
            // Load specials for this URL
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

            // Load featured links for this URL
            $featuredLinkList = new FeaturedLinkList();
            $featuredLinkList->getConditionBuilder()->add('linkID = ?', [$this->formObject->linkID]);
            $featuredLinkList->sqlOrderBy = 'sortOrder ASC, linkID ASC';
            $featuredLinkList->readObjects();

            $urlFeaturedLinks = $featuredLinkList->getObjects();
        }

        WCF::getTPL()->assign([
            'linkID' => $this->formObject->linkID ?? 0,
            'url' => $this->formObject->url ?? '',
            'urlSpecials' => $urlSpecials,
            'urlActiveSpecials' => $urlActiveSpecials,
            'hasActiveSpecials' => $hasActiveSpecials,
            'firstActiveSpecialID' => $firstActiveSpecialID,
            'urlFeaturedLinks' => $urlFeaturedLinks,
        ]);
    }
}
