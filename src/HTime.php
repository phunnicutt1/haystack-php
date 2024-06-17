<?php

namespace Cxalloy\Haystack;

use InvalidArgumentException;
use DateTime;

/**
 * HTime models a time of day tag value.
 *
 * @see <a href='http://project-haystack.org/doc/TagModel#tagKinds'>Project Haystack</a>
 */
class HTime extends HVal
{
    /** Singleton for midnight 00:00 */
    public static HTime $MIDNIGHT;

    /** Hour of day as 0-23 */
    public int $hour;

    /** Minute of hour as 0-59 */
    public int $min;

    /** Second of minute as 0-59 */
    public int $sec;

    /** Fractional seconds in milliseconds 0-999 */
    public int $ms;

    /** Construct with all fields */
    public static function make(int $hour, int $min, int $sec, int $ms = 0): HTime
    {
        if ($hour < 0 || $hour > 23) {
            throw new InvalidArgumentException("Invalid hour");
        }
        if ($min < 0 || $min > 59) {
            throw new InvalidArgumentException("Invalid min");
        }
        if ($sec < 0 || $sec > 59) {
            throw new InvalidArgumentException("Invalid sec");
        }
        if ($ms < 0 || $ms > 999) {
            throw new InvalidArgumentException("Invalid ms");
        }
        return new HTime($hour, $min, $sec, $ms);
    }

    /** Convenience constructing with ms = 0 */
    public static function makeWithSec(int $hour, int $min, int $sec): HTime
    {
        return self::make($hour, $min, $sec, 0);
    }

    /** Convenience constructing with sec = 0 and ms = 0 */
    public static function makeWithMin(int $hour, int $min): HTime
    {
        return self::make($hour, $min, 0, 0);
    }

    /** Initialize from PHP DateTime instance */
    public static function makeFromDateTime(DateTime $c): HTime
    {
        return new HTime(
            (int)$c->format('G'),
            (int)$c->format('i'),
            (int)$c->format('s'),
            (int)$c->format('v')
        );
    }

    /** Parse from string format "hh:mm:ss.FF" or raise InvalidArgumentException */
    public static function makeFromString(string $s): HTime
    {
        if (!preg_match('/^(\d{2}):(\d{2}):(\d{2})(\.\d{1,3})?$/', $s, $matches)) {
            throw new InvalidArgumentException("Invalid time format: " . $s);
        }

        $hour = (int)$matches[1];
        $min = (int)$matches[2];
        $sec = (int)$matches[3];
        $ms = isset($matches[4]) ? (int)str_pad(substr($matches[4], 1), 3, '0') : 0;

        return self::make($hour, $min, $sec, $ms);
    }

    /** Private constructor */
    public function __construct(int $hour, int $min, int $sec, int $ms)
    {
        $this->hour = $hour;
        $this->min = $min;
        $this->sec = $sec;
        $this->ms = $ms;
    }

    /** Hash is based on hour, min, sec, ms */
    public function hashCode(): int
    {
        return ($this->hour << 24) ^ ($this->min << 20) ^ ($this->sec << 16) ^ $this->ms;
    }

    /** Equals is based on hour, min, sec, ms */
    public function equals(object $that): bool
    {
        if (!($that instanceof HTime)) {
            return false;
        }
        $x = $that;
        return $this->hour === $x->hour && $this->min === $x->min && $this->sec === $x->sec && $this->ms === $x->ms;
    }

    /** Return sort order as negative, 0, or positive */
    public function compareTo(object $that): int
    {
        $x = $that;
        if ($this->hour < $x->hour) {
            return -1;
        } elseif ($this->hour > $x->hour) {
            return 1;
        }
        if ($this->min < $x->min) {
            return -1;
        } elseif ($this->min > $x->min) {
            return 1;
        }
        if ($this->sec < $x->sec) {
            return -1;
        } elseif ($this->sec > $x->sec) {
            return 1;
        }
        if ($this->ms < $x->ms) {
            return -1;
        } elseif ($this->ms > $x->ms) {
            return 1;
        }
        return 0;
    }

    /** Encode as "h:hh:mm:ss.FFF" */
    public function toJson(): string
    {
        $s = "h:";
        $this->encode($s);
        return $s;
    }

    /** Encode as "hh:mm:ss.FFF" */
    public function toZinc(): string
    {
        $s = '';
        $this->encode($s);
        return $s;
    }

    /** Package private implementation shared with HDateTime */
    public function encode(string &$s): void
    {
        $s .= sprintf('%02d:%02d:%02d', $this->hour, $this->min, $this->sec);
        if ($this->ms !== 0) {
            $s .= sprintf('.%03d', $this->ms);
        }
    }

    public static function init()
    {
        self::$MIDNIGHT = new HTime(0, 0, 0, 0);
    }
}

HTime::init();
