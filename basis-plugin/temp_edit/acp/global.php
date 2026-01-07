<?php

/**
 * @author      Sunny C <https://sunnyc.de>
 * @link        https://sunnyc.de
 * @copyright   2022 Sunny C Websites & Co.
 * @license     License for Commercial Plugins <https://sunnyc.de/lizenz/>
 *
 * @package    de.sunnyc.wsc.shrinkr
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
