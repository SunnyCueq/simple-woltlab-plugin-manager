<?php

namespace urlshort\system\event\listener;

use urlshort\acp\page\UrlListPage;
use wcf\system\event\listener\AbstractEventListener;
use wcf\system\WCF;

/**
 * Event listener to pass QR code option to UrlListPage template.
 *
 * @author      Benjaro <https://benjaro.info>
 * @copyright   2025 Benjaro
 * @license     Commercial License
 *
 * @package    info.benjaro.urlshort.affiliate
 * @subpackage system.event.listener
 */
class UrlListPageQrCodeListener extends AbstractEventListener
{
    /**
     * @inheritDoc
     */
    public function onAssignVariables(UrlListPage $page): void
    {
        // Pass QR code option to template
        WCF::getTPL()->assign([
            'enableQrCode' => URLSHORT_ENABLE_QR_CODE,
        ]);
    }
}

