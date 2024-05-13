<?php
namespace Cxalloy\HaystackPhp;

use Haystack\Exception;
use HBool;
use HDict;
use HMarker;
use HNum;
use HStr;

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
 * 12. Replaced JavaScript's `typeof` operator with PHP's `gettype()` function.
 * 13. Replaced JavaScript's array literal syntax with PHP's array syntax.
 * 14. Replaced JavaScript's `for...in` loop with PHP's `foreach` loop for iterating over object properties.
 * 15. Replaced JavaScript's `Object.keys()` function with PHP's `array_keys()` function.
 * 16. Replaced JavaScript's string concatenation with PHP's string concatenation operator `.`.
 */



class HDictBuilder
{
    private $map = [];

    public function __construct()
    {
    }

    public function isEmpty()
    {
        return $this->size() === 0;
    }

    public function has($name)
    {
        $t = $this->get($name, false);
        return $t !== null;
    }

    public function missing($name)
    {
        $t = $this->get($name, false);
        return $t === null;
    }

    public function size()
    {
        return count($this->map);
    }

    public function get($name, $checked)
    {
        $val = $this->map[$name] ?? null;
        if ($val !== null) {
            return $val;
        }
        if (!$checked) {
            return null;
        }
        throw new Exception("Unknown Name: " . $name);
    }

    public function toDict()
    {
        if ($this->isEmpty()) {
            return HDict::EMPTY;
        }
        $dict = new HDict\MapImpl($this->map);
        $this->map = null;
        return $dict;
    }

    public function add($name, $val = null, $unit = null)
    {
        if ($val === null) {
            if ($name instanceof HDict) {
                foreach ($name as $entry) {
                    $this->add($entry->getKey(), $entry->getValue());
                }
                return $this;
            } else {
                return $this->add($name, HMarker::VAL);
            }
        } else {
            if (!HDict::isTagName($name)) {
                throw new Exception("Invalid tag name: " . $name);
            }

            // handle primitives
            if (is_numeric($val)) {
                return $this->add($name, HNum::make($val, $unit));
            }
            if (is_string($val)) {
                return $this->add($name, HStr::make($val));
            }
            if (is_bool($val)) {
                return $this->add($name, HBool::make($val));
            }

            $this->map[$name] = $val;
            return $this;
        }
    }
}
