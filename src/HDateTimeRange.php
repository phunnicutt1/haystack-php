<?php

declare(strict_types=1);

namespace Cxalloy\Haystack;

use InvalidArgumentException;
use Exception;

/**
 * HDateTimeRange models a starting and ending timestamp
 *
 * @see <a href='http://project-haystack.org/doc/Ops#hisRead'>Project Haystack</a>
 */
class HDateTimeRange
{
    /** Inclusive starting timestamp */
    public readonly HDateTime $start;

    /** Inclusive ending timestamp */
    public readonly HDateTime $end;

    /**
     * Parse from string using the given timezone as context for
     * date based ranges. The formats are:
     *  - "today"
     *  - "yesterday"
     *  - "{date}"
     *  - "{date},{date}"
     *  - "{dateTime},{dateTime}"
     *  - "{dateTime}"  // anything after given timestamp
     * Throw Exception if invalid string format.
     */
    public static function makeFromString(string $str, HTimeZone $tz): self
    {
        // handle keywords
        $str = trim($str);
        if ($str === "today") {
            return self::make(HDate::today(), $tz);
        }
        if ($str === "yesterday") {
            return self::make(HDate::today()->minusDays(1), $tz);
        }

        // parse scalars
        $comma = strpos($str, ',');
        $start = null;
        $end = null;

        if ($comma === false) {
            $start = (new HZincReader($str))->readVal();
        } else {
            $start = (new HZincReader(substr($str, 0, $comma)))->readVal();
            $end = (new HZincReader(substr($str, $comma + 1)))->readVal();
        }

        // figure out what we parsed for start, end
        if ($start instanceof HDate) {
            if ($end === null) {
                return self::make($start, $tz);
            }
            if ($end instanceof HDate) {
                return self::make($start, $end, $tz);
            }
        } elseif ($start instanceof HDateTime) {
            if ($end === null) {
                return self::make($start, HDateTime::nowInTimeZone($tz));
            }
            if ($end instanceof HDateTime) {
                return self::make($start, $end);
            }
        }

        throw new Exception("Invalid HDateTimeRange: $str");
    }

    /** Make for single date within given timezone */
    public static function make(HDate $date, HTimeZone $tz): self
    {
        return self::makeFromDates($date, $date, $tz);
    }

    /** Make for inclusive dates within given timezone */
    public static function makeFromDates(HDate $start, HDate $end, HTimeZone $tz): self
    {
        return self::make($start->midnight($tz), $end->plusDays(1)->midnight($tz));
    }

    /** Make from two timestamps */
    public static function make(HDateTime $start, HDateTime $end): self
    {
        if ($start->tz !== $end->tz) {
            throw new InvalidArgumentException("start.tz != end.tz");
        }
        return new self($start, $end);
    }

    /** Make a range which encompasses the current week.
     *  The week is defined as Sunday thru Saturday.
     */
    public static function thisWeek(HTimeZone $tz): self
    {
        $today = HDate::today();
        $sun = $today->minusDays($today->weekday() - 1); // 1 is Sunday in PHP
        $sat = $today->plusDays(7 - $today->weekday()); // 7 is Saturday in PHP
        return self::makeFromDates($sun, $sat, $tz);
    }

    /** Make a range which encompasses the current month. */
    public static function thisMonth(HTimeZone $tz): self
    {
        $today = HDate::today();
        $first = HDate::make($today->year, $today->month, 1);
        $last = HDate::make($today->year, $today->month, HDate::daysInMonth($today->year, $today->month));
        return self::makeFromDates($first, $last, $tz);
    }

    /** Make a range which encompasses the current year. */
    public static function thisYear(HTimeZone $tz): self
    {
        $today = HDate::today();
        $first = HDate::make($today->year, 1, 1);
        $last = HDate::make($today->year, 12, 31);
        return self::makeFromDates($first, $last, $tz);
    }

    /** Make a range which encompasses the previous week.
     *  The week is defined as Sunday thru Saturday.
     */
    public static function lastWeek(HTimeZone $tz): self
    {
        $today = HDate::today();
        $prev = $today->minusDays(7);
        $sun = $prev->minusDays($prev->weekday() - 1); // 1 is Sunday in PHP
        $sat = $prev->plusDays(7 - $prev->weekday()); // 7 is Saturday in PHP
        return self::makeFromDates($sun, $sat, $tz);
    }

    /** Make a range which encompasses the previous month. */
    public static function lastMonth(HTimeZone $tz): self
    {
        $today = HDate::today();
        $year = $today->year;
        $month = $today->month;

        if ($month === 1) {
            $year--;
            $month = 12;
        } else {
            $month--;
        }

        $first = HDate::make($year, $month, 1);
        $last = HDate::make($year, $month, HDate::daysInMonth($year, $month));
        return self::makeFromDates($first, $last, $tz);
    }

    /** Make a range which encompasses the previous year. */
    public static function lastYear(HTimeZone $tz): self
    {
        $today = HDate::today();
        $first = HDate::make($today->year - 1, 1, 1);
        $last = HDate::make($today->year - 1, 12, 31);
        return self::makeFromDates($first, $last, $tz);
    }

    /** Private constructor */
    private function __construct(HDateTime $start, HDateTime $end)
    {
        $this->start = $start;
        $this->end = $end;
    }

    /** Return "start to end" */
    public function __toString(): string
    {
        return $this->start->__toString() . "," . $this->end->__toString();
    }
}
