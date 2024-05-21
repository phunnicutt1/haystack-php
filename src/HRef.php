<?php
declare(strict_types=1);
namespace Cxalloy\Haystack;
use \Exception;

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
 * 21. Replaced JavaScript's `Array.prototype.slice()` method with PHP's `array_slice()` function.
 * 22. Replaced JavaScript's `Array.prototype.push()` method with PHP's `array_push()` function.
 * 23. Replaced JavaScript's `Array.prototype.length` property with PHP's `count()` function.
 * 24. Replaced JavaScript's `for` loop with PHP's `for` loop.
 */



class HRef extends HVal
{
    public $val;
    public $display;

    public function __construct($val, $display = null)
    {
        if (!self::isIdChar($val)) {
            throw new Exception("Invalid id: " . $val);
        }
        $this->val = $val;
        $this->display = $display;
    }

    public static function create($val, $display = null)
    {
        return new static($val, $display);
    }

    public static function toIds($val)
    {
        if ($val instanceof HRef) {
            return [$val];
        }
        if (HVal::typeis($val, 'string', 'string')) {
            $ids = explode(" ", $val);
            $acc = [];
            foreach ($ids as $id) {
                if (strlen($id) > 0) {
                    $acc[] = self::create($id);
                }
            }
            return $acc;
        }
        throw new Exception("Invalid id: " . $val);
    }

    public function toZinc()
    {
        $s = "@" . $this->val;
        if ($this->display !== null) {
            $s .= " " . HStr::parseCode($this->display);
        }
        return $s;
    }

    public function toJSON()
    {
        $s = "r:" . $this->val;
        if ($this->display !== null) {
            $s .= " " . HStr::parseCode($this->display);
        }
        return $s;
    }

    public function equals($that)
    {
        return $that instanceof HRef && $this->val === $that->val;
    }

    public function toString()
    {
        return $this->val;
    }

    public function toCode()
    {
        return "@" . $this->val;
    }

    public function dis()
    {
        return $this->display === null ? $this->val : $this->display;
    }

    public static function isIdChar($ch)
    {
        $c = ord($ch);
        return ($c >= 48 && $c <= 57) || // 0-9
            ($c >= 65 && $c <= 90) || // A-Z
            ($c >= 97 && $c <= 122) || // a-z
            $c === 95 || // _
            $c === 45 || // -
            $c === 46; // .
    }

    private static $idChars = [];

    public static function __constructStatic()
    {
        for ($i = 0; $i < 256; $i++) {
            self::$idChars[$i] = self::isIdChar(chr($i));
        }
    }
}

HRef::__constructStatic();
