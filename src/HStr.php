<?php

namespace Cxalloy\Haystack;

use InvalidArgumentException;

/**
 * HStr wraps a string as a tag value.
 *
 * @see <a href='http://project-haystack.org/doc/TagModel#tagKinds'>Project Haystack</a>
 */
class HStr extends HVal
{
    /** Singleton value for empty string "" */
    private static HStr $EMPTY;

    /** String value */
    public string $val;

    /** Construct from string value */
    public static function make(?string $val): ?HStr
    {
        if ($val === null) {
            return null;
        }
        if (strlen($val) === 0) {
            return self::$EMPTY;
        }
        return new HStr($val);
    }

    /** Private constructor */
    private function __construct(string $val)
    {
        $this->val = $val;
    }

    /** Hash code is same as string */
    public function hashCode(): int
    {
        return crc32($this->val);
    }

    /** Equals is based on string */
    public function equals(object $that): bool
    {
        if (!($that instanceof HStr)) {
            return false;
        }
        return $this->val === $that->val;
    }

    /** Return value string. */
    public function __toString(): string
    {
        return $this->val;
    }

    /** Encode as "s:" if string contains a colon */
    public function toJson(): string
    {
        return strpos($this->val, ':') === false ? $this->val : "s:" . $this->val;
    }

    /** Encode using double quotes and back slash escapes */
    public function toZinc(): string
    {
        $s = '';
        self::toZincString($s, $this->val);
        return $s;
    }

    /** Encode using double quotes and back slash escapes */
    public static function toCode(string $val): string
    {
        $s = '';
        self::toZincString($s, $val);
        return $s;
    }

    /** Encode using double quotes and back slash escapes */
    private static function toZincString(string &$s, string $val): void
    {
        $s .= '"';
        for ($i = 0; $i < strlen($val); ++$i) {
            $c = ord($val[$i]);
            if ($c < ord(' ') || $c == ord('"') || $c == ord('\\')) {
                $s .= '\\';
                switch ($c) {
                    case ord('\n'):
                        $s .= 'n';
                        break;
                    case ord('\r'):
                        $s .= 'r';
                        break;
                    case ord('\t'):
                        $s .= 't';
                        break;
                    case ord('"'):
                        $s .= '"';
                        break;
                    case ord('\\'):
                        $s .= '\\';
                        break;
                    default:
                        $s .= 'u00';
                        if ($c <= 0xf) {
                            $s .= '0';
                        }
                        $s .= dechex($c);
                }
            } else {
                $s .= chr($c);
            }
        }
        $s .= '"';
    }

    /**
     * Custom split routine so we don't have to depend on regex
     */
    public static function split(string $str, int $separator, bool $trim): array
    {
        $toks = [];
        $len = strlen($str);
        $x = 0;
        for ($i = 0; $i < $len; ++$i) {
            if (ord($str[$i]) !== $separator) {
                continue;
            }
            if ($x <= $i) {
                $toks[] = self::splitStr($str, $x, $i, $trim);
            }
            $x = $i + 1;
        }
        if ($x <= $len) {
            $toks[] = self::splitStr($str, $x, $len, $trim);
        }
        return $toks;
    }

    private static function splitStr(string $val, int $s, int $e, bool $trim): string
    {
        if ($trim) {
            while ($s < $e && ord($val[$s]) <= ord(' ')) {
                ++$s;
            }
            while ($e > $s && ord($val[$e - 1]) <= ord(' ')) {
                --$e;
            }
        }
        return substr($val, $s, $e - $s);
    }

    public static function init()
    {
        self::$EMPTY = new HStr("");
    }
}

HStr::init();
