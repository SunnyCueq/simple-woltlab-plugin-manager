<?php

/**
 * Frontend entry point for Shr1nkr plugin.
 * 
 * Entry point for frontend requests. Includes global.php and handles requests
 * for the shrinkr application (redirect pages, shortened URLs).
 *
 * @author      Sunny C
 * @copyright   2026 Sunny C
 * @license     License for Commercial Plugins
 * @link        https://sunnyc.de
 * @package     de.sunnyc.wsc.shrinkr
 */

require_once './global.php';
wcf\system\request\RequestHandler::getInstance()->handle('shrinkr');
