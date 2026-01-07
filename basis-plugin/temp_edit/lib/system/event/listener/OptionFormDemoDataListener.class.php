<?php

namespace urlshort\system\event\listener;

use urlshort\system\option\OptionFormDemoDataHandler;
use wcf\acp\form\OptionForm;
use wcf\system\event\listener\IParameterizedEventListener;

/**
 * Event listener for OptionForm::saved event.
 * Delegates to OptionFormDemoDataHandler for demo data installation.
 *
 * @author      Benjaro <https://benjaro.info>
 * @copyright   2025 Benjaro
 * @license     Commercial License
 * @package     dev.tkirch.wsc.urlshort
 * @subpackage  system.event.listener
 */
class OptionFormDemoDataListener implements IParameterizedEventListener
{
    /**
     * @inheritDoc
     */
    public function execute($eventObj, $className, $eventName, array &$parameters)
    {
        if ($eventName === 'saved' && $eventObj instanceof OptionForm) {
            $handler = new OptionFormDemoDataHandler();
            $handler->handleSaved($eventObj);
        }
    }
}
