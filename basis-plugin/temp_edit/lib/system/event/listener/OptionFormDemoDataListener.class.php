<?php

namespace shrinkr\system\event\listener;

use shrinkr\system\option\OptionFormDemoDataHandler;
use wcf\acp\form\OptionForm;
use wcf\system\event\listener\IParameterizedEventListener;

/**
 * Event listener for OptionForm::saved event.
 * Delegates to OptionFormDemoDataHandler for demo data installation.
 *
 * @author      Sunny C
 * @copyright   2026 Sunny C
 * @license     Commercial License
 * @package     de.sunnyc.wsc.shrinkr
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
