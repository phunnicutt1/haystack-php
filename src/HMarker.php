<?php
namespace Cxalloy\HaystackPhp;

use HVal;

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
 * 13. Replaced JavaScript's `static` keyword with PHP's `static` keyword for static properties and methods.
 * 14. Replaced JavaScript's `arguments.callee` with PHP's `static` keyword for accessing static properties and methods.
 */


/**
 * HMarker is the singleton value for a marker tag.
 * @see {@link http://project-haystack.org/doc/TagModel#tagKinds|Project Haystack}
 */
class HMarker extends HVal
{
    public static $VAL;

    private function __construct()
    {
        // ensure singleton usage
        if (static::$_singletonInstance) {
            return static::$_singletonInstance;
        }
        static::$_singletonInstance = $this;
    }

    public static function __constructStatic()
    {
        static::$VAL = new static();
    }

    public function equals($that)
    {
        return $that instanceof HMarker && $this === $that;
    }

    public function toString()
    {
        return "marker";
    }

    public function toZinc()
    {
        return "M";
    }

    public function toJSON()
    {
        return "m:";
    }

    private static $_singletonInstance;
}

HMarker::__constructStatic();
