<?php

namespace shrinkr\system\exception;

/**
 * Exception thrown when ShrinkrLink operations fail.
 * 
 * Used for general errors related to shortened link operations, such as invalid
 * URLs, missing links, or permission issues. Extends PHP's Exception class.
 *
 * @author      Sunny C
 * @copyright   2026 Sunny C
 * @license     License for Commercial Plugins
 * @link        https://sunnyc.de
 * @package     de.sunnyc.wsc.shrinkr
 * @subpackage  system.exception
 */
class ShrinkrLinkException extends \Exception
{
    /**
     * Creates a new ShrinkrLinkException.
     *
     * @param   string      $message     Error message describing what went wrong
     * @param   int         $code        Error code (default: 0)
     * @param   string      $description Additional error description (currently unused)
     * @param   \Exception  $previous     Previous exception for exception chaining
     * @return  void
     */
    public function __construct($message = '', $code = 0, $description = '', ?\Exception $previous = null)
    {
        parent::__construct((string)$message, (int)$code, $previous);
    }
}
