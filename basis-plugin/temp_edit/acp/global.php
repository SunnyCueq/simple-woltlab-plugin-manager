<?php

/**
 * Global configuration file for Shr1nkr plugin (ACP).
 * 
 * Includes application configuration and WoltLab ACP global.php. Sets up
 * paths and configuration for ACP (admin) requests.
 *
 * @author      Sunny C
 * @copyright   2026 Sunny C
 * @license     License for Commercial Plugins
 * @link        https://sunnyc.de
 * @package     de.sunnyc.wsc.shrinkr
 */

// define paths
\define('RELATIVE_SHRINKR_DIR', '../');
/*
 * include config
 * @noinspection PhpIncludeInspection
 */
require_once \dirname(__FILE__, 2) . '/app.config.inc.php';
/*
 * include wcf
 */
require_once RELATIVE_WCF_DIR . 'acp/global.php';
