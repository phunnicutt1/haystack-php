<?php

namespace App;

use Exception;

/**
 * Custom exception class for handling decoding errors in HaystackDecoder.
 */
class HaystackDecodingException extends Exception
{
    // Constructor is inherited from the Exception class

    /**
     * Custom string representation of the exception.
     */
    public function __toString()
    {
        return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
    }

    /**
     * Logs the exception details including the stack trace.
     */
    public function logException()
    {
        error_log($this->getMessage() . "\n" . $this->getTraceAsString());
    }
}