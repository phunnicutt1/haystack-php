<?php
namespace Cxalloy\Haystack;

use \Exception;
use Cxalloy\Haystack\HVal;

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
 */



class HNum extends HVal
{
    public static $ZERO;

    public $val;
    public $unit;

    public function __construct($val, $unit = null)
    {
        // ensure singleton usage for zero
        if ($val === 0 && $unit === null) {
            if (static::$_zeroSingletonInstance) {
                return static::$_zeroSingletonInstance;
            }
            static::$_zeroSingletonInstance = $this;
        }

        $this->val = $val;
        $this->unit = $unit;
    }

    public static function __constructStatic()
    {
        static::$ZERO = new static(0);
    }

    public static function make($val, $unit = null)
    {
        if (!HVal::typeis($val, 'number', 'int', 'float')) {
            throw new Exception("Invalid number val: \"" . $val . "\"");
        }
        if ($unit !== null && !HVal::typeis($unit, 'string', 'string')) {
            throw new Exception("Invalid unit: \"" . $unit . "\"");
        }

        if ($val === 0 && $unit === null) {
            return static::$ZERO;
        }
        return new static($val, $unit);
    }

    public function toZinc()
    {
        $s = "";
        if ($this->val < 0) {
            $s .= "-";
        }
        $s .= abs($this->val);
        if ($this->unit !== null) {
            $s .= " " . $this->unit;
        }
        return $s;
    }

    public function toJSON()
    {
        $s = "n:" . $this->val;
        if ($this->unit !== null) {
            $s .= " " . $this->unit;
        }
        return $s;
    }

    public function equals($that)
    {
        return $that instanceof HNum && $this->val === $that->val && $this->unit === $that->unit;
    }

    public function toString()
    {
        return $this->toZinc();
    }

    private static $_zeroSingletonInstance;
}

HNum::__constructStatic();
