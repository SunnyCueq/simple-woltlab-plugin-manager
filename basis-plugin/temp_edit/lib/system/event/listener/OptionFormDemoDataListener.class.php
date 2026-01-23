<?php

namespace shrinkr\system\event\listener;

use shrinkr\system\option\OptionFormDemoDataHandler;
use wcf\acp\form\OptionForm;
use wcf\system\event\listener\IParameterizedEventListener;

/**
 * Event listener for OptionForm::saved event.
 * 
 * Delegates to OptionFormDemoDataHandler for demo data installation/deletion
 * when options are saved in the ACP. Implements IParameterizedEventListener
 * to handle the OptionForm saved event.
 *
 * @author      Sunny C
 * @copyright   2026 Sunny C
 * @license     License for Commercial Plugins
 * @link        https://sunnyc.de
 * @package     de.sunnyc.wsc.shrinkr
 * @subpackage  system.event.listener
 */
class OptionFormDemoDataListener implements IParameterizedEventListener
{
    /**
     * Executes the event listener.
     * 
     * Listens to OptionForm saved events and delegates to OptionFormDemoDataHandler
     * for demo data management.
     *
     * @param   object  $eventObj    The event object (OptionForm instance)
     * @param   string  $className   The class name of the event object
     * @param   string  $eventName   The event name ('saved')
     * @param   array   $parameters  Event parameters (passed by reference)
     * @return  void
     */
    public function execute($eventObj, $className, $eventName, array &$parameters)
    {
        if ($eventName === 'saved' && $eventObj instanceof OptionForm) {
            $handler = new OptionFormDemoDataHandler();
            $handler->handleSaved($eventObj);
        }
    }
}
