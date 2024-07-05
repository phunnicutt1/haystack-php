<?php
declare(strict_types=1);
namespace Cxalloy\Haystack;


abstract class HVal
{
    /**
     * String format is for human consumption only
     * @return string
     */
    public function __toString(): string
    {
        return $this->toZinc();
    }

    /**
     * Return sort order as negative, 0, or positive
     * @param HVal $that - HVal to compare to
     * @return int
     */
    public function compareTo(HVal $that): int
    {
        return strcmp($this->toString(), $that->toString());
    }

    /**
     * Encode value to zinc format
     * @abstract
     * @return string
     */
    abstract public function toZinc(): string;

    /**
     * Encode value to JSON format
     * @abstract
     * @return string
     */
    abstract public function toJSON(): string;

    /**
     * Equality is value based
     * @abstract
     * @return bool
     */
    abstract public function equals(HVal $that): bool;

    /**
     * Check if a string starts with a given prefix
     * @param string $s
     * @param string $prefix
     * @return bool
     */
    public static function startsWith(string $s, string $prefix): bool
    {
        return substr($s, 0, strlen($prefix)) === $prefix;
    }

    /**
     * Check if a string ends with a given suffix
     * @param string $s
     * @param string $suffix
     * @return bool
     */
    public static function endsWith(string $s, string $suffix): bool
    {
        return substr($s, -strlen($suffix)) === $suffix;
    }

    /**
     * Check the type of a variable
     * @param mixed $check
     * @param string $prim
     * @param string $obj
     * @return bool
     */
    public static function typeis($check, string $prim, string $obj): bool
    {
        return gettype($check) === $prim || $check instanceof $obj;
    }

    /**
     * Get the character code of a character
     * @param string $c
     * @return int
     */
    public static function cc(string $c): int
    {
        return ord($c);
    }
}
