<?php

/**
 * Shr1nkr Core Application Class
 * 
 * Main application class for the Shr1nkr plugin. Extends WoltLab's AbstractApplication
 * to register custom routes for shortened URLs. Sets up the static route pattern
 * (e.g., /r/{hash}/) based on the redirect page configuration.
 *
 * @author      Sunny C
 * @copyright   2026 Sunny C
 * @license     License for Commercial Plugins
 * @link        https://sunnyc.de
 * @package     de.sunnyc.wsc.shrinkr
 * @subpackage  system
 */

namespace shrinkr\system;

use wcf\data\page\Page;
use wcf\page\RedirectPage;
use wcf\system\application\AbstractApplication;
use wcf\system\request\route\StaticRequestRoute;
use wcf\system\request\RouteHandler;

/**
 * Core class for the Shr1nkr application.
 * 
 * Handles route registration and primary controller setup. Registers a static route
 * for shortened URLs that matches the pattern defined in the redirect page configuration.
 */
class SHRINKRCore extends AbstractApplication
{
    /**
     * Primary controller class for this application.
     *
     * @var    string
     */
    protected $primaryController = RedirectPage::class;

    /**
     * Initializes the application and registers routes.
     * 
     * Retrieves the redirect page configuration and sets up a static route
     * that matches shortened URL patterns (e.g., /r/{hash}/). The controller
     * part can be customized via the page's controllerCustomURL setting.
     *
     * @return  void
     */
    public function __run(): void
    {
        $redirectPage = Page::getPageByIdentifier('de.sunnyc.wsc.shrinkr.redirect');
        $redirectPageApplication = $redirectPage->getApplication();

        $controllerPart = 'r';
        if ($redirectPage->controllerCustomURL) {
            $controllerPart = $redirectPage->controllerCustomURL;
        }

        $route = new StaticRequestRoute();
        $route->setStaticController($redirectPageApplication->getAbbreviation(), 'Redirect');
        $route->setBuildSchema('/' . $controllerPart . '/{hash}/');
        $route->setPattern('~^/?' . $controllerPart . '/(?P<hash>' . SHRINKR_PATTERN . ')?~x');
        $route->setRequiredComponents(['hash' => '~^' . SHRINKR_PATTERN . '$~']);
        RouteHandler::getInstance()->addRoute($route);
    }
}
