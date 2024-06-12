<?php

declare(strict_types=1);

namespace Cxalloy\Haystack;

/**
 * HRef wraps a string reference identifier and optional display name.
 *
 * @see <a href='http://project-haystack.org/doc/TagModel#tagKinds'>Project Haystack</a>
 */
class HRef extends HVal
{
    public string $val;
    public ?string $dis;

    /** Singleton for the null ref */
    public static HRef $nullRef;

    public static array $idChars = [];

    /** Static initializer */
    public static function init(): void
    {
        for ($i = ord('a'); $i <= ord('z'); ++$i) self::$idChars[$i] = true;
        for ($i = ord('A'); $i <= ord('Z'); ++$i) self::$idChars[$i] = true;
        for ($i = ord('0'); $i <= ord('9'); ++$i) self::$idChars[$i] = true;
        self::$idChars[ord('_')] = true;
        self::$idChars[ord(':')] = true;
        self::$idChars[ord('-')] = true;
        self::$idChars[ord('.')] = true;
        self::$idChars[ord('~')] = true;

        self::$nullRef = new HRef("null", null);
    }

    /** Construct for string identifier and optional display */
    public static function make(string $val, ?string $dis = null): HRef
    {
        if ( ! self::isId($val)) {
            throw new InvalidArgumentException("Invalid id val: \"$val\"");
        }
        return new HRef($val, $dis);
    }

    /** Private constructor */
    private function __construct(string $val, ?string $dis)
    {
        $this->val = $val;
        $this->dis = $dis;
    }

    /** String identifier for reference */
    public function getVal(): string
    {
        return $this->val;
    }

    /** Display name for reference or null */
    public function getDis(): ?string
    {
        return $this->dis;
    }

    /** Hash code is based on val field only */
    public function hashCode(): int
    {
        return hash('crc32', $this->val);
    }

    /** Equals is based on val field only */
    public function equals(object $that): bool
    {
        if (!$that instanceof HRef) {
            return false;
        }
        return $this->val === $that->val;
    }

    /** Return display string which is dis field if non-null, val field otherwise */
    public function dis(): string
    {
        return $this->dis ?? $this->val;
    }

    /** Return the val string */
    public function __toString(): string
    {
        return $this->val;
    }

    /** Encode as "@id" */
    public function toCode(): string
    {
        return "@" . $this->val;
    }

    /** Encode as {@code "r:<id> [dis]"} */
    public function toJson(): string
    {
        $s = "r:" . $this->val;
        if ($this->dis !== null) {
            $s .= ' ' . $this->dis;
        }
        return $s;
    }

    /** Encode as {@code "@<id> [dis]"} */
    public function toZinc(): string
    {
        $s = '@' . $this->val;
        if ($this->dis !== null) {
            $s .= ' ';
            $s .= HStr::toZinc($this->dis);
        }
        return $s;
    }

    /** Return if the given string is a valid id for a reference */
    public static function isId(string $id): bool
    {
        if (strlen($id) === 0) {
            return false;
        }
        for ($i = 0; $i < strlen($id); ++$i) {
            if (!self::isIdChar(ord($id[$i]))) {
                return false;
            }
        }
        return true;
    }

    /** Is the given character valid in the identifier part */
    public static function isIdChar(int $ch): bool
    {
        return $ch >= 0 && $ch < 127 && (self::$idChars[$ch] ?? false);
    }
}

// Initialize static properties
HRef::init();
