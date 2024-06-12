<?php

namespace Cxalloy\Haystack;

/**
 * HRemove is the singleton value used to indicate a tag remove.
 *
 * @see <a href='http://project-haystack.org/doc/TagModel#tagKinds'>Project Haystack</a>
 */
class HRemove extends HVal
{
    /** Singleton value */
    public static HRemove $VAL;

    private function __construct()
    {
    }

    /** Hash code */
    public function hashCode(): int
    {
        return 0x8ab3;
    }

    /** Equals is based on reference */
    public function equals(object $that): bool
    {
        return $this === $that;
    }

    /** Encode as "remove" */
    public function __toString(): string
    {
        return "remove";
    }

    /** Encode as "x:" */
    public function toJson(): string
    {
        return "x:";
    }

    /** Encode as "R" */
    public function toZinc(): string
    {
        return "R";
    }
}

HRemove::$VAL = new HRemove();
