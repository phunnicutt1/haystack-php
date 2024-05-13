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

use Haystack\Exception;
use HStr;
use HVal;

/**
 * HUri wraps a URI tag value.
 * @see {@link http://project-haystack.org/doc/TagModel#tagKinds|Project Haystack}
 *
 * @extends {HVal}
 */
class HUri extends HVal
{
    public static $EMPTY;

    public $val;

    private function __construct($val)
    {
        $this->val = $val;
    }

    public static function __constructStatic()
    {
        static::$EMPTY = new static("");
    }

    public function toString()
    {
        return $this->val;
    }

    public function toZinc()
    {
        $s = "`";
        $s .= self::parse($this);
        $s .= "`";
        return $s;
    }

    public function toJSON()
    {
        return "u:" . self::parse($this);
    }

    private static function parse($self)
    {
        $s = "";
        for ($i = 0; $i < strlen($self->val); $i++) {
            $c = substr($self->val, $i, 1);
            if (HVal::cc($c) < 32) {
                throw new Exception("Invalid URI char '" . $self->val . "', char='" . $c . "'");
            }
            if ($c === "`") {
                $s .= "\\";
            }
            $s .= $c;
        }

        return $s;
    }

    public function equals($that)
    {
        return $that instanceof HUri && $this->val === $that->val;
    }

    public static function make($val)
    {
        if (strlen($val) === 0) {
            return static::$EMPTY;
        }
        return new static($val);
    }

    private static $_emptySingletonInstance;
}

HUri::__constructStatic();
