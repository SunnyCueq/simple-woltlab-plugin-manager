<?php

namespace shrinkr\acp\form;

use shrinkr\data\featuredlink\FeaturedLinkAction;
use shrinkr\data\featuredlink\FeaturedLinkList;
use wcf\form\AbstractFormBuilderForm;
use wcf\system\exception\IllegalLinkException;
use wcf\system\form\builder\container\FormContainer;
use wcf\system\form\builder\field\HiddenFormField;
use wcf\system\form\builder\field\IntegerFormField;
use wcf\system\form\builder\field\TextFormField;
use wcf\system\form\builder\field\UrlFormField;
use wcf\system\request\LinkHandler;
use wcf\system\WCF;
use wcf\util\HeaderUtil;

/**
 * Form for adding a new featured link.
 *
 * @author      Sunny C
 * @copyright   2026 Sunny C
 * @license     License for Commercial Plugins
 *
 * @package    de.sunnyc.wsc.shrinkr
 * @subpackage acp.form
 */
class FeaturedLinkAddForm extends AbstractFormBuilderForm
{
    /**
     * @inheritDoc
     */
    public $neededPermissions = ['admin.shrinkr.canManageFeaturedLinks'];

    /**
     * @inheritDoc
     */
    public $activeMenuItem = 'shrinkr.acp.menu.link.menu';

    /**
     * @inheritDoc
     */
    public $objectActionClass = FeaturedLinkAction::class;

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

        $dataContainer->appendChildren([
            // Hidden field for linkID (submitted with form)
            HiddenFormField::create('linkID')
                ->value($this->linkID),

            UrlFormField::create('url')
                ->label('wcf.shrinkr.featuredLink.url')
                ->description('wcf.shrinkr.featuredLink.url.description')
                ->required()
                ->autoFocus()
                ->maximumLength(255),

            TextFormField::create('title')
                ->label('wcf.global.title')
                ->description('wcf.shrinkr.featuredLink.title.description')
                ->required()
                ->maximumLength(255),

            IntegerFormField::create('sortOrder')
                ->label('wcf.shrinkr.featuredLink.sortOrder')
                ->description('wcf.shrinkr.featuredLink.sortOrder.description')
                ->value(1)
                ->minimum(1),
        ]);

        $this->form->appendChild($dataContainer);
    }

    /**
     * @inheritDoc
     */
    #[\Override]
    public function saved()
    {
        parent::saved();

        // Redirect back to add form with same linkID to allow adding another featured link
        $url = LinkHandler::getInstance()->getControllerLink(FeaturedLinkAddForm::class, [
            'application' => 'shrinkr',
        ]);
        $url .= '&linkID=' . $this->linkID;
        
        HeaderUtil::redirect($url);
        exit;
    }

    /**
     * @inheritDoc
     */
    #[\Override]
    public function assignVariables()
    {
        parent::assignVariables();

        // Check if there are existing featured links for this URL
        $featuredLinkList = new FeaturedLinkList();
        $featuredLinkList->getConditionBuilder()->add('linkID = ?', [$this->linkID]);
        $featuredLinkList->readObjects();
        $hasExistingFeaturedLinks = $featuredLinkList->count() > 0;

        WCF::getTPL()->assign([
            'linkID' => $this->linkID,
            'urlHash' => $this->urlHash,
            'hasExistingFeaturedLinks' => $hasExistingFeaturedLinks,
            'acpPageSubMenuCategoryList' => 'shrinkr.acp.menu.link.menu',
        ]);
    }
}
