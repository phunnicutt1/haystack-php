<?php

declare(strict_types=1);

namespace Cxalloy\Haystack;

use InvalidArgumentException;

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

    /** Private constructor */
    public function __construct(int $hour, int $min, int $sec = 0, int $ms = 0)
    {
        $this->hour = $hour;
        $this->min = $min;
        $this->sec = $sec;
        $this->ms = $ms;
    }

    /**
     * Construct with all fields, with DateTime object, or parse from string format "hh:mm:ss.FF"
     *
     * @param int|string|\DateTime $arg1
     * @param int|null $min
     * @param int|null $sec
     * @param int|null $ms
     * @return HTime
     */
    public static function make($arg1, ?int $min = null, ?int $sec = null, ?int $ms = null): HTime
    {
        if (is_string($arg1)) {
            $val = (new HZincReader($arg1))->readScalar();
            if ($val instanceof HTime) {
                return $val;
            }
            throw new InvalidArgumentException("Parse Error: $arg1");
        } elseif ($arg1 instanceof \DateTime) {
            return new HTime((int)$arg1->format('H'), (int)$arg1->format('i'), (int)$arg1->format('s'), (int)$arg1->format('v'));
        } else {
            return new HTime($arg1, $min ?? 0, $sec ?? 0, $ms ?? 0);
        }
    }

    /**
     * Equals is based on hour, min, sec, ms
     *
     * @param HTime $that
     * @return bool
     */
    public function equals(HVal | HTime $that): bool
    {
        return $that instanceof HTime && $this->hour === $that->hour &&
            $this->min === $that->min && $this->sec === $that->sec && $this->ms === $that->ms;
    }

    /**
     * Return sort order as negative, 0, or positive
     *
     * @param HTime $that
     * @return int
     */
    public function compareTo(HVal | HTime $that): int
    {
        if ($this->hour < $that->hour) return -1;
        if ($this->hour > $that->hour) return 1;

        if ($this->min < $that->min) return -1;
        if ($this->min > $that->min) return 1;

        if ($this->sec < $that->sec) return -1;
        if ($this->sec > $that->sec) return 1;

        if ($this->ms < $that->ms) return -1;
        if ($this->ms > $that->ms) return 1;

        return 0;
    }

    /**
     * Encode as "hh:mm:ss.FFF"
     *
     * @return string
     */
    public function toZinc(): string
    {
        $s = sprintf('%02d:%02d:%02d', $this->hour, $this->min, $this->sec);
        if ($this->ms !== 0) {
            $s .= sprintf('.%03d', $this->ms);
        }
        return $s;
    }

    /**
     * Encode as "h:hh:mm:ss.FFF"
     *
     * @return string
     */
    public function toJSON(): string
    {
        return 'h:' . $this->toZinc();
    }
}

// Initialize the static properties
HTime::$MIDNIGHT = new HTime(0, 0, 0, 0);
