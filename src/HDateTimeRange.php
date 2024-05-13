<?php
namespace Haystack;



/**
 * Translation Notes:
 *
 * 1. Converted JavaScript code to PHP 8.3 syntax.
 * 2. Preserved method and variable names as much as possible.
 * 3. Replaced JavaScript's `module.exports` with PHP's `class` syntax.
 * 4. Replaced JavaScript's `require` statements with PHP's `use` statements for class imports.
 * 5. Replaced JavaScript's `function` syntax with PHP's `function` syntax for class methods.
 * 6. Replaced JavaScript's `this` keyword with PHP's `$this` for class method access.
 * 7. Replaced JavaScript's `null` with PHP's `null`.
 * 8. Replaced JavaScript's `undefined` with PHP's `null`.
 * 9. Replaced JavaScript's `throw` statement with PHP's `throw` statement.
 * 10. Replaced JavaScript's `Error` class with PHP's `Exception` class.
 * 11. Replaced JavaScript's `instanceof` operator with PHP's `instanceof` operator.
 * 12. Replaced JavaScript's string concatenation with PHP's string concatenation operator `.`.
 * 13. Replaced JavaScript's `static` keyword with PHP's `static` keyword for static methods.
 */

use Haystack\Exception;
use HDate;
use HDateTime;
use HTimeZone;

class HDateTimeRange
{
    public $start;
    public $end;

    private function __construct($start, $end)
    {
        $this->start = $start;
        $this->end = $end;
    }

    public function __toString()
    {
        return $this->start->toString() . "," . $this->end->toString();
    }

    public static function make($arg1, $arg2 = null, $arg3 = null)
    {
        $arg1 = $arg1;
        $arg2 = $arg2;
        $arg3 = $arg3;
        if ($arg1 instanceof HDateTime) {
            /** Make from two timestamps */
            if ($arg1->tz !== $arg2->tz) {
                throw new Exception("_arg1->tz != _arg2->tz");
            }
            return new self($arg1, $arg2);
        } elseif ($arg1 instanceof HDate) {
            /** Make for inclusive dates within given timezone */
            if ($arg2 instanceof HTimeZone) {
                $arg3 = $arg2;
                $arg2 = $arg1;
            }
            // Make for single date within given timezone
            return self::make(HDate::midnight($arg1, $arg3), HDate::midnight($arg2->plusDays(1), $arg3));
        } else {
            /** Parse from string using the given timezone as context for date based ranges. */
            // handle keywords
            $str = trim($arg1);
            if ($str === "today") {
                return self::make(HDate::today(), $arg2);
            }
            if ($str === "yesterday") {
                return self::make(HDate::today()->minusDays(1), $arg2);
            }

            // parse scalars
            $comma = strpos($str, ',');
            $start = null;
            $end = null;
            if ($comma === false) {
                $start = HDateTime::make($str, $arg2);
            } else {
                $start = HDateTime::make(substr($str, 0, $comma), $arg2);
                $end = HDateTime::make(substr($str, $comma + 1), $arg2);
            }

            // figure out what we parsed for start,end
            if ($start instanceof HDate) {
                if ($end === null) {
                    return new self($start, $start);
                }
                if ($end instanceof HDate) {
                    return new self($start, $end);
                }
            } elseif ($start instanceof HDateTime) {
                if ($end === null) {
                    return new self($start, HDateTime::now($arg2));
                }
                if ($end instanceof HDateTime) {
                    return new self($start, $end);
                }
            }

            throw new Exception("Invalid HDateTimeRange: " . $str);
        }
    }

    public static function thisWeek($tz)
    {
        $today = HDate::today();
        $sun = $today->minusDays($today->weekday() - 1);
        $sat = $today->plusDays(7 - $today->weekday());
        return new self($sun, $sat, $tz);
    }

    public static function thisMonth($tz)
    {
        $today = HDate::today();
        $first = HDate::make($today->year, $today->month, 1);
        $last = HDate::make($today->year, $today->month, HDate::daysInMonth($today->year, $today->month));
        return new self($first, $last, $tz);
    }

    public static function thisYear($tz)
    {
        $today = HDate::today();
        $first = HDate::make($today->year, 1, 1);
        $last = HDate::make($today->year, 12, 31);
        return new self($first, $last, $tz);
    }

    public static function lastWeek($tz)
    {
        $today = HDate::today();
        $prev = $today->minusDays(7);
        $sun = $prev->minusDays($prev->weekday() - 1);
        $sat = $prev->plusDays(7 - $prev->weekday());
        return new self($sun, $sat, $tz);
    }

    public static function lastMonth($tz)
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
        return new self($first, $last, $tz);
    }

    public static function lastYear($tz)
    {
        $today = HDate::today();
        $first = HDate::make($today->year - 1, 1, 1);
        $last = HDate::make($today->year - 1, 12, 31);
        return new self($first, $last, $tz);
    }
}
