<?php

namespace urlshort\system\event\listener;

use urlshort\acp\form\UrlAddForm;
use wcf\system\event\listener\AbstractEventListener;
use wcf\system\exception\UserInputException;
use wcf\system\WCF;
use wcf\util\StringUtil;

/**
 * Event listener for URL add form to handle URL title field.
 *
 * @author      Sunny C. <https://benjaro.info>
 * @copyright   2022-2025 Benjaro
 * @license     License for Commercial Plugins <https://benjaro.info>
 *
 * @package    info.benjaro.urlshort.affiliate
 * @subpackage system.event.listener
 */
final class UrlAddEventListener extends AbstractEventListener
{
    /**
     * URL title
     */
    public string $urlTitle = '';

    /**
     * Maximum length for URL title field (VARCHAR)
     */
    private const MAX_URL_TITLE_LENGTH = 255;

    /**
     * @inheritDoc
     */
    protected function onAssignVariables(UrlAddForm $eventObj, array $parameters)
    {
        WCF::getTPL()->assign([
            'urlTitle' => $this->urlTitle
        ]);
    }

    /**
     * @inheritDoc
     */
    protected function onReadFormParameters(UrlAddForm $eventObj, array $parameters)
    {
        if (isset($_POST['urlTitle'])) {
            $this->urlTitle = StringUtil::trim($_POST['urlTitle']);
        }
    }

    /**
     * Validates the form input.
     */
    protected function onValidate(UrlAddForm $eventObj, array $parameters)
    {
        // Validate URL title length
        if (!empty($this->urlTitle) && mb_strlen($this->urlTitle) > self::MAX_URL_TITLE_LENGTH) {
            throw new UserInputException('urlTitle', 'tooLong');
        }
    }

    /**
     * @inheritDoc
     */
    protected function onReadData(UrlAddForm $eventObj, array $parameters)
    {
        if (isset($eventObj->urlObj->urlTitle)) {
            $this->urlTitle = $eventObj->urlObj->urlTitle;
        }
    }

    /**
     * @inheritDoc
     */
    protected function onSave(UrlAddForm $eventObj, array $parameters)
    {
        $eventObj->additionalFields = [
            'urlTitle' => $this->urlTitle
        ];
    }
}
