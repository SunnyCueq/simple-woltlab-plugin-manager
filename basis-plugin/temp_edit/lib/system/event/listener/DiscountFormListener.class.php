<?php

namespace shrinkr\system\event\listener;

use shrinkr\acp\form\DiscountAddForm;
use shrinkr\acp\form\DiscountEditForm;
use wcf\system\event\listener\AbstractEventListener;
use wcf\system\form\builder\data\processor\VoidFormDataProcessor;

/**
 * Event listener to remove obsolete "special" fields from discount forms.
 * 
 * Removes obsolete fields (special, specialIdentifier, countdownStart, countdownEnd)
 * from discount add/edit forms. These fields are no longer used and have been
 * replaced by the Special system.
 *
 * @author      Sunny C
 * @copyright   2026 Sunny C
 * @license     License for Commercial Plugins
 * @link        https://sunnyc.de
 * @package     de.sunnyc.wsc.shrinkr
 * @subpackage  system.event.listener
 */
class DiscountFormListener extends AbstractEventListener
{
    /**
     * Removes obsolete special and countdown fields from discount forms.
     * 
     * Hides and disables data processing for obsolete fields that are no longer
     * used in the discount system.
     *
     * @param   DiscountAddForm|DiscountEditForm  $form  The discount form instance
     * @return  void
     */
    public function onCreateForm($form): void
    {
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
