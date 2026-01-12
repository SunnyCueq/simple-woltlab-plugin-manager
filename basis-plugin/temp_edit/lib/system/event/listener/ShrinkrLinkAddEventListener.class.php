<?php

namespace shrinkr\system\event\listener;

use shrinkr\acp\form\ShrinkrLinkAddForm;
use wcf\system\event\listener\AbstractEventListener;
use wcf\system\exception\UserInputException;
use wcf\system\WCF;
use wcf\util\StringUtil;

/**
 * Event listener for URL add form to handle URL title field.
 *
 * @author      Sunny C
 * @copyright   2026 Sunny C
 * @license     License for Commercial Plugins
 *
 * @package    de.sunnyc.wsc.shrinkr
 * @subpackage system.event.listener
 */
final class ShrinkrLinkAddEventListener extends AbstractEventListener
{
    /**
     * URL title
     */
    public string $linkTitle = '';

    /**
     * Maximum length for URL title field (VARCHAR)
     */
    private const MAX_URL_TITLE_LENGTH = 255;

    /**
     * @inheritDoc
     */
    protected function onAssignVariables(ShrinkrLinkAddForm $eventObj, array $parameters)
    {
        WCF::getTPL()->assign([
            'linkTitle' => $this->linkTitle
        ]);
    }

    /**
     * @inheritDoc
     */
    protected function onReadFormParameters(ShrinkrLinkAddForm $eventObj, array $parameters)
    {
        if (isset($_POST['linkTitle'])) {
            $this->linkTitle = StringUtil::trim($_POST['linkTitle']);
        }
    }

    /**
     * Validates the form input.
     */
    protected function onValidate(ShrinkrLinkAddForm $eventObj, array $parameters)
    {
        // Validate URL title length
        if (!empty($this->linkTitle) && mb_strlen($this->linkTitle) > self::MAX_URL_TITLE_LENGTH) {
            throw new UserInputException('linkTitle', 'tooLong');
        }
    }

    /**
     * @inheritDoc
     */
    protected function onReadData(ShrinkrLinkAddForm $eventObj, array $parameters)
    {
        if (isset($eventObj->urlObj->linkTitle)) {
            $this->linkTitle = $eventObj->urlObj->linkTitle;
        }
    }

    /**
     * @inheritDoc
     */
    protected function onSave(ShrinkrLinkAddForm $eventObj, array $parameters)
    {
        $eventObj->additionalFields = [
            'linkTitle' => $this->linkTitle
        ];
    }
}
