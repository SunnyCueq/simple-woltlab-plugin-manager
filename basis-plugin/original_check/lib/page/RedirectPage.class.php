<?php

namespace urlshort\page;

use urlshort\data\url\Url;
use urlshort\data\url\UrlAction;
use wcf\page\AbstractPage;
use wcf\system\exception\IllegalLinkException;
use wcf\system\WCF;

/**
 * @author      Julian Pfeil, Titus Kirch <https://julian-pfeil.de>
 * @link        https://darkwood.design/store/user-file-list/1298-julian-pfeil/
 * @copyright   2022 Julian Pfeil Websites & Co.
 * @license     License for Commercial Plugins <https://julian-pfeil.de/lizenz/>
 *
 * @package    dev.tkirch.wsc.urlshort
 * @subpackage page
 */
class RedirectPage extends AbstractPage
{
    /**
     * @var null
     */
    public $url;

    /**
     * @inheritDoc
     */
    public function readParameters()
    {
        parent::readParameters();

        //check if the request contains a hash
        if (isset($_REQUEST['hash'])) {
            //get url
            $this->url = Url::getUrlByHash($_REQUEST['hash']);
        }
    }

    /**
     * @inheritDoc
     */
    public function assignVariables()
    {
        parent::assignVariables();

        //check if url exists
        if ($this->url->urlID) {
            //increase url counter
            UrlAction::increaseUrlCounter($this->url);

            //check for direct forwarding
            if (!URLSHORT_FORWARDING_MUST_CONFIRMED && URLSHORT_TIME_UNTIL_FORWARDING == 0) {
                //redirect
                \header('Location: ' . $this->url->url, true, 303);

                exit();
            }
            
            //assign to template
            WCF::getTPL()->assign([
                'url' => $this->url,
            ]);
        } else {
            throw new IllegalLinkException();
        }
    }
}
