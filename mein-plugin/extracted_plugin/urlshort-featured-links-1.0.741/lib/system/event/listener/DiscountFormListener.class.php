<?php

namespace urlshort\system\event\listener;

use urlshort\acp\form\DiscountAddForm;
use urlshort\acp\form\DiscountEditForm;
use wcf\system\event\listener\AbstractEventListener;
use wcf\system\form\builder\data\processor\VoidFormDataProcessor;

/**
 * Event listener to remove obsolete "special" fields from discount forms.
 *
 * @author      Sunny C. <https://benjaro.info>
 * @copyright   2022-2025 Benjaro
 * @license     License for Commercial Plugins <https://benjaro.info>
 *
 * @package    info.benjaro.urlshort.affiliate
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

