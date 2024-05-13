<?php
namespace Cxalloy\HaystackPhp;

use Haystack\DateTime;
use Haystack\Exception;
use HDateTime;
use HTime;
use HTimeZone;
use HVal;

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
 * 14. Replaced JavaScript's `explode()` function with PHP's `explode()` function.
 * 15. Replaced JavaScript's `Date` class with PHP's `DateTime` class.
 * 16. Replaced JavaScript's `static` keyword with PHP's `static` keyword for static methods.
 */



class HDate extends HVal
{
    public $year;
    public $month;
    public $day;

    private function __construct($year, $month, $day)
    {
        $this->year = $year;
        $this->month = $month;
        $this->day = $day;
    }

    public function toZinc()
    {
        $s = $this->year . "-";
        if ($this->month < 10) {
            $s .= "0";
        }
        $s .= $this->month . "-";
        if ($this->day < 10) {
            $s .= "0";
        }
        $s .= $this->day;
        return $s;
    }

    public function toJSON()
    {
        return "d:" . $this->toZinc();
    }

    public function equals($that)
    {
        return $that instanceof HDate && $this->year === $that->year && $this->month === $that->month && $this->day === $that->day;
    }

    public function compareTo($that)
    {
        if ($this->year < $that->year) {
            return -1;
        } elseif ($this->year > $that->year) {
            return 1;
        }

        if ($this->month < $that->month) {
            return -1;
        } elseif ($this->month > $that->month) {
            return 1;
        }

        if ($this->day < $that->day) {
            return -1;
        } elseif ($this->day > $that->day) {
            return 1;
        }

        return 0;
    }

    public function plusDays($numDays)
    {
        if ($numDays === 0) {
            return $this;
        }
        if ($numDays < 0) {
            return $this->minusDays(-$numDays);
        }

        $year = $this->year;
        $month = $this->month;
        $day = $this->day;
        for ($i = 0; $i < $numDays; $i++) {
            $day++;
            if ($day > self::daysInMonth($year, $month)) {
                $day = 1;
                $month++;
                if ($month > 12) {
                    $month = 1;
                    $year++;
                }
            }
        }
        return self::make($year, $month, $day);
    }

    public function minusDays($numDays)
    {
        if ($numDays === 0) {
            return $this;
        }
        if ($numDays < 0) {
            return $this->plusDays(-$numDays);
        }

        $year = $this->year;
        $month = $this->month;
        $day = $this->day;
        for ($i = 0; $i < $numDays; $i++) {
            $day--;
            if ($day <= 0) {
                $month--;
                if ($month < 1) {
                    $month = 12;
                    $year--;
                }
                $day = self::daysInMonth($year, $month);
            }
        }
        return self::make($year, $month, $day);
    }

    public function weekday()
    {
        $date = new DateTime($this->year . "-" . $this->month . "-" . $this->day);
        return $date->format('N');
    }

    public static function make($arg, $month = null, $day = null)
    {
        if ($arg instanceof DateTime) {
            return new self($arg->format('Y'), $arg->format('n'), $arg->format('j'));
        } elseif (HVal::typeis($arg, 'string', 'string')) {
            $s = explode('-', $arg);
            if (count($s) < 3) {
                throw new Exception("Invalid string format, should be YYYY-MM-DD");
            }

            return new self(intval($s[0]), intval($s[1]), intval($s[2]));
        } else {
            if ($arg < 1900) {
                throw new Exception("Invalid year");
            }
            if ($month < 1 || $month > 12) {
                throw new Exception("Invalid month");
            }
            if ($day < 1 || $day > 31) {
                throw new Exception("Invalid day");
            }

            return new self($arg, $month, $day);
        }
    }

    public static function isLeapYear($year)
    {
        if (($year & 3) !== 0) {
            return false;
        }
        return ($year % 100 !== 0) || ($year % 400 === 0);
    }

    private static $daysInMon = [-1, 31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
    private static $daysInMonLeap = [-1, 31, 29, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];

    public static function daysInMonth($year, $mon)
    {
        return self::isLeapYear($year) ? self::$daysInMonLeap[$mon] : self::$daysInMon[$mon];
    }

    public static function today()
    {
        return HDateTime::now()->date;
    }

    public static function midnight($date, $tz)
    {
        return HDateTime::make($date, HTime::MIDNIGHT, $tz);
    }
}
