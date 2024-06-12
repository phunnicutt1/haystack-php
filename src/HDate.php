<?php

declare(strict_types=1);

namespace Cxalloy\Haystack;

use InvalidArgumentException;
use DateTime;
use DateTimeZone;
use Exception;

/**
 * HDate models a date (day in year) tag value.
 *
 * @see <a href='http://project-haystack.org/doc/TagModel#tagKinds'>Project Haystack</a>
 */
class HDate extends HVal
{
    /** Four digit year such as 2011 */
    public readonly int $year;

    /** Month as 1-12 (Jan is 1, Dec is 12) */
    public readonly int $month;

    /** Day of month as 1-31 */
    public readonly int $day;

    /** Construct from basic fields */
    public static function make(int $year, int $month, int $day): self
    {
        if ($year < 1900) {
            throw new InvalidArgumentException("Invalid year");
        }
        if ($month < 1 || $month > 12) {
            throw new InvalidArgumentException("Invalid month");
        }
        if ($day < 1 || $day > 31) {
            throw new InvalidArgumentException("Invalid day");
        }
        return new self($year, $month, $day);
    }

    /** Construct from PHP DateTime instance */
    public static function makeFromDateTime(DateTime $dateTime): self
    {
        return new self(
            (int)$dateTime->format('Y'),
            (int)$dateTime->format('m'),
            (int)$dateTime->format('d')
        );
    }

    /** Parse from string format "YYYY-MM-DD" or raise Exception */
    public static function makeFromString(string $s): self
    {
        if (strlen($s) !== 10) {
            throw new Exception("Invalid date format: $s");
        }

        [$year, $month, $day] = sscanf($s, "%d-%d-%d");

        if ($year === null || $month === null || $day === null) {
            throw new Exception("Invalid date components in: $s");
        }

        return self::make($year, $month, $day);
    }

    /** Get HDate for current time in default timezone */
    public static function today(): self
    {
        return self::makeFromDateTime(new DateTime('now', new DateTimeZone(date_default_timezone_get())));
    }

    /** Private constructor */
    private function __construct(int $year, int $month, int $day)
    {
        $this->year = $year;
        $this->month = $month;
        $this->day = $day;
    }

    /** Hash is based on year, month, day */
    public function hashCode(): int
    {
        return ($this->year << 16) ^ ($this->month << 8) ^ $this->day;
    }

    /** Equals is based on year, month, day */
    public function equals(object $that): bool
    {
        if (!$that instanceof self) {
            return false;
        }
        return $this->year === $that->year && $this->month === $that->month && $this->day === $that->day;
    }

    /** Return sort order as negative, 0, or positive */
    public function compareTo(object $that): int
    {
        if (!$that instanceof self) {
            throw new InvalidArgumentException("Cannot compare HDate with " . get_class($that));
        }

        return $this->year <=> $that->year
            ?: $this->month <=> $that->month
            ?: $this->day <=> $that->day;
    }

    /** Encode as "d:YYYY-MM-DD" */
    public function toJson(): string
    {
        return 'd:' . $this->toZinc();
    }

    /** Encode as "YYYY-MM-DD" */
    public function toZinc(): string
    {
        return sprintf('%04d-%02d-%02d', $this->year, $this->month, $this->day);
    }

    /** Convert this date into HDateTime for midnight in given timezone. */
    public function midnight(HTimeZone $tz): HDateTime
    {
        return HDateTime::make($this, HTime::MIDNIGHT, $tz);
    }

    /** Return date in future given number of days */
    public function plusDays(int $numDays): self
    {
        if ($numDays === 0) {
            return $this;
        }

        $date = new DateTime(sprintf('%04d-%02d-%02d', $this->year, $this->month, $this->day));
        $date->modify("+$numDays days");

        return self::makeFromDateTime($date);
    }

    /** Return date in past given number of days */
    public function minusDays(int $numDays): self
    {
        if ($numDays === 0) {
            return $this;
        }

        $date = new DateTime(sprintf('%04d-%02d-%02d', $this->year, $this->month, $this->day));
        $date->modify("-$numDays days");

        return self::makeFromDateTime($date);
    }

    /** Return if given year a leap year */
    public static function isLeapYear(int $year): bool
    {
        return ($year % 4 === 0 && $year % 100 !== 0) || ($year % 400 === 0);
    }

    /** Return number of days in given year (xxxx) and month (1-12) */
    public static function daysInMonth(int $year, int $month): int
    {
        return self::isLeapYear($year) ? self::$daysInMonLeap[$month] : self::$daysInMon[$month];
    }

    /** Return day of week: Sunday is 1, Saturday is 7 */
    public function weekday(): int
    {
        $date = new DateTime(sprintf('%04d-%02d-%02d', $this->year, $this->month, $this->day));
        return (int)$date->format('N');
    }

    private static array $daysInMon = [-1, 31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
    private static array $daysInMonLeap = [-1, 31, 29, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
}
