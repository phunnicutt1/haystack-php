<?php

namespace Cxalloy\Haystack;

use RuntimeException;

/**
 * UnknownRecException is thrown when attempting to
 * resolve an entity record which is not found.
 */
class UnknownRecException extends RuntimeException
{
    /**
     * Constructor with message
     */
    public function __construct(string $message)
    {
        parent::__construct($message);
    }

    /**
     * Constructor with Ref
     */
    public static function fromRef(HRef $ref): self
    {
        return new self((string) $ref);
    }
}
