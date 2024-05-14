<?php
namespace Cxalloy\Haystack;

use Haystack\DateTime;
use Haystack\Exception;
use HDate;
use HTime;
use HTimeZone;
use HVal;
use io\HZincReader;
use function Haystack\readScalar;

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
 * 13. Replaced JavaScript's `parseInt()` function with PHP's `intval()` function.
 * 14. Replaced JavaScript's `parseFloat()` function with PHP's `floatval()` function.
 * 15. Replaced JavaScript's `Date` class with PHP's `DateTime` class.
 * 16. Replaced JavaScript's `static` keyword with PHP's `static` keyword for static methods.
 * 17. Replaced JavaScript's `arguments.callee` with PHP's `static` keyword for accessing static properties and methods.
 */


class HDateTime extends HVal
{
    public $date;
    public $time;
    public $tz;
    public $tzOffset;
    public $mils;

    private function __construct($date, $time, $tz, $tzOffset)
    {
        $this->date = $date;
        $this->time = $time;
        $this->tz = $tz;
        $this->tzOffset = $tzOffset;
        $this->mils = 0;
    }

    public function millis()
    {
        if ($this->mils <= 0) {
            $this->mils = intval(self::getMomentDate($this->date, $this->time, $this->tz)->getTimestamp() * 1000);
        }

        return $this->mils;
    }

    private static function getMomentDate($date, $time, $tz)
    {
        // convert to designated timezone
        $ds = $date->year . "-";
        if ($date->month < 10) {
            $ds .= "0";
        }
        $ds .= $date->month . "-";
        if ($date->day < 10) {
            $ds .= "0";
        }
        $ds .= $date->day . " ";
        if ($time->hour < 10) {
            $ds .= "0";
        }
        $ds .= $time->hour . ":";
        if ($time->min < 10) {
            $ds .= "0";
        }
        $ds .= $time->min . ":";
        if ($time->sec < 10) {
            $ds .= "0";
        }
        $ds .= $time->sec;
        if ($time->ms > 0) {
            $ds .= ".";
            if ($time->ms < 100) {
                $ds .= "0";
            }
            if ($time->ms < 10) {
                $ds .= "0";
            }
            $ds .= $time->ms;
        }

        $m = new DateTime($ds . "Z", $tz->js->name);
        $tzOffset = $m->getOffset() * 60;

        $ts = self::make($date, $time, $tz, $tzOffset);

        return $ts;
    }

    public static function make($arg1, $arg2 = null, $arg3 = null, $arg4 = null) : HDateTime
    {
        if ($arg1 instanceof HDate) {
            /** Make from two timestamps */
            if ($arg1->tz !== $arg2->tz) {
                throw new Exception("_arg1->tz != _arg2->tz");
            }
            return new self($arg1, $arg2, $arg3, $arg4);
        } elseif ($arg1 instanceof HDate) {
            /** Make for inclusive dates within given timezone */
            if ($arg2 instanceof HTimeZone) {
                $arg3 = $arg2;
                $arg2 = $arg1;
            }
            // Make for single date within given timezone
            return self::make(HDate::midnight($arg1, $arg3), HDate::midnight($arg2->plusDays(1), $arg3));
        } elseif (HVal::typeis($arg1, 'string', 'string')) {
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
                $start = new HZincReader($str);
				$start->readScalar();
            } else {
                $start = new HZincReader(substr($str, 0, $comma));
				$start->readScalar();
                $end = new HZincReader(substr($str, $comma + 1));
				$end->readScalar();
            }

            // figure out what we parsed for start,end
            if ($start instanceof HDate) {
                if ($end === null) {
                    return self::make($start, $arg2);
                }
                if ($end instanceof HDate) {
                    return self::make($start, $end, $arg2);
                }
            } elseif ($start instanceof HDateTime) {
                if ($end === null) {
                    return self::make($start, self::now($arg2));
                }
                if ($end instanceof HDateTime) {
                    return self::make($start, $end);
                }
            }

            throw new Exception("Invalid HDateTimeRange: " . $str);
        } else {
            $tz = $arg2;
            if ($tz === null) {
                $tz = HTimeZone::DEFAULT;
            }

            $d = new DateTime("@" . intval($arg1 / 1000));
            // convert to designated timezone
            $ds = $d->format('Y-m-d\TH:i:s.u');

            $m = new DateTime($ds . "Z", $tz->js->name);
            $tzOffset = $m->getOffset();

            $ts = new self(HDate::make($m->format('Y'), $m->format('n'), $m->format('j')), HTime::make($m->format('H'), $m->format('i'), $m->format('s'), intval($m->format('u') / 1000)), $tz, $tzOffset);
            $ts->mils = $arg1;

            return $ts;
        }
    }

    public static function now($tz = null)
    {
        if ($tz === null) {
            $tz = HTimeZone::DEFAULT;
        }
        $d = new DateTime();
        return self::make($d->getTimestamp() * 1000, $tz);
    }

    public static function thisWeek($tz)
    {
        $today = HDate::today();
        $sun = $today->minusDays($today->weekday() - 1);
        $sat = $today->plusDays(7 - $today->weekday());
        return self::make($sun, $sat, $tz);
    }

    public static function thisMonth($tz)
    {
        $today = HDate::today();
        $first = HDate::make($today->year, $today->month, 1);
        $last = HDate::make($today->year, $today->month, HDate::daysInMonth($today->year, $today->month));
        return self::make($first, $last, $tz);
    }

    public static function thisYear($tz)
    {
        $today = HDate::today();
        $first = HDate::make($today->year, 1, 1);
        $last = HDate::make($today->year, 12, 31);
        return self::make($first, $last, $tz);
    }

    public static function lastWeek($tz)
    {
        $today = HDate::today();
        $prev = $today->minusDays(7);
        $sun = $prev->minusDays($prev->weekday() - 1);
        $sat = $prev->plusDays(7 - $prev->weekday());
        return self::make($sun, $sat, $tz);
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
        return self::make($first, $last, $tz);
    }

    public static function lastYear($tz)
    {
        $today = HDate::today();
        $first = HDate::make($today->year - 1, 1, 1);
        $last = HDate::make($today->year - 1, 12, 31);
        return self::make($first, $last, $tz);
    }
}
