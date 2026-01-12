<?php

namespace shrinkr\system\event\listener;

use shrinkr\acp\form\DiscountAddForm;
use shrinkr\acp\form\DiscountEditForm;
use wcf\system\event\listener\AbstractEventListener;
use wcf\system\form\builder\data\processor\VoidFormDataProcessor;

/**
 * Event listener to remove obsolete "special" fields from discount forms.
 *
 * @author      Sunny C
 * @copyright   2026 Sunny C
 * @license     License for Commercial Plugins
 *
 * @package    de.sunnyc.wsc.shrinkr
 * @subpackage system.event.listener
 */
class DiscountFormListener extends AbstractEventListener
{
    /**
     * Removes obsolete special and countdown fields from discount forms.
     *
     * @param DiscountAddForm|DiscountEditForm $form
     */
    public function onCreateForm($form): void
    {
        // List of fields to remove
        $fieldsToRemove = ['special', 'specialIdentifier', 'countdownStart', 'countdownEnd'];
        
        foreach ($fieldsToRemove as $fieldId) {
            $field = $form->form->getNodeById($fieldId);
            if ($field !== null) {
                $field->available(false);
                $form->form->getDataHandler()->addProcessor(new VoidFormDataProcessor($fieldId));
            }
        }
    }
}
