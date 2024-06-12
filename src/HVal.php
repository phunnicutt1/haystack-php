<?php

declare(strict_types=1);
namespace Cxalloy\Haystack;

/**
 * HVal is the base class for representing haystack tag scalar values as an immutable class.
 *
 * @see <a href='http://project-haystack.org/doc/TagModel#tagKinds'>Project Haystack</a>
 */
abstract class HVal implements \JsonSerializable, \Stringable
{
    // Package private constructor
    protected function __construct() {}

    /**
     * String format is for human consumption only.
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->toZinc();
    }

    /**
     * Encode value to zinc format.
     *
     * @return string
     */
    abstract public function toZinc(): string;

    /**
     * Encode to JSON string value.
     *
     * @return string
     */
    abstract public function toJson(): string;

    /**
     * Hash code is value based.
     *
     * @return int
     */
    abstract public function hashCode(): int;

    /**
     * Equality is value based.
     *
     * @param mixed $that
     * @return bool
     */
    abstract public function equals(HVal $that): bool;

    /**
     * Return sort order as negative, 0, or positive.
     *
     * @param mixed $that
     * @return int
     */
    public function compareTo(HVal $that): int
    {
        return strcmp($this->__toString(), (string)$that);
    }

    /**
     * Specify data which should be serialized to JSON.
     *
     * @return mixed
     */
    public function jsonSerialize(): mixed
    {
        return $this->toJson();
    }
}
