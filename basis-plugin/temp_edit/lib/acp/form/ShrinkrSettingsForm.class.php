<?php

namespace shrinkr\acp\form;

use wcf\acp\form\AbstractOptionListForm;
use wcf\data\option\OptionAction;
use wcf\system\WCF;

/**
 * ACP settings form for URL Shortener Featured Links options.
 *
 * @author      Sunny C
 * @copyright   2026 Sunny C
 * @license     License for Commercial Plugins
 *
 * @package    de.sunnyc.wsc.shrinkr
 * @subpackage acp.form
 */
class ShrinkrSettingsForm extends AbstractOptionListForm
{
    /**
     * @inheritDoc
     */
    public $activeMenuItem = 'shrinkr.acp.menu.link.settings';

    /**
     * @inheritDoc
     */
    public $neededPermissions = ['admin.configuration.canManageOption'];

    /**
     * @inheritDoc
     */
    protected $languageItemPattern = 'wcf.acp.option.option\d+';

    /**
     * @inheritDoc
     */
    public $categoryName = 'shrinkr.featuredLinks';

    /**
     * @inheritDoc
     */
    public function save()
    {
        parent::save();

        // save options
        $saveOptions = $this->optionHandler->save('wcf.acp.option', 'wcf.acp.option.option');
        $this->objectAction = new OptionAction([], 'updateAll', ['data' => $saveOptions]);
        $this->objectAction->executeAction();
        $this->saved();

        // show success message
        WCF::getTPL()->assign('success', true);
    }
}

