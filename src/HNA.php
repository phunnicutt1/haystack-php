<?php

namespace Cxalloy\Haystack;

/**
 * HNA is the singleton value used to indicate not available.
 *
 * @see <a href='http://project-haystack.org/doc/TagModel#tagKinds'>Project Haystack</a>
 */
class HNA extends HVal
{
    /** Singleton value */
    public static HNA $VAL;

    private function __construct()
    {
    }

    /** Hash code */
    public function hashCode(): int
    {
        return 0x6e61;
    }

    /** Equals is based on reference */
    public function equals(object $that): bool
    {
        return $this === $that;
    }

    /** Encode as "na" */
    public function __toString(): string
    {
        return "na";
    }

    /** Encode as "z:" */
    public function toJson(): string
    {
        return "z:";
    }

    /** Encode as "NA" */
    public function toZinc(): string
    {
        return "NA";
    }
}

HNA::$VAL = new HNA();
