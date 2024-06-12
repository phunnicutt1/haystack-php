<?php

namespace Cxalloy\Haystack;

use InvalidArgumentException;

/**
 * HXStr is an extended string which is a type name and value
 * encoded as a string. It is used as a generic value when an
 * HXStr is decoded without any predefined type.
 */
class HXStr extends HVal
{
    /** Type name */
    public string $type;

    /** String value */
    public string $val;

    /**
     * Decode a string into an HVal.
     */
    public static function decode(string $type, string $val): HVal
    {
        if ($type === "Bin") {
            return HBin::make($val);
        }
        return new HXStr($type, $val);
    }

    /**
     * Encode an object into an HXStr.
     */
    public static function encode(object $val): HXStr
    {
        return new HXStr((new \ReflectionClass($val))->getShortName(), (string)$val);
    }

    /**
     * Private constructor
     */
    private function __construct(string $type, string $val)
    {
        if (!self::isValidType($type)) {
            throw new InvalidArgumentException("Invalid type name: " . $type);
        }
        $this->type = $type;
        $this->val = $val;
    }

    /**
     * Check if the type name is valid.
     */
    private static function isValidType(string $t): bool
    {
        if (empty($t) || !ctype_upper($t[0])) {
            return false;
        }
        for ($i = 0; $i < strlen($t); ++$i) {
            if (ctype_alpha($t[$i]) || ctype_digit($t[$i]) || $t[$i] === '_') {
                continue;
            }
            return false;
        }
        return true;
    }

    /**
     * Encode as Zinc format.
     */
    public function toZinc(): string
    {
        return sprintf('%s("%s")', $this->type, $this->val);
    }

    /**
     * Encode as JSON format.
     */
    public function toJson(): string
    {
        throw new \BadMethodCallException("Unsupported operation");
    }

    /**
     * Check equality.
     */
    public function equals(object $o): bool
    {
        if ($this === $o) {
            return true;
        }
        if ($o === null || get_class($this) !== get_class($o)) {
            return false;
        }

        $hxStr = $o;
        return $this->type === $hxStr->type && $this->val === $hxStr->val;
    }

    /**
     * Generate hash code.
     */
    public function hashCode(): int
    {
        return crc32($this->type) ^ crc32($this->val);
    }
}
