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
 * Manages the linkTitle field in the ShrinkrLinkAddForm, including validation,
 * reading from request parameters, and saving to the database.
 *
 * @author      Sunny C
 * @copyright   2026 Sunny C
 * @license     License for Commercial Plugins
 * @link        https://sunnyc.de
 * @package     de.sunnyc.wsc.shrinkr
 * @subpackage  system.event.listener
 */
final class ShrinkrLinkAddEventListener extends AbstractEventListener
{
    /**
     * URL title value from form input.
     *
     * @var    string
     */
    public string $linkTitle = '';

    /**
     * Maximum length for URL title field (matches database VARCHAR length).
     *
     * @var    int
     */
    private const MAX_URL_TITLE_LENGTH = 255;

    /**
     * Assigns variables to the template.
     * 
     * Makes the linkTitle value available to the form template.
     *
     * @param   ShrinkrLinkAddForm  $eventObj   The form instance
     * @param   array               $parameters Event parameters
     * @return  void
     */
    protected function onAssignVariables(ShrinkrLinkAddForm $eventObj, array $parameters)
    {
        WCF::getTPL()->assign([
            'linkTitle' => $this->linkTitle
        ]);
    }

    /**
     * Reads form parameters from request.
     * 
     * Extracts linkTitle from POST data and trims whitespace.
     *
     * @param   ShrinkrLinkAddForm  $eventObj   The form instance
     * @param   array               $parameters Event parameters
     * @return  void
     */
    protected function onReadFormParameters(ShrinkrLinkAddForm $eventObj, array $parameters)
    {
        if (isset($_POST['linkTitle'])) {
            $this->linkTitle = StringUtil::trim($_POST['linkTitle']);
        }
    }

    /**
     * Validates the form input.
     * 
     * Ensures linkTitle doesn't exceed the maximum length.
     *
     * @param   ShrinkrLinkAddForm  $eventObj   The form instance
     * @param   array               $parameters Event parameters
     * @return  void
     * @throws  UserInputException              If linkTitle is too long
     */
    protected function onValidate(ShrinkrLinkAddForm $eventObj, array $parameters)
    {
        if (!empty($this->linkTitle) && mb_strlen($this->linkTitle) > self::MAX_URL_TITLE_LENGTH) {
            throw new UserInputException('linkTitle', 'tooLong');
        }
    }

    /**
     * Reads existing data for edit forms.
     * 
     * Loads the linkTitle from the existing link object when editing.
     *
     * @param   ShrinkrLinkAddForm  $eventObj   The form instance
     * @param   array               $parameters Event parameters
     * @return  void
     */
    protected function onReadData(ShrinkrLinkAddForm $eventObj, array $parameters)
    {
        if (isset($eventObj->urlObj->linkTitle)) {
            $this->linkTitle = $eventObj->urlObj->linkTitle;
        }
    }

    /**
     * Saves the linkTitle to the database.
     * 
     * Adds linkTitle to additionalFields for database insertion/update.
     *
     * @param   ShrinkrLinkAddForm  $eventObj   The form instance
     * @param   array               $parameters Event parameters
     * @return  void
     */
    protected function onSave(ShrinkrLinkAddForm $eventObj, array $parameters)
    {
        $eventObj->additionalFields = [
            'linkTitle' => $this->linkTitle
        ];
    }
}
