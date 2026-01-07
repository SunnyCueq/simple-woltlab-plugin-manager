<?php

namespace urlshort\system;

use wcf\data\page\Page;
use wcf\page\RedirectPage;
use wcf\system\application\AbstractApplication;
use wcf\system\request\route\StaticRequestRoute;
use wcf\system\request\RouteHandler;

/**
 * @author      Julian Pfeil, Titus Kirch <https://julian-pfeil.de>
 * @link        https://darkwood.design/store/user-file-list/1298-julian-pfeil/
 * @copyright   2022 Julian Pfeil Websites & Co.
 * @license     License for Commercial Plugins <https://julian-pfeil.de/lizenz/>
 *
 * @package    dev.tkirch.wsc.urlshort
 * @subpackage system
 */
class URLSHORTCore extends AbstractApplication
{
    /**
     * @inheritDoc
     */
    protected $primaryController = RedirectPage::class;

    /**
     * @inheritDoc
     */
    public function __run()
    {
        //get redirect page application by redirect page identifier
        $redirectPage = Page::getPageByIdentifier('dev.tkirch.wsc.urlshort.redirect');
        $redirectPageApplication = $redirectPage->getApplication();

        $controllerPart = 'r';
        if ($redirectPage->controllerCustomURL) {
            $controllerPart = $redirectPage->controllerCustomURL;
        }

        //setup static route
        $route = new StaticRequestRoute();
        $route->setStaticController($redirectPageApplication->getAbbreviation(), 'Redirect');
        $route->setBuildSchema('/' . $controllerPart . '/{hash}/');
        $route->setPattern('~^/?' . $controllerPart . '/(?P<hash>' . URLSHORT_PATTERN . ')?~x');
        $route->setRequiredComponents(['hash' => '~^' . URLSHORT_PATTERN . '$~']);
        RouteHandler::getInstance()->addRoute($route);
    }
}
