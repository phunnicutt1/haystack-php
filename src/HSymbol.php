<?php

namespace Cxalloy\Haystack;

use InvalidArgumentException;

/**
 * HSymbol is a name to a def in the meta-model namespace.
 */
class HSymbol extends HVal
{
    /** Internal representation */
    private string $str;
    private string $name;

    /**
     * Create a symbol from a string.
     * @param string $str The symbol string.
     * @return HSymbol the symbol.
     * @throws InvalidArgumentException if the string is invalid.
     */
    public static function make(string $str): HSymbol
    {
        if (empty($str)) {
            throw new InvalidArgumentException("Empty str");
        }
        if (!ctype_lower($str[0])) {
            throw new InvalidArgumentException("Invalid start char: " . $str);
        }

        $colon = -1;
        $dot = -1;
        $dash = -1;

        for ($i = 0; $i < strlen($str); ++$i) {
            $c = $str[$i];
            if ($c === ':') {
                if ($colon >= 0) {
                    throw new InvalidArgumentException("Too many colons: " . $str);
                }
                $colon = $i;
            } elseif ($c === '.') {
                if ($dot >= 0) {
                    throw new InvalidArgumentException("Too many dots: " . $str);
                }
                $dot = $i;
            } elseif ($c === '-') {
                $dash = $i;
            } elseif (!self::isTagChar($c)) {
                throw new InvalidArgumentException("Invalid char at pos: " . $i . ": " . $str);
            }
        }

        if ($dot > 0) {
            throw new InvalidArgumentException("Compose symbols deprecated: " . $str);
        }

        if ($colon > 0) {
            return new HSymbol($str, substr($str, $colon + 1));
        }

        return new HSymbol($str);
    }

    private static function isTagChar(string $c): bool
    {
        return ctype_alnum($c) || $c === '_';
    }

    private function __construct(string $str, ?string $name = null)
    {
        $this->str = $str;
        $this->name = $name ?? $str;
    }

    /**
     * @return string the simple name.
     */
    public function name(): string
    {
        return $this->name;
    }

    public function toZinc(): string
    {
        return '^' . $this->str;
    }

    public function toJson(): string
    {
        return "y:" . $this->str;
    }

    public function hashCode(): int
    {
        return crc32($this->str);
    }

    public function equals(object $that): bool
    {
        if (!($that instanceof HSymbol)) {
            return false;
        }
        return $this->str === $that->str;
    }

    public function __toString(): string
    {
        return $this->str;
    }
}
