<?php

namespace shrinkr\acp\form;

use CuyZ\Valinor\Mapper\MappingError;
use shrinkr\data\featuredlink\FeaturedLink;
use wcf\http\Helper;
use wcf\system\exception\IllegalLinkException;

/**
 * Form for editing an existing featured link.
 * 
 * ACP form for editing featured links. Extends FeaturedLinkAddForm and loads
 * the featured link object from the database. Handles linkID and URL hash
 * loading for form initialization.
 *
 * @author      Sunny C
 * @copyright   2026 Sunny C
 * @license     License for Commercial Plugins
 * @link        https://sunnyc.de
 * @package     de.sunnyc.wsc.shrinkr
 * @subpackage  acp.form
 */
class FeaturedLinkEditForm extends FeaturedLinkAddForm
{
    /**
     * Active menu item identifier for navigation highlighting.
     *
     * @var    string
     */
    public $activeMenuItem = 'shrinkr.acp.menu.link.featuredLink.edit';

    /**
     * Form action name (edit, update, delete).
     *
     * @var    string
     */
    public $formAction = 'edit';

    /**
     * Reads request parameters and loads the featured link to edit.
     * 
     * Loads the FeaturedLink object first to get linkID, then loads the URL hash.
     * Calls parent readParameters without the linkID check since we already
     * have the linkID from the featured link object.
     *
     * @return  void
     * @throws  IllegalLinkException  If featured link ID is invalid or link doesn't exist
     */
    #[\Override]
    public function readParameters()
    {
        try {
            $queryParameters = Helper::mapQueryParameters(
                $_GET,
                <<<'EOT'
                    array {
                        id: positive-int
                    }
                    EOT
            );
            $this->formObject = new FeaturedLink($queryParameters['id']);

            if (!$this->formObject->linkID) {
                throw new IllegalLinkException();
            }

            $this->linkID = $this->formObject->linkID;
        } catch (MappingError) {
            throw new IllegalLinkException();
        }

        $sql = "SELECT hash FROM shrinkr1_link WHERE linkID = ?";
        $statement = \wcf\system\WCF::getDB()->prepareStatement($sql);
        $statement->execute([$this->linkID]);
        $this->urlHash = $statement->fetchSingleColumn();

        if (empty($this->urlHash)) {
            throw new IllegalLinkException();
        }

        \wcf\form\AbstractFormBuilderForm::readParameters();
    }

    /**
     * @inheritDoc
     */
    #[\Override]
    public function assignVariables()
    {
        parent::assignVariables();

        \wcf\system\WCF::getTPL()->assign([
            'acpPageSubMenuCategoryList' => 'shrinkr.acp.menu.link.featuredLink.list'
        ]);
    }
}
