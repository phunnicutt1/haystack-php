<?php

namespace Cxalloy\Haystack;

use InvalidArgumentException;

/**
 * HUri models a URI as a string.
 *
 * @see <a href='http://project-haystack.org/doc/TagModel#tagKinds'>Project Haystack</a>
 */
class HUri extends HVal
{
    /** Singleton value for empty URI */
    private static HUri $EMPTY;

    /** String value of URI */
    public string $val;

    /** Construct from string value */
    public static function make(string $val): HUri
    {
        if (strlen($val) === 0) {
            return self::$EMPTY;
        }
        return new HUri($val);
    }

    /** Private constructor */
    private function __construct(string $val)
    {
        $this->val = $val;
    }

    /** Hash code is based on string value */
    public function hashCode(): int
    {
        return crc32($this->val);
    }

    /** Equals is based on string value */
    public function equals(object $that): bool
    {
        if (!($that instanceof HUri)) {
            return false;
        }
        return $this->val === $that->val;
    }

    /** Return value string. */
    public function __toString(): string
    {
        return $this->val;
    }

    /** Encode as {@code "u:<val>"} */
    public function toJson(): string
    {
        return "u:" . $this->val;
    }

    /** Encode using "`" back ticks */
    public function toZinc(): string
    {
        $s = '`';
        for ($i = 0; $i < strlen($this->val); ++$i) {
            $c = $this->val[$i];
            if (ord($c) < ord(' ')) {
                throw new InvalidArgumentException("Invalid URI char '" . $this->val . "', char='" . $c . "'");
            }
            if ($c === '`') {
                $s .= '\\';
            }
            $s .= $c;
        }
        $s .= '`';
        return $s;
    }

    public static function init(): void
    {
        self::$EMPTY = new HUri("");
    }
}

HUri::init();
