<?php

declare(strict_types=1);

namespace Cxalloy\Haystack;

use InvalidArgumentException;
use DateTime;
use DateTimeZone;
use Exception;

/**
 * HDateTime models a timestamp with a specific timezone.
 *
 * @see <a href='http://project-haystack.org/doc/TagModel#tagKinds'>Project Haystack</a>
 */
class HDateTime extends HVal
{
    /** Date component of the timestamp */
    public readonly HDate $date;

    /** Time component of the timestamp */
    public readonly HTime $time;

    /** Offset in seconds from UTC including DST offset */
    public readonly int $tzOffset;

    /** Timezone as Olson database city name */
    public readonly HTimeZone $tz;

    private volatile int $millis = 0;

    /** Constructor with basic fields */
    public static function make(HDate $date, HTime $time, HTimeZone $tz, int $tzOffset): self
    {
        if ($date === null || $time === null || $tz === null) {
            throw new InvalidArgumentException("null args");
        }
        return new self($date, $time, $tz, $tzOffset);
    }

    /** Constructor with date, time, tz, but no tzOffset */
    public static function makeWithoutOffset(HDate $date, HTime $time, HTimeZone $tz): self
    {
        $dateTime = new DateTime(sprintf('%04d-%02d-%02d %02d:%02d:%02d.%03d', $date->year, $date->month, $date->day, $time->hour, $time->min, $time->sec, $time->ms), new DateTimeZone($tz->java));
        $tzOffset = $dateTime->getOffset();
        $millis = $dateTime->getTimestamp() * 1000 + (int)($dateTime->format('v'));

        $ts = new self($date, $time, $tz, $tzOffset);
        $ts->millis = $millis;

        return $ts;
    }

    /** Constructor with date and time (to sec) fields */
    public static function makeFromFields(int $year, int $month, int $day, int $hour, int $min, int $sec, HTimeZone $tz, int $tzOffset): self
    {
        return self::make(HDate::make($year, $month, $day), HTime::make($hour, $min, $sec), $tz, $tzOffset);
    }

    /** Constructor with date and time (to min) fields */
    public static function makeFromFieldsToMin(int $year, int $month, int $day, int $hour, int $min, HTimeZone $tz, int $tzOffset): self
    {
        return self::make(HDate::make($year, $month, $day), HTime::make($hour, $min), $tz, $tzOffset);
    }

    /** Constructor with PHP DateTime instance */
    public static function makeFromDateTime(DateTime $dateTime, HTimeZone $tz): self
    {
        $date = HDate::makeFromDateTime($dateTime);
        $time = HTime::makeFromDateTime($dateTime);
        $tzOffset = $dateTime->getOffset();
        $millis = $dateTime->getTimestamp() * 1000 + (int)($dateTime->format('v'));

        $ts = new self($date, $time, $tz, $tzOffset);
        $ts->millis = $millis;

        return $ts;
    }

    /** Parse from string format "YYYY-MM-DD'T'hh:mm:ss.FFFz zzzz" or raise Exception */
    public static function makeFromString(string $s): self
    {
        $tIdx = strpos($s, 'T');
        if ($tIdx === false) {
            throw new Exception("Invalid date-time format: $s");
        }

        $date = HDate::makeFromString(substr($s, 0, $tIdx));

        if (str_ends_with($s, 'Z')) {
            return self::makeWithoutOffset($date, HTime::makeFromString(substr($s, $tIdx + 1, -1)), HTimeZone::UTC());
        }

        if (str_ends_with($s, 'Z UTC')) {
            return self::makeWithoutOffset($date, HTime::makeFromString(substr($s, $tIdx + 1, -5)), HTimeZone::UTC());
        }

        $spIdx = strrpos($s, ' ');
        if ($spIdx === false) {
            throw new Exception("Expected time zone name: $s");
        }

        $offsetIdx = $spIdx - 1;
        while (true) {
            if ($offsetIdx <= $tIdx) {
                throw new Exception("Expected Z or -/+ for timezone offset: $s");
            }
            $c = $s[$offsetIdx];
            if ($c === '-' || $c === '+' || $c === 'Z') {
                break;
            }
            --$offsetIdx;
        }

        $offsetStr = substr($s, $offsetIdx, $spIdx - $offsetIdx);
        $offset = $offsetStr === 'Z' ? 0 : self::parseOffset($offsetStr);

        $tz = HTimeZone::make(substr($s, $spIdx + 1));

        return self::make($date, HTime::makeFromString(substr($s, $tIdx + 1, $offsetIdx - $tIdx - 1)), $tz, $offset);
    }

