<?php

declare(strict_types=1);

namespace Cxalloy\Haystack;

/**
 * HBool defines singletons for true/false tag values.
 *
 * @see <a href='http://project-haystack.org/doc/TagModel#tagKinds'>Project Haystack</a>
 */
class HBool extends HVal
{
    /** Singleton value for true */
    public static HBool $TRUE;

    /** Singleton value for false */
    public static HBool $FALSE;

    /** Boolean value */
    public  bool $val;

    /** Construct from boolean value */
    public static function make(bool $val): self
    {
        return $val ? self::$TRUE : self::$FALSE;
    }

    /** Private constructor */
    public function __construct(bool $val)
    {
        $this->val = $val;
    }

    /** Hash code is same as java.lang.Boolean */
    public function hashCode(): int
    {
        return $this->val ? 1231 : 1237;
    }

    /** Equals is based on reference */
    public function equals(object $that): bool
    {
        return $this === $that;
    }

    /** Encode as "true" or "false" */
    public function __toString(): string
    {
        return $this->val ? 'true' : 'false';
    }

    /** Raise UnsupportedOperationException */
    public function toJson(): string
    {
        throw new \BadMethodCallException();
    }

    /** Encode as "T" or "F" */
    public function toZinc(): string
    {
        return $this->val ? 'T' : 'F';
    }
}

// Initialize the static properties
HBool::$TRUE = new HBool(true);
HBool::$FALSE = new HBool(false);
