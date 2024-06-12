<?php

namespace Cxalloy\Haystack;

use RuntimeException;

/**
 * UnknownWatchException is thrown when attempting to perform
 * a checked lookup of a watch by its identifier.
 */
class UnknownWatchException extends RuntimeException
{
    /**
     * Constructor with message
     */
    public function __construct(string $message)
    {
        parent::__construct($message);
    }
}
