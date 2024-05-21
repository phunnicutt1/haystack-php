<?php
//declare(strict_types=1);
namespace Cxalloy\Haystack;

use \Exception;

/**
 * Translation Notes:
 *
 * 1. Converted JavaScript code to PHP 8.3 syntax.
 * 2. Preserved method and variable names as much as possible.
 * 3. Replaced JavaScript's `module.exports` with PHP's `class` syntax.
 * 4. Replaced JavaScript's `prototype` syntax with PHP's class method definitions.
 * 5. Replaced JavaScript's `function` syntax with PHP's `function` syntax for class methods.
 * 6. Replaced JavaScript's `this` keyword with PHP's `$this` for class method access.
 * 7. Replaced JavaScript's `null` with PHP's `null`.
 * 8. Replaced JavaScript's `undefined` with PHP's `null`.
 * 9. Replaced JavaScript's `throw` statement with PHP's `throw` statement.
 * 10. Replaced JavaScript's `Error` class with PHP's `Exception` class.
 * 11. Replaced JavaScript's string concatenation with PHP's string concatenation operator `.`.
 * 12. Replaced JavaScript's `charCodeAt()` method with PHP's `ord()` function.
 * 13. Replaced JavaScript's `substring()` method with PHP's `substr()` function.
 * 14. Replaced JavaScript's `typeof` operator with PHP's `gettype()` function.
 * 15. Replaced JavaScript's `instanceof` operator with PHP's `instanceof` operator.
 */



abstract class HVal
{
	public static $EMPTY;

    public function toString()
    {
        return $this->toZinc();
    }

    public function compareTo($that)
    {
        return $this->toString() <=> $that->toString();
    }

    public function toZinc()
    {
        throw Exception('must be implemented by subclass!');
    }

    public function toJSON()
    {
        throw Exception('must be implemented by subclass!');
    }

    public function equals($other)
    {
        throw Exception('must be implemented by subclass!');
    }

    public static function startsWith($s, $prefix)
    {
        return substr($s, 0, strlen($prefix)) === $prefix;
    }

    public static function endsWith($s, $suffix)
    {
        return substr($s, -strlen($suffix)) === $suffix;
    }

    public static function typeis($check, $prim, $obj)
    {
        $type = gettype($check);
        return $type === $prim || ($check instanceof $obj);
    }

	/**
	 *
	 *   Factory method to create an instance of the subclass.
	 *   This method should be implemented by each subclass.
	 *
	 *   @param $val
	 *   @return mixed
	 */
	public static function create($val) : mixed
	{
		throw Exception('must be implemented by subclass!');
	}

	public static function empty()
	{
		//static::$EMPTY = new static('');
		throw Exception('must be implemented by subclass!');
	}



	public static function cc($c)
    {
        return ord($c);
    }
}
