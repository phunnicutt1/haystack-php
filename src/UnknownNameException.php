<?php

namespace Cxalloy\Haystack;

use RuntimeException;

/**
 * UnknownNameException is thrown when attempting to perform
 * a checked lookup by name for a tag/col not present.
 */
class UnknownNameException extends RuntimeException
{
    /**
     * Constructor with message
     */
    public function __construct(string $message)
    {
        parent::__construct($message);
    }
}
