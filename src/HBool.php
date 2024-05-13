<?php
namespace Haystack;

use Haystack\Exception;
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
 * 12. Replaced JavaScript's `arguments.callee` with PHP's `static` keyword for accessing static properties and methods.
 * 13. Replaced JavaScript's string concatenation with PHP's string concatenation operator `.`.
 */


class HBool extends HVal
{
    public static $TRUE;
    public static $FALSE;

    public $val;

    public function __construct($val)
    {
        // ensure singleton usage
        if ($val && static::$_trueSingletonInstance) {
            return static::$_trueSingletonInstance;
        }
        if (!$val && static::$_falseSingletonInstance) {
            return static::$_falseSingletonInstance;
        }

        if ($val) {
            static::$_trueSingletonInstance = $this;
        } else {
            static::$_falseSingletonInstance = $this;
        }

        $this->val = $val;
    }

    public static function __constructStatic()
    {
        static::$TRUE = new static(true);
        static::$FALSE = new static(false);
    }

    public static function make($val)
    {
        if (!HVal::typeis($val, 'boolean', 'bool')) {
            throw new Exception("Invalid boolean val: \"" . $val . "\"");
        }

        return $val ? static::$TRUE : static::$FALSE;
    }

    public function toZinc()
    {
        return $this->val ? "T" : "F";
    }

    public function toJSON()
    {
        return $this->toString();
    }

    public function equals($that)
    {
        return $that instanceof HBool && $this === $that;
    }

    public function toString()
    {
        return $this->val ? "true" : "false";
    }

    private static $_trueSingletonInstance;
    private static $_falseSingletonInstance;
}

HBool::__constructStatic();
