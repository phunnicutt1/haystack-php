<?php

namespace Cxalloy\Haystack;

use RuntimeException;

/**
 * ParseException is thrown when there is an exception read
 * from HReader.
 */
class ParseException extends RuntimeException
{
    /**
     * Constructor with message and null cause
     */
    public function __construct(string $message, ?\Throwable $cause = null)
    {
        parent::__construct($message, 0, $cause);
    }
}
