<?php

namespace urlshort\acp\form;

use wcf\acp\form\AbstractOptionListForm;
use wcf\data\option\OptionAction;
use wcf\system\WCF;

/**
 * ACP settings form for URL Shortener Featured Links options.
 *
 * @author      Sunny C. <https://benjaro.info>
 * @copyright   2022-2025 Benjaro
 * @license     License for Commercial Plugins <https://benjaro.info>
 *
 * @package    dev.tkirch.wsc.urlshort
 * @subpackage acp.form
 */
class UrlshortSettingsForm extends AbstractOptionListForm
{
    /**
     * @inheritDoc
     */
    public $activeMenuItem = 'urlshort.acp.menu.link.settings';

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
    public $categoryName = 'urlshort.featuredLinks';

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

