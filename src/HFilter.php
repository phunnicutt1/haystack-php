<?php
namespace Haystack;



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
 * 15. Replaced JavaScript's `Array.prototype.slice()` method with PHP's `array_slice()` function.
 * 16. Replaced JavaScript's `Array.prototype.push()` method with PHP's `array_push()` function.
 * 17. Replaced JavaScript's `Array.prototype.length` property with PHP's `count()` function.
 * 18. Replaced JavaScript's `for` loop with PHP's `for` loop.
 * 19. Replaced JavaScript's `parseInt()` function with PHP's `intval()` function.
 * 20. Replaced JavaScript's `parseFloat()` function with PHP's `floatval()` function.
 * 21. Replaced JavaScript's `isNaN()` function with PHP's `is_nan()` function.
 * 22. Replaced JavaScript's `substr()` function with PHP's `substr()` function.
 * 23. Replaced JavaScript's `indexOf()` method with PHP's `strpos()` function.
 * 24. Replaced JavaScript's `lastIndexOf()` method with PHP's `strrpos()` function.
 * 25. Replaced JavaScript's `trim()` method with PHP's `trim()` function.
 * 26. Replaced JavaScript's `split()` method with PHP's `explode()` function.
 * 27. Replaced JavaScript's `charAt()` method with PHP's `substr()` function.
 * 28. Replaced JavaScript's `charCodeAt()` method with PHP's `ord()` function.
 */

use Haystack\Exception;
use HDate;
use HDateTime;
use HNum;
use HRef;
use HStr;
use HVal;

class HFilter
{
    public $string;

    public function __construct()
    {
        $this->string = null;
    }

    public function include($dict, $pather)
    {
        throw new Exception('must be implemented by subclass!');
    }

    public function toStr($dict, $pather)
    {
        throw new Exception('must be implemented by subclass!');
    }

    /**
     * @constructor
     * @extends {HFilter.Path}
     * @param {string} str
     * @param {string[]} names
     */
    private static function PathN($str, $names)
    {
        $obj = new static;
        $obj->string = $str;
        $obj->names = $names;
        return $obj;
    }

    private static function PathN_prototype()
    {
        $proto = new static;
        $proto->size = function () {
            return count($this->names);
        };
        $proto->get = function ($i) {
            return $this->names[$i];
        };
        $proto->__toString = function () {
            return $this->string;
        };
        return $proto;
    }

    /**
     * @constructor
     * @abstract
     * @extends {HFilter}
     * @param {Path} path
     */
    private static function PathFilter($path)
    {
        $obj = new static;
        $obj->path = $path;
        return $obj;
    }

    private static function PathFilter_prototype()
    {
        $proto = new static;
        return $proto;
    }

    /**
     * Number of names in the path.
     * @return {int}
     */
    private static function Path_size()
    {
        throw new Exception('must be implemented by subclass!');
    }

    /**
     * Get name at given index.
     * @param {int} i
     * @return {string}
     */
    private static function Path_get($i)
    {
        throw new Exception('must be implemented by subclass!');
    }

    /**
     * Get string encoding
     * @return {string}
     */
    private static function Path_toString()
    {
        throw new Exception('must be implemented by subclass!');
    }

    /** Construct a new Path from string or throw ParseException */
    public static function Path_make($path)
    {
        try {
            // optimize for common single name case
            $dash = strpos($path, '-');
            if ($dash === false) {
                return new HFilter\Path1($path);
            }

            // parse
            $s = 0;
            $acc = [];
            while (true) {
                $n = substr($path, $s, $dash - $s);
                if (strlen($n) === 0) {
                    throw new Exception();
                }
                $acc[] = $n;
                if (HVal::cc(substr($path, $dash + 1, 1)) !== HVal::cc('>')) {
                    throw new Exception();
                }
                $s = $dash + 2;
                $dash = strpos($path, '-', $s);
                if ($dash === false) {
                    $acc[] = substr($path, $s);
                    break;
                }
            }

            return new HFilter\PathN($path, $acc);
        } catch (Exception $e) {
            throw new Exception("Parse Exception: " . $path);
        }
    }

    /**
     * @constructor
     * @extends {HFilter.PathFilter}
     * @param {Path} p
     */
    private static function Has($p)
    {
        $obj = new static;
        $obj->path = $p;
        return $obj;
    }

    private static function Has_prototype()
    {
        $proto = new static;
        $proto->doInclude = function ($hval) {
            return $hval !== null;
        };
        $proto->toStr = function () {
            return $this->path->__toString();
        };
        return $proto;
    }

    /**
     * @constructor
     * @extends {HFilter.PathFilter}
     * @param {Path} p
     */
    private static function Missing($p)
    {
        $obj = new static;
        $obj->path = $p;
        return $obj;
    }

    private static function Missing_prototype()
    {
        $proto = new static;
        $proto->doInclude = function ($hval) {
            return $hval === null;
        };
        $proto->toStr = function () {
            return "not " . $this->path->__toString();
        };
        return $proto;
    }

    /**
     * @constructor
     * @abstract
     * @extends {HFilter.PathFilter}
     * @param {Path} p
     * @param {HVal} hval
     */
    private static function CmpFilter($p, $hval)
    {
        $obj = new static;
        $obj->path = $p;
        $obj->val = $hval;
        return $obj;
    }

    private static function CmpFilter_prototype()
    {
        $proto = new static;
        $proto->cmpStr = function () {
            throw new Exception('must be implemented by subclass!');
        };
        $proto->toStr = function () {
            return $this->path->__toString() . $this->cmpStr() . $this->val->toZinc();
        };
        return $proto;
    }

