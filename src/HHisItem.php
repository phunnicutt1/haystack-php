<?php
namespace Cxalloy\Haystack;

use \Exception;
use Cxalloy\Haystack\HDict;
use Cxalloy\Haystack\HGrid;

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
 * 13. Replaced JavaScript's `static` keyword with PHP's `static` keyword for static methods.
 * 14. Replaced JavaScript's `arguments.callee` with PHP's `static` keyword for accessing static properties and methods.
 * 15. Replaced JavaScript's `Array.prototype.length` property with PHP's `count()` function.
 * 16. Replaced JavaScript's `for` loop with PHP's `for` loop.
 */



class HHisItem
{
    public $ts;
    public $val;

    public function __construct($ts, $val)
    {
        $this->ts = $ts;
        $this->val = $val;
    }

    public function iterator()
    {
        $cur = -1;
        $hasNext = function () use (&$cur) {
            return $cur < 1;
        };

        $next = function () use (&$cur) {
            ++$cur;
            if ($cur === 0) {
                return new HDict\MapEntry("ts", $this->ts);
            }
            if ($cur === 1) {
                return new HDict\MapEntry("val", $this->val);
            }
            throw new Exception("No Such Element");
        };

        return new class($hasNext, $next) {
            private $hasNext;
            private $next;

            public function __construct($hasNext, $next)
            {
                $this->hasNext = $hasNext;
                $this->next = $next;
            }

            public function hasNext()
            {
                return call_user_func($this->hasNext);
            }

            public function next()
            {
                return call_user_func($this->next);
            }
        };
    }

    public static function make($ts, $val)
    {
        if ($ts === null || $val === null) {
            throw new Exception("ts or val is undefined");
        }
        return new self($ts, $val);
    }

    public static function gridToItems($grid)
    {
        $ts = $grid->col("ts");
        $val = $grid->col("val");
        $items = [];
        for ($i = 0; $i < $grid->numRows(); $i++) {
            $row = $grid->row($i);
            $items[$i] = new self($row->get($ts, true), $row->get($val, false));
        }
        return $items;
    }
}
