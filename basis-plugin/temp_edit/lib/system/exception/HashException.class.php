<?php

namespace shrinkr\system\exception;

/**
 * Class HashException
 *
 * @author      Sunny C, Sunny C <https://sunnyc.de>
 * @link        https://sunnyc.de
 * @copyright   2022 Sunny C Websites & Co.
 * @license     License for Commercial Plugins <https://sunnyc.de/lizenz/>
 *
 * @package    de.sunnyc.wsc.shrinkr
 * @subpackage system.exception
 */
class HashException extends \Exception
{
    /**
     * Creates a new HashException.
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
