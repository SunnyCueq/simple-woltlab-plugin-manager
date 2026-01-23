<?php

namespace shrinkr\system\exception;

/**
 * Exception thrown when hash validation or generation fails.
 * 
 * Used when a hash is invalid, already exists, or doesn't match the required pattern.
 * Extends PHP's Exception class to provide custom error handling for hash-related operations.
 *
 * @author      Sunny C
 * @copyright   2026 Sunny C
 * @license     License for Commercial Plugins
 * @link        https://sunnyc.de
 * @package     de.sunnyc.wsc.shrinkr
 * @subpackage  system.exception
 */
class HashException extends \Exception
{
    /**
     * Creates a new HashException.
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
