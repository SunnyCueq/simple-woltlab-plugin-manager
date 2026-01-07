<?php

namespace shrinkr\acp\form;

use CuyZ\Valinor\Mapper\MappingError;
use shrinkr\data\featuredlink\FeaturedLink;
use wcf\http\Helper;
use wcf\system\exception\IllegalLinkException;

/**
 * Form for editing an existing featured link.
 *
 * @author      Sunny C. <https://benjaro.info>
 * @copyright   2026 Sunny C
 * @license     License for Commercial Plugins <https://benjaro.info>
 *
 * @package    de.sunnyc.wsc.shrinkr
 * @subpackage acp.form
 */
class FeaturedLinkEditForm extends FeaturedLinkAddForm
{
    /**
     * @inheritDoc
     */
    public $formAction = 'edit';

    /**
     * @inheritDoc
     */
    #[\Override]
    public function readParameters()
    {
        // Load FeaturedLink first (before parent, to get linkID)
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

            // Set linkID from loaded object (needed for parent::readParameters)
            $this->linkID = $this->formObject->linkID;
        } catch (MappingError) {
            throw new IllegalLinkException();
        }

        // Now load URL hash (parent expects linkID to be set)
        $sql = "SELECT hash FROM shrinkr1_link WHERE linkID = ?";
        $statement = \wcf\system\WCF::getDB()->prepareStatement($sql);
        $statement->execute([$this->linkID]);
        $this->urlHash = $statement->fetchSingleColumn();

        if (empty($this->urlHash)) {
            throw new IllegalLinkException();
        }

        // Call parent WITHOUT readParameters (to avoid linkID check from query)
        \wcf\form\AbstractFormBuilderForm::readParameters();
    }
}