    /**
     * @constructor
     * @extends {HFilter.CmpFilter}
     * @param {Path} p
     * @param {HVal} hval
     */
    private static function Eq($p, $hval)
    {
        $obj = new static;
        $obj->path = $p;
        $obj->val = $hval;
        return $obj;
    }

    private static function Eq_prototype()
    {
        $proto = new static;
        $proto->cmpStr = function () {
            return "==";
        };
        $proto->doInclude = function ($hval) {
            return $this->sameType($hval) && $hval->equals($this->val);
        };
        return $proto;
    }

    /**
     * @constructor
     * @extends {HFilter.CmpFilter}
     * @param {Path} p
     * @param {HVal} hval
     */
    private static function Ne($p, $hval)
    {
        $obj = new static;
        $obj->path = $p;
        $obj->val = $hval;
        return $obj;
    }

    private static function Ne_prototype()
    {
        $proto = new static;
        $proto->cmpStr = function () {
            return "!=";
        };
        $proto->doInclude = function ($hval) {
            return $this->sameType($hval) && !$hval->equals($this->val);
        };
        return $proto;
    }

    /**
     * @constructor
     * @extends {HFilter.CmpFilter}
     * @param {Path} p
     * @param {HVal} hval
     */
    private static function Le($p, $hval)
    {
        $obj = new static;
        $obj->path = $p;
        $obj->val = $hval;
        return $obj;
    }

    private static function Le_prototype()
    {
        $proto = new static;
        $proto->cmpStr = function () {
            return "<=";
        };
        $proto->doInclude = function ($hval) {
            return $this->sameType($hval) && $hval->compareTo($this->val) <= 0;
        };
        return $proto;
    }

    /**
     * @constructor
     * @extends {HFilter.CmpFilter}
     * @param {Path} p
     * @param {HVal} hval
     */
    private static function Ge($p, $hval)
    {
        $obj = new static;
        $obj->path = $p;
        $obj->val = $hval;
        return $obj;
    }

    private static function Ge_prototype()
    {
        $proto = new static;
        $proto->cmpStr = function () {
            return ">=";
        };
        $proto->doInclude = function ($hval) {
            return $this->sameType($hval) && $hval->compareTo($this->val) >= 0;
        };
        return $proto;
    }

    /**
     * @constructor
     * @extends {HFilter.CmpFilter}
     * @param {Path} p
     * @param {HVal} hval
     */
    private static function Lt($p, $hval)
    {
        $obj = new static;
        $obj->path = $p;
        $obj->val = $hval;
        return $obj;
    }

    private static function Lt_prototype()
    {
        $proto = new static;
        $proto->cmpStr = function () {
            return "<";
        };
        $proto->doInclude = function ($hval) {
            return $this->sameType($hval) && $hval->compareTo($this->val) < 0;
        };
        return $proto;
    }

    /**
     * @constructor
     * @extends {HFilter.CmpFilter}
     * @param {Path} p
     * @param {HVal} hval
     */
    private static function Gt($p, $hval)
    {
        $obj = new static;
        $obj->path = $p;
        $obj->val = $hval;
        return $obj;
    }

    private static function Gt_prototype()
    {
        $proto = new static;
        $proto->cmpStr = function () {
            return ">";
        };
        $proto->doInclude = function ($hval) {
            return $this->sameType($hval) && $hval->compareTo($this->val) > 0;
        };
        return $proto;
    }

    /**
     * @constructor
     * @extends {HFilter}
     * @param {HFilter} a
     * @param {HFilter} b
     */
    private static function And($a, $b)
    {
        $obj = new static;
        $obj->a = $a;
        $obj->b = $b;
        return $obj;
    }

    private static function And_prototype()
    {
        $proto = new static;
        $proto->include = function ($dict, $pather) {
            return $this->a->include($dict, $pather) && $this->b->include($dict, $pather);
        };
        $proto->toStr = function ($dict, $pather) {
            return $this->a->toStr($dict, $pather) . " and " . $this->b->toStr($dict, $pather);
        };
        return $proto;
    }

    /**
     * @constructor
     * @extends {HFilter}
     * @param {HFilter} a
     * @param {HFilter} b
     */
    private static function Or($a, $b)
    {
        $obj = new static;
        $obj->a = $a;
        $obj->b = $b;
        return $obj;
    }

    private static function Or_prototype()
    {
        $proto = new static;
        $proto->include = function ($dict, $pather) {
            return $this->a->include($dict, $pather) || $this->b->include($dict, $pather);
        };
        $proto->toStr = function ($dict, $pather) {
            return $this->a->toStr($dict, $pather) . " or " . $this->b->toStr($dict, $pather);
        };
        return $proto;
    }

    public static function has($path)
    {
        return self::Has(self::Path_make($path));
    }

    public static function missing($path)
    {
        return self::Missing(self::Path_make($path));
    }

    public static function eq($path, $val)
    {
        return self::Eq(self::Path_make($path), $val);
    }

    public static function ne($path, $val)
    {
        return self::Ne(self::Path_make($path), $val);
    }

    public static function le($path, $val)
    {
        return self::Le(self::Path_make($path), $val);
    }

    public static function ge($path, $val)
    {
        return self::Ge(self::Path_make($path), $val);
    }

    public static function lt($path, $val)
    {
        return self::Lt(self::Path_make($path), $val);
    }

    public static function gt($path, $val)
    {
        return self::Gt(self::Path_make($path), $
