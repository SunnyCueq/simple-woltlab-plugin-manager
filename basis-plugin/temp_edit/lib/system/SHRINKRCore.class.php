<?php

/**
 * Shr1nkr Core Application Class
 *
 * @author      Sunny C
 * @copyright   2026 Sunny C
 * @license     License for Commercial Plugins
 * @link        https://sunnyc.de
 *
 * @package     de.sunnyc.wsc.shrinkr
 */

namespace shrinkr\system;

use wcf\data\page\Page;
use wcf\page\RedirectPage;
use wcf\system\application\AbstractApplication;
use wcf\system\request\route\StaticRequestRoute;
use wcf\system\request\RouteHandler;

/**
 * Core class for the Shr1nkr application.
 * Handles route registration and primary controller setup.
 */
class SHRINKRCore extends AbstractApplication
{
    /**
     * @inheritDoc
     */
    protected $primaryController = RedirectPage::class;

    /**
     * @inheritDoc
     */
    public function __run(): void
    {
        // Get redirect page application by redirect page identifier
        $redirectPage = Page::getPageByIdentifier('de.sunnyc.wsc.shrinkr.redirect');
        $redirectPageApplication = $redirectPage->getApplication();

        $controllerPart = 'r';
        if ($redirectPage->controllerCustomURL) {
            $controllerPart = $redirectPage->controllerCustomURL;
        }

        // Setup static route
        $route = new StaticRequestRoute();
        $route->setStaticController($redirectPageApplication->getAbbreviation(), 'Redirect');
        $route->setBuildSchema('/' . $controllerPart . '/{hash}/');
        $route->setPattern('~^/?' . $controllerPart . '/(?P<hash>' . SHRINKR_PATTERN . ')?~x');
        $route->setRequiredComponents(['hash' => '~^' . SHRINKR_PATTERN . '$~']);
        RouteHandler::getInstance()->addRoute($route);
    }
}
