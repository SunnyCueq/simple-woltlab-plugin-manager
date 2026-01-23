<?php

namespace shrinkr\acp\form;

use wcf\acp\form\AbstractOptionListForm;
use wcf\data\option\OptionAction;
use wcf\system\WCF;

/**
 * ACP settings form for Shr1nkr plugin options.
 * 
 * Provides a form for managing plugin-wide settings and options. Extends
 * AbstractOptionListForm to handle option management. Saves options when
 * the form is submitted.
 *
 * @author      Sunny C
 * @copyright   2026 Sunny C
 * @license     License for Commercial Plugins
 * @link        https://sunnyc.de
 * @package     de.sunnyc.wsc.shrinkr
 * @subpackage  acp.form
 */
class ShrinkrSettingsForm extends AbstractOptionListForm
{
    /**
     * Active menu item identifier for navigation highlighting.
     *
     * @var    string
     */
    public $activeMenuItem = 'shrinkr.acp.menu.link.settings';

    /**
     * Required permissions to access this form.
     *
     * @var    string[]
     */
    public $neededPermissions = ['admin.configuration.canManageOption'];

    /**
     * Language item pattern for option labels.
     *
     * @var    string
     */
    protected $languageItemPattern = 'wcf.acp.option.option\d+';

    /**
     * Option category name to display.
     *
     * @var    string
     */
    public $categoryName = 'shrinkr.featuredLinks';

    /**
     * Saves the form and updates options.
     * 
     * Processes form submission, saves options via OptionAction, and displays
     * a success message.
     *
     * @return  void
     */
    public function save()
    {
        parent::save();

        $saveOptions = $this->optionHandler->save('wcf.acp.option', 'wcf.acp.option.option');
        $this->objectAction = new OptionAction([], 'updateAll', ['data' => $saveOptions]);
        $this->objectAction->executeAction();
        $this->saved();

        WCF::getTPL()->assign('success', true);
    }
}

