<?php
namespace Cxalloy\HaystackPhp;




/**
 * Translation Notes:
 *
 * 1. Converted JavaScript code to PHP 8.3 syntax.
 * 2. Preserved comments, method, and variable names as much as possible.
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
 * 15. Replaced JavaScript's `isNaN()` function with PHP's `is_nan()` function.
 * 16. Replaced JavaScript's `static` keyword with PHP's `static` keyword for static properties and methods.
 * 17. Replaced JavaScript's `arguments.callee` with PHP's `static` keyword for accessing static properties and methods.
 * 18. Replaced JavaScript's `substr()` function with PHP's `substr()` function.
 * 19. Replaced JavaScript's `charAt()` method with PHP's `substr()` function.
 * 20. Replaced JavaScript's `charCodeAt()` method with PHP's `ord()` function.
 * 21. Replaced JavaScript's `Array.prototype.length` property with PHP's `strlen()` function.
 * 22. Replaced JavaScript's `for` loop with PHP's `for` loop.
 */

use Haystack\DateTime;
use Haystack\Exception;
use HVal;
use HZincReader;

/**
 * HTime models a time (hour, min, sec, ms) tag value.
 * @see {@link http://project-haystack.org/doc/TagModel#tagKinds|Project Haystack}
 *
 * @extends {HVal}
 */
class HTime extends HVal
{
    public static $MIDNIGHT;

    public $hour;
    public $min;
    public $sec;
    public $ms;

    private function __construct($hour, $min, $sec = 0, $ms = 0)
    {
        $this->hour = $hour;
        $this->min = $min;
        $this->sec = $sec;
        $this->ms = $ms;

        if ($hour === 0 && $min === 0 && $this->sec === 0 && $this->ms === 0) {
            if (static::$_midnightSingletonInstance) {
                return static::$_midnightSingletonInstance;
            }
            static::$_midnightSingletonInstance = $this;
        }
    }

    public static function __constructStatic()
    {
        static::$MIDNIGHT = new static(0, 0, 0, 0);
    }

    public function equals($that)
    {
        return $that instanceof HTime && $this->hour === $that->hour &&
            $this->min === $that->min && $this->sec === $that->sec && $this->ms === $that->ms;
    }

    public function compareTo($that)
    {
        if ($this->hour < $that->hour) {
            return -1;
        } elseif ($this->hour > $that->hour) {
            return 1;
        }

        if ($this->min < $that->min) {
            return -1;
        } elseif ($this->min > $that->min) {
            return 1;
        }

        if ($this->sec < $that->sec) {
            return -1;
        } elseif ($this->sec > $that->sec) {
            return 1;
        }

        if ($this->ms < $that->ms) {
            return -1;
        } elseif ($this->ms > $that->ms) {
            return 1;
        }

        return 0;
    }

    public function toZinc()
    {
        $s = "";
        if ($this->hour < 10) {
            $s .= "0";
        }
        $s .= $this->hour . ":";
        if ($this->min < 10) {
            $s .= "0";
        }
        $s .= $this->min . ":";
        if ($this->sec < 10) {
            $s .= "0";
        }
        $s .= $this->sec;
        if ($this->ms !== 0) {
            $s .= ".";
            if ($this->ms < 10) {
                $s .= "0";
            }
            if ($this->ms < 100) {
                $s .= "0";
            }
            $s .= $this->ms;
        }

        return $s;
    }

    public function toJSON()
    {
        return "h:" . $this->toZinc();
    }

    public static function make($arg1, $min = null, $sec = null, $ms = null)
    {
        if (HVal::typeis($arg1, 'string', 'string')) {
            $val = new HZincReader($arg1);
			$val = $val->readScalar();
            if ($val instanceof HTime) {
                return $val;
            }
            throw new Exception("Parse Error: " . $arg1);
        } elseif ($arg1 instanceof DateTime) {
            return new static($arg1->format('H'), $arg1->format('i'), $arg1->format('s'), $arg1->format('u') * 1000);
        } else {
            return new static($arg1, $min, $sec, $ms);
        }
    }

    private static $_midnightSingletonInstance;
}

HTime::__constructStatic();