    private static function parseOffset(string $s): int
    {
        if (strlen($s) !== 6) {
            throw new Exception("Invalid tz offset: $s");
        }

        $sign = $s[0] === '-' ? -1 : 1;
        $tzHours = (int)substr($s, 1, 2);
        if ($s[3] !== ':') {
            throw new Exception("Invalid tz offset: $s");
        }
        $tzMins = (int)substr($s, 4);

        return $sign * ($tzHours * 3600 + $tzMins * 60);
    }

    /** Get HDateTime for current time in default timezone */
    public static function now(): self
    {
        return self::makeFromDateTime(new DateTime('now', new DateTimeZone(date_default_timezone_get())), HTimeZone::DEFAULT());
    }

    /** Get HDateTime for given timezone */
    public static function nowInTimeZone(HTimeZone $tz): self
    {
        return self::makeFromDateTime(new DateTime('now', new DateTimeZone($tz->java)), $tz);
    }

    /** Private constructor */
    private function __construct(HDate $date, HTime $time, HTimeZone $tz, int $tzOffset)
    {
        $this->date = $date;
        $this->time = $time;
        $this->tz = $tz;
        $this->tzOffset = $tzOffset;
    }

    /** Get this date time as Java milliseconds since epoch */
    public function millis(): int
    {
        if ($this->millis <= 0) {
            $dateTime = new DateTime(sprintf('%04d-%02d-%02d %02d:%02d:%02d.%03d', $this->date->year, $this->date->month, $this->date->day, $this->time->hour, $this->time->min, $this->time->sec, $this->time->ms), new DateTimeZone('UTC'));
            $dateTime->setTimezone(new DateTimeZone($this->tz->java));
            $dateTime->setOffset($this->tzOffset);
            $this->millis = $dateTime->getTimestamp() * 1000 + (int)($dateTime->format('v'));
        }

        return $this->millis;
    }

    /** Hash is based on date, time, tzOffset, and tz */
    public function hashCode(): int
    {
        return $this->date->hashCode() ^ $this->time->hashCode() ^ $this->tzOffset ^ $this->tz->hashCode();
    }

    /** Equals is based on date, time, tzOffset, and tz */
    public function equals(object $that): bool
    {
        if (!$that instanceof self) {
            return false;
        }
        return $this->date->equals($that->date) && $this->time->equals($that->time) && $this->tzOffset === $that->tzOffset && $this->tz->equals($that->tz);
    }

    /** Comparison based on millis. */
    public function compareTo(object $that): int
    {
        $thisMillis = $this->millis();
        $thatMillis = $that->millis();

        return $thisMillis <=> $thatMillis;
    }

    /** Encode as "t:YYYY-MM-DD'T'hh:mm:ss.FFFz zzzz" */
    public function toJson(): string
    {
        return 't:' . $this->toZinc();
    }

    /** Encode as "YYYY-MM-DD'T'hh:mm:ss.FFFz zzzz" */
    public function toZinc(): string
    {
        $s = sprintf('%04d-%02d-%02dT%02d:%02d:%02d.%03d', $this->date->year, $this->date->month, $this->date->day, $this->time->hour, $this->time->min, $this->time->sec, $this->time->ms);

        if ($this->tzOffset === 0) {
            $s .= 'Z';
        } else {
            $offset = $this->tzOffset;
            $sign = $offset < 0 ? '-' : '+';
            $offset = abs($offset);
            $zh = intdiv($offset, 3600);
            $zm = intdiv($offset % 3600, 60);
            $s .= sprintf('%s%02d:%02d', $sign, $zh, $zm);
        }

        return $s . ' ' . $this->tz;
    }
}
