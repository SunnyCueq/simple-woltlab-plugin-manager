<?php

namespace urlshort\system\exception;

/**
 * Class UrlException
 *
 * @author      Julian Pfeil, Titus Kirch <https://julian-pfeil.de>
 * @link        https://darkwood.design/store/user-file-list/1298-julian-pfeil/
 * @copyright   2022 Julian Pfeil Websites & Co.
 * @license     License for Commercial Plugins <https://julian-pfeil.de/lizenz/>
 *
 * @package    dev.tkirch.wsc.urlshort
 * @subpackage system.exception
 */
class UrlException extends \Exception
{
    /**
     * Creates a new UrlException.
     *
     * @param   string		$message	    error message
     * @param	integer		$code		    error code
     * @param	string		$description	description of the error
     * @param	\Exception	$previous	    repacked Exception
     */
    public function __construct($message = '', $code = 0, $description = '', ?\Exception $previous = null)
    {
        parent::__construct((string)$message, (int)$code, $previous);
    }
}
