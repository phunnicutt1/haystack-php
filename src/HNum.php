<?php

namespace Cxalloy\Haystack;

use InvalidArgumentException;
use NumberFormatter;

/**
 * HNum wraps a 64-bit floating point number and optional unit name.
 *
 * @see <a href='http://project-haystack.org/doc/TagModel#tagKinds'>Project Haystack</a>
 */
class HNum extends HVal
{
    /** Singleton value for zero */
    public static HNum $ZERO;

    /** Singleton value for positive infinity "Inf" */
    public static HNum $POS_INF;

    /** Singleton value for negative infinity "-Inf" */
    public static HNum $NEG_INF;

    /** Singleton value for not-a-number "NaN" */
    public static HNum $NaN;

    /** Double scalar value */
    public float | int $val;

    /** Unit name or null */
    public ?string $unit;

    /** Private constructor */
    private function __construct(float | int $val, ?string $unit)
    {
        if (!self::isUnitName($unit)) {
            throw new InvalidArgumentException("Invalid unit name: " . $unit);
        }
        $this->val = $val;
        $this->unit = $unit;
    }

    /** Construct with int and null unit (may have loss of precision) */
    public static function makeFromInt(int $val): HNum
    {
        return self::make($val, null);
    }

    /** Construct with int and null/non-null unit (may have loss of precision) */
    public static function make(int $val, ?string $unit = NULL): HNum
    {
        if ($val === 0 && $unit === null) {
            return self::$ZERO;
        }
        return new HNum((float)$val, $unit);
    }

    /** Construct with long and null unit (may have loss of precision) */
    public static function makeWithInt(int $val): HNum
    {
        return self::make($val, null);
    }

    /** Construct with long and null/non-null unit (may have loss of precision) */
    public static function makeWithUnit(int|float $val, ?string $unit): HNum
    {
        if ($val === 0 && $unit === null) {
            return self::$ZERO;
        }
        return new HNum((float)$val, $unit);
    }

    /** Construct with double and null unit */
    public static function makeFromDouble(float $val): HNum
    {
        return self::make($val, null);
    }

    /** Construct with double and null/non-null unit */
    public static function makeFromDoubleWithUnit(float $val, ?string $unit): HNum
    {
        if ($val === 0.0 && $unit === null) {
            return self::$ZERO;
        }
        return new HNum($val, $unit);
    }

    /** Hash code is based on val, unit */
    public function hashCode(): int
    {
        $bits = unpack('l', pack('d', $this->val))[1];
        $hash = $bits ^ ($bits >> 32);
        if ($this->unit !== null) {
            $hash ^= crc32($this->unit);
        }
        return $hash;
    }

    /** Equals is based on val, unit (NaN == NaN) */
    public function equals(HVal $that): bool
    {
        if (!($that instanceof HNum)) {
            return false;
        }
        $x = $that;
        if (is_nan($this->val)) {
            return is_nan($x->val);
        }
        if ($this->val !== $x->val) {
            return false;
        }
        if ($this->unit === null) {
            return $x->unit === null;
        }
        if ($x->unit === null) {
            return false;
        }
        return $this->unit === $x->unit;
    }

    /** Return sort order as negative, 0, or positive */
    public function compareTo(HVal $that): int
    {
        $thatVal = $that->val;
        if ($this->val < $thatVal) {
            return -1;
        }
        if ($this->val === $thatVal) {
            return 0;
        }
        return 1;
    }

    /** Encode as {@code "n:<float> [unit]"} */
    public function toJson(): string
    {
        $s = 'n:';
        $this->encode($s, true);
        return $s;
    }

    /** Encode as floating value followed by optional unit string */
    public function toZinc(): string
    {
        $s = '';
        $this->encode($s, false);
        return $s;
    }

    private function encode(string &$s, bool $spaceBeforeUnit): void
    {
        if ($this->val === INF) {
            $s .= 'INF';
        } elseif ($this->val === -INF) {
            $s .= '-INF';
        } elseif (is_nan($this->val)) {
            $s .= 'NaN';
        } else {
            // don't encode huge set of decimals if over 1.0
            $abs = abs($this->val);
            if ($abs > 1.0) {
                $formatter = new NumberFormatter('en', NumberFormatter::PATTERN_DECIMAL, '#0.####');
                $s .= $formatter->format($this->val);
            } else {
                $s .= $this->val;
            }

            if ($this->unit !== null) {
                if ($spaceBeforeUnit) {
                    $s .= ' ';
                }
                $s .= $this->unit;
            }
        }
    }

    /**
     * Get this number as a duration of milliseconds.
     * Raise InvalidArgumentException if the unit is not a duration unit.
     */
    public function millis(): int
    {
        $u = $this->unit ?? 'null';
        return match ($u) {
            'ms', 'millisecond' => (int)$this->val,
            's', 'sec', 'second' => (int)($this->val * 1000.0),
            'min', 'minute' => (int)($this->val * 1000.0 * 60.0),
            'h', 'hr', 'hour' => (int)($this->val * 1000.0 * 60.0 * 60.0),
            'day' => (int)($this->val * 1000.0 * 60.0 * 60.0 * 24.0),
            default => throw new InvalidArgumentException("Invalid duration unit: " . $u),
        };
    }

    /**
     * Return true if the given string is null or contains only valid unit
     * chars. If the unit name contains invalid chars return false. This
     * method does *not* check that the unit name is part of the standard
     * unit database.
     */
    public static function isUnitName(?string $unit): bool
    {
        if ($unit === null) {
            return true;
        }
        if (strlen($unit) === 0) {
            return false;
        }
        for ($i = 0; $i < strlen($unit); ++$i) {
            $c = ord($unit[$i]);
            if ($c < 128 && !self::$unitChars[$c]) {
                return false;
            }
        }
        return true;
    }

    private static array $unitChars = [];

    static function init()
    {
        for ($i = ord('a'); $i <= ord('z'); ++$i) {
            self::$unitChars[$i] = true;
        }
        for ($i = ord('A'); $i <= ord('Z'); ++$i) {
            self::$unitChars[$i] = true;
        }
        self::$unitChars[ord('_')] = true;
        self::$unitChars[ord('$')] = true;
        self::$unitChars[ord('%')] = true;
        self::$unitChars[ord('/')] = true;

        self::$ZERO = new HNum(0.0, null);
        self::$POS_INF = new HNum(INF, null);
        self::$NEG_INF = new HNum(-INF, null);
        self::$NaN = new HNum(NAN, null);
    }
}

HNum::init();
